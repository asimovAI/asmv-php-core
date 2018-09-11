<?php

/*
|--------------------------------------------------------------------------
| Start the Wozot/Core
|--------------------------------------------------------------------------
|
| It  not depends from any vendor/autoloader, because
| it is called by The Wozot Deep Framework
| and by the Graphql/Lumen Framework
|
*/


use App\Core\Core;

require_once __DIR__ . "/../../base.inc";
require_once __DIR__ . '/Inner/Cache/CacheFile.php';
require_once __DIR__ . '/Inner/Cache/Cache.php';
require_once __DIR__ . '/Inner/Cache/Hhvm/CacheAsync.hh';
require_once __DIR__ . '/Inner/Cyml.php';
require_once __DIR__ . '/Core.php';

Core::start();
include(Core::$_settings['emoji-lib']);

/*
from => [web|api|cli]
*/

if (!DEFINED('from')) {
  if (php_sapi_name() === 'cli') {
    define('from', 'cli');
  }
  if (!DEFINED('from')) {
    DEFINE('from', 'web');
  }
}
if (from !== cli) {
  Core::session_start();
}


