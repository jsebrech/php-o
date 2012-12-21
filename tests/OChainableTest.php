<?php

include_once dirname(__FILE__)."/../O.php";

class OChainableTest extends PHPUnit_Framework_TestCase 
{
  public function testChainString() 
  {
    $s = "123fooxxx";
    $r = O\cs($s)->substr(3)->rtrim("x")->raw();
    $this->assertEquals("foo", $r);
  }
  
  public function testTypeTransition()
  {
    $s = "ababa";
    $r = O\cs($s)->explode("b")->implode("c")->raw();
    $this->assertEquals("acaca", $r);
  }
  
  public function testImplicitString()
  {
    $s = "foobar";
    $r = (string) O\cs($s)->substr("3");
    $this->assertEquals("bar", $r);
  }
  
  public function testChainBuiltins()
  {
    $a = new ArrayObject(array("foo", "bar", "baz"));
    $r = O\c($a)->getArrayCopy()->implode()->raw();
    $this->assertEquals("foobarbaz", $r);
  }

  public function testArrayForeach() {
    $arr = array("a", "b", "c");
    $r = "";
    foreach (O\ca($arr) as $s) {
      $r .= $s;
    };
    $this->assertEquals("abc", $r);
  }
  
  public function testDeepNesting() {
    $s = O\c(O\c(O\cs("test")))->raw();
    $this->assertEquals("test", $s);
  }
}