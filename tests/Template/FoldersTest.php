<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\Folder;
use PiedWeb\Splates\Template\Folders;

class FoldersTest extends TestCase
{
    private Folders $folders;

    protected function setUp(): void
    {
        vfsStream::setup('templates');

        $this->folders = new Folders();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Folders::class, $this->folders);
    }

    public function testAddFolder(): void
    {
        $this->assertInstanceOf(Folders::class, $this->folders->add('name', vfsStream::url('templates')));
        $this->assertSame('vfs://templates', $this->folders->get('name')->getPath());
    }

    public function testAddFolderWithNamespaceConflict(): void
    {
        // The template folder "name" is already being used.
        $this->expectException(LogicException::class);
        $this->folders->add('name', vfsStream::url('templates'));
        $this->folders->add('name', vfsStream::url('templates'));
    }

    public function testAddFolderWithInvalidDirectory(): void
    {
        // The specified directory path "vfs://does/not/exist" does not exist.
        $this->expectException(LogicException::class);
        $this->folders->add('name', vfsStream::url('does/not/exist'));
    }

    public function testRemoveFolder(): void
    {
        $this->folders->add('folder', vfsStream::url('templates'));
        $this->assertTrue($this->folders->exists('folder'));
        $this->assertInstanceOf(Folders::class, $this->folders->remove('folder'));
        $this->assertFalse($this->folders->exists('folder'));
    }

    public function testRemoveFolderWithInvalidDirectory(): void
    {
        // The template folder "name" was not found.
        $this->expectException(LogicException::class);
        $this->folders->remove('name');
    }

    public function testGetFolder(): void
    {
        $this->folders->add('name', vfsStream::url('templates'));
        $this->assertInstanceOf(Folder::class, $this->folders->get('name'));
        $this->assertSame(vfsStream::url('templates'), $this->folders->get('name')->getPath());
    }

    public function testGetNonExistentFolder(): void
    {
        // The template folder "name" was not found.
        $this->expectException(LogicException::class);
        $this->assertInstanceOf(Folder::class, $this->folders->get('name'));
    }

    public function testFolderExists(): void
    {
        $this->assertFalse($this->folders->exists('name'));
        $this->folders->add('name', vfsStream::url('templates'));
        $this->assertTrue($this->folders->exists('name'));
    }
}
