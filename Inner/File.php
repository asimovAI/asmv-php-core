<?php

namespace App\Core\Inner;

/**
 * 
 */
abstract class File {
  
  /**
   * Storage Type
   * (Local, Ftp, Cloud, etc..)
   * @return \App\Core\Inner\Storage\Local
   */
  public static function Local() {
    return new Storage\Local();
  }
  
  
  
  
}