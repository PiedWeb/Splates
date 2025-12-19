<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Template;

final class TemplateTest extends TestCase
{
    private Template $template;

    protected function setUp(): void
    {
        vfsStream::setup('templates');

        $engine = new Engine(vfsStream::url('templates'));
        $engine->registerFunction('uppercase', 'strtoupper');

        $this->template = new Template($engine, 'template');
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Template::class, $this->template);
    }

    public function testCanCallFunction(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php echo $this->uppercase("jonathan") ?>',
            ]
        );

        $this->assertSame('JONATHAN', $this->template->render());
    }

    public function testAssignData(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php echo $name ?>',
            ]
        );

        $this->template->data(['name' => 'Jonathan']);
        $this->assertSame('Jonathan', $this->template->render());
    }

    public function testGetData(): void
    {
        $data = ['name' => 'Jonathan'];

        $this->template->data($data);
        $this->assertSame($this->template->data(), $data);
    }

    public function testExists(): void
    {
        vfsStream::create(
            [
                'template.php' => '',
            ]
        );

        $this->assertTrue($this->template->exists());
    }

    public function testDoesNotExist(): void
    {
        $this->assertFalse($this->template->exists());
    }

    public function testGetPath(): void
    {
        $this->assertSame('vfs://templates/template.php', $this->template->path());
    }

    public function testRender(): void
    {
        vfsStream::create(
            [
                'template.php' => 'Hello World',
            ]
        );

        $this->assertSame('Hello World', $this->template->render());
    }

    public function testRenderViaToStringMagicMethod(): void
    {
        vfsStream::create(
            [
                'template.php' => 'Hello World',
            ]
        );

        $actual = (string) $this->template;

        $this->assertSame('Hello World', $actual);
    }

    public function testRenderWithData(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php echo $name ?>',
            ]
        );

        $this->assertSame('Jonathan', $this->template->render(['name' => 'Jonathan']));
    }

    public function testRenderDoesNotExist(): void
    {
        // The template "template" could not be found at "vfs://templates/template.php".
        $this->expectException(LogicException::class);
        var_dump($this->template->render());
    }

    public function testRenderException(): void
    {
        // error
        $this->expectException('Exception');
        vfsStream::create(
            [
                'template.php' => '<?php throw new Exception("error"); ?>',
            ]
        );
        var_dump($this->template->render());
    }

    public function testRenderDoesNotLeakVariables(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?=json_encode(get_defined_vars())?>',
            ]
        );

        $this->assertSame('[]', $this->template->render());
    }

    public function testLayout(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php $this->layout("layout") ?>',
                'layout.php' => 'Hello World',
            ]
        );

        $this->assertSame('Hello World', $this->template->render());
    }

    public function testSection(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php $this->layout("layout")?><?php $this->start("test") ?>Hello World<?php $this->stop() ?>',
                'layout.php' => '<?php echo $this->section("test") ?>',
            ]
        );

        $this->assertSame('Hello World', $this->template->render());
    }

    public function testReplaceSection(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php $this->layout("template2")?><?php $this->start("test") ?>See this instead!<?php $this->stop() ?>',
                'template2.php' => '<?php $this->layout("layout")?><?php if($this->start("test")) { ?><?php exit() ?><?php } $this->stop() ?>',
                'layout.php' => '<?php echo $this->section("test", "initial content") ?>',
            ]
        );

        $this->assertSame('See this instead!', $this->template->render());
    }

    public function testStartSectionWithInvalidName(): void
    {
        // The section name "content" is reserved.
        $this->expectException(LogicException::class);

        vfsStream::create(
            [
                'template.php' => '<?php $this->start("content") ?>',
            ]
        );

        $this->template->render();
    }

    public function testNestSectionWithinAnotherSection(): void
    {
        // You cannot nest sections within other sections.
        $this->expectException(LogicException::class);

        vfsStream::create(
            [
                'template.php' => '<?php $this->start("section1") ?><?php $this->start("section2") ?>',
            ]
        );

        $this->template->render();
    }

    public function testStopSectionBeforeStarting(): void
    {
        // You must start a section before you can stop it.
        $this->expectException(LogicException::class);

        vfsStream::create(
            [
                'template.php' => '<?php $this->stop() ?>',
            ]
        );

        $this->template->render();
    }

    public function testSectionDefaultValue(): void
    {
        vfsStream::create([
            'template.php' => '<?php echo $this->section("test", "Default value") ?>',
        ]);

        $this->assertSame('Default value', $this->template->render());
    }

    public function testNullSection(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php $this->layout("layout") ?>',
                'layout.php' => '<?php if (is_null($this->section("test"))) echo "NULL" ?>',
            ]
        );

        $this->assertSame('NULL', $this->template->render());
    }

    public function testPushSection(): void
    {
        vfsStream::create(
            [
                'template.php' => implode('\n', [
                    '<?php $this->layout("layout")?>',
                    '<?php $this->push("scripts") ?><script src="example1.js"></script><?php $this->end() ?>',
                    '<?php $this->push("scripts") ?><script src="example2.js"></script><?php $this->end() ?>',
                ]),
                'layout.php' => '<?php echo $this->section("scripts") ?>',
            ]
        );

        $this->assertSame(
            '<script src="example1.js"></script><script src="example2.js"></script>',
            $this->template->render()
        );
    }

    public function testPushWithMultipleSections(): void
    {
        vfsStream::create(
            [
                'template.php' => implode('\n', [
                    '<?php $this->layout("layout")?>',
                    '<?php $this->push("scripts") ?><script src="example1.js"></script><?php $this->end() ?>',
                    '<?php $this->start("test") ?>test<?php $this->stop() ?>',
                    '<?php $this->push("scripts") ?><script src="example2.js"></script><?php $this->end() ?>',
                ]),
                'layout.php' => implode('\n', [
                    '<?php echo $this->section("test") ?>',
                    '<?php echo $this->section("scripts") ?>',
                ]),
            ]
        );

        $this->assertSame(
            'test\n<script src="example1.js"></script><script src="example2.js"></script>',
            $this->template->render()
        );
    }

    public function testFetchFunction(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php echo $this->fetch("fetched") ?>',
                'fetched.php' => 'Hello World',
            ]
        );

        $this->assertSame('Hello World', $this->template->render());
    }

    public function testFetchAutowiring(): void
    {
        vfsStream::create(['fetched.php' => 'Hello World',  ]);

        $engine = new Engine(vfsStream::url('templates'));
        $templateRendered = $engine->render(new TemplateClassTpl());
        $this->assertSame('Hello World', $templateRendered);
    }

    public function testBatchFunction(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php echo $this->batch("Jonathan", "uppercase|strtolower") ?>',
            ]
        );

        $this->assertSame('jonathan', $this->template->render());
    }

    public function testBatchFunctionWithInvalidFunction(): void
    {
        // The batch function could not find the "function_that_does_not_exist" function.
        $this->expectException(LogicException::class);

        vfsStream::create(
            [
                'template.php' => '<?php echo $this->batch("Jonathan", "function_that_does_not_exist") ?>',
            ]
        );

        $this->template->render();
    }

    public function testEscapeFunction(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php echo $this->escape("<strong>Jonathan</strong>") ?>',
            ]
        );

        $this->assertSame('&lt;strong&gt;Jonathan&lt;/strong&gt;', $this->template->render());
    }

    public function testEscapeFunctionBatch(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php echo $this->escape("<strong>Jonathan</strong>", "strtoupper|strrev") ?>',
            ]
        );

        $this->assertSame('&gt;GNORTS/&lt;NAHTANOJ&gt;GNORTS&lt;', $this->template->render());
    }

    public function testEscapeShortcutFunction(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?php echo $this->e("<strong>Jonathan</strong>") ?>',
            ]
        );

        $this->assertSame('&lt;strong&gt;Jonathan&lt;/strong&gt;', $this->template->render());
    }
}
