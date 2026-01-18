<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Robwasripped\Restorm\Mapping\Exception;

use Robwasripped\Restorm\Exception\RestormException;

/**
 * Description of UnknownEntityException
 *
 * @author Rob Treacy <email@roberttreacy.com>
 */
class UnknownEntityException extends \Exception implements RestormException
{

    public function __construct(string $class, int $code = 0, \Throwable $previous
    = null)
    {
        $message = sprintf('The entity "%s" is not mapped.', $class);
        parent::__construct($message, $code, $previous);
    }
}
