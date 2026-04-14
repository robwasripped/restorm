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

namespace Robwasripped\Restorm;


use Robwasripped\Restorm\Query\QueryBuilder;

/**
 * Description of Repository
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class EntityRepository implements RepositoryInterface
{
    public function __construct(protected readonly EntityManager $entityManager, protected readonly string $entityClass)
    {
    }

    public function find(array $filters, $page = 1, $limit = 0)
    {
        return $this->getQueryBuilder()
            ->get($this->entityClass)
            ->where($filters)
            ->page($page, $limit)
            ->getQuery()
            ->getResult();
    }

    public function findOne($filter)
    {
        $entityMapping = $this->entityManager->getEntityMappingRegister()->getEntityMapping($this->entityClass);
        
        // Filter can be either an ID or an array of filters. Ensure that it's
        // an array for the where statement
        $query = is_array($filter) ? $filter : [$entityMapping->getIdentifierMappedFromName() => $filter];
        
        return $this->getQueryBuilder()
            ->get($this->entityClass)
            ->where($query)
            ->getQuery()
            ->getSingleResult();
    }

    public function findAll()
    {
        return $this->getQueryBuilder()
            ->get($this->entityClass)
            ->getQuery()
            ->getResult();
    }
    
    public function remove(array $filters)
    {
        return $this->getQueryBuilder()
            ->delete($this->entityClass)
            ->where($filters)
            ->getQuery()
            ->getResult();
    }
    
    protected function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->entityManager);
    }
}
