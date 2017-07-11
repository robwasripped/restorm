<?php
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

/**
 * Description of EntityManager
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class EntityManager
{
    /**
     *
     * @var RepositoryRegister
     */
    protected $repositoryRegister;

    /**
     *
     * @var EntityMappingRegister
     */
    protected $entityMappingRegister;

    /**
     *
     * @var ConnectionRegister
     */
    protected $connectionRegister;

    public function __construct(RepositoryRegister $repositoryRegister, EntityMappingRegister $entityMappingRegister, ConnectionRegister $connectionRegister)
    {
        $this->repositoryRegister = $repositoryRegister;
        $this->entityMappingRegister = $entityMappingRegister;
        $this->connectionRegister = $connectionRegister;
    }

    public function getRepository(string $entityClass): Repository
    {
        $repositoryName = $this->entityMappingRegister->getEntityMapping($entityClass);

        return $this->repositoryRegister->getRepository($repositoryName);
    }

    public function persist($entity)
    {
        
    }
}
