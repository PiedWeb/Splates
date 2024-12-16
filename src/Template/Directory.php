<?php

namespace PiedWeb\Splates\Template;

use LogicException;

/**
 * Default template directory.
 */
class Directory
{
    public function __construct(private ?string $path = null)
    {
        $this->set($path);
    }

    public function set(?string $path): static
    {
        if (! is_null($path) && ! is_dir($path)) {
            throw new LogicException(
                'The specified path "' . $path . '" does not exist.'
            );
        }

        $this->path = $path;

        return $this;
    }

    public function get(): ?string
    {
        return $this->path;
    }
}
