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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Robwasripped\Restorm\Event\PreQueryEvent;
use Robwasripped\Restorm\Query\Query;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

/**
 * Description of Connection
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class GuzzleConnection implements ConnectionInterface, PaginatedConnectionInterface
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var array
     */
    private $config;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     *
     * @var int|null
     */
    private $totalResultsSum;

    /**
     *
     * @var int|null
     */
    private $currentPageResultsSum;

    /**
     *
     * @var int|null
     */
    private $currentPage;

    public function __construct(array $config, EventDispatcherInterface $eventDispatcher)
    {
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->guzzleClient = new Client([
            'base_uri' => rtrim($config['base_uri'], '/') . '/'
        ]);
    }

    public function handleQuery(Query $query)
    {
        $path = ltrim($query->getPath(), '/');

        $this->eventDispatcher->dispatch(PreQueryEvent::NAME, new PreQueryEvent($query));

        $options = [];

        if ($this->config['filter_mode'] === 'query') {
            $options['query'] = $this->buildQuery($query->getFilter());
        }

        $options['body'] = json_encode($query->getData());

        $options['headers'] = $query->getHeaders();

        if (isset($this->config['pagination_parameters'])) {
            if ($query->getPage()) {
                $options['query'][$this->config['pagination_parameters']['page_param']] = $query->getPage();
            }
            if ($query->getPerPage()) {
                $options['query'][$this->config['pagination_parameters']['per_page_param']] = $query->getPerPage();
            }
        }

        try {
            $response = $this->guzzleClient->request($query->getMethod(), $path, $options);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return null;
            }
            throw $e;
        }

        // Handle pagination data if it's available on this connection
        if (isset($this->config['pagination_data'])) {
            $this->retrievePaginationData($this->config['pagination_data'], $response);
        }

        return json_decode($response->getBody()->getContents());
    }

    private function retrievePaginationData(array $paginationSettings, Response $response)
    {
        if (isset($paginationSettings['query_total_header'])) {
            $this->totalResultsSum = (int) $response->getHeaderLine($paginationSettings['query_total_header']);
        }

        if (isset($paginationSettings['page_total_header'])) {
            $this->currentPageResultsSum = (int) $response->getHeaderLine($paginationSettings['page_total_header']);
        }

        if (isset($paginationSettings['current_page_header'])) {
            $this->currentPage = (int) $response->getHeaderLine($paginationSettings['current_page_header']);
        }
    }

    public function getTotalResultsSum(): ?int
    {
        return $this->totalResultsSum;
    }

    public function getCurrentPageResultsSum(): ?int
    {
        return $this->currentPageResultsSum;
    }

    public function getCurrentPage(): ?int
    {
        return $this->currentPage;
    }

    private function buildQuery(array $filters): array
    {
        return array_map(function($value) {
            $nullValue = $this->config['null_value'] ?? "\000";

            return is_null($value) ? $nullValue : $value;
        }, $filters);
    }
}
