<?php

use Fabrik\Admin\Views\Cron;

/**
 * Created by PhpStorm.
 * User: rob
 * Date: 30/04/2015
 * Time: 11:20
 */
class CronEditTest extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		JFactory::getApplication('administrator');
		//initialize session and user
		$session = JFactory::getSession();
		$session->set('user', JUser::getInstance('admin'));
		//bypass JSession::checkToken()
		JFactory::getApplication()->input->post->set(JSession::getFormToken(),'1');
	}

	public function testRenderEdit ()
	{
		require JPATH_COMPONENT_ADMINISTRATOR . '/views/cron/html.php';
		require JPATH_COMPONENT_ADMINISTRATOR . '/models/cron.php';
		$model = new Fabrik\Admin\Models\Cron;
		$view = new Fabrik\Admin\Views\Cron\Html($model);
		$html = $view->render();
		$this->assertInternalType('string', $html);
	}
}