<?php

namespace Templates;

use Closure;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

/**
 * Reusable Card component demonstrating:
 * - Simple component with required and optional slots
 * - Clean, focused single-purpose component
 */
class Card extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $title,
        #[TemplateData]
        public Closure $content,
        #[TemplateData]
        public ?Closure $footer = null,
        #[TemplateData]
        public string $class = '',
    ) {
    }

    public function __invoke(): void
    { ?>
<div class="card <?= $this->e($this->class) ?>">
    <div class="card-header">
        <h2><?= $this->e($this->title) ?></h2>
    </div>
    <div class="card-body">
        <?= ($this->content)() ?>
    </div>
    <?php if ($this->footer): ?>
    <div class="card-footer">
        <?= ($this->footer)() ?>
    </div>
    <?php endif ?>
</div>
<?php }
    }
