<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\Entity\EntityMetadata;
use Robwasripped\Restorm\Mapping\EntityMapping;
use Robwasripped\Restorm\Tests\Unit\Fixtures\DummyEntity;

class EntityMetadataTest extends TestCase
{
    private function makeMapping(array $properties): EntityMapping
    {
        return new EntityMapping(DummyEntity::class, null, $properties, [], null);
    }

    #[Test]
    public function getEntityReturnsTheEntity(): void
    {
        $entity = new DummyEntity();
        $mapping = $this->makeMapping([]);
        $metadata = new EntityMetadata($entity, $mapping);

        $this->assertSame($entity, $metadata->getEntity());
    }

    #[Test]
    public function getPropertyValueReadsPrivateProperty(): void
    {
        $entity = new DummyEntity();

        // Set the property via a setter fixture — but DummyEntity has no setters.
        // Use reflection to pre-set a value, then verify EntityMetadata reads it correctly.
        $reflection = new \ReflectionClass($entity);
        $prop = $reflection->getProperty('name');
        $prop->setAccessible(true);
        $prop->setValue($entity, 'Alice');

        $mapping = $this->makeMapping(['name' => ['type' => 'string']]);
        $metadata = new EntityMetadata($entity, $mapping);

        $this->assertSame('Alice', $metadata->getPropertyValue('name'));
    }

    #[Test]
    public function setPropertyValueWritesPrivateProperty(): void
    {
        $entity = new DummyEntity();
        $mapping = $this->makeMapping(['name' => ['type' => 'string']]);
        $metadata = new EntityMetadata($entity, $mapping);

        $metadata->setPropertyValue('name', 'Bob');

        $reflection = new \ReflectionClass($entity);
        $prop = $reflection->getProperty('name');
        $prop->setAccessible(true);

        $this->assertSame('Bob', $prop->getValue($entity));
    }

    #[Test]
    public function getIdentifierValueReadsIdentifierProperty(): void
    {
        $entity = new DummyEntity();

        $reflection = new \ReflectionClass($entity);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($entity, 42);

        $mapping = $this->makeMapping([
            'id' => ['type' => 'integer', 'identifier' => true],
        ]);
        $metadata = new EntityMetadata($entity, $mapping);

        $this->assertSame(42, $metadata->getIdentifierValue());
    }

    #[Test]
    public function getPropertiesReturnsPropertyNames(): void
    {
        $mapping = $this->makeMapping([
            'id' => ['type' => 'integer', 'identifier' => true],
            'name' => ['type' => 'string'],
        ]);
        $metadata = new EntityMetadata(new DummyEntity(), $mapping);

        $this->assertSame(['id', 'name'], $metadata->getProperties());
    }

    #[Test]
    public function getWritablePropertyValuesExcludesReadOnlyFields(): void
    {
        $entity = new DummyEntity();

        $reflection = new \ReflectionClass($entity);
        $nameProp = $reflection->getProperty('name');
        $nameProp->setAccessible(true);
        $nameProp->setValue($entity, 'Charlie');

        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($entity, 99);

        $mapping = $this->makeMapping([
            'id' => ['type' => 'integer', 'identifier' => true, 'read_only' => true],
            'name' => ['type' => 'string'],
        ]);
        $metadata = new EntityMetadata($entity, $mapping);

        $writableValues = $metadata->getWritablePropertyValues();

        $this->assertArrayNotHasKey('id', $writableValues);
        $this->assertArrayHasKey('name', $writableValues);
        $this->assertSame('Charlie', $writableValues['name']);
    }
}
