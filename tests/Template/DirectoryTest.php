<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\Directory;

final class DirectoryTest extends TestCase
{
    private Directory $directory;

    protected function setUp(): void
    {
        vfsStream::setup('templates');

        $this->directory = new Directory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Directory::class, $this->directory);
    }

    public function testSetDirectory(): void
    {
        $this->assertInstanceOf(Directory::class, $this->directory->set(vfsStream::url('templates')));
        $this->assertSame($this->directory->get(), vfsStream::url('templates'));
    }

    public function testSetNullDirectory(): void
    {
        $this->assertInstanceOf(Directory::class, $this->directory->set(null));
        $this->assertNull($this->directory->get());
    }

    public function testSetInvalidDirectory(): void
    {
        // The specified path "vfs://does/not/exist" does not exist.
        $this->expectException(LogicException::class);
        $this->directory->set(vfsStream::url('does/not/exist'));
    }

    public function testGetDirectory(): void
    {
        $this->assertNull($this->directory->get());
    }
}
