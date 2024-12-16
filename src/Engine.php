<?php

namespace PiedWeb\Splates;

use PiedWeb\Splates\Extension\ExtensionInterface;
use PiedWeb\Splates\Template\Data;
use PiedWeb\Splates\Template\Directory;
use PiedWeb\Splates\Template\FileExtension;
use PiedWeb\Splates\Template\Folders;
use PiedWeb\Splates\Template\Func;
use PiedWeb\Splates\Template\Functions;
use PiedWeb\Splates\Template\Name;
use PiedWeb\Splates\Template\ResolveTemplatePath;
use PiedWeb\Splates\Template\ResolveTemplatePath\NameAndFolderResolveTemplatePath;
use PiedWeb\Splates\Template\ResolveTemplatePath\ThemeResolveTemplatePath;
use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateClass;
use PiedWeb\Splates\Template\TemplateClassInterface;
use PiedWeb\Splates\Template\Theme;

/**
 * Template API and environment settings storage.
 */
class Engine
{
    /**
     * Default template directory.
     */
    protected Directory $directory;

    /**
     * Template file extension.
     */
    protected FileExtension $fileExtension;

    /**
     * Collection of template folders.
     */
    protected Folders $folders;

    /**
     * Collection of template functions.
     */
    protected Functions $functions;

    /**
     * Collection of preassigned template data.
     */
    protected Data $data;

    /** @var ResolveTemplatePath */
    private $resolveTemplatePath;

    /**
     * Create new Engine instance.
     * @param string $directory
     * @param string $fileExtension
     */
    public function __construct($directory = null, $fileExtension = 'php')
    {
        $this->directory = new Directory($directory);
        $this->fileExtension = new FileExtension($fileExtension);
        $this->folders = new Folders();
        $this->functions = new Functions();
        $this->data = new Data();
        $this->resolveTemplatePath = new NameAndFolderResolveTemplatePath();
    }

    public static function fromTheme(Theme $theme, string $fileExtension = 'php'): self
    {
        $engine = new self(null, $fileExtension);
        $engine->setResolveTemplatePath(new ThemeResolveTemplatePath($theme));

        return $engine;
    }

    public function setResolveTemplatePath(ResolveTemplatePath $resolveTemplatePath): static
    {
        $this->resolveTemplatePath = $resolveTemplatePath;

        return $this;
    }

    public function getResolveTemplatePath(): ResolveTemplatePath
    {
        return $this->resolveTemplatePath;
    }

    /**  @param  string|null $directory Pass null to disable the default directory.  */
    public function setDirectory(?string $directory): static
    {
        $this->directory->set($directory);

        return $this;
    }

    public function getDirectory(): ?string
    {
        return $this->directory->get();
    }

    public function setFileExtension(string $fileExtension): static
    {
        $this->fileExtension->fileExtension = $fileExtension;

        return $this;
    }

    public function getFileExtension(): string
    {
        return $this->fileExtension->fileExtension;
    }

    public function addFolder(string $name, string $directory, bool $fallback = false): static
    {
        $this->folders->add($name, $directory, $fallback);

        return $this;
    }

    public function removeFolder(string $name): static
    {
        $this->folders->remove($name);

        return $this;
    }

    /**
     * Get collection of all template folders.
     * @return Folders
     */
    public function getFolders(): Folders
    {
        return $this->folders;
    }

    /**
     * @param  array<mixed>             $data;
     * @param  null|string|array<string> $templates;
     */
    public function addData(array $data, $templates = null): static
    {
        $this->data->add($data, $templates);

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getData(?string $template = null): array
    {
        return $this->data->get($template);
    }

    public function registerFunction(string $name, callable $callback): static
    {
        $this->functions->add($name, $callback);

        return $this;
    }

    public function dropFunction(string $name): static
    {
        $this->functions->remove($name);

        return $this;
    }

    public function getFunction(string $name): Func
    {
        return $this->functions->get($name);
    }

    public function doesFunctionExist(string $name): bool
    {
        return $this->functions->exists($name);
    }

    public function loadExtension(ExtensionInterface $extension): static
    {
        $extension->register($this);

        return $this;
    }

    /**
     * @param  array<ExtensionInterface>  $extensions
     */
    public function loadExtensions(array $extensions = []): static
    {
        foreach ($extensions as $extension) {
            $this->loadExtension($extension);
        }

        return $this;
    }

    public function path(string $name): string
    {
        $name = new Name($this, $name);

        return $name->getPath();
    }

    public function exists(string $name): bool
    {
        $name = new Name($this, $name);

        return $name->doesPathExist();
    }

    /**
     * @param  array<mixed>                           $data
     */
    public function make(string|TemplateClassInterface $name, array $data = []): Template
    {
        $template = $name instanceof TemplateClassInterface ? new TemplateClass($this, $name)
            : new Template($this, $name);
        $template->data($data);

        return $template;
    }

    /**
     * @param  array<mixed>  $data
     */
    public function render(string|TemplateClassInterface $name, array $data = []): string
    {
        return $this->make($name)->render($data);
    }
}
