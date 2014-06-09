<?php

namespace O;

// verify that output and string handling occurs as UTF-8
if (!extension_loaded("mbstring")) {
  throw new \Exception("enable the mbstring extension in php.ini");
} else if (headers_sent()) {
  throw new \Exception("headers already sent, load O.php at the top of the page");
} else {
  ini_set("default_charset", "UTF-8");
  mb_internal_encoding("UTF-8");
};

/**
 * Supporting class for the s() function
 */
class StringClass implements \IteratorAggregate, \ArrayAccess {
  private $s;

  function __construct($s) {
    $this->s = $s;
  }

  function __toString() {
    return strval($this->s);
  }

// PHP style API

  function pos($needle) {
    return mb_strpos($this->s, $needle);
  }
  function ipos($needle) {
    return mb_stripos($this->s, $needle);
  }
  function rpos($needle) {
    return mb_strrpos($this->s, $needle);
  }
  function ripos($needle) {
    return mb_strripos($this->s, $needle);
  }
  function explode($delimiter, $limit = 0xFFFFFF) {
    // split in utf-8 characters
    if ($delimiter == "") {
      $l = min($this->len(), $limit);
      $r = array();
      for ($i = 0; $i < $l; $i++) {
        $r[] = $this->substr($i, 1);
      };
      return $r;
    } else {
      return explode($delimiter, $this->s, $limit);
    }
  }
  function trim($charlist = " \t\n\r\0\x0B") {
    return trim($this->s, $charlist);
  }
  function ltrim($charlist = " \t\n\r\0\x0B") {
    return ltrim($this->s, $charlist);
  }
  function rtrim($charlist = " \t\n\r\0\x0B") {
    return rtrim($this->s, $charlist);
  }
  function pad($padLength, $padString = " ", $padType = STR_PAD_RIGHT) {
    // padLength == byte length, so calculate it correctly
    $padLength += (strlen($this->s) - $this->len());
    $padStringByteToCharRatio = strlen($padString) / mb_strlen($padString);
    if ($padStringByteToCharRatio > 1) {
      $charsToAdd = ($padLength - strlen($this->s));
      $padLength -= $charsToAdd;
      $padLength += ceil($charsToAdd * $padStringByteToCharRatio);
    };
    return str_pad($this->s, $padLength, $padString, $padType);
  }
  function len() {
    return mb_strlen($this->s);
  }
  function tolower() {
    return mb_strtolower($this->s);
  }
  function toupper() {
    return mb_strtoupper($this->s);
  }
  function substr($start = 0, $length = 0xFFFFFFF) {
    return mb_substr($this->s, $start, $length);
  }
  function replace($search, $replace, &$count = NULL) {
    return str_replace($search, $replace, $this->s, $count);
  }
  function ireplace($search, $replace, &$count = NULL) {
    return str_ireplace($search, $replace, $this->s, $count);
  }
  function preg_match($pattern, &$matches = NULL, $flags = 0, $offset = 0) {
    if (!is_array($matches)) $matches = array();
    return preg_match($pattern, $this->s, $matches, $flags, $offset);
  }
  function preg_match_all($pattern, &$matches = NULL, $flags = PREG_PATTERN_ORDER, $offset = 0) {
    if (!is_array($matches)) $matches = array();
    return preg_match_all($pattern, $this->s, $matches, $flags, $offset);
  }
  function preg_replace($pattern , $replacement , $limit = -1, &$count = NULL) {
    return preg_replace($pattern, $replacement, $this->s, $limit, $count);
  }
  function in_array($haystack) {
    return in_array($this->s, $haystack);
  }

// JavaScript-style API

  function charAt($index) {
    return $this->substr($index, 1);
  }
  function indexOf($search, $start = 0) {
    $pos = s($this->substr($start))->pos($search);
    return ($pos === FALSE) ? -1 : $pos+$start;
  }
  function lastIndexOf($search, $start = 0) {
    $pos = s($this->substr(0, $start))->rpos($search);
    return ($pos === FALSE) ? -1 : $pos;
  }
  function match($regexp) {
    $matches = array();
    if ($this->preg_match($regexp, $matches)) {
      return $matches;
    };
    return NULL;
  }
  // replace() already implemented for PHP syntax
  function split($separator = NULL, $limit = 0xFFFFFF) {
    if ($separator === NULL) return array($this->s);
    return $this->explode($separator, $limit);
  }
  // substr() already implemented for PHP syntax
  function substring($start, $end = NULL) {
    return $this->substr($start, ($end !== NULL) ? $end-$start : 0xFFFFFFF);
  }
  function toLowerCase() {
    return $this->tolower();
  }
  function toUpperCase() {
    return $this->toupper();
  }
  // trim() already implemented for PHP syntax
  function trimLeft() {
    return $this->ltrim();
  }
  function trimRight() {
    return $this->rtrim();
  }
  function valueOf() {
    return $this->s;
  }

// encoder functions

  // secure encode for html element context
  // see https://www.owasp.org/index.php/Abridged_XSS_Prevention_Cheat_Sheet
  function html() {
    $s = htmlspecialchars($this->s, ENT_QUOTES, "UTF-8");
    $s = s($s)->replace("/", "&#x2F;");
    $s = s($s)->replace("&apos;", "&#039;");
    return $s;
  }

  // secure encode for <script> context
  function script() {
    return json_encode($this->s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  }

  // secure encode for JSON context
  function json() {
    return $this->script();
  }

// IteratorAggregate

  function getIterator() {
    $o = new \ArrayObject($this->explode(""));
    return $o->getIterator();
  }

// ArrayAccess

  function offsetExists($offset) {
    return $offset < $this->len();
  }

  function offsetGet($offset) {
    return $this->substr($offset, 1);
  }

  function offsetSet($offset, $value) {
    $char = s($value)->substr(0, 1);
    $this->s = $this->substr(0, $offset) . $char . $this->substr($offset + 1);
  }

  function offsetUnset($offset) {
    $this->s = $this->substr(0, $offset);
  }

// other methods

  /**
   * parse type string (phplint / phpdoc syntax)
   * http://www.icosaedro.it/phplint/phpdoc.html#types
   * @return \O\VariableType
   */
  function parse_type() {
    $type = $this->s;
    $matches = array();
    $isArray = FALSE;
    $keyType = NULL;
    // array[keytype]type
    if (s($type)->preg_match("/array(?:\\[([\S]*)\\]([\S]*))?/", $matches)) {
      $isArray = TRUE;
      $keyType = $matches[1];
      $type = $matches[2];
      // type[]
    } else if (s($type)->preg_match("/([^\\[]+)\\[\\]/", $matches)) {
      $isArray = TRUE;
      $keyType = NULL;
      $type = $matches[1];
    } else if ($type == "array") {
      $isArray = TRUE;
      $keyType = NULL;
      $type = "mixed";
    };
    $validTypes = array(
      "void",
      "bool", "boolean",
      "int", "integer", "float", "double",
      "string", "resource", "object", "mixed");
    if (!s($keyType)->in_array($validTypes)) {
      if (empty($keyType) || !class_exists($keyType)) {
        $keyType = NULL;
      };
    };
    if (!s($type)->in_array($validTypes)) {
      if (empty($type) || !class_exists($type)) {
        $type = "mixed";
      };
    };
    return new VariableType($isArray, $keyType, $type);
  }

  function clear() {
    return $this->s = "";
  }

  function raw() {
    return $this->s;
  }

}

class VariableType {
  /** @var bool */
  public $isArray = FALSE;
  /** @var string */
  public $key = "void";
  /** @var string */
  public $value = "void";

  public function __construct($isArray = FALSE, $key = "void", $value = "void") {
    $this->isArray = $isArray;
    $this->key = $key;
    $this->value = $value;
  }
}

/**
 * @param $p string
 * @return \O\StringClass
 */
function s($p) {
  if ($p instanceof StringClass) {
    return $p;
  } else {
    return new StringClass($p);
  }
}
