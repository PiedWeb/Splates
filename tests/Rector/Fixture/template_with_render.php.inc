<?php

namespace App\Templates;

use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateFetch;
use PiedWeb\Splates\Template\TemplateEscape;
use PiedWeb\Splates\Template\TemplateClassInterface;

class PageTpl implements TemplateClassInterface
{
    public function __construct(
        public string $title,
        public string $content,
    ) {}

    public function display(Template $t, TemplateFetch $f, TemplateEscape $e, string $title, string $content): void
    {
        echo $f(new SidebarTpl());
        echo $e($title);
    }
}

-----
<?php

namespace App\Templates;

use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateFetch;
use PiedWeb\Splates\Template\TemplateEscape;
use PiedWeb\Splates\Template\TemplateClassInterface;

class PageTpl extends \PiedWeb\Splates\Template\TemplateAbstract
{
    public function __construct(
        #[\PiedWeb\Splates\Template\Attribute\TemplateData]
        public string $title,
        #[\PiedWeb\Splates\Template\Attribute\TemplateData]
        public string $content,
    ) {}
    public function __invoke(): void
    {
        echo $this->render(new SidebarTpl());
        echo $this->e($title);
    }
}
