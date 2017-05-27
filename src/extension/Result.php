<?php namespace Spoom\Sql;

use Spoom\Core\Helper;

/**
 * Interface ResultInterface
 */
interface ResultInterface extends \Iterator, \Countable {

  /**
   * Free stored results ( when override )
   * and clear result, reset Iterator
   */
  public function free();

  /**
   * Get the $field pointed column from the $record pointed row
   * of the result set
   *
   * @param int $record the row index
   * @param int $field  the column index
   *
   * @return mixed
   */
  public function get( int $record = 0, int $field = 0 );
  /**
   * Get a column from the result list. The column
   * defined by the $field param
   *
   * @param int $field the column index
   *
   * @return array
   */
  public function getList( int $field = 0 ): array;

  /**
   * Get the $record pointed row of the result set in an
   * array where the keys is the field index (position).
   *
   * @param int $record the row index
   *
   * @return array
   */
  public function getArray( int $record = 0 ): array;
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
  public function getArrayList( $index = null ): array;

  /**
   * Get the $record pointed row of the result set in an
   * associative array where the keys is the field names
   *
   * @param int $record
   *
   * @return array
   */
  public function getAssoc( int $record = 0 ): array;
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
  public function getAssocList( $index = null ): array;

  /**
   * Get the $record pointed row of the result set in an
   * object where the properties is the field names
   *
   * @param int $record
   *
   * @return object
   */
  public function getObject( int $record = 0 );
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
  public function getObjectList( $index = null ): array;

  /**
   * @since 1.2.0
   *
   * @return \Throwable|null
   */
  public function getException():?\Throwable;
  /**
   * @since 1.2.0
   *
   * @return string
   */
  public function getStatement(): string;
  /**
   * @since 1.2.0
   *
   * @return mixed
   */
  public function getResult();
  /**
   * @since 1.2.0
   *
   * @return int
   */
  public function getRows(): int;
  /**
   * @since 1.2.0
   *
   * @return int|null
   */
  public function getInsertid():?int;
}
/**
 * Class Result
 *
 * @property-read mixed           $result      The real result of the query execution
 * @property-read \Throwable|null $exception   The exception object from the execution (if any)
 * @property-read string          $statement   The statement that creates the result
 * @property-read int|null        $insertid    The last inserted id from the command
 * @property-read int             $rows        The rows affected in the command execution
 */
abstract class Result implements ResultInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Store the result exception, if any
   *
   * @var \Throwable|null
   */
  private $_exception;
  /**
   * The result command
   *
   * @var string
   */
  private $_statement;

  /**
   * Store the result object, resouce or
   * other result link
   *
   * @var mixed
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
   * The Iterator cursor pointed row array
   *
   * @var null|array
   */
  private $row;

  /**
   * @param string          $statement
   * @param mixed           $result
   * @param \Throwable|null $exception
   * @param int             $rows
   * @param int|null        $insert_id
   */
  public function __construct( string $statement, $result = null, ?\Throwable $exception = null, int $rows = 0, ?int $insert_id = null ) {

    $this->_statement = $statement;
    $this->_result    = $result;
    $this->_exception = $exception;
    $this->_rows      = $rows;
    $this->_insertid  = $insert_id;
  }
  /**
   * Free the result
   */
  public function __destruct() {
    $this->free();
  }

  //
  public function free() {
    $this->_result = null;

    $this->cursor = 0;
    $this->row    = null;
  }

  //
  public function getException(): \Throwable {
    return $this->_exception;
  }
  //
  public function getStatement(): string {
    return $this->_statement;
  }
  //
  public function getResult() {
    return $this->_result;
  }
  //
  public function getRows(): int {
    return $this->_rows;
  }
  //
  public function getInsertid():?int {
    return $this->_insertid;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   */
  public function current() {
    return $this->row;
  }
  //
  public function next() {
    $this->row = $this->getArray( ++$this->cursor );
  }
  //
  public function key() {
    return $this->cursor;
  }
  //
  public function valid() {
    return $this->row !== null;
  }
  //
  public function rewind() {
    $this->cursor = 0;
    $this->row    = $this->getArray( $this->cursor );
  }
  //
  public function count() {
    return $this->rows;
  }
}
