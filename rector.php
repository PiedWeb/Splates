<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests'])
    ->withPhpSets(php83: true)
    ->withRules([
        AddReturnTypeDeclarationRector::class,
        ExplicitNullableParamTypeRector::class,
    ])
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        SetList::PRIVATIZATION,
        LevelSetList::UP_TO_PHP_83,
        /** @disregard P1009 */
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ])
    ->withImportNames()
    ->withSkip([]);
