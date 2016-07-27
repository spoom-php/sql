<?php namespace Sql;

use Framework\Exception;
use Framework\Extension;
use Framework\Helper\Enumerable;
use Framework\Helper\Library;

/**
 * Sql query execution and preparse. Also handle Query instantiation and driver class cache
 *
 * @package Sql
 *
 * @property-read Connection $connection The connection where the commands run
 * @property      string     $prefix     Insertion for '{.}' strings
 * @property-read string     $separator  Command separator for multi command execution
 */
abstract class Query extends Library {

  /**
   * Missing or invalid Builder class
   */
  const EXCEPTION_MISSING_BUILDER = 'sql#1C';
  /**
   * Missing or invalid Result class
   */
  const EXCEPTION_INVALID_RESULT = 'sql#2C';
  /**
   * Missing or invalid Driver definition
   */
  const EXCEPTION_INVALID_DRIVER = 'sql#3C';
  /**
   * @depricated Use EXCEPTION_FAIL_QUERY instead
   */
  const EXCEPTION_INVALID_QUERY = 'sql#4E';
  /**
   * Empty query execution
   */
  const EXCEPTION_MISSING_QUERY = 'sql#5W';
  /**
   * @depricated Use the Connection::EXCEPTION_FAIL_CONNECT
   */
  const EXCEPTION_UNABLE_TO_CONNECT = 'sql#6E';
  /**
   * Failed command execution
   */
  const EXCEPTION_FAIL_QUERY = 'sql#7E';
  /**
   * Missing or invalid Transaction class
   *
   * @since 1.1.0
   */
  const EXCEPTION_MISSING_TRANSACTION = 'sql#10C';

  /**
   * Event before the query execution. Arguments:
   * - &dbq [Query]: This class instance
   * - &prefix [string]: The prefix string used for {.} replacement
   * - &command [string]: The command that will run after the event
   * - &insertion [array]: The insertion array
   */
  const EVENT_EXECUTE_BEFORE = 'execute.before';
  /**
   * Event after the query execution. Arguments:
   * - &dbq [Query]: This class instance
   * - &dbr [Result|Result[]]: The query result or array of results
   */
  const EVENT_EXECUTE = 'execute';

  /**
   * Insertion starter
   */
  const CHARACTER_DATA_START = '{';
  /**
   * End of the insertion
   */
  const CHARACTER_DATA_END = '}';
  /**
   * Prefix insertion identifier
   */
  const CHARACTER_DATA_PREFIX = '.';
  /**
   * Flag for raw data insertion instead of the quoted version
   */
  const CHARACTER_DATA_RAW = '!';

  /**
   * Class name of the base Connection object
   */
  const CLASS_CONNECTION = '\\Sql\\Connection';
  /**
   * Class name of the base Query object
   */
  const CLASS_QUERY = '\\Sql\\Query';
  /**
   * Class name of the base Transaction object
   *
   * @since 1.1.0
   */
  const CLASS_TRANSACTION = '\\Sql\\Transaction';
  /**
   * Class name of the base Builder object
   *
   * @since 1.1.0
   */
  const CLASS_BUILDER = '\\Sql\\Builder';

  /**
   * Store driver class cache
   *
   * @var array
   */
  private static $drivers = [ ];
  /**
   * Cache the connections with 'driver:configuration' indexes
   *
   * @var array[string]Connection
   */
  private static $connections = [ ];

  /**
   * Store the builder class name for optimalization
   *
   * @var string
   */
  private $builder_class;
  /**
   * Store the transaction class name for optimalization
   *
   * @var string
   */
  private $transaction_class;

  /**
   * Store Connection object
   *
   * @var Connection
   */
  private $_connection;

  /**
   * Default quote character for the quoting
   *
   * @var string
   */
  protected $quoter = "'";
  /**
   * Default quote character for the variable name quoting
   *
   * @var string
   */
  protected $quoter_name = '`';
  /**
   * The parser skip qouteing insertions in blocks that delimited by these characters
   *
   * @var array
   */
  protected $delimiter = [ "'", '"', '`' ];

  /**
   * Store {.} replace
   *
   * @var string
   */
  private $_prefix;
  /**
   * Command separator charachter
   *
   * @var string
   */
  protected $_separator = ";";

  /**
   * @param Connection            $connection
   * @param Extension|string|null $prefix
   */
  public function __construct( Connection $connection, $prefix = null ) {
    $this->_connection = $connection;
    $this->prefix      = $prefix;
  }

  /**
   * Builder class instantiation
   *
   * @return Builder
   * @throws Exception\System
   */
  public function builder() {

    if( empty( $this->builder_class ) ) {

      $class = get_class( $this );
      $tmp   = explode( '\\', $class );

      $extension = \Framework::search( $tmp );
      if( empty( $extension ) ) throw new Exception\System( self::EXCEPTION_MISSING_BUILDER, [ $class ] );
      else {

        $tmp[ count( $tmp ) - 1 ] = 'builder';
        $extension                = Extension::instance( $extension );

        $tmp = $extension->library( implode( '.', $tmp ) );
        if( empty( $tmp ) || !is_subclass_of( $tmp, self::CLASS_BUILDER ) ) throw new Exception\System( self::EXCEPTION_MISSING_BUILDER, [ $class ] );
        else $this->builder_class = $tmp;
      }
    }

    $tmp = $this->builder_class;
    return new $tmp( $this );
  }
  /**
   * Transaction class instantiation
   *
   * @since 1.1.0
   *
   * @return Transaction
   * @throws Exception\System
   */
  public function transaction() {

    if( empty( $this->transaction_class ) ) {

      $class = get_class( $this );
      $tmp   = explode( '\\', $class );

      $extension = \Framework::search( $tmp );
      if( empty( $extension ) ) throw new Exception\System( self::EXCEPTION_MISSING_TRANSACTION, [ $class ] );
      else {

        $tmp[ count( $tmp ) - 1 ] = 'transaction';
        $extension                = Extension::instance( $extension );

        $tmp = $extension->library( implode( '.', $tmp ) );
        if( empty( $tmp ) || !is_subclass_of( $tmp, self::CLASS_TRANSACTION ) ) throw new Exception\System( self::EXCEPTION_MISSING_TRANSACTION, [ $class ] );
        else $this->transaction_class = $tmp;
      }
    }

    $tmp = $this->transaction_class;
    return new $tmp( $this );
  }

  /**
   * Execute parametered command and return the result object or a result object list
   *
   * @param string $command   The command or commands ( separated by ; ) to execute
   * @param array  $insertion Insertion array for command
   *
   * @return Result|Result[]|null
   * @throws Exception
   */
  public function execute( $command, array $insertion = [ ] ) {

    // normalise $insertion
    if( !is_array( $insertion ) ) $insertion = [ ];

    // call event
    $prefix    = (string) $this->_prefix;
    $extension = Extension::instance( 'sql' );
    $event     = $extension->trigger( self::EVENT_EXECUTE_BEFORE, [
      'dbq'       => &$this,
      'prefix'    => &$prefix,
      'command'   => &$command,
      'insertion' => &$insertion
    ] );

    if( $event->prevented ) return $this->getResultList( [ ], $command, $insertion );
    else if( empty( $command ) ) throw new Exception\Strict( self::EXCEPTION_MISSING_QUERY );
    else {

      // call the execution method
      $results = $this->getResultList( $this->getCommandList( $command, $insertion, $prefix ), $command, $insertion );
      if( !is_array( $results ) ) $results = [ $results ];

      // check the results
      $count = count( $results );
      if( !$count ) throw new Exception\System( self::EXCEPTION_INVALID_RESULT, [ $results ] );
      else foreach( $results as $result ) {
        if( !( $result instanceof Result ) ) throw new Exception\System( self::EXCEPTION_INVALID_RESULT, [ $result ] );
      }

      // call after event
      $extension->trigger( self::EVENT_EXECUTE, [ 'dbq' => &$this, 'dbr' => &$results ] );
      return $count == 1 ? $results[ 0 ] : $results;
    }
  }
  /**
   * Replace insertions and prefixes in the command and explode
   * into commands by semicolon
   *
   * @param string      $command_raw the command or commands ( separated by ; ) to parse
   * @param array       $insertion   insertion array for command like on execute
   * @param string|null $prefix      replacement for {.}
   *
   * @return string[] An array of commands
   */
  public function getCommandList( $command_raw, array $insertion = [ ], $prefix = null ) {

    // define locals
    $prefix        = explode( '.', preg_replace( '/\\.+/i', '.', is_string( $prefix ) ? $prefix : $this->_prefix ) );
    $prefix_length = count( $prefix );

    $command_raw   = trim( $command_raw, ' ' . $this->_separator );
    $command_array = [ ];
    $command_tmp   = '';

    // iterate and parse the whole command
    $delimiter = null;
    for( $i = 0, $length = strlen( $command_raw ); $i < $length; ++$i ) {

      // detect non-quote blocks (start and end)
      $escape = ( $i != 0 && $command_raw[ $i - 1 ] == '\\' );
      if( !$escape ) {

        if( $delimiter == $command_raw[ $i ] ) $delimiter = null;
        else if( !$delimiter && in_array( $command_raw[ $i ], $this->delimiter ) ) $delimiter = $command_raw[ $i ];
      }

      // handle command ends
      if( !$delimiter && $command_raw[ $i ] == $this->_separator ) {
        $command_array[] = $command_tmp;
        $command_tmp     = '';
        continue;
      }

      // insert variable to the command
      if( $command_raw[ $i ] == self::CHARACTER_DATA_START ) {

        $buffer = '';
        for( $j = $i + 1; $j < $length && $command_raw[ $j ] != self::CHARACTER_DATA_END; ++$j ) {
          $buffer .= $command_raw[ $j ];
        }
        $i = $j;

        // instert data to the query
        if( $buffer{0} != self::CHARACTER_DATA_PREFIX ) {

          $quote  = $delimiter || $buffer{0} == self::CHARACTER_DATA_RAW ? '' : $this->quoter;
          $buffer = ltrim( $buffer, self::CHARACTER_DATA_RAW );
          $command_tmp .= $this->quote( array_key_exists( $buffer, $insertion ) ? $insertion[ $buffer ] : null, $quote );

          // insert prefix to the query
        } else {

          $prefix_buffer = [ ];
          for( $j = 0, $buffer_length = strlen( $buffer ); $j < $buffer_length && $j < $prefix_length; ++$j ) {
            $prefix_buffer[] = $prefix[ $j ];
          }

          $command_tmp .= implode( '_', $prefix_buffer );
        }

        continue;
      }

      $command_tmp .= $command_raw[ $i ];
    }

    if( !empty( $command_tmp ) ) $command_array[] = $command_tmp;
    return $command_array;
  }
  /**
   * Get executed result or result list for the given command
   *
   * @param array  $commands    array of command strings
   * @param string $raw_command the command list imploded with semicolon and without insertions or prefix changes
   * @param array  $insertion   the inserted data to the command
   *
   * @return Result|Result[]
   */
  abstract protected function getResultList( array $commands, $raw_command, array $insertion );

  /**
   * Get protected and parsed value that can be inserted
   * to the query with quotes based on type.
   *
   * String: escaped version surrounded by apostrophes
   * Array or Object: converted into ( array[0], array[1], ... ) or if its 2D (array[0][0], array[0][1],...),
   * (array[1][0], array[1][1], .. ),... all array element will be quoted empty arrays ( or objects ) handled as Null
   * Null: values converted to NULL ( without apostrophes ) Boolean: converted into 1 or 0 string Builder: objects
   * converted into a string surrounded by brackets
   *
   * @param mixed  $value the value to quote
   * @param string $mark  the character used to quote strings
   *
   * @return string|number
   */
  public function quote( $value, $mark = null ) {
    if( !is_string( $mark ) ) $mark = $this->quoter;

    if( is_bool( $value ) ) return $value ? '1' : '0';
    else if( is_null( $value ) || is_resource( $value ) ) return 'NULL';
    else if( is_float( $value ) ) return str_replace( ',', '.', (float) $value );
    else if( is_int( $value ) ) return (string) (int) $value;
    else if( is_object( $value ) && $value instanceof Builder ) return "({$value})";
    else if( Enumerable::is( $value ) ) {

      $quoted    = [ ];
      $tmp       = is_object( $value ) ? (array) $value : $value;
      $has_array = false;

      if( !count( $tmp ) ) return 'NULL';
      foreach( $tmp as $v ) {
        if( !$has_array ) $has_array = is_array( $v ) || is_object( $v );
        $quoted[] = $this->quote( $v );
      }

      return ( $has_array ? '' : '(' ) . implode( ',', $quoted ) . ( $has_array ? '' : ')' );
    }

    // anything else is handled as string
    return "{$mark}" . $this->escape( $value ) . "{$mark}";
  }
  /**
   * Get quoted variable names
   *
   * @since 1.4.0
   *
   * @param string $value the value to quote
   * @param string $mark  the character used to quote
   *
   * @return string
   */
  public function quoteName( $value, $mark = null ) {

    if( !is_string( $value ) ) return $value;
    else {

      if( !is_string( $mark ) ) $mark = $this->quoter_name;
      return "{$mark}{$value}{$mark}";
    }
  }
  /**
   * Escape the given parameter
   *
   * @param string $text
   *
   * @return string
   */
  abstract protected function escape( $text );

  /**
   * @since 1.2.0
   *
   * @return Connection
   */
  public function getConnection() {
    return $this->_connection;
  }
  /**
   * @since 1.2.0
   *
   * @return string
   */
  public function getSeparator() {
    return $this->_separator;
  }
  /**
   * @since 1.2.0
   *
   * @return string
   */
  public function getPrefix() {
    return $this->_prefix;
  }
  /**
   * @since 1.2.0
   *
   * @param string $value
   */
  public function setPrefix( $value ) {

    if( $value instanceof Extension ) $this->_prefix = $value->id;
    else if( Extension\Helper::validate( $value ) ) $this->_prefix = $value;
    else if( is_string( $value ) && trim( $value ) !== '' ) $this->_prefix = (string) $value;
    else $this->_prefix = null;
  }

  /**
   * Returns a Query object instance that can be used database operations
   *
   * @param string|null $prefix
   * @param string|null $configuration
   * @param string|null $driver
   *
   * @return Query
   * @throws Exception
   */
  public static function instance( $prefix = null, $configuration = null, $driver = null ) {

    $dbe    = Extension::instance( 'sql' );
    $driver = !empty( $driver ) ? $driver : $dbe->option( 'default:driver' );
    if( !isset( self::$drivers[ $driver ] ) ) {

      // check driver existance
      $namespace = str_replace( '-', '\\', $driver );
      if( !is_subclass_of( $namespace . '\\Connection', self::CLASS_CONNECTION ) || !is_subclass_of( $namespace . '\\Query', self::CLASS_QUERY ) ) {
        throw new Exception\System( self::EXCEPTION_INVALID_DRIVER, [ $driver ] );
      }

      self::$drivers[ $driver ] = $namespace;
    }

    // load class names from the cache
    $configuration    = !empty( $configuration ) ? $configuration : $dbe->option( 'default:configuration', null );
    $class_connection = self::$drivers[ $driver ] . '\\Connection';
    $class_query      = self::$drivers[ $driver ] . '\\Query';

    // create connection object if not already exists
    $index = $driver . ':' . $configuration;
    if( !isset( self::$connections[ $index ] ) ) {
      self::$connections[ $index ] = new $class_connection( $configuration );
    }

    return new $class_query( self::$connections[ $index ], empty( $prefix ) ? null : $prefix );
  }
}
