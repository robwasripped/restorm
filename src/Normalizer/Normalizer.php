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

namespace Robwasripped\Restorm\Normalizer;

use Robwasripped\Restorm\EntityManager;
use Robwasripped\Restorm\Normalizer\Transformer\TransformerInterface;
use Robwasripped\Restorm\Normalizer\Transformer\AdvancedTransformerInterface;
use Robwasripped\Restorm\Entity\EntityMetadata;
use Robwasripped\Restorm\PaginatedCollection;

/**
 * Description of Normalizer
 *
 * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
 */
class Normalizer
{

    public function __construct(EntityManager $entityManager, /**
     * @var TransformerInterface[]
     */
    private array $transformers)
    {
        foreach ($this->transformers as $transformer) {
            if (!$transformer instanceof AdvancedTransformerInterface) {
                continue;
            }

            $transformer->setEntityManager($entityManager);
        }
    }

    public function normalize(EntityMetadata $entityMetadata): \stdClass
    {
        $normalizedEntity = new \stdClass;

        $writableProperties = $entityMetadata->getWritableProperties();

        foreach ($writableProperties as $propertyName => $propertyOptions) {

            // Ignore inverse fields
            if ($propertyOptions['inverse_field'] ?? false) {
                continue;
            }

            $mapFrom = $propertyOptions['map_from'] ?? $propertyName;

            $propertyType = $propertyOptions['type'];
            $propertyValue = $entityMetadata->getPropertyValue($propertyName);

            if ($propertyType === 'object') {

                $normalizedValue = $this->normalizeObject($propertyValue);
            } else {

                $transformer = $this->getTransformer($propertyType);

                $normalizedValue = $transformer->normalize($propertyValue, $propertyOptions);
            }

            $normalizedEntity->$mapFrom = $normalizedValue;
        }

        return $normalizedEntity;
    }

    public function denormalize(\stdClass $data, EntityMetadata $entityMetadata, bool $partialData = false)
    {
        $identifierName = $entityMetadata->getEntityMapping()->getIdentifierName();

        foreach ($entityMetadata->getEntityMapping()->getProperties() as $propertyName => $propertyOptions) {
            $mapFrom = $propertyOptions['map_from'] ?? $propertyName;

            if (!property_exists($data, $mapFrom) && !($propertyOptions['inverse_field'] ?? false)) {

                if ($partialData) {
                    continue;
                } else {
                    throw new Exception\MissingPropertyException(sprintf('Property "%s" was not available in the data.', $mapFrom));
                }
            }

            // Do not overwrite existing inverse field properties
            if (($propertyOptions['inverse_field'] ?? false) && $entityMetadata->getPropertyValue($propertyName) instanceof PaginatedCollection) {
                continue;
            }

            $propertyType = $propertyOptions['type'];
            $propertyValue = ($propertyOptions['inverse_field'] ?? false) ? null : $data->$mapFrom;

            if ($propertyType === 'object') {

                $denormalizedValue = $this->denormalizeObject($propertyValue);
            } else {

                $transformer = $this->getTransformer($propertyType);

                // The data doesn't necessarily have an identifier (eg an inline
                // object) so only set this value if there is one
                if($transformer instanceof AdvancedTransformerInterface && $identifierName) {
                    $transformer->setEntityIdentifierValue($data->$identifierName);
                }

                $denormalizedValue = $transformer->denormalize($propertyValue, $propertyOptions);
            }

            $entityMetadata->setPropertyValue($propertyName, $denormalizedValue);
        }

        return $entityMetadata->getEntity();
    }

    private function getTransformer($type): TransformerInterface
    {
        if (!array_key_exists($type, $this->transformers)) {
            throw new Exception\UnknownTransformerException(sprintf('No transformer for type "%s" exists.', $type));
        }

        return $this->transformers[$type];
    }

    private function inferType($value): string
    {
        switch ($type = gettype($value)) {
            case 'boolean':
            case 'integer':
            case 'string':
                return $type;
            case 'double':
                return 'float';
            case 'object':
            case 'array':
                return 'object';
            case 'NULL':
                return 'string';
            default:
                throw new Exception\UnknownPropertyTypeException;
        }
    }

    private function normalizeObject($value)
    {
        if ($value === null) {
            return null;
        } else {
            $normalizedValue = new \stdClass;

            foreach ($value as $dataName => $dataValue) {
                $dataType = $this->inferType($dataValue);

                if ($dataType === 'object') {
                    $normalizedValue->$dataName = $this->normalizeObject($dataValue);
                } else {

                    $transformer = $this->getTransformer($dataType);
                    $normalizedDataValue = $transformer->normalize($dataValue, []);

                    $normalizedValue->$dataName = $normalizedDataValue;
                }
            }
        }

        return $normalizedValue;
    }

    private function denormalizeObject($value)
    {
        if ($value === null) {
            return null;
        } else {
            $denormalizedValue = [];

            foreach ($value as $dataName => $dataValue) {
                $dataType = $this->inferType($dataValue);

                if ($dataType === 'object') {
                    $denormalizedValue[$dataName] = $this->denormalizeObject($dataValue);
                } else {

                    $transformer = $this->getTransformer($dataType);
                    $denormalizedDataValue = $transformer->denormalize($dataValue, []);

                    $denormalizedValue[$dataName] = $denormalizedDataValue;
                }
            }
        }

        return $denormalizedValue;
    }

}
