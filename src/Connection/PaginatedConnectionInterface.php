<?php

declare(strict_types=1);
/*
 * The MIT License
 *
 * Copyright 2018 Rob Treacy <robert.treacy@thesalegroup.co.uk>.
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

/**
 * An interface for connections that can handle paginated queries.
 */
interface PaginatedConnectionInterface
{

    /**
     * Return the total number of results available for this query.
     *
     * @return int|null The number of results if the query was successful. Null
     * otherwise
     *
     * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
     */
    public function getTotalResultsSum(): ?int;

    /**
     * Return the number of results from the current query page.
     *
     * @return int|null The number of results if the query was successful. Null
     * otherwise
     *
     * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
     */
    public function getCurrentPageResultsSum(): ?int;
}
