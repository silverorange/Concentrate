<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Concentrate',
        __DIR__ . '/scripts',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php82: true)
    ->withRules([
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        MixedTypeRector::class,
        NullToStrictStringFuncCallArgRector::class,
        RemoveUnusedVariableInCatchRector::class,
    ])
    ->withTypeCoverageLevel(1)
    ->withDeadCodeLevel(1);
