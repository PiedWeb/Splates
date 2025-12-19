# Splates: Type-Safe PHP Template Engine

A native PHP template engine with full IDE autocompletion and PHPStan support. Fork of [league/plates](https://github.com/thephpleague/plates), redesigned for modern PHP development.

[![Latest Version](https://img.shields.io/github/release/piedweb/splates.svg?style=flat-square)](https://github.com/piedweb/splates/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/piedweb/splates/php.yml?style=flat-square)](https://github.com/piedweb/splates/actions)

## Why Splates?

- **Full IDE support** - Constructor parameters with `#[TemplateData]` provide autocomplete everywhere
- **PHPStan max level** - Every template is statically analyzable
- **No magic** - No string-based template names, no runtime errors from typos
- **Slots pattern** - Layouts are just components with `Closure` properties (no magic sections)
- **Value objects** - `Text`, `Html`, `Attr`, `Js` for context-aware escaping
- **Global services** - Inject dependencies via `Engine::addGlobal()` with `#[TemplateData(global: true)]`

## Installation

```bash
composer require piedweb/splates
```

Requires PHP 8.2+.

## Quick Start

### 1. Create a template

```php
<?php
// src/Templates/ProfileTpl.php

namespace App\Templates;

use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;
use PiedWeb\Splates\Template\Value\Text;

class ProfileTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public Text $name,       // Auto-escapes on output
        #[TemplateData]
        public Text $email,      // No need to call $this->e()
    ) {}

    public function __invoke(): void
    { ?>
        <div class="profile">
            <h1><?= $this->name ?></h1>
            <p>Email: <?= $this->email ?></p>
        </div>
    <?php }
}
```

### 2. Render it

```php
<?php

use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Value\Text;
use App\Templates\ProfileTpl;

$engine = new Engine();

// Full IDE autocompletion on constructor parameters!
echo $engine->render(new ProfileTpl(
    name: new Text('John Doe'),
    email: new Text('john@example.com'),
));
```

---

## Core Concepts

### Template Creation Options

Templates implement `TemplateClassInterface` and can be created in several ways:

#### 1. Minimal (no helpers needed)
```php
class Hello implements TemplateClassInterface
{
    public function __construct(public string $name) {}

    public function __invoke(): void
    {
        echo "Hello, {$this->name}!";
    }
}
```

#### 2. With `__invoke()` parameter injection
```php
class Profile implements TemplateClassInterface
{
    public function __construct(public string $name) {}

    public function __invoke(TemplateFetch $f, TemplateEscape $e): void
    {
        echo '<h1>' . $e($this->name) . '</h1>';
        echo $f(new SidebarTpl());
    }
}
```

#### 3. With `#[Inject]` property injection
```php
use PiedWeb\Splates\Template\Attribute\Inject;

class Profile implements TemplateClassInterface
{
    #[Inject]
    protected TemplateFetch $f;

    #[Inject]
    protected TemplateEscape $e;

    public function __construct(public string $name) {}

    public function __invoke(): void
    {
        echo '<h1>' . ($this->e)($this->name) . '</h1>';
        echo ($this->f)(new SidebarTpl());
    }
}
```

#### 4. Extending `TemplateAbstract` (full helper methods)
```php
class MyTemplate extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public User $user,
        #[TemplateData]
        public array $items = [],  // Optional with default
    ) {}

    public function __invoke(): void
    {
        echo $this->render(new LayoutTpl(...));
        echo $this->e($this->user->name);
    }
}
```

### Helper Methods

Inside templates, you have access to:

| Method | Description |
|--------|-------------|
| `$this->e($value)` | Escape value for HTML output |
| `$this->render(new Tpl())` | Render a child template |
| `$this->capture(fn() => ...)` | Capture output as string |
| `$this->slot(fn() => ...)` | Create a lazy slot (syntactic sugar) |

### Escaping

Always escape user data:

```php
<h1><?= $this->e($this->title) ?></h1>
```

Or use auto-escaping value objects:

```php
use PiedWeb\Splates\Template\Value\Text;

// Text auto-escapes when converted to string
echo new Text('<script>alert("XSS")</script>');
// Output: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
```

### IDE Syntax Highlighting with Heredoc

When mixing PHP and HTML inside closures, VS Code's syntax highlighter often loses context. For better highlighting, use **heredoc syntax**:

```php
private function renderSidebar(): string
{
    // Extract and escape values first
    $dashboardUrl = $this->e($this->app->url('/dashboard'));
    $usersUrl = $this->e($this->app->url('/users'));

    // Heredoc provides proper HTML highlighting in VS Code
    return <<<HTML
        <nav class="sidebar-nav">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="{$dashboardUrl}">Dashboard</a></li>
                <li><a href="{$usersUrl}">All Users</a></li>
            </ul>
        </nav>
        HTML;
}
```

**Trade-offs:**
- Heredoc: Better IDE highlighting, but requires pre-computing variables
- Closure with `?>`: Can use inline PHP expressions (`<?php if ?>`), but inconsistent highlighting

See `exampleTemplateClass/Templates/Profile.php` for a complete heredoc example.

---

## Layouts with Slots Pattern

Instead of magic sections, Splates uses typed `Closure` properties (slots):

### Layout Template

```php
<?php
// src/Templates/LayoutTpl.php

namespace App\Templates;

use Closure;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

class LayoutTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $title,
        #[TemplateData]
        public Closure $content,           // Required slot
        #[TemplateData]
        public ?Closure $scripts = null,   // Optional slot
    ) {}

    public function __invoke(): void
    { ?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->e($this->title) ?></title>
</head>
<body>
    <main><?= ($this->content)() ?></main>

    <?php if ($this->scripts): ?>
    <?= ($this->scripts)() ?>
    <?php endif ?>
</body>
</html>
    <?php }
}
```

### Page Template

Use the `$this->slot()` helper for clean inline slots:

```php
<?php
// src/Templates/UserPageTpl.php

namespace App\Templates;

use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

class UserPageTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public User $user,
    ) {}

    public function __invoke(): void
    {
        // Clean syntax with $this->slot() helper
        echo $this->render(new LayoutTpl(
            title: $this->user->getName(),
            content: $this->slot(function() { ?>

                <h1><?= $this->e($this->user->getName()) ?></h1>
                <p>Email: <?= $this->e($this->user->getEmail()) ?></p>
                <?= $this->render(new SidebarTpl()) ?>

            <?php }),
            scripts: $this->slot(function() { ?>

                <script>console.log("loaded")</script>

            <?php }),
        ));
    }
}
```

For complex slots, you can still use private methods:

```php
public function __invoke(): void
{
    echo $this->render(new LayoutTpl(
        title: $this->user->getName(),
        content: fn() => $this->renderContent(),  // Delegate to method
    ));
}

private function renderContent(): string
{
    return $this->capture(function() { ?>
        <h1><?= $this->e($this->user->getName()) ?></h1>
        <!-- Complex content... -->
    <?php });
}
```

### Or use the `Slot` value object for cleaner syntax

```php
use PiedWeb\Splates\Template\Value\Slot;

class LayoutTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $title,
        #[TemplateData]
        public Slot $content,  // Slot instead of Closure
    ) {}

    public function __invoke(): void
    { ?>
        <main><?= $this->content ?></main>
    <?php }
}

// Usage
echo $this->render(new LayoutTpl(
    title: 'My Page',
    content: new Slot(fn() => '<p>Page content</p>'),
));
```

---

## Global Services

Inject services that are available to ALL templates:

### Setup

```php
$engine = new Engine();

// Register global services
$engine->addGlobal('ext', $templateExtension);
$engine->addGlobal('router', $router);
```

### Usage in Templates

```php
class MyTemplate extends TemplateAbstract
{
    // Auto-injected from globals
    #[TemplateData(global: true)]
    public TemplateExtension $ext;

    #[TemplateData(global: true)]
    public RouterInterface $router;

    public function __construct(
        #[TemplateData]
        public User $user,
    ) {}

    public function __invoke(): void
    { ?>
        <a href="<?= $this->ext->url('user_profile', ['id' => $this->user->getId()]) ?>">
            <?= $this->e($this->user->getName()) ?>
        </a>
    <?php }
}
```

### App-Specific Base Template

Create your own base class for app-wide helpers:

```php
<?php
// src/Templates/AppTemplate.php

namespace App\Templates;

use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

abstract class AppTemplate extends TemplateAbstract
{
    // Auto-injected to ALL templates
    #[TemplateData(global: true)]
    public TemplateExtension $ext;

    // Convenience helpers
    protected function url(string $route, array $params = []): string
    {
        return $this->ext->url($route, $params);
    }

    protected function user(): ?User
    {
        return $this->ext->getUser();
    }
}

// All app templates extend AppTemplate
class DashboardTpl extends AppTemplate
{
    public function __invoke(): void
    { ?>
        <p>Welcome, <?= $this->e($this->user()?->getName() ?? 'Guest') ?></p>
    <?php }
}
```

---

## Value Objects for Safe Output

Splates provides context-aware value objects:

| Class | Use Case | Example |
|-------|----------|---------|
| `Text` | HTML text content | `<p><?= $text ?></p>` |
| `Html` | Pre-escaped HTML | `<?= $html ?>` |
| `Attr` | HTML attributes | `<div class="<?= $attr ?>">`  |
| `Js` | JavaScript values | `<script>var x = <?= $js ?>;</script>` |

### Examples

```php
use PiedWeb\Splates\Template\Value\{Text, Html, Attr, Js};

// Text - auto-escapes for HTML content
$name = new Text('<script>bad</script>');
echo "<p>$name</p>";  // <p>&lt;script&gt;bad&lt;/script&gt;</p>

// Html - for trusted pre-escaped content
$content = Html::trusted('<strong>Safe HTML</strong>');
echo $content;  // <strong>Safe HTML</strong>

// Attr - escapes for HTML attributes
$class = new Attr('my-class" onclick="bad');
echo "<div class=\"$class\">";  // <div class="my-class&quot; onclick=&quot;bad">

// Js - JSON-encodes for JavaScript
$data = new Js(['user' => 'John', 'count' => 42]);
echo "<script>var config = $data;</script>";
// <script>var config = {"user":"John","count":42};</script>
```

---

## Caching

For production, enable reflection caching:

```php
$engine = new Engine(cacheDir: '/path/to/cache');

// Warm cache on deploy
$engine->getTemplateDataResolver()->warmCache([
    ProfileTpl::class,
    LayoutTpl::class,
    // ... all template classes
]);
```

---

## Development

```bash
composer test    # Run tests
composer stan    # Run PHPStan
composer format  # Format code
composer rector  # Run Rector
```

---

## Migrating from v3 to v4

### Breaking Changes

| v3 | v4 |
|----|-----|
| `implements TemplateClassInterface` | `extends TemplateAbstract` or `implements TemplateClassInterface` |
| `display(Template $t, TemplateFetch $f, TemplateEscape $e, ...)` | `__invoke(): void` or `__invoke(TemplateFetch $f, TemplateEscape $e): void` |
| `$e($value)` | `$this->e($value)` or `$e($value)` (with parameter injection) |
| `$f(new Tpl())` | `$this->render(new Tpl())` or `$f(new Tpl())` (with parameter injection) |
| `$t->layout(new Layout())` + sections | Slots pattern with `Closure` |
| `$t->section('content')` | `($this->content)()` |
| Extension system | `#[TemplateData(global: true)]` |
| Path resolution (folders, themes) | Removed - use PSR-4 autoloading |

### Automated Migration with Rector

A Rector rule is included to automate most of the migration:

```php
// rector.php
use PiedWeb\Splates\Rector\MigrateTemplateToV4Rector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src/Templates'])
    ->withRules([
        MigrateTemplateToV4Rector::class,
    ]);
```

Run:
```bash
vendor/bin/rector process
```

### What Rector Does

1. Changes `implements TemplateClassInterface` to `extends TemplateAbstract`
2. Adds `#[TemplateData]` to constructor properties
3. Renames `display()` to `__invoke()` and removes `Template $t`, `TemplateFetch $f`, `TemplateEscape $e` parameters
4. Transforms `$e($x)` to `$this->e($x)`
5. Transforms `$f(new Tpl())` to `$this->render(new Tpl())`

### Manual Steps After Rector

1. **Remove old imports**, add new ones:
   ```php
   // Remove
   use PiedWeb\Splates\Template\Template;
   use PiedWeb\Splates\Template\TemplateFetch;
   use PiedWeb\Splates\Template\TemplateEscape;
   use PiedWeb\Splates\Template\TemplateClassInterface;

   // Add
   use PiedWeb\Splates\Template\Attribute\TemplateData;
   use PiedWeb\Splates\Template\TemplateAbstract;
   ```

2. **Convert sections to slots** - see examples below

3. **Update global service injection** - see examples below

---

## Migration Examples (Real-World)

### Example 1: Simple Template

**Before (v3)**
```php
<?php

namespace App\Templates;

use App\Plates\AbstractTemplate;
use App\Plates\TemplateExtension;
use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateClassInterface;

class LogoTpl extends AbstractTemplate implements TemplateClassInterface
{
    public function display(
        Template $t,
        TemplateExtension $ext,
        string $style = '',
    ): void { ?>
<svg style="<?= $style ?>">...</svg>
    <?php }

    public function __construct(public string $style = '') {}
}
```

**After (v4)**
```php
<?php

namespace App\Templates;

use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

class LogoTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $style = '',
    ) {}

    public function __invoke(): void
    { ?>
<svg style="<?= $this->e($this->style) ?>">...</svg>
    <?php }
}
```

### Example 2: Layout with Sections → Slots

**Before (v3)** - Layout using sections
```php
<?php

class BaseTpl extends AbstractTemplate implements TemplateClassInterface
{
    public function display(
        Template $t,
        TemplateExtension $ext,
        string $title = 'App',
    ): void { ?>
<!DOCTYPE html>
<html>
<head><title><?= $t->e($title) ?></title></head>
<body>
    <?= $t->section('navbar', $t->fetch(new NavbarTpl())) ?>
    <?= $t->section('content') ?>
    <?= $t->section('footer') ?>
</body>
</html>
    <?php }

    public function __construct(public string $title = 'App') {}
}
```

**Before (v3)** - Page using layout + sections
```php
<?php

class PageTpl extends AbstractTemplate implements TemplateClassInterface
{
    public function display(
        Template $t,
        TemplateExtension $ext,
        \PiedWeb\Splates\Template\TemplateFetch $f,
        string $title,
        string $content,
    ): void { ?>
<?php $t->layout(new BaseTpl($title)) ?>

<?php $t->start('content') ?>
<div class="page">
  <h1><?= $t->e($title) ?></h1>
  <?= $content ?>
</div>
<?php $t->stop() ?>
    <?php }

    public function __construct(public string $title, public string $content) {}
}
```

**After (v4)** - Layout with Closure slots
```php
<?php

namespace App\Templates;

use Closure;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

class BaseTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $title = 'App',
        #[TemplateData]
        public ?Closure $navbar = null,
        #[TemplateData]
        public ?Closure $content = null,
        #[TemplateData]
        public ?Closure $footer = null,
    ) {}

    public function __invoke(): void
    { ?>
<!DOCTYPE html>
<html>
<head><title><?= $this->e($this->title) ?></title></head>
<body>
    <?php if ($this->navbar): ?>
        <?= ($this->navbar)() ?>
    <?php else: ?>
        <?= $this->render(new NavbarTpl()) ?>
    <?php endif ?>

    <?php if ($this->content): ?>
        <?= ($this->content)() ?>
    <?php endif ?>

    <?php if ($this->footer): ?>
        <?= ($this->footer)() ?>
    <?php endif ?>
</body>
</html>
    <?php }
}
```

**After (v4)** - Page using slots
```php
<?php

namespace App\Templates;

use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

class PageTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $title,
        #[TemplateData]
        public string $content,
    ) {}

    public function __invoke(): void
    {
        echo $this->render(new BaseTpl(
            title: $this->title,
            content: fn() => $this->renderContent(),
        ));
    }

    private function renderContent(): string
    {
        return $this->capture(function() { ?>
<div class="page">
  <h1><?= $this->e($this->title) ?></h1>
  <?= $this->content ?>
</div>
        <?php });
    }
}
```

### Example 3: Global Service (TemplateExtension)

**Before (v3)** - Service passed to every template
```php
<?php

// AbstractTemplate.php
abstract class AbstractTemplate implements TemplateClassInterface
{
    public TemplateExtension $ext;  // Set by engine
}

// SearchTpl.php
class SearchTpl extends AbstractTemplate implements TemplateClassInterface
{
    public function display(
        Template $t,
        TemplateExtension $ext,  // Passed to every template!
        \PiedWeb\Splates\Template\TemplateFetch $f,
        string $keyword,
        ?Search $search,
    ): void { ?>
<?php $t->layout(new BaseTpl(searchValue: $keyword)) ?>

<div class="px-3 mx-auto">
  <?= $t->fetch(new SearchNavbarTpl($keyword, $search)) ?>

  <?php $project = $ext->getUser()->getCurrentProject() ?>
  <a href="<?= $ext->url(ProjectController::class, ['projectId' => $project->getId()]) ?>">
    <?= $project->getName() ?>
  </a>

  <?= $f(new NoteDeleteButtonTpl($note)) ?>
</div>
    <?php }

    public function __construct(public string $keyword, public ?Search $search) {}
}
```

**After (v4)** - Service auto-injected via global
```php
<?php

namespace App\Templates;

use App\Service\TemplateExtension;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

// Optional: Create app-specific base class
abstract class AppTemplate extends TemplateAbstract
{
    #[TemplateData(global: true)]
    public TemplateExtension $ext;
}

// SearchTpl.php
class SearchTpl extends AppTemplate
{
    public function __construct(
        #[TemplateData]
        public string $keyword,
        #[TemplateData]
        public ?Search $search,
    ) {}

    public function __invoke(): void
    {
        echo $this->render(new BaseTpl(
            searchValue: $this->keyword,
            content: fn() => $this->renderContent(),
        ));
    }

    private function renderContent(): string
    {
        return $this->capture(function() { ?>
<div class="px-3 mx-auto">
  <?= $this->render(new SearchNavbarTpl($this->keyword, $this->search)) ?>

  <?php $project = $this->ext->getUser()->getCurrentProject() ?>
  <a href="<?= $this->ext->url(ProjectController::class, ['projectId' => $project->getId()]) ?>">
    <?= $this->e($project->getName()) ?>
  </a>

  <?= $this->render(new NoteDeleteButtonTpl($note)) ?>
</div>
        <?php });
    }
}
```

**Engine setup:**
```php
$engine = new Engine();
$engine->addGlobal('ext', $templateExtension);

echo $engine->render(new SearchTpl(
    keyword: 'my search',
    search: $searchEntity,
));
```

### Example 4: Complex Template with Multiple Components

**After (v4)**
```php
<?php

namespace App\Templates;

use App\Entity\Search;
use PiedWeb\Splates\Template\Attribute\TemplateData;

class SearchResultsTpl extends AppTemplate
{
    public function __construct(
        #[TemplateData]
        public Search $search,
    ) {}

    public function __invoke(): void
    {
        echo $this->render(new BaseTpl(
            title: 'Search: ' . $this->search->getKeyword(),
            content: fn() => $this->renderContent(),
        ));
    }

    private function renderContent(): string
    {
        return $this->capture(function() { ?>
<div class="flex gap-4">
    <!-- Sidebar -->
    <aside class="w-64">
        <?= $this->render(new SearchFiltersTpl($this->search)) ?>
    </aside>

    <!-- Main content -->
    <main class="flex-1">
        <?= $this->render(new SearchNavbarTpl($this->search)) ?>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <?= $this->render(new VolumeChartTpl($this->search)) ?>
            <?= $this->render(new SerpFeaturesTpl($this->search)) ?>
        </div>

        <?= $this->render(new ResultsTableTpl($this->search)) ?>
    </main>
</div>
        <?php });
    }
}
```

---

## Credits

- [Robin D. / Pied Web](https://piedweb.com) - Current Maintainer

Original **league/plates** contributors:

- [RJ Garcia](https://github.com/ragboyjr) - Original Plates Maintainer
- [Jonathan Reinink](https://github.com/reinink) - Original Plates Author
- [All Contributors](https://github.com/piedweb/splates/contributors)

## License

MIT License. See [LICENSE](LICENSE) for details.
