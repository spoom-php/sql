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
   * @param string $index
   *
   * @return mixed
   */
  public function __get( $index ) {
    $i = '_' . $index;

    if( property_exists( $this, $i ) ) return $this->{$i};
    else return parent::__get( $index );
  }
  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index ) || parent::__isset( $index );
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
