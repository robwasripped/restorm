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

namespace Robwasripped\Restorm;

/**
 * Description of EntityCollection
 *
 * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
 */
class EntityCollection implements \ArrayAccess, \Iterator, \Countable
{
    /**
     *
     * @var array
     */
    protected $entities;

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

    public function __construct(array $entities = array(), ?int $totalItemSum = null, ?int $pageItemSum = null, ?int $currentPage = null)
    {
        $this->entities = $entities;

        $this->expectedTotalItemSum = $totalItemSum;
        $this->expectedPageItemSum = $pageItemSum;
        $this->expectedCurrentPage = $currentPage;
    }

    public function addEntity($entity)
    {
        if (!in_array($entity, $this->entities)) {
            $this->entities[] = $entity;
        }
    }
    
    public function removeEntity($entity)
    {
        if($key = array_search($entity, $this->entities, true)) {
            $this->offsetUnset($key);
        }
    }

    public function current()
    {
        return current($this->entities);
    }

    public function key()
    {
        return key($this->entities);
    }

    public function next(): void
    {
        next($this->entities);
    }

    public function offsetExists($offset): bool
    {
        return key_exists($offset, $this->entities);
    }

    public function offsetGet($offset)
    {
        return $this->entities[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->entities[] = $value;
        } else {
            $this->entities[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->entities[$offset]);
    }

    public function rewind(): void
    {
        reset($this->entities);
    }

    public function valid(): bool
    {
        return $this->offsetExists($this->key());
    }

    public function isEmpty(): bool
    {
        return empty($this->entities);
    }

    public function contains($entity): bool
    {
        return in_array($entity, $this->entities);
    }

    public function count(): int
    {
        return count($this->entities);
    }

    public function toArray(): array
    {
        return $this->entities;
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
