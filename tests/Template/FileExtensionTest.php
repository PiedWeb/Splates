<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\FileExtension;

class FileExtensionTest extends TestCase
{
    public function testGetFileExtension(): void
    {
        $this->assertSame('php', (new FileExtension())->fileExtension);
    }
}
