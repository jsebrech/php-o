<?php

include_once realpath(__DIR__)."/../src/O/DateTime.php";

class ODateTimeTest extends PHPUnit_Framework_TestCase
{
  public function testIso8601()
  {
    $strISODate = "2011-12-19T22:15:00+01:00";
    $date = new O\DateTime($strISODate);
    $dateStr = (string) $date;
    $this->assertEquals($strISODate, $dateStr);
  }

  public function testJsonIso8601()
  {
    $strISODate = "\"2011-12-19T22:15:00+01:00\"";
    $date = new O\DateTime(json_decode($strISODate));
    $dateStr = json_encode($date);
    $this->assertEquals($strISODate, $dateStr);
  }
}
