<?php

namespace PiedWeb\Splates\Extension;

use PiedWeb\Splates\Engine;

/**
 * A common interface for extensions.
 */
interface ExtensionInterface
{
    public function register(Engine $engine): void;
}
