<?php

class Johnny_Router
{
	protected $routes = array();
	
	protected $aliases = array();
	
	protected $varNameRe = '/\:([a-zA-Z0-9_]+)/';
	
	public $defaultRe = '.*?';
	
	public function connect($pattern, $args = array(), $options = array())
	{
		$vars = array();
		if (is_array($pattern)) {	// it's not really a pattern, rather list of variable names
			foreach ($pattern as $name) {
				$vars[$name] = isset($args[$name]) ? $args[$name] : null;
			}
			$pattern = null;
			$routeRe = null;
		} else {
			preg_match_all($this->varNameRe, $pattern, $matches);
			foreach ($matches[1] as $name) {
				$vars[$name] = isset($args[$name]) ? $args[$name] : null;
			}
		}
		
		$consts = array();
		foreach ($args as $k => $v) {
			if (!isset($vars[$k])) {
				$consts[$k] = $v;
			}
		}
		
		if ($pattern) {
			$routeRe = "#^$pattern$#";
			$sortedVars = $vars;
			krsort($sortedVars);
			foreach ($sortedVars as $name => $varRe) {
				if (!isset($varRe)) $varRe = $this->defaultRe;
				$routeRe = str_replace(":$name", "($varRe)", $routeRe);
			}
		}
		
		array_push($this->routes, array(
			'pattern' => $pattern,
			'vars' => $vars,
			'consts' => $consts,
			're' => $routeRe,
			'options' => $options
		));
		
		if (isset($options['alias'])) {
			$this->alias($options['alias'], $consts, array_keys($vars));
		}
	}
	
	public function match($request)
	{
		foreach ($this->routes as $route) {
			if ($route['re'] && preg_match($route['re'], $request, $matches)) {
				$result = $route['consts'];
				$i = 1;
				foreach ($route['vars'] as $name => $re) {
					$result[$name] = $matches[$i];
					$i += 1 + substr_count($re, '(');
				}
				if (isset($route['options']['onMatch'])) {
					$ret = call_user_func($route['options']['onMatch'], $result);
					if ($ret === true) {
						return $result;
					} elseif ($ret === false) {
						continue;
					}
				}
				return $result;
			}
		}
	}
	
	public function createUrl($args)
	{
		foreach ($this->routes as $route) {
			if ($this->matchArgs($route, $args)) {
				if (isset($route['options']['onCreate'])) {
					$ret = call_user_func($route['options']['onCreate'], $this, $args);
					if ($ret === false) {
						continue;		// try to find another route
					} elseif ($ret === true) {
						;				// use this route's pattern
					} else {
						return $ret;	// callback returned generated URL
					}
				}

				$result = $route['pattern'];
				foreach ($args as $k => $v) {
					$result = str_replace(':'.$k, $v, $result);
				}
				return $result;
			}
		}
		throw new Johnny_Router_Exception('createUrl failed for: ' . implode(', ', array_keys($args)));
	}
	
	public function alias($name, $args = array(), $names = array())
	{
		$this->aliases[$name] = array('args' => $args, 'names' => $names);
	}
	
	public function fromAlias($name, $givenArgs = array())
	{
		if (!isset($this->aliases[$name])) {
			throw new Johnny_Router_Exception('Undefined alias: ' . $name);
		}
		$args = $this->aliases[$name]['args'];
		if (count($givenArgs) > 0) {
			$args = array_merge($args, array_combine($this->aliases[$name]['names'], $givenArgs));
		}
		return $this->createUrl($args);
	}
	
	protected function matchArgs($route, $givenArgs)
	{
		foreach ($route['consts'] as $k => $v) {
			if (!isset($givenArgs[$k]) || $givenArgs[$k] != $v) return false;
			unset($givenArgs[$k]);
		}
		foreach ($route['vars'] as $k => $v) {
			if (!isset($givenArgs[$k])) return false;
			if (isset($v) && !preg_match("#$v#", $givenArgs[$k])) return false;
			unset($givenArgs[$k]);
		}
		return empty($givenArgs);
	}
}
