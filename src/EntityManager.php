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

declare(strict_types=1);

namespace Robwasripped\Restorm;

use Robwasripped\Restorm\Configuration\Configuration;
use Robwasripped\Restorm\Mapping\EntityMappingRegister;
use Robwasripped\Restorm\Connection\ConnectionRegister;
use Robwasripped\Restorm\Normalizer\Normalizer;
use Robwasripped\Restorm\Entity\Proxy;
use Robwasripped\Restorm\Entity\EntityMetadataRegister;
use Robwasripped\Restorm\Mapping\EntityBuilder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use ProxyManager\Factory\LazyLoadingGhostFactory;
use Robwasripped\Restorm\Event\PrePersistEvent;

/**
 * @author Rob Treacy <email@roberttreacy.com>
 */
class EntityManager
{
    private readonly RepositoryRegister $repositoryRegister;

    private readonly EntityMappingRegister $entityMappingRegister;

    private readonly EntityMetadataRegister $entityMetadataRegister;

    private readonly ConnectionRegister $connectionRegister;

    private readonly EntityBuilder $entityBuilder;

    private readonly EventDispatcherInterface $eventDispatcher;

    private readonly EntityStore $entityStore;

    private readonly LazyLoadingGhostFactory $proxyFactory;

    private readonly Normalizer $normalizer;

    private readonly Proxy $proxy;

    private function __construct(
        EntityMappingRegister $entityMappingRegister,
        ConnectionRegister $connectionRegister,
        array $dataTransformers,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityMappingRegister = $entityMappingRegister;
        $this->connectionRegister = $connectionRegister;
        $this->eventDispatcher = $eventDispatcher;
        $this->repositoryRegister = new RepositoryRegister;
        $this->entityMetadataRegister = new EntityMetadataRegister;
        $this->entityStore = new EntityStore($this->entityMappingRegister, $this->entityMetadataRegister);
        $this->proxyFactory = new LazyLoadingGhostFactory;

        $this->normalizer = new Normalizer($this, $dataTransformers);
        $this->entityBuilder = new EntityBuilder($this->entityMappingRegister, $this->entityMetadataRegister, $this->normalizer, $this->eventDispatcher);
        $this->proxy = new Proxy($this);

        $this->eventDispatcher->addSubscriber($this->entityStore);
        $this->eventDispatcher->addSubscriber($this->proxy);
    }

    public static function createFromConfiguration(Configuration $configuration): self
    {
        return new self(
            $configuration->getEntityMappingRegister(),
            $configuration->getConnectionRegister(),
            $configuration->getDataTransformers(),
            $configuration->getEventDispatcher()
        );
    }

    public function getRepository(object|string $entity): RepositoryInterface
    {
        $entityClass = is_object($entity) ? get_class($entity) : $entity;

        $entityMapping = $this->entityMappingRegister->getEntityMapping($entityClass);
        $repositoryClass = $entityMapping->getRepositoryName();

        if (!$this->repositoryRegister->hasRepository($entityClass)) {

            if (!is_a($repositoryClass, RepositoryInterface::class, true)) {
                throw new \Exception('Repository must extend RepositoryInterface');
            }

            $repository = new $repositoryClass($this, $entityClass);
            $this->repositoryRegister->addRepository($entityClass, $repository);
        }

        return $this->repositoryRegister->getRepository($entityClass);
    }

    public function getEntityMappingRegister(): EntityMappingRegister
    {
        return $this->entityMappingRegister;
    }

    public function getConnectionRegister(): ConnectionRegister
    {
        return $this->connectionRegister;
    }

    public function getEntityMetadataRegister(): EntityMetadataRegister
    {
        return $this->entityMetadataRegister;
    }

    public function getEntityBuilder(): EntityBuilder
    {
        return $this->entityBuilder;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getEntityStore(): EntityStore
    {
        return $this->entityStore;
    }

    public function getProxyFactory(): LazyLoadingGhostFactory
    {
        return $this->proxyFactory;
    }

    public function getNormalizer(): Normalizer
    {
        return $this->normalizer;
    }

    public function persist($entity)
    {
        $entityMapping = $this->entityMappingRegister->findEntityMapping($entity);

        if (!$entityMapping) {
            throw new Mapping\Exception\UnknownEntityException(get_class($entity));
        }

        $this->eventDispatcher->dispatch(new PrePersistEvent($entity, $entityMapping->getEntityClass()));

        $knownState = $this->entityStore->getEntityData($entity);

        $queryBuilder = new Query\QueryBuilder($this);

        if ($knownState) {
            // Filter only mapped fields
            $entityMetadata = $this->entityMetadataRegister->getEntityMetadata($entity);
            $writableFields = $entityMapping->getWritableFields();

            // Get normalised entity
            $currentState = (array) $this->normalizer->normalize($entityMetadata);
            $mappedCurrentState = array_intersect_key($currentState, $writableFields);

            // Diff arrays to find changes
            $queryData = array_udiff_assoc($mappedCurrentState, (array) $knownState, static function($current, $known) {

                if (gettype($current) !== gettype($known)) {
                    return 1;
                } elseif (is_object($current)) {
                    return (int) ($current != $known);
                } else {
                    return (int) ($current !== $known);
                }
            });

            if (!$queryData) {
                return;
            }

            $queryBuilder->patch($entity);
        } else {
            $entityMetadata = new Entity\EntityMetadata($entity, $entityMapping);
            $this->entityMetadataRegister->addEntityMetadata($entityMetadata);

            $queryData = (array) $this->normalizer->normalize($entityMetadata);

            $queryBuilder->post($entity);
        }

        // Build query and set array as body of PATCH request
        $queryBuilder
            ->setData($queryData)
            ->getQuery()
            ->getResult();
    }

    public function remove($entity)
    {
        $entityMetadata = $this->entityMetadataRegister->getEntityMetadata($entity);

        if(!$entityMetadata) {
            throw new Exception\UnrecognisedEntityException($entity);
        }

        $queryBuilder = new Query\QueryBuilder($this);
        $queryBuilder->delete($entity)
            ->getQuery()
            ->getResult();

        $this->entityMetadataRegister->removeEntityMetadata($entityMetadata);
    }
}
