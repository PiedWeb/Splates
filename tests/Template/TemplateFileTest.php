<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\TemplateFile;

final class TemplateFileTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        // Use test directory as template root
        $this->engine = new Engine(templateDir: __DIR__ . '/../..');
    }

    public function testRenderSimpleFile(): void
    {
        $template = new TemplateFile($this->engine, 'tests/Template/fixtures/simple.php');
        $result = $template->render();

        $this->assertSame("<h1>Hello</h1>\n", $result);
    }

    public function testRenderWithData(): void
    {
        $template = new TemplateFile($this->engine, 'tests/Template/fixtures/greeting.php');
        $result = $template->render(['name' => 'John']);

        $this->assertStringContainsString('Hello, John!', $result);
    }

    public function testRenderWithEscaping(): void
    {
        $template = new TemplateFile($this->engine, 'tests/Template/fixtures/greeting.php');
        $result = $template->render(['name' => '<script>alert("XSS")</script>']);

        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testRenderSubdirectory(): void
    {
        $template = new TemplateFile($this->engine, 'tests/Template/fixtures/partials/header.php');
        $result = $template->render(['title' => 'My Page']);

        $this->assertStringContainsString('My Page', $result);
        $this->assertStringContainsString('<header>', $result);
    }

    public function testPathTraversalBlocked(): void
    {
        // Path traversal that doesn't exist throws RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found');

        new TemplateFile($this->engine, '../../../etc/passwd');
    }

    public function testNullByteBlocked(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('null byte');

        new TemplateFile($this->engine, "test\0file.php");
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found');

        new TemplateFile($this->engine, 'tests/Template/fixtures/nonexistent.php');
    }

    public function testExistsMethod(): void
    {
        $template = new TemplateFile($this->engine, 'tests/Template/fixtures/simple.php');

        $this->assertTrue($template->exists());
    }

    public function testPathMethod(): void
    {
        $template = new TemplateFile($this->engine, 'tests/Template/fixtures/simple.php');

        $this->assertSame('tests/Template/fixtures/simple.php', $template->path());
    }
}
