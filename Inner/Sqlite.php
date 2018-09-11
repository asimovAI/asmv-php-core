<?php

namespace App\Core\Inner;


class Sqlite extends Database\Database {

  static $init = 0; //flag;

  static function connect($filename) {
    if (self::$init == 0) {
      DEFINE('DB_TYPE', 'sqlite');
      DEFINE('DB_PATH', $filename);
      DEFINE('ERROR_LEVEL', 0); // Set at 0 for development and 1 for production.
      parent::initialize();
      self::$init = 1;
    }
  }

}
