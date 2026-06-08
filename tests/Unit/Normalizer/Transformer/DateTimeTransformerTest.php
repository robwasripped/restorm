<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit\Normalizer\Transformer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\Normalizer\Transformer\DateTimeTransformer;

class DateTimeTransformerTest extends TestCase
{
    private DateTimeTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DateTimeTransformer();
    }

    #[Test]
    public function denormalizeStringCreatesDateTime(): void
    {
        $result = $this->transformer->denormalize('2024-06-15T12:00:00+0000', []);

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertSame('2024', $result->format('Y'));
    }

    #[Test]
    public function denormalizeNullReturnsNull(): void
    {
        $this->assertNull($this->transformer->denormalize(null, []));
    }

    #[Test]
    public function normalizeDateTimeReturnsISO8601String(): void
    {
        $date = new \DateTime('2024-06-15T12:00:00+00:00');
        $result = $this->transformer->normalize($date, []);

        $this->assertIsString($result);
        // ISO8601 format includes date and time
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result);
    }

    #[Test]
    public function normalizeNullReturnsNull(): void
    {
        $this->assertNull($this->transformer->normalize(null, []));
    }

    #[Test]
    public function normalizeThrowsForNonDateTimeInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->transformer->normalize('not-a-date', []);
    }

    #[Test]
    public function normalizeDateTimeImmutableWorks(): void
    {
        $date = new \DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $result = $this->transformer->normalize($date, []);

        $this->assertIsString($result);
        $this->assertStringStartsWith('2024-01-01', $result);
    }

    #[Test]
    public function denormalizeThenNormalizeRoundTrips(): void
    {
        $original = '2024-06-15T12:30:00+0000';

        $dateTime = $this->transformer->denormalize($original, []);
        $result = $this->transformer->normalize($dateTime, []);

        // Re-parse to compare timestamps rather than exact string formats
        $this->assertSame((new \DateTime($original))->getTimestamp(), (new \DateTime($result))->getTimestamp());
    }
}
