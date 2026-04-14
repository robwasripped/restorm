<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\EntityCollection;

class EntityCollectionTest extends TestCase
{
    #[Test]
    public function emptyCollectionHasZeroCount(): void
    {
        $collection = new EntityCollection();

        $this->assertCount(0, $collection);
    }

    #[Test]
    public function constructWithEntitiesPopulatesCollection(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $collection = new EntityCollection([$entity1, $entity2]);

        $this->assertCount(2, $collection);
    }

    #[Test]
    public function isEmptyReturnsTrueForEmptyCollection(): void
    {
        $collection = new EntityCollection();

        $this->assertTrue($collection->isEmpty());
    }

    #[Test]
    public function isEmptyReturnsFalseWhenEntitiesExist(): void
    {
        $collection = new EntityCollection([new \stdClass()]);

        $this->assertFalse($collection->isEmpty());
    }

    #[Test]
    public function addEntityIncreasesCount(): void
    {
        $collection = new EntityCollection();
        $collection->addEntity(new \stdClass());

        $this->assertCount(1, $collection);
    }

    #[Test]
    public function addEntityDoesNotAddDuplicate(): void
    {
        $entity = new \stdClass();
        $collection = new EntityCollection();
        $collection->addEntity($entity);
        $collection->addEntity($entity);

        $this->assertCount(1, $collection);
    }

    #[Test]
    public function removeEntityDecreasesCount(): void
    {
        $entity = new \stdClass();
        $collection = new EntityCollection([$entity]);
        $collection->removeEntity($entity);

        $this->assertCount(0, $collection);
    }

    #[Test]
    public function removeNonExistentEntityDoesNothing(): void
    {
        $entity = new \stdClass();
        $other = new \stdClass();
        $collection = new EntityCollection([$entity]);
        $collection->removeEntity($other);

        $this->assertCount(1, $collection);
    }

    #[Test]
    public function containsReturnsTrueForAddedEntity(): void
    {
        $entity = new \stdClass();
        $collection = new EntityCollection([$entity]);

        $this->assertTrue($collection->contains($entity));
    }

    #[Test]
    public function containsReturnsFalseForMissingEntity(): void
    {
        $collection = new EntityCollection([new \stdClass()]);

        $this->assertFalse($collection->contains(new \stdClass()));
    }

    #[Test]
    public function toArrayReturnsAllEntities(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $collection = new EntityCollection([$entity1, $entity2]);

        $this->assertSame([$entity1, $entity2], $collection->toArray());
    }

    #[Test]
    public function arrayAccessOffsetExists(): void
    {
        $entity = new \stdClass();
        $collection = new EntityCollection([$entity]);

        $this->assertTrue(isset($collection[0]));
        $this->assertFalse(isset($collection[1]));
    }

    #[Test]
    public function arrayAccessOffsetGet(): void
    {
        $entity = new \stdClass();
        $collection = new EntityCollection([$entity]);

        $this->assertSame($entity, $collection[0]);
    }

    #[Test]
    public function arrayAccessOffsetSet(): void
    {
        $entity = new \stdClass();
        $collection = new EntityCollection();
        $collection[] = $entity;

        $this->assertCount(1, $collection);
        $this->assertTrue($collection->contains($entity));
    }

    #[Test]
    public function arrayAccessOffsetUnset(): void
    {
        $entity = new \stdClass();
        $collection = new EntityCollection([$entity]);
        unset($collection[0]);

        $this->assertCount(0, $collection);
    }

    #[Test]
    public function iterationCoversAllEntities(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $entity3 = new \stdClass();
        $collection = new EntityCollection([$entity1, $entity2, $entity3]);

        $iterated = [];
        foreach ($collection as $entity) {
            $iterated[] = $entity;
        }

        $this->assertSame([$entity1, $entity2, $entity3], $iterated);
    }

    #[Test]
    public function collectionCanBeIteratedMultipleTimes(): void
    {
        $entity = new \stdClass();
        $collection = new EntityCollection([$entity]);

        $first = [];
        foreach ($collection as $e) {
            $first[] = $e;
        }

        $second = [];
        foreach ($collection as $e) {
            $second[] = $e;
        }

        $this->assertSame($first, $second);
    }
}
