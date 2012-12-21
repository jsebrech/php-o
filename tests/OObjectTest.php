<?php

include_once dirname(__FILE__)."/../O.php";

class OObjectTest extends PHPUnit_Framework_TestCase 
{
  public function testMagicMethods() 
  {
    $o = O\o(array("foo" => "bar", "fn" => function($a, $b) { return $a.$b; }));
    $this->assertTrue(is_object($o));
    $this->assertEquals("bar", $o->foo);
    $o->foo = "baz";
    $this->assertEquals("baz", $o->foo);
    $this->assertTrue(isset($o->foo));
    unset($o->foo);
    $this->assertFalse(isset($o->foo));
    $this->assertTrue(isset($o->fn));
    $this->assertEquals("test", $o->fn("te","st"));
  }
  
  public function testArrayToType()
  {
    $o = O\o(array(
      "var1" => "123",
      "var2" => 123,
      "var3" => "12.3",
      "var4" => "0",
      "var5" => array(
        "var1" => 3.14
      )
    ))->cast("ObjectTest1");
    $this->assertTrue(is_object($o));
    $this->assertEquals("ObjectTest1", get_class($o));
    $this->assertEquals(123, $o->var1);
    $this->assertEquals("integer", gettype($o->var1));
    $this->assertEquals("123", $o->var2);
    $this->assertEquals("string", gettype($o->var2));
    $this->assertEquals(12.3, $o->var3);
    $this->assertEquals("double", gettype($o->var3));
    $this->assertFalse($o->var4);
    $this->assertEquals("boolean", gettype($o->var4));    
    $this->assertNotNull($o->var5);
    $this->assertEquals("integer", gettype($o->var5->var1));
    $this->assertEquals(3, $o->var5->var1);
  }
  
  public function testJSONToObject() {
    $o = O\o('{"key":"value"}')->raw();
    $this->assertTrue(is_object($o));
    $this->assertTrue(isset($o->key));
    $this->assertEquals("value", $o->key);
  }
  
  public function testStaticPropsIgnored() {
    ObjectTest2::$var2 = 1;
    $o = O\o(array("var1" => 2))->cast("ObjectTest2");
    $this->assertEquals(1, ObjectTest2::$var2);
    $this->assertEquals(2, $o->var1);
    $o = O\o(array("var1" => 3, "var2" => 3))->cast("ObjectTest2");
    $this->assertEquals(1, ObjectTest2::$var2);
    $this->assertEquals(3, $o->var2);
    $this->assertEquals(3, $o->var1);
  }
  
}

class ObjectTest1 {
  /**
   * @var int
   */
  public $var1;
  
  /**
   * @var string
   */
  public $var2;
  
  /**
   * @var float
   */
  public $var3;
  
  /**
   * @var boolean
   */
  public $var4;
  
  /**
   * @var ObjectTest1
   */
  public $var5;
}

class ObjectTest2
{
  /** @var int */
  public $var1 = 0;
  
  /** @var int */
  public static $var2 = 0;
}