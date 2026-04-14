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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Robwasripped\Restorm\EntityManager;
use ProxyManager\Proxy\GhostObjectInterface;
use Robwasripped\Restorm\Event\PreBuildEvent;


/**
 * Description of Proxy
 *
 * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
 */
class Proxy implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManager $entityManager
        )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreBuildEvent::class => [
                ['buildProxy', -20],
            ],
        ];
    }

    public function buildProxy(PreBuildEvent $event)
    {
        if (!$event->isPartialData() || $event->getEntity()) {
            return;
        }

        $proxyOptions = [
            'skippedProperties' => [],
        ];

        $entityMapping = $this->entityManager->getEntityMappingRegister()->getEntityMapping($event->getEntityClass());
        $mappedProperties = $entityMapping->getProperties();

        $reflectionProperties = (new \ReflectionClass($event->getEntityClass()))->getProperties(\ReflectionProperty::IS_PRIVATE);
        $properties = array_map(
            static fn(\ReflectionProperty $reflectionProperty) => $reflectionProperty->getName(),
            $reflectionProperties
        );

        foreach ($mappedProperties as $propertyName => $propertyOptions) {
            $dataPropertyName = $propertyOptions['map_from'] ?? $propertyName;
            if (!property_exists($event->getData(), $dataPropertyName)) {

                $propertyKey = array_search($propertyName, $properties, true);

                // Remove properties from the skipped properties list if they're
                // not already set or if they're an inverse_field
                if (is_int($propertyKey) && !($propertyOptions['inverse_field'] ?? false)) {
                    unset($properties[$propertyKey]);
                }

                continue;
            }

            $proxyOptions['skippedProperties'][] = $this->getPropertyProxyName($event->getEntityClass(), $dataPropertyName);
        }

        foreach ($properties as $property) {
            $proxyOptions['skippedProperties'][] = $this->getPropertyProxyName($event->getEntityClass(), $property);
        }


        $initializer = function (
            GhostObjectInterface $ghostObject,
            string $method,
            array $parameters,
            & $initializer,
            array $properties
            ) use ($event) {
            $initializer = null;

            $identifierValue = $this->entityManager->getEntityMetadataRegister()->getEntityMetadata($ghostObject)->getIdentifierValue();
            $this->entityManager->getRepository($event->getEntityClass())->findOne($identifierValue);

            return true;
        };

        $proxyEntity = $this->entityManager->getProxyFactory()->createProxy($event->getEntityClass(), $initializer, $proxyOptions);
        $event->setEntity($proxyEntity);

        $entityMetadata = new EntityMetadata($proxyEntity, $entityMapping);
        $this->entityManager->getEntityMetadataRegister()->addEntityMetadata($entityMetadata);
    }

    private function getPropertyProxyName(string $entityClass, string $property): string
    {
        $reflectionProperty = new \ReflectionProperty($entityClass, $property);

        return $reflectionProperty->getDeclaringClass()->getName() === $entityClass ? sprintf("\0%s\0%s", $entityClass, $property) : sprintf("\0*\0%s", $property);
    }
}
