<?php

declare(strict_types=1);

use PiedWeb\Splates\RectorizeTemplate;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RectorizeTemplate::class);
};
