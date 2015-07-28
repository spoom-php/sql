<?php namespace Sql;

use Framework\Helper\Library;

/**
 * Represent a connection with database that Query class use
 *
 * @package Sql
 *
 * @property-read string $configuration The connection's configuration name
 */
abstract class Connection extends Library {

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
   * @since 1.2.0
   *
   * @return string
   */
  public function getConfiguration() {
    return $this->_configuration;
  }

  /**
   * Create connection with the database using the saved parameters
   * or check the connection status and try to reconnect if $ping is true
   *
   * @param bool $ping check status and reconnect
   *
   * @return $this
   */
  abstract protected function connect( $ping = false );
  /**
   * Close the connection if it's active
   *
   * @return $this
   */
  abstract public function disconnect();
}
