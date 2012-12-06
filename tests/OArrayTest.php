<?php

include_once dirname(__FILE__)."/../O.php";
use \O;

class OArrayTest extends PHPUnit_Framework_TestCase 
{
  public function testCount() {
    $arr = array(1, 2, 3);
    $this->assertEquals(3, O\a($arr)->count());
  }
  
  public function testSearch() {
    $arr = array(1, 2, 3);
    $this->assertEquals(1, O\a($arr)->search(2));
  }
  
  public function testShift() {
    $arr = array(1, 2, 3);
    $elem = O\a($arr)->shift();
    $this->assertEquals(1, $elem);
    $this->assertEquals(2, count($arr));
  }
  
  public function testKeyExists() {
    $arr = array("a" => "b");
    $this->assertFalse(O\a($arr)->key_exists("c"));
    $this->assertTrue(O\a($arr)->key_exists("a"));
  }
  
  public function testUnshift()
  {
    $arr = array("a", "b");
    $this->assertEquals(4, O\a($arr)->unshift("c", "d"));
    $this->assertEquals("cdab", implode($arr));
  }
  
  public function testImplode() {
    $arr = array("a", "b", "c");
    $this->assertEquals("abc", O\a($arr)->implode());
    $this->assertEquals("a b c", O\a($arr)->implode(" "));
  }
  
  public function testKeys() {
    $arr = array("a" => "b", "c" => "d");
    $this->assertEquals("ac", implode(O\a($arr)->keys()));
  }
  
  public function testValues() {
    $arr = array("a" => "b", "c" => "d");
    $this->assertEquals("bd", implode(O\a($arr)->values()));  
  }
  
  public function testPop() {
    $arr = array("a", "b");
    $this->assertEquals("b", O\a($arr)->pop());
    $this->assertEquals(1, count($arr));
  }
  
  public function testPush() {
    $arr = array("a", "b");
    $this->assertEquals(4, O\a($arr)->push("c", "d"));
    $this->assertEquals("abcd", implode($arr));
  }
  
  public function testSlice() {
    $arr = array("a", "b", "c", "d");
    $this->assertEquals("bc", implode(O\a($arr)->slice(1, 2)));
  }
  
  public function testSplice() {
    $arr = array("a", "b", "c", "d");
    $this->assertEquals("bc", implode(O\a($arr)->splice(1, 2)));
    $this->assertEquals("ad", implode($arr));
  }
  
  public function testMerge() {
    $arr = array("a", "b");
    $arrNew = O\a($arr)->merge(array("c", "d"), array("e", "f"));
    $this->assertEquals("abcdef", implode($arrNew));
    $this->assertEquals("ab", implode($arr));
  }
  
  public function testForeach() {
    $arr = array("a", "b", "c");
    $r = "";
    foreach (O\a($arr) as $s) {
      $r .= $s;
    };
    $this->assertEquals("abc", $r);
  }
}
