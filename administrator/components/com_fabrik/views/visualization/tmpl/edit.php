<?php
/**
 * Admin Visualization Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\HTML;
use Fabrik\Helpers\Text;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
JHtml::_('behavior.tooltip');
HTML::formvalidation();
JHtml::_('behavior.keepalive');
?>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

	<div class="row-fluid">

		<div class="span6">
			<fieldset class="form-horizontal">
				<legend><?php echo Text::_('COM_FABRIK_DETAILS'); ?></legend>
				<?php foreach ($this->form->getFieldset('details') as $this->field) :
					require '_control_group.php';
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="span5">
			<div class="offset2">
				<fieldset class="form-horizontal">
					<legend>
						<?php echo Text::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS');?>
					</legend>
					<?php foreach ($this->form->getFieldset('publishing') as $this->field) :
						require '_control_group.php';
					endforeach;
					?>
				</fieldset>

				<fieldset class="form-horizontal">
					<legend>
						<?php echo Text::_('COM_FABRIK_VISUALIZATION_LABEL_VISUALIZATION_DETAILS');?>
					</legend>
					<?php foreach ($this->form->getFieldset('more') as $this->field) :
						require '_control_group.php';
					endforeach;
					?>
				</fieldset>
			</div>
		</div>
	</div>
	<div class="row-fluid">

		<div class="span12">
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
	<?php echo JHtml::_('form.token'); ?>
</form>