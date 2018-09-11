<?php

namespace App\Core\Inner\Storage;
use App\Core\Inner\Cyml;

class Storage {

  function get($file) {
    $rFile = fopen($this->path . $file, "r") or die("Unable to open file!");
    $res = fread($rFile, filesize($file));
    fclose($rFile);
    return $res;
  }

  function getYml($file) {
    $ymlClass = new cyml();
    return $ymlClass->parse($this->path . $file);
  }


}
