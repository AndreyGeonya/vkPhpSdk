<?php
/**
 * VkPhpSdk class file.
 * 
 * This source file is subject to the New BSD License
 * that is bundled with this package in the file license.txt.
 *
 * @author Andrey Geonya <manufacturer.software@gmail.com>
 * @link https://github.com/AndreyGeonya/vkPhpSdk
 * @copyright Copyright &copy; 2011-2012 Andrey Geonya
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

if (!function_exists('curl_init'))
	throw new Exception('VkPhpSdk needs the CURL PHP extension.');
if (!function_exists('json_decode'))
	throw new Exception('VkPhpSdk needs the JSON PHP extension.');

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'interfaces' . DIRECTORY_SEPARATOR . 'IVkPhpSdk.php';
require_once 'VkApiException.php';

/**
 * VkPhpSdk class.
 * Provides access to the Vkontakte Platform.
 *
 * @see http://vkontakte.ru/developers.php
 * @author Andrey Geonya <manufacturer.software@gmail.com>
 */
class VkPhpSdk
{
	/**
	 * Version.
	 */
	const VERSION = '0.2.1';
	
	/**
	 * Default options for curl.
	 */
	public static $curlOptions = array(
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 60,
		CURLOPT_USERAGENT => 'vkPhpSdk-0.2.1',
		CURLOPT_SSL_VERIFYPEER => false,
	);

	/**
	 * Maps aliases to Vkontakte domains.
	 */
	public static $domainMap = array(
		'api' => 'https://api.vk.com/method/',
		'www' => 'http://www.vk.com/',
	);
		
	private $_accessToken;

	private $_userId;

	private $_curlConnection;
	

	/**
	 * Get OAuth 2.0 access token.
	 * 
	 * @return string
	 */
	public function getAccessToken()
	{
		return $this->_accessToken;
	}	
	
	/**
	 * Set OAuth 2.0 access token. 
	 * 
	 * @param string $accessToken with access token we can make calls to secure API
	 */
	public function setAccessToken($accessToken)
	{
		$this->_accessToken = $accessToken;
	}

	/**
	 * Get user id.
	 * 
	 * @return string
	 */
	public function getUserId()
	{
		return $this->_userId;
	}
	
	/**
	 * Set user id.
	 * 
	 * @return string
	 */
	public function setUserId($userId)
	{
		$this->_userId = $userId;
	}

	/**
	 * Makes a call to VK API.
	 *
	 * @param string $method The API method name
	 * @param array $params The API call parameters
	 * 
	 * @return array decoded response
	 * 
	 * @throws VkApiException
	 */
	public function api($method, array $params = null)
	{
		$result = json_decode($this->makeCurlRequest($method, $params), true);
		
		$this->validateApiResult($result);
		
		return $result;
	}
		
	/**
	 * Make request to service provider (by cURL) and return response.
	 * 
	 * @param string $method The API method name
	 * @param array $params The API call parameters
	 * 
	 * @return string
	 * 
	 * @throws VkApiException
	 */
	protected function makeCurlRequest($method, array $params = null)
	{
		// Init cURL
		$this->_curlConnection = curl_init();
		
		// Add access token to params
		if($this->_accessToken!==null)
			$params['access_token'] = $this->_accessToken;

		if(is_array($params))
			self::$curlOptions[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
		
		self::$curlOptions[CURLOPT_URL] = self::$domainMap['api'] . $method;
		
		curl_setopt_array($this->_curlConnection, self::$curlOptions);
			
		$result = curl_exec($this->_curlConnection);
		
		if ($result === false)
		{
			$exception = new VkApiException(array(
						'error_code' => curl_errno($this->_curlConnection),
						'error_msg' => curl_error($this->_curlConnection),
						'error_type' => 'CurlException'
					));
			curl_close($this->_curlConnection);
			throw $exception;
		}
		
		curl_close($this->_curlConnection);
		
		return $result;		
	}
	
    /**
	 * Validate the API result array.
	 *
	 * @return true
	 * 
	 * @throws VkApiException
	 */
	protected function validateApiResult(array $result)
	{
		if (is_array($result) && isset($result['error']))
			throw new VkApiException($result['error']);
		
		return true;
	}
}