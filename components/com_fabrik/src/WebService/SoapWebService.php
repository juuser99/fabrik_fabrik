<?php
/**
 * SOAP web service
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\WebService;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

/**
 * SOAP web service
 *
 * @package  Fabrik
 * @since    4.0
 */
class SoapWebService extends AbstractWebService
{
	/**
	 * @var \SoapClient
	 * @since 4.0
	 */
	private $client;

	/**
	 * Constructor
	 *
	 * @param   array $options ini state
	 *
	 * @since 4.0
	 */
	public function __construct($options)
	{
		ini_set("soap.wsdl_cache_enabled", 0);
		$this->client = new \SoapClient($options['endpoint']);
	}

	/**
	 * get SOAP clients function
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function getFunctions()
	{
		$functions = $this->client->__getFunctions();
	}

	/**
	 * Query the web service to get the data
	 *
	 * @param   string $method     to call at web service (soap only)
	 * @param   array  $options    key value filters to send to web service to filter the data
	 * @param   string $startPoint of actual data, if soap this is an xpath expression,
	 *                             otherwise its a key.key2.key3 string to traverse the returned data to arrive at the data to map to the fabrik list
	 * @param   string $result     method name - soap only, if not set then "$method . 'Result' will be used
	 *
	 * @return  array    series of objects which can then be bound to the list using storeLocally()
	 *
	 * @since 4.0
	 */
	public function get($method, $options = array(), $startPoint = null, $result = null)
	{
		if (is_null($result))
		{
			$result = $method . 'Result';
		}

		$xml  = $this->client->$method($options);
		$data = Factory::getXML($xml->$result, false);

		if (!is_null($startPoint))
		{
			$data = $data->xpath($startPoint);
		}

		$return = array();

		// Convert xml nodes into simple objects
		foreach ($data as $xmlElement)
		{
			$json     = json_encode($xmlElement);
			$return[] = json_decode($json);
		}

		return $return;
	}
}
