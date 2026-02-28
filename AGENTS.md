## Project Overview

**Splates** is a PHP 8.4+ native template engine, forked from `league/plates`. It provides PHPStan support and IDE code completion for PHP templates without requiring compiled template languages.

- **Repository**: https://github.com/piedweb/splates
- **Namespace**: `PiedWeb\Splates`
- **License**: MIT

## Tech Stack

- PHP 8.4+
- PHPStan (max level)
- PHPUnit
- PHP-CS-Fixer for formatting

## Directory Structure

```
src/                          # Main source code
├── Engine.php               # Core API entry point
└── Template/                # Template rendering core
    ├── Template.php         # File-based templates
    ├── TemplateClass.php    # Class-based typed templates
    ├── TemplateAbstract.php # Base class for template classes
    ├── TemplateFile.php     # File-based template rendering
    ├── InjectResolver.php   # Reflection-based #[Inject] resolution
    ├── Attribute/           # #[TemplateData], #[Inject]
    └── Value/               # Text, Html, Attr, Js, Slot

tests/                        # PHPUnit test suite
exampleTemplateClass/         # Class-based template examples
```

## Code Standards

### Strict Requirements

1. **PSR-12 compliant** - Run `composer format` before committing
2. **PHPStan max level** - No ignored errors, run `composer stan`
3. **Type declarations required** - All parameters, returns, and properties must be typed
4. **Tests required** - Every feature/fix needs tests, run `composer test`

### Coding Patterns

- **Constructor property promotion**: Use `public function __construct(public readonly Type $prop)`
- **Readonly properties**: Prefer `readonly` for immutable state

### What NOT to Do

- No dynamic property access without `@property` PHPDoc
- No suppressed PHPStan errors (`@phpstan-ignore`)
- No `@var` type overrides - fix the actual types instead

## Key Components

### Engine (`src/Engine.php`)

Main entry point. Creates and renders templates.

```php
$engine = new Engine();
$engine->addGlobal('ext', $templateExtension);
echo $engine->render(new ProfileTpl(name: 'John'));
```

### Template Modes

1. **Class-based** (`TemplateClass.php`): Typed templates extending `TemplateAbstract` with `#[TemplateData]` constructor params
2. **File-based** (`TemplateFile.php`): Traditional `.php` templates via `$engine->render('template.php', ['key' => 'val'])`

### Class-based Template Pattern

```php
class Profile extends TemplateAbstract {
    public function __construct(
        #[TemplateData] public string $name,
    ) {}

    public function __invoke(): void { ?>
<h1><?= $this->e($this->name) ?></h1>
    <?php }
}
```

### Injection System

- `#[TemplateData]` on constructor params for IDE autocompletion
- `#[Inject]` on properties for engine globals injection
- `InjectResolver` handles reflection-based resolution with caching

## Development Commands

```bash
composer test      # Run PHPUnit tests
composer stan      # Run PHPStan analysis
composer format    # Fix code style
```

## Testing Guidelines

- Test both file-based and class-based template modes
- Cover edge cases: missing templates, invalid data types
- Value objects have dedicated tests in `tests/Template/ValueObjectsTest.php`

## Important Files

| File                             | Purpose                                             |
| -------------------------------- | --------------------------------------------------- |
| `src/Engine.php`                 | Main API, start here for understanding the system   |
| `src/Template/TemplateClass.php` | Class-based template rendering (reflection, autowiring) |
| `src/Template/TemplateAbstract.php` | Base class with render(), capture(), slot(), e() helpers |
| `src/Template/InjectResolver.php` | Reflection-based injection with caching |
| `phpstan.neon.dist`              | Static analysis configuration                       |

## Architecture Notes

- Class templates use `#[TemplateData]` constructor params and `#[Inject]` properties
- Layouts use the slots pattern: pass `Closure` properties instead of sections
- Value objects (`Text`, `Html`, `Attr`, `Js`, `Slot`) provide context-aware escaping
- `InjectResolver` caches reflection data for performance
