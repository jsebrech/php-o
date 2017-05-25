<?php

namespace O;

if (!class_exists("\\O\\ReflectionClass")) include("ReflectionClass.php");
if (!class_exists("\\O\\DateTime")) include("DateTime.php");

/**
 * Supporting class for the o() function
 */
class ObjectClass implements \IteratorAggregate, \ArrayAccess
{
  private $o;

  function __construct($o) {
    if (is_string($o)) $o = json_decode($o);
    if (is_object($o) || is_array($o)) {
      $this->o = (object) $o;
    } else {
      $this->o = NULL;
    };
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
          /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
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

  function clear() {
    return $this->o = new \stdClass();
  }

  function raw() {
    return $this->o;
  }

  function render($template) {
    extract((array) $this->o);
    /** @noinspection PhpIncludeInspection */
    include $template;
  }

  /**
   * Validate an object using Validator::validate
   * @param Array $errors Variable to return any found errors in, optional reference.
   * @return bool Whether the object is valid according to its annootations
   */
  function validate(&$errors = NULL) {
    if (!class_exists("\\O\\Validator")) include("Validator.php");
    $errors = Validator::validate($this->raw());
    return empty($errors);
  }

// IteratorAggregate

  /** @return \ArrayIterator */
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
  if ($p instanceof ObjectClass) {
    return $p;
  } else {
    return new ObjectClass($p);
  }
}

// supports types from phplint/phpdoc
// http://www.icosaedro.it/phplint/phpdoc.html#types
function convertType($value, $type) {
  if ($value === NULL) return $value;
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
      case "bool": case "boolean": return filter_var($value, FILTER_VALIDATE_BOOLEAN);
      case "FALSE": case "false": return FALSE;
      case "int": case "integer": return intval(is_object($value) ? (string) $value : $value);
      case "float": case "double": return floatval(is_object($value) ? (string) $value : $value);
      case "string": return strval($value);
      case "mixed": return $value;
      case "resource": return is_resource($value) ? $value : NULL;
      case "object": return is_object($value) ? $value : o($value)->cast();
      case "DateTime": return ($value instanceof DateTime) ? $value : new DateTime($value);
      default: return o($value)->cast($type->value);
    }
  };
  return NULL;
}
