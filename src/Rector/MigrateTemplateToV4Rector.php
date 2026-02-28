<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Rector;

use PhpParser\Node\Name;
use PiedWeb\Splates\Template\TemplateAbstract;
use PhpParser\Node\Stmt\ClassMethod;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Migrates Splates v3 templates to v4 syntax.
 *
 * Before (v3):
 * ```php
 * class ProfileTpl implements TemplateClassInterface
 * {
 *     public function __construct(public string $name) {}
 *
 *     public function display(Template $t, TemplateFetch $f, TemplateEscape $e, string $name): void
 *     {
 *         echo $e($name);
 *         echo $f(new SidebarTpl());
 *     }
 * }
 * ```
 *
 * After (v4):
 * ```php
 * class ProfileTpl extends TemplateAbstract
 * {
 *     public function __construct(
 *         #[TemplateData]
 *         public string $name,
 *     ) {}
 *
 *     public function __invoke(): void
 *     {
 *         echo $this->e($name);
 *         echo $this->render(new SidebarTpl());
 *     }
 * }
 * ```
 */
final class MigrateTemplateToV4Rector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Migrates Splates v3 templates to v4 syntax with #[TemplateData] attributes',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateFetch;
use PiedWeb\Splates\Template\TemplateEscape;
use PiedWeb\Splates\Template\TemplateClassInterface;

class ProfileTpl implements TemplateClassInterface
{
    public function __construct(public string $name) {}

    public function display(Template $t, TemplateFetch $f, TemplateEscape $e, string $name): void
    {
        echo $e($name);
        echo $f(new SidebarTpl());
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

class ProfileTpl extends TemplateAbstract
{
    public function __construct(
        #[TemplateData]
        public string $name,
    ) {}

    public function __invoke(): void
    {
        echo $this->e($name);
        echo $this->render(new SidebarTpl());
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isTemplateClass($node)) {
            return null;
        }

        $hasChanges = false;

        // 1. Change "implements TemplateClassInterface" to "extends TemplateAbstract"
        if ($this->changeToExtendsTemplateAbstract($node)) {
            $hasChanges = true;
        }

        // 2. Add #[TemplateData] to constructor properties
        if ($this->addTemplateDataToConstructor($node)) {
            $hasChanges = true;
        }

        // 3. Clean up display() method parameters and transform calls
        if ($this->cleanupDisplayMethod($node)) {
            $hasChanges = true;
        }

        return $hasChanges ? $node : null;
    }

    private function isTemplateClass(Class_ $class): bool
    {
        // Check if implements TemplateClassInterface
        foreach ($class->implements as $implement) {
            $implementName = $this->getName($implement);
            if (str_contains((string) $implementName, 'TemplateClassInterface')) {
                return true;
            }
        }

        // Check if has display() method with Template parameter
        foreach ($class->getMethods() as $method) {
            if ($this->isName($method, 'display')) {
                foreach ($method->params as $param) {
                    if ($param->type !== null) {
                        $typeName = $this->getName($param->type);
                        if ($typeName !== null && str_contains($typeName, 'Template')) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function changeToExtendsTemplateAbstract(Class_ $class): bool
    {
        $hasChanges = false;

        // Remove TemplateClassInterface from implements
        $newImplements = [];
        foreach ($class->implements as $implement) {
            $implementName = $this->getName($implement);
            if (str_contains((string) $implementName, 'TemplateClassInterface')) {
                $hasChanges = true;

                continue;
            }

            $newImplements[] = $implement;
        }

        $class->implements = $newImplements;

        // Add extends TemplateAbstract if not already extending
        if (!$class->extends instanceof Name) {
            $class->extends = new FullyQualified(TemplateAbstract::class);
            $hasChanges = true;
        }

        return $hasChanges;
    }

    private function addTemplateDataToConstructor(Class_ $class): bool
    {
        $constructor = $class->getMethod('__construct');
        if (!$constructor instanceof ClassMethod) {
            return false;
        }

        $hasChanges = false;

        foreach ($constructor->params as $param) {
            // Skip if already has #[TemplateData]
            if ($this->hasTemplateDataAttribute($param)) {
                continue;
            }

            // Skip if it's a promoted property (has visibility flag)
            if ($param->flags === 0) {
                continue;
            }

            // Add #[TemplateData] attribute
            $attribute = new Attribute(
                new FullyQualified(TemplateData::class)
            );
            $param->attrGroups[] = new AttributeGroup([$attribute]);
            $hasChanges = true;
        }

        return $hasChanges;
    }

    private function hasTemplateDataAttribute(Param $param): bool
    {
        foreach ($param->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $this->getName($attr->name);
                if (str_contains((string) $attrName, 'TemplateData')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function cleanupDisplayMethod(Class_ $class): bool
    {
        $displayMethod = $class->getMethod('display');
        if (!$displayMethod instanceof ClassMethod) {
            return false;
        }

        $hasChanges = false;

        // Rename display() to __invoke()
        $displayMethod->name = new Identifier('__invoke');
        $hasChanges = true;

        // Remove Template, TemplateFetch, TemplateEscape parameters and data parameters
        $displayMethod->params = array_values(array_filter(
            $displayMethod->params,
            function (Param $param) use ($class): bool {
                if ($param->type !== null) {
                    $typeName = $this->getName($param->type);
                    if ($typeName !== null && (str_contains($typeName, 'Template') || str_contains($typeName, 'TemplateFetch') || str_contains($typeName, 'TemplateEscape'))) {
                        return false;
                    }
                }

                $paramName = $this->getName($param->var);

                return $paramName === null || ! $this->isConstructorProperty($class, $paramName);
            }
        ));

        // Transform method calls in the body
        if ($displayMethod->stmts !== null) {
            $this->transformMethodCalls($displayMethod->stmts);
        }

        return $hasChanges;
    }

    private function isConstructorProperty(Class_ $class, string $paramName): bool
    {
        $constructor = $class->getMethod('__construct');
        if (!$constructor instanceof ClassMethod) {
            return false;
        }
        return array_any($constructor->params, fn($param): bool => $param->flags !== 0 && $this->isName($param->var, $paramName));
    }

    /**
     * @param Node[] $stmts
     */
    private function transformMethodCalls(array $stmts): void
    {
        $this->traverseNodesWithCallable($stmts, function (Node $node): ?Node {
            // Transform $e($value) to $this->e($value)
            if ($node instanceof FuncCall &&
                $node->name instanceof Variable &&
                $this->isName($node->name, 'e')) {
                return new MethodCall(
                    new Variable('this'),
                    new Identifier('e'),
                    $node->args
                );
            }

            // Transform $f(new Tpl()) to $this->render(new Tpl())
            if ($node instanceof FuncCall &&
                $node->name instanceof Variable &&
                $this->isName($node->name, 'f')) {
                return new MethodCall(
                    new Variable('this'),
                    new Identifier('render'),
                    $node->args
                );
            }

            // Transform $t->e($value) to $this->e($value)
            if ($node instanceof MethodCall &&
                $node->var instanceof Variable &&
                $this->isName($node->var, 't')) {
                $methodName = $this->getName($node->name);
                if ($methodName === 'e' || $methodName === 'escape') {
                    return new MethodCall(
                        new Variable('this'),
                        new Identifier('e'),
                        $node->args
                    );
                }

                // Transform $t->layout() - will need manual conversion to slots
                if ($methodName === 'layout') {
                    // Add a comment to indicate manual conversion needed
                    // For now, just change to $this->render()
                    return new MethodCall(
                        new Variable('this'),
                        new Identifier('render'),
                        $node->args
                    );
                }
            }

            // Transform $variable to $this->variable for constructor properties
            // This is tricky - we need context about what variables are properties
            // For now, we skip this transformation as it requires more context

            return null;
        });
    }
}
