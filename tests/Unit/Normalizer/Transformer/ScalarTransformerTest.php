<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit\Normalizer\Transformer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\Normalizer\Exception\InvalidValueException;
use Robwasripped\Restorm\Normalizer\Transformer\ScalarTransformer;
use Robwasripped\Restorm\Normalizer\Transformer\BooleanTransformer;
use Robwasripped\Restorm\Normalizer\Transformer\IntegerTransformer;
use Robwasripped\Restorm\Normalizer\Transformer\FloatTransformer;
use Robwasripped\Restorm\Normalizer\Transformer\TextTransformer;

class ScalarTransformerTest extends TestCase
{
    private ScalarTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ScalarTransformer();
    }

    #[Test]
    public function normalizeReturnsScalarUnchanged(): void
    {
        $this->assertSame('hello', $this->transformer->normalize('hello', []));
        $this->assertSame(42, $this->transformer->normalize(42, []));
        $this->assertSame(3.14, $this->transformer->normalize(3.14, []));
        $this->assertSame(true, $this->transformer->normalize(true, []));
    }

    #[Test]
    public function denormalizeReturnsScalarUnchanged(): void
    {
        $this->assertSame('world', $this->transformer->denormalize('world', []));
        $this->assertSame(7, $this->transformer->denormalize(7, []));
    }

    #[Test]
    public function normalizeNullReturnsNull(): void
    {
        $this->assertNull($this->transformer->normalize(null, []));
    }

    #[Test]
    public function denormalizeNullReturnsNull(): void
    {
        $this->assertNull($this->transformer->denormalize(null, []));
    }

    #[Test]
    public function normalizeThrowsForNonScalar(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->transformer->normalize(new \stdClass(), []);
    }

    #[Test]
    public function normalizeThrowsForNonArrayWithMultipleOption(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->transformer->normalize('not-an-array', ['multiple' => true]);
    }

    #[Test]
    public function normalizeMultipleReturnsArrayOfScalars(): void
    {
        $result = $this->transformer->normalize([1, 2, 3], ['multiple' => true]);

        $this->assertSame([1, 2, 3], $result);
    }

    #[Test]
    public function normalizeMultipleThrowsForNonScalarElement(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->transformer->normalize([1, new \stdClass(), 3], ['multiple' => true]);
    }

    // --- BooleanTransformer ---

    #[Test]
    public function booleanTransformerCastsToBool(): void
    {
        $transformer = new BooleanTransformer();

        $result = $transformer->normalize([1], ['multiple' => true]);
        $this->assertIsBool($result[0]);
        $this->assertTrue($result[0]);

        $result = $transformer->normalize([0], ['multiple' => true]);
        $this->assertFalse($result[0]);
    }

    // --- IntegerTransformer ---

    #[Test]
    public function integerTransformerCastsToInt(): void
    {
        $transformer = new IntegerTransformer();

        $result = $transformer->normalize(['42'], ['multiple' => true]);
        $this->assertIsInt($result[0]);
        $this->assertSame(42, $result[0]);
    }

    // --- FloatTransformer ---

    #[Test]
    public function floatTransformerCastsToFloat(): void
    {
        $transformer = new FloatTransformer();

        $result = $transformer->normalize(['3'], ['multiple' => true]);
        $this->assertIsFloat($result[0]);
        $this->assertSame(3.0, $result[0]);
    }

    // --- TextTransformer ---

    #[Test]
    public function textTransformerCastsToString(): void
    {
        $transformer = new TextTransformer();

        $result = $transformer->normalize([42], ['multiple' => true]);
        $this->assertIsString($result[0]);
        $this->assertSame('42', $result[0]);
    }
}
