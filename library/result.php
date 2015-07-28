<?php namespace Sql;

use Framework\Exception;
use Framework\Helper\Library;

/**
 * Class Result
 * @package Sql
 *
 * @property-read bool|mixed     $result    The real result of the query execution
 * @property-read Exception|null $exception The exception object from the execution (if any)
 * @property-read string         $command   The command that creates the result
 * @property-read int|null       $insertid  The last inserted id from the command
 * @property-read int            $rows      The rows affected in the command execution
 *
 * @property-read Exception|null $error     @depricated
 */
abstract class Result extends Library implements \Iterator, \Countable {

  /**
   * Store the result exception, if any
   *
   * @var Exception|null
   * @depricated
   */
  private $_error;
  /**
   * Store the result exception, if any
   *
   * @var Exception|null
   */
  private $_exception;
  /**
   * The result command
   *
   * @var string
   */
  private $_command;

  /**
   * Store the result object, resouce or
   * other result link
   *
   * @var bool|mixed
   */
  private $_result;
  /**
   * The result returned row count or the affected row count
   * for non select queries
   *
   * @var int
   */
  private $_rows;
  /**
   * The result last inserted id, if any
   * @var int|null
   */
  private $_insertid;

  /**
   * The Iterator cursor
   *
   * @var int
   */
  private $cursor = 0;
  /**
   * The Iterator cursor pointed row object
   *
   * @var null|object
   */
  private $row;

  /**
   * @param string          $command
   * @param mixed           $result
   * @param \Exception|null $exception
   * @param int             $rows
   * @param int|null        $insert_id
   */
  public function __construct( $command, $result = false, $exception = null, $rows = 0, $insert_id = null ) {

    $this->_command   = $command;
    $this->_result    = $result;
    $this->_exception = $this->_error = $exception;
    $this->_rows      = $rows;
    $this->_insertid  = $insert_id;
  }
  /**
   * Free the exist result
   */
  public function __destruct() {
    $this->free();
  }

  /**
   * Free stored results ( when override )
   * and clear result, reset Iterator
   */
  public function free() {
    $this->_result = false;

    $this->cursor = 0;
    $this->row    = null;
  }
  
  /**
   * @since 1.2.0
   *
   * @return Exception|null
   */
  public function getException() {
    return $this->_exception;
  }
  /**
   * @since 1.2.0
   *
   * @return string
   */
  public function getCommand() {
    return $this->_command;
  }
  /**
   * @since 1.2.0
   *
   * @return bool|mixed
   */
  public function getResult() {
    return $this->_result;
  }
  /**
   * @since 1.2.0
   *
   * @return int
   */
  public function getRows() {
    return $this->_rows;
  }
  /**
   * @since 1.2.0
   *
   * @return int|null
   */
  public function getInsertid() {
    return $this->_insertid;
  }
  /**
   * @since 1.2.0
   *
   * @deprecated
   *
   * @return Exception|null
   */
  public function getError() {
    return $this->_error;
  }

  /**
   * Return the actual iteration object ( row )
   *
   * @return object
   */
  public function current() {
    return $this->row;
  }
  /**
   * Move forward the Iterator
   */
  public function next() {
    $this->row = $this->getObject( ++$this->cursor );
  }
  /**
   * Return the actual Iterator key
   *
   * @return int|mixed
   */
  public function key() {
    return $this->cursor;
  }
  /**
   * Checks if current position is valid
   *
   * @return bool
   */
  public function valid() {
    return $this->row !== null;
  }
  /**
   * Rewind the Iterator to the first element
   */
  public function rewind() {
    $this->cursor = 0;
    $this->row    = $this->getObject( $this->cursor );
  }
  /**
   * Count method for the count(..) function. This result is the
   * same as ->rows
   */
  public function count() {
    return $this->rows;
  }

  /**
   * Get the $field pointed column from the $record pointed row
   * of the result set
   *
   * @param int $record the row index
   * @param int $field  the column index
   *
   * @return mixed
   */
  abstract public function get( $record = 0, $field = 0 );
  /**
   * Get a column from the result list. The column
   * defined by the $field param
   *
   * @param int $field the column index
   *
   * @return array
   */
  abstract public function getList( $field = 0 );

  /**
   * Get the $record pointed row of the result set in an
   * array where the keys is the field index (position).
   *
   * @param int $record the row index
   *
   * @return array
   */
  abstract public function getArray( $record = 0 );
  /**
   * Get the result in 2d array of arrays where
   * the keys is the field index (position).
   * If $index param setted, the array keys will be the
   * $index pointed field value in each object
   *
   * @param mixed $index
   *
   * @return array[]
   */
  abstract public function getArrayList( $index = null );

  /**
   * Get the $record pointed row of the result set in an
   * associative array where the keys is the field names
   *
   * @param int $record
   *
   * @return array
   */
  abstract public function getAssoc( $record = 0 );
  /**
   * Get the result in 2d array of associative arrays where
   * the keys is the field names.
   * If $index param setted, the array keys will be the $index defined
   * field value in each object
   *
   * @param mixed $index
   *
   * @return array[]
   */
  abstract public function getAssocList( $index = null );

  /**
   * Get the $record pointed row of the result set in an
   * object where the properties is the field names
   *
   * @param int $record
   *
   * @return object
   */
  abstract public function getObject( $record = 0 );
  /**
   * Get the result in 2d array of objects where
   * the properties is the field names and values.
   * If $index param setted, the array keys will be the $index defined
   * field value in each object
   *
   * @param mixed $index
   *
   * @return object[]
   */
  abstract public function getObjectList( $index = null );
}
