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

namespace Robwasripped\Restorm\Query;

use Robwasripped\Restorm\Connection\ConnectionInterface;
use Robwasripped\Restorm\Mapping\EntityBuilder;
use Robwasripped\Restorm\EntityCollection;
use Robwasripped\Restorm\PaginatedCollection;
use Robwasripped\Restorm\Connection\PaginatedConnectionInterface;

/**
 * Description of Query
 *
 * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
 */
class Query {

    // Method constants
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    // Sort constants
    const SORT_ASCENDING = 'ASC';
    const SORT_DESCENDING = 'DESC';

    /**
     * @var string
     */
    private $method;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var array
     */
    private $filter;

    /**
     * @var array
     */
    private $sort;

    /**
     * @var int
     */
    private $page;

    /**
     * @var int
     */
    private $perPage;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var ConnectionInterface[]
     */
    private readonly array $connections;

    private readonly EntityBuilder $entityBuilder;

    /**
     * @var string
     */
    private readonly string $entityClass;

    public function __construct(array $connections, EntityBuilder $entityBuilder, string $entityClass, string $path, string $method, $data, array $filter = [], int $page = 0, int $perPage = 0, array $sort = []) {
        $this->connections = $connections;
        $this->entityBuilder = $entityBuilder;
        $this->entityClass = $entityClass;
        $this->setPath($path);
        $this->setMethod($method);
        $this->setData($data);
        $this->setFilter($filter);
        $this->setPage($page);
        $this->setPerPage($perPage);
        $this->setSort($sort);
    }

    public function getResult(bool $single = false) {
        foreach ($this->connections as $connection) {
            $result = $connection->handleQuery($this);

            if (is_null($result)) {
                continue;
            }

            if ($single) {
                $entityData = is_array($result) ? reset($result) : $result;

                // If the data was an empty array, give up
                if ($entityData === false) {
                    continue;
                }

                return $this->buildEntity($entityData);
            } else {
                $expectedTotalSum = $connection instanceof PaginatedConnectionInterface ? $connection->getTotalResultsSum() : null;
                $expectedPageSum = $connection instanceof PaginatedConnectionInterface ? $connection->getCurrentPageResultsSum() : null;
                $expectedCurrentPage = $connection instanceof PaginatedConnectionInterface ? $connection->getCurrentPage() : null;

                $entityCollection = $this->page == 0 || ($this->perPage == 0 && $this->page == 0) ? new PaginatedCollection($this, true, $expectedTotalSum, $expectedPageSum, $expectedCurrentPage) : new EntityCollection([], $expectedTotalSum, $expectedPageSum, $expectedCurrentPage);

                $entityData = is_array($result) ? $result : [$result];

                foreach ($entityData as $singleResult) {
                    $entityCollection[] = $this->buildEntity($singleResult);
                }

                return $entityCollection;
            }
        }

        return $single ? null : new EntityCollection;
    }

    public function getSingleResult() {
        return $this->getResult(true);
    }

    private function buildEntity($entityData) {
        return $this->entityBuilder->buildEntity($this->entityClass, $entityData);
    }

    public function getMethod() {
        return $this->method;
    }

    public function getData() {
        return $this->data;
    }

    public function getFilter() {
        return $this->filter;
    }

    public function getSort() {
        return $this->sort;
    }

    public function getPage() {
        return $this->page;
    }

    public function getPerPage() {
        return $this->perPage;
    }

    public function getPath() {
        return $this->path;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function setFilter($filter) {
        $this->filter = $filter;
    }

    public function setSort($sort) {
        $this->sort = $sort;
    }

    public function setPage($page) {
        $this->page = $page;
    }

    public function setPerPage($perPage) {
        $this->perPage = $perPage;
    }

    public function setPath($path) {
        $this->path = $path;
    }

    public function setHeader($key, $value) {
        $this->headers[$key] = $value;
    }

    public function getEntityClass() {
        return $this->entityClass;
    }

}
