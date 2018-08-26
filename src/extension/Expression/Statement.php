<?php namespace Spoom\Sql\Expression;

use Spoom\Core\StorageInterface;
use Spoom\Core\Helper;
use Spoom\Core\Exception;
use Spoom\Sql\ConnectionInterface;
use Spoom\Sql\ExceptionInterface;
use Spoom\Sql\Expression;
use Spoom\Sql\ResultInterface;

//
interface StatementInterface {

  const TABLE_INNER = 'INNER';
  const TABLE_LEFT  = 'LEFT';
  const TABLE_RIGHT = 'RIGHT';

  const SORT_ASC  = 'ASC';
  const SORT_DESC = 'DESC';

  const FILTER_SIMPLE = 'WHERE';
  const FILTER_GROUP  = 'HAVING';

  const CONTEXT_FIELD  = 'field';
  const CONTEXT_FILTER = 'filter';

  /**
   * @return string
   */
  public function __toString();

  /**
   * Executes a select command from the statement contents
   *
   * @param array|object $context
   *
   * @return ResultInterface|ResultInterface[]|null
   * @throws StatementException
   */
  public function search( $context = [] );
  /**
   * Executes an insert command from the statement contents
   *
   * @param array|object $context
   *
   * @return ResultInterface|ResultInterface[]|null
   * @throws StatementException
   */
  public function create( $context = [] );
  /**
   * Executes an update command from the statement contents
   *
   * @param array|object $context
   *
   * @return ResultInterface|ResultInterface[]|null
   * @throws StatementException
   */
  public function update( $context = [] );
  /**
   * Executes a delete command from the statement contents
   *
   * @param array|object $context
   *
   * @return null|ResultInterface|ResultInterface[]
   * @throws StatementException
   */
  public function remove( $context = [] );

  /**
   * Get table(s)
   *
   * @param null|string $alias Get specific table
   *
   * @return array|array[] List of associative array of alias, and table data in { definition: , filter: , type: } format
   */
  public function getTable( ?string $alias = null );
  /**
   * Set tables
   *
   * @param array[] $list Array of { definition: , filter: , type: } objects. The key is the alias
   *
   * @return static
   */
  public function setTable( array $list );
  /**
   * Add new table to the statement
   *
   * @param string|StatementInterface $definition
   * @param string|null               $alias
   * @param string|null               $filter The join contiditons
   * @param string                    $type   it should be inner|left|right
   *
   * @return static
   */
  public function addTable( $definition, ?string $alias = null, $filter = null, string $type = self::TABLE_INNER );
  /**
   * Remove (all) table(s)
   *
   * ..by alias. If no alias specified all table will be removed
   *
   * @param string[]|null $list
   *
   * @return static
   */
  public function removeTable( ?array $list = null );

  /**
   * Get a(ll) field(s)
   *
   * @param string|null $alias A specific field
   *
   * @return array|mixed Field, a list of fields
   */
  public function getField( ?string $alias = null );
  /**
   * Set or add fields
   *
   * @param array $list  Associative list of alias (key) and the field's definition (value)
   * @param bool  $merge Set or merge
   *
   * @return static
   */
  public function setField( array $list, bool $merge = false );
  /**
   * Remove field(s)
   *
   * @param string[]|null $list Fields (alias) to remove (===null to all)
   *
   * @return static
   */
  public function removeField( ?array $list = null );

  /**
   * Get filters
   *
   * @param string $type
   *
   * @return array
   */
  public function getFilter( string $type = self::FILTER_SIMPLE ): array;
  /**
   * Set the filters
   *
   * An array of filters (with AND relation to each other)
   *
   * @param array        $list    List of filters with AND relations
   * @param array|object $context Data to set the static::CONTEXT_FILTER array
   * @param bool         $merge   Replace or extend the existed filters
   * @param string       $type
   *
   * @return static
   */
  public function setFilter( array $list, $context = [], bool $merge = false, string $type = self::FILTER_SIMPLE );
  /**
   * Add a filter
   *
   * @param string       $expression
   * @param array|object $context Data to extend the static::CONTEXT_FILTER array
   * @param string       $type
   *
   * @return static
   */
  public function addFilter( $expression, $context = [], string $type = self::FILTER_SIMPLE );
  /**
   * Remove filters
   *
   * It will clear (all) filter(s) (in the type)
   *
   * @param null|string $type
   * @param null|string $expression
   * @param null|array  $context Data keys to remove from the static::CONTEXT_FILTER array. NULL=== all
   *
   * @return static
   */
  public function removeFilter( ?string $type = null, $expression = null, ?array $context = null );

  /**
   * Get the list of group expressions
   *
   * In [ expression, reverse ] format
   *
   * @return array[]
   */
  public function getGroup(): array;
  /**
   * Set the groups
   *
   * @param array $list list of group expressions in [ expression, reverse ] format
   *
   * @return static
   */
  public function setGroup( array $list );
  /**
   * Add group for the statement
   *
   * @param string $expression
   * @param bool   $reverse
   *
   * @return static
   */
  public function addGroup( $expression, bool $reverse = false );
  /**
   * Remove (all) group(s)
   *
   * @param string[]|StatementInterface[] $list List of expressions to remove
   *
   * @return static
   */
  public function removeGroup( ?array $list = null );

  /**
   * Get the list of sort expressions
   *
   * In [ expression, reverse ] format
   *
   * @return array[]
   */
  public function getSort(): array;
  /**
   * Set the sorts
   *
   * @param array $list list of sort expressions in [ expression, reverse ] format
   *
   * @return static
   */
  public function setSort( array $list );
  /**
   * Add sort for the statement
   *
   * @param string $expression
   * @param bool   $reverse
   *
   * @return static
   */
  public function addSort( $expression, bool $reverse = false );
  /**
   * Remove (all) sort(s)
   *
   * @param string[]|StatementInterface[] $list List of expressions to remove
   *
   * @return static
   */
  public function removeSort( ?array $list = null );

  /**
   * Get the limit
   *
   * @param int $offset
   *
   * @return int
   */
  public function getLimit( ?int &$offset = 0 ): int;
  /**
   * Set the limit for the statement
   *
   * TODO add support for dynamic limits (subquery or something..)
   *
   * @param int $value  The maximum number of items to get
   * @param int $offset The minimum number of items to skip. ===null means no change
   *
   * @return static
   */
  public function setLimit( int $value, ?int $offset = null );

  /**
   * Get flag(s)
   *
   * @param null|string $name Get specific flag
   *
   * @return string|string[]
   */
  public function getFlag( ?string $name = null );
  /**
   * Enable or disable flags for the statement
   *
   * If no parameter specified all flag will be removed
   *
   * @param string|null $name
   * @param bool        $enable
   *
   * @return static
   */
  public function setFlag( ?string $name = null, bool $enable = true );

  /**
   * @param null|string $name
   *
   * @return mixed
   */
  public function getCustom( ?string $name = null ): array;
  /**
   * Add custom definition to the statement
   *
   * like 'ON DUPLICATE UPDATE' or an 'UNION' command
   *
   * @param string $name
   * @param mixed  $definition
   *
   * @return static
   */
  public function addCustom( string $name, $definition );
  /**
   * Remove one or more custom definition from the statement. With no argument, all custom will be removed.
   * If only the name was defined, remove all definition from that name
   *
   * @param string|null $name
   * @param mixed       $definition
   *
   * @return static
   */
  public function removeCustom( ?string $name = null, $definition = null );

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
   * Build a select command from the statement contents
   *
   * @return string
   */
  public function getSelect();
  /**
   * Build an insert command from the statement contents
   *
   * @return string
   */
  public function getInsert();
  /**
   * Build an update command from the statement contents
   *
   * @return string
   */
  public function getUpdate();
  /**
   * Build a delete command from the statement contents
   *
   * @return string
   */
  public function getDelete();
}
/**
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
abstract class Statement extends Expression implements StatementInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * @var array
   */
  private $_table = [];
  /**
   * @var array
   */
  private $_field = [];
  /**
   * @var array[]
   */
  private $_filter = [];

  /**
   * @var array
   */
  private $_group = [];
  /**
   * @var array
   */
  private $_sort = [];

  /**
   * @var int
   */
  private $_limit = 0;
  /**
   * @var int
   */
  private $offset = 0;

  /**
   * @var array
   */
  private $_flag = [];
  /**
   * @var array[]
   */
  private $_custom = [];

  /**
   * @param ConnectionInterface    $connection
   * @param StorageInterface|array $context
   */
  public function __construct( ConnectionInterface $connection, $context = [] ) {
    parent::__construct( $connection, '', $context );

    $this->supportFilter( [ static::FILTER_SIMPLE, static::FILTER_GROUP ] );
  }

  //
  public function __toString() {
    $this->setDefinition( $this->getSelect() );
    return parent::__toString();
  }

  /**
   * Add or remove support for a filter type
   *
   * @param array $list List of types
   * @param bool  $enable
   *
   * @return $this
   */
  protected function supportFilter( array $list = [], bool $enable = true ) {
    foreach( $list as $type ) {
      if( !$enable ) unset( $this->_filter[ $type ] );
      else if( !isset( $this->_filter[ $type ] ) ) $this->_filter[ $type ] = [];
    }

    return $this;
  }
  /**
   * Add or remove support for a custom
   *
   * @param array $list List of customs
   * @param bool  $enable
   *
   * @return $this
   */
  protected function supportCustom( array $list = [], bool $enable = true ) {
    foreach( $list as $name ) {
      if( !$enable ) unset( $this->_custom[ $name ] );
      else if( !isset( $this->_custom[ $name ] ) ) $this->_custom[ $name ] = [];
    }

    return $this;
  }

  //
  public function search( $context = [] ) {
    $this->setDefinition( $this->getSelect() );
    return $this->getConnection()->execute( parent::__toString(), Helper\Collection::merge( clone $this->getContext(), $context ) );
  }
  //
  public function create( $context = [] ) {
    $this->setDefinition( $this->getInsert() );
    return $this->getConnection()->execute( parent::__toString(), Helper\Collection::merge( clone $this->getContext(), $context ) );
  }
  //
  public function update( $context = [] ) {
    $this->setDefinition( $this->getUpdate() );
    return $this->getConnection()->execute( parent::__toString(), Helper\Collection::merge( clone $this->getContext(), $context ) );
  }
  //
  public function remove( $context = [] ) {
    $this->setDefinition( $this->getDelete() );
    return $this->getConnection()->execute( parent::__toString(), Helper\Collection::merge( clone $this->getContext(), $context ) );
  }

  //
  public function getTable( ?string $alias = null ) {
    return $alias === null ? $this->_table : ( $this->_table[ $alias ] ?? null );
  }
  //
  public function setTable( array $list ) {

    $table = [];
    foreach( $list as $alias => $data ) {
      if( !isset( $data[ 'definition' ] ) ) throw new \LogicException( "Table '{$alias}' must contain a 'definition'" );
      else $table[ $alias ] = $data + [ 'filter' => null, 'type' => static::TABLE_INNER ];
    }

    $this->_table = $table;
    return $this;
  }
  //
  public function addTable( $definition, ?string $alias = null, $filter = null, string $type = self::TABLE_INNER ) {

    $alias                  = $alias ? $alias : Helper\Text::read( $definition );
    $this->_table[ $alias ] = [ 'definition' => $definition, 'filter' => $filter, 'type' => $type ];

    return $this;
  }
  //
  public function removeTable( ?array $list = null ) {

    if( $list === null ) $this->_table = [];
    else {

      foreach( $list as $alias ) {
        unset( $this->_table[ $alias ] );
      }
    }

    return $this;
  }

  //
  public function getField( ?string $alias = null ) {
    return $alias === null ? $this->_field : ( $this->_field[ $alias ] ?? null );
  }
  //
  public function setField( array $list, bool $merge = true ) {

    // force aliases from numeric indexes
    $tmp = [];
    foreach( $list as $alias => $field ) {
      $tmp[ is_numeric( $alias ) ? Helper\Text::read( $field ) : $alias ] = $field;
    }
    $list = $tmp;

    $this->_field = $list + ( $merge ? $this->_field : [] );
    return $this;
  }
  //
  public function removeField( ?array $list = null ) {

    // handle simple clear
    if( $list === null ) $this->_field = [];
    else {

      foreach( $list as $alias ) {
        unset( $this->_field[ $alias ] );
      }
    }

    return $this;
  }

  //
  public function getFilter( string $type = self::FILTER_SIMPLE ): array {
    if( !isset( $this->_filter[ $type ] ) ) throw new \LogicException( "There is no support for '{$type}' filters" );
    else return $this->_filter[ $type ];
  }
  //
  public function setFilter( array $list, $context = [], bool $merge = false, string $type = self::FILTER_SIMPLE ) {
    if( !isset( $this->_filter[ $type ] ) ) throw new \LogicException( "There is no support for '{$type}' filters" );
    else {

      $this->_filter[ $type ] = $merge ? ( $list + $this->_filter[ $type ] ) : $list;

      // handle the context
      if( !$merge ) unset( $this->getContext()[ static::CONTEXT_FILTER . '.' . $type ] );
      Helper\Collection::merge( $this->getContext(), [
        static::CONTEXT_FILTER => [
          $type => $context
        ]
      ] );

      return $this;
    }
  }
  //
  public function addFilter( $expression, $context = [], string $type = self::FILTER_SIMPLE ) {
    if( !isset( $this->_filter[ $type ] ) ) throw new \LogicException( "There is no support for '{$type}' filters" );
    else {

      $this->_filter[ $type ][] = $expression;

      // handle the context
      Helper\Collection::merge( $this->getContext(), [
        static::CONTEXT_FILTER => [
          $type => $context
        ]
      ] );

      return $this;
    }
  }
  //
  public function removeFilter( ?string $type = null, $expression = null, ?array $context = null ) {

    // remove expressions
    if( $type === null ) $this->_filter = array_fill_keys( array_keys( $this->_filter ), [] );
    else if( !isset( $this->_filter[ $type ] ) ) throw new \LogicException( "There is no support for '{$type}' filters" );
    else if( $expression === null ) $this->_filter[ $type ] = [];
    else {

      $index = array_search( $expression, $this->_filter[ $type ], true );
      if( $index !== false ) array_splice( $this->_filter[ $type ], $index, 1 );
    }

    // remove the context too 
    $index = static::CONTEXT_FILTER . ( $type ? ".{$type}" : '' );
    if( $context === null ) unset( $this->getContext()[ $index ] );
    else foreach( $context as $name ) {
      unset( $this->getContext()[ $index . '.' . $name ] );
    }

    return $this;
  }

  //
  public function getGroup(): array {
    return $this->_group;
  }
  //
  public function setGroup( array $list ) {

    // validate the input
    $tmp = [];
    foreach( $list as $item ) {
      if( !is_array( $item ) || count( $item ) != 2 ) throw new \InvalidArgumentException( 'List item must be an array with two elements' );
      else $tmp[] = array_values( $item );
    }

    $this->_group = $tmp;
  }
  //
  public function addGroup( $expression, bool $reverse = false ) {
    $this->_group[] = [ $expression, $reverse ];
  }
  //
  public function removeGroup( ?array $list = null ) {

    // 
    if( $list === null ) $this->_group = [];
    else {

      // remove groups that's in the list
      foreach( $this->_group as $i => $item ) {
        if( in_array( $item[ 0 ], $list, true ) ) {
          array_splice( $this->_group, $i, 1 );
        }
      }
    }
  }

  //
  public function getSort(): array {
    return $this->_sort;
  }
  //
  public function setSort( array $list ) {

    // validate the input
    $tmp = [];
    foreach( $list as $item ) {
      if( !is_array( $item ) || count( $item ) != 2 ) throw new \InvalidArgumentException( 'List item must be an array with two elements' );
      else $tmp[] = array_values( $item );
    }

    $this->_sort = $tmp;
  }
  //
  public function addSort( $expression, bool $reverse = false ) {
    $this->_sort[] = [ $expression, $reverse ];
  }
  //
  public function removeSort( ?array $list = null ) {

    // 
    if( $list === null ) $this->_sort = [];
    else {

      // remove sorts that's in the list
      foreach( $this->_sort as $i => $item ) {
        if( in_array( $item[ 0 ], $list, true ) ) {
          array_splice( $this->_sort, $i, 1 );
        }
      }
    }
  }

  //
  public function getLimit( ?int &$offset = 0 ): int {

    $offset = $this->offset;
    return $this->_limit;
  }
  //
  public function setLimit( int $value, ?int $offset = null ) {

    $this->_limit = $value;
    if( $offset !== null ) {
      $this->offset = $offset;
    }

    return $this;
  }

  //
  public function setFlag( ?string $name = null, bool $enable = true ) {

    if( $name === null ) $this->_flag = [];
    else if( $enable ) $this->_flag[ $name ] = $name;
    else unset( $this->_flag[ $name ] );

    return $this;
  }
  //
  public function getFlag( ?string $name = null ) {
    return $name === null ? $this->_flag : ( $this->_flag[ $name ] ?? null );
  }

  //
  public function getCustom( ?string $name = null ): array {
    if( !isset( $this->_custom[ $name ] ) ) throw new \LogicException( "There is no support for '{$name}' customs" );
    else return $this->_custom[ $name ];
  }
  //
  public function addCustom( string $name, $definition ) {
    if( !isset( $this->_custom[ $name ] ) ) throw new \LogicException( "There is no support for '{$name}' customs" );
    else {

      $this->_custom[ $name ][] = $definition;
      return $this;
    }
  }
  //
  public function removeCustom( ?string $name = null, $definition = null ) {

    if( $name === null ) $this->_custom = array_fill_keys( array_keys( $this->_custom ), [] );
    if( !isset( $this->_custom[ $name ] ) ) throw new \LogicException( "There is no support for '{$name}' customs" );
    else if( $definition === null ) $this->_custom[ $name ] = [];
    else {

      $index = array_search( $definition, $this->_custom[ $name ], true );
      if( $index !== false ) array_splice( $this->_custom[ $name ], $index, 1 );
    }

    return $this;
  }

  //
  protected function setDefinition( string $value ) {
    return parent::setDefinition( '(' . $value . ')' );
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
