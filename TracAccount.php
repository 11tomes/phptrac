<?php

class TracAccount
{
	/**
	 * @var string
	 */
	protected $_username;

	/**
	 * @var string
	 */
	protected $_password;

	public function __construct($username, $password)
	{
		$this->_username = $username;
		$this->_password = $password;
	}

	public function getUsername()
	{
		return $this->_username;
	}

	public function getPassword()
	{
		return $this->_password;
	}

}

?>
