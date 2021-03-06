<?php 
namespace Emagid\Mvc;

use Emagid\Emagid;


/**
* @todo : add routing table support
*/
class Mvc{



	/**
	* @var string site's root, used to determine where the controller starts 
	*/
	private static $debug = true; 


	/**
	* @var string site's root, used to determine where the controller starts 
	*/
	private static $root = '/'; 


	/**
	* @var string the default controller when none specified
	*/
	private static $default_controller = 'home'; 


	/**
	* @var string the default view when none specified
	*/
	private static $default_view = 'index'; 


	/**
	* @var string the default area when none specified
	*/
	private static $default_area = ''; 


	/**
	* @var array - routing table allows the user to  add new "translators " for routes
	* 				- name : name for the route
	*				- pattern : using regular expression
	*				- controller
	*				- action
	*/
	private static $routes = []; 


	/**
	* Load the Mvc structure
	* 
	* @param array $arr 
	* 		'root' string - the absolute URI of the current site, should always end with '/' (e.g.: '/', '/mysite/')
	*
	* @todo  Add support for arguments to the routing table.
	*/
	public static function load(array $arr = []){

		global $emagid; 


		$exclude_ext = ['.css' , '.jpg', '.html' ,'.js'];

		if(isset($arr['template']) && $arr['template'])
			$emagid->template=$arr['template'];

		if(isset($arr['root']))
			self::$root=$arr['root'];

		if(isset($arr['default_controller']))
			self::$default_controller=$arr['default_controller'];

		if(isset($arr['default_view']))
			self::$default_view=$arr['default_view'];

		if(isset($arr['routes'])){
			self::$routes=$arr['routes'];
		}


		$uri = $_SERVER['REQUEST_URI'];

		

		if(stristr($uri, "?")){
			$uri_parts = explode("?", $uri); 
			$uri = $uri_parts[0];

			//$_SERVER['QUERY_STRING'] =$uri_parts[1];
			self::rebuildQueryString($uri_parts[1]);

		}


		if(self::startsWith($uri, self::$root)){
			$uri = substr($uri, strlen(self::$root));
		}

		if(self::startsWith($uri, '/')){
			$uri = substr($uri, 1);
		}

		foreach ($exclude_ext as $ext) {
			if(stristr($uri, $ext)){
				header("HTTP/1.0 404 Not Found");
				die();
			}
		}


		$route_found = false; 

		if(self::$routes && count(self::$routes)>0 ){
			foreach (self::$routes as $route ) {
				$segments = explode('/', $uri);
				$uri = $segments[0] . (isset($segments[1])?'/' . $segments[1]:'');
				//to allow routing to allow pathing to have method/view from url
				if(isset($segments[2])) $fallback_method = $segments[2];
				
				if($uri == $route['pattern']){

					$segments = [];

					$route_found = true; 
					
					$area_name = (isset($route['area'])?$route['area'] . '/':'');
					$controller_name = $route['controller'];
					$view_name = $route['action'];
					//use a fallback method based on the url
					if($view_name == '')$view_name = $fallback_method;
					
					break;
				}
				
			}
		}



		if(!$route_found){


			$segments = $uri != '' && $uri != '/' ? explode('/', $uri) : array();



			$controller_name = self::getAndPop($segments) ; 

			$area_name = self::$default_area;
			if(!$controller_name ) {
					// if controller doesn't exist, view won't exist neigther .
					$controller_name = self::$default_controller ;
					$view_name = self::$default_view;
			}else {
				// controller exists, might have view definition, and parameters .
				$view_name = self::getAndPop($segments);

				if(!$view_name)
					$view_name = self::$default_view;
				
			}
		}


		$path = $emagid->base_path.'/controllers/'.$area_name.$controller_name.'.php' ;

		// for windows server .
		if($emagid->windows_server)
			$path = str_replace ('/','\\', str_replace ('//','\\',$path));

		if(!file_exists($path))
			return ;




		// load the controller 
		require_once($path);



		//$emagid->controller = new \stdClass ;
		$emagid->controller->name = $controller_name; 
		$emagid->controller->view = $view_name; 
		$emagid->controller->area = $area_name; 


		$req = strtolower($_SERVER['REQUEST_METHOD']); 

		$method = $view_name.'_'.$req; 
		//currently dies with a revealed error to the user, or logs in php_error.log, instead setting an else
		//to let the user know that the method is invalid
		if(method_exists($emagid->controller, $method)){
			call_user_func_array(array(&$emagid->controller, $method),$segments);
		}else if(method_exists($emagid->controller, $view_name)){
			call_user_func_array(array(&$emagid->controller, $view_name),$segments);
		}else{
			echo 'Invalid Method';
		}
		die();
		

	}
	


	/**
	* Get the first element from the array and remove it 
	*
	* @param array &$arr reference to the array 
	*/
	private static function getAndPop(&$arr ){
		if(count($arr)){
			$ret = $arr[0];

			array_shift($arr);

			return $ret ;

		}

		return null; 
	}



	/**
	* Rebuild the querystring 
	*
	* @param string $str - the text that comes after the "?" 
	*/
	private static function rebuildQueryString ($str){
		global $_GET; 

		$_GET = [] ;

		foreach (explode("&", $str) as $qs) {
			$key = explode("=", $qs)[0];
			$val = explode("=", $qs)[1];

			$_GET[$key] = $val;
		}



	}

	/** 
	* Checks whether a strings starts with a specific string.
	*
	* @todo Move this function to functions.inc.php
	*/
	static function startsWith($haystack,$needle,$case=true){
		if($case)
       		return strpos($haystack, $needle, 0) === 0;

   		return stripos($haystack, $needle, 0) === 0;
	}


	/** 
	* Config routes, allowing to override the url when necessary 
	*
	* @param array $routingTable 
	* 				- name : name for the route
	*				- pattern : using regular expression
	*				- controller
	*				- view
	*
	* @todo expand functionality and add support for route variables. 
	*/
	// static function registerRoutes ($routingTable) {
	// 	self::$routes = $routingTable;
	// }


	
}



?>