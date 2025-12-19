<?php

namespace App\Templates;

use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateFetch;
use PiedWeb\Splates\Template\TemplateEscape;
use PiedWeb\Splates\Template\TemplateClassInterface;

class ProfileTpl implements TemplateClassInterface
{
    public function __construct(
        public string $name,
    ) {}

    public function display(Template $t, TemplateFetch $f, TemplateEscape $e, string $name): void
    {
        echo $e($name);
    }
}

-----
<?php

namespace App\Templates;

use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateFetch;
use PiedWeb\Splates\Template\TemplateEscape;
use PiedWeb\Splates\Template\TemplateClassInterface;

class ProfileTpl extends \PiedWeb\Splates\Template\TemplateAbstract
{
    public function __construct(
        #[\PiedWeb\Splates\Template\Attribute\TemplateData]
        public string $name,
    ) {}
    public function __invoke(): void
    {
        echo $this->e($name);
    }
}
