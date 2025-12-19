<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Template\Data;

final class DataTest extends TestCase
{
    private Data $template_data;

    protected function setUp(): void
    {
        $this->template_data = new Data();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Data::class, $this->template_data);
    }

    public function testAddDataToAllTemplates(): void
    {
        $this->template_data->add(['name' => 'Jonathan']);
        $data = $this->template_data->get();
        $this->assertSame('Jonathan', $data['name']);
    }

    public function testAddDataToOneTemplate(): void
    {
        $this->template_data->add(['name' => 'Jonathan'], 'template');
        $data = $this->template_data->get('template');
        $this->assertSame('Jonathan', $data['name']);
    }

    public function testAddDataToOneTemplateAgain(): void
    {
        $this->template_data->add(['firstname' => 'Jonathan'], 'template');
        $this->template_data->add(['lastname' => 'Reinink'], 'template');

        $data = $this->template_data->get('template');
        $this->assertSame('Reinink', $data['lastname']);
    }

    public function testAddDataToSomeTemplates(): void
    {
        $this->template_data->add(['name' => 'Jonathan'], ['template1', 'template2']);
        $data = $this->template_data->get('template1');
        $this->assertSame('Jonathan', $data['name']);
    }
}
