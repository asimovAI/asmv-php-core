<?php

namespace App\Core;

use App\Core\Inner\Cache\Cache;
use App\Core\Inner\Cyml;
use App\Core\Outer\Blade;


/**
 * Class Core
 * @package App\Core
 */
class Core {

  public static $_yml = null;
  private static $_instance = false;

  private static $isApi = false;
  private static $isGraph = false;
  private static $isAdmin = false;

  public static $_settings = array();

  public static $views_path = '';
  public static $views_cache_path = '';
  public static $Blade = null;

  public static $_dictionaries = array();
  protected static $filenames_saved = array();

  /**
   * @return bool
   */
  private static function appcheck() {
//    if(from !== cli) {
    if (!defined('HHVM_VERSION')) return false;
    if (!hh_test()) return false;
//    }
    if (!_isCurlEnabled()) return false;
    return true;
  }

  /**
   * @return bool
   */
  public static function start() {

    if (empty(self::$_instance)) {
      $single_or_multi_cache = (php_sapi_name() === 'cli') ? 1 : 2;
      Cache::init($single_or_multi_cache);
      self::$_settings = self::yml()->parse(__DIR__ . '/config.yml');
      if (!empty($_SERVER['SERVER_PORT']) && in_array($_SERVER['SERVER_PORT'], self::$_settings['test-ports'])) {
        self::$_settings['DomainName'] .= ':' . $_SERVER['SERVER_PORT'];
        self::$_settings['DomainName-IT'] .= ':' . $_SERVER['SERVER_PORT'];
      }
      foreach (self::$_settings["phpmod-files"] as $file) {
        require_once(__DIR__ . "/Inner/Functions/" . $file . ".php");
      }
      if (!self::appcheck()) return false;
      self::$_instance = true;
      self::check_env(); //set API env, etc.
      self::start_blade();
    }

    if (php_sapi_name() !== 'cli') {
      if (isImage($_SERVER['REQUEST_URI'])) { //to cache (304) img resources
        session_cache_limiter('public');
      }
    }

    return true;
  }

  /**
   * <session_start()>
   * @return boolean
   */
  public static function session_start() {
    ini_set('session.save_handler', self::$_settings["session_save_handler"]);
    ini_set('session.save_path', self::$_settings["session_save_path"]);
    return session_start();
  }

  /**
   * @return Cyml|null
   */
  public static function yml() {
    self::$_yml = new cyml();
    return self::$_yml;
  }

  /**
   * @return bool
   */
  private static function start_blade() {
    if (self::$isGraph === true)
      return false;

    if (php_sapi_name() !== 'cli') {
      if (!self::$isApi && !self::$isAdmin) {
        $views = __DIR__ . '../../../' . self::$_settings['blade-views'];
        $cache = __DIR__ . '/../../' . self::$_settings['blade-cache'];
      } else if (self::$isApi) {
        $views = __DIR__ . '../../../' . self::$_settings['api-views'];
        $cache = __DIR__ . '/../../' . self::$_settings['api-cache'];
      } else if (self::$isAdmin) {
        $views = __DIR__ . '../../../' . self::$_settings['admin-views'];
        $cache = __DIR__ . '/../../' . self::$_settings['admin-cache'];
      }
      self::$views_path = $views;
      self::$views_cache_path = $cache;

      self::$Blade = new Blade($views, $cache);
      return true;
    }
    return true;
  }

  /**
   *
   */
  public static function check_env() {
    if (php_sapi_name() !== 'cli') {
      $d = $_SERVER['SERVER_NAME'];
      if ($d === self::$_settings['ApiDomain']
        || $d === self::$_settings['GraphDomain']
      ) {
        self::$isApi = true;
        DEFINE('from', 'api');
        if ($d === self::$_settings['GraphDomain'])
          self::$isGraph = true;
      } else if ($d === self::$_settings['AdminDomain']) {
        self::$isAdmin = true;
        DEFINE('from', 'admin');
      }
      return;
    }
    return;
  }

}