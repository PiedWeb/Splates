<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use LogicException;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Extension\ExtensionInterface;
use PiedWeb\Splates\Template\Func;

final class FuncTest extends TestCase
{
    private Func $function;

    protected function setUp(): void
    {
        $this->function = new Func('uppercase', fn (string $string) => strtoupper($string));
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Func::class, $this->function);
    }

    public function testSetAndGetName(): void
    {
        $this->assertInstanceOf(Func::class, $this->function->setName('test'));
        $this->assertSame('test', $this->function->getName());
    }

    public function testSetInvalidName(): void
    {
        // Not a valid function name.
        $this->expectException(LogicException::class);
        $this->function->setName('invalid-function-name');
    }

    public function testSetAndGetCallback(): void
    {
        $this->assertInstanceOf(Func::class, $this->function->setCallback('strtolower'));
        $this->assertSame('strtolower', $this->function->getCallback());
    }

    public function testFunctionCall(): void
    {
        $this->assertSame('JONATHAN', $this->function->call(null, ['Jonathan']));
    }

    public function testExtensionFunctionCall(): void
    {
        $extension = new class() implements ExtensionInterface {
            public function register(Engine $engine): void
            {
            }

            public function foo(): string
            {
                return 'bar';
            }
        };
        $this->function->setCallback($extension->foo(...));
        $this->assertSame('bar', $this->function->call(null));
    }
}
