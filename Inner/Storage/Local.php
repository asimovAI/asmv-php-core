<?php

namespace App\Core\Inner\Storage;

use App\Core\Core;


/**
 * Class Local
 * @package App\Core\Inner\Storage
 *
 *
 * example:
 *  $filesystem = new Local();
 *  $filesystem->getYml('/lists/engine/hpriority.yml');
 */
class Local extends Storage {

  protected $path = null;

  public function __construct() {
    $this->path = Core::$_settings['root-dir'] . Core::$_settings["local-storage"];
  }




}