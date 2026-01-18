<?php
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

namespace Robwasripped\Restorm\Normalizer\Transformer;

use Robwasripped\Restorm\Normalizer\Exception\InvalidValueException;

/**
 * Description of ScalarTransformer
 *
 * @author Rob Treacy <robert.treacy@thesalegroup.co.uk>
 */
class ScalarTransformer implements TransformerInterface
{

    public function denormalize($value, array $options)
    {
        return $this->bidirectionalNormalize($value, $options);
    }

    public function normalize($value, array $options)
    {
        return $this->bidirectionalNormalize($value, $options);
    }

    private function bidirectionalNormalize($value, array $options)
    {
        if ($value === null){
            return null;
        }

        if ($options['multiple'] ?? false) {
            if (!is_array($value)) {
                throw new InvalidValueException('The value passed to the transformer must be an array if option "multiple" is true.');
            }

            foreach ($value as $key => &$element) {
                if (!is_scalar($element)) {
                    throw new InvalidValueException(sprintf('Expected array of scalar values, found value of type "%s" at position %s.', gettype($element), $key));
                }

                if ($type = $this->getExplicitType()) {
                    settype($element, $type);
                }
            }
        } else {
            if (!is_scalar($value)) {
                throw new InvalidValueException(sprintf('Expected scalar value, got value of type "%s" instead.', gettype($value)));
            }
        }

        return $value;
    }

    protected function getExplicitType(): ?string
    {
        return null;
    }
}
