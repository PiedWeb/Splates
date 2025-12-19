<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\Value\Attr;
use PiedWeb\Splates\Template\Value\Html;
use PiedWeb\Splates\Template\Value\Js;
use PiedWeb\Splates\Template\Value\Text;

final class ValueObjectsTest extends TestCase
{
    public function testTextEscapesOnStringConversion(): void
    {
        $text = new Text('<script>alert("XSS")</script>');

        $this->assertSame('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', (string) $text);
    }

    public function testTextRawReturnsUnescaped(): void
    {
        $text = new Text('<b>Bold</b>');

        $this->assertSame('<b>Bold</b>', $text->raw());
    }

    public function testTextWithSpecialCharacters(): void
    {
        $text = new Text("Test & 'quotes' \"double\"");

        $this->assertSame('Test &amp; &#039;quotes&#039; &quot;double&quot;', (string) $text);
    }

    public function testHtmlOutputsUnescaped(): void
    {
        $html = new Html('<b>Bold</b>');

        $this->assertSame('<b>Bold</b>', (string) $html);
    }

    public function testHtmlTrustedFactory(): void
    {
        $html = Html::trusted('<div class="widget">Content</div>');

        $this->assertSame('<div class="widget">Content</div>', (string) $html);
    }

    public function testAttrEscapesForAttributeContext(): void
    {
        $attr = new Attr('value with "quotes"');

        $this->assertSame('value with &quot;quotes&quot;', (string) $attr);
    }

    public function testAttrRawReturnsUnescaped(): void
    {
        $attr = new Attr('test');

        $this->assertSame('test', $attr->raw());
    }

    public function testAttrWithSpecialCharacters(): void
    {
        $attr = new Attr("onclick='alert(1)'");

        // ENT_HTML5 uses &apos; for single quotes
        $this->assertSame('onclick=&apos;alert(1)&apos;', (string) $attr);
    }

    public function testJsEncodesAsJson(): void
    {
        $js = new Js(['key' => 'value']);

        $this->assertSame('{"key":"value"}', (string) $js);
    }

    public function testJsWithScriptTag(): void
    {
        $js = new Js('</script><script>alert(1)');

        // Should escape < and >
        $this->assertSame('"\u003C\/script\u003E\u003Cscript\u003Ealert(1)"', (string) $js);
    }

    public function testJsRawReturnsOriginalValue(): void
    {
        $data = ['foo' => 'bar', 'number' => 42];
        $js = new Js($data);

        $this->assertSame($data, $js->raw());
    }

    public function testJsWithNumber(): void
    {
        $js = new Js(42);

        $this->assertSame('42', (string) $js);
    }

    public function testJsWithBool(): void
    {
        $js = new Js(true);

        $this->assertSame('true', (string) $js);
    }

    public function testJsWithNull(): void
    {
        $js = new Js(null);

        $this->assertSame('null', (string) $js);
    }
}
