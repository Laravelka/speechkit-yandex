<?php

namespace Helpers;

class Logger
{
	public static $path = '/logs';
	protected static $instance = null;
	
	private function __construct() {}
	
	public static function instance()
	{
		return self::$instance ?? new self;
	}
	
	public static function file($data, $file = null)
	{
		$file = $file ?? date('H:i', TIME);
		$content = is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
		
		return file_put_contents(ROOT.self::$path.'/'.$file.'.json', $content);
	}
	
	public function __clone() {}
	public function __wakeup() {}
}