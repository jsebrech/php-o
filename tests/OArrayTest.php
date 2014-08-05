<?php

include_once realpath(__DIR__)."/../src/O/ArrayClass.php";

class OArrayTest extends PHPUnit_Framework_TestCase 
{
  public function testCount() {
    $arr = array(1, 2, 3);
    $this->assertEquals(3, O\a($arr)->count());
  }
  
  public function testHas() {
    $arr = array(1, 2, 3);
    $this->assertTrue(O\a($arr)->has(2));
    $this->assertTrue(O\a($arr)->has("2"));
    $this->assertFalse(O\a($arr)->has("2", TRUE));
    $this->assertFalse(O\a($arr)->has(0));
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
  
  public function testMap() {
    $arr = array(1, 2, 3);
    $fn = function($v, $s) { return $v*$s; };
    $mapped = O\a($arr)->map($fn, array(2, 3, 0));
    $this->assertEquals(3, O\a($mapped)->count());
    $this->assertEquals(2, $mapped[0]);
    $this->assertEquals(6, $mapped[1]);
  }
  
  public function testReduce() {
    $arr = array(2, 2, 4);
    $fn = function($a, $b) { return $a+$b; };
    $result = O\a($arr)->reduce($fn, 1);
    $this->assertEquals(9, $result);
  }
  
  public function testSum() {
    $arr = array(1, 1);
    $this->assertEquals(2, O\a($arr)->sum());
  }
  
  public function testForeach() {
    $arr = array("a", "b", "c");
    $r = "";
    foreach (O\a($arr) as $s) {
      $r .= $s;
    };
    $this->assertEquals("abc", $r);
  }

  public function testArrayIndexing() {
    $arr = array("a", "b", "c");
    $obj = O\a($arr);
    $this->assertEquals("b", $obj[1]);
    $obj[1] = "d";
    $this->assertEquals("adc", $obj->implode());
    unset($obj[1]);
    $this->assertEquals(2, $obj->count());
    $this->assertTrue(isset($obj[2]));
    $this->assertFalse(isset($obj[3]));
  }

  public function testNavigation() {
    $arr = array("a", "b", "c");
    $obj = O\a($arr);
    $this->assertEquals("c", $obj->end());
    $this->assertEquals("a", $obj->begin());
    $this->assertEquals("b", $obj->next());
    $this->assertEquals("b", $obj->current());
    $this->assertEquals("c", $obj->next());
    $str = "";
    $obj->begin();
    /** @noinspection PhpUnusedLocalVariableInspection */
    while (list($key, $val) = $obj->each()) {
      $str .= $val;
    };
    $this->assertEquals("abc", $str);
  }

  public function testClear() {
    $arr = array("a", "b", "c");
    $obj = O\a($arr);
    $this->assertEquals(3, $obj->count());
    $obj->clear();
    $this->assertEquals(0, $obj->count());
  }
}
