<?php

namespace PiedWeb\Splates\Template;

use LogicException;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Exception\TemplateNotFound;
use Stringable;
use Throwable;

/**
 * Container which holds template data and provides access to template functions.
 */
class Template implements Stringable
{
    const SECTION_MODE_REWRITE = 1;

    const SECTION_MODE_PREPEND = 2;

    const SECTION_MODE_APPEND = 3;

    /**
     * Set section content mode: rewrite/append/prepend
     * @var int
     */
    protected $sectionMode = self::SECTION_MODE_REWRITE;

    /**
     * @var array<string, int> where string is section name and int sectionMode
     */
    protected $sectionsMode = [];

    private readonly Name $name;

    /** @var array<mixed> */
    protected $data = [];

    /**
     * An array of section content.
     * @var array<string, string>
     */
    protected $sections = [];

    protected ?string $sectionName = null;

    /**
     * @deprecated stayed for backward compatibility, use $sectionMode instead
     */
    protected bool $appendSection;

    protected string|TemplateClassInterface|null $layoutName = null;

    /** @var array<mixed> */
    protected array $layoutData;

    public function __construct(protected Engine $engine, string $name)
    {
        $this->name = new Name($this->engine, $name);

        $this->data($this->engine->getData($name));
    }

    /**
     * @param  array<mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->engine->getFunction($name)->call($this, $arguments);
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @param  ?array<mixed> $data
     * @return array<mixed>
     */
    public function data(?array $data = null): array
    {
        if (is_null($data)) {
            return $this->data;
        }

        return $this->data = array_merge($this->data, $data);
    }

    public function exists(): bool
    {
        try {
            ($this->engine->getResolveTemplatePath())($this->name);

            return true;
        } catch (TemplateNotFound) {
            return false;
        }
    }

    /**
     * Get the template path.
     * @return string
     */
    public function path()
    {
        try {
            return ($this->engine->getResolveTemplatePath())($this->name);
        } catch (TemplateNotFound $templateNotFound) {
            return $templateNotFound->paths[0];
        }
    }

    /**
     * @param  array<mixed>  $data
     */
    public function render(array $data = []): string
    {
        $this->data($data);

        try {
            $level = ob_get_level();
            ob_start();
            $this->display();
            $content = ob_get_clean();
            assert(is_string($content));

            if ($this->layoutName !== null) {
                $layout = $this->engine->make($this->layoutName);
                $layout->sections = array_merge($this->sections, ['content' => $content]);
                $layout->sectionsMode = array_merge($layout->sectionsMode, $this->sectionsMode);
                $content = $layout->render($this->layoutData);
            }

            return $content;
        } catch (Throwable $throwable) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $throwable;
        }
    }

    protected function display(): void
    {
        $path = ($this->engine->getResolveTemplatePath())($this->name);

        (function (): void { // @phpstan-ignore-line permit to avoid to leak variable
            extract($this->data);
            include func_get_arg(0);
        })($path);
    }

    /**
     * @param  string|TemplateClassInterface|null $name
     * @param  array<mixed>  $data
     */
    public function layout(string|TemplateClassInterface|null $name, array $data = []): void
    {
        $this->layoutName = $name;
        $this->layoutData = array_merge($this->data, $data);
    }

    private function mustStopRenderingSection(): bool
    {
        return isset($this->sections[$this->sectionName]) && $this->sectionMode == self::SECTION_MODE_REWRITE;
    }

    /**
     * Start a new section block.
     * @param  string  $name
     * @return bool
     */
    public function start($name)
    {
        if ($name === 'content') {
            throw new LogicException(
                'The section name "content" is reserved.'
            );
        }

        if ($this->sectionName) {
            throw new LogicException('You cannot nest sections within other sections.');
        }

        $this->sectionName = $name;

        if ($this->mustStopRenderingSection()) {
            return false;
        }

        return ob_start();
    }

    /**
     * Start a new section block in APPEND mode.
     * @param  string $name
     * @return bool
     */
    public function push($name): bool
    {
        $this->appendSection = true; /* for backward compatibility */
        $this->sectionMode = $this->sectionsMode[$name] = self::SECTION_MODE_APPEND;
        $this->start($name);

        return true;
    }

    /**
     * Start a new section block in PREPEND mode.
     * @param  string $name
     * @return bool
     */
    public function unshift($name): bool
    {
        $this->appendSection = false; /* for backward compatibility */
        $this->sectionMode = $this->sectionsMode[$name] = self::SECTION_MODE_PREPEND;
        $this->start($name);

        return true;
    }

    public function stop(): void
    {
        if (is_null($this->sectionName)) {
            throw new LogicException(
                'You must start a section before you can stop it.'
            );
        }


        if (! $this->mustStopRenderingSection()) {

            if (! isset($this->sections[$this->sectionName])) {
                $this->sections[$this->sectionName] = '';
            }

            switch ($this->sectionMode) {

                case self::SECTION_MODE_REWRITE:
                    $output = ob_get_clean();
                    assert(is_string($output));
                    $this->sections[$this->sectionName] = $output;

                    break;

                case self::SECTION_MODE_APPEND:
                    $output = ob_get_clean();
                    assert(is_string($output));
                    $this->sections[$this->sectionName] .= $output;

                    break;

                case self::SECTION_MODE_PREPEND:
                    $output = ob_get_clean();
                    assert(is_string($output));
                    $this->sections[$this->sectionName] = $output.$this->sections[$this->sectionName];

                    break;

            }
        }

        $this->sectionName = null;
        $this->sectionMode = self::SECTION_MODE_REWRITE;
        $this->appendSection = false; /* for backward compatibility */
    }

    public function end(): void
    {
        $this->stop();
    }

    public function section(string $name, ?string $default = null): ?string
    {
        if (! isset($this->sections[$name])) {
            return $default;
        }

        return $this->sections[$name];
    }

    private function getSectionMode(string $name): int
    {
        return $this->sectionsMode[$name] ?? self::SECTION_MODE_REWRITE;
    }

    /**
     * Echo the content for a section block else return bool.
     *
     * Usage :
     * <?php if ($this->startSection('exampleSection')) { ?>
     *  Default Content
     * <?php } ?>
     * Alternative To : <?= $this->section('exampleSection', 'Default Content') ?>
     * + Feature : works with push and unshift
     * + could be used with defaultValue inline too : <?(=|php) $this->startSection('exampleSection', 'Default Content') ?>
     *
     */
    public function startSection(string $name, ?string $default = null): string|bool
    {
        if (isset($this->sections[$name])) {
            if ($this->getSectionMode($name) === self::SECTION_MODE_REWRITE) {
                echo $this->sections[$name];

                return  $default !== null ? '' : false;
            }

            if ($this->getSectionMode($name) === self::SECTION_MODE_PREPEND) {
                echo $this->sections[$name];

                if ($default !== null) {
                    echo $default;
                }

                return  $default !== null ? '' : true;
            }

            if ($this->getSectionMode($name) === self::SECTION_MODE_APPEND) {
                $this->sectionMode = self::SECTION_MODE_PREPEND;
                $this->start($name);


                if ($default !== null) {
                    echo $default;
                    $this->stopSection();
                }

                return $default !== null ? '' : true;
            }
        }

        return  $default !== null ? '' : true;
    }

    public function stopSection(): void
    {
        if ($this->sectionName === null) {
            return;
        }

        $name = $this->sectionName;
        $this->stop();
        echo $this->sections[$name];
    }

    /**
     * @param  array<mixed>  $data
     */
    public function fetch(string|TemplateClassInterface $name, array $data = [], bool $useTemplateData = true): string
    {
        return $this->engine->render($name, $useTemplateData ? array_merge($this->data, $data) : $data);
    }

    public function batch(mixed $var, string $functions): mixed
    {
        foreach (explode('|', $functions) as $function) {
            if ($this->engine->doesFunctionExist($function)) {
                $callable = [$this, $function];
                assert(is_callable($callable));
                $var = call_user_func($callable, $var);
            } elseif (is_callable($function)) {
                $var = call_user_func($function, $var);
            } else {
                throw new LogicException(
                    'The batch function could not find the "' . $function . '" function.'
                );
            }
        }

        return $var;
    }

    public function escape(string $string, ?string $functions = null): string
    {
        static $flags;

        if (! isset($flags)) {
            $flags = ENT_QUOTES | (defined('ENT_SUBSTITUTE') ? ENT_SUBSTITUTE : 0);
        }

        if ($functions) {
            $string = $this->batch($string, $functions);
        }

        assert(is_string($string));
        assert(is_int($flags));

        return htmlspecialchars($string, $flags, 'UTF-8');
    }

    public function e(string $string, ?string $functions = null): string
    {
        return $this->escape($string, $functions);
    }
}
