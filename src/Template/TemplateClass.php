<?php

namespace PiedWeb\Splates\Template;

use Exception;
use Override;
use PiedWeb\Splates\Engine;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Container which holds template data and provides access to template functions.
 */
class TemplateClass extends Template
{
    protected TemplateFetch $templateFetch;

    protected TemplateEscape $templateEscape;

    public function __construct(
        Engine $engine,
        protected TemplateClassInterface $templateClass
    ) {
        $this->engine = $engine;
        $name = $templateClass::class;

        $this->data($this->engine->getData($name)); // needed for addData, too much magic, deprecate it ?!

        $this->templateFetch = new TemplateFetch($this->engine, $this);
        $this->templateEscape = new TemplateEscape($this);

    }

    #[Override]
    protected function display(): void
    {
        $this->mergePropertyToData();
        $this->autowireDataToTemplateClass();

        $vars = $this->getVarToAutowireDisplayMethod();
        if (! method_exists($this->templateClass, 'display')) {
            throw new Exception('A `display` method is missing in `'.$this->templateClass::class.'`');
        }

        $this->templateClass->display(...$vars);
    }

    protected function mergePropertyToData(): void
    {
        $properties = (new ReflectionClass($this->templateClass))->getProperties(ReflectionProperty::IS_PUBLIC);

        $dataToImport = [];
        foreach ($properties as $property) {
            if (! $property->isInitialized($this->templateClass)) { // $property->isReadOnly() &&
                continue;
            }

            $propertyValue = $property->getValue($this->templateClass);
            if ($propertyValue === $property->getDefaultValue()) {
                continue;
            }

            if ($propertyValue === null) {
                continue;
            }

            $dataToImport[$property->getName()] = $propertyValue;
        }

        if ($dataToImport !== []) {
            $this->data($dataToImport);
        }

    }

    /** @return array<string, mixed> */
    protected function getVarToAutowireDisplayMethod(): array
    {
        $displayReflection = new ReflectionMethod($this->templateClass, 'display');

        $parameters = $displayReflection->getParameters();

        // Extract the parameter names
        $parametersToAutowire = [];
        foreach ($parameters as $parameter) {

            if ($parameter->getType() instanceof ReflectionNamedType // avoid union or intersection type
                && in_array($parameter->getType()->getName(), [TemplateClass::class, Template::class], true)) {
                $parametersToAutowire[$parameter->getName()] = $this;

                continue;
            }

            if ($parameter->getName() === 'f') {
                $fetch = $this->templateFetch;
                $parametersToAutowire['f'] = $fetch; //$fetch(...);

                continue;
            }


            if ($parameter->getName() === 'e') {
                $escape = $this->templateEscape;
                $parametersToAutowire['e'] = $escape; //(...);

                continue;
            }

            $parametersToAutowire[$parameter->getName()] = $this->data()[$parameter->getName()]
                ?? ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
        }

        return $parametersToAutowire;

    }

    protected function autowireDataToTemplateClass(): void
    {
        $properties = (new ReflectionClass($this->templateClass))->getProperties();

        foreach ($properties as $property) {
            if ($property->isInitialized($this->templateClass)) {
                continue;
            }

            if ($property->getName() === 'template' && property_exists($this->templateClass, 'template')) {
                /**  @disregard P1014 */
                $this->templateClass->template = $this;

                continue;
            }

            if (isset($this->data[$property->getName()])) {
                $this->templateClass->{$property->getName()} = $this->data[$property->getName()];
            }
        }
    }

    /** Disable useless public/protected parent method and property */

    //private mixed $name;

    #[Override]
    public function exists(): bool
    {
        return true;
    }

    #[Override]
    public function path(): string
    {
        return $this->templateClass::class;
    }
}
