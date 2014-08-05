<?php

namespace O;

// s()
if (!class_exists("\\O\\StringClass")) include("StringClass.php");
// a()
if (!class_exists("\\O\\ArrayClass")) include("ArrayClass.php");
// o()
if (!class_exists("\\O\\ObjectClass")) include("ObjectClass.php");

/**
 * Supporting class for c() function
 */
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
 * @return \O\ChainableClass|\O\StringClass
 */
function cs($o) {
  return c(s($o));
}

/**
 * @param array $o
 * @return \O\ChainableClass|\O\ArrayClass
 */
function ca($o) {
  return c(a($o));
}

/**
 * @param mixed $o
 * @return \O\ChainableClass|\O\ObjectClass
 */
function co($o) {
  return c(o($o));
}
