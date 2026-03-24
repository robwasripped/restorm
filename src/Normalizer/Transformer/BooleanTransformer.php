<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Normalizer\Transformer;

class BooleanTransformer extends ScalarTransformer
{

    protected function getExplicitType(): ?string
    {
        return 'boolean';
    }
}
