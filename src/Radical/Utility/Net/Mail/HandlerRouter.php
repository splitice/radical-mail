<?php
namespace Radical\Utility\Net\Mail;

class HandlerRouter {
	private static $handler_functor;
	
	public static function set_default_handler($default){
		self::set_handler_router(function() use($default){
			return $default;
		});
	}
	
	public static function set_handler_router($func){
		self::$handler_functor = $func;
	}
	
	public static function get_handler(){
		$func = self::$handler_functor;
		if($func === null){
			return new Handler\Internal();
		}
		return $func();
	}
}