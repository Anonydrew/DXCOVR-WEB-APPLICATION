<?php

namespace App\Exceptions;

use RuntimeException;

class MediaInUseException extends RuntimeException
{
    /**
     * @param  list<array{type: string, title: string, url: string|null}>  $usages
     */
    public function __construct(public readonly array $usages)
    {
        parent::__construct('This file is still in use and cannot be deleted.');
    }
}
