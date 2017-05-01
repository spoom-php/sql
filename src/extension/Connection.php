<?php namespace Spoom\Sql;

use Spoom\Core\Helper\Collection;
use Spoom\Core\Helper\Number;
use Spoom\Core\Helper\Text;
use Spoom\Core\Helper;

/**
 * Interface ConnectionInterface
 */
interface ConnectionInterface {

  /**
   * Connect to the database
   *
   * @param bool $ping If already connected ping the host, to ensure connection (and reconnect when neccessary)
   *
   * @return static
   */
  public function connect( bool $ping = false );
  /**
   * Disconnect from the database
   *
   * @return static
   */
  public function disconnect();

  /**
   * Create an empty statement
   *
   * @return StatementInterface
   */
  public function statement(): StatementInterface;
  /**
   * Get transaction handler
   *
   * @return TransactionInterface
   */
  public function transaction(): TransactionInterface;

  /**
   * Execute statement(s) on the database
   *
   * This will connect to the server if there is no connection
   *
   * @param string|string[] $statement One or more statement to execute
   * @param array           $context   Context to apply on the statement(s)
   *
   * @return ResultInterface|ResultInterface[]
   * @throws \Throwable TODO create custom exception(s) for some basic SQL errors
   */
  public function execute( $statement, $context = [] );

  /**
   * Escape the input for a statement
   *
   * @param string $text
   *
   * @return string
   */
  public function escape( string $text ): string;
  /**
   * Quote values as a data
   *
   * @param mixed $value
   *
   * @return string Property quoted and escaped value to insert into a statment
   */
  public function quote( $value ): string;
  /**
   * Quote values as object names
   *
   * TODO support dot separated names
   *
   * @param mixed $value
   *
   * @return string Property quoted and value to insert into a statment
   */
  public function quoteName( $value ): string;
  /**
   * Apply a context to a statement
   *
   * Similar to {@see Spoom\Core\Helper\Text::apply()} but with ability to proper quote the inserted context values
   *
   * @param string $statement The statement to process
   * @param array  $context   The context to insert in the statement
   *
   * @return string Statement with the applied context
   */
  public function apply( string $statement, $context = [] ): string;

  /**
   * Check for valid server connection
   *
   * @return bool
   */
  public function isConnected(): bool;

  /**
   * Get the database uri
   *
   * @return string
   */
  public function getUri(): string;
  /**
   * Get the authentication credentials
   *
   * @param string|null $password Optionally get the password
   *
   * @return null|string The username
   */
  public function getAuthentication( &$password = null ): ?string;
  /**
   * Set the authentication credentials
   *
   * @param null|string $user
   * @param null|string $password
   *
   * @return static
   */
  public function setAuthentication( ?string $user, ?string $password = null );
  /**
   * Get the currently selected database
   *
   * @return null|string
   */
  public function getDatabase(): ?string;
  /**
   * Select a database
   *
   * @param null|string $value
   *
   * @return static
   */
  public function setDatabase( ?string $value );
  /**
   * Get connection (all) metadata
   *
   * @param null|string $name
   *
   * @return mixed
   */
  public function getMeta( ?string $name = null );
}
/**
 * Class Connection
 */
abstract class Connection implements ConnectionInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Quote character for the quoting
   */
  const CHARACTER_QUOTE = "'";
  /**
   * Quote character for the variable name quoting
   */
  const CHARACTER_QUOTE_NAME = "`";
  /**
   * The parser skips blocks that delimited by these characters
   */
  const CHARACTER_DELIMITER = '\'"`';
  /**
   * Command separator character for multi statement
   */
  const CHARACTER_SEPARATOR = ';';

  /**
   * Flag for raw data insertion instead of the quoted version
   */
  const CHARACTER_DATA_RAW = '?';
  /**
   * Flag for name quoted data insertion instead of the simple quoted version
   */
  const CHARACTER_DATA_NAME = '!';

  //
  public function quote( $value ): string {

    if( is_bool( $value ) ) return $value ? '1' : '0';
    else if( Number::is( $value, true ) ) return Number::write( $value );
    else if( is_object( $value ) && $value instanceof Statement ) return "({$value})";
    else if( Collection::is( $value, true ) ) {

      $quoted    = [];
      $tmp       = Collection::read( $value );
      $has_array = false;

      if( !count( $tmp ) ) return 'NULL';
      foreach( $tmp as $v ) {
        if( !$has_array ) $has_array = Collection::is( $v, true );
        $quoted[] = $this->quote( $v );
      }

      return ( $has_array ? '' : '(' ) . implode( ',', $quoted ) . ( $has_array ? '' : ')' );
    }

    return Text::is( $value ) ? ( static::CHARACTER_QUOTE . $this->escape( (string) $value ) . static::CHARACTER_QUOTE ) : 'NULL';
  }
  //
  public function quoteName( $value ): string {

    if( is_string( $value ) ) return static::CHARACTER_QUOTE_NAME . trim( $value, static::CHARACTER_QUOTE_NAME ) . static::CHARACTER_QUOTE_NAME;
    else if( Collection::is( $value, true ) ) {

      $quoted    = [];
      $tmp       = Collection::read( $value );
      $has_array = false;

      if( count( $tmp ) ) {

        foreach( $tmp as $v ) {
          if( !$has_array ) $has_array = Collection::is( $v, true );
          $quoted[] = $this->quoteName( $v );
        }

        return ( $has_array ? '' : '(' ) . implode( ',', $quoted ) . ( $has_array ? '' : ')' );
      }
    }

    return '';
  }
  //
  public function apply( string $statement, $context = [] ): string {
    return Text::apply( $statement, $context, static::CHARACTER_DELIMITER, function ( $buffer, $insertion ) {

      $index = ltrim( $buffer, static::CHARACTER_DATA_RAW . static::CHARACTER_DATA_NAME );
      switch( true ) {

        // insert with name quoting
        case $buffer{0} == static::CHARACTER_DATA_NAME:

          return $this->quoteName( $insertion[ $index ] );
          break;

        // insert without processing
        case $buffer{0} == self::CHARACTER_DATA_RAW:

          return $insertion[ $index ];
          break;

        // insert with quoting
        default:
          return $this->quote( $insertion[ $index ] );
      }
    } );
  }
}
