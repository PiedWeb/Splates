<?php

namespace Templates;

use Closure;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

/**
 * Main layout component demonstrating:
 * - Global service injection via #[TemplateData(global: true)]
 * - Required and optional Closure slots
 * - Clean HTML structure with conditional rendering
 */
class Layout extends TemplateAbstract
{
    #[TemplateData(global: true)]
    public AppService $app;

    public function __construct(
        #[TemplateData]
        public string $title,
        #[TemplateData]
        public Closure $content,
        #[TemplateData]
        public ?Closure $header = null,
        #[TemplateData]
        public ?Closure $sidebar = null,
        #[TemplateData]
        public ?Closure $scripts = null,
    ) {
    }

    public function __invoke(): void
    { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($this->title) ?> | <?= $this->e($this->app->getAppName()) ?></title>
    <link rel="stylesheet" href="<?= $this->e($this->app->asset('css/style.css')) ?>">
</head>
<body>
    <nav class="navbar">
        <a href="<?= $this->e($this->app->url('/')) ?>" class="brand">
            <?= $this->e($this->app->getAppName()) ?>
        </a>
        <?php if ($this->app->isAuthenticated()): ?>
            <?php $user = $this->app->getCurrentUser(); ?>
            <span class="user-info">
                Welcome, <?= $this->e($user?->name ?? 'Guest') ?>
            </span>
        <?php endif ?>
    </nav>

    <?php if ($this->header): ?>
    <header class="page-header">
        <?= ($this->header)() ?>
    </header>
    <?php endif ?>

    <div class="container <?= $this->sidebar ? 'with-sidebar' : '' ?>">
        <?php if ($this->sidebar): ?>
        <aside class="sidebar">
            <?= ($this->sidebar)() ?>
        </aside>
        <?php endif ?>

        <main class="content">
            <?= ($this->content)() ?>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> <?= $this->e($this->app->getAppName()) ?> v<?= $this->e($this->app->getVersion()) ?></p>
    </footer>

    <script src="<?= $this->e($this->app->asset('js/app.js')) ?>"></script>
    <?php if ($this->scripts): ?>
    <?= ($this->scripts)() ?>
    <?php endif ?>
</body>
</html>
<?php }
    }
