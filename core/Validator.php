<?php

namespace O;

if (!class_exists("\\O\\StringClass")) include("StringClass.php");
if (!class_exists("\\O\\ObjectClass")) include("ObjectClass.php");
if (!class_exists("\\O\\ReflectionClass")) include("ReflectionClass.php");

/**
 * PHP implementation of JSR-303 (object validation via annotation)
 * Usage: O\Validator::validate($obj)
 */
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
    $result = array();
    if (is_string($property)) {
      $class = new ReflectionClass($class);
      $property = $class->getProperty($property);
    };
    $converted = convertType($value, $property->getType());
    if ((gettype($converted) != gettype($value)) || ($converted != $value)) {
      $result[] = new ConstraintViolation(
        "Property type mismatch", "type", NULL, $property->getName(), $value);
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

