<?php

namespace App\Core\Inner;
use Symfony\Component\Yaml\Parser; 
use App\Core\Inner\Cache\Cache;

/**
 * Yaml-Cached Manager
 * 
 * @info YAML is a human friendly data serialization
  standard for all programming languages.
 * @from yaml symfony component
 * http://symfony.com/doc/current/components/yaml/introduction.html
 */
class Cyml {
  
  const attachmentsuffix = '-attachment';
  protected $symfony_yaml = null;
  private $mtime = array();
  
  protected $yml_cachetime        = 10;//m //default, in minutes
  protected $attachment_cachetime = 10;//m //default, in minutes
  protected $filemtime_cachetime  = 20;//s //defail in seconds

  /**
   * Cyml constructor.
   */
  public function __construct() {
    $this->symfony_yaml = new Parser();
  }

  /**
   * @param $filename
   * @return array|mixed
   */
  public function parse($filename) {
    $return = array();
    $mtime = $this->filemtime($filename);
    $key = $filename . "_" . $mtime;
    if (Cache::has($key)) {
      $return = unserialize(Cache::get($key));
      return $return;
    } else {
      $values = $this->symfony_yaml->parse(file_get_contents($filename));
      $this->yml_cachetime = $values["config-cache-time"];
      Cache::put($key, serialize($values), $this->yml_cachetime*60); 
      return $values;
    }
  }

  /**
   * @param $filename
   * @return Cache\type|bool|int|mixed
   */
  private function filemtime($filename) {
    $mtimecacheKey = $filename."mtime";
    
    if(!empty($this->mtime[$filename])) {
      return $this->mtime[$filename];
    } 
    else if(Cache::has($mtimecacheKey)) {
      return Cache::get($mtimecacheKey);
    }
    else {
      $mtime = filemtime($filename);
      Cache::put($mtimecacheKey, $mtime, $this->filemtime_cachetime);
      return $mtime;
    }
    return false; 
  }
  
  
  
  
}
