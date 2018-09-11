<?php

namespace App\Core\Outer;
use App\Core\Inner\Cache\Cache;
/**
 * Class XmlToJson
 */
abstract class XmlToJson {

  const microCacheExpirationTime = 10; //in seconds
  const suffixCache = 'api-xmltojson';

  /**
   * @param $url
   * @return bool|mixed|string|void
   */
  public static function Parse ($key, $content) {
    //check if it's in cache:
    if ($r = self::fromCache($key)) {
      return $r;
    } else { //else, generate:
      $content = str_replace(array("\n", "\r", "\t"), '', $content);
      $content = trim(str_replace('"', "'", $content));
      $simpleXml = simplexml_load_string($content);
      $json = json_encode($simpleXml);
      Cache::put(self::suffixCache . $key, serialize($json), self::microCacheExpirationTime);
      return $json;
    }
  }

  /**\
   * @param $key
   * @return bool|mixed
   */
  private static function fromCache($key) {
    if (Cache::has(self::suffixCache . $key)) {
      $return = unserialize(Cache::get($key));
      return $return;
    }
   return false;
  }

}

