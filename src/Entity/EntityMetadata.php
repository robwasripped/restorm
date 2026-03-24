<?php

declare(strict_types=1);
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

namespace Robwasripped\Restorm\Entity;

use Robwasripped\Restorm\Mapping\EntityMapping;

/**
 * Description of EntityMetadata
 *
 * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
 */
class EntityMetadata
{
    private readonly object $entity;

    private readonly EntityMapping $entityMapping;

    private readonly \ReflectionClass $entityReflection;

    public function __construct($entity, EntityMapping $entityMapping)
    {
        $this->entity = $entity;
        $this->entityMapping = $entityMapping;

        $this->entityReflection = new \ReflectionClass($entityMapping->getEntityClass());
    }

    public function getIdentifierValue()
    {
        $identifierName = $this->entityMapping->getIdentifierName();

        return $this->getPropertyValue($identifierName);
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setPropertyValue($propertyName, $value)
    {
        $reflectionProperty = $this->entityReflection->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->entity, $value);
    }

    public function getPropertyValue($propertyName)
    {
        $reflectionProperty = $this->entityReflection->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($this->entity);
    }

    public function getProperties(): array
    {
        $properties = $this->entityMapping->getProperties();

        return array_keys($properties);
    }

    public function getWritablePropertyValues(): array
    {
        $writablePropertyValues = [];

        foreach ($this->getWritableProperties() as $propertyName => $propertyOptions) {
            $writablePropertyValues[$propertyName] = $this->getPropertyValue($propertyName);
        }

        return $writablePropertyValues;
    }

    public function getWritableProperties(): array
    {
        $writableProperties = [];

        foreach ($this->entityMapping->getProperties() as $propertyName => $propertyOptions) {
            if (isset($propertyOptions['read_only']) && $propertyOptions['read_only'] === true) {
                continue;
            }

            $writableProperties[$propertyName] = $propertyOptions;
        }

        return $writableProperties;
    }

    public function getEntityMapping(): EntityMapping
    {
        return $this->entityMapping;
    }
}
