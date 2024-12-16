<?php

namespace PiedWeb\Splates\Template\ResolveTemplatePath;

use PiedWeb\Splates\Exception\TemplateNotFound;
use PiedWeb\Splates\Template\Name;
use PiedWeb\Splates\Template\ResolveTemplatePath;
use PiedWeb\Splates\Template\Theme;

final readonly class ThemeResolveTemplatePath implements ResolveTemplatePath
{
    public function __construct(private Theme $theme)
    {
    }

    public function __invoke(Name $name): string
    {
        $searchedPaths = [];
        foreach ($this->theme->listThemeHierarchy() as $theme) {
            $path = $theme->dir() . '/' .  $name->getName() . '.' . $name->engine->getFileExtension();
            if (is_file($path)) {
                return $path;
            }

            $searchedPaths[] = [$theme->name(), $path];
        }

        throw new TemplateNotFound(
            $name->getName(),
            array_map(fn (array $tup): string => $tup[1], $searchedPaths),
            sprintf(
                'The template "%s" was not found in the following themes: %s',
                $name->getName(),
                implode(', ', array_map(fn (array $tup): string => implode(':', $tup), $searchedPaths))
            )
        );
    }
}
