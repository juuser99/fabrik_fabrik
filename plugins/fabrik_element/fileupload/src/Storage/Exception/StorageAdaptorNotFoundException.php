<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikElement\Fileupload\Storage\Exception;


use Throwable;

class StorageAdaptorNotFoundException extends \Exception
{
	public function __construct($alias)
	{

		parent::__construct($message, $code, $previous);
	}
}