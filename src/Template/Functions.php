<?php

namespace PiedWeb\Splates\Template;

use LogicException;

/**
 * A collection of template functions.
 */
class Functions
{
    /** @var array<string, Func> */
    protected array $functions = [];

    public function add(string $name, callable $callback): static
    {
        if ($this->exists($name)) {
            throw new LogicException(
                'The template function name "' . $name . '" is already registered.'
            );
        }

        $this->functions[$name] = new Func($name, $callback);

        return $this;
    }

    public function remove(string $name): static
    {
        if (! $this->exists($name)) {
            throw new LogicException(
                'The template function "' . $name . '" was not found.'
            );
        }

        unset($this->functions[$name]);

        return $this;
    }

    public function get(string $name): Func
    {
        if (! $this->exists($name)) {
            throw new LogicException('The template function "' . $name . '" was not found.');
        }

        return $this->functions[$name];
    }

    public function exists(string $name): bool
    {
        return isset($this->functions[$name]);
    }
}
