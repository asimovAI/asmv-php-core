<?php
//PHP_VERSION >= 502

/**
 * @millitime()
 * @return now-time milliseconds
 *
 * from <stackoverflow>
 * http://stackoverflow.com/questions/3656713/how-to-get-current-time-in-milliseconds-in-php
 */
function millitime() {
  $microtime = microtime();
  $comps = explode(' ', $microtime);
  // Note: Using a string here to prevent loss of precision
  // in case of "overflow" (PHP converts it to a double)
  return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
}


/**
 * @_isCurl()
 */
function _isCurlEnabled() {
  return function_exists('curl_version');
}


/**
 * @require_path()
 */
function require_path($path) {
  $dir = new RecursiveDirectoryIterator($path);
  $iterator = new RecursiveIteratorIterator($dir);
  foreach ($iterator as $file) {
    $fname = $file->getFilename();
    if (preg_match('%\.php$%', $fname)) {
      require_once($file->getPathname());
    }
  }
}


/**
 * @mb_substr_replace()
 *
 * <stemar>/mb_substr_replace.php
 * https://gist.github.com/stemar/8287074
 */
function mb_substr_replace($string, $replacement, $start, $length = NULL) {
  if (is_array($string)) {
    $num = count($string);
    // $replacement
    $replacement = is_array($replacement)
      ? array_slice($replacement, 0, $num)
      : array_pad(array($replacement), $num, $replacement);
    // $start
    if (is_array($start)) {
      $start = array_slice($start, 0, $num);
      foreach ($start as $key => $value)
        $start[$key] = is_int($value) ? $value : 0;
    } else {
      $start = array_pad(array($start), $num, $start);
    }
    // $length
    if (!isset($length)) {
      $length = array_fill(0, $num, 0);
    } elseif (is_array($length)) {
      $length = array_slice($length, 0, $num);
      foreach ($length as $key => $value)
        $length[$key] = isset($value) ? (is_int($value) ? $value : $num) : 0;
    } else {
      $length = array_pad(array($length), $num, $length);
    }
    // Recursive call
    return array_map(__FUNCTION__, $string, $replacement, $start, $length);
  }
  preg_match_all('/./us', (string)$string, $smatches);
  preg_match_all('/./us', (string)$replacement, $rmatches);
  if ($length === NULL) $length = mb_strlen($string);
  array_splice($smatches[0], $start, $length, $rmatches[0]);
  return join($smatches[0]);
}


/**
 * @narray_slice()
 *
 * Slice an array but keep numeric keys
 *
 * <php.net>
 * http://php.net/manual/en/function.array-slice.php?#73882
 */
function narray_slice($array, $offset, $length) {
  //Check if this version already supports it
  if (str_replace('.', '', PHP_VERSION) >= 502)
    return array_slice($array, $offset, $length, true);

  foreach ($array as $key => $value) {
    if ($a >= $offset && $a - $offset <= $length)
      $output_array[$key] = $value;
    $a++;
  }
  return $output_array;
}


/**
 * @get_string_between($string, $start, $end)
 */
function get_string_between($string, $start, $end) {
  $string = " " . $string;
  $ini = strpos($string, $start);
  if ($ini == 0) return "";
  $ini += strlen($start);
  $len = strpos($string, $end, $ini) - $ini;
  return substr($string, $ini, $len);
}


/**
 * @checksum
 * a fast checksum with crc32 polynomial of a string
 */
function checksum($string) {
  return crc32($string);
}


/**
 * @isJson
 * checking if a string is JSON or not.
 */
function isjson($string) {
  if (is_array($string)) {
    return false;
  }
  json_decode($string);
  return (json_last_error() == JSON_ERROR_NONE);
}


/**
 * @str_varname
 *  transform a $variable in a var string name
 *
 *  ex:
 *  $nomevariabile = 1;
 *  str_varname($nomevariabile) => 'nomevariabile"
 *
 *  from <stackoverflow>
 *  http://stackoverflow.com/questions/255312/how-to-get-a-variable-name-as-a-string-in-php
 */

function str_varname($var) {
  foreach ($GLOBALS as $var_name => $value) {
    if ($value === $var) {
      return $var_name;
    }
  }

  return false;
}


/**
 * Convert bytes to human readable format
 *
 * @param integer bytes Size in bytes to convert
 * @return string
 */
function bytesToSize($bytes, $precision = 2) {
  $kilobyte = 1024;
  $megabyte = $kilobyte * 1024;
  $gigabyte = $megabyte * 1024;
  $terabyte = $gigabyte * 1024;

  if (($bytes >= 0) && ($bytes < $kilobyte)) {
    return $bytes . ' B';

  } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
    return round($bytes / $kilobyte, $precision) . ' KB';

  } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
    return round($bytes / $megabyte, $precision) . ' MB';

  } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
    return round($bytes / $gigabyte, $precision) . ' GB';

  } elseif ($bytes >= $terabyte) {
    return round($bytes / $terabyte, $precision) . ' TB';
  } else {
    return $bytes . ' B';
  }
}


/**
 * Performs a cURL-Request to check, if a website exists / is online
 * from <css-tricks.com>
 * @credits: https://css-tricks.com/snippets/php/check-if-website-is-available/
 *
 * @param type $url
 * @return boolean
 */
function isUrlAvailible($url) {
  //check, if a valid url is provided
  if (!filter_var($url, FILTER_VALIDATE_URL)) {
    return false;
  }

  $agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, $agent);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_VERBOSE, false);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSLVERSION, 3);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  $page = curl_exec($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($httpcode >= 200 && $httpcode <= 350) return true;
  else return false;
}


/**
 *
 * from <stackoverflow>
 * http://stackoverflow.com/questions/79960/how-to-truncate-a-string-in-php-to-the-word-closest-to-a-certain-number-of-chara
 * @param type $string
 * @param type $your_desired_width
 * @return type
 */
function tokenTruncate($string, $your_desired_width, $strip_tags = false, $dotting = false) {

  if ($strip_tags === true)
    $string = strip_tags($string);

  $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
  $parts_count = count($parts);

  $length = 0;
  $last_part = 0;
  for (; $last_part < $parts_count; ++$last_part) {
    $length += strlen($parts[$last_part]);
    if ($length > $your_desired_width) {
      break;
    }
  }

  $r = implode(array_slice($parts, 0, $last_part));
  if ($dotting)
    $r .= '...';
  return trim($r);
}


/**
 * _get()
 * Returns Gets parameters
 * @return type
 */
function _get() {
  return !empty($_GET) ? $_GET : [];
}


/**
 *
 * @param type $url
 * @return boolean
 *
 * it requires <strposa()>
 */
function isImage($url) {
  $pos = strrpos($url, ".");
  if ($pos === false) return false;
  $ext = strtolower(trim(substr($url, $pos)));
  $imgExts = array(".gif", ".jpg", ".jpeg", ".png", ".tiff", ".tif"); // this is far from complete but that's always going to be the case...
  if (strposa($ext, $imgExts) !== false) {
    return true;
  }
  return false;
}


/**
 * Strpos with array in needles
 * ==
 * <http://stackoverflow.com/questions/6284553/using-an-array-as-needles-in-strpos>
 *
 * @param type $haystack
 * @param type $needles
 * @param type $offset
 * @return boolean
 */
function strposa($haystack, $needles = array(), $offset = 0) {
  $chr = array();
  foreach ($needles as $needle) {
    $res = strpos($haystack, $needle, $offset);
    if ($res !== false) $chr[$needle] = $res;
  }
  if (empty($chr)) return false;
  return min($chr);
}


function getURLSegments($p = 1) {
  $_SERVER['REQUEST_URI_PATH'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $segments = explode('/', $_SERVER['REQUEST_URI_PATH']);
  return (!empty($segments[$p])) ? $segments[$p] : false;
}


/**
 * get_calling_class()
 * http://stackoverflow.com/questions/3620923/how-to-get-the-name-of-the-calling-class-in-php
 * @return type
 */
function get_calling_class() {
  //get the trace
  $trace = debug_backtrace();
  // Get the class that is asking for who awoke it
  $class = $trace[1]['class'];
  // +1 to i cos we have to account for calling this function
  for ($i = 1; $i < count($trace); $i++) {
    if (isset($trace[$i])) // is it set?
      if ($class != $trace[$i]['class']) // is it a different class
        return $trace[$i]['class'];
  }
}


/**
 * getNowDateTime
 * @return false|string
 */
function getNowDateTime() {
  return date("Y-m-d H:i:s");
}


/**
 * removeEmoji
 * @param $text
 * @return mixed
 */
function removeEmoji($text) {

  // Match Emoticons
  $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
  $clean_text = preg_replace($regexEmoticons, '', $text);

  // Match Miscellaneous Symbols and Pictographs
  $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
  $clean_text = preg_replace($regexSymbols, '', $clean_text);

  // Match Transport And Map Symbols
  $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
  $clean_text = preg_replace($regexTransport, '', $clean_text);

  return $clean_text;
}


/**
 * @param $search
 * @param $replace
 * @param $subject
 * @return mixed
 *
 * http://stackoverflow.com/questions/3835636/php-replace-last-occurence-of-a-string-in-a-string
 */
function str_lreplace($search, $replace, $subject) {
  $pos = strrpos($subject, $search);

  if ($pos !== false) {
    $subject = substr_replace($subject, $replace, $pos, strlen($search));
  }

  return $subject;
}


/**
 *
 * @param $text
 * @return array
 */
function tokenize($text) {
  $split = preg_split("/[^\w]*([\s]+[^\w]*|$)/", $text, -1, PREG_SPLIT_NO_EMPTY);
  return $split;
}


/**
 *
 * dal vecchio wozot
 * TODO: bisogna verificare se restituisce un array on un singolo url
 *
 * @param $text
 * @return bool
 */
function getUrlInText($text) {
  // The Regular Expression filter
  $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
  // Check if there is a url in the text
  if (preg_match($reg_exUrl, $text, $url)) {
    // make the urls hyper links
    return $url[0];
  } else {
    // if no urls in the text just return the text
    return false;
  }
}


/**
 * @param $url
 * @return bool
 */
function isValidUrl($url) {
  if (filter_var($url, FILTER_VALIDATE_URL) === FALSE)
    return false;
  else
    return true;
}


/**
 * getUserIP()
 * @return null
 * @credits http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
 */
function getUserIP() {
  $ip = null;
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
return $ip;
}




