<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

// Run in dry-run mode in CI (composer rector); apply with "composer rector:fix".
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/config', __DIR__ . '/public/index.php', __DIR__ . '/public/launcher/status.php', __DIR__ . '/public/launcher/news.php', __DIR__ . '/bin', __DIR__ . '/tests'])
    ->withPhpSets(php84: true)
    ->withPreparedSets(deadCode: true, typeDeclarations: true)
    ->withCache('/tmp/rector');
