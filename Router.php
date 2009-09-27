<?php

/*

TODO
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
	 *       ['xyz'] => array(re => ... | const => ...)
	 *     )
	 *     'options' => array()
	 *   )
	 * )
	 */
	protected $routes = array();
	
	protected $argNameRe = '/\:([a-zA-Z0-9_]+)/';
	
	protected $defaultArgRe = '.*?';

	public function connect($pattern, $args, $options = array())
	{
		$routeArgs = array();
		preg_match_all($this->argNameRe, $pattern, $matches);
		foreach ($matches[1] as $argName) {
			$routeArgs[$argName] = array('re' => $this->defaultArgRe);
		}
		foreach ($args as $k => $v) {
			if (is_array($v)) {
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
			$re = '#^' . preg_replace($this->argNameRe, '(.*?)', $route['pattern']) . '$#';
			if (preg_match($re, $request, $matches)) {
				$result = array();
				foreach ($route['args'] as $k => $v) {
					if (isset($v['const'])) $result[$k] = $v['const'];
				}
				$varArgs = $this->getVarArgs($route);
				if (count($varArgs) > 0) {
					array_shift($matches);
					$result = array_merge($result, array_combine($varArgs, $matches));
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
	
	protected function getVarArgs($route) {
		$a = array();
		foreach ($route['args'] as $name => $arg) {
			if (!empty($arg['var'])) $a[] = $name;
		}
		return $a;
	}
}