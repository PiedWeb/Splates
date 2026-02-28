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
composer rector  # Run Rector
```

---

## Migrating from v3 to v4

v4 is a full rewrite. The extension system, folder/theme system, function registry, and string-based template names are all removed in favor of PHP classes, attributes, and PSR-4 autoloading.

### Breaking Changes Summary

#### Engine API

| v3 | v4 |
|---|---|
| `new Engine('/templates', 'php')` | `new Engine(templateDir: '/templates')` |
| `$engine->render('profile', ['name' => 'John'])` | `$engine->render(new ProfileTpl(name: 'John'))` |
| `$engine->addData(['key' => 'val'])` | `$engine->addGlobal('key', $val)` |
| `$engine->registerFunction('upper', ...)` | Removed - use plain PHP |
| `$engine->loadExtension(new Asset(...))` | Removed - use `#[Inject]` |
| `$engine->addFolder('emails', '/path')` | Removed - use PSR-4 namespaces |
| `$engine->setFileExtension('tpl')` | Removed - always `.php` |
| `Engine::fromTheme(...)` | Removed |
| `$engine->path('template')` | Removed |
| `$engine->exists('template')` | Removed |
| `$engine->make('template')` | `$engine->make(new Tpl())` (accepts `TemplateClassInterface` only) |

#### Template API

| v3 | v4 |
|---|---|
| `implements TemplateClassInterface` | `extends TemplateAbstract` (recommended) or `implements TemplateClassInterface` |
| `display(Template $t, TemplateFetch $f, TemplateEscape $e, ...)` | `__invoke(): void` |
| `$e($value)` or `$t->e($value)` | `$this->e($value)` |
| `$f(new Tpl())` or `$t->fetch(new Tpl())` | `$this->render(new Tpl())` |
| `$t->layout(new BaseTpl())` | `echo $this->render(new BaseTpl(content: ...))` |
| `$t->start('content')` ... `$t->stop()` | Slots: `content: $this->slot(function() { ... })` |
| `$t->section('content')` | `($this->content)()` in layout |
| `$t->section('nav', $default)` | `$this->nav ? ($this->nav)() : $default` |
| Constructor props (no attribute) | Constructor props with `#[TemplateData]` |
| `public TemplateExtension $ext` (autowired via data) | `#[Inject] public TemplateExtension $ext` |

#### Removed Classes

- `PiedWeb\Splates\Extension\ExtensionInterface`, `Asset`, `URI`
- `PiedWeb\Splates\Template\Data`, `Directory`, `FileExtension`, `Folder`, `Folders`
- `PiedWeb\Splates\Template\Func`, `Functions`, `Name`, `Theme`
- `PiedWeb\Splates\Template\ResolveTemplatePath\*`
- `PiedWeb\Splates\Template\TemplateClassAbstract`, `DoNotAddItInConstructorInterface`
- `PiedWeb\Splates\Exception\TemplateNotFound`
- `PiedWeb\Splates\RectorizeTemplate` (replaced by `Rector\MigrateTemplateToV4Rector`)

#### New Classes

- `PiedWeb\Splates\Template\TemplateAbstract` - base class with `render()`, `capture()`, `slot()`, `e()` helpers
- `PiedWeb\Splates\Template\InjectResolver` - reflection-based injection with caching
- `PiedWeb\Splates\Template\Attribute\TemplateData` - marks constructor parameters for IDE autocompletion
- `PiedWeb\Splates\Template\Attribute\Inject` - injects `TemplateFetch`/`TemplateEscape` and Engine globals into properties
- `PiedWeb\Splates\Template\Value\Text`, `Html`, `Attr`, `Js`, `Slot` - context-aware escaping value objects
- `PiedWeb\Splates\Template\TemplateFile` - file-based templates (new)
- `PiedWeb\Splates\Template\InjectBinding` - internal binding mechanism

### Step-by-Step Migration

#### Step 1: Update Engine Setup

**Before (v3):**

```php
$engine = new Engine('/path/to/templates', 'php');
$engine->addFolder('emails', '/path/to/emails');
$engine->loadExtension(new Asset('/assets'));
$engine->addData(['siteName' => 'My App']);

// Custom engine subclass
class MyEngine extends Engine {
    public function make(string|TemplateClassInterface $name, array $data = []): Template {
        $template = parent::make($name, $data);
        $template->data(['ext' => $this->ext]);
        return $template;
    }
}
```

**After (v4):**

```php
$engine = new Engine();
$engine->addGlobal('ext', $templateExtension);  // Replaces extension system AND data injection
$engine->addGlobal('siteName', 'My App');

// No subclass needed - globals are auto-injected via #[Inject]
```

#### Step 2: Run Rector (Partial Automation)

A Rector rule handles some of the mechanical changes:

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

```bash
vendor/bin/rector process
```

**What Rector does:**

1. Changes `implements TemplateClassInterface` to `extends TemplateAbstract`
2. Adds `#[TemplateData]` to promoted constructor properties
3. Renames `display()` to `__invoke()`
4. Removes `Template $t`, `TemplateFetch $f`, `TemplateEscape $e` parameters from `__invoke()`
5. Removes data parameters that duplicate constructor properties
6. Transforms `$e($x)` to `$this->e($x)`
7. Transforms `$f(new Tpl())` to `$this->render(new Tpl())`
8. Transforms `$t->e($x)` / `$t->escape($x)` to `$this->e($x)`
9. Transforms `$t->layout(new Tpl())` to `$this->render(new Tpl())` (partial - see limitations)

**What Rector does NOT do (manual fixes required):**

1. **Does not convert `$variable` to `$this->variable`** - You must update all references to display parameters (e.g. `$name` becomes `$this->name`)
2. **Does not clean up old `use` statements** - Remove unused imports manually
3. **Does not handle `$t->fetch(new Tpl())`** - Only handles `$f(...)`, not `$t->fetch(...)`
4. **Does not convert sections to slots** - `$t->start('content')` / `$t->stop()` / `$t->section('content')` need full manual rewrite (see Step 4)
5. **Does not handle `$ext->method()` calls** - Extension references stay as-is but need `$this->ext->` prefix
6. **Does not add `#[Inject]`** - Global properties must be annotated manually

#### Step 3: Fix Variable References and Imports

After Rector runs, fix every template file:

```php
// Rector output (broken):
public function __invoke(): void
{
    echo $this->e($name);        // $name is no longer a parameter!
    echo $this->render(new SidebarTpl());
}

// Fixed:
public function __invoke(): void
{
    echo $this->e($this->name);  // Access as property
    echo $this->render(new SidebarTpl());
}
```

Clean up imports in every file:

```php
// Remove these
use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateFetch;
use PiedWeb\Splates\Template\TemplateEscape;
use PiedWeb\Splates\Template\TemplateClassInterface;
use App\Plates\AbstractTemplate;  // Your old base class

// Add these (if not already added by Rector)
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;
```

#### Step 4: Convert Layouts and Sections to Slots

This is the biggest manual change. The v3 section system (`$t->layout()`, `$t->start()/$t->stop()`, `$t->section()`) is replaced by passing `Closure` properties to layout templates.

**v3 layout:**

```php
class BaseTpl implements TemplateClassInterface
{
    public function display(Template $t, string $title = 'App'): void { ?>
<!DOCTYPE html>
<html>
<head><title><?= $t->e($title) ?></title></head>
<body>
    <?= $t->section('navbar', $t->fetch(new NavbarTpl())) ?>
    <?= $t->section('content') ?>
</body>
</html>
    <?php }
    public function __construct(public string $title = 'App') {}
}
```

**v4 layout (slots are explicit constructor parameters):**

```php
class BaseTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData] public string $title = 'App',
        #[TemplateData] public ?Closure $navbar = null,
        #[TemplateData] public ?Closure $content = null,
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
</body>
</html>
    <?php }
}
```

**v3 page (using layout + sections):**

```php
class PageTpl implements TemplateClassInterface
{
    public function display(Template $t, TemplateFetch $f, string $title, string $body): void { ?>
<?php $t->layout(new BaseTpl($title)) ?>

<?php $t->start('content') ?>
<div class="page">
    <h1><?= $t->e($title) ?></h1>
    <?= $body ?>
</div>
<?php $t->stop() ?>
    <?php }
    public function __construct(public string $title, public string $body) {}
}
```

**v4 page (passing slots to layout):**

```php
class PageTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData] public string $title,
        #[TemplateData] public string $body,
    ) {}

    public function __invoke(): void
    {
        echo $this->render(new BaseTpl(
            title: $this->title,
            content: $this->slot(function() { ?>

<div class="page">
    <h1><?= $this->e($this->title) ?></h1>
    <?= $this->body ?>
</div>

            <?php }),
        ));
    }
}
```

Key patterns for section conversion:

| v3 Section Pattern | v4 Slot Pattern |
|---|---|
| `$t->section('name')` in layout | `($this->name)()` or `<?= $this->name ?>` (with `Slot` type) |
| `$t->section('name', $default)` in layout | `$this->name ? ($this->name)() : $default` |
| `$t->start('name') ... $t->stop()` in page | `name: $this->slot(function() { ... })` |
| `$t->layout(new BaseTpl(...))` in page | `echo $this->render(new BaseTpl(..., content: $this->slot(...)))` |

#### Step 5: Convert Global Services (Extensions)

**v3 pattern** - custom AbstractTemplate + engine subclass:

```php
// AbstractTemplate.php
abstract class AbstractTemplate implements TemplateClassInterface
{
    public TemplateExtension $ext;  // Injected via $template->data(['ext' => ...])
}

// MyEngine.php
class MyEngine extends Engine
{
    public function make(string|TemplateClassInterface $name, array $data = []): Template
    {
        $template = parent::make($name, $data);
        $template->data(['ext' => $this->ext]);
        return $template;
    }
}
```

**v4 pattern** - `#[Inject]` + `Engine::addGlobal()`:

```php
// AppTemplate.php (optional base class)
abstract class AppTemplate extends TemplateAbstract
{
    #[Inject]
    public TemplateExtension $ext;
}

// Engine setup - no subclass needed
$engine = new Engine();
$engine->addGlobal('ext', $templateExtension);
```

The global name (`'ext'`) must match the property name (`$ext`). The engine auto-injects it into any template that declares an `#[Inject]` property with that name.

#### Step 6: Delete Old Infrastructure

After migrating all templates, remove:

- Your `AbstractTemplate` base class
- Your `Engine` subclass (if any)
- Any `ExtensionInterface` implementations (move logic to service classes)
- Template folder/theme configuration
- The `RectorizeTemplate` rector rule (replaced by `MigrateTemplateToV4Rector`)

#### Step 7: Verify

```bash
composer stan     # PHPStan will catch most type errors
composer test     # Run your tests
```

### Migrating from league/plates

If you're coming from the original `league/plates` package (not Splates v3), the migration is similar but you'll also need to:

1. **Change namespace**: `League\Plates\` becomes `PiedWeb\Splates\`
2. **Convert string templates to classes**: `$engine->render('profile', ['name' => 'John'])` becomes `$engine->render(new ProfileTpl(name: 'John'))`
3. **Remove all `$this->` template helper calls** from `.php` template files and convert them to class-based templates extending `TemplateAbstract`
4. Follow Steps 1-7 above for the rest

File-based templates are still supported for simple cases via `$engine->render('path/to/template.php', ['name' => 'John'])`, but class-based templates are recommended for type safety.

---

## Migration Examples (Real-World)

### Example 1: Simple Template

**Before (v3)**

```php
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

### Example 2: Template with Child Rendering

**Before (v3)**

```php
class SearchTpl extends AbstractTemplate implements TemplateClassInterface
{
    public function display(
        Template $t,
        TemplateExtension $ext,
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

**After (v4)**

```php
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
            content: $this->slot(function() { ?>

<div class="px-3 mx-auto">
  <?= $this->render(new SearchNavbarTpl($this->keyword, $this->search)) ?>

  <?php $project = $this->ext->getUser()->getCurrentProject() ?>
  <a href="<?= $this->ext->url(ProjectController::class, ['projectId' => $project->getId()]) ?>">
    <?= $this->e($project->getName()) ?>
  </a>

  <?= $this->render(new NoteDeleteButtonTpl($note)) ?>
</div>

            <?php }),
        ));
    }
}
```

### Example 3: Layout with Multiple Slots

**After (v4)**

```php
class BaseTpl extends AppTemplate
{
    public function __construct(
        #[TemplateData] public string $title = 'App',
        #[TemplateData] public ?Closure $navbar = null,
        #[TemplateData] public ?Closure $content = null,
        #[TemplateData] public ?Closure $scripts = null,
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

    <?php if ($this->scripts): ?>
        <?= ($this->scripts)() ?>
    <?php endif ?>
</body>
</html>
    <?php }
}
```

### Example 4: Engine Setup

**Before (v3):**

```php
$engine = new PlatesTemplateEngine($templateExtension, $stopwatch);
// PlatesTemplateEngine extends Engine, injects $ext via data

echo $engine->render(new SearchTpl(keyword: 'test', search: $search));
```

**After (v4):**

```php
$engine = new Engine();
$engine->addGlobal('ext', $templateExtension);

echo $engine->render(new SearchTpl(keyword: 'test', search: $search));
```

---

## TODO

- [ ] Permit simple template file next to the class file (but discouraged the usage in docs)

```php
// The goal is to manage very simple template (like a button)
// before to implement : resolve how to inject template tools like escape
$this->render('profile.html.php', ['name' => 'John']);
// ...
// profile.html.php
<?php /** @var string $name */ ?>
<h1><?= $e($name) ?></h1>
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
