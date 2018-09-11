<?php

namespace App\Core\Outer;

/**
 * Class ResponseCode
 * @package App\Core\Outer
 */
abstract class ResponseCode {

  /**
   * @param $n
   * @param null $message
   * @param bool $api
   */
  public static function get($n, $message = null, $api = false) {

    http_response_code($n);

    if (!$api) {

      if (!empty($message))
        echo $message;

    } else {

      $result = [];

      header('Content-Type: application/json');

      if(in_array($n, [404,501,502,503,504]))
        $result['result'] = 'false';
      else
        $result['result'] = 'true';

      if (!empty($message))
        $result['message'] = $message;

      echo json_encode($result);

    }

    die(); //close
  }

}