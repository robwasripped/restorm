<?php
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

namespace Robwasripped\Restorm\Mapping;

use Robwasripped\Restorm\Mapping\EntityMappingRegister;
use Robwasripped\Restorm\Entity\EntityMetadataRegister;
use Robwasripped\Restorm\Entity\EntityMetadata;
use Robwasripped\Restorm\Normalizer\Normalizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Robwasripped\Restorm\Event\PreBuildEvent;
use Robwasripped\Restorm\Event\PostBuildEvent;

/**
 * Description of EntityBuilder
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class EntityBuilder
{
    /**
     * @var EntityMappingRegister
     */
    private $entityMappingRegister;

    /**
     * @var EntityMetadataRegister
     */
    private $entityMetadataRegister;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EntityMappingRegister $entityMappingRegister, EntityMetadataRegister $entityMetadataRegister, Normalizer $normalizer, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityMappingRegister = $entityMappingRegister;
        $this->entityMetadataRegister = $entityMetadataRegister;
        $this->normalizer = $normalizer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function buildEntity(string $entityClass, $entityData, bool $partialData = false)
    {
        $preBuildEvent = new PreBuildEvent($entityClass, $entityData, $partialData);
        $this->eventDispatcher->dispatch(PreBuildEvent::NAME, $preBuildEvent);

        $entity = $preBuildEvent->getEntity() ?: $this->createEntity($entityClass);

        $this->populateEntity($entity, $preBuildEvent->getData(), $partialData);

        $postBuildEvent = new PostBuildEvent($entity, $entityClass);
        $this->eventDispatcher->dispatch(PostBuildEvent::NAME, $postBuildEvent);

        return $entity;
    }

    private function createEntity(string $entityClass)
    {
        $entityMapping = $this->entityMappingRegister->getEntityMapping($entityClass);

        $entity = new $entityClass;
        $entityMetadata = new EntityMetadata($entity, $entityMapping);
        $this->entityMetadataRegister->addEntityMetadata($entityMetadata);

        return $entity;
    }

    private function populateEntity($entity, \stdClass $data, bool $partialData = false)
    {
        $entityMetadata = $this->entityMetadataRegister->getEntityMetadata($entity);

        return $this->normalizer->denormalize($data, $entityMetadata, $partialData);
    }
}
