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

namespace Robwasripped\Restorm;

use Robwasripped\Restorm\Query\Query;

/**
 * Description of PaginatedCollection
 *
 * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
 */
class PaginatedCollection extends EntityCollection
{
    /**
     * @var Query
     */
    private readonly Query $originalQuery;

    /**
     * @var bool Whether the original query has been used or not yet.
     */
    private $isInitialized;
    private $previousPage;

    /**
     * @var integer the count of entities in this collection.
     */
    private $count;

    public function __construct(Query $query, bool $isInitialized, ?int $totalItemSum = null, ?int $pageItemSum = null, ?int $currentPage = null)
    {
        $this->originalQuery = $query;
        $this->isInitialized = $isInitialized;
        $this->previousPage = \max(1, $query->getPage());

        parent::__construct([], $totalItemSum, $pageItemSum, $currentPage);
    }

    public function next(): void
    {
        if (parent::next() !== false && $this->valid()) {
            return;
        }

        $nextPageQuery = clone $this->originalQuery;
        $nextPageQuery->setPage($this->previousPage + 1);

        $this->populateEntitiesFromQuery($nextPageQuery);
    }

    public function valid(): bool
    {
        if(!$this->isInitialized) {
            $this->populateEntitiesFromQuery($this->originalQuery);
            $this->isInitialized = true;
        }

        return parent::valid();
    }

    private function populateEntitiesFromQuery(Query $query)
    {
        $entityCollection = $query->getResult();

        if (!$entityCollection) {
            return;
        }

        $this->previousPage++;

        foreach ($entityCollection as $entity) {
            $this->entities[] = $entity;
        }
    }

    /**
     * Get the count of entities.
     *
     * Calling this method will force initialisation of this object and call
     * each page of content.
     *
     * @return integer the count of entities.
     *
     * @author Rob Treacy <erobert.treacy@thesalegroup.co.uk>
     */
    public function count(): int
    {
        // If the count is already set, return it
        if(!is_null($this->count)) {
            return $this->count;
        }

        // Maually create a count by looping through the entities. This will
        // trigger the pagination calls to get the correct count of entities
        $count = 0;
        foreach($this as $entity) {
            $count++;
        }

        $this->count = $count;
        return $count;
    }
}
