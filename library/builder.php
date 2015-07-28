<?php namespace Sql;

use Framework\Storage;

/**
 * Class Builder
 * @package Sql\Query
 *
 * @property-read Query $dbq The query object that runs the command
 * @property-read array $tables
 * @property-read array $fields
 * @property-read array $filters
 * @property-read array $groups
 * @property-read array $joins
 * @property-read array $orders
 * @property-read array $limit
 * @property-read array $customs
 * @property-read array $flags
 */
abstract class Builder extends Storage {

  /**
   * The Query object that will be used to execute the builded command
   *
   * @var Query
   */
  private $_dbq = null;

  /**
   * Store the tables in a alias => tablename structure.
   * If the table has no alias, then the alias will be
   * the table name ( tablename => tablename array item )
   *
   * @var array
   */
  private $_tables = [ ];
  /**
   * Store the fields in a alias => fieldname structure.
   * If the field has no alias, then the alias will be
   * the field name ( fieldname => fieldname array item )
   *
   * @var array
   */
  private $_fields = [ ];
  /**
   * Store the filters in a type => [ definition => glue, ... ]
   * structure, where the type is the filter type ( eg: where or having )
   * and definition is the filter string ( eg: field > 200 ) and the glue
   * is an 'OR' or 'AND' should placed BEFORE the filter string
   *
   * @var array
   */
  private $_filters = [ ];

  /**
   * Store the groups of the query
   *
   * @var array
   */
  private $_groups = [ ];
  /**
   * Store the order fields in  a field => ordering structure, where the ordering is 'ASC' or 'DESC'
   *
   * @var array
   */
  private $_orders = [ ];

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
  private $_flags = [ ];
  /**
   * Stores custom properties
   *
   * @var array
   */
  private $_customs = [ ];

  /**
   * @param Query $dbq
   */
  public function __construct( Query $dbq ) {
    parent::__construct( 'insertion' );

    $this->_dbq = $dbq;
  }

  /**
   * @return string
   */
  public function __toString() {

    $commands = $this->_dbq->getCommandList( $this->getSelect(), $this->getArray( '' ) );
    return implode( $this->_dbq->_separator, $commands );
  }

  /**
   * Add new table to the builder. If no alias specified the definition will be used when it's a string ( else the
   * table will not be stored ). If condition is a string, the table will be joined with type and condition.
   *
   * @param string|Builder $definition
   * @param string|null    $alias
   * @param string|null    $condition
   * @param string         $type it should be inner|left|right
   *
   * @return self
   */
  public function addTable( $definition, $alias = null, $condition = null, $type = 'inner' ) {

    if( $alias || is_string( $definition ) ) {

      $alias = $alias ? $alias : $definition;
      if( !$this->existAlias( $alias, $this->_tables ) ) {
        $this->_tables[ $alias ] = is_string( $condition ) ? [ 'definition' => $definition, 'condition' => $condition, 'type' => $type ] : $definition;
      }
    }

    return $this;
  }
  /**
   * Remove table from builder by alias ( or definition ). If no alias specified all table will be removed.
   *
   * @param string $alias
   *
   * @return self
   */
  public function removeTable( $alias = null ) {
    if( !is_string( $alias ) ) $this->_tables = [ ];
    else unset( $this->_tables[ $alias ] );

    return $this;
  }

  /**
   * Add new field ( or fields ) to the builder. If no alias specified the definition will be used when it's a string (
   * else the field will not be stored ). When the argument is an array, the key will be the alias ( if not numeric )
   *
   * @param string|string[]|Builder|Builder[] $definitions
   * @param string|null                       $alias
   *
   * @return self
   */
  public function addField( $definitions, $alias = null ) {

    if( !is_array( $definitions ) ) $definitions = is_string( $alias ) ? [ $alias => $definitions ] : [ $definitions ];

    foreach( $definitions as $alias => $definition ) {

      $alias = is_numeric( $alias ) ? null : $alias;
      if( is_string( $alias ) || is_string( $definition ) ) {

        $alias = is_string( $alias ) ? $alias : $definition;
        if( !$this->existAlias( $alias, $this->_fields ) ) {
          $this->_fields[ $alias ] = $definition;
        }
      }
    }

    return $this;
  }
  /**
   * Remove field ( or fields ) from builder by alias ( or definition ). If no alias specified all field will be
   * removed.
   *
   * @param string|string[] $aliases
   *
   * @return self
   */
  public function removeField( $aliases = null ) {

    if( $aliases == null ) $this->_fields = [ ];
    else {

      $aliases = is_array( $aliases ) ? $aliases : [ $aliases ];
      foreach( $aliases as $alias ) if( is_string( $alias ) ) {
        unset( $this->_fields[ $alias ] );
      }
    }

    return $this;
  }

  /**
   * Add new filter to the builder.
   *
   * @param string $expression the filter definition
   * @param string $glue       the glue BEFORE the expression
   * @param string $type       the filter type ( eg: where or having )
   *
   * @return self
   */
  public function addFilter( $expression, $glue = 'AND', $type = 'where' ) {
    if( is_string( $expression ) ) {

      $type = strtolower( $type );
      if( !isset( $this->_filters[ $type ] ) ) $this->_filters[ $type ] = [ ];

      $this->_filters[ $type ][ ] = [ 'expression' => $expression, 'glue' => $glue ];
    }

    return $this;
  }
  /**
   * Remove expression from the filter type. If no expression specified all filter remove from the type.
   *
   * @param string|null $type this should be where|having or null if remove all filter types
   * @param string|null $expression
   *
   * @return self
   */
  public function removeFilter( $type = 'where', $expression = null ) {

    if( empty( $type ) ) $this->_filters = [ ];
    else if( isset( $this->_filters[ $type ] ) ) {

      if( !is_string( $expression ) ) unset( $this->_filters[ $type ] );
      else foreach( $this->_filters[ $type ] as $index => $filter ) {
        if( $filter[ 'expression' ] == $expression ) unset( $this->_filters[ $type ][ $index ] );
      }
    }

    return $this;
  }

  /**
   * Add new group to the builder
   *
   * @param string|Builder $definition
   * @param string|null    $sort
   *
   * @return self
   */
  public function addGroup( $definition, $sort = null ) {
    $this->_groups[ ] = [ $definition, $sort ];

    return $this;
  }
  /**
   * Remove group or groups from the builder. If the definition is null all group will be removed
   *
   * @param string|string[]|Builder|Builder[] $definitions
   *
   * @return self
   */
  public function removeGroup( $definitions = null ) {

    if( !$definitions ) $this->_groups = [ ];
    else {
      $definitions = is_array( $definitions ) ? $definitions : [ $definitions ];

      foreach( $definitions as $definition ) {
        foreach( $this->_groups as $index => $group ) if( $group[ 0 ] === $definition ) {
          unset( $this->_groups[ $index ] );
        }
      }
    }

    return $this;
  }

  /**
   * Add new order to the builder
   *
   * @param string|Builder $definition
   * @param string|null    $sort
   *
   * @return self
   */
  public function addOrder( $definition, $sort = 'ASC' ) {
    $this->_orders[ ] = [ $definition, $sort ];

    return $this;
  }
  /**
   * Remove order ( or orders ) from the builder. If the definition is null all order will be removed
   *
   * @param string|string[]|Builder|Builder[] $definitions
   *
   * @return self
   */
  public function removeOrder( $definitions = null ) {

    if( !$definitions ) $this->_orders = [ ];
    else {
      $definitions = is_array( $definitions ) ? $definitions : [ $definitions ];

      foreach( $definitions as $definition ) {
        foreach( $this->_orders as $index => $order ) if( $order[ 0 ] === $definition ) {
          unset( $this->_orders[ $index ] );
        }
      }
    }

    return $this;
  }

  /**
   * Set the builder limit with offset and count values. Each param will converted to int.
   * If count is zero, the limit will be omitted
   *
   * @param int|null $count
   * @param int      $offset
   *
   * @return self
   */
  public function setLimit( $count = null, $offset = 0 ) {
    $this->_limit = [ (int) $offset, (int) $count ];

    return $this;
  }

  /**
   * Enable or disable flags for the builder. If no parameter specified all flag will be removed.
   *
   * @param string|null $name
   * @param bool        $enable
   *
   * @return self
   */
  public function setFlag( $name = null, $enable = true ) {

    if( !is_string( $name ) ) $this->_flags = [ ];
    else {

      if( $enable ) $this->_flags[ $name ] = $name;
      else {
        $index = array_search( $name, $this->_flags );
        unset( $this->_flags[ $index ] );
      }
    }

    return $this;
  }

  /**
   * Add custom definition to the builder, like 'ON DUPLICATE UPDATE' or an 'UNION' command
   *
   * @param string $name
   * @param mixed  $definition
   *
   * @return self
   */
  public function addCustom( $name, $definition ) {
    if( is_string( $name ) && isset( $definition ) ) {
      $name = strtolower( $name );
      if( !isset( $this->_customs[ $name ] ) ) $this->_customs[ $name ] = [ ];
      $this->_customs[ $name ][ ] = $definition;
    }

    return $this;
  }
  /**
   * Remove one or more custom definition from the builder. With no argument, all custom will be removed.
   * If only the name was defined, remove all definition from that name
   *
   * @param string|null $name
   * @param mixed       $definition
   *
   * @return self
   */
  public function removeCustom( $name = null, $definition = null ) {
    if( !is_string( $name ) ) $this->_customs = [ ];
    else if( !$definition ) unset( $this->_customs[ strtolower( $name ) ] );
    else if( isset( $this->_customs[ strtolower( $name ) ] ) ) {
      $name  = strtolower( $name );
      $index = array_search( $definition, $this->_customs[ $name ], true );
      if( $index >= 0 ) {
        unset( $this->_customs[ $name ][ $index ] );

        // only names with definitions in it will remain
        if( !count( $this->_customs[ $name ] ) ) unset( $this->_customs[ $name ] );
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

    if( $storage === null ) return array_key_exists( $alias, $this->_tables ) || array_key_exists( $alias, $this->_fields );
    else return array_key_exists( $alias, $storage );
  }

  /**
   * Executes a select command from the builder contents
   *
   * @return Result|Result[]|null
   * @throws \Framework\Exception\Strict
   * @throws \Framework\Exception\System
   */
  public function select() {
    return $this->_dbq->execute( $this->getSelect(), $this->getArray( 'insertion:' ) );
  }
  /**
   * Executes an insert command from the builder contents
   *
   * @return Result|Result[]|null
   * @throws \Framework\Exception\Strict
   * @throws \Framework\Exception\System
   */
  public function insert() {
    return $this->_dbq->execute( $this->getInsert(), $this->getArray( 'insertion:' ) );
  }
  /**
   * Executes an update command from the builder contents
   *
   * @return Result|Result[]|null
   * @throws \Framework\Exception\Strict
   * @throws \Framework\Exception\System
   */
  public function update() {
    return $this->_dbq->execute( $this->getUpdate(), $this->getArray( 'insertion:' ) );
  }
  /**
   * Executes a delete command from the builder contents
   *
   * @return Result|Result[]|null
   * @throws \Framework\Exception\Strict
   * @throws \Framework\Exception\System
   */
  public function delete() {
    return $this->_dbq->execute( $this->getDelete(), $this->getArray( 'insertion:' ) );
  }

  /**
   * @return Query
   */
  public function getDbq() {
    return $this->_dbq;
  }
  /**
   * @return array
   */
  public function getTables() {
    return $this->_tables;
  }
  /**
   * @return array
   */
  public function getFields() {
    return $this->_fields;
  }
  /**
   * @return array
   */
  public function getFilters() {
    return $this->_filters;
  }
  /**
   * @return array
   */
  public function getGroups() {
    return $this->_groups;
  }
  /**
   * @return array
   */
  public function getOrders() {
    return $this->_orders;
  }
  /**
   * @return array
   */
  public function getLimit() {
    return $this->_limit;
  }
  /**
   * @return array
   */
  public function getFlags() {
    return $this->_flags;
  }
  /**
   * @return array
   */
  public function getCustoms() {
    return $this->_customs;
  }

  /**
   * Build a select command from the builder contents
   *
   * @return string
   */
  abstract public function getSelect();
  /**
   * Build an insert command from the builder contents
   *
   * @return string
   */
  abstract public function getInsert();
  /**
   * Build an update command from the builder contents
   *
   * @return string
   */
  abstract public function getUpdate();
  /**
   * Build a delete command from the builder contents
   *
   * @return string
   */
  abstract public function getDelete();
}
