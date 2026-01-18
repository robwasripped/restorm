<?php
/*
 * The MIT License
 *
 * Copyright 2017 Rob Treacy <robert.treacy@thesalegroup.co.uk>.
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

namespace Robwasripped\Restorm\Normalizer\Transformer;

use Robwasripped\Restorm\EntityManager;
use Robwasripped\Restorm\Normalizer\Exception\InvalidValueException;
use Robwasripped\Restorm\Mapping\EntityMapping;
use Robwasripped\Restorm\EntityCollection;
use Robwasripped\Restorm\PaginatedCollection;
use Robwasripped\Restorm\Entity\EntityMetadata;
use Robwasripped\Restorm\Query\QueryBuilder;

/**
 * Description of EntityTransformer
 *
 * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
 */
class EntityTransformer implements AdvancedTransformerInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var mixed
     */
    private $identifierValue;

    public function denormalize($value, array $options)
    {
        if ($value === null && !($options['inverse_field'] ?? false)) {
            return $options['multiple'] ?? false ? new EntityCollection : null;
        }

        $entityClass = $options['entity'];
        $entityMapping = $this->entityManager->getEntityMappingRegister()->getEntityMapping($entityClass);

        if ($options['multiple'] ?? false) {

            if($options['inverse_field'] ?? false) {
                $queryBuilder = new QueryBuilder($this->entityManager);
                $query = $queryBuilder->get($entityClass)
                    ->where([
                        $options['inverse_field'] => $this->identifierValue,
                    ])
                    ->getQuery();

                return new PaginatedCollection($query, false);
            }

            if (!is_array($value)) {
                throw new InvalidValueException('The value passed to the entity transformer must be an array if option "multiple" is true.');
            }

            $entities = array();

            foreach ($value as $entityIdentifierValue) {
                $entity = $this->buildEntity($entityMapping, $entityIdentifierValue, $options['inline'] ?? false);
                $entities[] = $entity;
            }

            return new EntityCollection($entities);
        } else {

            $entity = $this->buildEntity($entityMapping, $value, $options['inline'] ?? false);
            return $entity;
        }
    }

    public function normalize($value, array $options)
    {
        if ($value === null) {
            return null;
        }

        if ($options['multiple'] ?? false) {
            if (!$value instanceof EntityCollection) {
                throw new InvalidValueException('The value passed to the entity transformer must be an EntityCollection instance if option "multiple" is true.');
            }
            $entityValues = array();
            foreach ($value as $entity) {
                $entityValues[] = $options['inline'] ?? false ? $this->getNormalizedEntity($entity) : $this->getEntityIdentifierValue($entity);
            }

            return $entityValues;
        } else {
            return $options['inline'] ?? false ? $this->getNormalizedEntity($value) : $this->getEntityIdentifierValue($value);
        }
    }

    public function setEntityManager(EntityManager $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setEntityIdentifierValue($identifierValue): void
    {
        $this->identifierValue = $identifierValue;
    }

    private function buildEntity(EntityMapping $entityMapping, $entityData, bool $isInlineEntity = false)
    {
        $entityClass = $entityMapping->getEntityClass();
        $identifierName = $entityMapping->getIdentifierMappedFromName();

        $data = $isInlineEntity ? $entityData : (object) [$identifierName => $entityData];

        return $this->entityManager->getEntityBuilder()->buildEntity($entityClass, $data, !$isInlineEntity);
    }

    private function getNormalizedEntity($entity)
    {
        $entityMetadataRegister = $this->entityManager->getEntityMetadataRegister();

        $entityMetadata = $entityMetadataRegister->getEntityMetadata($entity);

        if(!$entityMetadata) {
            $entityMappingRegister = $this->entityManager->getEntityMappingRegister();

            $entityMetadata = new EntityMetadata($entity, $entityMappingRegister->getEntityMapping(get_class($entity)));
            $entityMetadataRegister->addEntityMetadata($entityMetadata);
        }

        return $this->entityManager->getNormalizer()->normalize($entityMetadata);
    }

    //if identifier value does not exist null is now returned to prevent error
    private function getEntityIdentifierValue($entity)
    {
        $entityMetaData = $this->entityManager->getEntityMetadataRegister()->getEntityMetadata($entity);

        return $entityMetaData ? $entityMetaData->getIdentifierValue() : null;
    }
}
