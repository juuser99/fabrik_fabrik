<?php
/**
 * Admin Lizt Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\HTML;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
JHtml::_('behavior.tooltip');
HTML::formvalidation();
JHtml::_('behavior.keepalive');

?>
<script type="text/javascript">

	Joomla.submitbutton = function(task) {
		if (task !== 'lizt.cancel'  && !Fabrik.controller.canSaveForm()) {
			alert('Please wait - still loading');
			return false;
		}
		if (task == 'lizt.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
			<?php echo $this->form->getField('introduction')->save(); ?>
			window.fireEvent('form.save');
			debugger;
			Joomla.submitform(task, document.getElementById('adminForm'));
		} else {
			alert('<?php echo $this->escape(FText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<div class="row-fluid" id="elementFormTable">

		<div class="span2">


				<ul class="nav nav-list"style="margin-top:40px">
					<li class="active">
						<a data-toggle="tab" href="#detailsX">
							<?php echo FText::_('COM_FABRIK_DETAILS')?>
						</a>
					</li>
					<li>
						<a data-toggle="tab" href="#data">
							<?php echo FText::_('COM_FABRIK_DATA')?>
						</a>
					</li>
					<li>
						<a data-toggle="tab" href="#publishing">
							<?php echo FText::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS')?>
						</a>
					</li>
					<li>
						<a data-toggle="tab" href="#access">
							<?php echo FText::_('COM_FABRIK_GROUP_LABEL_RULES_DETAILS')?>
						</a>
					</li>
					<li>
						<a data-toggle="tab" href="#tabplugins">
							<?php echo FText::_('COM_FABRIK_GROUP_LABEL_PLUGINS_DETAILS')?>
						</a>
					</li>
				</ul>
		</div>
		<div class="span10">

			<div class="tab-content">
				<?php
				require_once '_details.php';
				require_once '_data.php';
				require_once '_publishing.php';
				require_once '_plugins.php';
				require_once '_access.php';
				?>
			</div>

			<input type="hidden" name="task" value="" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
	</div>
</form>
