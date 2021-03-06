<?php namespace Spoom\Sql;

use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase {

  /**
   * Test data and name quoting
   */
  public function testQuote() {
    $connection = new ConnectionMock();

    // test name quoting
    $this->assertEquals( '`test`.`test1`', $connection->quoteName( 'test.test1' ) );
    $this->assertEquals( '`test`.`test1`', $connection->quoteName( 'test.`test1`' ) );
    $this->assertEquals( '`test`', $connection->quoteName( 'test' ) );
    $this->assertEquals( '(`test`,`test`.`test1`)', $connection->quoteName( [ 'test', 'test.test1' ] ) );

    // test data quoting
    $this->assertEquals( '(0,1)', $connection->quote( [ false, true ] ) );
    $this->assertEquals( '(123,2.1)', $connection->quote( [ 123, 2.1 ] ) );
    $this->assertEquals( '123,2.1', $connection->quote( [ 123, 2.1 ], false ), 'This shouldn\'t be enclosed by parenthesis' );
    $this->assertEquals( 'NULL,NULL', $connection->quote( [ null, [] ] ) );
    $this->assertEquals( "('a','b'),('c','d')", $connection->quote( [ [ 'a', 'b' ], [ 'c', 'd' ] ], false ), 'This should still be enclosed by parenthesis' );
    $this->assertEquals( "'this is a test string'", $connection->quote( 'this is a test string' ) );

    // test special name quoting
    $this->assertEquals( 'DEFAULT', $connection->quote( $connection->name( 'DEFAULT' ) ), 'This should be unquoted because its a fully uppercase string (a keyword)' );
    $this->assertEquals( '`Default`', $connection->quote( $connection->name( 'Default' ) ), 'This should be namequoted despite the called `->quote()` method' );
    $this->assertEquals( '`SCHEME`.`database`', $connection->quote( $connection->name( 'SCHEME.database' ) ), 'This should be name quoted even if there is an uppercase string in it' );

    // test special expression quoting
    $this->assertEquals( '`column` = 12 AND ISNULL( `column2` )', $connection->quote( $connection->expression( '{!0} = {1} AND ISNULL( {!2} )', [ 'column', 12, 'column2' ] ) ), 'This should be an unqouted expression, with applied context' );
  }

  /**
   * Test statement processing (variable insertion)
   */
  public function testApply() {
    $connection = new ConnectionMock();

    // test statement processing
    $this->assertEquals( "'This' `is a` GOOD 'test string'", $connection->apply( '{this} {!isa} {?good} {test.string}', [
      'this' => 'This',
      'isa'  => 'is a',
      'good' => 'GOOD',
      'test' => [
        'string' => 'test string'
      ]
    ] ) );
  }
}

/**
 * Dummy ConnectionInterface implementation
 *
 * to test non-abstract methods from Connection class
 */
class ConnectionMock extends Connection {

  //
  public function connect( bool $ping = false ) {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function disconnect() {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function statement(): Expression\StatementInterface {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function execute( $statement, $context = [] ) {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function escape( string $text ): string {
    return $text;
  }
  //
  public function isConnected( bool $ping = true ): bool {
    return false;
  }
  //
  public function getTransaction(): TransactionInterface {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function getUri(): string {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function getAuthentication( &$password = null ): ?string {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function setAuthentication( ?string $user, ?string $password = null ) {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function getDatabase(): ?string {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function setDatabase( ?string $value ) {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function getOption( ?string $name = null, $default = null ) {
    throw new \LogicException( 'Not implemented' );
  }
  //
  public function setOption( $value, ?string $name = null ) {
    throw new \LogicException( 'Not implemented' );
  }
}
