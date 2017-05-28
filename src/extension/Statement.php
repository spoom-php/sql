<?php namespace Spoom\Sql;

use Spoom\Core\Storage;
use Spoom\Core\StorageInterface;
use Spoom\Core\Helper;
use Spoom\Core\Exception;

/**
 * Interface StatementInterface
 *
 * TODO create unittest
 */
interface StatementInterface {

  const TABLE_INNER = 'INNER';
  const TABLE_LEFT  = 'LEFT';
  const TABLE_RIGHT = 'RIGHT';

  const OPERATOR_NONE           = '{0}';
  const OPERATOR_EQUAL          = '{0} = {1}';
  const OPERATOR_EQUAL_NOT      = '{0} <> {1}';
  const OPERATOR_EQUAL_MULTIPLE = '{0} IN {1}';
  const OPERATOR_GREATER        = '{0} > {1}';
  const OPERATOR_GREATER_EQUAL  = '{0} >= {1}';
  const OPERATOR_SMALLER        = '{0} < {1}';
  const OPERATOR_SMALLER_EQUAL  = '{0} <= {1}';
  const OPERATOR_BETWEEN        = '{0} BETWEEN {1} AND {2}';
  const OPERATOR_PATTERN        = '{0} LIKE {1}';
  const OPERATOR_ALIAS          = '{0} AS {1}';
  const OPERATOR_NOT            = 'NOT {0}';
  const OPERATOR_AND            = '{0} AND {1}';
  const OPERATOR_OR             = '{0} OR {1}';

  const SORT_ASC  = 'ASC';
  const SORT_DESC = 'DESC';

  const FILTER_SIMPLE = 'WHERE';
  const FILTER_GROUP  = 'HAVING';

  /**
   * @return string
   */
  public function __toString();

  /**
   * Add new table to the builder. If no alias specified the definition will be used when it's a string ( else the
   * table will not be stored ). If condition is a string, the table will be joined with type and condition.
   *
   * @param string|Statement $definition
   * @param string|null      $alias
   * @param string|null      $expression
   * @param string           $type it should be inner|left|right
   *
   * @return self
   */
  public function addTable( $definition, $alias = null, $expression = null, $type = self::TABLE_INNER );
  /**
   * Remove table from builder by alias ( or definition ). If no alias specified all table will be removed.
   *
   * @param string $alias
   *
   * @return self
   */
  public function removeTable( $alias = null );
  /**
   * Add new field ( or fields ) to the builder. If no alias specified the definition will be used when it's a string (
   * else the field will not be stored ). When the argument is an array, the key will be the alias ( if not numeric )
   *
   * @param string|string[]|Statement|Statement[] $definition
   * @param string|null                           $alias
   *
   * @return self
   */
  public function addField( $definition, $alias = null );
  /**
   * Remove field ( or fields ) from builder by alias ( or definition ). If no alias specified all field will be
   * removed.
   *
   * @param string|string[] $alias
   *
   * @return self
   */
  public function removeField( $alias = null );

  /**
   * Add new filter to the builder.
   *
   * @param string $expression the filter definition
   * @param string $glue       the glue BEFORE the expression
   * @param string $type       the filter type ( eg: where or having )
   *
   * @return self
   */
  public function addFilter( $expression, $glue = self::OPERATOR_AND, $type = self::FILTER_SIMPLE );
  /**
   * Remove expression from the filter type. If no expression specified all filter remove from the type.
   *
   * @param string|null $type this should be where|having or null if remove all filter types
   * @param string|null $expression
   *
   * @return self
   */
  public function removeFilter( $type = self::FILTER_SIMPLE, $expression = null );

  /**
   * Add new group to the builder
   *
   * @param string|Statement $expression
   * @param string|null      $sort
   *
   * @return self
   */
  public function addGroup( $expression, $sort = null );
  /**
   * Remove group or groups from the builder. If the definition is null all group will be removed
   *
   * @param string|string[]|Statement|Statement[] $expression
   *
   * @return self
   */
  public function removeGroup( $expression = null );
  /**
   * Add new order to the builder
   *
   * @param string|Statement $definition
   * @param string|null      $sort
   *
   * @return self
   */
  public function addOrder( $definition, $sort = self::SORT_ASC );
  /**
   * Remove order ( or orders ) from the builder. If the definition is null all order will be removed
   *
   * @param string|string[]|Statement|Statement[] $definition
   *
   * @return self
   */
  public function removeOrder( $definition = null );

  /**
   * Set the builder limit with offset and count values. Each param will converted to int.
   * If count is zero, the limit will be omitted
   *
   * @param int|null $count
   * @param int      $offset
   *
   * @return self
   */
  public function setLimit( $count = null, $offset = 0 );

  /**
   * Enable or disable flags for the builder. If no parameter specified all flag will be removed.
   *
   * @param string|null $name
   * @param bool        $enable
   *
   * @return self
   */
  public function setFlag( $name = null, $enable = true );

  /**
   * Add custom definition to the builder, like 'ON DUPLICATE UPDATE' or an 'UNION' command
   *
   * @param string $name
   * @param mixed  $definition
   *
   * @return self
   */
  public function addCustom( $name, $definition );
  /**
   * Remove one or more custom definition from the builder. With no argument, all custom will be removed.
   * If only the name was defined, remove all definition from that name
   *
   * @param string|null $name
   * @param mixed       $definition
   *
   * @return self
   */
  public function removeCustom( $name = null, $definition = null );

  /**
   * Executes a select command from the builder contents
   *
   * @param array|object $context
   *
   * @return ResultInterface|ResultInterface[]|null
   * @throws StatementException
   */
  public function search( $context = [] );
  /**
   * Executes an insert command from the builder contents
   *
   * @param array|object $context
   *
   * @return ResultInterface|ResultInterface[]|null
   * @throws StatementException
   */
  public function create( $context = [] );
  /**
   * Executes an update command from the builder contents
   *
   * @param array|object $context
   *
   * @return ResultInterface|ResultInterface[]|null
   * @throws StatementException
   */
  public function update( $context = [] );
  /**
   * Executes a delete command from the builder contents
   *
   * @param array|object $context
   *
   * @return null|ResultInterface|ResultInterface[]
   * @throws StatementException
   */
  public function remove( $context = [] );

  /**
   * @return ConnectionInterface
   */
  public function getConnection();
  /**
   * Base context for the statement when execute
   *
   * @return StorageInterface
   */
  public function getContext(): StorageInterface;
  /**
   * @since 1.2.0
   *
   * @return array
   */
  public function getTableList();
  /**
   * @since 1.2.0
   *
   * @return array
   */
  public function getFieldList();
  /**
   * @since 1.2.0
   *
   * @return array
   */
  public function getFilterList();
  /**
   * @since 1.2.0
   *
   * @return array
   */
  public function getGroupList();
  /**
   * @since 1.2.0
   *
   * @return array
   */
  public function getOrderList();
  /**
   * @since 1.2.0
   *
   * @return array
   */
  public function getLimit();
  /**
   * @since 1.2.0
   *
   * @return array
   */
  public function getFlagList();
  /**
   * @since 1.2.0
   *
   * @return array
   */
  public function getCustomList();

  /**
   * Build a select command from the builder contents
   *
   * @return string
   */
  public function getSelect();
  /**
   * Build an insert command from the builder contents
   *
   * @return string
   */
  public function getInsert();
  /**
   * Build an update command from the builder contents
   *
   * @return string
   */
  public function getUpdate();
  /**
   * Build a delete command from the builder contents
   *
   * @return string
   */
  public function getDelete();
}
/**
 * Class Statement
 *
 * @property-read ConnectionInterface $connection
 * @property-read array               $table_list
 * @property-read array               $field_list
 * @property-read array               $filter_list
 * @property-read array               $group_list
 * @property-read array               $order_list
 * @property-read array               $limit
 * @property-read array               $custom_list
 * @property-read array               $flag_list
 */
abstract class Statement implements StatementInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * The Connection object that will be used to execute the builded command
   *
   * @var ConnectionInterface
   */
  private $_connection = null;
  /**
   * @var StorageInterface|null
   */
  private $_context = null;

  /**
   * Store the tables in a alias => tablename structure.
   * If the table has no alias, then the alias will be
   * the table name ( tablename => tablename array item )
   *
   * @var array
   */
  private $_table_list = [];
  /**
   * Store the fields in a alias => fieldname structure.
   * If the field has no alias, then the alias will be
   * the field name ( fieldname => fieldname array item )
   *
   * @var array
   */
  private $_field_list = [];
  /**
   * Store the filters in a type => [ definition => glue, ... ]
   * structure, where the type is the filter type ( eg: where or having )
   * and definition is the filter string ( eg: field > 200 ) and the glue
   * is an 'OR' or 'AND' should placed BEFORE the filter string
   *
   * @var array
   */
  private $_filter_list = [];

  /**
   * Store the groups of the query
   *
   * @var array
   */
  private $_group_list = [];
  /**
   * Store the order fields in  a field => ordering structure, where the ordering is 'ASC' or 'DESC'
   *
   * @var array
   */
  private $_order_list = [];

  /**
   * Store the limit in an array like [ offset, max ]
   *
   * @var array
   */
  private $_limit = [ 0, 0 ];

  /**
   * Store the flags in an array
   *
   * @var array
   */
  private $_flag_list = [];
  /**
   * Stores custom properties
   *
   * @var array
   */
  private $_custom_list = [];

  /**
   * @param ConnectionInterface    $connection
   * @param StorageInterface|array $context
   */
  public function __construct( ConnectionInterface $connection, $context = [] ) {
    $this->_connection = $connection;
    $this->_context    = Storage::instance( $context );
  }

  //
  public function __toString() {
    return $this->_connection->apply( $this->getSelect(), $this->getContext() );
  }

  //
  public function addTable( $definition, $alias = null, $condition = null, $type = self::TABLE_INNER ) {

    if( $alias || is_string( $definition ) ) {

      $alias = $alias ? $alias : $definition;
      if( !$this->existAlias( $alias, $this->_table_list ) ) {
        $this->_table_list[ $alias ] = is_string( $condition ) ? [ 'definition' => $definition, 'condition' => $condition, 'type' => $type ] : $definition;
      }
    }

    return $this;
  }
  //
  public function removeTable( $alias = null ) {
    if( !is_string( $alias ) ) $this->_table_list = [];
    else unset( $this->_table_list[ $alias ] );

    return $this;
  }

  //
  public function addField( $definitions, $alias = null ) {

    if( !is_array( $definitions ) ) $definitions = is_string( $alias ) ? [ $alias => $definitions ] : [ $definitions ];

    foreach( $definitions as $alias => $definition ) {

      $alias = is_numeric( $alias ) ? null : $alias;
      if( is_string( $alias ) || is_string( $definition ) ) {

        $alias = is_string( $alias ) ? $alias : $definition;
        if( !$this->existAlias( $alias, $this->_field_list ) ) {
          $this->_field_list[ $alias ] = $definition;
        }
      }
    }

    return $this;
  }
  //
  public function removeField( $aliases = null ) {

    if( $aliases == null ) $this->_field_list = [];
    else {

      $aliases = is_array( $aliases ) ? $aliases : [ $aliases ];
      foreach( $aliases as $alias ) if( is_string( $alias ) ) {
        unset( $this->_field_list[ $alias ] );
      }
    }

    return $this;
  }

  //
  public function addFilter( $expression, $glue = self::OPERATOR_AND, $type = self::FILTER_SIMPLE ) {
    if( is_string( $expression ) || $expression instanceof Statement ) {

      $type = strtolower( $type );
      if( !isset( $this->_filter_list[ $type ] ) ) $this->_filter_list[ $type ] = [];

      $this->_filter_list[ $type ][] = [ 'expression' => $expression, 'glue' => $glue ];
    }

    return $this;
  }
  //
  public function removeFilter( $type = self::FILTER_SIMPLE, $expression = null ) {

    if( empty( $type ) ) $this->_filter_list = [];
    else if( isset( $this->_filter_list[ $type ] ) ) {

      if( !is_string( $expression ) ) unset( $this->_filter_list[ $type ] );
      else foreach( $this->_filter_list[ $type ] as $index => $filter ) {
        if( $filter[ 'expression' ] == $expression ) unset( $this->_filter_list[ $type ][ $index ] );
      }
    }

    return $this;
  }

  //
  public function addGroup( $definition, $sort = null ) {
    $this->_group_list[] = [ $definition, $sort ];

    return $this;
  }
  //
  public function removeGroup( $definitions = null ) {

    if( !$definitions ) $this->_group_list = [];
    else {
      $definitions = is_array( $definitions ) ? $definitions : [ $definitions ];

      foreach( $definitions as $definition ) {
        foreach( $this->_group_list as $index => $group ) if( $group[ 0 ] === $definition ) {
          unset( $this->_group_list[ $index ] );
        }
      }
    }

    return $this;
  }

  //
  public function addOrder( $definition, $sort = self::SORT_ASC ) {
    $this->_order_list[] = [ $definition, $sort ];

    return $this;
  }
  //
  public function removeOrder( $definitions = null ) {

    if( !$definitions ) $this->_order_list = [];
    else {
      $definitions = is_array( $definitions ) ? $definitions : [ $definitions ];

      foreach( $definitions as $definition ) {
        foreach( $this->_order_list as $index => $order ) if( $order[ 0 ] === $definition ) {
          unset( $this->_order_list[ $index ] );
        }
      }
    }

    return $this;
  }

  //
  public function setLimit( $count = null, $offset = 0 ) {
    $this->_limit = [ (int) $offset, (int) $count ];

    return $this;
  }

  //
  public function setFlag( $name = null, $enable = true ) {

    if( !is_string( $name ) ) $this->_flag_list = [];
    else {

      if( $enable ) $this->_flag_list[ $name ] = $name;
      else {
        $index = array_search( $name, $this->_flag_list );
        unset( $this->_flag_list[ $index ] );
      }
    }

    return $this;
  }

  //
  public function addCustom( $name, $definition ) {
    if( is_string( $name ) && isset( $definition ) ) {
      $name = strtolower( $name );
      if( !isset( $this->_custom_list[ $name ] ) ) $this->_custom_list[ $name ] = [];
      $this->_custom_list[ $name ][] = $definition;
    }

    return $this;
  }
  //
  public function removeCustom( $name = null, $definition = null ) {
    if( !is_string( $name ) ) $this->_custom_list = [];
    else if( !$definition ) unset( $this->_custom_list[ strtolower( $name ) ] );
    else if( isset( $this->_custom_list[ strtolower( $name ) ] ) ) {
      $name  = strtolower( $name );
      $index = array_search( $definition, $this->_custom_list[ $name ], true );
      if( $index >= 0 ) {
        unset( $this->_custom_list[ $name ][ $index ] );

        // only names with definitions in it will remain
        if( !count( $this->_custom_list[ $name ] ) ) unset( $this->_custom_list[ $name ] );
      }
    }

    return $this;
  }

  /**
   * Check if the alias exist in tables, fields
   *
   * @param string     $alias   The alias to check
   * @param array|null $storage The storage to check in (or null if _tables and _fields)
   *
   * @return bool
   */
  protected function existAlias( $alias, $storage = null ) {

    if( $storage === null ) return array_key_exists( $alias, $this->_table_list ) || array_key_exists( $alias, $this->_field_list );
    else return array_key_exists( $alias, $storage );
  }

  //
  public function search( $context = [] ) {
    return $this->_connection->execute( $this->getSelect(), Helper\Collection::merge( clone $this->getContext(), $context ) );
  }
  //
  public function create( $context = [] ) {
    return $this->_connection->execute( $this->getInsert(), Helper\Collection::merge( clone $this->getContext(), $context ) );
  }
  //
  public function update( $context = [] ) {
    return $this->_connection->execute( $this->getUpdate(), Helper\Collection::merge( clone $this->getContext(), $context ) );
  }
  //
  public function remove( $context = [] ) {
    return $this->_connection->execute( $this->getDelete(), Helper\Collection::merge( clone $this->getContext(), $context ) );
  }

  //
  public function getConnection() {
    return $this->_connection;
  }
  //
  public function getContext(): StorageInterface {
    return $this->_context;
  }
  //
  public function getTableList() {
    return $this->_table_list;
  }
  //
  public function getFieldList() {
    return $this->_field_list;
  }
  //
  public function getFilterList() {
    return $this->_filter_list;
  }
  //
  public function getGroupList() {
    return $this->_group_list;
  }
  //
  public function getOrderList() {
    return $this->_order_list;
  }
  //
  public function getLimit() {
    return $this->_limit;
  }
  //
  public function getFlagList() {
    return $this->_flag_list;
  }
  //
  public function getCustomList() {
    return $this->_custom_list;
  }
}

/**
 * Unsuccessful statement execution on the database server
 *
 * TODO create custom exception(s) for some basic SQL errors
 */
class StatementException extends Exception\Logic implements ExceptionInterface {

  const ID = '7#spoom-sql';

  /**
   * @param string              $statement
   * @param ConnectionInterface $connection
   * @param null|\Throwable     $exception
   */
  public function __construct( string $statement, ConnectionInterface $connection, ?\Throwable $exception = null ) {

    $data = [ 'connection' => $connection, 'statement' => $statement, 'error' => $exception->getMessage() ];
    parent::__construct(
      Helper\Text::apply( "Failed to execute statement(s) on '{connection.authentication}@{connection.uri}', due to: {error}", $data ),
      static::ID,
      $data,
      $exception
    );
  }
}
