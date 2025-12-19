<?php

namespace PiedWeb\Splates\Template;

use Generator;
use RuntimeException;

final class Theme
{
    private ?Theme $next = null;

    private function __construct(private readonly string $dir, private readonly string $name)
    {
    }

    /** @param Theme[] $themes */
    public static function hierarchy(array $themes): Theme
    {
        self::assertThemesForHierarchyAreNotEmpty($themes);
        self::assertAllThemesInHierarchyAreLeafThemes($themes);

        /** @var Theme $theme */
        $theme = array_reduce(array_slice($themes, 1), function (Theme $parent, Theme $child): Theme {
            $child->next = $parent;

            return $child;
        }, $themes[0]);
        self::assertHierarchyContainsUniqueThemeNames($theme);

        return $theme;
    }

    public static function new(string $dir, string $name = 'Default'): self
    {
        return new self($dir, $name);
    }

    public function dir(): string
    {
        return $this->dir;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Generator<Theme>
     */
    public function listThemeHierarchy(): Generator
    {
        yield $this;
        if ($this->next instanceof \PiedWeb\Splates\Template\Theme) {
            yield from $this->next->listThemeHierarchy();
        }
    }

    /** @param Theme[] $themes */
    private static function assertThemesForHierarchyAreNotEmpty(array $themes): void
    {
        if ($themes === []) {
            throw new RuntimeException('Empty theme hierarchies are not allowed.');
        }
    }

    /** @param Theme[] $themes */
    private static function assertAllThemesInHierarchyAreLeafThemes(array $themes): void
    {
        foreach ($themes as $theme) {
            if ($theme->next) {
                throw new RuntimeException('Nested theme hierarchies are not allowed, make sure to use Theme::new when creating themes in your hierarchy. Theme ' . $theme->name . ' is already in a hierarchy.');
            }
        }
    }

    private static function assertHierarchyContainsUniqueThemeNames(Theme $theme): void
    {
        $names = [];
        foreach ($theme->listThemeHierarchy() as $theme) {
            $names[] = $theme->name;
        }

        if (count(array_unique($names)) !== count($names)) {
            throw new RuntimeException('Duplicate theme names in hierarchies are not allowed. Received theme names: [' . implode(', ', $names) . '].');
        }
    }
}
