<?php

namespace App\Core\Outer;

abstract class Crawlers {

  public static function detect() {
    if (isset($_SERVER['HTTP_USER_AGENT']) 
      && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'])) {
      return true;
    }
    else {
      return false;
    }
  }
  
  
  

}