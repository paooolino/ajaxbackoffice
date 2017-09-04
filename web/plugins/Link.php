<?php
/**
 * The Machine
 *
 * PHP version 5
 *
 * @category  Plugin
 * @package   Machine
 * @author    Paolo Savoldi <paooolino@gmail.com>
 * @copyright 2017 Paolo Savoldi
 * @license   https://github.com/paooolino/Machine/blob/master/LICENSE 
 *            (Apache License 2.0)
 * @link      https://github.com/paooolino/Machine
 */
namespace Plugin;

/**
 * Link class
 *
 * A class grouping useful methods to manage links.
 *
 * @category Plugin
 * @package  Machine
 * @author   Paolo Savoldi <paooolino@gmail.com>
 * @license  https://github.com/paooolino/Machine/blob/master/LICENSE 
 *           (Apache License 2.0)
 * @link     https://github.com/paooolino/Machine
 */
class Link
{
    private $_machine;
    
	private $_routes;
	
    /**
     * Link plugin constructor.
     *
     * The user should not use it directly, as this is called by the Machine.
     *
     * @param Machine $machine the Machine instance.
     */
    public function __construct($machine)
    {
        $this->_machine = $machine;
		$this->_routes = [];
    }
    
    /**
     * Given a name or a slug, gives the complete link.
     *
     * @param array $params
     *
     * @return string The complete link.
     */
    public function Get($params) 
    {
        if (gettype($params) == "string") {
            $params = [$params];
        }
        // the first get param may be a route or a route name
		$name = $params[0];
		$route = isset($this->_routes[$name]) ? $this->_routes[$name] : $name;
		
		// find and fill route parameters with get parameters
		if (count($params) > 1) {
			$matches = [];
			$regexp = "/\{(.*?)\}/";
			$result = preg_match_all($regexp, $route, $matches);
			for ($i = 0; $i < count($matches[0]); $i++) {
				if (isset($params[$i+1])) {
					$route = str_replace($matches[0][$i], $params[$i+1], $route);
				} else {
					break;
				}
			}
		}
		
		$r = $this->_machine->getRequest();
        return "//" . $r["SERVER"]["HTTP_HOST"] . $route;
    }
    
    /**
     * Given a slug, return a string indicating if it matches the current URL.
     *
     * @param array $params
     *
     * @return string "active" if the request matches the slug. Empty string 
     *                  otherwise
     */
    public function Active($params)
    {
        if (gettype($params) == "string") {
            $params = [$params];
        }
        $slug = $params[0];
        $r = $this->_machine->getRequest();
        if ($r["SERVER"]["REQUEST_URI"] == $slug) {
            return "active";
        }
        return "";
    }
	
    /**
     * Set a name for a route.
     *
	 * @param string $name A name for the route.
     * @param string $route The route to map.
	 *
     * @return void
     */
	public function setRoute($name, $route)
	{
		$this->_routes[$name] = $route;
	}
	
    /**
     * Return the route mapped to a name.
     *
	 * @param array $params
	 *
     * @return string The route name.
     */
	public function GetRoute($params)
	{
        if (gettype($params) == "string") {
            $params = [$params];
        }
		$name = $params[0];
		return isset($this->_routes[$name]) ? $this->_routes[$name] : false;
	}
}


