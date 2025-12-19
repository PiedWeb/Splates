<?php

declare(strict_types=1);

use PiedWeb\Splates\Rector\MigrateTemplateToV4Rector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        MigrateTemplateToV4Rector::class,
    ]);
