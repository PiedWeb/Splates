<?php

namespace PiedWeb\Splates\Template;

use LogicException;
use PiedWeb\Splates\Extension\ExtensionInterface;

/**
 * A template function.
 */
class Func
{
    protected string $name;

    /**
     * @var callable
     */
    protected $callback;

    public function __construct(string $name, callable $callback)
    {
        $this->setName($name);
        $this->setCallback($callback);
    }

    public function setName(string $name): static
    {
        if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name) !== 1) {
            throw new LogicException(
                'Not a valid function name.'
            );
        }

        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCallback(callable $callback): static
    {

        $this->callback = $callback;

        return $this;
    }

    /**
     * Get the function callback.
     * @return ?callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**  @param  array<mixed>    $arguments  */
    public function call(Template $template = null, $arguments = []): mixed
    {
        // autowire template
        $callbackFirstPart = is_array($this->callback) ? ($this->callback[0] ?? null) : null;
        if ($callbackFirstPart instanceof ExtensionInterface && property_exists($callbackFirstPart, 'template')) {
            $callbackFirstPart->template = $template;
        }

        return call_user_func_array($this->callback, $arguments);
    }
}
