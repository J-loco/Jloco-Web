<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/config', __DIR__ . '/tests'])
    ->append([__DIR__ . '/public/index.php', __DIR__ . '/public/launcher/status.php', __DIR__ . '/public/launcher/news.php', __DIR__ . '/bin/migrate', __DIR__ . '/bin/sync-experience', __DIR__ . '/bin/item-sprites']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setCacheFile('/tmp/php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
    ])
    ->setFinder($finder);
