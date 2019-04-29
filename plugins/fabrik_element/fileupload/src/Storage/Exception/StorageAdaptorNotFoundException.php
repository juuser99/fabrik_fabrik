<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikElement\Fileupload\Storage\Exception;


class StorageAdaptorNotFoundException extends \Exception
{
	public function __construct($alias)
	{
		parent::__construct("$alias is not registered as a storage adaptor");
	}
}