<?php
/**
 * Admin Element Edit - Publishing Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<?php echo JHtml::_('bootstrap.startTabSet', 'publishingTabs', array('active' => 'publishing')); ?>
<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'publishing', JText::_('COM_FABRIK_ELEMENT_LABEL_PUBLISHING_DETAILS')); ?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
				<?php foreach ($this->form->getFieldset('publishing') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
            </fieldset>
        </div>
    </div>
<?php echo JHtml::_('bootstrap.endTab'); ?>
<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'rss', JText::_('COM_FABRIK_ELEMENT_LABEL_RSS')); ?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
				<?php foreach ($this->form->getFieldset('rss') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
            </fieldset>
        </div>
    </div>
<?php echo JHtml::_('bootstrap.endTab'); ?>
<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'rss', JText::_('COM_FABRIK_ELEMENT_LABEL_TIPS')); ?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
				<?php foreach ($this->form->getFieldset('rss') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
            </fieldset>
        </div>
    </div>
<?php echo JHtml::_('bootstrap.endTab'); ?>
<?php echo JHtml::_('bootstrap.endTabSet'); ?>