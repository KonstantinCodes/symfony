<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Components\DependencyInjection;

use Symfony\Components\DependencyInjection\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
  public function testConstructor()
  {
    $sc = new Container();
    $this->assertEquals(spl_object_hash($sc), spl_object_hash($sc->getService('service_container')), '__construct() automatically registers itself as a service');

    $sc = new Container(array('foo' => 'bar'));
    $this->assertEquals(array('foo' => 'bar'), $sc->getParameters(), '__construct() takes an array of parameters as its first argument');
  }

  public function testGetSetParameters()
  {
    $sc = new Container();
    $this->assertEquals(array(), $sc->getParameters(), '->getParameters() returns an empty array if no parameter has been defined');

    $sc->setParameters(array('foo' => 'bar'));
    $this->assertEquals(array('foo' => 'bar'), $sc->getParameters(), '->setParameters() sets the parameters');

    $sc->setParameters(array('bar' => 'foo'));
    $this->assertEquals(array('bar' => 'foo'), $sc->getParameters(), '->setParameters() overrides the previous defined parameters');

    $sc->setParameters(array('Bar' => 'foo'));
    $this->assertEquals(array('bar' => 'foo'), $sc->getParameters(), '->setParameters() converts the key to lowercase');
  }

  public function testGetSetParameter()
  {
    $sc = new Container(array('foo' => 'bar'));
    $sc->setParameter('bar', 'foo');
    $this->assertEquals('foo', $sc->getParameter('bar'), '->setParameter() sets the value of a new parameter');
    $this->assertEquals('foo', $sc['bar'], '->offsetGet() gets the value of a parameter');

    $sc['bar1'] = 'foo1';
    $this->assertEquals('foo1', $sc['bar1'], '->offsetset() sets the value of a parameter');

    unset($sc['bar1']);
    $this->assertTrue(!isset($sc['bar1']), '->offsetUnset() removes a parameter');

    $sc->setParameter('foo', 'baz');
    $this->assertEquals('baz', $sc->getParameter('foo'), '->setParameter() overrides previously set parameter');

    $sc->setParameter('Foo', 'baz1');
    $this->assertEquals('baz1', $sc->getParameter('foo'), '->setParameter() converts the key to lowercase');
    $this->assertEquals('baz1', $sc->getParameter('FOO'), '->getParameter() converts the key to lowercase');
    $this->assertEquals('baz1', $sc['FOO'], '->offsetGet() converts the key to lowercase');

    try
    {
      $sc->getParameter('baba');
      $this->fail('->getParameter() thrown an \InvalidArgumentException if the key does not exist');
    }
    catch (\InvalidArgumentException $e)
    {
    }

    try
    {
      $sc['baba'];
      $this->fail('->offsetGet() thrown an \InvalidArgumentException if the key does not exist');
    }
    catch (\InvalidArgumentException $e)
    {
    }
  }

  public function testHasParameter()
  {
    $sc = new Container(array('foo' => 'bar'));
    $this->assertTrue($sc->hasParameter('foo'), '->hasParameter() returns true if a parameter is defined');
    $this->assertTrue($sc->hasParameter('Foo'), '->hasParameter() converts the key to lowercase');
    $this->assertTrue(isset($sc['Foo']), '->offsetExists() converts the key to lowercase');
    $this->assertTrue(!$sc->hasParameter('bar'), '->hasParameter() returns false if a parameter is not defined');
    $this->assertTrue(isset($sc['foo']), '->offsetExists() returns true if a parameter is defined');
    $this->assertTrue(!isset($sc['bar']), '->offsetExists() returns false if a parameter is not defined');
  }

  public function testAddParameters()
  {
    $sc = new Container(array('foo' => 'bar'));
    $sc->addParameters(array('bar' => 'foo'));
    $this->assertEquals(array('foo' => 'bar', 'bar' => 'foo'), $sc->getParameters(), '->addParameters() adds parameters to the existing ones');
    $sc->addParameters(array('Bar' => 'fooz'));
    $this->assertEquals(array('foo' => 'bar', 'bar' => 'fooz'), $sc->getParameters(), '->addParameters() converts keys to lowercase');
  }

  public function testServices()
  {
    $sc = new Container();
    $sc->setService('foo', $obj = new \stdClass());
    $this->assertEquals(spl_object_hash($obj), spl_object_hash($sc->getService('foo')), '->setService() registers a service under a key name');

    $sc->foo1 = $obj1 = new \stdClass();
    $this->assertEquals(spl_object_hash($obj1), spl_object_hash($sc->foo1), '->__set() sets a service');

    $this->assertEquals(spl_object_hash($obj), spl_object_hash($sc->foo), '->__get() gets a service by name');
    $this->assertTrue($sc->hasService('foo'), '->hasService() returns true if the service is defined');
    $this->assertTrue(isset($sc->foo), '->__isset() returns true if the service is defined');
    $this->assertTrue(!$sc->hasService('bar'), '->hasService() returns false if the service is not defined');
    $this->assertTrue(!isset($sc->bar), '->__isset() returns false if the service is not defined');
  }

  public function testGetServiceIds()
  {
    $sc = new Container();
    $sc->setService('foo', $obj = new \stdClass());
    $sc->setService('bar', $obj = new \stdClass());
    $this->assertEquals(array('service_container', 'foo', 'bar'), $sc->getServiceIds(), '->getServiceIds() returns all defined service ids');

    $sc = new ProjectServiceContainer();
    $this->assertEquals(spl_object_hash($sc->__bar), spl_object_hash($sc->getService('bar')), '->getService() looks for a getXXXService() method');
    $this->assertTrue($sc->hasService('bar'), '->hasService() returns true if the service has been defined as a getXXXService() method');

    $sc->setService('bar', $bar = new \stdClass());
    $this->assertNotEquals(spl_object_hash($sc->getService('bar')), spl_object_hash($bar), '->getService() prefers to return a service defined with a getXXXService() method than one defined with setService()');

    try
    {
      $sc->getService('baba');
      $this->fail('->getService() thrown an \InvalidArgumentException if the service does not exist');
    }
    catch (\InvalidArgumentException $e)
    {
    }

    try
    {
      $sc->baba;
      $this->fail('->__get() thrown an \InvalidArgumentException if the service does not exist');
    }
    catch (\InvalidArgumentException $e)
    {
    }

    try
    {
      unset($sc->baba);
      $this->fail('->__unset() thrown an LogicException if you try to remove a service');
    }
    catch (\LogicException $e)
    {
    }

    $this->assertEquals(spl_object_hash($sc->__foo_bar), spl_object_hash($sc->getService('foo_bar')), '->getService() camelizes the service id when looking for a method');
    $this->assertEquals(spl_object_hash($sc->__foo_baz), spl_object_hash($sc->getService('foo.baz')), '->getService() camelizes the service id when looking for a method');
  }

  public function testMagicCall()
  {
    $sc = new Container();
    $sc->setService('foo_bar.foo', $foo = new \stdClass());
    $this->assertEquals($foo, $sc->getFooBar_FooService(), '__call() finds services is the method is getXXXService()');

    try
    {
      $sc->getFooBar_Foo();
      $this->fail('__call() throws a \RuntimeException exception if the method is not a service method');
    }
    catch (\RuntimeException $e)
    {
    }
  }

  public function testGetService()
  {
    $sc = new Container();

    try
    {
      $sc->getService('');
      $this->fail('->getService() throws a \InvalidArgumentException exception if the service is empty');
    }
    catch (\InvalidArgumentException $e)
    {
    }
  }
}

class ProjectServiceContainer extends Container
{
  public $__bar, $__foo_bar, $__foo_baz;

  public function __construct()
  {
    parent::__construct();

    $this->__bar = new \stdClass();
    $this->__foo_bar = new \stdClass();
    $this->__foo_baz = new \stdClass();
  }

  protected function getBarService()
  {
    return $this->__bar;
  }

  protected function getFooBarService()
  {
    return $this->__foo_bar;
  }

  protected function getFoo_BazService()
  {
    return $this->__foo_baz;
  }
}
