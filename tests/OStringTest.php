<?php

include_once realpath(dirname(__FILE__)."/../core/StringClass.php");

$utf8string = json_decode("\"\u03ba\u03cc\u03c3\u03bc\u03b5\""); // strlen($utf8string) == 10, s()->len() == 5

class OStringTest extends PHPUnit_Framework_TestCase 
{
  public function testPos() 
  {
    $this->assertEquals(4, O\s("testfootest")->pos("foo"));
  }
  
  public function testPosUTF8()
  {
    global $utf8string;
    $this->assertEquals(5, O\s($utf8string."test")->pos("test"));
  }
  
  public function testIPos()
  {
    $this->assertEquals(4, O\s("testfootest")->ipos("FOO"));
  }
  
  public function testRPos()
  {
    $this->assertEquals(3, O\s("12121")->rpos("2"));
  }

  public function testRIPos()
  {
    $this->assertEquals(3, O\s("ababa")->ripos("B"));
  }
  
  public function testExplode()
  {
    $parts = O\s("12121")->explode("2");
    $this->assertEquals(3, count($parts));
    $parts = O\s("12121")->explode("2", 2);
    $this->assertEquals("121", array_pop($parts));
    // utf8 aware
    global $utf8string;
    $parts = O\s($utf8string)->explode("");
    $this->assertEquals(5, count($parts));
  }
  
  public function testTrim()
  {
    $this->assertEquals("test", (string) O\s("  test  ")->trim());
    $this->assertEquals("test", (string) O\s("aatestbb")->trim("ab"));
  }
  
  public function testLRTrim()
  {
    $this->assertEquals("test  ", (string) O\s("  test  ")->ltrim());
    $this->assertEquals("  test", (string) O\s("  test  ")->rtrim());    
  }
  
  public function testPad()
  {
    global $utf8string;
    $this->assertEquals("0001", (string) O\s("1")->pad(4, "0", STR_PAD_LEFT));
    $paddedString = O\s($utf8string)->pad(7);
    $this->assertEquals(7, O\s($paddedString)->len());
    $this->assertEquals(12, strlen($paddedString));
    $paddedString = O\s($utf8string)->pad(15, $utf8string);
    $this->assertEquals(15, O\s($paddedString)->len());
    $this->assertEquals(30, strlen($paddedString));
  }
  
  public function testLen()
  {
    global $utf8string;
    $this->assertEquals(5, (string) O\s($utf8string)->len());
  }
  
  public function testCase()
  {
    $this->assertEquals("A", (string) O\s("a")->toupper());
    $this->assertEquals("a", (string) O\s("A")->tolower());
  }
  
  public function testSubstr()
  {
    $this->assertEquals("foo", (string) O\s("testfootest")->substr(4, 3));
  }
  
  public function testReplace()
  {
    $this->assertEquals("aaccaaccaa", (string) O\s("aabbaabbaa")->replace("bb", "cc"));
    $this->assertEquals("aaccaaccaa", (string) O\s("aabbaabbaa")->ireplace("BB", "cc"));
  }
  
  public function testPregMatch()
  {
    $this->assertEquals(1, O\s("test foo")->preg_match("/foo/"));
    $this->assertEquals(2, O\s("foo foo")->preg_match_all("/foo/"));
    $this->assertEquals("test", (string) O\s("tefoost")->preg_replace("/foo/", ""));
  }
  
  public function testParseType()
  {
    $type = O\s("string[]")->parse_type();
    $this->assertTrue(is_object($type));
    $this->assertTrue($type->isArray);
    $this->assertNull($type->key);
    $this->assertEquals("string", $type->value);
    $type = O\s("array[int]string")->parse_type();
    $this->assertTrue($type->isArray);
    $this->assertEquals("int", $type->key);
    $this->assertEquals("string", $type->value);
    $type = O\s("array[]string")->parse_type();
    $this->assertTrue($type->isArray);
    $this->assertNull($type->key);
    $this->assertEquals("string", $type->value);
    $type = O\s("float")->parse_type();
    $this->assertFalse($type->isArray);
    $this->assertNull($type->key);
    $this->assertEquals("float", $type->value);
    $type = O\s("array[]")->parse_type();
    $this->assertTrue($type->isArray);
    $this->assertNull($type->key);
    $this->assertEquals("mixed", $type->value);
  }
  
  public function testJSCharAt() {
    $this->assertEquals("b", (string) O\s("abc")->charAt(1));
  }
  
  public function testJSIndexOf() {
    $this->assertEquals(6, O\s("abcdefcd")->indexOf("cd", 5));
  }
  
  public function testJSMatch() {
    $this->assertNull(O\s("abc")->match("/d/"));
    $matches = O\s("abc123abc678")->match("/[1-3]+([a-c]+)[6-8]+/");
    $this->assertTrue(is_array($matches));
    $this->assertEquals(2, count($matches));
    $this->assertEquals("123abc678", $matches[0]);
    $this->assertEquals("abc", $matches[1]);
  }
  
  public function testJSSplit() {
    $this->assertEquals(1, count(O\s("abc")->split()));
    $this->assertEquals(3, count(O\s("abcbd")->split("b")));
  }
  
  public function testHtml() {
    $s = O\s("&'\"<>/")->html();
    $this->assertEquals("&amp;&#039;&quot;&lt;&gt;&#x2F;", $s);
  }

  public function testScript() {
    $s = json_decode("\"<&\u2028\"");
    $r = O\s($s)->script();
    $this->assertEquals("\"\\u003C\\u0026\\u2028\"", $r);
  }
  public function testIterable() {
    $s = "abc";
    $a = array();
    foreach (O\s($s) as $i) {
      $a[] = $i;
    };
    $this->assertEquals(3, count($a));
    $this->assertEquals("c", $a[2]);
  }

  public function testArrayIndexing() {
    $s = "abcd";
    $obj = O\s($s);
    $this->assertEquals("c", $obj[2]);
    $obj[2] = "e";
    $this->assertEquals("abed", $obj->raw());
    $this->assertTrue(isset($obj[3]));
    $this->assertFalse(isset($obj[4]));
  }

  public function testClear() {
    $s = "abc";
    $obj = O\s($s);
    $this->assertEquals(3, $obj->len());
    $obj->clear();
    $this->assertEquals(0, $obj->len());
  }
}
