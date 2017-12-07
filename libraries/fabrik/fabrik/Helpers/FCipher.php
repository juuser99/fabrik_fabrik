<?php
/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Crypt\Cipher;
use Joomla\Crypt\Key;
//use ParagonIE\Sodium\Compat;

class FCipher
{
	private $key;

	private $cipher;

	public function __construct()
	{
		$config = \JFactory::getConfig();
		$secret = $config->get('secret', '');

		if (trim($secret) == '')
		{
			throw new RuntimeException('You must supply a secret code in your Joomla configuration.php file');
		}

		$this->cipher = new Cipher\CryptoCipher();
		$this->key    = $this->getKey();
	}

	public function encrypt($data)
	{
		try
		{
			return bin2hex($this->cipher->encrypt($data, $this->key));
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	public function decrypt($data)
	{
		try
		{
			return $this->cipher->decrypt(hex2bin($data), $this->key);
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	private function getKey()
	{
		$fbConfig = \JComponentHelper::getParams('com_fabrik');
		$privateKey = $fbConfig->get('fabrik_private_key', '');
		$publicKey = $fbConfig->get('fabrik_public_key', '');

		if (empty($privateKey))
		{
			$key = $this->generateKey();
		}
		else
		{
			$key = new Key('crypto', hex2bin($privateKey), hex2bin($publicKey));
		}

		return $key;
	}

	private function generateKey()
	{
		$fbConfig = \JComponentHelper::getParams('com_fabrik');
		$key = $this->cipher->generateKey();
		$privateKey = $key->getPrivate();
		$publicKey = $key->getPublic();
		$fbConfig->set('fabrik_private_key', bin2hex($privateKey));
		$fbConfig->set('fabrik_public_key', bin2hex($publicKey));

		$componentid = \JComponentHelper::getComponent('com_fabrik')->id;
		$table = \JTable::getInstance('extension');
		$table->load($componentid);
		$table->bind(array('params' => $fbConfig->toString()));

		// check for error
		if (!$table->check()) {
			echo $table->getError();
			return false;
		}

		// Save to database
		if (!$table->store()) {
			echo $table->getError();
			return false;
		}

		return $key;
	}

}