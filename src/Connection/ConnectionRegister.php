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

namespace Robwasripped\Restorm\Connection;

use Robwasripped\Restorm\Connection\ConnectionInterface;

/**
 * Description of ConnectionManager
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class ConnectionRegister
{
    private $connections = array();

    public function registerConnection(string $connectionName, ConnectionInterface $connection)
    {
        $this->connections[$connectionName] = $connection;
    }

    /**
     * 
     * @param string $connectionName
     * @return ConnectionInterface[]
     * 
     */
    public function getConnections(string $connectionName): array
    {
        $connections = array();
        if (array_key_exists('_cache', $this->connections)) {
            $connections[] = $this->connections['_cache'];
        }

        $connections[] = $this->connections[$connectionName];

        return $connections;
    }
}
