<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use LogicException;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\Functions;

final class FunctionsTest extends TestCase
{
    private Functions $functions;

    protected function setUp(): void
    {
        $this->functions = new Functions();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Functions::class, $this->functions);
    }

    public function testAddAndGetFunction(): void
    {
        $this->assertInstanceOf(Functions::class, $this->functions->add('upper', 'strtoupper'));
        $this->assertSame('strtoupper', $this->functions->get('upper')->getCallback());
    }

    public function testAddFunctionConflict(): void
    {
        // The template function name "upper" is already registered.
        $this->expectException(LogicException::class);
        $this->functions->add('upper', 'strtoupper');
        $this->functions->add('upper', 'strtoupper');
    }

    public function testGetNonExistentFunction(): void
    {
        // The template function "foo" was not found.
        $this->expectException(LogicException::class);
        $this->functions->get('foo');
    }

    public function testRemoveFunction(): void
    {
        $this->functions->add('upper', 'strtoupper');
        $this->assertTrue($this->functions->exists('upper'));
        $this->functions->remove('upper');
        $this->assertFalse($this->functions->exists('upper'));
    }

    public function testRemoveNonExistentFunction(): void
    {
        // The template function "foo" was not found.
        $this->expectException(LogicException::class);
        $this->functions->remove('foo');
    }

    public function testFunctionExists(): void
    {
        $this->assertFalse($this->functions->exists('upper'));
        $this->functions->add('upper', 'strtoupper');
        $this->assertTrue($this->functions->exists('upper'));
    }
}
