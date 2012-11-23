<?php
/**
 * IOauth2Proxy class file.
 *
 * This source file is subject to the New BSD License
 * that is bundled with this package in the file license.txt.
 * 
 * @author Andrey Geonya <manufacturer.software@gmail.com>
 * @link https://github.com/AndreyGeonya/vkPhpSdk
 * @copyright Copyright &copy; 2011-2012 Andrey Geonya
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'interfaces' . DIRECTORY_SEPARATOR . 'IOauth2Proxy.php';

/**
 * Oauth2Proxy is the OAuth 2.0 proxy class.
 * Redirects requests to the external web resource by OAuth 2.0 protocol.
 *
 * @see http://oauth.net/2/
 * @author Andrey Geonya
 */
class Oauth2Proxy implements IOauth2Proxy
{
	private $_clientId;
	private $_clientSecret;
	private $_dialogUrl;
	private $_redirectUri;
	private $_scope;
	private $_responseType;
	private $_accessTokenUrl;
	private $_accessParams;
	private $_authJson;

	/**
	 * Constructor.
	 * 
	 * @param string $clientId id of the client application
	 * @param string $clientSecret application secret key
 	 * @param string $accessTokenUrl access token url
	 * @param string $dialogUrl dialog url
	 * @param string $responseType response type (for example: code)
	 * @param string $redirectUri redirect uri
	 * @param string $scope access scope (for example: friends,video,offline)
	 */
	public function __construct($clientId, $clientSecret, $accessTokenUrl, $dialogUrl, $responseType, $redirectUri = null, $scope = null)
	{
		$this->_clientId = $clientId;
		$this->_clientSecret = $clientSecret;
		$this->_accessTokenUrl = $accessTokenUrl;		
		$this->_dialogUrl = $dialogUrl;
		$this->_responseType = $responseType;
		$this->_redirectUri = $redirectUri;
		$this->_scope = $scope;
	}

	/**
	 * Authorize client.
	 */
	public function authorize()
	{
		if(!isset ($_SESSION))
			session_start();
		
		$result = false;
		
		if(isset($_SESSION['vkPhpSdk']['authJson'. $this->_clientId]))
		{
			$this->_authJson = $_SESSION['vkPhpSdk']['authJson'. $this->_clientId];
			$result = true;
		}
		else
		{
			if(!(isset($_REQUEST['code']) && $_REQUEST['code']))
			{
				$_SESSION['vkPhpSdk']['state'] = md5(uniqid(rand(), true)); // CSRF protection

				$this->_dialogUrl .= '?client_id=' . $this->_clientId;
				$this->_dialogUrl .= '&redirect_uri=' . $this->_redirectUri;
				$this->_dialogUrl .= '&scope=' . $this->_scope;
				$this->_dialogUrl .= '&response_type=' . $this->_responseType;
				$this->_dialogUrl .= '&state=' . $_SESSION['vkPhpSdk']['state'];

				echo("<script>top.location.href='" . $this->_dialogUrl . "'</script>");			
			}
			elseif($_REQUEST['state'] === $_SESSION['vkPhpSdk']['state'])
			{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 
					$this->_accessTokenUrl
				    .'?client_id='.$this->_clientId
				    .'&client_secret='.$this->_clientSecret
				    .'&code='.$_REQUEST['code']
				    .'&redirect_uri='.$this->_redirectUri
				);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				$this->_authJson = curl_exec($ch);
				curl_close($ch);
				if($this->_authJson !== false)
				{
					$_SESSION['vkPhpSdk']['authJson'. $this->_clientId] = $this->_authJson;
					$result = true;
				}
				else
					$result = false;
			}
		}

		return $result;
	}
	
	/**
	 * Get access token.
	 * 
	 * @return string
	 */
	public function getAccessToken()
	{		
		if ($this->_accessParams === null)
			$this->_accessParams = json_decode($this->getAuthJson(), true);
		return $this->_accessParams['access_token'];
	}

	/**
	 * Get expires time.
	 * 
	 * @return string
	 */
	public function getExpiresIn()
	{
		if ($this->_accessParams === null)
			$this->_accessParams = json_decode($this->getAuthJson(), true);
		return $this->_accessParams['expires_in'];
	}
	
	/**
	 * Get user id.
	 * 
	 * @return string
	 */
	public function getUserId()
	{
		if ($this->_accessParams === null)
			$this->_accessParams = json_decode($this->getAuthJson(), true);
		return $this->_accessParams['user_id'];		
	}
	
	/**
	 * Get authorization JSON string.
	 * 
	 * @return string
	 */
	protected function getAuthJson()
	{
		return $this->_authJson;
	}
}