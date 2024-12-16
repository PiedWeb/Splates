<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Extension;

use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Extension\Asset;

class AssetTest extends TestCase
{
    protected function setUp(): void
    {
        vfsStream::setup('assets');
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Asset::class, new Asset(vfsStream::url('assets')));
        $this->assertInstanceOf(Asset::class, new Asset(vfsStream::url('assets'), true));
        $this->assertInstanceOf(Asset::class, new Asset(vfsStream::url('assets'), false));
    }

    public function testRegister(): void
    {
        $engine = new Engine();
        $extension = new Asset(vfsStream::url('assets'));
        $extension->register($engine);
        $this->assertTrue($engine->doesFunctionExist('asset'));
    }

    public function testCachedAssetUrl(): void
    {
        vfsStream::create(
            [
                'styles.css' => '',
            ]
        );

        $extension = new Asset(vfsStream::url('assets'));
        $this->assertSame('styles.css?v=' . filemtime(vfsStream::url('assets/styles.css')), $extension->cachedAssetUrl('styles.css'));
        $this->assertSame('/styles.css?v=' . filemtime(vfsStream::url('assets/styles.css')), $extension->cachedAssetUrl('/styles.css'));
    }

    public function testCachedAssetUrlInFolder(): void
    {
        vfsStream::create(
            [
                'folder' => [
                    'styles.css' => '',
                ],
            ]
        );

        $extension = new Asset(vfsStream::url('assets'));
        $this->assertSame('/folder/styles.css?v=' . filemtime(vfsStream::url('assets/folder/styles.css')), $extension->cachedAssetUrl('/folder/styles.css'));
    }

    public function testCachedAssetUrlUsingFilenameMethod(): void
    {
        vfsStream::create(
            [
                'styles.css' => '',
            ]
        );

        $extension = new Asset(vfsStream::url('assets'), true);
        $this->assertSame('styles.' . filemtime(vfsStream::url('assets/styles.css')) . '.css', $extension->cachedAssetUrl('styles.css'));
    }

    public function testFileNotFoundException(): void
    {
        // Unable to locate the asset "styles.css" in the @assets directory.
        $this->expectException(LogicException::class);

        $extension = new Asset(vfsStream::url('assets'));
        $extension->cachedAssetUrl('styles.css');
    }
}
