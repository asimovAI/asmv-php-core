<?hh

namespace App\Core\Inner\Cache\Hhvm;

use App\Core\Core;
use App\Core\Inner\Cache\Cache;
use \Memcached;
use App\Core\Inner\Php\LayerException;


/**

CacheAsync Class

@description:
It use Multi Memcached Server to set a FastCache and SlowCache Endpoints
Hack provides a feature called >async< that provides your program the benefit of cooperative multi-tasking.
The two instances of memcached are in this cooperative multi-tasking environment.

@requires: Hack + Memcached

*/
abstract class CacheAsync {

    public static $_mem_slowcache = null;
    public static $_mem_fastcache = null;

    private static $results = array();
    private static $temp_tracking = array();

    const DEFAULT_EXPIRATION = 600; //in seconds
    const DEFAULT_TRACK_EXPIRATION = 43200; //12h
    const MAX_FREQ_TOFASTCACHE = 10;
    const MAX_ITEMS_FASTCACHE = 50; //bytes written
    const KEY_FREQ_TRACKING = 'freqtracking';

    static async function init_main(): Awaitable<bool> {
      if(empty(Core::$_settings["cache-prefix"]))
        Core::$_settings["cache-prefix"] = '';

      await \HH\Asio\v([
        self::do_init_fastcache(),
        self::do_init_slowcache(),
      ]);
      return true;
    }

    /**
    Init Servers
    */

    static async function do_init_fastcache(): Awaitable<void> {
        self::$_mem_fastcache = new Memcached();
        self::$_mem_fastcache->addServer(memcached_host_fastcache, (int) memcached_port_fastcache);
        $statuses = self::$_mem_fastcache->getStats();
        self::$_mem_fastcache->setOption(Memcached::OPT_BINARY_PROTOCOL,true);
        self::$temp_tracking = ($r = self::$_mem_fastcache->get(self::KEY_FREQ_TRACKING)) ? $r : array();
        //error_log(print_r($statuses, true));
        if ($statuses[memcached_host_fastcache.":".(int) memcached_port_fastcache]["pid"] <= 0) {
          throw new LayerException('Please, setup 1st Memcached Instance');
        }
    }

    static async function do_init_slowcache(): Awaitable<void> {
        self::$_mem_slowcache = new Memcached();
        self::$_mem_slowcache->addServer(memcached_host, (int) memcached_port);
        $statuses = self::$_mem_slowcache->getStats();
        //error_log(print_r($statuses, true));
        self::$_mem_slowcache->setOption(Memcached::OPT_BINARY_PROTOCOL,true);
        if ($statuses[memcached_host.":".(int) memcached_port]["pid"] <= 0) {
          if(php_sapi_name() !== 'cli')
          throw new LayerException('Please, setup 2nd Memcached Instance');
        }

    }


    /**
    Get & Put Main Methods
    */
    static async function get_main($key): Awaitable<mixed> {
      $key = Core::$_settings["cache-prefix"] . $key;
      $r = false;
      await \HH\Asio\v([
        self::do_get($key),
        self::do_tracking_get($key),
      ]);
      if(!empty(self::$results[$key])) {
        $r = self::$results[$key];
      }
      return $r;
    }

    static async function put_main($key, $value, $expiration): Awaitable<bool> {

      $key = Core::$_settings["cache-prefix"] . $key;
      $stats = self::$_mem_fastcache->getStats();

      if($expiration === 1) { // se l'expire = 1, allora annullo anche la cache locale
        self::$_mem_fastcache->set($key, $value, 1);
      }

      if($stats[key($stats)]["curr_items"] <= self::MAX_ITEMS_FASTCACHE) {
      //^ Se il valore è 1, (quasi) sicuramente è un override del valore in cache, quindi salto il controllo sul limite della cache locale
        self::$temp_tracking[$key."-expiration"] = $expiration;
        self::$_mem_fastcache->set(self::KEY_FREQ_TRACKING, self::$temp_tracking, self::DEFAULT_TRACK_EXPIRATION);
      }
      return self::$_mem_slowcache->set($key, $value, $expiration);
    }

    //

    /**
    do_get
    @async + do_tracking_get
    */
    static async function do_get(string $key): Awaitable<void> {
        if(self::$results[$key] = self::$_mem_fastcache->get($key)) {
            //error_log("ho chiamato da locale: {$key}");
            return;
        }
        self::$results[$key] = self::$_mem_slowcache->get($key);
        if(empty(self::$temp_tracking[$key])) self::$temp_tracking[$key] = 0;
        if(self::$temp_tracking[$key] >= self::MAX_FREQ_TOFASTCACHE) {
            self::$_mem_fastcache->set($key, self::$results[$key],
                (!empty(self::$temp_tracking[$key."-expiration"])) ? self::$temp_tracking[$key."-expiration"] : Cache::DEFAULT_EXPIRATION );
        }
    }

    static async function do_tracking_get(string $key): Awaitable<void> {
        $expiration = self::DEFAULT_TRACK_EXPIRATION;
        if(!empty(self::$temp_tracking[$key])) {
        self::$temp_tracking[$key] = self::$temp_tracking[$key] + 1;
        } else {
        self::$temp_tracking[$key] = 1;
        }
        self::$_mem_fastcache->set(self::KEY_FREQ_TRACKING, self::$temp_tracking, $expiration);
    }

}
