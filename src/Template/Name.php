<?php

namespace PiedWeb\Splates\Template;

use LogicException;
use PiedWeb\Splates\Engine;

final class Name
{
    private ?Folder $folder = null;

    private string $file;

    public function __construct(public readonly Engine $engine, private string $name)
    {
        $this->setName($name);
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        $parts = explode('::', $this->name);

        if (count($parts) === 1) {
            $this->setFile($parts[0]);
        } elseif (count($parts) === 2) {
            $this->setFolder($parts[0]);
            $this->setFile($parts[1]);
        } else {
            throw new LogicException(
                'The template name "' . $this->name . '" is not valid. ' .
                'Do not use the folder namespace separator "::" more than once.'
            );
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setFolder(string $folder): static
    {
        $this->folder = $this->engine->getFolders()->get($folder);

        return $this;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFile(string $file): static
    {
        if ($file === '') {
            throw new LogicException(
                'The template name "' . $this->name . '" is not valid. ' .
                'The template name cannot be empty.'
            );
        }


        $extension = $this->engine->getFileExtension();
        $this->file = $file . ($extension === '' ? '' : '.' . $this->engine->getFileExtension());

        return $this;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getPath(): string
    {
        if (is_null($this->folder)) {
            return sprintf('%s/%s', $this->getDefaultDirectory(), $this->file);
        }

        $path = sprintf('%s/%s', $this->folder->getPath(), $this->file);

        if (! is_file($path)
        && $this->folder->fallback
        && is_file(sprintf('%s/%s', $this->getDefaultDirectory(), $this->file))) {
            return sprintf('%s/%s', $this->getDefaultDirectory(), $this->file);
        }

        return $path;
    }

    /**
     * Check if template path exists.
     * @return bool
     */
    public function doesPathExist(): bool
    {
        return is_file($this->getPath());
    }

    private function getDefaultDirectory(): string
    {
        $directory = $this->engine->getDirectory();

        if (is_null($directory)) {
            throw new LogicException(
                'The template name "' . $this->name . '" is not valid. '.
                'The default directory has not been defined.'
            );
        }

        return $directory;
    }
}
