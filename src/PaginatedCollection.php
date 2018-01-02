<?php
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

namespace TheSaleGroup\Restorm;

use TheSaleGroup\Restorm\Query\Query;

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
    private $originalQuery;
    private $previousPage;

    /**
     *
     * @var int|null
     */
    private $expectedTotalItemSum;

    /**
     *
     * @var int|null
     */
    private $expectedPageItemSum;

    /**
     *
     * @var int|null
     */
    private $expectedCurrentPage;

    public function __construct(Query $query, ?int $totalItemSum = null, ?int $pageItemSum = null, ?int $currentPage = null)
    {
        $this->originalQuery = $query;
        $this->previousPage = $query->getPage() ?: 1;

        $this->expectedTotalItemSum = $totalItemSum;
        $this->expectedPageItemSum = $pageItemSum;
        $this->expectedCurrentPage = $currentPage;
    }

    public function next(): void
    {
        if (parent::next() !== false && $this->valid()) {
            return;
        }

        $nextPageQuery = clone $this->originalQuery;
        $nextPageQuery->setPage($this->previousPage + 1);

        $entityCollection = $nextPageQuery->getResult();

        if (!$entityCollection) {
            return;
        }

        $this->previousPage++;

        foreach ($entityCollection as $entity) {
            $this->entities[] = $entity;
        }
    }

    public function getExpectedTotalItemSum(): ?int
    {
        return $this->expectedTotalItemSum;
    }

    public function getExpectedPageItemSum(): ?int
    {
        return $this->expectedPageItemSum;
    }

    public function getExpectedCurrentPage(): ?int
    {
        return $this->expectedCurrentPage;
    }
}
