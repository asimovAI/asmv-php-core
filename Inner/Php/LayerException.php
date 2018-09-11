<?php

namespace App\Core\Inner\Php;

use Exception;

/**
 * 
 */
class LayerException extends Exception {

  private $calling_class = null;

  // Redefine the exception so message isn't optional
  public function __construct($message, $code = 0, Exception $previous = null) {

    if(function_exists('get_calling_class')) {
      $this->calling_class = get_calling_class();
    } else {
      $this->calling_class = 'unknown class';
    }

    $code = (empty($code)) ? crc32(array_pop(explode('\\', $this->calling_class))) : $code;
    parent::__construct($message, $code, $previous);
//    error_log($this->__toString());
    
  }

  // custom string representation of object
  public function __toString() {
    $colors = new Colors();
    return $colors->getColoredString("[Wozot]",  "light_red", "black")
    . " # [" . $colors->getColoredString($this->code, "light_green") . "] "
    . $this->calling_class
    . $colors->getColoredString(" >> ", "dark_gray")
    . $colors->getColoredString(' ' . $this->message . ' ', "red", "light_gray");
  }

}
