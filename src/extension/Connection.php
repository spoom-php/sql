<?php namespace Spoom\Sql;

use Spoom\Core\Application;
use Spoom\Core\Helper\Collection;
use Spoom\Core\Helper\Number;
use Spoom\Core\Helper\Text;
use Spoom\Core\Helper;
use Spoom\Core\Exception;

/**
 * Interface ConnectionInterface
 *
 * @property-read string $uri
 * @property string      $authentication
 * @property string      $database
 * @property array       $option
 * @property bool        $connected
 */
interface ConnectionInterface extends Helper\AccessableInterface {

  /**
   * Connect to the database
   *
   * @param bool $ping If already connected ping the host, to ensure connection (and reconnect when neccessary)
   *
   * @return static
   * @throws ConnectionException Can't connect to the server
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
   * Execute statement(s) on the database
   *
   * This will connect to the server if there is no connection
   *
   * @param string|string[] $statement One or more statement to execute
   * @param array           $context   Context to apply on the statement(s)
   *
   * @return ResultInterface|ResultInterface[]
   * @throws StatementException Failed execution
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
   * Supports arrays like `->quote()` and namespace(s) separator
   *
   * @param mixed $value
   *
   * @return string Property quoted and value to insert into a statement
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
   * @param bool $ping {@see static::connect()}
   *
   * @return bool
   */
  public function isConnected( bool $ping = true ): bool;
  /**
   * Get transaction handler
   *
   * @return TransactionInterface
   */
  public function getTransaction(): TransactionInterface;

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
   * @throws ConnectionOptionException
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
   * @throws ConnectionOptionException
   */
  public function setDatabase( ?string $value );
  /**
   * Get connection (all) option
   *
   * @param string|null $name
   * @param mixed|null  $default
   *
   * @return mixed
   */
  public function getOption( ?string $name = null, $default = null );
  /**
   * Set connection option(s)
   *
   * Set to NULL for reset to default
   *
   * @param mixed       $value
   * @param string|null $name
   *
   * @return static
   * @throws ConnectionOptionException
   */
  public function setOption( $value, ?string $name = null );
}
/**
 * Class Connection
 */
abstract class Connection implements ConnectionInterface {
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
   * Separator character for 'namespace' in names
   */
  const CHARACTER_NAME_SEPARATOR = '.';
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
    else if( is_object( $value ) && ( $value instanceof Statement || $value instanceof StatementExpression ) ) return (string) $value;
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

    if( is_string( $value ) ) {

      return implode( static::CHARACTER_NAME_SEPARATOR, array_map( function ( $v ) {
        return static::CHARACTER_QUOTE_NAME . trim( $v, static::CHARACTER_QUOTE_NAME ) . static::CHARACTER_QUOTE_NAME;
      }, explode( static::CHARACTER_NAME_SEPARATOR, $value ) ) );

    } else if( Collection::is( $value, true ) ) {

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

/**
 * Unsuccessful connection to the database server
 */
class ConnectionException extends Exception\Runtime implements ExceptionInterface {

  const ID = '6#spoom-sql';

  /**
   * @param ConnectionInterface $connection
   * @param null|\Throwable     $exception
   */
  public function __construct( ConnectionInterface $connection, ?\Throwable $exception = null ) {

    $data = [ 'connection' => $connection, 'error' => $exception->getMessage() ];
    parent::__construct(
      Helper\Text::apply( "Failed to connect '{connection.authentication}@{connection.uri}', due to: {error}", $data ),
      static::ID,
      $data,
      $exception,
      Application::SEVERITY_CRITICAL
    );
  }
}
/**
 * Unable to change connection option
 */
class ConnectionOptionException extends Exception\Runtime implements ExceptionInterface {

  const ID = '9#spoom-sql';

  /**
   * @param string              $option Option name
   * @param mixed               $value
   * @param ConnectionInterface $connection
   * @param null|\Throwable     $exception
   */
  public function __construct( string $option, $value, ConnectionInterface $connection, ?\Throwable $exception = null ) {

    $data = [ 'connection' => $connection, 'option' => $option, 'value' => $value, 'error' => $exception->getMessage() ];
    parent::__construct(
      Text::apply( "Failed to set {option} on '{connection.authentication}@{connection.uri}', due to: {error}", $data ),
      static::ID,
      $data,
      $exception,
      Application::SEVERITY_WARNING
    );
  }
}
