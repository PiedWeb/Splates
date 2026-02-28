<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Attribute\Inject;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\InjectResolver;
use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateAbstract;

final class EngineTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        $this->engine = new Engine();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Engine::class, $this->engine);
    }

    public function testCanCreateInstanceWithCacheDir(): void
    {
        $cacheDir = sys_get_temp_dir() . '/splates_test_' . uniqid();
        $engine = new Engine(cacheDir: $cacheDir);

        $this->assertInstanceOf(Engine::class, $engine);

        // Clean up
        if (is_dir($cacheDir)) {
            rmdir($cacheDir);
        }
    }

    public function testCanCreateInstanceWithTemplateDir(): void
    {
        $templateDir = __DIR__;
        $engine = new Engine(templateDir: $templateDir);

        $this->assertInstanceOf(Engine::class, $engine);
        $this->assertSame($templateDir, $engine->getTemplateDir());
    }

    public function testAutoDetectsProjectRoot(): void
    {
        $engine = new Engine();
        $templateDir = $engine->getTemplateDir();

        // Should find composer.json and use that directory
        $this->assertFileExists($templateDir . '/composer.json');
    }

    public function testAddGlobal(): void
    {
        $service = new \stdClass();
        $service->name = 'TestService';

        $result = $this->engine->addGlobal('service', $service);

        $this->assertInstanceOf(Engine::class, $result);
        $this->assertSame($service, $this->engine->getGlobal('service'));
    }

    public function testGetGlobals(): void
    {
        $this->engine->addGlobal('foo', 'bar');
        $this->engine->addGlobal('baz', 123);

        $globals = $this->engine->getGlobals();

        $this->assertArrayHasKey('foo', $globals);
        $this->assertArrayHasKey('baz', $globals);
        $this->assertSame('bar', $globals['foo']);
        $this->assertSame(123, $globals['baz']);
    }

    public function testGetGlobalReturnsNullForMissing(): void
    {
        $this->assertNull($this->engine->getGlobal('nonexistent'));
    }

    public function testGetInjectResolver(): void
    {
        $resolver = $this->engine->getInjectResolver();

        $this->assertInstanceOf(InjectResolver::class, $resolver);
    }

    public function testMakeTemplate(): void
    {
        $template = new SimpleTestTemplate('Hello');
        $result = $this->engine->make($template);

        $this->assertInstanceOf(Template::class, $result);
    }

    public function testRenderTemplate(): void
    {
        $template = new SimpleTestTemplate('World');
        $result = $this->engine->render($template);

        $this->assertSame('Hello, World!', $result);
    }

    public function testRenderWithGlobalInjection(): void
    {
        $service = new TestService('Injected');
        $this->engine->addGlobal('service', $service);

        $template = new GlobalServiceTestTemplate();
        $result = $this->engine->render($template);

        $this->assertSame('Service: Injected', $result);
    }

    public function testClearCache(): void
    {
        $this->expectNotToPerformAssertions();
        $this->engine->clearCache();
    }

    public function testRenderFileTemplate(): void
    {
        $engine = new Engine(templateDir: __DIR__);
        $result = $engine->render('Template/fixtures/simple.php');

        $this->assertStringContainsString('Hello', $result);
    }

    public function testRenderFileTemplateWithData(): void
    {
        $engine = new Engine(templateDir: __DIR__);
        $result = $engine->render('Template/fixtures/greeting.php', ['name' => 'Alice']);

        $this->assertStringContainsString('Hello, Alice!', $result);
    }

    public function testRenderClassTemplateStillWorks(): void
    {
        // Ensure backward compatibility with class-based templates
        $template = new SimpleTestTemplate('ClassBased');
        $result = $this->engine->render($template);

        $this->assertSame('Hello, ClassBased!', $result);
    }
}

/**
 * Simple test template.
 */
class SimpleTestTemplate extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $name,
    ) {
    }

    public function __invoke(): void
    {
        echo 'Hello, ' . $this->e($this->name) . '!';
    }
}

/**
 * Template with global service injection.
 */
class GlobalServiceTestTemplate extends TemplateAbstract
{
    #[Inject]
    public TestService $service;

    public function __invoke(): void
    {
        echo 'Service: ' . $this->e($this->service->name);
    }
}

/**
 * Test service for injection testing.
 */
class TestService
{
    public function __construct(
        public string $name,
    ) {
    }
}
