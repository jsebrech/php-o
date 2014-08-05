<?php

namespace O;

/**
 * Supporting class for the a() function
 */
class ArrayClass implements \IteratorAggregate, \ArrayAccess, \Countable {
  private $a;

  function __construct(&$a) {
    $this->a =& $a;
  }

  /**
   * Count all elements in an array
   * @param int $mode If set to COUNT_RECURSIVE, will recursively count the array.
   * @return int
   */
  function count($mode = COUNT_NORMAL) {
    return count($this->a, $mode);
  }

  /**
   * Checks if a value exists in an array
   * @param mixed $needle The searched value
   * @param bool $strict If true will also compare the types
   * @return bool
   */
  function has($needle, $strict = FALSE) {
    return in_array($needle, $this->a, $strict);
  }

  /**
   * Searches the array for a given value and returns the corresponding key if successful
   * @param mixed $needle The searched value
   * @param bool $strict If true will also compare the types
   * @return mixed
   */
  function search($needle, $strict = FALSE) {
    return array_search($needle, $this->a, $strict);
  }

  /**
   * Shift an element off the beginning of array
   * @return mixed
   */
  function shift() {
    return array_shift($this->a);
  }

  /**
   * Prepend one or more elements to the beginning of an array
   * @param mixed $value1 First value to prepend
   * @return int
   */
  function unshift($value1) {
    $args = func_get_args();
    for ($i = count($args) - 1; $i >= 0; $i--) {
      array_unshift($this->a, $args[$i]);
    };
    return count($this->a);
  }

  /**
   * Checks if the given key or index exists in the array
   * @param mixed $key Value to check
   * @return bool
   */
  function key_exists($key) {
    return array_key_exists($key, $this->a);
  }

  /**
   * Join array elements with a string
   * @param string $glue Defaults to an empty string
   * @return string|StringClass
   */
  function implode($glue = "") {
    return implode($this->a, $glue);
  }

  /**
   * Return all the keys of an array.
   * Due to limitations the additional parameters of array_keys are not supported.
   * @return Array|ArrayClass
   */
  function keys() {
    return array_keys($this->a);
  }

  /**
   * Return all the values of an array
   * @return Array|ArrayClass
   */
  function values() {
    return array_values($this->a);
  }

  /**
   * Pop the element off the end of array
   * @return mixed
   */
  function pop() {
    return array_pop($this->a);
  }

  /**
   * Push one or more elements onto the end of array
   * @param mixed $value1 The first value to append
   * @return int
   */
  function push($value1) {
    $args = func_get_args();
    for ($i = 0; $i < count($args); $i++) {
      array_push($this->a, $args[$i]);
    };
    return count($this->a);
  }

  /**
   * Extract a slice of the array
   * @param int $offset Start from this offset in the array. If negative, offset from end.
   * @param int $length Number of elements to slice. If negative, stop slicing $length from the end.
   * @param bool $preserve_keys Preserve the array's keys
   * @return Array|ArrayClass
   */
  function slice($offset, $length = NULL, $preserve_keys = false) {
    return array_slice($this->a, $offset, $length, $preserve_keys);
  }

  /**
   * Remove a portion of the array and replace it with something else
   * @param int $offset Start from this offset in the array. If negative, offset from end.
   * @param int $length Number of elements to slice. If negative, stop splicing $length from the end.
   * @param Array $replacement Array to insert instead of the spliced segment.
   * @return Array|ArrayClass
   */
  function splice($offset, $length = 0, $replacement = NULL) {
    if ($replacement == NULL) $replacement = array();
    return array_splice($this->a, $offset, $length, $replacement);
  }

  /**
   * Merge one or more arrays
   * @param Array $array1 The first array to merge
   * @param Array $array2 The second array to merge
   * @return Array|ArrayClass
   */
  function merge($array1, $array2) {
    return call_user_func_array("array_merge", array_merge(array($this->a), func_get_args()));
  }

  /**
   * Applies the callback to the elements of this array and additional ones
   * @param Callable $callback Callback function to run for each element in each array.
   * The number of parameters that the callback function accepts should match the number of arrays passed
   * @param Array $array2 The second array whose items to pass as the second argument of $callback.
   * @return Array|ArrayClass
   */
  function map($callback, $array2 = NULL) {
    $args = func_get_args();
    $params = a($args)->slice(1);
    a($params)->unshift($callback, $this->a);
    return call_user_func_array("array_map", $params);
  }

  /**
   * Iteratively reduce the array to a single value using a callback function
   * @param Callable $callback The callback function to call
   *    <code>mixed function($carry, $item)</code>
   *    $carry is the previous iteration's return value, for the first iteration it holds $initial<br>
   *    $item holds the value of the current iteration
   * @param mixed $initial The initial value for the first iteration
   * @return mixed
   */
  function reduce($callback, $initial = NULL) {
    return array_reduce($this->a, $callback, $initial);
  }

  /**
   * Filters elements of an array using a callback function
   * @param Callable $callback If this returns true for a value, the value is in the result array.
   * @return Array|ArrayClass
   */
  function filter($callback = NULL) {
    return array_filter($this->a, $callback);
  }

  /**
   * Calculate the sum of values in an array
   * @return number
   */
  function sum() {
    return array_sum($this->a);
  }

  /**
   * Set the internal pointer of an array to its first element
   * @return mixed The first array value
   */
  function begin() {
    return reset($this->a);
  }

  /**
   * Return the current element in an array
   * @return mixed
   */
  function current() {
    return current($this->a);
  }

  /**
   * Advance the internal array pointer of an array
   * @return mixed The next array value
   */
  function next() {
    return next($this->a);
  }

  /**
   * Set the internal pointer of an array to its last element
   * @return mixed The last array value
   */
  function end() {
    return end($this->a);
  }

  /**
   * Return the current key and value pair from an array and advance the array cursor
   * @return Array|ArrayClass
   */
  function each() {
    return each($this->a);
  }

  /**
   * Remove all elements from the array
   * @return Array|ArrayClass
   */
  function clear() {
    return $this->a = array();
  }

  /**
   * Return the internal raw Array for this ArrayClass object
   * @return Array
   */
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
  if ($p instanceof ArrayClass) {
    return $p;
  } else {
    return new ArrayClass($p);
  }
}
