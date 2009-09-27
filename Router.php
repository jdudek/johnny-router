<?php

class Johnny_Router
{
	/**
	 *
	 * array(
	 *   [0] => array(
	 *     'pattern' =>
	 *     'vars' => array()
	 *     'consts' => array()
	 *     'options' => array()
	 *     're' => 
	 *   )
	 * )
	 */
	protected $routes = array();
	
	protected $varNameRe = '/\:([a-zA-Z0-9_]+)/';
	
	protected $defaultVarRe = '.*?';

	public function connect($pattern, $args = array(), $options = array())
	{
		$vars = array();
		preg_match_all($this->varNameRe, $pattern, $matches);
		foreach ($matches[1] as $name) {
			if (isset($args[$name])) {
				$vars[$name] = $args[$name];
			} else {
				$vars[$name] = $this->defaultVarRe;
			}
		}
		
		$consts = array();
		foreach ($args as $k => $v) {
			if (!isset($vars[$k])) {
				$consts[$k] = $v;
			}
		}
		
		$routeRe = "#^$pattern$#";
		$sortedVars = $vars;
		krsort($sortedVars);
		foreach ($sortedVars as $name => $varRe) {
			$routeRe = str_replace(":$name", "($varRe)", $routeRe);
		}
		
		array_push($this->routes, array(
			'pattern' => $pattern,
			'vars' => $vars,
			'consts' => $consts,
			're' => $routeRe,
			'options' => $options
		));
	}
	
	public function match($request)
	{
		foreach ($this->routes as $route) {
			if (preg_match($route['re'], $request, $matches)) {
				$result = $route['consts'];
				$i = 1;
				foreach ($route['vars'] as $name => $re) {
					$result[$name] = $matches[$i];
					$i += 1 + substr_count($re, '(');
				}
				return $result;
			}
		}
	}
	
	public function url($args)
	{
		foreach ($this->routes as $route) {
			if ($this->matchArgs($route, $args)) {
				$result = $route['pattern'];
				foreach ($args as $k => $v) {
					$result = str_replace(':'.$k, $v, $result);
				}
				return $result;
			}
		}
	}
	
	protected function matchArgs($route, $givenArgs) {
		foreach ($route['consts'] as $k => $v) {
			if (!isset($givenArgs[$k]) || $givenArgs[$k] != $v) return false;
			unset($givenArgs[$k]);
		}
		foreach ($route['vars'] as $k => $v) {
			if (!isset($givenArgs[$k])) return false;
			if ($v != $this->defaultVarRe && !preg_match("#$v#", $givenArgs[$k])) return false;
			unset($givenArgs[$k]);
		}
		return empty($givenArgs);
	}
}