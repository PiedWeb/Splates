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
- **Global services** - Inject dependencies via `Engine::addGlobal()` with `#[Inject]`

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

| Method                        | Description                          |
| ----------------------------- | ------------------------------------ |
| `$this->e($value)`            | Escape value for HTML output         |
| `$this->render(new Tpl())`    | Render a child template              |
| `$this->capture(fn() => ...)` | Capture output as string             |
| `$this->slot(fn() => ...)`    | Create a lazy slot (syntactic sugar) |

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
use PiedWeb\Splates\Template\Attribute\Inject;

class MyTemplate extends TemplateAbstract
{
    // Auto-injected from globals
    #[Inject]
    public TemplateExtension $ext;

    #[Inject]
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
    #[Inject]
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

| Class  | Use Case          | Example                                |
| ------ | ----------------- | -------------------------------------- |
| `Text` | HTML text content | `<p><?= $text ?></p>`                  |
| `Html` | Pre-escaped HTML  | `<?= $html ?>`                         |
| `Attr` | HTML attributes   | `<div class="<?= $attr ?>">`           |
| `Js`   | JavaScript values | `<script>var x = <?= $js ?>;</script>` |

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
$engine->getInjectResolver()->warmCache([
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
```

---

## Migrating from league/plates

Splates is a fork of [league/plates](https://github.com/thephpleague/plates), redesigned around PHP classes, attributes, and PSR-4 autoloading instead of string-based template names.

### Key Differences

| league/plates | Splates |
|---|---|
| `new Engine('/templates', 'php')` | `new Engine(templateDir: '/templates')` |
| `$engine->render('profile', ['name' => 'John'])` | `$engine->render(new ProfileTpl(name: 'John'))` |
| `$engine->addData(['key' => 'val'])` | `$engine->addGlobal('key', $val)` |
| `$engine->registerFunction('upper', ...)` | Removed - use plain PHP |
| `$engine->loadExtension(new Asset(...))` | Removed - use `#[Inject]` |
| `$engine->addFolder('emails', '/path')` | Removed - use PSR-4 namespaces |
| `$this->e($value)` in templates | `$this->e($value)` (same) |
| `$this->fetch('partial')` | `$this->render(new PartialTpl())` |
| `$this->layout('layout')` | `echo $this->render(new LayoutTpl(content: ...))` |
| `$this->section('content')` | `($this->content)()` in layout |
| `$this->start('content')` ... `$this->stop()` | `content: $this->slot(function() { ... })` |

### Migration Steps

#### 1. Convert string templates to classes

**Before (league/plates):**

```php
// templates/profile.php
<h1><?= $this->e($name) ?></h1>
<p><?= $this->e($bio) ?></p>
```

**After (Splates):**

```php
class ProfileTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData] public string $name,
        #[TemplateData] public string $bio,
    ) {}

    public function __invoke(): void
    { ?>
<h1><?= $this->e($this->name) ?></h1>
<p><?= $this->e($this->bio) ?></p>
    <?php }
}
```

For simple templates that don't need type safety, file-based templates are still supported:

```php
$engine->render('path/to/template.php', ['name' => 'John']);
```

#### 2. Convert layouts to slots

**Before (league/plates):**

```php
// templates/layout.php
<!DOCTYPE html>
<html>
<head><title><?= $this->e($title) ?></title></head>
<body><?= $this->section('content') ?></body>
</html>

// templates/page.php
<?php $this->layout('layout', ['title' => $title]) ?>
<?php $this->start('content') ?>
<h1><?= $this->e($title) ?></h1>
<?php $this->stop() ?>
```

**After (Splates):**

```php
class LayoutTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData] public string $title = 'App',
        #[TemplateData] public ?Closure $content = null,
    ) {}

    public function __invoke(): void
    { ?>
<!DOCTYPE html>
<html>
<head><title><?= $this->e($this->title) ?></title></head>
<body>
    <?php if ($this->content): ?>
        <?= ($this->content)() ?>
    <?php endif ?>
</body>
</html>
    <?php }
}

class PageTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData] public string $title,
    ) {}

    public function __invoke(): void
    {
        echo $this->render(new LayoutTpl(
            title: $this->title,
            content: $this->slot(function() { ?>
<h1><?= $this->e($this->title) ?></h1>
            <?php }),
        ));
    }
}
```

#### 3. Replace extensions with globals

**Before (league/plates):**

```php
$engine->loadExtension(new Asset('/assets'));
// In template: $this->asset('logo.png')
```

**After (Splates):**

```php
$engine = new Engine();
$engine->addGlobal('assetPath', '/assets');

// In template class:
class MyTpl extends TemplateAbstract
{
    #[Inject] public string $assetPath;

    public function __invoke(): void
    {
        echo $this->assetPath.'/logo.png';
    }
}
```

#### 4. Verify

```bash
composer stan     # PHPStan will catch most type errors
composer test     # Run your tests
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
