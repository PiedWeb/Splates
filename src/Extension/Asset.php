<?php

namespace PiedWeb\Splates\Extension;

use LogicException;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Template;

/**
 * Extension that adds the ability to create "cache busted" asset URLs.
 */
class Asset implements ExtensionInterface
{
    /**
     * Instance of the current template.
     * @var Template
     */
    public $template;

    /**
     * Path to asset directory.
     * @var string
     */
    public $path;

    /**
     * Create new Asset instance.
     * @param string  $path
     * @param bool $filenameMethod
     */
    public function __construct($path, /**
     * Enables the filename method.
     */
        public $filenameMethod = false)
    {
        $this->path = rtrim($path, '/');
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('asset', $this->cachedAssetUrl(...));
    }

    /**
     * Create "cache busted" asset URL.
     * @param  string $url
     * @return string
     */
    public function cachedAssetUrl(string $url): string
    {
        $filePath = $this->path . '/' .  ltrim($url, '/');

        if (! file_exists($filePath)) {
            throw new LogicException(
                'Unable to locate the asset "' . $url . '" in the "' . $this->path . '" directory.'
            );
        }

        $lastUpdated = filemtime($filePath);
        $pathInfo = pathinfo($url);
        $dirname = $pathInfo['dirname'] ?? '';
        $extension = $pathInfo['extension'] ?? '';

        if ($dirname === '.') {
            $directory = '';
        } elseif ($dirname === DIRECTORY_SEPARATOR) {
            $directory = '/';
        } else {
            $directory = $dirname . '/';
        }

        if ($this->filenameMethod) {
            return $directory . $pathInfo['filename'] . '.' . $lastUpdated . '.' . $extension;
        }

        return $directory . $pathInfo['filename'] . '.' . $extension . '?v=' . $lastUpdated;
    }
}
