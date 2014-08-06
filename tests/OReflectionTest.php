<?php

include_once realpath(__DIR__)."/../O.php";

class OReflectionTest extends PHPUnit_Framework_TestCase 
{
  public function testClassComment() 
  {
    $reflection = new O\ReflectionClass("ReflectionTest1");
    $this->assertEquals("@package Example", $reflection->getDocComment(TRUE));
    $reflection = new O\ReflectionClass("ReflectionTest2");
    $this->assertEquals("@package Example", $reflection->getDocComment(TRUE));    
  }
  
  /**
   * @expectedException ReflectionException
   */
  public function testFakePropertyException()
  {
    $reflection = new O\ReflectionClass("ReflectionTest1");
    $reflection->getProperty("nosuchprop");
  }  
  
  public function testPropertyComment()
  {
    $reflection = new O\ReflectionClass("ReflectionTest1");
    $property = $reflection->getProperty("test");
    $this->assertEquals("@var string", $property->getDocComment(TRUE));
  }
  
  public function testMethodComment()
  {
    $reflection = new O\ReflectionClass("ReflectionTest1");
    $method = $reflection->getMethod("test");
    $this->assertGreaterThan(0, O\s($method->getDocComment())->pos("@param string \$two"));
  }
  
  public function testMethodParameter()
  {
    $reflection = new O\ReflectionClass("ReflectionTest1");
    $method = $reflection->getMethod("test");
    $param = $method->getParameter("two");
    $this->assertNotNull($param);
    $this->assertEquals("@param string \$two", $param->getDocComment());
    $param = $method->getParameter("one");
    $this->assertNotNull($param);
    $this->assertNotSame(FALSE, O\s($param->getDocComment())->pos("@param int \$one"));
    $this->assertNotSame(FALSE, O\s($param->getDocComment())->pos("Some text"));
  }
  
  public function testGetType()
  {
    $reflection = new O\ReflectionClass("ReflectionTest3");
    $this->assertEquals("string", $reflection->getProperty("test")->getType());
    $this->assertEquals("string[]", $reflection->getProperty("test2")->getType());
    $this->assertEquals("array[int]string", $reflection->getProperty("test3")->getType());
  }
  
}

/**
 * @package Example
 */
class ReflectionTest1 {
  /** @var string */
  public $test = "foo";
  
  /**
   * @param int $one
   * Some text
   * @param string $two
   */
  public function test($one, $two = "") {}
}

/** @package Example */
class ReflectionTest2 { }

class ReflectionTest3 {
  /** @var string */
  public $test;
  
  /** @var string[] test*/
  public $test2;
  
  /** 
   * @var array[int]string 
   */
  public $test3;
}
