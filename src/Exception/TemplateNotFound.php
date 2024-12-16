<?php

namespace PiedWeb\Splates\Exception;

use LogicException;

final class TemplateNotFound extends LogicException
{
    /**
     * @param list<string> $paths
     */
    public function __construct(public readonly string $template, public readonly array $paths, string $message)
    {
        parent::__construct($message);
    }
}
