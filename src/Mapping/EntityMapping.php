<?php

declare(strict_types=1);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Robwasripped\Restorm\Mapping;

/**
 * Description of EntityMapping
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class EntityMapping
{
    const PATH_LIST = 'list';
    const PATH_GET = 'get';
    const PATH_POST = 'post';
    const PATH_PUT = 'put';
    const PATH_PATCH = 'patch';
    const PATH_DELETE = 'delete';

    /**
     * @var string
     */
    private readonly string $entityClass;

    /**
     * @var string
     */
    private readonly ?string $repositoryName;

    /**
     * @var array
     */
    private readonly array $properties;

    /**
     * @var array
     */
    private readonly ?array $paths;

    /**
     * @var string
     */
    private readonly ?string $connection;

    /**
     * @var string
     */
    private $identifier;

    public function __construct(string $entityClass, ?string $repositoryName, array $properties, ?array $paths, ?string $connection)
    {
        $this->entityClass = $entityClass;
        $this->repositoryName = $repositoryName;
        $this->properties = $properties;
        $this->paths = $paths;
        $this->connection = $connection;
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    public function getRepositoryName(): ?string
    {
        return $this->repositoryName;
    }

    public function getIdentifierName()
    {
        if (!$this->identifier) {

            foreach ($this->properties as $propertyName => $property) {

                if (!isset($property['identifier']) || !$property['identifier'] === true) {
                    continue;
                }

                if ($this->identifier) {
                    throw new \Exception('Cannot have more than one identifier per entity');
                }

                $this->identifier = $propertyName;
            }
        }

        return $this->identifier;
    }
    
    public function hasIdentifier(): bool
    {
        return is_string($this->getIdentifierName());
    }

    public function getIdentifierMappedFromName()
    {
        $identifierName = $this->getIdentifierName();
        
        return $this->properties[$identifierName]['map_from'] ?? $identifierName;
    }

    public function getpath($method)
    {
        return $this->paths[$method];
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getProperties()
    {
        return $this->properties;
    }
    
    public function getWritableFields()
    {
        $writableFields = [];

        foreach ($this->getProperties() as $propertyName => $propertyOptions) {
            if (isset($propertyOptions['read_only']) && $propertyOptions['read_only'] === true) {
                continue;
            }

            $fieldName = $propertyOptions['map_from'] ?? $propertyName;
            $writableFields[$fieldName] = $propertyOptions;
        }

        return $writableFields;
    }
}
