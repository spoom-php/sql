<?php namespace Sql;

use Framework\Exception;
use Framework\Helper\Library;

/**
 * Class Transaction
 * @package Sql
 * @since 1.1.0
 *
 * @property-read Query $query   The source query object
 * @property-read bool  $pending The transaction state: started or not
 */
abstract class Transaction extends Library {

  /**
   * The runnable results was false
   */
  const EXCEPTION_FAIL_RUNNABLE = 'sql#9E';

  /**
   * The source query object
   *
   * @var Query
   */
  protected $_query;

  /**
   * Flag for the transaction state
   *
   * @var bool
   */
  protected $_pending = false;

  /**
   * @param Query $query
   */
  public function __construct( Query $query ) {
    $this->_query = $query;
  }

  /**
   * Executes the runnable with exception handling
   *
   * @param callable $runnable The runnable to call in the transaction
   * @param bool     $silent   Throw the exceptions, or just a false if any exception occurs
   *
   * @return bool False on rollback (in silent mode) otherwise true
   * @throws \Exception
   */
  public function execute( callable $runnable, $silent = false ) {

    try {

      // start the transaction
      $this->start();
      return $this->run( $runnable );

    } catch( \Exception $exception ) {

      // stop with rollback if has a started transaction
      if( $this->_pending ) $this->stop( true );

      // return the result or throw the error, based on the input
      if( $silent ) return false;
      else throw $exception;
    }
  }

  /**
   * Execute the runnable function, and handle the result
   *
   * @param callable $runnable The runnable to run in the transaction
   *
   * @return mixed
   * @throws Exception
   */
  public function run( callable $runnable ) {

    $result = $runnable( $this->_query, $this );
    if( $result === false ) $this->stop( true );
    else {

      $this->stop();
      return $result;
    }

    throw new Exception\Strict( self::EXCEPTION_FAIL_RUNNABLE );
  }

  /**
   * This will start the transaction and set the state flag for the instance
   */
  abstract public function start();
  /**
   * Stop the transaction with or without rollback action and set the state flag for the instance
   *
   * @param bool $rollback Rollback transaction changes or commit it successfully
   */
  abstract public function stop( $rollback = false );
}
