<?php

namespace O;

/**
 * Supporting class for the a() function
 */
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

  function begin() {
    return reset($this->a);
  }

  function current() {
    return current($this->a);
  }

  function next() {
    return next($this->a);
  }

  function end() {
    return end($this->a);
  }

  function each() {
    return each($this->a);
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
