<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit\Mapping;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\Mapping\EntityMapping;
use Robwasripped\Restorm\Mapping\EntityMappingRegister;
use Robwasripped\Restorm\Mapping\Exception\UnknownEntityException;
use Robwasripped\Restorm\Tests\Unit\Fixtures\DummyEntity;

class EntityMappingRegisterTest extends TestCase
{
    private EntityMappingRegister $register;

    protected function setUp(): void
    {
        $this->register = new EntityMappingRegister();
    }

    private function makeMapping(string $entityClass): EntityMapping
    {
        return new EntityMapping($entityClass, null, [], [], null);
    }

    #[Test]
    public function addAndGetEntityMapping(): void
    {
        $mapping = $this->makeMapping(DummyEntity::class);
        $this->register->addEntityMapping($mapping);

        $this->assertSame($mapping, $this->register->getEntityMapping(DummyEntity::class));
    }

    #[Test]
    public function getEntityMappingThrowsUnknownEntityException(): void
    {
        $this->expectException(UnknownEntityException::class);

        $this->register->getEntityMapping('App\Entity\NonExistent');
    }

    #[Test]
    public function unknownEntityExceptionMessageContainsClassName(): void
    {
        try {
            $this->register->getEntityMapping('App\Entity\Ghost');
            $this->fail('Expected UnknownEntityException was not thrown');
        } catch (UnknownEntityException $e) {
            $this->assertStringContainsString('App\Entity\Ghost', $e->getMessage());
        }
    }

    #[Test]
    public function findEntityMappingByExactClass(): void
    {
        $mapping = $this->makeMapping(DummyEntity::class);
        $this->register->addEntityMapping($mapping);

        $found = $this->register->findEntityMapping(DummyEntity::class);

        $this->assertSame($mapping, $found);
    }

    #[Test]
    public function findEntityMappingByObjectInstance(): void
    {
        $mapping = $this->makeMapping(DummyEntity::class);
        $this->register->addEntityMapping($mapping);

        $found = $this->register->findEntityMapping(new DummyEntity());

        $this->assertSame($mapping, $found);
    }

    #[Test]
    public function findEntityMappingReturnsNullForUnknownClass(): void
    {
        $found = $this->register->findEntityMapping('App\Entity\Unknown');

        $this->assertNull($found);
    }

    #[Test]
    public function addingMappingOverwritesPreviousForSameClass(): void
    {
        $first = $this->makeMapping(DummyEntity::class);
        $second = $this->makeMapping(DummyEntity::class);

        $this->register->addEntityMapping($first);
        $this->register->addEntityMapping($second);

        $this->assertSame($second, $this->register->getEntityMapping(DummyEntity::class));
    }
}
