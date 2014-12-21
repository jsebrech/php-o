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

  /**
   * Find the position of the first occurrence of a substring in a string
   * @param string $needle The string to search
   * @param int $offset The position to start searching
   * @return int
   */
  function pos($needle, $offset = 0) {
    return mb_strpos($this->s, $needle, $offset);
  }

  /**
   * Find the position of the first occurrence of a case-insensitive substring in a string
   * @param string $needle The string to search
   * @param int $offset The position to start searching
   * @return int
   */
  function ipos($needle, $offset = 0) {
    return mb_stripos($this->s, $needle, $offset);
  }

  /**
   * Find the position of the last occurrence of a substring in a string
   * @param string $needle The string to search
   * @param int $offset The position to start searching
   * @return int
   */
  function rpos($needle, $offset = 0) {
    return mb_strrpos($this->s, $needle, $offset);
  }

  /**
   * Find the position of the last occurrence of a case-insensitive substring in a string
   * @param string $needle The string to search
   * @param int $offset The position to start searching
   * @return int
   */
  function ripos($needle, $offset = 0) {
    return mb_strripos($this->s, $needle, $offset);
  }

  /**
   * Split a string by string
   * @param string $delimiter The boundary string
   * @param int $limit If limit is set and positive, the returned array will contain
   * a maximum of limit elements with the last element containing the rest of string.
   * @return array|ArrayClass
   */
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

  /**
   * Strip whitespace (or other characters) from the beginning and end of a string
   * @param string $charlist Characters to strip
   * @return string|StringClass
   */
  function trim($charlist = " \t\n\r\0\x0B") {
    return trim($this->s, $charlist);
  }

  /**
   * Strip whitespace (or other characters) from the beginning of a string
   * @param string $charlist Characters to strip
   * @return string|StringClass
   */
  function ltrim($charlist = " \t\n\r\0\x0B") {
    return ltrim($this->s, $charlist);
  }

  /**
   * Strip whitespace (or other characters) from the end of a string
   * @param string $charlist Characters to strip
   * @return string|StringClass
   */
  function rtrim($charlist = " \t\n\r\0\x0B") {
    return rtrim($this->s, $charlist);
  }

  /**
   * Pad a string to a certain length with another string
   * @param int $padLength Length in characters to pad to
   * @param string $padString String to pad with
   * @param int $padType STR_PAD_LEFT, STR_PAD_RIGHT (default) or STR_PAD_BOTH
   * @return string|StringClass
   */
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

  /**
   * Get the string length in characters
   * @return int
   */
  function len() {
    return mb_strlen($this->s);
  }

  /**
   * Make a string lowercase
   * @return string|StringClass
   */
  function tolower() {
    return mb_strtolower($this->s);
  }

  /**
   * Make a string uppercase
   * @return string|StringClass
   */
  function toupper() {
    return mb_strtoupper($this->s);
  }

  /**
   * Return part of a string
   * @param int $start If negative, counts from the end of the string
   * @param int $length
   * @return string|StringClass
   */
  function substr($start = 0, $length = 0xFFFFFFF) {
    return mb_substr($this->s, $start, $length);
  }

  /**
   * Replace all occurrences of the search string with the replacement string
   * @param string $search The value being searched for
   * @param string $replace The replacement value
   * @param int $count If set, the number of replacements performed
   * @return string|StringClass
   */
  function replace($search, $replace, &$count = NULL) {
    return str_replace($search, $replace, $this->s, $count);
  }

  /**
   * Replace all occurrences of the search string (case-insensitive) with the replacement string
   * @param string $search The value being searched for
   * @param string $replace The replacement value
   * @param int $count If set, the number of replacements performed
   * @return string|StringClass
   */
  function ireplace($search, $replace, &$count = NULL) {
    return str_ireplace($search, $replace, $this->s, $count);
  }

  /**
   * Perform a regular expression match
   * @param string $pattern The pattern to search for
   * @param array $matches If provided it is filled with the results of the search
   * $matches[0] will contain the text that matched the full pattern,
   * $matches[1] will have the text that matched the first captured parenthesized subpattern, and so on.
   * @param int $flags If PREG_OFFSET_CAPTURE for every occurring match the appendant string offset will also be returned.
   * Note that this changes the value of matches into an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
   * @param int $offset Alternate start of the search (in characters)
   * @return int
   */
  function preg_match($pattern, &$matches = NULL, $flags = 0, $offset = 0) {
    if (!is_array($matches)) $matches = array();
    // convert offset from characters to bytes
    if ($offset) $offset = strlen($this->substr(0, $offset));
    $result = preg_match($pattern, $this->s, $matches, $flags, $offset);
    if ($flags & PREG_OFFSET_CAPTURE) {
      foreach ($matches as &$match) {
        // convert offset in bytes into offset in code points
        $match[1] = mb_strlen(substr($this->s, 0, $match[1]));
      }
    };
    return $result;
  }

  /**
   * Searches subject for all matches to the regular expression given in pattern and puts them in matches in the order specified by flags.
   * @param string $pattern The pattern to search for
   * @param array $matches If provided it is filled with the results of the search
   * @param int $flags {@see http://php.net/manual/en/function.preg-match-all.php}
   * @param int $offset Alternate start of the search (in characters)
   * @return int
   */
  function preg_match_all($pattern, &$matches = NULL, $flags = PREG_PATTERN_ORDER, $offset = 0) {
    if (!is_array($matches)) $matches = array();
    // convert offset from characters to bytes
    if ($offset) $offset = strlen($this->substr(0, $offset));
    $result = preg_match_all($pattern, $this->s, $matches, $flags, $offset);
    if ($flags & PREG_OFFSET_CAPTURE) {
      foreach ($matches as &$group) {
        foreach ($group as &$match) {
          // convert offset in bytes into offset in code points
          $match[1] = mb_strlen(substr($this->s, 0, $match[1]));
        };
      };
    };
    return $result;
  }

  /**
   * Perform a regular expression search and replace
   * @param string|array $pattern The pattern(s) to search for
   * @param string|array $replacement The string(s) to replace with
   * Each element from $pattern is replaced with its counterpart from $replacement
   * @param int $limit The maximal number of replacements
   * @param int $count If specified it is filled with the number of replacements done
   * @return string|StringClass
   */
  function preg_replace($pattern , $replacement , $limit = -1, &$count = NULL) {
    return preg_replace($pattern, $replacement, $this->s, $limit, $count);
  }

  /**
   * Checks if a value exists in an array
   * @param array|ArrayClass $haystack
   * @return bool
   */
  function in_array($haystack) {
    if (!is_array($haystack) && ($haystack instanceof ArrayClass)) {
      $haystack = $haystack->raw();
    }
    return in_array($this->s, $haystack);
  }

// JavaScript-style API

  /**
   * Returns the specified character from a string.
   * @param int $index
   * @return string|StringClass
   */
  function charAt($index) {
    return $this->substr($index, 1);
  }

  /**
   * Returns the index of the first occurrence of the specified value,
   * starting the search at fromIndex. Returns -1 if the value is not found.
   * @param string $search
   * @param int $start
   * @return int
   */
  function indexOf($search, $start = 0) {
    $pos = s($this->substr($start))->pos($search);
    return ($pos === FALSE) ? -1 : $pos+$start;
  }

  /**
   * Returns the index of the last occurrence of the specified value,
   * starting the search at fromIndex. Returns -1 if the value is not found.
   * @param string $search
   * @param int $start
   * @return int
   */
  function lastIndexOf($search, $start = 0) {
    $pos = s($this->substr(0, $start))->rpos($search);
    return ($pos === FALSE) ? -1 : $pos;
  }

  /**
   * Retrieves the matches when matching a string against a regular expression.
   * @param string $regexp
   * @return array|ArrayClass|null
   */
  function match($regexp) {
    $matches = array();
    if ($this->preg_match($regexp, $matches)) {
      return $matches;
    };
    return NULL;
  }

  // replace() already implemented for PHP syntax

  /**
   * Splits the string into an array of strings
   * @param string $separator
   * @param int $limit
   * @return array|ArrayClass
   */
  function split($separator = NULL, $limit = 0xFFFFFF) {
    if ($separator === NULL) return array($this->s);
    return $this->explode($separator, $limit);
  }

  // substr() already implemented for PHP syntax

  /**
   * Returns a subset of a string between one index and another,
   * or through the end of the string.
   * @param int $start
   * @param int $end
   * @return string|StringClass
   */
  function substring($start, $end = NULL) {
    return $this->substr($start, ($end !== NULL) ? $end-$start : 0xFFFFFFF);
  }

  /**
   * Convert to lowercase
   * @return string|StringClass
   */
  function toLowerCase() {
    return $this->tolower();
  }

  /**
   * Convert to uppercase
   * @return string|StringClass
   */
  function toUpperCase() {
    return $this->toupper();
  }

  // trim() already implemented for PHP syntax

  /**
   * Removes whitespace from the left end of a string
   * @return string|StringClass
   */
  function trimLeft() {
    return $this->ltrim();
  }

  /**
   * Removes whitespace from the right end of a string
   * @return string|StringClass
   */
  function trimRight() {
    return $this->rtrim();
  }

  /**
   * Return the internal raw string value
   * @return string|StringClass
   */
  function valueOf() {
    return $this->s;
  }

// encoder functions

  /**
   * Securely encode the string for the html element context
   * {@see https://www.owasp.org/index.php/Abridged_XSS_Prevention_Cheat_Sheet}
   * @return string|StringClass
   */
  function html() {
    $s = htmlspecialchars($this->s, ENT_QUOTES, "UTF-8");
    $s = s($s)->replace("/", "&#x2F;");
    $s = s($s)->replace("&apos;", "&#039;");
    return $s;
  }

  /**
   * Securely encode the string for the script element context
   * {@see https://www.owasp.org/index.php/Abridged_XSS_Prevention_Cheat_Sheet}
   * @return string|StringClass
   */
  function script() {
    return json_encode($this->s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  }

  /**
   * Securely encode the string for the JSON context
   * {@see https://www.owasp.org/index.php/Abridged_XSS_Prevention_Cheat_Sheet}
   * @return string|StringClass
   */
  function json() {
    return $this->script();
  }

// IteratorAggregate

  /**
   * @return \ArrayIterator|\Traversable
   */
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
   * {@see http://www.icosaedro.it/phplint/phpdoc.html#types}
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

  /**
   * Set this string object to the empty string
   * @return string|StringClass
   */
  function clear() {
    return $this->s = "";
  }

  /**
   * Return the internal primitive string value
   * @return string
   */
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
