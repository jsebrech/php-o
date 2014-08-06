<?php

include_once realpath(__DIR__)."/../src/O/Validator.php";

class OValidatorTest extends PHPUnit_Framework_TestCase 
{
  public function testAnnotations() {
    $reflection = new O\ReflectionClass("ValidationTest1");
    $property = $reflection->getProperty("test");
    $comment = $property->getDocComment(TRUE);
    $annotations = O\Validator::getAnnotations($comment);
    $this->assertEquals("array", gettype($annotations));
    $this->assertEquals(3, count($annotations));
    $this->assertTrue($annotations["NotNull"]);
    $this->assertEquals(10, $annotations["Max"]);
    $this->assertTrue(is_array($annotations["Size"]));
    $vars = $annotations["Size"];
    $this->assertEquals(2, count($vars));
    $this->assertEquals(2, $vars["min"]);
    $this->assertEquals(10, $vars["max"]);
  }
  
  public function testNull() {
    $result = O\Validator::validateValue("ValidationTest_Null", "test", NULL);
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(0, count($result));
    $result = O\Validator::validateValue("ValidationTest_Null", "test", "notnull");
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(1, count($result));
  }
  
  public function testNotNull() {
    $obj = new ValidationTest_NotNull();
    $obj->test = "foo";
    $result = O\Validator::validateProperty($obj, "test");
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(0, count($result));
    $obj->test = NULL;
    $result = O\Validator::validateProperty($obj, "test");
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(1, count($result));
    $this->assertEquals("NotNull", $result[0]->constraint);
    $this->assertTrue($result[0]->rootObject === $obj);
  }
  
  public function testNotEmpty() {
    $obj = new ValidationTest_NotEmpty();
    $obj->test = "test";
    $result = O\Validator::validateProperty($obj, "test");
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(0, count($result));
    $obj->test = "  ";
    $result = O\Validator::validateProperty($obj, "test");
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(1, count($result));
    $this->assertEquals("NotEmpty", $result[0]->constraint);
    $this->assertTrue($result[0]->rootObject === $obj);
    
    $obj = new ValidationTest_NotEmpty_Array();
    $obj->test = array("");
    $result = O\Validator::validateProperty($obj, "test");
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(0, count($result));
    $obj->test = array();
    $result = O\Validator::validateProperty($obj, "test");
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(1, count($result));
    $this->assertEquals("NotEmpty", $result[0]->constraint);
    $this->assertTrue($result[0]->rootObject === $obj);
  }
  
  function testAssertTrue() {
    $obj = new ValidationTest_AssertTrue();
    $obj->test = TRUE;
    $result = O\Validator::validate($obj);
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(0, count($result));
    $obj->test = FALSE;
    $result = O\Validator::validate($obj);
    $this->assertEquals("array", gettype($result));
    $this->assertEquals(1, count($result));
  }
  
  function testMin() {
    $obj = new ValidationTest_Min();
    $obj->test = array(0, 1, 4);
    $this->assertEquals(0, count(O\Validator::validate($obj)));
    $obj->test[] = -1;
    $this->assertEquals(1, count(O\Validator::validate($obj)));
  }
  
  function testValid() {
    $obj = new ValidationTest_Valid();
    $obj2 = new ValidationTest_NotNull();
    $obj2->test = "foo";
    $obj->test = $obj2;
    $this->assertEquals(0, count(O\Validator::validate($obj)));
    $obj2->test = NULL;
    $this->assertEquals(1, count(O\Validator::validate($obj)));
  }
  
  function testArrayValid() {
    $obj = new ValidationTest_Valid();
    $obj->test = new ValidationTest_NotNull();
    $obj->test->test = "foo";
    $this->assertEquals(0, count(O\Validator::validate($obj)));
    $obj->arrayVal = NULL;
    $this->assertEquals(1, count(O\Validator::validate($obj)));
    $obj->arrayVal = array(new ValidationTest_NotNull());
    $this->assertEquals(1, count(O\Validator::validate($obj)));
    $obj->arrayVal[0]->test = "foo";
    $this->assertEquals(0, count(O\Validator::validate($obj)));
  }
  
  function testDecimalMin() {
    $obj = new ValidationTest_DecimalMin();
    $obj->test = "3.1415926";
    $this->assertEquals(0, count(O\Validator::validate($obj)));
    $obj->test = "2.9999999";
    $this->assertEquals(1, count(O\Validator::validate($obj)));
  }
  
  function testDigits() {
    $obj = new ValidationTest_Digits();
    $obj->test = "3.1415";
    $this->assertEquals(0, count(O\Validator::validate($obj)));
    $obj->test = "3.141";
    $this->assertEquals(1, count(O\Validator::validate($obj)));
    $obj->test = "30.1415";
    $this->assertEquals(1, count(O\Validator::validate($obj)));
  }
  
  function testFuture() {
    $obj = new ValidationTest_Future();
    $obj->test = mktime() + 3600;
    $this->assertEquals(0, count(O\Validator::validate($obj)));
    $obj->test = mktime() - 1;
    $this->assertEquals(1, count(O\Validator::validate($obj)));
  }
  
  function testPast() {
    $obj = new ValidationTest_Past();
    $obj->test = mktime() - 1;
    $this->assertEquals(0, count(O\Validator::validate($obj)));
    $obj->test = mktime() + 3600;
    $this->assertEquals(1, count(O\Validator::validate($obj)));
  }

}

class ValidationTest1
{
  /**
   * @var string
   * @NotNull
   * @Max(10)
   * @Size(min=2, max=10)
   */
  public $test = "";
}

class ValidationTest_Null {
  /**
   * @var string
   * @Null
   */
  public $test;
}

class ValidationTest_NotNull {
  /**
   * @var string
   * @NotNull
   */
  public $test;
}

class ValidationTest_NotEmpty {
  /**
   * @var string
   * @NotEmpty
   */
  public $test;
}

class ValidationTest_NotEmpty_Array {
  /**
   * @var string[]
   * @NotEmpty
   */
  public $test;
}

class ValidationTest_AssertTrue {
  /**
   * @var bool
   * @AssertTrue
   */
  public $test;
}

class ValidationTest_Min {
  /**
   * @var int[]
   * @Min(0)
   */
  public $test; 
}

class ValidationTest_Valid {
  /**
   * @var ValidationTest_NotNull
   * @Valid
   */
  public $test;
  
  /**
   * @var ValidationTest_NotNull[]
   * @NotNull
   * @Valid
   */
  public $arrayVal = array();
}

class ValidationTest_DecimalMin {
  /**
   * @var string
   * @DecimalMin(3)
   */  
  public $test;
}

class ValidationTest_Digits {
  /**
   * @Digits(decimals=1,fraction=4)
   */
  public $test;
}

class ValidationTest_Future {
  /**
   * @Future
   */
  public $test;
}

class ValidationTest_Past {
  /**
   * @Past
   */
  public $test;
}