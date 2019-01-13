<?php
/**
 * Fabrik class for generating Generic OAuth API access token.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.rest
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikForm\Rest\OAuth;

use Joomla\Application\AbstractWebApplication;
use Joomla\CMS\Http\Http;
use Joomla\Http\Response;
use Joomla\Input\Input;
use Joomla\OAuth1\Client;
use Joomla\Registry\Registry;

/**
 * Fabrik class for generating Generic OAuth API access token.
 *
 * @since 4.0
 */
class FabrikOAuth extends Client
{
	/**
	 * @var    Registry  Options for the FabrikOauth object.
	 * @since  13.1
	 */
	protected $options;

	/**
	 * FabrikOAuth constructor.
	 *
	 * @param AbstractWebApplication $application
	 * @param Registry|null          $options
	 * @param Http|null              $client
	 * @param Input|null             $input
	 *
	 * @since 4.0
	 */
	public function __construct(AbstractWebApplication $application, Registry $options = null, Http $client = null, Input $input = null)
	{
		$this->options = isset($options) ? $options : new Registry;

		// Call the JOAuth1Client constructor to setup the object.
		parent::__construct($application, $this->options, $client, $input);
	}

	/**
	 * Method to verify if the access token is valid by making a request to an API endpoint.
	 *
	 * @return  boolean  Returns true if the access token is valid and false otherwise.
	 *
	 * @since   13.1
	 */
	public function verifyCredentials()
	{
		return true;
	}

	/**
	 * Method to validate a response.
	 *
	 * @param   string   $url      The request URL.
	 * @param   Response $response The response to validate.
	 *
	 * @return  void
	 *
	 * @since  13.1
	 * @throws \DomainException
	 */
	public function validateResponse($url, $response)
	{
		if (!$code = $this->getOption('success_code'))
		{
			$code = 200;
		}

		if ($response->code != $code && $response->code != 201)
		{
			if ($error = json_decode($response->body))
			{
				throw new \DomainException('Error code ' . $error->errorCode . ' received with message: ' . $error->message . '.');
			}
			else
			{
				throw new \DomainException($response->body);
			}
		}
	}

	/**
	 * Method used to set permissions.
	 *
	 * @param   mixed $scope String or an array of string containing permissions.
	 *
	 * @return  FabrikOauth  This object for method chaining
	 *
	 * @since 4.0
	 */
	public function setScope($scope)
	{
		$this->setOption('scope', $scope);

		return $this;
	}

	/**
	 * Method to get the current scope
	 *
	 * @return  string String or an array of string containing permissions.
	 *
	 * @since   13.1
	 */
	public function getScope()
	{
		return $this->getOption('scope');
	}
}