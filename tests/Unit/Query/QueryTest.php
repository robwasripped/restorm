<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit\Query;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\Mapping\EntityBuilder;
use Robwasripped\Restorm\Query\Query;

class QueryTest extends TestCase
{
    private function makeQuery(
        string $method = Query::METHOD_GET,
        string $path = '/users',
        mixed $data = null,
        array $filter = [],
        int $page = 0,
        int $perPage = 0,
        array $sort = [],
    ): Query {
        $entityBuilder = $this->createMock(EntityBuilder::class);

        return new Query(
            connections: [],
            entityBuilder: $entityBuilder,
            entityClass: 'App\Entity\User',
            path: $path,
            method: $method,
            data: $data,
            filter: $filter,
            page: $page,
            perPage: $perPage,
            sort: $sort,
        );
    }

    #[Test]
    public function methodConstants(): void
    {
        $this->assertSame('GET', Query::METHOD_GET);
        $this->assertSame('POST', Query::METHOD_POST);
        $this->assertSame('PUT', Query::METHOD_PUT);
        $this->assertSame('PATCH', Query::METHOD_PATCH);
        $this->assertSame('DELETE', Query::METHOD_DELETE);
    }

    #[Test]
    public function sortConstants(): void
    {
        $this->assertSame('ASC', Query::SORT_ASCENDING);
        $this->assertSame('DESC', Query::SORT_DESCENDING);
    }

    #[Test]
    public function getMethodReturnsConstructedMethod(): void
    {
        $query = $this->makeQuery(method: Query::METHOD_POST);

        $this->assertSame(Query::METHOD_POST, $query->getMethod());
    }

    #[Test]
    public function getPathReturnsConstructedPath(): void
    {
        $query = $this->makeQuery(path: '/items/42');

        $this->assertSame('/items/42', $query->getPath());
    }

    #[Test]
    public function getEntityClassReturnsConstructedClass(): void
    {
        $query = $this->makeQuery();

        $this->assertSame('App\Entity\User', $query->getEntityClass());
    }

    #[Test]
    public function getDataReturnsConstructedData(): void
    {
        $data = ['key' => 'value'];
        $query = $this->makeQuery(data: $data);

        $this->assertSame($data, $query->getData());
    }

    #[Test]
    public function getFilterReturnsConstructedFilter(): void
    {
        $filter = ['status' => 'active'];
        $query = $this->makeQuery(filter: $filter);

        $this->assertSame($filter, $query->getFilter());
    }

    #[Test]
    public function getPageReturnsConstructedPage(): void
    {
        $query = $this->makeQuery(page: 3);

        $this->assertSame(3, $query->getPage());
    }

    #[Test]
    public function getPerPageReturnsConstructedPerPage(): void
    {
        $query = $this->makeQuery(perPage: 25);

        $this->assertSame(25, $query->getPerPage());
    }

    #[Test]
    public function getSortReturnsConstructedSort(): void
    {
        $sort = ['name' => Query::SORT_ASCENDING];
        $query = $this->makeQuery(sort: $sort);

        $this->assertSame($sort, $query->getSort());
    }

    #[Test]
    public function setMethodChangesMethod(): void
    {
        $query = $this->makeQuery(method: Query::METHOD_GET);
        $query->setMethod(Query::METHOD_DELETE);

        $this->assertSame(Query::METHOD_DELETE, $query->getMethod());
    }

    #[Test]
    public function setPathChangesPath(): void
    {
        $query = $this->makeQuery(path: '/old');
        $query->setPath('/new');

        $this->assertSame('/new', $query->getPath());
    }

    #[Test]
    public function setFilterChangesFilter(): void
    {
        $query = $this->makeQuery();
        $query->setFilter(['role' => 'admin']);

        $this->assertSame(['role' => 'admin'], $query->getFilter());
    }

    #[Test]
    public function setSortChangesSort(): void
    {
        $query = $this->makeQuery();
        $query->setSort(['createdAt' => Query::SORT_DESCENDING]);

        $this->assertSame(['createdAt' => Query::SORT_DESCENDING], $query->getSort());
    }

    #[Test]
    public function setPageChangesPage(): void
    {
        $query = $this->makeQuery();
        $query->setPage(5);

        $this->assertSame(5, $query->getPage());
    }

    #[Test]
    public function setPerPageChangesPerPage(): void
    {
        $query = $this->makeQuery();
        $query->setPerPage(50);

        $this->assertSame(50, $query->getPerPage());
    }

    #[Test]
    public function setHeaderStoresHeader(): void
    {
        $query = $this->makeQuery();
        $query->setHeader('Authorization', 'Bearer token123');

        $this->assertSame(['Authorization' => 'Bearer token123'], $query->getHeaders());
    }

    #[Test]
    public function setMultipleHeadersAreMerged(): void
    {
        $query = $this->makeQuery();
        $query->setHeader('Accept', 'application/json');
        $query->setHeader('Authorization', 'Bearer abc');

        $headers = $query->getHeaders();
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Authorization', $headers);
    }

    #[Test]
    public function getHeadersReturnsEmptyArrayByDefault(): void
    {
        $query = $this->makeQuery();

        $this->assertSame([], $query->getHeaders());
    }
}
