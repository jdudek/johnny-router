<?php

require dirname(__FILE__) . '/../../lib/Johnny/Router.php';
require dirname(__FILE__) . '/../../lib/Johnny/Router/Exception.php';

class Johnny_RouterTest extends PHPUnit_Framework_TestCase
{
	public function testMatch() {
		$r = new Johnny_Router();
		$r->connect('/news', array('action' => 'list'));
		$r->connect('/news/:id', array('action' => 'show'));

		$this->assertEquals($r->match('/news'), array('action' => 'list'));
		$this->assertEquals($r->match('/news/12'), array('action' => 'show', 'id' => 12));
	}

	public function testCreateUrl() {
		$r = new Johnny_Router();
		$r->connect('/news', array('action' => 'list'));
		$r->connect('/news/:id', array('action' => 'show'));

		$this->assertEquals($r->createUrl(array('action' => 'list')), '/news');
		$this->assertEquals($r->createUrl(array('action' => 'show', 'id' => 12)), '/news/12');
		try {
			$r->createUrl(array('action' => 'show', 'wtf' => 37));
			$this->fail();
		} catch (Johnny_Router_Exception $e) {
		}
	}

	public function testRouteWithRe() {
		$r = new Johnny_Router();
		$r->connect('/news', array('action' => 'list'));
		$r->connect('/news/:id', array('action' => 'show', 'id' => '\d+'));
		$r->connect('/news/:id/:slug', array('action' => 'show'));
		$r->connect('/test/:arg/xx', array('arg' => '(a|b)'));
		$r->connect('/test2/:arg/:arg2', array());

		$this->assertEquals($r->match('/news'), array('action' => 'list'));
		$this->assertEquals($r->match('/news/12'), array('action' => 'show', 'id' => 12));
		$this->assertEquals($r->match('/news/wtf'), null);
		$this->assertEquals($r->match('/news/12/test'), array('action' => 'show', 'id' => 12, 'slug' => 'test'));
		$this->assertEquals($r->match('/test/a/xx'), array('arg' => 'a'));
		$this->assertEquals($r->match('/test/c/xx'), null);
		$this->assertEquals($r->match('/test2/a/b'), array('arg' => 'a', 'arg2' => 'b'));
	}

	public function testAliases() {
		$r = new Johnny_Router();
		$r->connect('/news', array('action' => 'list'));
		$r->connect('/news/:id', array('action' => 'show'));
		$r->alias('list', array('action' => 'list'));
		$r->alias('show', array('action' => 'show'), array('id'));

		$this->assertEquals($r->fromAlias('list'), '/news');
		$this->assertEquals($r->fromAlias('show', array(12)), '/news/12');
		try {
			$r->fromAlias('test');
			$this->fail();
		} catch (Johnny_Router_Exception $e) {
		}
	}

	public function testAliasesWithinConnect() {
		$r = new Johnny_Router();
		$r->connect('/news/:id', array('action' => 'show'), array('alias' => 'show'));
		$this->assertEquals($r->fromAlias('show', array(12)), '/news/12');
	}

	public function testOnCreate() {
		$r = new Johnny_Router();

		$fn = create_function('$r, $args', '
			$args["id"] = $args["item"]["id"];
			$args["slug"] = $args["item"]["slug"];
			unset($args["item"]);
			return $r->createUrl($args);
		');

		$r->connect('/news', array('action' => 'list'));
		$r->connect('/news/:id/:slug', array('action' => 'show', 'id' => '\d+'));
		$r->connect(array('item'), array('action' => 'show'), array('onCreate' => $fn));

		$item = array('id' => 12, 'slug' => 'my-test');
		$this->assertEquals($r->createUrl(array('action' => 'show', 'item' => $item)), '/news/12/my-test');
	}

	public function testOnMatch() {
		$r = new Johnny_Router();
		$fn = create_function('$args', 'return false;');
		$r->connect('/test', array('action' => 'test1'), array('onMatch' => $fn));
		$r->connect('/test', array('action' => 'test2'));

		$this->assertEquals($r->match('/test'), array('action' => 'test2'));
	}

	public function testCreateUrlFailure() {
		$r = new Johnny_Router();
		try {
			$r->createUrl(array('action' => 'list'));
			$this->fail();
		} catch (Johnny_Router_Exception $e) {
		}
	}

	public function testUrlShortcut() {
		$r = new Johnny_Router();
		$r->connect('/news', array('action' => 'list'));
		$r->connect('/news/:id', array('action' => 'show'));
		$r->alias('show', array('action' => 'show'), array('id'));

		$this->assertEquals($r->url(array('action' => 'list')), '/news');
		$this->assertEquals($r->url('show', 12), '/news/12');
	}
}

