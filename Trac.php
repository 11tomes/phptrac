<?php

require_once('TracAccount.php');

class Trac
{

	/**
	 * Base URL for Trac page
	 *
	 * @var string
	 */
	protected $_base_url;

	/**
	 * @var self
	 */
	protected static $trac;

	/**
	 *
	 * @var 
	 */
	protected $_client;

	/**
	 *
	 * @var TracAccount
	 */
	protected $_trac_account;

	/**
	 * @param string $base_url
	 * @return self
	 */
	public static function getTrac($base_url)
	{
		if ( ! self::$trac) {
			self::$trac = new Trac($base_url);
		}

		return self::$trac;
	}

	/**
	 * 
	 * @param TracAccount $trac_account
	 */
	public function login(TracAccount $trac_account)
	{
		$client = $this->_getClient();
		$uri = $this->_getUri('login');
		$login_request = $this->_loadPage($client, $uri);
		$login_parameters = $this->_getLoginParameters($login_request, $trac_account);
		$wiki_request = $this->_submit($client, $login_parameters);

		$this->_trac_account = $trac_account;

		return $wiki_request;
	}

	public function getTicket($ticket_number)
	{
		$client = $this->_getClient();
		$uri = $this->_getUri("ticket/$ticket_number");
		$ticket_response = $this->_loadPage($client, $uri);
		return $ticket_response;
	}

	public function getPage($parameters)
	{
		$client = $this->_getClient();
		$uri = $this->_getUri($parameters);
		$response = $this->_loadPage($client, $uri);
		return $response;
	}

	/**
	 *
	 * @param IrisError $error
	 * @return integer
	 */
	public function createTicket($description)
	{
		$client = $this->_getClient();
		$uri = $this->_getUri('newticket');
		$new_ticket_request = $this->_loadPage($client, $uri);
		$new_ticket_parameters = $this->_getTicketParameters($new_ticket_request, $description);
		$ticket_request = $this->_submit($client, $new_ticket_parameters);
		$ticket_number = $this->_getTicketNumber($ticket_request);
		return $ticket_number;
	}

	protected function __construct($base_url)
	{
		if ( ! $base_url) {
			throw new Exception('Invalid Trac URL.');
		}

		$this->_base_url = $base_url;
	}


	/**
	 *
	 * @var string
	 */
	protected function _getUri($page)
	{
		return "{$this->_base_url}/{$page}";
	}

	protected function _initClient()
	{
		require_once('Zend/Http/Client.php');
		$this->_client = new Zend_Http_Client();
		$this->_client->setCookieJar();
	}

	/**
	 *
	 * @return Zend_Http_Client
	 */
	protected function _getClient()
	{
		if ( ! $this->_client) {
			$this->_initClient();
		}
		return $this->_client;
	}

	/**
	 * A form token is a hidden input element in every form that is used
	 * for validating if the request is correct
	 * 
	 * @return string
	 */
	protected function _getFormToken($request)
	{
		require_once('Zend/Dom/Query.php');
		$dom = new Zend_Dom_Query($request->getBody());
		$form_token_control = $dom->query('input[name="__FORM_TOKEN"]');
		$form_token = $form_token_control->current()->getAttribute('value');

		return $form_token;
	}

	protected function _getTicketNumber($response)
	{
		require_once('Zend/Dom/Query.php');
		$dom = new Zend_Dom_Query($response->getBody());
		$form_control = $dom->query('#content h1');
		$action = $form_control->current()->textContent;
		preg_match_all('!\d+!', $action, $matches);
		return $matches[0][0];
	}

	/**
	 * See Zend_Http_Client::request() for return value
	 *
	 */
	protected function _loadPage($client, $uri)
	{
		$client->setUri($uri);
		$request = $client->request();

		return $request;
	}

	protected function _getLoginParameters($login_request, $trac_account)
	{
		$form_token = $this->_getFormToken($login_request);
		$parameters = array(
			'__FORM_TOKEN'	=> $form_token,
			'uid'		=> $trac_account->getUsername(),
			'pwd'		=> $trac_account->getPassword(),
			'login'		=> 'Login'
		);

		return $parameters;
	}

	/**
	 * Perform a POST request passing the parameters
	 *
	 * @param @todo $client
	 * @param array $parameters
	 * @return @todo
	 */
	protected function _submit($client, $parameters)
	{
		$client->setParameterPost($parameters);
		$request = $client->request('POST');

		return $request;
	}

	protected function _getTicketParameters($ticket_request, $description)
	{
		$trac_account = $this->_trac_account;
		$form_token = $this->_getFormToken($ticket_request);
		$milestone = '';
		$version = '';
		$severity = 'normal';
		$reporter = $trac_account->getUsername();

		$parameters = array(
			'__FORM_TOKEN'		=> $form_token,
			'field_summary'		=> $error->message,
			'field_reporter'	=> $reporter,
			'field_description'	=> $description,
			'field_type'		=> 'defect',
			'field_priority'	=> 'P1',
			'field_milestone'	=> $milestone,
			'field_component'	=> $error->component,
			'field_version'		=> $version,
			'field_severity'	=> $severity,
			'field_keywords'	=> '',
			'field_cc'		=> '',
			'field_devstatus'	=> '',
			'field_parentticket'	=> '',
			'field_esthours'	=> '',
			'field_testticket'	=> '',
			'field_hoursspent'	=> '',
			'field_developer'	=> $reporter,
			'field_class'		=> '',
			'field_percent'		=> '',
			'field_navigation'	=> '',
			'field_releasenote'	=> '',
			'field_owner'		=> $reporter,
			'attachment'		=> 'on',
			'field_status'		=> 'new',
			'submit'		=> 'Create ticket'
		);

		return $parameters;
	}
}

?>
