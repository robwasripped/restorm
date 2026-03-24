<?php

declare(strict_types=1);
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

namespace Robwasripped\Restorm\Configuration;

use Symfony\Component\Yaml\Yaml;
use Robwasripped\Restorm\Mapping\EntityMappingRegister;
use Robwasripped\Restorm\Mapping\EntityMapping;
use Robwasripped\Restorm\Connection\ConnectionRegister;
use Robwasripped\Restorm\Normalizer\Transformer\TransformerInterface;
use Robwasripped\Restorm\Connection\GuzzleConnection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Description of Configuration
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class Configuration
{
    /**
     * @var Configuration
     */
    private static $instance;

    /**
     * @var array
     */
    private readonly array $configuration;

    private readonly EntityMappingRegister $entityMappingRegister;

    private readonly ConnectionRegister $connectionRegister;

    /**
     * @var TransformerInterface[]
     */
    private readonly array $dataTransformers;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    private function __construct(array $configuration, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->configuration = $configuration;

        $this->eventDispatcher = $eventDispatcher ?: new EventDispatcher;
        $this->entityMappingRegister = $this->buildEntityMappingRegister();
        $this->connectionRegister = $this->buildConnectionRegister();
        $this->dataTransformers = $this->buildDataTransformers();
    }

    public static function buildFromYaml(string $filePath, EventDispatcherInterface $eventDispatcher = null): Configuration
    {
        if (self::$instance) {
            throw new Exception\ConfigurationAlreadyInitialisedException('A configuration object has already been created.');
        }

        if (!file_exists($filePath)) {
            throw new \Exception(sprintf('The configuration file "%s" cannot be found.', $filePath));
        }

        $configurationArray = Yaml::parse(file_get_contents($filePath), Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE);

        return self::$instance = new Configuration($configurationArray, $eventDispatcher);
    }

    public static function buildFromArray(array $configuration, EventDispatcherInterface $eventDispatcher = null): Configuration
    {
        if (self::$instance) {
            throw new Exception\ConfigurationAlreadyInitialisedException('A configuration object has already been created.');
        }

        return self::$instance = new Configuration($configuration, $eventDispatcher);
    }

    public static function getInstance(): Configuration
    {
        if (!self::$instance) {
            throw new Exception\ConfigurationUnitialisedException('The configuration has not been initialised.');
        }

        return self::$instance;
    }

    public function getEntityMappingRegister(): EntityMappingRegister
    {
        return $this->entityMappingRegister;
    }

    public function getConnectionRegister(): ConnectionRegister
    {
        return $this->connectionRegister;
    }

    public function getDataTransformers(): array
    {
        return $this->dataTransformers;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    private function buildEntityMappingRegister(): EntityMappingRegister
    {
        $entityMappingRegister = new EntityMappingRegister;

        $entityMappingConfigurations = $this->configuration['entity_mappings'] ?? [];

        foreach ($entityMappingConfigurations as $entityClass => $entityConfiguration) {
            $entityMapping = new EntityMapping($entityClass, $entityConfiguration['repository_class'], $entityConfiguration['properties'], $entityConfiguration['paths'], $entityConfiguration['connection']);

            $entityMappingRegister->addEntityMapping($entityMapping);
        }

        return $entityMappingRegister;
    }

    private function buildConnectionRegister(): ConnectionRegister
    {
        $connectionRegister = new ConnectionRegister;

        $connectionConfigurations = $this->configuration['connections'] ?? [];

        foreach ($connectionConfigurations as $connectionName => $connectionConfiguration) {
            $connection = new GuzzleConnection($connectionConfiguration, $this->getEventDispatcher());
            $connectionRegister->registerConnection($connectionName, $connection);
        }

        return $connectionRegister;
    }

    private function buildDataTransformers(): array
    {
        $transformers = [];

        foreach ($this->configuration['transformers'] as $transformerType => $transformerClass) {
            $transformers[$transformerType] = new $transformerClass;
        }

        return $transformers;
    }
}
