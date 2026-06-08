<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\Entity\EntityMetadata;
use Robwasripped\Restorm\Entity\EntityMetadataRegister;
use Robwasripped\Restorm\Mapping\EntityMapping;
use Robwasripped\Restorm\Tests\Unit\Fixtures\DummyEntity;

class EntityMetadataRegisterTest extends TestCase
{
    private EntityMetadataRegister $register;

    protected function setUp(): void
    {
        $this->register = new EntityMetadataRegister();
    }

    private function makeMetadata(): EntityMetadata
    {
        $entity = new DummyEntity();
        $mapping = new EntityMapping(DummyEntity::class, null, ['id' => ['type' => 'integer']], [], null);

        return new EntityMetadata($entity, $mapping);
    }

    #[Test]
    public function getEntityMetadataReturnsNullForUnregisteredEntity(): void
    {
        $entity = new DummyEntity();

        $this->assertNull($this->register->getEntityMetadata($entity));
    }

    #[Test]
    public function addAndGetEntityMetadata(): void
    {
        $metadata = $this->makeMetadata();
        $this->register->addEntityMetadata($metadata);

        $this->assertSame($metadata, $this->register->getEntityMetadata($metadata->getEntity()));
    }

    #[Test]
    public function hasEntityMetadataReturnsFalseWhenNotAdded(): void
    {
        $metadata = $this->makeMetadata();

        $this->assertFalse($this->register->hasEntityMetadata($metadata));
    }

    #[Test]
    public function hasEntityMetadataReturnsTrueAfterAdding(): void
    {
        $metadata = $this->makeMetadata();
        $this->register->addEntityMetadata($metadata);

        $this->assertTrue($this->register->hasEntityMetadata($metadata));
    }

    #[Test]
    public function removeEntityMetadataRemovesIt(): void
    {
        $metadata = $this->makeMetadata();
        $this->register->addEntityMetadata($metadata);
        $this->register->removeEntityMetadata($metadata);

        $this->assertFalse($this->register->hasEntityMetadata($metadata));
    }

    #[Test]
    public function removeNonExistentMetadataDoesNothing(): void
    {
        $metadata = $this->makeMetadata();

        // Should not throw
        $this->register->removeEntityMetadata($metadata);

        $this->assertFalse($this->register->hasEntityMetadata($metadata));
    }

    #[Test]
    public function multipleMetadataCanBeTracked(): void
    {
        $entity1 = new DummyEntity();
        $entity2 = new DummyEntity();
        $mapping = new EntityMapping(DummyEntity::class, null, [], [], null);

        $meta1 = new EntityMetadata($entity1, $mapping);
        $meta2 = new EntityMetadata($entity2, $mapping);

        $this->register->addEntityMetadata($meta1);
        $this->register->addEntityMetadata($meta2);

        $this->assertSame($meta1, $this->register->getEntityMetadata($entity1));
        $this->assertSame($meta2, $this->register->getEntityMetadata($entity2));
    }
}
