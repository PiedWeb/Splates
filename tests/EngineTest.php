<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests;

use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Extension\Asset;
use PiedWeb\Splates\Extension\URI;
use PiedWeb\Splates\Template\Folders;
use PiedWeb\Splates\Template\Func;
use PiedWeb\Splates\Template\Template;

final class EngineTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        vfsStream::setup('templates');

        $this->engine = new Engine(vfsStream::url('templates'));
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Engine::class, $this->engine);
    }

    public function testSetDirectory(): void
    {
        $this->assertInstanceOf(Engine::class, $this->engine->setDirectory(vfsStream::url('templates')));
        $this->assertSame(vfsStream::url('templates'), $this->engine->getDirectory());
    }

    public function testSetNullDirectory(): void
    {
        $this->assertInstanceOf(Engine::class, $this->engine->setDirectory(null));
        $this->assertNull($this->engine->getDirectory());
    }

    public function testSetInvalidDirectory(): void
    {
        // The specified path "vfs://does/not/exist" does not exist.
        $this->expectException(LogicException::class);
        $this->engine->setDirectory(vfsStream::url('does/not/exist'));
    }

    public function testGetDirectory(): void
    {
        $this->assertSame(vfsStream::url('templates'), $this->engine->getDirectory());
    }

    public function testSetFileExtension(): void
    {
        $this->assertInstanceOf(Engine::class, $this->engine->setFileExtension('tpl'));
        $this->assertSame('tpl', $this->engine->getFileExtension());
    }

    public function testGetFileExtension(): void
    {
        $this->assertSame('php', $this->engine->getFileExtension());
    }

    public function testAddFolder(): void
    {
        vfsStream::create(
            [
                'folder' => [
                    'template.php' => '',
                ],
            ]
        );

        $this->assertInstanceOf(Engine::class, $this->engine->addFolder('folder', vfsStream::url('templates/folder')));
        $this->assertSame('vfs://templates/folder', $this->engine->getFolders()->get('folder')->getPath());
    }

    public function testAddFolderWithNamespaceConflict(): void
    {
        // The template folder "name" is already being used.
        $this->expectException(LogicException::class);
        $this->engine->addFolder('name', vfsStream::url('templates'));
        $this->engine->addFolder('name', vfsStream::url('templates'));
    }

    public function testAddFolderWithInvalidDirectory(): void
    {
        // The specified directory path "vfs://does/not/exist" does not exist.
        $this->expectException(LogicException::class);
        $this->engine->addFolder('namespace', vfsStream::url('does/not/exist'));
    }

    public function testRemoveFolder(): void
    {
        vfsStream::create(
            [
                'folder' => [
                    'template.php' => '',
                ],
            ]
        );

        $this->engine->addFolder('folder', vfsStream::url('templates/folder'));
        $this->assertTrue($this->engine->getFolders()->exists('folder'));
        $this->assertInstanceOf(Engine::class, $this->engine->removeFolder('folder'));
        $this->assertFalse($this->engine->getFolders()->exists('folder'));
    }

    public function testGetFolders(): void
    {
        $this->assertInstanceOf(Folders::class, $this->engine->getFolders());
    }

    public function testAddData(): void
    {
        $this->engine->addData(['name' => 'Jonathan']);
        $data = $this->engine->getData();
        $this->assertSame('Jonathan', $data['name']);
    }

    public function testAddDataWithTemplate(): void
    {
        $this->engine->addData(['name' => 'Jonathan'], 'template');
        $data = $this->engine->getData('template');
        $this->assertSame('Jonathan', $data['name']);
    }

    public function testAddDataWithTemplates(): void
    {
        $this->engine->addData(['name' => 'Jonathan'], ['template1', 'template2']);
        $data = $this->engine->getData('template1');
        $this->assertSame('Jonathan', $data['name']);
    }

    public function testRegisterFunction(): void
    {
        vfsStream::create(
            [
                'template.php' => '<?=$this->uppercase($name)?>',
            ]
        );

        $this->engine->registerFunction('uppercase', 'strtoupper');
        $this->assertInstanceOf(Func::class, $this->engine->getFunction('uppercase'));
        $this->assertSame('strtoupper', $this->engine->getFunction('uppercase')->getCallback());
    }

    public function testDropFunction(): void
    {
        $this->engine->registerFunction('uppercase', 'strtoupper');
        $this->assertTrue($this->engine->doesFunctionExist('uppercase'));
        $this->engine->dropFunction('uppercase');
        $this->assertFalse($this->engine->doesFunctionExist('uppercase'));
    }

    public function testDropInvalidFunction(): void
    {
        // The template function "some_function_that_does_not_exist" was not found.
        $this->expectException(LogicException::class);
        $this->engine->dropFunction('some_function_that_does_not_exist');
    }

    public function testGetFunction(): void
    {
        $this->engine->registerFunction('uppercase', 'strtoupper');
        $function = $this->engine->getFunction('uppercase');

        $this->assertInstanceOf(Func::class, $function);
        $this->assertSame('uppercase', $function->getName());
        $this->assertSame('strtoupper', $function->getCallback());
    }

    public function testGetInvalidFunction(): void
    {
        // The template function "some_function_that_does_not_exist" was not found.
        $this->expectException(LogicException::class);
        $this->engine->getFunction('some_function_that_does_not_exist');
    }

    public function testDoesFunctionExist(): void
    {
        $this->engine->registerFunction('uppercase', 'strtoupper');
        $this->assertTrue($this->engine->doesFunctionExist('uppercase'));
    }

    public function testDoesFunctionNotExist(): void
    {
        $this->assertFalse($this->engine->doesFunctionExist('some_function_that_does_not_exist'));
    }

    public function testLoadExtension(): void
    {
        $this->assertFalse($this->engine->doesFunctionExist('uri'));
        $this->assertInstanceOf(Engine::class, $this->engine->loadExtension(new URI()));
        $this->assertTrue($this->engine->doesFunctionExist('uri'));
    }

    public function testLoadExtensions(): void
    {
        $this->assertFalse($this->engine->doesFunctionExist('uri'));
        $this->assertFalse($this->engine->doesFunctionExist('asset'));
        $this->assertInstanceOf(
            Engine::class,
            $this->engine->loadExtensions(
                [
                    new URI(),
                    new Asset('public'),
                ]
            )
        );
        $this->assertTrue($this->engine->doesFunctionExist('uri'));
        $this->assertTrue($this->engine->doesFunctionExist('asset'));
    }

    public function testGetTemplatePath(): void
    {
        $this->assertSame('vfs://templates/template.php', $this->engine->path('template'));
    }

    public function testTemplateExists(): void
    {
        $this->assertFalse($this->engine->exists('template'));

        vfsStream::create(
            [
                'template.php' => '',
            ]
        );

        $this->assertTrue($this->engine->exists('template'));
    }

    public function testMakeTemplate(): void
    {
        vfsStream::create(
            [
                'template.php' => '',
            ]
        );

        $this->assertInstanceOf(Template::class, $this->engine->make('template'));
    }

    public function testMakeTemplateWithData(): void
    {
        vfsStream::create(
            [
                'template.php' => '',
            ]
        );

        $template = $this->engine->make('template', ['name' => 'Jonathan']);
        $this->assertInstanceOf(Template::class, $template);
        $this->assertSame(['name' => 'Jonathan'], $template->data());
    }

    public function testRenderTemplate(): void
    {
        vfsStream::create(
            [
                'template.php' => 'Hello!',
            ]
        );

        $this->assertSame('Hello!', $this->engine->render('template'));
    }
}
