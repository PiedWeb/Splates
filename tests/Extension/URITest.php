<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Extension;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Extension\URI;

class URITest extends TestCase
{
    public function testRegister(): void
    {
        $engine = new Engine();
        $extension = new URI();
        $extension->register($engine);
        $this->assertTrue($engine->doesFunctionExist('uri'));
    }
}
