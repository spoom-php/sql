<?php namespace Sql;

use Framework\Exception;
use Framework\Helper\Library;
use Framework\Helper\LibraryInterface;

/**
 * Interface ConnectionInterface
 * @package Sql
 */
interface ConnectionInterface extends LibraryInterface {

  /**
   * Save initial connection parameters
   *
   * @param string|null $configuration the connection configuration identifier
   */
  public function __construct( $configuration = null );

  /**
   * Create connection with the database using the saved parameters
   * or check the connection status and try to reconnect if $ping is true
   *
   * @param bool $ping check status and reconnect
   *
   * @return $this
   */
  public function connect( $ping = false );
  /**
   * Close the connection if it's active
   *
   * @return $this
   */
  public function disconnect();

  /**
   * Get the connection status
   *
   * @param bool $try Try to connect if there is no connection
   *
   * @return bool
   */
  public function isAlive( $try = false );
  /**
   * Get the connection configuration
   *
   * @return string
   */
  public function getConfiguration();
  /**
   * Get the last occured exception
   *
   * @return \Exception|null
   */
  public function getException();
}
/**
 * Represent a connection with database that Query class use
 *
 * @package Sql
 *
 * @property-read string $configuration The connection's configuration name
 */
abstract class Connection extends Library implements ConnectionInterface {

  /**
   * Failed connection to the database server
   */
  const EXCEPTION_FAIL_CONNECT = 'sql#8C';

  /**
   * Storage the configuration name
   *
   * @var string
   */
  protected $_configuration;
  /**
   * The last occured exception
   *
   * @var \Exception|null
   */
  protected $_exception;

  /**
   * Save initial connection parameters
   *
   * @param string $configuration the connection configuration identifier
   */
  public function __construct( $configuration = null ) {
    $this->_configuration = $configuration;
  }
  /**
   * Close active connection on object destruction
   */
  public function __destruct() {
    $this->disconnect();
  }

  /**
   * Reset the object exception state
   */
  protected function reset() {
    $this->_exception = null;
  }

  /**
   * Get the connection status
   *
   * FIXME this is here just for compatibility reasons. Remove it from 2.x
   *
   * @param bool $try Try to connect if there is no connection
   *
   * @return bool
   */
  public function isAlive( $try = false ) {
    return false;
  }
  /**
   * @since 1.2.0
   *
   * @return string
   */
  public function getConfiguration() {
    return $this->_configuration;
  }
  /**
   * Get the last occured exception
   *
   * @return \Exception|null
   */
  public function getException() {
    return $this->_exception;
  }
}
