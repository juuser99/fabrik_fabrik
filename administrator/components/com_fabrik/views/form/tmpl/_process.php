<?php
/**
 * Admin Form Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="tab-pane" id="tab-process">

    <fieldset class="form-horizontal">
		<div class="control-group">
			<div class="control-label">
				<?php echo $this->form->getLabel('record_in_database'); ?>
			</div>
			<div class="controls">
				<?php echo $this->form->getInput('record_in_database'); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo $this->form->getLabel('db_table_name'); ?>
			</div>
		</div>


		<?php foreach ($this->form->getFieldset('processing') as $this->field) :
			require '_control_group.php';;
		endforeach;
		?>
	</fieldset>

    <fieldset class="form-horizontal">
		<legend><?php echo FText::_('COM_FABRIK_NOTES');?></legend>
		<?php foreach ($this->form->getFieldset('notes') as $this->field) :
			require '_control_group.php';;
		endforeach;
		?>
	</fieldset>
</div>
