<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\Folder;

class FolderTest extends TestCase
{
    private Folder $folder;

    protected function setUp(): void
    {
        vfsStream::setup('templates');

        $this->folder = new Folder('folder', vfsStream::url('templates'));
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Folder::class, $this->folder);
    }

    public function testSetAndGetPath(): void
    {
        vfsStream::create(
            [
                'folder' => [],
            ]
        );

        $this->folder->setPath(vfsStream::url('templates/folder'));
        $this->assertSame(vfsStream::url('templates/folder'), $this->folder->getPath());
    }

    public function testSetInvalidPath(): void
    {
        // The specified directory path "vfs://does/not/exist" does not exist.
        $this->expectException(LogicException::class);
        $this->folder->setPath(vfsStream::url('does/not/exist'));
    }
}
