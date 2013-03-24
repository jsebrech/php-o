<?php

namespace O;

if (!class_exists("\\O\\StringClass")) include("StringClass.php");

/**
 * Reflection class with type hinting and extended docblock parsing
 */
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
    return new ReflectionMethod($this->getName(), $name);
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
    return new ReflectionProperty($this->getName(), $name);
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
    return new ReflectionClass(parent::getDeclaringClass()->getName());
  }

  public function getParameters() {
    $params = parent::getParameters();
    foreach ($params as $index => $param) {
      $params[$index] = new ReflectionParameter(
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
      return new ReflectionMethod($f->getDeclaringClass()->getName(), $f->getName());
    } else {
      return $f;
    }
  }

}
