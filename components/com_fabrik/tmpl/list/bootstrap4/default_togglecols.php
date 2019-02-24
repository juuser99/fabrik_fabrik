<?php
/**
 * Bootstrap List Template - Toggle columns widget
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Language\Text;

?>
<div class="dropdown togglecols">
	<a href="#" class="dropdown-toggle" data-toggle="dropdown">
		<?php echo Html::icon('icon-eye-open', Text::_('COM_FABRIK_TOGGLE')); ?>
		<b class="caret"></b>
	</a>
	<div class="dropdown-menu">
	<?php
	$groups = array();

	foreach ($this->toggleCols as $group) :
		?>
        <a class="dropdown-item" data-toggle-group="<?php echo $group['name']?>" data-toggle-state="open">
            <?php echo Html::icon('icon-eye-open'); ?>
            <strong><?php echo Text::_($group['name']);?></strong>
        </a>
		<?php
		foreach ($group['elements'] as $element => $label) :
		?>
        <a class="dropdown-item" data-toggle-col="<?php echo $element?>" data-toggle-parent-group="<?php echo $group['name']?>" data-toggle-state="open">
            <?php echo Html::icon('icon-eye-open', Text::_($label)); ?>
        </a>
		<?php
		endforeach;

	endforeach;

	?>
	</div>
</div>
