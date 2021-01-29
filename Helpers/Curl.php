<?php

namespace Helpers;

use Helpers\Logger;

class Curl
{
	private $curl     = null;
	private $response = null;
	private $options  = [
		[CURLOPT_HEADER, false],
		[CURLOPT_RETURNTRANSFER, true],
		[CURLOPT_SSL_VERIFYPEER, false],
	];
	
	public function __construct(array $args = [])
	{
		$this->curl = curl_init();
		
		$this->opt(...$this->options);
	}

	public function getCurl() {
		return $this->curl;
	}

	public function opt(...$args)
	{
		if (!empty($args))
		{
			if (is_array($args[0]))
			{
				foreach($args as $option)
				{
					if (isset($option[0]) && is_int($option[0])) {
						if ($option[0] == 10015 && is_array(array_values($option[1])[0])) {
							curl_setopt($this->curl, $option[0], json_encode($option[1]));
						} else {
							curl_setopt($this->curl, $option[0], $option[1]);
						}
					} else {
						curl_setopt($this->curl, array_keys($option)[0], array_values($option)[0]);
					}
				}
			}
			else
			{
				curl_setopt($this->curl, $args[0], $args[1]);
			}
		}
		else
		{
			Logger::file('ERROR CURL EMPTY OPTION', 'curl-error');
		}
	}
		
	public function headers($args)
	{
		$this->opt(CURLOPT_HTTPHEADER, $args);
	}
	
	public function get(string $url)
	{
		$this->opt(CURLOPT_URL, $url);
		
		$this->response = curl_exec($this->curl);

		curl_close($this->curl);

		return $this->response;
	}

	public function post(string $url, array $data = [])
	{

		$this->opt(
			[CURLOPT_POST, 1],
			[CURLOPT_URL, $url],
			[CURLOPT_POSTFIELDS, $data],
		);
		$this->response = curl_exec($this->curl);
		
		curl_close($this->curl);

		return $this->response;
	}
	
	public function getData(bool $isArray = true)
	{
		if (!empty($this->response))
		{
			if (isJson($this->response))
			{
				return json_decode($this->response, $isArray);
			}
			else
			{
				return $this->response;
			}
		}
		else
		{
			Logger::file('ERROR CURL EMPTY DATA', 'curl-error');
			
			return false;
		}
	}
}