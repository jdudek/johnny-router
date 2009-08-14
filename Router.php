<?php

/*

TODO
 * pozbyc sie wymogu podawania zmiennych parametrow w args
 * filtry
 * wlasne re argumentow
*/

class Johnny_Router
{
	/**
	 *
	 * array(
	 *   [0] => array(
	 *     'pattern' =>
	 *     'args' => array(
	 *       ['xyz'] => array(re => ... | const => ... | var => true)
	 *     )
	 *     'options' => array()
	 *   )
	 * )
	 */
	protected $routes = array();

	public function connect($pattern, $args, $options = array())
	{
		$routeArgs = array();
		foreach ($args as $k => $v) {
			if (is_integer($k)) {
				$routeArgs[$v] = array('var' => true);
			} else if (is_array($v)) {
				$routeArgs[$k] = $v;
			} else {
				$routeArgs[$k] = array('const' => $v);
			}
		}
		array_push($this->routes, array('pattern' => $pattern, 'args' => $routeArgs, 'options' => $options));
	}
	
	public function match($request)
	{
		foreach ($this->routes as $route) {
			preg_match_all('/\:([a-zA-Z0-9_]+)/', $route['pattern'], $matches);
			$patternArgs = $matches[1];
			$re = '#^' . preg_replace('/\:[a-zA-Z0-9_]+/', '(.*?)', $route['pattern']) . '$#';
			if (preg_match($re, $request, $matches)) {
				$result = array();
				foreach ($route['args'] as $k => $v) {
					if (isset($v['const'])) $result[$k] = $v['const'];
				}
				if (count($patternArgs) > 0) {
					array_shift($matches);
					$result = array_merge($result, array_combine($patternArgs, $matches));
				}
				return $result;
			}
		}
	}
	
	public function url($args)
	{
		foreach ($this->routes as $route) {
			if ($this->matchArgs($route['args'], $args)) {
				$result = $route['pattern'];
				foreach ($args as $k => $v) {
					$result = str_replace(':'.$k, $v, $result);
				}
				return $result;
			}
		}
	}
	
	protected function matchArgs($routeArgs, $givenArgs) {
		foreach ($routeArgs as $k => $v) {
			if (!isset($givenArgs[$k])) return false;
			if (isset($v['const']) && $v['const'] != $givenArgs[$k]) return false;
			unset($givenArgs[$k]);
		}
		return empty($givenArgs);
	}
}