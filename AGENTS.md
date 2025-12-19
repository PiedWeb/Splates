## Project Overview

**Splates** is a PHP 8.3+ native template engine, forked from `league/plates`. It provides PHPStan support and IDE code completion for PHP templates without requiring compiled template languages.

- **Repository**: https://github.com/piedweb/splates
- **Namespace**: `PiedWeb\Splates`
- **License**: MIT

## Tech Stack

- PHP 8.4+
- PHPStan (max level)
- PHPUnit
- Rector for code modernization
- PHP-CS-Fixer for formatting

## Directory Structure

```
src/                          # Main source code
├── Engine.php               # Core API entry point
├── RectorizeTemplate.php    # Rector rule for auto-constructor generation
├── Exception/               # Custom exceptions
├── Extension/               # Extension system (Asset, URI)
└── Template/                # Template rendering core
    ├── Template.php         # String-based templates
    ├── TemplateClass.php    # Class-based typed templates
    └── ...                  # Data, Folders, Functions, Theme, etc.

tests/                        # PHPUnit test suite
example/                      # Traditional template examples
exampleTemplateClass/         # Class-based template examples
doc/                          # Hugo documentation
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

- No union types in `display()` method signatures (breaks autowiring)
- No dynamic property access without `@property` PHPDoc
- No suppressed PHPStan errors (`@phpstan-ignore`)
- No `@var` type overrides - fix the actual types instead

## Key Components

### Engine (`src/Engine.php`)

Main entry point. Creates and renders templates.

```php
$engine = new Engine('/templates');
$engine->render('profile', ['name' => 'John']);
```

### Template Modes

1. **String-based** (`Template.php`): Traditional `<?php ?>` templates
2. **Class-based** (`TemplateClass.php`): Typed templates with autowired parameters

### Class-based Template Pattern

```php
class Profile implements TemplateClassInterface {
    public function display(string $name, int $age): void {
        // Template logic with full IDE support
    }
}
```

### Extension System

Extensions implement `ExtensionInterface` and register functions via `Engine::loadExtension()`.

## Development Commands

```bash
composer test      # Run PHPUnit tests
composer stan      # Run PHPStan analysis
composer format    # Fix code style
composer rector    # Apply code modernization
```

## Testing Guidelines

- Use `vfsStream` for filesystem tests (see existing tests)
- Test both string-based and class-based template modes
- Cover edge cases: missing templates, invalid data types, theme fallbacks
- Rector rules have fixture-based tests in `tests/RectorizeTemplate/Fixture/`

## Common Tasks

### Adding a New Extension

1. Create class in `src/Extension/` implementing `ExtensionInterface`
2. Implement `register(Engine $engine): void`
3. Add tests in `tests/Extension/`
4. Register via `$engine->loadExtension(new MyExtension())`

### Modifying Template Rendering

- String templates: Edit `src/Template/Template.php`
- Class templates: Edit `src/Template/TemplateClass.php`
- Path resolution: See `src/Template/ResolveTemplatePath/`

### Adding Engine Features

1. Add method to `src/Engine.php`
2. Return `$this` for fluent API consistency
3. Add corresponding test in `tests/EngineTest.php`

## Important Files

| File                             | Purpose                                             |
| -------------------------------- | --------------------------------------------------- |
| `src/Engine.php`                 | Main API, start here for understanding the system   |
| `src/Template/TemplateClass.php` | Class-based template magic (reflection, autowiring) |
| `src/RectorizeTemplate.php`      | Auto-generates constructors for template classes    |
| `phpstan.neon.dist`              | Static analysis configuration                       |
| `rector.php`                     | Code modernization rules                            |

## Architecture Notes

- Templates are resolved via `ResolveTemplatePath` strategy pattern
- Data flows: Engine → Data → Template (merged at render time)
- Functions are wrapped in `Func` objects with callback management
- Theme support uses hierarchical fallback resolution
