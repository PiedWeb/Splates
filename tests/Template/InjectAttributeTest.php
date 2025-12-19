<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Attribute\Inject;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;
use PiedWeb\Splates\Template\TemplateClassInterface;
use PiedWeb\Splates\Template\TemplateEscape;
use PiedWeb\Splates\Template\TemplateFetch;

final class InjectAttributeTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        $this->engine = new Engine();
    }

    public function testMinimalTemplateWithoutHelpers(): void
    {
        $result = $this->engine->render(new MinimalTemplate('World'));

        $this->assertSame('Hello, World!', $result);
    }

    public function testInvokeParameterInjection(): void
    {
        $result = $this->engine->render(new InvokeParamTemplate('World'));

        $this->assertSame('Hello, World!', $result);
    }

    public function testInvokeParameterInjectionWithEscape(): void
    {
        $result = $this->engine->render(new InvokeParamTemplate('<script>alert("XSS")</script>'));

        $this->assertSame('Hello, &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;!', $result);
    }

    public function testInvokeParameterInjectionOnlyFetch(): void
    {
        $result = $this->engine->render(new InvokeParamFetchOnlyTemplate());

        $this->assertSame('Child Content', $result);
    }

    public function testInvokeParameterInjectionOnlyEscape(): void
    {
        $result = $this->engine->render(new InvokeParamEscapeOnlyTemplate('<b>test</b>'));

        $this->assertSame('Value: &lt;b&gt;test&lt;/b&gt;', $result);
    }

    public function testPropertyInjection(): void
    {
        $result = $this->engine->render(new PropertyInjectTemplate('World'));

        $this->assertSame('Hello, World!', $result);
    }

    public function testPropertyInjectionWithEscape(): void
    {
        $result = $this->engine->render(new PropertyInjectTemplate('<script>alert("XSS")</script>'));

        $this->assertSame('Hello, &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;!', $result);
    }

    public function testPropertyInjectionWithFetch(): void
    {
        $result = $this->engine->render(new PropertyInjectFetchTemplate());

        $this->assertSame('Parent: Child Content', $result);
    }

    public function testAbstractStillWorks(): void
    {
        $result = $this->engine->render(new AbstractBasedTemplate('World'));

        $this->assertSame('Hello, World!', $result);
    }

    public function testAbstractWithChildRender(): void
    {
        $result = $this->engine->render(new AbstractParentTemplate());

        $this->assertSame('Parent: Child Content', $result);
    }
}

/**
 * Minimal template without any helpers.
 */
class MinimalTemplate implements TemplateClassInterface
{
    public function __construct(
        public string $name,
    ) {
    }

    public function __invoke(): void
    {
        echo 'Hello, ' . $this->name . '!';
    }
}

/**
 * Template using __invoke() parameter injection.
 */
class InvokeParamTemplate implements TemplateClassInterface
{
    public function __construct(
        public string $name,
    ) {
    }

    public function __invoke(TemplateFetch $f, TemplateEscape $e): void
    {
        echo 'Hello, ' . $e($this->name) . '!';
    }
}

/**
 * Template using only TemplateFetch parameter.
 */
class InvokeParamFetchOnlyTemplate implements TemplateClassInterface
{
    public function __invoke(TemplateFetch $f): void
    {
        echo $f(new MinimalChildTemplate());
    }
}

/**
 * Template using only TemplateEscape parameter.
 */
class InvokeParamEscapeOnlyTemplate implements TemplateClassInterface
{
    public function __construct(
        public string $value,
    ) {
    }

    public function __invoke(TemplateEscape $e): void
    {
        echo 'Value: ' . $e($this->value);
    }
}

/**
 * Template using #[Inject] property injection.
 */
class PropertyInjectTemplate implements TemplateClassInterface
{
    #[Inject]
    protected TemplateEscape $e;

    public function __construct(
        public string $name,
    ) {
    }

    public function __invoke(): void
    {
        echo 'Hello, ' . ($this->e)($this->name) . '!';
    }
}

/**
 * Template using #[Inject] property for TemplateFetch.
 */
class PropertyInjectFetchTemplate implements TemplateClassInterface
{
    #[Inject]
    protected TemplateFetch $f;

    public function __invoke(): void
    {
        echo 'Parent: ' . ($this->f)(new MinimalChildTemplate());
    }
}

/**
 * Minimal child template for composition testing.
 */
class MinimalChildTemplate implements TemplateClassInterface
{
    public function __invoke(): void
    {
        echo 'Child Content';
    }
}

/**
 * Template extending TemplateAbstract (backwards compatibility).
 */
class AbstractBasedTemplate extends TemplateAbstract
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
 * Parent template using TemplateAbstract.
 */
class AbstractParentTemplate extends TemplateAbstract
{
    public function __invoke(): void
    {
        echo 'Parent: ' . $this->render(new MinimalChildTemplate());
    }
}
