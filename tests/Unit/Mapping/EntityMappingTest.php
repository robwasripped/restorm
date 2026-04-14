<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit\Mapping;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\Mapping\EntityMapping;

class EntityMappingTest extends TestCase
{
    private function makeMapping(array $properties = [], ?array $paths = null, ?string $repositoryName = null, ?string $connection = 'default'): EntityMapping
    {
        return new EntityMapping(
            'App\Entity\User',
            $repositoryName,
            $properties,
            $paths ?? [EntityMapping::PATH_LIST => '/users', EntityMapping::PATH_GET => '/users/{id}'],
            $connection,
        );
    }

    #[Test]
    public function getEntityClass(): void
    {
        $mapping = $this->makeMapping();

        $this->assertSame('App\Entity\User', $mapping->getEntityClass());
    }

    #[Test]
    public function getRepositoryName(): void
    {
        $mapping = $this->makeMapping(repositoryName: 'App\Repository\UserRepository');

        $this->assertSame('App\Repository\UserRepository', $mapping->getRepositoryName());
    }

    #[Test]
    public function getRepositoryNameCanBeNull(): void
    {
        $mapping = $this->makeMapping();

        $this->assertNull($mapping->getRepositoryName());
    }

    #[Test]
    public function getConnection(): void
    {
        $mapping = $this->makeMapping(connection: 'my_api');

        $this->assertSame('my_api', $mapping->getConnection());
    }

    #[Test]
    public function getConnectionCanBeNull(): void
    {
        $mapping = $this->makeMapping(connection: null);

        $this->assertNull($mapping->getConnection());
    }

    #[Test]
    public function getProperties(): void
    {
        $properties = [
            'id' => ['type' => 'integer', 'identifier' => true],
            'name' => ['type' => 'string'],
        ];
        $mapping = $this->makeMapping(properties: $properties);

        $this->assertSame($properties, $mapping->getProperties());
    }

    #[Test]
    public function getPath(): void
    {
        $mapping = $this->makeMapping(paths: [EntityMapping::PATH_LIST => '/items', EntityMapping::PATH_GET => '/items/{id}']);

        $this->assertSame('/items', $mapping->getpath(EntityMapping::PATH_LIST));
        $this->assertSame('/items/{id}', $mapping->getpath(EntityMapping::PATH_GET));
    }

    #[Test]
    public function getIdentifierName(): void
    {
        $mapping = $this->makeMapping(properties: [
            'id' => ['type' => 'integer', 'identifier' => true],
            'name' => ['type' => 'string'],
        ]);

        $this->assertSame('id', $mapping->getIdentifierName());
    }

    #[Test]
    public function hasIdentifierReturnsTrueWhenIdentifierPresent(): void
    {
        $mapping = $this->makeMapping(properties: [
            'id' => ['type' => 'integer', 'identifier' => true],
        ]);

        $this->assertTrue($mapping->hasIdentifier());
    }

    #[Test]
    public function hasIdentifierReturnsFalseWhenNoIdentifier(): void
    {
        $mapping = $this->makeMapping(properties: [
            'name' => ['type' => 'string'],
        ]);

        $this->assertFalse($mapping->hasIdentifier());
    }

    #[Test]
    public function getIdentifierMappedFromNameReturnsPropertyNameWhenNoMapFrom(): void
    {
        $mapping = $this->makeMapping(properties: [
            'id' => ['type' => 'integer', 'identifier' => true],
        ]);

        $this->assertSame('id', $mapping->getIdentifierMappedFromName());
    }

    #[Test]
    public function getIdentifierMappedFromNameReturnsMapFromWhenSet(): void
    {
        $mapping = $this->makeMapping(properties: [
            'id' => ['type' => 'integer', 'identifier' => true, 'map_from' => 'user_id'],
        ]);

        $this->assertSame('user_id', $mapping->getIdentifierMappedFromName());
    }

    #[Test]
    public function getWritableFieldsExcludesReadOnlyProperties(): void
    {
        $mapping = $this->makeMapping(properties: [
            'id' => ['type' => 'integer', 'identifier' => true, 'read_only' => true],
            'name' => ['type' => 'string'],
        ]);

        $writableFields = $mapping->getWritableFields();

        $this->assertArrayNotHasKey('id', $writableFields);
        $this->assertArrayHasKey('name', $writableFields);
    }

    #[Test]
    public function getWritableFieldsUsesMapFromAsKey(): void
    {
        $mapping = $this->makeMapping(properties: [
            'username' => ['type' => 'string', 'map_from' => 'user_name'],
        ]);

        $writableFields = $mapping->getWritableFields();

        $this->assertArrayHasKey('user_name', $writableFields);
        $this->assertArrayNotHasKey('username', $writableFields);
    }

    #[Test]
    public function pathConstants(): void
    {
        $this->assertSame('list', EntityMapping::PATH_LIST);
        $this->assertSame('get', EntityMapping::PATH_GET);
        $this->assertSame('post', EntityMapping::PATH_POST);
        $this->assertSame('put', EntityMapping::PATH_PUT);
        $this->assertSame('patch', EntityMapping::PATH_PATCH);
        $this->assertSame('delete', EntityMapping::PATH_DELETE);
    }
}
