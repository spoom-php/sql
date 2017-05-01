<?php namespace Spoom\Sql;

use Spoom\Core\Exception;
use Spoom\Core\Helper;

/**
 * Interface TransactionInterface
 */
interface TransactionInterface {

  /**
   * Start a new transaction with commands
   *
   * @param callable $runnable Callback to run in the transaction
   * @param bool     $commit   Commit after the callback was successful
   *
   * @return mixed Callback's result
   * @throws Exception TODO define custom exception for already pending transaction
   * @throws \Throwable Failed transaction
   */
  public function create( callable $runnable, bool $commit = false );
  /**
   * Resume (or start) a transaction with commands
   *
   * @param callable    $runnable  Callback to run in the transaction
   * @param string|null $savepoint Create a new savepoint before the callback
   * @param bool        $commit    Commit after the callback was successful
   *
   * @return mixed Callback's result
   * @throws \Throwable Failed transaction
   */
  public function update( callable $runnable, ?string $savepoint = null, bool $commit = false );

  /**
   * Begin an empty transaction
   *
   * Does nothing if there is already has pending transactions
   *
   * @return static
   */
  public function begin();
  /**
   * Create a savepoint
   *
   * @param string $name
   *
   * @return static
   * @throws Exception TODO define custom exception for not supported operation
   */
  public function savepoint( string $name );
  /**
   * Rollback transaction(s)
   *
   * @param string|null $savepoint Rollback to a specific savepoint or the whole transaction
   *
   * @return static
   */
  public function rollback( ?string $savepoint = null );
  /**
   * Commit pending transaction
   *
   * @return static
   */
  public function commit();

  /**
   * Connection that holds the transaction
   *
   * @return ConnectionInterface
   */
  public function getConnection(): ConnectionInterface;
  /**
   * There is pending transaction
   *
   * @return bool
   */
  public function isPending(): bool;
}
/**
 * Class Transaction
 *
 * @property-read ConnectionInterface $connection
 * @property-read bool                $pending
 */
abstract class Transaction implements TransactionInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Database connection
   *
   * @var ConnectionInterface
   */
  protected $_connection;

  /**
   * @param ConnectionInterface $connection
   */
  public function __construct( ConnectionInterface $connection ) {
    $this->_connection = $connection;
  }

  //
  public function create( callable $runnable, bool $commit = false ) {
    try {

      // check for already pending transaction
      if( $this->isPending() ) throw new Exception( 'There is pending transaction', 0 ); // TODO define custom exception
      else {

        $this->begin();

        $result = $runnable( $this->_connection, $this );
        if( $commit ) $this->commit();

        return $result;
      }

    } catch( \Throwable $e ) {

      // we must rollback on exceptions
      if( $this->isPending() ) {
        $this->rollback();
      }

      throw $e;
    }
  }
  //
  public function update( callable $runnable, ?string $savepoint = null, bool $commit = false ) {
    try {

      // 
      if( !$this->isPending() ) {
        $this->begin();
      }

      //
      if( $savepoint ) {
        $this->savepoint( $savepoint );
      }

      $result = $runnable( $this->_connection, $this );
      if( $commit ) $this->commit();

      return $result;

    } catch( \Throwable $e ) {

      // we must rollback on exceptions
      if( $this->isPending() ) {
        $this->rollback( $savepoint );
      }

      // throw the exception again
      throw $e;
    }
  }

  //
  public function getConnection(): ConnectionInterface {
    return $this->_connection;
  }
}
