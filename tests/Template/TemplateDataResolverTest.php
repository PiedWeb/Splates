<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\PropertyBinding;
use PiedWeb\Splates\Template\TemplateAbstract;
use PiedWeb\Splates\Template\TemplateDataResolver;

final class TemplateDataResolverTest extends TestCase
{
    public function testResolveReturnsEmptyArrayForTemplateWithoutGlobals(): void
    {
        $resolver = new TemplateDataResolver();

        $bindings = $resolver->resolve(TemplateWithoutGlobals::class);

        $this->assertSame([], $bindings);
    }

    public function testResolveFindsGlobalProperties(): void
    {
        $resolver = new TemplateDataResolver();

        $bindings = $resolver->resolve(TemplateWithGlobals::class);

        $this->assertCount(1, $bindings);
        $this->assertInstanceOf(PropertyBinding::class, $bindings[0]);
        $this->assertSame('service', $bindings[0]->propertyName);
        $this->assertSame('service', $bindings[0]->globalKey);
        $this->assertFalse($bindings[0]->escape);
    }

    public function testResolveFindsGlobalWithEscape(): void
    {
        $resolver = new TemplateDataResolver();

        $bindings = $resolver->resolve(TemplateWithGlobalEscape::class);

        $this->assertCount(1, $bindings);
        $this->assertTrue($bindings[0]->escape);
    }

    public function testResolveUsesMemoryCache(): void
    {
        $resolver = new TemplateDataResolver();

        // First call
        $bindings1 = $resolver->resolve(TemplateWithGlobals::class);

        // Second call should return same instance
        $bindings2 = $resolver->resolve(TemplateWithGlobals::class);

        $this->assertSame($bindings1, $bindings2);
    }

    public function testResolveWithFileCaching(): void
    {
        $cacheDir = sys_get_temp_dir() . '/splates_resolver_test_' . uniqid();

        try {
            $resolver = new TemplateDataResolver($cacheDir);

            // First call - should create cache file
            $bindings1 = $resolver->resolve(TemplateWithGlobals::class);

            // Create new resolver - should read from file cache
            $resolver2 = new TemplateDataResolver($cacheDir);
            $bindings2 = $resolver2->resolve(TemplateWithGlobals::class);

            $this->assertCount(1, $bindings1);
            $this->assertCount(1, $bindings2);
            $this->assertSame($bindings1[0]->propertyName, $bindings2[0]->propertyName);
        } finally {
            // Clean up
            $files = glob($cacheDir . '/splates_*.php');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            if (is_dir($cacheDir)) {
                rmdir($cacheDir);
            }
        }
    }

    public function testClearCache(): void
    {
        $cacheDir = sys_get_temp_dir() . '/splates_clear_test_' . uniqid();

        try {
            $resolver = new TemplateDataResolver($cacheDir);

            // Populate cache
            $resolver->resolve(TemplateWithGlobals::class);

            // Clear cache
            $resolver->clearCache();

            // Cache should be empty
            $files = glob($cacheDir . '/splates_*.php');
            $this->assertSame([], $files === false ? [] : $files);
        } finally {
            if (is_dir($cacheDir)) {
                rmdir($cacheDir);
            }
        }
    }

    public function testWarmCache(): void
    {
        $resolver = new TemplateDataResolver();

        $this->expectNotToPerformAssertions();
        $resolver->warmCache([
            TemplateWithoutGlobals::class,
            TemplateWithGlobals::class,
        ]);
    }
}

/**
 * Template without any global properties.
 */
class TemplateWithoutGlobals extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $name,
    ) {
    }

    public function display(): void
    {
        echo $this->e($this->name);
    }
}

/**
 * Template with a global service property.
 */
class TemplateWithGlobals extends TemplateAbstract
{
    #[TemplateData(global: true)]
    public object $service;

    public function display(): void
    {
        echo 'test';
    }
}

/**
 * Template with escape flag on global.
 */
class TemplateWithGlobalEscape extends TemplateAbstract
{
    #[TemplateData(global: true, escape: true)]
    public string $title;

    public function display(): void
    {
        echo $this->title;
    }
}
