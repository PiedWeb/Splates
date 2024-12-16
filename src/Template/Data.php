<?php

namespace PiedWeb\Splates\Template;

/**
 * Preassigned template data.
 */
class Data
{
    /** @var array<mixed> */
    protected array $sharedVariables = [];

    /** @var array<mixed> */
    protected $templateVariables = [];

    /**
     * @param  array<mixed>             $data;
     * @param  null|string|array<string> $templates;
     */
    public function add(array $data, $templates = null): static
    {
        if (is_null($templates)) {
            return $this->shareWithAll($data);
        }

        if (is_array($templates)) {
            return $this->shareWithSome($data, $templates);
        }

        return $this->shareWithSome($data, [$templates]);
    }

    /**
     * @param  array<mixed> $data;
     */
    public function shareWithAll(array $data): static
    {
        $this->sharedVariables = array_merge($this->sharedVariables, $data);

        return $this;
    }

    /**
     * @param  array<mixed> $data;
     * @param  array<string> $templates;
     */
    public function shareWithSome(array $data, array $templates): static
    {
        foreach ($templates as $template) {
            if (isset($this->templateVariables[$template])) {
                assert(is_array($this->templateVariables[$template]));
                $this->templateVariables[$template] = array_merge($this->templateVariables[$template], $data);
            } else {
                $this->templateVariables[$template] = $data;
            }
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function get(?string $template = null): array
    {
        if (isset($template, $this->templateVariables[$template])) {
            assert(is_array($this->templateVariables[$template]));

            return array_merge($this->sharedVariables, $this->templateVariables[$template]);
        }

        return $this->sharedVariables;
    }
}
