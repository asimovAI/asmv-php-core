<?php

namespace App\Core\Outer;


/**
 * Inspired to Redirect Laravel Class
 * =
 */
abstract class Redirect {
  
  public static function to($url, $code = 300) {
    
    http_response_code($code);
    
    header("Location: $url");
    //if unsuccessful:
    echo '<script language="javascript">
            top.location.href = "'.$url.'";
          </script>';
    die();
    return; 
  }
  
  
  
}