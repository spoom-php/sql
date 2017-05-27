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
   * @param null|string $savepoint Create a savepoint
   *
   * @return static
   * @throws TransactionException Unable to begin a transaction
   */
  public function begin( ?string $savepoint = null );
  /**
   * Create a savepoint
   *
   * @param string $name
   *
   * @return static
   * @throws TransactionException Unable to create the savepoint
   */
  public function savepoint( string $name );
  /**
   * Rollback transaction(s)
   *
   * @param string|null $savepoint Rollback to a specific savepoint or the whole transaction
   *
   * @return static
   * @throws TransactionException Unable to rollback the transaction
   */
  public function rollback( ?string $savepoint = null );
  /**
   * Commit pending transaction
   *
   * @return static
   * @throws TransactionException Unable to commit the transaction
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
abstract class Transaction implements TransactionInterface {

  //
  public function create( callable $runnable, bool $commit = false ) {

    // check for already pending transaction
    if( $this->isPending() ) throw new \LogicException( 'There is already a pending transaction' );
    else try {

      $this->begin();

      $result = $runnable( $this->getConnection(), $this );
      if( $commit ) $this->commit();

      return $result;

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
      if( !$this->isPending() ) $this->begin( $savepoint );
      else if( $savepoint ) $this->savepoint( $savepoint );

      $result = $runnable( $this->getConnection(), $this );
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

}

/**
 * Failed to change the transaction state
 */
class TransactionException extends Exception\Runtime implements ExceptionInterface {

  const ID = '10#spoom-sql';

  /**
   * @param string              $action
   * @param ConnectionInterface $connection
   * @param null|\Throwable     $exception
   */
  public function __construct( string $action, ConnectionInterface $connection, ?\Throwable $exception = null ) {

    $data = [ 'connection' => $connection, 'action' => $action, 'error' => $exception->getMessage() ];
    parent::__construct( Helper\Text::apply( "Failed to change the transaction state to '{action}' on '{connection.authentication}@{connection.uri}', due to: {error}", $data ), static::ID, $data, $exception );
  }
}
