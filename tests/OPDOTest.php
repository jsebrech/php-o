<?php

include_once dirname(__FILE__)."/../O.php";

class OPDOTest extends PHPUnit_Framework_TestCase
{
  /** @var \O\PDO */
  protected $db;

  protected function setUp()
  {
    if (!extension_loaded('pdo_sqlite')) {
      $this->markTestSkipped(
        'The PDO sqlite extension is not available.'
      );
    }
    $this->db = new O\PDO("sqlite::memory:");
    $this->db->exec(
      "CREATE TABLE IF NOT EXISTS test (
         id INTEGER PRIMARY KEY,
         description TEXT
       )");
    $stmt = $this->db->prepare(
      "insert into test (id, description) values (:id, :description)");
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":description", $description);
    for ($id = 1; $id <= 10; $id++) {
      /** @noinspection PhpUnusedLocalVariableInspection */
      $description = "row with id ".$id;
      $stmt->execute();
    }
  }

  public function testConnect() {
    $this->assertNotNull($this->db);
  }


  /**
   * @expectedException PDOException
   */
  public function testShouldThrowException() {
    // will throw exception
    $this->db->fetchOne("select id from invalid");
  }

  public function testFetchAll() {
    $rows = $this->db->fetchAll(
      "select * from test where id <> :id",
      array("id" => 2)
    );
    $this->assertInternalType("array", $rows);
    $this->assertEquals(9, count($rows));
  }

  public function testFetchRow() {
    $row = $this->db->fetchRow(
      "select * from test where id = :id",
      array("id" => 3)
    );
    $this->assertInternalType("array", $row);
    $this->assertEquals("row with id 3", $row["description"]);
  }

  public function testFetchColumn() {
    $col = $this->db->fetchColumn(
      "select description, id from test where id <> :id",
      array("id" => 1), 1
    );
    $this->assertInternalType("array", $col);
    $this->assertEquals(9, count($col));
    $this->assertEquals(2, $col[0]);
  }

  public function testFetchOne() {
    $value = $this->db->fetchOne(
      "select description from test where id = :id",
      array("id" => 2)
    );
    $this->assertEquals("row with id 2", $value);

    $value = $this->db->fetchOne(
      "select description from test where id = :id",
      array("id" => -1),
      "replacement"
    );
    $this->assertEquals("replacement", $value);
  }

  public function testBindParams() {
    // test named params
    $stmt = $this->db->prepare(
      "select description from test where id = :id");
    $value = $stmt->bindParams(array(":id" => 3))->execute()->fetchColumn(0);
    $this->assertEquals("row with id 3", $value);

    // test named params as object
    $param = new StdClass();
    $param->id = 4;
    $value = $stmt->bindParams($param)->execute()->fetchColumn(0);
    $this->assertEquals("row with id 4", $value);

    // test anon params as array
    $value = $this->db->prepare(
      "select count(*) from test where id <> ? and id <> ?"
    )->bindParams(array(2, 3))->execute()->fetchColumn(0);
    $this->assertEquals(8, $value);

    // test anon params as list
    $value = $this->db->prepare(
      "select count(*) from test where id <> ? and id <> ? and id <> ?"
    )->bindParams(2, 3, 4)->execute()->fetchColumn(0);
    $this->assertEquals(7, $value);
  }

  function testInsert() {
    $returned = $this->db->insert("test", array(
      "description" => "foo"
    ));
    $this->assertEquals(11, $returned);
    $count = $this->db->fetchOne(
      "select count(*) from test where id = ?", array(11));
    $this->assertEquals(1, $count);
  }

  function testUpdate() {
    $count = $this->db->update(
      "test",
      array("description" => "foo"),
      "id >= :id1 and id <= :id2",
      array("id1" => 2, "id2" => 6)
    );
    $this->assertEquals(5, $count);
    $count = $this->db->fetchOne("select count(*) from test where description = 'foo'");
    $this->assertEquals(5, $count);
  }

  function testDelete() {
    $count = $this->db->delete(
      "test",
      "id >= :id1 and id <= :id2",
      array("id1" => 2, "id2" => 6)
    );
    $this->assertEquals(5, $count);
    $count = $this->db->fetchOne("select count(*) from test");
    $this->assertEquals(5, $count);
  }

  /**
   * @expectedException PDOException
   */
  function testInsertInvalid() {
    $this->db->insert("test", "invalid");
  }
}
