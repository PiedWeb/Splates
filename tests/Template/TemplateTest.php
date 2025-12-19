<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateAbstract;
use PiedWeb\Splates\Template\Value\Slot;
use PiedWeb\Splates\Template\Value\Text;

final class TemplateTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        $this->engine = new Engine();
    }

    public function testCanCreateInstance(): void
    {
        $template = $this->engine->make(new BasicTemplate('Test'));

        $this->assertInstanceOf(Template::class, $template);
    }

    public function testRender(): void
    {
        $result = $this->engine->render(new BasicTemplate('World'));

        $this->assertSame('Hello, World!', $result);
    }

    public function testEscapeFunction(): void
    {
        $result = $this->engine->render(new BasicTemplate('<script>alert("XSS")</script>'));

        $this->assertSame('Hello, &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;!', $result);
    }

    public function testRenderComponent(): void
    {
        $result = $this->engine->render(new ParentTemplate());

        $this->assertStringContainsString('Child Content', $result);
    }

    public function testCaptureOutput(): void
    {
        $result = $this->engine->render(new CaptureTemplate());

        $this->assertSame('Captured: <p>Test</p>', $result);
    }

    public function testSlotsPattern(): void
    {
        $result = $this->engine->render(new PageWithLayoutTemplate());

        $this->assertStringContainsString('<title>My Page</title>', $result);
        $this->assertStringContainsString('<main>Page Content</main>', $result);
    }

    public function testOptionalSlots(): void
    {
        $result = $this->engine->render(new PageWithOptionalSlotsTemplate());

        $this->assertStringContainsString('<main>Content</main>', $result);
        $this->assertStringNotContainsString('<aside>', $result);
    }

    public function testTemplateToString(): void
    {
        $template = $this->engine->make(new BasicTemplate('Test'));

        $this->assertSame('Hello, Test!', (string) $template);
    }

    public function testValueObjectText(): void
    {
        $text = new Text('<script>alert("XSS")</script>');

        $this->assertSame('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', (string) $text);
        $this->assertSame('<script>alert("XSS")</script>', $text->raw());
    }

    public function testTemplateWithTextValueObject(): void
    {
        $result = $this->engine->render(new TextValueObjectTemplate(
            new Text('Safe <b>Text</b>'),
        ));

        // Text should be auto-escaped
        $this->assertSame('Content: Safe &lt;b&gt;Text&lt;/b&gt;', $result);
    }
}

/**
 * Basic template for testing.
 */
class BasicTemplate extends TemplateAbstract
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
 * Child template for composition testing.
 */
class ChildTemplate extends TemplateAbstract
{
    public function __invoke(): void
    {
        echo 'Child Content';
    }
}

/**
 * Parent template that renders child.
 */
class ParentTemplate extends TemplateAbstract
{
    public function __invoke(): void
    {
        echo 'Parent: ';
        echo $this->render(new ChildTemplate());
    }
}

/**
 * Template testing capture functionality.
 */
class CaptureTemplate extends TemplateAbstract
{
    public function __invoke(): void
    {
        $captured = $this->capture(function () {
            echo '<p>Test</p>';
        });

        echo 'Captured: ' . $captured;
    }
}

/**
 * Simple layout template with slots.
 */
class SimpleLayoutTemplate extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $title,
        #[TemplateData]
        public Slot $content,
    ) {
    }

    public function __invoke(): void
    { ?>
<!DOCTYPE html>
<html>
<head><title><?= $this->e($this->title) ?></title></head>
<body><main><?= $this->content ?></main></body>
</html>
<?php }
    }

/**
 * Page template that uses layout with slots.
 */
class PageWithLayoutTemplate extends TemplateAbstract
{
    public function __invoke(): void
    {
        echo $this->render(new SimpleLayoutTemplate(
            title: 'My Page',
            content: new Slot(fn () => 'Page Content'),
        ));
    }
}

/**
 * Layout with optional slots.
 */
class LayoutWithOptionalSlotsTemplate extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public Slot $content,
        #[TemplateData]
        public ?Slot $sidebar = null,
    ) {
    }

    public function __invoke(): void
    { ?>
<main><?= $this->content ?></main>
<?php if ($this->sidebar): ?>
<aside><?= $this->sidebar ?></aside>
<?php endif ?>
<?php }
    }

/**
 * Page with optional slots.
 */
class PageWithOptionalSlotsTemplate extends TemplateAbstract
{
    public function __invoke(): void
    {
        echo $this->render(new LayoutWithOptionalSlotsTemplate(
            content: new Slot(fn () => 'Content'),
            // sidebar not provided
        ));
    }
}

/**
 * Template using Text value object.
 */
class TextValueObjectTemplate extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public Text $text,
    ) {
    }

    public function __invoke(): void
    {
        echo 'Content: ' . $this->text;
    }
}
