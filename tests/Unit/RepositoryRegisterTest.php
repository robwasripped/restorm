<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\RepositoryRegister;
use Robwasripped\Restorm\RepositoryInterface;

class RepositoryRegisterTest extends TestCase
{
    private RepositoryRegister $register;

    protected function setUp(): void
    {
        $this->register = new RepositoryRegister();
    }

    #[Test]
    public function hasRepositoryReturnsFalseWhenEmpty(): void
    {
        $this->assertFalse($this->register->hasRepository('App\Entity\User'));
    }

    #[Test]
    public function addRepositoryAndHasRepository(): void
    {
        $repo = $this->createMock(RepositoryInterface::class);
        $this->register->addRepository('App\Entity\User', $repo);

        $this->assertTrue($this->register->hasRepository('App\Entity\User'));
    }

    #[Test]
    public function getRepositoryReturnsAddedInstance(): void
    {
        $repo = $this->createMock(RepositoryInterface::class);
        $this->register->addRepository('App\Entity\User', $repo);

        $this->assertSame($repo, $this->register->getRepository('App\Entity\User'));
    }

    #[Test]
    public function addingMultipleRepositoriesKeepsThem(): void
    {
        $repoA = $this->createMock(RepositoryInterface::class);
        $repoB = $this->createMock(RepositoryInterface::class);

        $this->register->addRepository('App\Entity\User', $repoA);
        $this->register->addRepository('App\Entity\Post', $repoB);

        $this->assertSame($repoA, $this->register->getRepository('App\Entity\User'));
        $this->assertSame($repoB, $this->register->getRepository('App\Entity\Post'));
    }

    #[Test]
    public function addingRepositoryForSameEntityClassOverwritesPrevious(): void
    {
        $repo1 = $this->createMock(RepositoryInterface::class);
        $repo2 = $this->createMock(RepositoryInterface::class);

        $this->register->addRepository('App\Entity\User', $repo1);
        $this->register->addRepository('App\Entity\User', $repo2);

        $this->assertSame($repo2, $this->register->getRepository('App\Entity\User'));
    }
}
