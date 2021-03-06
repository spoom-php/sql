<?php namespace Spoom\Sql;

//
use Spoom\Core\Storage;
use Spoom\Core\StorageInterface;

//
class Expression {

  /**
   * @var ConnectionInterface
   */
  private $_connection;

  /**
   * @var string
   */
  private $_definition;
  /**
   * @var StorageInterface
   */
  private $_context;

  /**
   * @param ConnectionInterface $connection
   * @param string              $definition
   * @param array|null          $context
   */
  public function __construct( ConnectionInterface $connection, string $definition, $context = [] ) {
    $this->_connection = $connection;
    $this->_definition = $definition;

    $this->_context = Storage::instance( $context );
  }

  //
  public function __clone() {
    $this->_context = clone $this->_context;
  }
  //
  public function __toString() {
    return $this->_connection->apply( $this->_definition, $this->_context );
  }

  /**
   * @return ConnectionInterface
   */
  public function getConnection(): ConnectionInterface {
    return $this->_connection;
  }

  /**
   * @return string
   */
  public function getDefinition(): string {
    return $this->_definition;
  }
  /**
   * @param string $value
   *
   * @return static
   */
  protected function setDefinition( string $value ) {
    $this->_definition = $value;
    return $this;
  }

  /**
   * @return StorageInterface
   */
  public function getContext(): StorageInterface {
    return $this->_context;
  }
  /**
   * @param StorageInterface $value
   */
  public function setContext( StorageInterface $value ) {
    $this->_context = $value;
  }
}