<?php namespace Spoom\Sql\Expression;

use Spoom\Sql\Connection;
use Spoom\Sql\ConnectionInterface;
use Spoom\Sql\Expression;

//
class Name extends Expression {

  /**
   * @param ConnectionInterface $connection
   * @param string              $definition
   * @param array|null          $argument_list
   * @param bool|null           $quote
   */
  public function __construct( ConnectionInterface $connection, string $definition, ?array $argument_list = null, ?bool $quote = null ) {

    $quote = ( $quote === null && !preg_match( '/^[A-Z0-9_]+$/', $definition ) ) || $quote ? Connection::CHARACTER_DATA_NAME : Connection::CHARACTER_DATA_RAW;

    $command = "{{$quote}definition}";
    $command .= $argument_list === null ? '' : '{argument}';

    parent::__construct( $connection, $command, [
      'definition' => $definition,
      'argument'   => [ $argument_list ]
    ] );
  }
}