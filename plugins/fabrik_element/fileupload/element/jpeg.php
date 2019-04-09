<?php
/**
 * Fileupload plugin element to render jpeg file types
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Plugin\FabrikElement\Fileupload\Renderer\ImageRenderer;

return new ImageRenderer();