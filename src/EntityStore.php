<?php

declare(strict_types=1);

/*
 * The MIT License
 *
 * Copyright 2017 Rob Treacy <email@roberttreacy.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Robwasripped\Restorm;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Robwasripped\Restorm\Mapping\EntityMappingRegister;
use Robwasripped\Restorm\Event\PreBuildEvent;
use Robwasripped\Restorm\Event\PostBuildEvent;
use Robwasripped\Restorm\Event\PrePersistEvent;
use Robwasripped\Restorm\Event\PopulatedEntityEventInterface;
use Robwasripped\Restorm\Entity\EntityMetadataRegister;
use Robwasripped\Restorm\Entity\EntityMetadata;
use Robwasripped\Restorm\Mapping\Exception\UnknownEntityException;

/**
 * Description of EntityCache
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class EntityStore implements EventSubscriberInterface
{
    private readonly EntityMappingRegister $entityMappingRegister;

    /**
     * @var array
     */
    private $entityData;

    /**
     * @var array
     */
    private $entityInstances;

    private readonly EntityMetadataRegister $entityMetadataRegister;
    private $newEntity;

    public function __construct(EntityMappingRegister $entityMappingRegister, EntityMetadataRegister $entityMetadataRegister)
    {
        $this->entityMappingRegister = $entityMappingRegister;
        $this->entityMetadataRegister = $entityMetadataRegister;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreBuildEvent::class => [
                ['cacheEntityData', -10],
                ['findExistingEntity', -10],
                ['protectPendingEntityChanges', -20],
            ],
            PostBuildEvent::class => [
                ['cacheEntity', 0],
            ],
            PrePersistEvent::class => [
                ['storeNewEntity', 0],
            ]
        ];
    }

    public function cacheEntityData(PreBuildEvent $event)
    {
        // Do not attempt to cache entities that are inline
        if (!$this->entityMappingRegister->getEntityMapping($event->getEntityClass())->hasIdentifier()) {
            return;
        }

        $entityIdentifierName = $this->getEntityIdentifierName($event->getEntityClass());

        $identifier = $event->getData()->$entityIdentifierName;
        $this->entityData[$event->getEntityClass()][$identifier] = $event->getData();
    }

    public function findExistingEntity(PreBuildEvent $event)
    {
        if ($this->newEntity) {
            $event->setEntity($this->newEntity);
            $this->newEntity = null;

            return;
        }

        // Do not attempt to cache entities that are inline
        if (!$this->entityMappingRegister->getEntityMapping($event->getEntityClass())->hasIdentifier()) {
            return;
        }

        $entityIdentifierName = $this->getEntityIdentifierName($event->getEntityClass());

        $identifier = $event->getData()->$entityIdentifierName;

        if (isset($this->entityInstances[$event->getEntityClass()][$identifier])) {
            $event->setEntity($this->entityInstances[$event->getEntityClass()][$identifier]);
        }
    }
    
    public function protectPendingEntityChanges(PreBuildEvent $event)
    {
        // Get the metadata for this entity
        $entityMetadata = $this->entityMetadataRegister->getEntityMetadata($event->getEntity());

        if (!$entityMetadata) {
            return;
        }

        $entityMapping = $entityMetadata->getEntityMapping();

        // Find the fields that aren't the same as the last known state
        $pendingChanges = array_udiff_assoc($entityMetadata->getWritablePropertyValues(), (array) $this->entityData[$event->getEntityClass()][$entityMetadata->getIdentifierValue()], static function($a, $b) {
            return (int) ($a !== $b);
        });

        // Prevent the changes from being overwritten by the build
        foreach ($pendingChanges as $fieldName => $fieldValue) {
            $dataKey = $entityMapping->getProperties()[$fieldName]['map_from'] ?? $fieldName;

            // Only preserve the pending value if it's scalar or an entity. Arrays
            // and other objects need speccing before a choice is made here
            if ($entityMapping->getProperties()[$fieldName]['type'] === 'entity' && $propertyMetadata = $this->entityMetadataRegister->getEntityMetadata($fieldValue)) {
                $fieldValue = $propertyMetadata->getIdentifierValue();
            } elseif (!isset($event->getData()->$dataKey) || !is_scalar($fieldValue)) {
                continue;
            }

            $event->getData()->$dataKey = $fieldValue;
        }
    }

    public function cacheEntity(PopulatedEntityEventInterface $event)
    {
        $entityClass = $event->getEntityClass();

        // Do not attempt to cache entities that are inline
        if (!$this->entityMappingRegister->getEntityMapping($entityClass)->hasIdentifier()) {
            return;
        }

        if (!$this->entityMetadataRegister->getEntityMetadata($event->getEntity())) {
            $entityMetadata = new EntityMetadata($event->getEntity(), $this->entityMappingRegister->getEntityMapping($entityClass));
            $this->entityMetadataRegister->addEntityMetadata($entityMetadata);
        } else {
            $entityMetadata = $this->entityMetadataRegister->getEntityMetadata($event->getEntity());
        }

        $identifier = $entityMetadata->getIdentifierValue();

        if (isset($this->entityInstances[$entityClass][$identifier]) && $this->entityInstances[$entityClass][$identifier] !== $event->getEntity()) {
            throw new \LogicException('this should not happen');
        }

        $this->entityInstances[$entityClass][$identifier] = $event->getEntity();
    }

    public function storeNewEntity(PrePersistEvent $event)
    {
        $entity = $event->getEntity();
        $this->newEntity = $this->getEntityData($entity) ? null : $entity;
    }

    public function getEntityData($entity)
    {
        $entityMapping = $this->entityMappingRegister->findEntityMapping($entity);

        if (!$entityMapping) {
            throw new UnknownEntityException(get_class($entity));
        }

        return $this->entityData[$entityMapping->getEntityClass()][$this->getEntityIdentifier($entity)] ?? null;
    }

    private function getEntityIdentifier($entity)
    {
        $entityMetadata = $this->entityMetadataRegister->getEntityMetadata($entity);

        return $entityMetadata ? $entityMetadata->getIdentifierValue() : null;
    }

    private function getEntityIdentifierName(string $entityClass): string
    {
        $entityMapping = $this->entityMappingRegister->getEntityMapping($entityClass);

        return $entityMapping->getIdentifierMappedFromName();
    }

}
