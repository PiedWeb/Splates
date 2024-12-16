<?php

namespace PiedWeb\Splates\Template;

use LogicException;

final class Folders
{
    /** @var array<string, Folder> */
    private array $folders = [];

    public function add(string $name, string $path, bool $fallback = false): static
    {
        if ($this->exists($name)) {
            throw new LogicException('The template folder "' . $name . '" is already being used.');
        }

        $this->folders[$name] = new Folder($name, $path, $fallback);

        return $this;
    }

    public function remove(string $name): static
    {
        if (! $this->exists($name)) {
            throw new LogicException('The template folder "' . $name . '" was not found.');
        }

        unset($this->folders[$name]);

        return $this;
    }

    public function get(string $name): Folder
    {
        if (! $this->exists($name)) {
            throw new LogicException('The template folder "' . $name . '" was not found.');
        }

        return $this->folders[$name];
    }

    public function exists(string $name): bool
    {
        return isset($this->folders[$name]);
    }
}
