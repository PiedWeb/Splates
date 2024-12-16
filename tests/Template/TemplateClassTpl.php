<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Tests\Template;

use PiedWeb\Splates\Template\TemplateClassInterface;
use PiedWeb\Splates\Template\TemplateFetch;

class TemplateClassTpl implements TemplateClassInterface
{
    public function display(TemplateFetch $f): void
    {
        echo $f("fetched");
    }
}
