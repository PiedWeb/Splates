<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Folder;
use PiedWeb\Splates\Template\Name;

final class NameTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        vfsStream::setup('templates');
        vfsStream::create(
            [
                'template.php' => '',
                'fallback.php' => '',
                'folder' => [
                    'template.php' => '',
                ],
            ]
        );

        $this->engine = new Engine(vfsStream::url('templates'));
        $this->engine->addFolder('folder', vfsStream::url('templates/folder'), true);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Name::class, new Name($this->engine, 'template'));
    }

    public function testGetEngine(): void
    {
        $name = new Name($this->engine, 'template');

        $this->assertInstanceOf(Engine::class, $name->engine);
    }

    public function testGetName(): void
    {
        $name = new Name($this->engine, 'template');

        $this->assertSame('template', $name->getName());
    }

    public function testGetFolder(): void
    {
        $name = new Name($this->engine, 'folder::template');
        $folder = $name->getFolder();

        $this->assertInstanceOf(Folder::class, $folder);
        $this->assertSame('folder', $name->getFolder()?->name);
    }

    public function testGetFile(): void
    {
        $name = new Name($this->engine, 'template');

        $this->assertSame('template.php', $name->getFile());
    }

    public function testGetPath(): void
    {
        $name = new Name($this->engine, 'template');

        $this->assertSame('vfs://templates/template.php', $name->getPath());
    }

    public function testGetPathWithFolder(): void
    {
        $name = new Name($this->engine, 'folder::template');

        $this->assertSame('vfs://templates/folder/template.php', $name->getPath());
    }

    public function testGetPathWithFolderFallback(): void
    {
        $name = new Name($this->engine, 'folder::fallback');

        $this->assertSame('vfs://templates/fallback.php', $name->getPath());
    }

    public function testTemplateExists(): void
    {
        $name = new Name($this->engine, 'template');

        $this->assertTrue($name->doesPathExist());
    }

    public function testTemplateDoesNotExist(): void
    {
        $name = new Name($this->engine, 'missing');

        $this->assertFalse($name->doesPathExist());
    }

    public function testParse(): void
    {
        $name = new Name($this->engine, 'template');

        $this->assertSame('template', $name->getName());
        $this->assertNotInstanceOf(Folder::class, $name->getFolder());
        $this->assertSame('template.php', $name->getFile());
    }

    public function testParseWithNoDefaultDirectory(): void
    {
        // The default directory has not been defined.
        $this->expectException(LogicException::class);

        $this->engine->setDirectory(null);
        $name = new Name($this->engine, 'template');
        $name->getPath();
    }

    public function testParseWithEmptyTemplateName(): void
    {
        // The template name cannot be empty.
        $this->expectException(LogicException::class);

        $name = new Name($this->engine, '');
    }

    public function testParseWithFolder(): void
    {
        $name = new Name($this->engine, 'folder::template');

        $this->assertSame('folder::template', $name->getName());
        $this->assertSame('folder', $name->getFolder()?->name);
        $this->assertSame('template.php', $name->getFile());
    }

    public function testParseWithFolderAndEmptyTemplateName(): void
    {

        $this->expectException(LogicException::class);
        new Name($this->engine, 'folder::');
    }

    public function testParseWithInvalidName(): void
    {
        // Do not use the folder namespace separator "::" more than once.
        $this->expectException(LogicException::class);

        $name = new Name($this->engine, 'folder::template::wrong');
    }

    public function testParseWithNoFileExtension(): void
    {
        $this->engine->setFileExtension('');

        $name = new Name($this->engine, 'template.php');

        $this->assertSame('template.php', $name->getName());
        $this->assertNotInstanceOf(Folder::class, $name->getFolder());
        $this->assertSame('template.php', $name->getFile());
    }
}
