<?php

// @todo: This can be refactored out as it is using eval.
$projectPath = $argv[1];
unset($argv[1]);

/*
|--------------------------------------------------------------------------
| Partial copy of artisan.
|--------------------------------------------------------------------------
| This is  hard copy of the artisan file. It will load the custom commands used to extract
| required data from the host application.
*/

require $projectPath . '/vendor/autoload.php';

$app = require_once $projectPath . '/bootstrap/app.php';

if ($app === true) {
    echo json_encode([]);
    return;
}

// Force the cache driver to be array.
$_ENV['CACHE_DRIVER'] = 'array';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$baseDir = __DIR__ . '/../../';

$options = [];

if ($argv[2] === 'command') {
    include_once $baseDir . '/app/helpers/SubCommands/ExecuteArtisan.php';
} elseif ($argv[2] === 'views') {
    include_once $baseDir . '/app/helpers/SubCommands/Views.php';
} elseif ($argv[2] === 'config') {
    include_once $baseDir . '/app/helpers/SubCommands/Config.php';
} elseif ($argv[2] === 'snippets') {
    include_once $baseDir . '/app/helpers/SubCommands/Snippets.php';
} elseif ($argv[2] === 'container') {
    include_once $baseDir . '/app/helpers/SubCommands/Container.php';
} elseif ($argv[2] === 'routes') {
    include_once $baseDir . '/app/helpers/SubCommands/Routes.php';
} elseif ($argv[2] === 'helpers') {
    include_once $baseDir . '/app/helpers/SubCommands/Helpers.php';
} elseif ($argv[2] === 'models') {
    include_once $baseDir . '/app/helpers/SubCommands/Models.php';
}
