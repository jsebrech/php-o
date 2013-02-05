<?php

namespace O;

//-----------------------------------------------------------------------------
// set up environment + session handling logic
//-----------------------------------------------------------------------------

// verify that output and string handling occurs as UTF-8
if (!extension_loaded("mbstring")) {
  echo "enable the mbstring extension in php.ini".PHP_EOL;
  exit;
} else if (headers_sent()) {
  echo "headers already sent, load O.php at the top of the page".PHP_EOL;
  exit;
} else {
  ini_set("default_charset", "UTF-8");
  mb_internal_encoding("UTF-8");
};

// verify that magic quotes are disabled
if (get_magic_quotes_gpc()) {
   echo "disable magic_quotes_gpc in php.ini".PHP_EOL;
   exit;
}

// verify that session settings are secure
if (session_id()) {
  echo "session must be opened after loading O.php".PHP_EOL;
  if (ini_get("session.auto_start")) {
    echo "disable session.auto_start in php.ini".PHP_EOL;
  };
  exit;
} else {
  // javascript shouldn't be able to see the session cookie
  ini_set("session.cookie_httponly", "1");
  // url's should never contain session id's
  ini_set("session.use_trans_sid", "0");
  ini_set("session.use_only_cookies", "1"); 
  if (!empty($_SERVER["HTTPS"])) {
    // a good idea to set this in php.ini
    ini_set("session.cookie_secure", "1");
  };
  // security by obscurity, but there's no downside here
  session_name("OSID");
};
// secure session_start function (overrides built-in)
function session_start() {
  \session_start();
  // rotate session id on first request in session
  if (!isset($_SESSION["__O_SESSION_VALIDATED"])) {
    session_regenerate_id(true);
    $_SESSION["__O_SESSION_VALIDATED"] = TRUE;
  };
  // generate an anti-CSRF token
  if (!isset($_SESSION["__O_ANTI_CSRF_TOKEN"])) {
    $_SESSION["__O_ANTI_CSRF_TOKEN"] = md5(uniqid());
  }; 
};
// obtain the anti-CSRF token
function get_csrf_token() {
  if (!session_id()) session_start();
  return $_SESSION["__O_ANTI_CSRF_TOKEN"];
};
// check that CSRF token was given
function is_csrf_protected($token = "") {
  if (empty($token) && isset($_REQUEST["csrftoken"])) {
    $token = $_REQUEST["csrftoken"];
  };
  return $token === get_csrf_token();
};

class Session {
  function __construct() {
    if (!session_id()) session_start();
  }

  function getCSRFToken() {
    return get_csrf_token();
  }

  function isCSRFProtected($token = "") {
    return is_csrf_protected($token);
  }

  function &__get($prop) {
    if (isset($_SESSION[$prop])) {
      return $_SESSION[$prop];
    } else {
      $null = NULL;
      return $null; // must return reference to variable
    }
  }

  function __set($prop, $value) {
    return $_SESSION[$prop] = $value;
  }

  function __isset($prop) {
    return isset($_SESSION[$prop]);
  }

  function __unset($prop) {
    unset($_SESSION[$prop]);
  }
}
//-----------------------------------------------------------------------------
// string and array API's
//-----------------------------------------------------------------------------

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
  
  function css() {
    // TODO: encode for css context
    return $this->s;
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
  if ($p instanceof \O\StringClass) {
    return $p;
  } else {
    return new \O\StringClass($p);
  }
}

//-----------------------------------------------------------------------------

class ArrayClass implements \IteratorAggregate, \ArrayAccess {
  private $a;
  
  function __construct(&$a) {
    $this->a =& $a;
  }
  
  function count() {
    return count($this->a);
  }
  
  function has($needle, $strict = FALSE) {
    return in_array($needle, $this->a, $strict);
  }
  
  function search($needle, $strict = FALSE) {
    return array_search($needle, $this->a, $strict);
  }
  
  function shift() {
    return array_shift($this->a);
  }
  
  function unshift() {
    $args = func_get_args();
    for ($i = count($args) - 1; $i >= 0; $i--) {
      array_unshift($this->a, $args[$i]);
    };
    return count($this->a);
  }
  
  function key_exists($key) {
    return array_key_exists($key, $this->a);
  }
  
  function implode($glue = "") {
    return implode($this->a, $glue);
  }
  
  function keys() {
    return array_keys($this->a);
  }
  
  function values() {
    return array_values($this->a);
  }
  
  function pop() {
    return array_pop($this->a);
  }

  function push() {
    $args = func_get_args();
    for ($i = 0; $i < count($args); $i++) {
      array_push($this->a, $args[$i]);
    };
    return count($this->a);
  }
  
  function slice($offset, $length = NULL, $preserve_keys = false) {
    return array_slice($this->a, $offset, $length, $preserve_keys);
  } 
  
  function splice($offset, $length = 0, $replacement = NULL) {
    if ($replacement == NULL) $replacement = array();
    return array_splice($this->a, $offset, $length, $replacement);
  }
  
  function merge() {
    return call_user_func_array("array_merge", array_merge(array($this->a), func_get_args()));
  }
  
  function map($callback) {
    $params = a(func_get_args())->slice(1);
    a($params)->unshift($callback, $this->a);
    return call_user_func_array("array_map", $params);
  }
  
  function reduce($callable, $initial = NULL) {
    return array_reduce($this->a, $callable, $initial);
  }
  
  function filter($callable = "") {
    return array_filter($this->a, $callable);
  }
  
  function sum() {
    return array_sum($this->a);
  }
  
  function raw() {
    return $this->a;
  }

// IteratorAggregate
  
  function getIterator() {
    $o = new \ArrayObject($this->a);
    return $o->getIterator();
  }

// ArrayAccess

// ArrayAccess

  function offsetExists($offset) {
    return isset($this->a[$offset]);
  }

  function offsetGet($offset) {
    return $this->a[$offset];
  }

  function offsetSet($offset, $value) {
    $this->a[$offset] = $value;
  }

  function offsetUnset($offset) {
    unset($this->a[$offset]);
  }

}

/**
 * @param string $p
 * @return \O\ArrayClass
 */
function a(&$p) {
  if ($p instanceof \O\ArrayClass) {
    return $p;
  } else {
    return new \O\ArrayClass($p);
  }
}

//-----------------------------------------------------------------------------

class ObjectClass implements \IteratorAggregate, \ArrayAccess
{
  private $o;

  function __construct($o) {
    if (is_string($o)) $o = json_decode($o);
    $this->o = (object) $o;
  }
  
  function __toString() {
    return json_encode($this->o);
  }
  
  function __call($fn, $args) {
    if (method_exists($this->o, $fn)) {
      return call_user_func_array(array($this->o, $fn), $args);
    } else if (isset($this->o->$fn)) {
      return call_user_func_array($this->o->$fn, $args);
    } else return NULL;
  }
  
  function __get($prop) {
    return $this->o->$prop;
  }
  
  function __set($prop, $value) {
    return $this->o->$prop = $value;
  }
  
  function __isset($prop) {
    return isset($this->o->$prop);
  }
  
  function __unset($prop) {
    unset($this->o->$prop);
  }
  
  function cast($asType = "stdClass") {
    if ($asType == "stdClass") {
      return $this->o;
    } else {
      if (!class_exists($asType)) $asType = "O\\".$asType;
      if (class_exists($asType)) {
        if (is_object($this->o)) {
          $a = (array) $this->o;
          $refl = new \O\ReflectionClass($asType);
          $props = $refl->getProperties(
            ReflectionProperty::IS_STATIC|ReflectionProperty::IS_PUBLIC);
          $result = new $asType();
          // convert properties to the right type    
          foreach ($props as $prop) {
            $propName = $prop->getName();
            if (isset($a[$propName])) {
              $result->$propName =
                convertType($a[$propName], $prop->getType());
            };
          };
          return $result;
        } else {
          return NULL;
        }
      } else {
        throw new \Exception("Unrecognized type: ".$asType);
      }
    }
  }
  
  function raw() {
    return $this->o;
  }

  function render($template) {
    extract((array) $this->o);
    /** @noinspection PhpIncludeInspection */
    include $template;
  }

// IteratorAggregate
  
  function getIterator() {
    $o = new \ArrayObject($this->o);
    return $o->getIterator();
  }

// ArrayAccess

  function offsetExists($offset) {
    return isset($this->o[$offset]);
  }

  function offsetGet($offset) {
    return $this->o[$offset];
  }

  function offsetSet($offset, $value) {
    $this->o[$offset] = $value;
  }

  function offsetUnset($offset) {
    unset($this->o[$offset]);
  }

}

/**
 * @param mixed $p
 * @return \O\ObjectClass
 */
function o($p) {
  if ($p instanceof \O\ObjectClass) {
    return $p;
  } else {
    return new \O\ObjectClass($p);
  }
}

// supports types from phplint/phpdoc
// http://www.icosaedro.it/phplint/phpdoc.html#types
function convertType($value, $type) {
  $type = s($type)->parse_type();
  if ($type->isArray) {
    if (is_array($value)) {
      $newVal = array();
      foreach ($value as $key => $item) {
        if ($type->key !== NULL) {
          $newVal[convertType($key, $type->key)] =
            convertType($item, $type->value);
        } else {
          $newVal[] = convertType($item, $type->value);
        };
      };
      return $newVal;
    };
  } else {
    switch ($type->value) {
      case "void": return NULL;
      case "bool": case "boolean": return (bool) $value;
      case "FALSE": case "false": return FALSE;
      case "int": case "integer": return intval($value);
      case "float": case "double": return floatval($value);
      case "string": return strval($value);
      case "mixed": return $value;
      case "resource": return is_resource($value) ? $value : NULL;
      case "object": return is_object($value) ? $value : o($value)->cast();
      default: return o($value)->cast($type->value);
    }
  };
  return NULL;
}
//-----------------------------------------------------------------------------
// Chainable, allows chaining methods together
//-----------------------------------------------------------------------------

class ChainableClass implements \IteratorAggregate, \ArrayAccess
{
  private $o;

  function __construct($o) {
    $this->o = $o;
  }
  
  function __toString() {
    return (string) $this->o;
  }

  private static function asChainable($p) {
    switch (gettype($p)) {
      case "string":
        return cs($p);
      case "array":
        return ca($p);
      case "object":
        return co($p);
      default:
        if (is_object($p)) {
          return c($p);
        } else {
          return $p;
        }
    }
  }

  /**
   * @param string $fn
   * @param array $args
   * @return mixed|\O\ChainableClass
   */
  function __call($fn, $args) {
    return self::asChainable(call_user_func_array(array($this->o, $fn), $args));
  }

  function raw() {
    if (is_object($this->o) && method_exists($this->o, "raw")) {
      return call_user_func(array($this->o, "raw"));
    } else {
      return $this->o;
    }
  }

// IteratorAggregate

  /**
   * @return \Traversable
   */
  function getIterator() {
    if (method_exists($this->o, "getIterator")) {
      return call_user_func(array($this->o, "getIterator"));
    } else {
      return NULL;
    }
  }

// ArrayAccess

  function offsetExists($offset) {
    return isset($this->o[$offset]);
  }

  function offsetGet($offset) {
    return self::asChainable($this->o[$offset]);
  }

  function offsetSet($offset, $value) {
    $this->o[$offset] = $value;
  }

  function offsetUnset($offset) {
    unset($this->o[$offset]);
  }

}

/**
 * @param mixed $o
 * @return \O\ChainableClass
 */
function c($o) {
  if ($o instanceof ChainableClass) {
    return $o;
  } else {
    return new ChainableClass($o);
  }
}

/**
 * @param string $o
 * @return \O\ChainableClass
 */
function cs($o) {
  return c(s($o));
}

/**
 * @param array $o
 * @return \O\ChainableClass
 */
function ca($o) {
  return c(a($o));
}

/**
 * @param mixed $o
 * @return \O\ChainableClass
 */
function co($o) {
  return c(o($o));
}

//-----------------------------------------------------------------------------
// Reflection classes with type hinting and extended docblock parsing
//-----------------------------------------------------------------------------

class ReflectionClass extends \ReflectionClass
{
  /**
   * @param int $filter
   * @return \O\ReflectionMethod[]
   */
  public function getMethods($filter = NULL) {
    $methods = parent::getMethods($filter);
    foreach ($methods as $index => $method) {
      $methods[$index] = new ReflectionMethod(
        $this->getName(), $method->getName());
    };
    return $methods;
  }

  /**
   * @param string $name
   * @return \O\ReflectionMethod
   */
  public function getMethod($name) {
    return new \O\ReflectionMethod($this->getName(), $name);
  }


  /**
   * @param int $filter
   * @return \O\ReflectionProperty[]
   */
  public function getProperties($filter = NULL) {
    if ($filter === NULL) {
      $filter = 
        ReflectionProperty::IS_STATIC |
        ReflectionProperty::IS_PUBLIC |
        ReflectionProperty::IS_PROTECTED |
        ReflectionProperty::IS_PRIVATE;
    };
    $properties = parent::getProperties($filter);
    foreach ($properties as $index => $property) {
      $properties[$index] = new ReflectionProperty(
        $this->getName(), $property->getName());
    };
    return $properties;
  }

  /**
   * @param string $name
   * @return \O\ReflectionProperty
   */
  public function getProperty($name) {
    return new \O\ReflectionProperty($this->getName(), $name);
  }

  /**
   * @param bool $onlytext
   * @return string
   */
  public function getDocComment($onlytext = FALSE) {
    $doc = parent::getDocComment();
    if ($onlytext) {
      $doc = s($doc)->preg_replace("/(?<=[\r\n])[\\s]*\*(\ )?(?![\/])/", "");
      $doc = s($doc)->preg_replace("/^[\\s]*\/\*\*[\\s]*[\r\n]*/", "");
      $doc = s($doc)->preg_replace("/[\r\n]*[\\s]*\*\/$/", "");
    };
    return (string) $doc;
  }
}

class ReflectionProperty extends \ReflectionProperty 
{
  public function getDocComment($onlytext = FALSE) {
    $doc = parent::getDocComment();
    if ($onlytext) {
      $doc = s($doc)->preg_replace("/(?<=[\r\n])[\\s]*\*(\ )?(?![\/])/", "");
      $doc = s($doc)->preg_replace("/^[\\s]*\/\*\*[\\s]*[\r\n]*/", "");
      $doc = s($doc)->preg_replace("/[\r\n]*[\\s]*\*\/$/", "");
    };
    return (string) $doc;
  }
  
  public function getType() {
    $doc = $this->getDocComment();
    $matches = array();
    $pattern = "/\@var[\\s]+([\\S]+)/";
    if (s($doc)->preg_match($pattern, $matches)) {
      return $matches[1];
    } else {
      return NULL;
    }
  }
}

class ReflectionMethod extends \ReflectionMethod
{
  public function getDocComment($onlytext = FALSE) {
    $doc = parent::getDocComment();
    if ($onlytext) {
      $doc = s($doc)->preg_replace("/(?<=[\r\n])[\\s]*\*(\ )?(?![\/])/", "");
      $doc = s($doc)->preg_replace("/^[\\s]*\/\*\*[\\s]*[\r\n]*/", "");
      $doc = s($doc)->preg_replace("/[\r\n]*[\\s]*\*\/$/", "");
    };
    return (string) $doc;
  }

  public function getDeclaringClass() {
    return new \O\ReflectionClass(parent::getDeclaringClass()->getName());
  }
  
  public function getParameters() {
    $params = parent::getParameters();
    foreach ($params as $index => $param) {
      $params[$index] = new \O\ReflectionParameter(
        array($this->getDeclaringClass()->getName(), $this->getName()), 
        $param->getName());
    };
    return $params;
  }
  
  public function getParameter($name) {
    return new ReflectionParameter(
      array($this->getDeclaringClass()->getName(), $this->getName()),
      $name);
  }
}

class ReflectionParameter extends \ReflectionParameter
{
  public function getDocComment() {
    $methoddoc = $this->getDeclaringFunction()->getDocComment(TRUE);
    $parts = s($methoddoc)->explode("@param");
    for ($i = 1; $i < count($parts); $i++) $parts[$i] = "@param".$parts[$i];
    a($parts)->shift();
    $filter = "/\@param[^\\$]+\\$".$this->getName()."(?![\\w])/";
    foreach ($parts as $part) {
      if (s($part)->preg_match($filter)) {
        return $part;
      };
    };
    return "";
  }
  
  public function getDeclaringFunction() {
    $f = parent::getDeclaringFunction();
    /** @var $f ReflectionMethod */
    if (is_a($f, "ReflectionMethod")) {
      return new \O\ReflectionMethod($f->getDeclaringClass()->getName(), $f->getName());
    } else {
      return $f;
    }
  }
  
}

//-----------------------------------------------------------------------------
// PHP implementation of JSR-303 (object validation via annotation)
// Usage: O\Validator::validate($obj)
//-----------------------------------------------------------------------------

class Validator
{
  /**
   * @var string $doc comment of a parameter or property
   * @return array
   */
  static function getAnnotations($doc) {
    $matches = array();
    s($doc)->preg_match_all("/\@([\\w]+)(?:\(([^)]+)\))?/", $matches, PREG_SET_ORDER);    
    $annotations = array();
    foreach($matches as $match) {
      if (!s($match[1])->in_array(array("var", "param"))) {
        if (count($match) == 2) {
          $annotations[$match[1]] = TRUE;
        } else if (count($match) == 3) {
          // example: @Min(30)
          if (s($match[2])->pos("=") === FALSE) {
            $annotations[$match[1]] = trim($match[2]);
          } else {
            // example: @Size(min=10, max=30)
            $variables = array();
            $pairs = s($match[2])->explode(",");
            foreach ($pairs as $pair)
            {
              $parts = s($pair)->explode("=");
              if (count($parts) == 2)
              {
                $variables[trim($parts[0])] = trim($parts[1]);
              };
            };
            if (count($variables) > 0) {
              $annotations[$match[1]] = $variables;
            };
          };
        };
      };
    };
    return $annotations;
  }
  
  /**
   * Validate a property value according to the rules on its comment
   *  
   * Note: for array types (e.g. int[]) the validation is applied to each element,
   * but only if it cannot be applied to the array as a whole. For example,
   * @Min can be used on an int[] to validate each element,
   * but @Size cannot be used on a string[] except to validate the array's size.
   * @Valid will validate an object property recursively, 
   * or validate each element in an object[].
   * 
   * @param string|\O\ReflectionClass $class
   * @param string|\O\ReflectionProperty $property
   * @param mixed $value
   * @return \O\ConstraintViolation[]
   */
  static function validateValue($class, $property, $value) {
    // TODO: type validation
    $result = array();
    if (is_string($property)) {
      $class = new ReflectionClass($class);
      $property = $class->getProperty($property);
    };
    $constraints = self::getAnnotations($property->getDocComment(TRUE));
    foreach ($constraints as $constraint => $param) {
      if ($constraint == "Valid") {
        // recursive validation
        if (is_object($value)) {
          $violations = self::validate($value);
          foreach ($violations as $violation) {
            $violation->propertyPath = 
              $property->getName().".".$violation->propertyPath;
            $result[] = $violation;
          };
        } else if (is_array($value)) {
          foreach ($value as $i => $item) {
            if (is_object($item)) {
              $violations = self::validate($item);
              foreach ($violations as $violation) {
                $violation->propertyPath = 
                  $property->getName()."[$i].".$violation->propertyPath;
                $result[] = $violation;
              };
            };
          };
        };
      } else {
        $fn = self::$constraints[$constraint];
        if (function_exists($fn)) {
          if (!call_user_func($fn, $value, $param)) {
            $msg = $constraint." constraint violated";
            if (function_exists($fn."_Message")) {
              $msg = call_user_func($fn."_Message", $param);
            };
            $result[] = new ConstraintViolation(
              $msg, $constraint, NULL, $property->getName(), $value);
          };
        };
      };
    };
    return $result;
  }
  
  /**
   * @var mixed $object
   * @var string|\O\ReflectionProperty $property
   * @return \O\ConstraintViolation[]
   */
  static function validateProperty($object, $property) {
    $result = array();
    if (is_string($property)) {
      $class = new ReflectionClass($object);
      $property = $class->getProperty($property);
    };
    if (is_a($property, "ReflectionProperty") && $property->isPublic()) {
      $propertyName = $property->getName();
      $value = $object->$propertyName;
      $result = self::validateValue($property->getDeclaringClass(), $property, $value);
      foreach ($result as &$violation) {
        $violation->rootObject = $object;
      };
    };
    return $result;
  }
  
  /**
   * @var mixed $object
   * @return \O\ConstraintViolation[]
   */
  static function validate($object) {
    $result = array();
    $class = new ReflectionClass($object);
    foreach ($class->getProperties() as $property) {
      $propertyResult = self::validateProperty($object, $property);
      $result = array_merge($result, $propertyResult);
    };
    return $result;
  }
  
  private static $constraints = array();
  static function addConstraint($name, $constraintFn) {
    self::$constraints[$name] = $constraintFn;
  }
  
}

class ConstraintViolation {
  /**
   * A human-readable description of the message
   */
  public $message = "";
  /**
   * The constraint that failed validation (e.g. "NotNull")
   */
  public $constraint = "";
  /**
   * The object whose properties are being validated.
   * For method parameters this is the ReflectionParameter instance.
   */
  public $rootObject = NULL;
  /**
   * The property path relative to the validated object.
   * For example "employee.firstName"
   */
  public $propertyPath = NULL;
  /**
   * The value on which validation failed
   */
  public $invalidValue = NULL;
  
  public function __construct(
    $message, $constraint, $rootObject, $propertyPath, $invalidValue) 
  {
    $this->message = $message;
    $this->constraint = $constraint;
    $this->rootObject = $rootObject;
    $this->propertyPath = $propertyPath;
    $this->invalidValue = $invalidValue;
  }
}

// constraints

// @Null
function validate_Null($value) { return $value === NULL; }
Validator::addConstraint("Null", "O\\validate_Null");
function validate_Null_Message() { return "Must be null"; }

// @NotNull
function validate_NotNull($value) { return $value !== NULL; }
Validator::addConstraint("NotNull", "O\\validate_NotNull");
function validate_NotNull_Message() { return "Cannot be null"; }

// @NotEmpty
function validate_NotEmpty($value) { 
  if ($value === NULL) return FALSE;
  if (is_array($value)) {
    return count($value) > 0;
  } else {
    return (s($value)->trim() !== ""); 
  }
}
Validator::addConstraint("NotEmpty", "O\\validate_NotEmpty");
function validate_NotEmpty_Message() { return "Cannot be empty"; }

// @AssertTrue
function validate_AssertTrue($value) { return $value == TRUE; }
Validator::addConstraint("AssertTrue", "O\\validate_AssertTrue");
function validate_AssertTrue_Message() { return "Must be true"; }

// @AssertFalse
function validate_AssertFalse($value) { return $value == FALSE; }
Validator::addConstraint("AssertFalse", "O\\validate_AssertFalse");
function valudate_AssertFalse_Message() { return "Must be false"; }

// @Min(value)
function validate_Min($value, $param) { 
  if (is_array($value)) {
    foreach ($value as $item) {
      if ($item < $param) return FALSE;
    };
    return TRUE;
  } else {
    return $value >= $param; 
  }
}
Validator::addConstraint("Min", "O\\validate_Min");
function validate_Min_Message($param) { return "Must be >= ".$param; }

// @Max(value)
function validate_Max($value, $param) { 
  if (is_array($value)) {
    foreach ($value as $item) {
      if ($item > $param) return FALSE;
    };
    return TRUE;
  } else {
    return $value <= $param; 
  }
}
Validator::addConstraint("Max", "O\\validate_Max");
function validate_Max_Message($param) { return "Must be <= ".$param; }

// @Size(min=value,max=value)
function validate_Size($value, $variables) {
  $min = isset($variables["min"]) ? $variables["min"] : NULL;
  $max = isset($variables["max"]) ? $variables["max"] : NULL;
  $length = NULL;
  switch (gettype($value)) {
    case "array": $length = count($value); break;
    case "string": $length = s($value)->len(); break;
  };
  return ($length === NULL) || 
    ( (($min === NULL) || ($length >= $min)) &&
      (($max === NULL) || ($length <= $max)) );
}
Validator::addConstraint("Size", "O\\validate_Size");
function validate_Size_Message($param) {
  $min = isset($param["min"]) ? $param["min"] : "?";
  $max = isset($param["max"]) ? $param["max"] : "?";
  return "Size must be between $min and $max";
}
// @DecimalMin(value)
function validate_DecimalMin($value, $param) {
  if ($value === null) return TRUE;
  if (is_array($value)) {
    foreach ($value as $item) {
      if (!validate_DecimalMin($item, $param)) return FALSE;
    };
    return TRUE;
  } else {
    if (function_exists("gmp_init")) {
      $first = gmp_init($value);
      $second = gmp_init($param);
      return gmp_cmp($first, $second) >= 0;
    } else if (function_exists("bccomp")) {
      return bccomp($value, $param) >= 0;
    } else {
      return floatval($value) >= floatval($param);
    }
  }
}
Validator::addConstraint("DecimalMin", "O\\validate_DecimalMin");
function validate_DecimalMin_Message($param) { return "Must be >= ".$param; }
// @DecimalMax(value)
function validate_DecimalMax($value, $param) {
  if ($value === null) return TRUE;
  if (is_array($value)) {
    foreach ($value as $item) {
      if (!validate_DecimalMax($item, $param)) return FALSE;
    };
    return TRUE;
  } else {
    if (function_exists("gmp_init")) {
      $first = gmp_init($value);
      $second = gmp_init($param);
      return gmp_cmp($first, $second) <= 0;
    } else if (function_exists("bccomp")) {
      return bccomp($value, $param) <= 0;
    } else {
      return floatval($value) <= floatval($param);
    }
  }
}
Validator::addConstraint("DecimalMax", "O\\validate_DecimalMax");
function validate_DecimalMax_Message($param) { return "Must be <= ".$param; }
// @Digits(integer=value,fraction=value)
function validate_Digits($value, $variables) {
  if (is_array($value)) {
    foreach ($value as $item) {
      if (!validate_Digits($item, $variables)) return FALSE;
    };
    return TRUE;
  } else {
    $decimals = isset($variables["decimals"]) ? intval($variables["decimals"]) : 0;
    $fraction = isset($variables["fraction"]) ? intval($variables["fraction"]) : 0;
    $value = strval($value);
    $parts = s($value)->explode(".");
    $valueDecimals = s($parts[0])->len();
    $valueFraction = isset($parts[1]) ? s($parts[1])->len() : 0;
    return ($valueDecimals == $decimals) && ($valueFraction == $fraction);
  }
}
Validator::addConstraint("Digits", "O\\validate_Digits");
function validate_Digits_Message($param) {
  $decimals = isset($param["decimals"]) ? intval($param["decimals"]) : 0;
  $fraction = isset($param["fraction"]) ? intval($param["fraction"]) : 0;
  return "Number must have $decimals decimals and $fraction fractional digits";
}
// @Past
function validate_Past($value) {
  if ($value === null) return TRUE;
  if (is_array($value)) {
    foreach ($value as $item) {
      if (!validate_Past($item)) return FALSE;
    };
    return TRUE;
  } else {
    if (!is_a($value, "DateTime")) {
      if (is_int($value)) {
        $value = new \DateTime("@".$value);
      } else {
        $value = new \DateTime($value);
      };
    };
    $now = new \DateTime();
    return $value < $now;
  }
}
Validator::addConstraint("Past", "O\\validate_Past");
function validate_Past_Message() { return "Must be in the past"; };
// @Future
function validate_Future($value) {
  if ($value === null) return TRUE;
  if (is_array($value)) {
    foreach ($value as $item) {
      if (!validate_Future($item)) return FALSE;
    };
    return TRUE;
  } else {
    if (!is_a($value, "DateTime")) {
      if (is_int($value)) {
        $value = new \DateTime("@".$value);
      } else {
        $value = new \DateTime($value);
      };
    };
    $now = new \DateTime();
    return $value > $now;
  }
}
Validator::addConstraint("Future", "O\\validate_Future");
function validate_Future_Message() { return "Must be in the future"; };
// TODO: remaining validator: @Pattern(regex=value,flag=value)

//-----------------------------------------------------------------------------
 