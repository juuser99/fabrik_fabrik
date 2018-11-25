<?php
/**
 * Admin Visualization Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Fabrik\Helpers\Html;
use Joomla\CMS\Router\Route;
use Fabrik\Helpers\Text;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::stylesheet('administrator/components/com_fabrik/tmpl/fabrikadmin.css');
HTMLHelper::_('behavior.tooltip');
Html::formvalidation();
HTMLHelper::_('behavior.keepalive');

?>

<form action="<?php Route::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

	<div class="row">
		<div class="col-5">
			<fieldset class="form-horizontal">
				<legend><?php echo Text::_('COM_FABRIK_DETAILS'); ?></legend>
				<?php foreach ($this->form->getFieldset('details') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="col-6 offset-1">
            <fieldset class="form-horizontal">
                    <legend>
                        <?php echo Text::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS');?>
                    </legend>
                <?php foreach ($this->form->getFieldset('publishing') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>
            </fieldset>

            <fieldset class="form-horizontal">
                    <legend>
                        <?php echo Text::_('COM_FABRIK_VISUALIZATION_LABEL_VISUALIZATION_DETAILS');?>
                    </legend>
                <?php foreach ($this->form->getFieldset('more') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>
            </fieldset>
        </div>
	</div>
	<div class="row">
		<div class="col-12">
		    <fieldset class="form-horizontal">
		    	<legend>
		    		<?php echo Text::_('COM_FABRIK_OPTIONS');?>
		    	</legend>
			</fieldset>
			<div id="plugin-container">
				<?php echo $this->pluginFields;?>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
