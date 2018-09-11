<?php

namespace App\Core\Inner\Php;

/**
 * Class override
 */
class OverrideFunction {

  var $functions = array();
  var $includes = array();

  function override_function($override, $function, $include) {
    if ($include) {
      $this->includes[$override] = $include;
    } else if (isset($this->includes[$override])) {
      unset($this->includes[$override]);
    }
    $this->functions[$override] = $function;
  }

  function override_check($override) {
    if (isset($this->includes[$override])) {
      if (file_exists($this->includes[$override])) {
        include_once($this->includes[$override]);
      }
      if (function_exists($this->functions[$override])) {
        return $this->functions[$override];
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
}

