<?php

/**
 * Helper class for doing OAuth2 authentication with Nosto.
 */
class NostoTaggingOAuth2Client
{
	const NOSTOTAGGING_OAUTH2_CLIENT_BASE_URL = 'https://api.nosto.com/oauth';
	const NOSTOTAGGING_OAUTH2_CLIENT_AUTH_PATH = '/authorize/?client_id={cid}&redirect_uri={uri}&response_type=code';
	const NOSTOTAGGING_OAUTH2_CLIENT_TOKEN_PATH = '/token/?client_id={cid}&redirect_uri={uri}&code={code}&grant_type=implicit';

	/**
	 * @var string the client id the identify this application to the oauth2 server.
	 */
	protected $client_id;

	/**
	 * @var string the redirect url that will be used by the oauth2 server when authenticating the client.
	 */
	protected $redirect_url;

	/**
	 * @var string access token returned by the oauth2 server after authenticating.
	 */
	protected $access_token;

	/**
	 * Setter for the client id the identify this application to the oauth2 server.
	 *
	 * @param string $client_id the client id.
	 */
	public function setClientId($client_id)
	{
		$this->client_id = $client_id;
	}

	/**
	 * Setter for the redirect url that will be used by the oauth2 server when authenticating the client.
	 *
	 * @param string $redirect_url the redirect url.
	 */
	public function setRedirectUrl($redirect_url)
	{
		$this->redirect_url = $redirect_url;
	}

	/**
	 * The access token returned by the oauth2 server after authenticating.
	 *
	 * @param string $access_token the access token.
	 */
	public function setAccessToken($access_token)
	{
		$this->access_token = $access_token;
	}

	/**
	 * Getter for the access token returned by the oauth2 server after authenticating.
	 *
	 * @return string the access token.
	 */
	public function getAccessToken()
	{
		return $this->access_token;
	}

	/**
	 * Returns the authentication url to the oauth2 server.
	 *
	 * @return string the url.
	 */
	public function getAuthorizationUrl()
	{
		$url = self::NOSTOTAGGING_OAUTH2_CLIENT_BASE_URL.self::NOSTOTAGGING_OAUTH2_CLIENT_AUTH_PATH;
		return strtr($url, array('{cid}' => $this->client_id, '{uri}' => $this->redirect_url));
	}

	/**
	 * Authenticates the application with the given code to receive an access token.
	 *
	 * @param string $code
	 * @return string|bool the access token or false on failure.
	 */
	public function authenticate($code)
	{
		if (empty($code))
		{
			NostoTaggingLogger::log(
				__CLASS__.'::'.__FUNCTION__.' - invalid authentication token.',
				NostoTaggingLogger::LOG_SEVERITY_ERROR,
				500
			);
			return false;
		}

		$request = new NostoTaggingHttpRequest();
		$url = self::NOSTOTAGGING_OAUTH2_CLIENT_BASE_URL.self::NOSTOTAGGING_OAUTH2_CLIENT_AUTH_PATH;
		$url = strtr($url, array('{cid}' => $this->client_id, '{uri}' => $this->redirect_url, '{code}' => $code));
		$response = $request->post($url);
		$result = $response->getJsonResult();

		if ($response->getCode() !== 200)
		{
			$message_parts = array();
			if (isset($result->error_reason))
				$message_parts[] = $result->error_reason;
			if (isset($result->error_description))
				$message_parts[] = $result->error_description;
			if (empty($message_parts))
				$message_parts[] = 'Failed to authenticate with code.';

			NostoTaggingLogger::log(
				__CLASS__.'::'.__FUNCTION__.implode(' - ', $message_parts),
				NostoTaggingLogger::LOG_SEVERITY_ERROR,
				$response->getCode()
			);
			return false;
		}

		if (empty($result->access_token))
		{
			NostoTaggingLogger::log(
				__CLASS__.'::'.__FUNCTION__.' - No "access_token" returned after authenticating with code.',
				NostoTaggingLogger::LOG_SEVERITY_ERROR,
				$response->getCode()
			);
			return false;
		}

		$this->setAccessToken($result->access_token);
		return $result->access_token;
	}
} 