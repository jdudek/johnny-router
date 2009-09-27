<?php

require 'PHPUnit/Framework.php';
require './Router.php';

class RouterTest extends PHPUnit_Framework_TestCase
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

		$this->assertEquals($r->url(array('action' => 'list')), '/news');
		$this->assertEquals($r->url(array('action' => 'show', 'id' => 12)), '/news/12');
		$this->assertEquals($r->url(array('action' => 'show', 'wtf' => 37)), null);
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
}

