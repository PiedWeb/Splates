<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\Attribute\Inject;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\InjectBinding;
use PiedWeb\Splates\Template\InjectResolver;
use PiedWeb\Splates\Template\TemplateAbstract;

final class InjectResolverTest extends TestCase
{
    public function testResolveReturnsEmptyArrayForTemplateWithoutInjects(): void
    {
        $resolver = new InjectResolver();

        $bindings = $resolver->resolve(TemplateWithoutInjects::class);

        // TemplateAbstract has #[Inject] on $f and $e, so we get those
        $this->assertCount(2, $bindings);
    }

    public function testResolveFindsInjectProperties(): void
    {
        $resolver = new InjectResolver();

        $bindings = $resolver->resolve(TemplateWithInject::class);

        // $f, $e from TemplateAbstract + $service
        $serviceBindings = array_values(array_filter(
            $bindings,
            static fn (InjectBinding $b): bool => $b->propertyName === 'service',
        ));

        $this->assertCount(1, $serviceBindings);
        $this->assertSame('service', $serviceBindings[0]->propertyName);
        $this->assertSame('service', $serviceBindings[0]->globalKey);
        $this->assertFalse($serviceBindings[0]->escape);
    }

    public function testResolveFindsInjectWithEscape(): void
    {
        $resolver = new InjectResolver();

        $bindings = $resolver->resolve(TemplateWithInjectEscape::class);

        $titleBindings = array_values(array_filter(
            $bindings,
            static fn (InjectBinding $b): bool => $b->propertyName === 'title',
        ));

        $this->assertCount(1, $titleBindings);
        $this->assertTrue($titleBindings[0]->escape);
    }

    public function testResolveUsesMemoryCache(): void
    {
        $resolver = new InjectResolver();

        // First call
        $bindings1 = $resolver->resolve(TemplateWithInject::class);

        // Second call should return same instance
        $bindings2 = $resolver->resolve(TemplateWithInject::class);

        $this->assertSame($bindings1, $bindings2);
    }

    public function testResolveWithFileCaching(): void
    {
        $cacheDir = sys_get_temp_dir() . '/splates_resolver_test_' . uniqid();

        try {
            $resolver = new InjectResolver($cacheDir);

            // First call - should create cache file
            $bindings1 = $resolver->resolve(TemplateWithInject::class);

            // Create new resolver - should read from file cache
            $resolver2 = new InjectResolver($cacheDir);
            $bindings2 = $resolver2->resolve(TemplateWithInject::class);

            $this->assertCount(\count($bindings1), $bindings2);

            $service1 = array_values(array_filter(
                $bindings1,
                static fn (InjectBinding $b): bool => $b->propertyName === 'service',
            ));
            $service2 = array_values(array_filter(
                $bindings2,
                static fn (InjectBinding $b): bool => $b->propertyName === 'service',
            ));

            $this->assertSame($service1[0]->propertyName, $service2[0]->propertyName);
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
            $resolver = new InjectResolver($cacheDir);

            // Populate cache
            $resolver->resolve(TemplateWithInject::class);

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
        $resolver = new InjectResolver();

        $this->expectNotToPerformAssertions();
        $resolver->warmCache([
            TemplateWithoutInjects::class,
            TemplateWithInject::class,
        ]);
    }
}

/**
 * Template without any extra #[Inject] properties (only inherited from TemplateAbstract).
 */
class TemplateWithoutInjects extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $name,
    ) {
    }

    public function __invoke(): void
    {
        echo $this->e($this->name);
    }
}

/**
 * Template with an #[Inject] service property.
 */
class TemplateWithInject extends TemplateAbstract
{
    #[Inject]
    public object $service;

    public function __invoke(): void
    {
        echo 'test';
    }
}

/**
 * Template with escape flag on #[Inject].
 */
class TemplateWithInjectEscape extends TemplateAbstract
{
    #[Inject(escape: true)]
    public string $title;

    public function __invoke(): void
    {
        echo $this->title;
    }
}
