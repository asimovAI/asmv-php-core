<?php

namespace App\Core\Inner\Cache;

use App\Core\Core;
use App\Core\Inner\Cache\Hhvm\CacheAsync;

/**
 * Cache Manager
 * **************
 * It Uses a double-memcached instances -> see CacheAsync
 * in a cooperative multi-tasking environment /HHVM
 * <CacheAsync>
 *
 * Otherwise you can execute a "single" request (set/get) with:
 * @singleSet()
 * @singleGet()
 *
 */
abstract class Cache {

  const DEFAULT_EXPIRATION = 600; //in seconds
  const LABEL_REMOTECACHE = 'REMOTE-Pool-Cache';
  const LABEL_LOCALCACHE = 'LOCAL-Pool-Cache';
  const LABEL_FILECACHE = 'FILE-Cache';
  const RAND_FILECACHE_DEL = 10;
  const CacheFileDir = '/data/www/.cache/';

  protected static $_mem_rem = null;
  public static $_mem_loc = null;
  private static $memc_init_multiple = false;
  private static $memc_init_single = false;
  private static $cf = null;

  /**
   * Re-INITialized on every put/get method, to set file cache key
   * Memcached Objects instead remain in singleton logic
   *
   * @param integer $i
   * @param string $key
   * @return boolean
   */
  static function init(int $i = 2, $key = null): bool {

    //cache backup on file
    $CacheName = self::LABEL_FILECACHE . "|";
    $CacheName .= !empty($key) ? $key : ''; //cachename file ad hoc to key sent by get or put method

    self::$cf = new CacheFile(array(
      'name' => $CacheName,
      'path' => self::CacheFileDir,
      'extension' => '.cache'
    ));

    if (rand(1, self::RAND_FILECACHE_DEL) === self::RAND_FILECACHE_DEL) {
      $interval = strtotime('-24 hours');//files older than 24hours
      foreach (glob(self::CacheFileDir . "*") as $file)
        //delete if older
        if (filemtime($file) <= $interval) unlink($file);
    }

    //# if 2 or more instances and it's not instantiated
    if ($i !== 1 && empty(self::$memc_init_multiple)) {
      if (self::$memc_init_multiple = \HH\Asio\join(CacheAsync::init_main())) { //set flag to true if init_main correctly executed
        self::$_mem_loc = CacheAsync::$_mem_fastcache;
        self::$_mem_rem = CacheAsync::$_mem_slowcache;
      } else {
        return false;
      }
      //# else if 1 instance and it's not instantiated
    } else if ($i === 1 && empty(self::$memc_init_single)) {
      //single instance environment
      \HH\Asio\join(CacheAsync::do_init_fastcache());
      self::$_mem_loc = CacheAsync::$_mem_fastcache;
      self::$memc_init_single = true;
    }

    return true;
  }


  /**
   * Reset Connections
   * @return bool
   */
  static function reset() {
    usleep(10000); //wait 0.01s
    self::$memc_init_multiple = false;
    if (is_object(self::$_mem_loc)) {
      self::$_mem_loc->quit();
    }
    if (is_object(self::$_mem_rem)) {
      self::$_mem_rem->quit();
    }
    usleep(10000); //wait other 0.01s
    return self::init();
  }

    /**
     * @param $key
     * @param $value
     * @param int $expiration
     * @return mixed
     */
  static function put($key, $value, int $expiration = self::DEFAULT_EXPIRATION) {
    self::init(2, $key);
    self::$cf->store($key, $value, $expiration);
    return \HH\Asio\join(CacheAsync::put_main($key, $value, $expiration));
  }

    /**
     * Get Value
     * @param string $key
     * @param bool $flagCacheOnFile
     * @return
     */
  static function get(string $key, $flagCacheOnFile = false) {

    self::init(2, $key);
    $g = \HH\Asio\join(CacheAsync::get_main($key));

    if (empty($g) && self::$_mem_loc->getResultCode() == 47) {
      self::reset();
      $g = \HH\Asio\join(CacheAsync::get_main($key));
    }

    //result $g is still empty, try to get from file:
    //if file_alternative param is true
    if (empty($g) && $flagCacheOnFile === true) {
      self::$cf->eraseExpired();
      $g = self::$cf->retrieve($key);
    }

    return $g;
  }

    /**
     * Has?
     * @param string $key
     * @param bool $flagCacheOnFile
     * @return bool
     */
  static function has(string $key, $flagCacheOnFile = false) {
    self::init();
    $r = self::get($key, $flagCacheOnFile);
    return !empty($r) ? true : false;
  }

    /**
     * <local-memcached> Set
     * @ignoring multi-instance cache
     * @param string $key
     * @param $value
     * @param integer $expiration
     * @return mixed
     */
  static function singleSet(string $key, $value, int $expiration) {
    self::init(1, $key);
    //self::$cf->store($key, $value, $expiration);
    return self::$_mem_loc->set(Core::$_settings["cache-prefix"] . $key, $value, $expiration);
  }

//  /**
//   * <local-memcached> Get
//   * @ignoring multi-instance cache
//   *
//   * It try always to get file cache if memcached failed
//   */
//  static function singleGet($key) {
//    self::init(1, $key);
//    $g = self::$_mem_loc->get(Core::$_settings["cache-prefix"] . $key);
////    if (empty($g) && self::$_mem_loc->getResultCode() == 47) {
////      self::reset();
////      $g = \HH\Asio\join(CacheAsync::get_main($key));
////    }
//
//    //result $g is still empty, try to get from file:
//    //(i) > in singleGet method framework try to get always file cache
//    if (empty($g)) {
//      //self::$cf->eraseExpired();
//      //$g = self::$cf->retrieve($key);
//    }
//
//    return $g;
//  }

  /**
   * Memached Stats
   *
   * @return
   */
  static function stats(): array {
    self::init();
    $r = array();
    $r["Get-FreqTracked:"] = self::$_mem_loc->get('freqtracking');

    //Local
    $ml = self::$_mem_loc;
    $al = $ml->getStats();
    reset($al);
    $first_key = key($al);
    $r[self::LABEL_LOCALCACHE] = $al[$first_key];
    $size = bytesToSize($r[self::LABEL_LOCALCACHE]['bytes_written']);
    $r[self::LABEL_LOCALCACHE]['[*] size-written'] = $size;

    //Remote
    $m = self::$_mem_rem;
    $a = $m->getStats();
    reset($a);
    $first_key = key($a);
    $r[self::LABEL_REMOTECACHE] = $a[$first_key];
    $size = bytesToSize($r[self::LABEL_REMOTECACHE]['bytes_written']);
    $r[self::LABEL_REMOTECACHE]['[*] size-written'] = $size;

    return $r;
  }

}
