<?php

require 'PHPUnit/Framework.php';
require './Router.php';

class RouterTest extends PHPUnit_Framework_TestCase
{
	public function testMatch() {
		$r = new Johnny_Router();
		$r->connect('/news', array('action' => 'NewsList'));
		$r->connect('/news/:id', array('action' => 'NewsShow'));

		$this->assertEquals($r->match('/news'), array('action' => 'NewsList'));
		$this->assertEquals($r->match('/news/12'), array('action' => 'NewsShow', 'id' => 12));
	}

	public function testMatchWithRe() {
		$r = new Johnny_Router();
		$r->connect('/news', array('action' => 'NewsList'));
		$r->connect('/news/:id', array('action' => 'NewsShow', 'id' => array('re' =>'\d+')));

		$this->assertEquals($r->match('/news'), array('action' => 'NewsList'));
		$this->assertEquals($r->match('/news/12'), array('action' => 'NewsShow', 'id' => 12));
		$this->assertEquals($r->match('/news/wtf'), null);
	}
	
	public function testCreateUrl() {
		$r = new Johnny_Router();
		$r->connect('/news', array('action' => 'NewsList'));
		$r->connect('/news/:id', array('action' => 'NewsShow'));

		$this->assertEquals($r->url(array('action' => 'NewsList')), '/news');
		$this->assertEquals($r->url(array('action' => 'NewsShow', 'id' => 12)), '/news/12');
		$this->assertEquals($r->url(array('action' => 'NewsShow', 'wtf' => 37)), null);
	}
}

