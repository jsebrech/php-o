<?php

namespace O;

class PDO extends \PDO {

  /**
   * @var bool Enable a fluent API (methods that return bool become chainable)
   */
  private $fluent = TRUE;

  public function __construct($dsn, $username="", $password="", $options=array()) {
    parent::__construct($dsn, $username, $password, $options);
    if (isset($options["fluent"])) $this->fluent = !!$options["fluent"];
    // not compatible with persistent PDO connections
    $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('O\\PDOStatement', array($this->fluent)));
    // don't sweep errors under the rug
    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  /**
   * Fetch all rows from the result set.
   * Additional parameters like PDOStatement::fetchAll
   * @param string $query
   * @param array $bind Parameters to bind (key/value)
   * @param int $fetchStyle
   * @return mixed
   */
  public function fetchAll($query, $bind = array(), $fetchStyle = NULL) {
    $args = array_slice(func_get_args(), 2);
    $args[0] = $fetchStyle ?: $this->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
    return $this->_internalFetch("fetchAll", $query, $bind, $args);
  }

  /**
   * Fetch the first row from the result set.
   * Additional parameters like PDOStatement::fetchRow
   * @param string $query
   * @param array $bind Parameters to bind (key/value)
   * @param int $fetchStyle
   * @return mixed
   */
  public function fetchRow($query, $bind = array(), $fetchStyle = NULL) {
    $args = array_slice(func_get_args(), 2);
    $args[0] = $fetchStyle ?: $this->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
    return $this->_internalFetch("fetch", $query, $bind, $args);
  }

  /**
   * Fetch the first column from all rows
   * @param string $query
   * @param array $bind Parameters to bind (key/value)
   * @param int $columnNumber
   * @return array
   */
  public function fetchColumn($query, $bind = array(), $columnNumber = 0) {
    return $this->fetchAll($query, $bind, PDO::FETCH_COLUMN, $columnNumber);
  }

  /**
   * Fetch the first column of the first row.
   * @param string $query
   * @param array $bind Parameters to bind (key/value)
   * @param mixed $default Return this if the query has no results
   * @return mixed
   */
  public function fetchOne($query, $bind = array(), $default = NULL) {
    $value = $this->_internalFetch("fetchColumn", $query, $bind, array(0));
    return ($value === FALSE) ? $default : $value;
  }

  /**
   * Insert a row into the DB
   * @param string $table
   * @param mixed $bind assoc array or object of key/value pairs
   * @param string $returning param for lastInsertId
   * @return string|int result of lastInsertId($returning)
   */
  public function insert($table, $bind = array(), $returning = NULL) {
    $bind = $this->_convertBind($bind, "insert");
    $values = array();
    for ($i = 0; $i < count($bind); $i++) $values[] = "?";
    $query =
      "insert into ".$table.PHP_EOL.
      "(".implode(", ", array_keys($bind)).")".PHP_EOL.
      "values".PHP_EOL.
      "(".implode(", ", $values).")";
    $stmt = $this->prepare($query);
    $stmt->bindParams(array_values($bind))->execute();
    $result = $this->lastInsertId($returning);
    $stmt->closeCursor();
    return $result;
  }

  /**
   * Update rows in $table
   * @param string $table
   * @param mixed $values assoc array or object of key/value pairs
   * @param string $where sql where clause (excluding "where" keyword)
   * @param mixed $whereBind assoc array or object of key/value pairs
   * @return int affected number of rows
   */
  public function update($table, $values, $where = "", $whereBind = NULL) {
    $query =
      "update ".$table.PHP_EOL .
      "set" . PHP_EOL;
    $values = $this->_convertBind($values, "update");
    $bind = array();
    $set = array();
    foreach ($values as $field => $value) {
      $bind["pdo".count($set)] = $value;
      $set[] = "  ".$field." = :pdo".count($set);
    };
    $query .= implode(",".PHP_EOL, $set).PHP_EOL;
    if (!empty($where)) {
      $query .= "where".PHP_EOL.$where;
      if (!empty($whereBind)) {
        $whereBind = $this->_convertBind($whereBind, "update");
        $bind = array_merge($bind, $whereBind);
      };
    };

    $stmt = $this->prepare($query);
    $stmt->bindParams($bind)->execute();
    $rowCount = $stmt->rowCount();
    $stmt->closeCursor();
    return $rowCount;
  }

  /**
   * Deletes rows from a table
   * @param string $table table to delete rows from
   * @param string $where sql where clause (excluding "where" keyword)
   * @param mixed $whereBind assoc array or object of key/value pairs
   * @return int affected number of rows
   */
  public function delete($table, $where = "", $whereBind = NULL) {
    $query = "delete from ".$table.PHP_EOL;
    $bind = array();
    if (!empty($where)) {
      $query .= "where".PHP_EOL.$where;
      if (!empty($whereBind)) {
        $bind = $this->_convertBind($whereBind, "delete");
      };
    };

    $stmt = $this->prepare($query);
    $stmt->bindParams($bind)->execute();
    $rowCount = $stmt->rowCount();
    $stmt->closeCursor();
    return $rowCount;
  }

  /**
   * @param string $statement
   * @param array $driver_options
   * @return PDOStatement
   */
  public function prepare($statement, $driver_options = array()) {
    return parent::prepare($statement, $driver_options);
  }

  /**
   * @param string $statement
   * @return PDOStatement
   */
  public function query($statement) {
    return parent::query($statement);
  }

  /**
   * @return bool|PDO
   */
  public function beginTransaction() {
    $result = parent::beginTransaction();
    return $this->fluent ? $this : $result;
  }

  /**
   * @return bool|PDO
   */
  public function commit() {
    $result = parent::commit();
    return $this->fluent ? $this : $result;
  }

  /**
   * @return bool|PDO
   */
  public function rollBack() {
    $result = parent::rollBack();
    return $this->fluent ? $this : $result;
  }

  /**
   * @param int $attribute
   * @param mixed $value
   * @return bool|PDO
   */
  public function setAttribute($attribute, $value) {
    $result = parent::setAttribute($attribute, $value);
    return $this->fluent ? $this : $result;
  }

  private function _internalFetch($method, $query, $bind, $args) {
    /** @var \O\PDOStatement $stmt */
    $stmt = $this->prepare($query);
    $stmt->bindParams($bind);
    $stmt->execute();
    $result = call_user_func_array(array($stmt, $method), $args);
    $stmt->closeCursor();
    return $result;
  }

  /**
   * @param mixed $bind
   * @return array
   * @throws \PDOException
   */
  private function _convertBind($bind) {
    if (is_object($bind)) $bind = (array) $bind;
    if (!is_array($bind)) {
      throw new \PDOException(
        "O\\PDO::insert expects argument to be array or object", "90001");
    };
    return $bind;
  }

}

class PDOStatement extends \PDOStatement {

  private $fluent = FALSE;

  /**
   * @param bool $fluent
   * Return $this from API's that would return bool
   */
  protected function __construct($fluent = FALSE) {
    $this->fluent = $fluent;
  }

  /**
   * @param array $bind
   * @return PDOStatement|bool
   */
  public function bindParams($bind) {
    $success = TRUE;
    // support object with key value pairs (= named parameters)
    if (is_object($bind)) {
      $bind = (array) $bind;
    };
    // support list of parameters (= anonymous parameters)
    if (!is_array($bind)) {
      $bind = func_get_args();
    };
    // support array of key value pairs (= named parameters)
    // and array of values (= anonymous parameters)
    if (is_array($bind)) {
      foreach ($bind as $key => $value) {
        if ($this->_isAssocArray($bind)) { // named param
          if ($key[0] !== ":") $key = ":".$key;
        } else { // 1-indexed position for anon param
          $key++;
        };
        $success = $success && $this->bindValue($key, $value);
      };
    };
    return $this->fluent ? $this : $success;
  }

  /**
   * @param mixed $column
   * @param mixed $param
   * @param int $type
   * @param int $maxlen
   * @param mixed $driverdata
   * @return bool|PDOStatement
   */
  public function bindColumn($column, &$param, $type = NULL, $maxlen = NULL, $driverdata = NULL) {
    $result = parent::bindColumn($column, $param, $type, $maxlen, $driverdata);
    return $this->fluent ? $this : $result;
  }

  /**
   * @param mixed $parameter
   * @param mixed $variable
   * @param int $data_type
   * @param int $length
   * @param mixed $driver_options
   * @return bool|PDOStatement
   */
  public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = NULL, $driver_options = NULL) {
    $result = parent::bindParam($parameter, $variable, $data_type, $length, $driver_options);
    return $this->fluent ? $this : $result;
  }

  /**
   * @param mixed $parameter
   * @param mixed $value
   * @param int $data_type
   * @return bool|PDOStatement
   */
  public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR) {
    $result = parent::bindValue($parameter, $value, $data_type);
    return $this->fluent ? $this : $result;
  }

  /**
   * @return bool|PDOStatement
   */
  public function closeCursor() {
    $result = parent::closeCursor();
    return $this->fluent ? $this : $result;
  }

  /**
   * @param array $input_parameters
   * @return bool|PDOStatement
   */
  public function execute($input_parameters = NULL) {
    $result = parent::execute($input_parameters);
    return $this->fluent ? $this : $result;
  }

  /**
   * @return bool|PDOStatement
   */
  public function nextRowSet() {
    $result = parent::nextRowSet();
    return $this->fluent ? $this : $result;
  }

  /**
   * @param int $attribute
   * @param mixed $value
   * @return bool|PDOStatement
   */
  public function setAttribute($attribute, $value) {
    $result = parent::setAttribute($attribute, $value);
    return $this->fluent ? $this : $result;
  }

  /**
   * @param int $mode
   * @return bool|PDOStatement
   */
  public function setFetchMode($mode) {
    $result = parent::setFetchMode($mode);
    return $this->fluent ? $this : $result;
  }

  /**
   * Returns true if the array is associative (key/value pairs)
   * @param array $arr
   * @return bool
   */
  private function _isAssocArray($arr) {
    return array_keys($arr) !== range(0, count($arr) - 1);
  }
}