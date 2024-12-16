<?php

namespace PiedWeb\Splates\Template;

use LogicException;

final class Folder
{
    public function __construct(public string $name, private string $path, public readonly bool $fallback = false)
    {
        $this->setPath($path);
    }

    public function setPath(string $path): static
    {
        if (! is_dir($path)) {
            throw new LogicException('The specified directory path "' . $path . '" does not exist.');
        }

        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
