<?php

use Fabrik\Admin\Views\Lizt;

/**
 * Created by PhpStorm.
 * User: rob
 * Date: 30/04/2015
 * Time: 11:20
 */
class ListEditTest extends PHPUnit_Framework_TestCase
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
		require JPATH_COMPONENT_ADMINISTRATOR . '/views/lizt/html.php';
		require JPATH_COMPONENT_ADMINISTRATOR . '/models/lizt.php';
		$model = new Fabrik\Admin\Models\Lizt;
		$view = new Fabrik\Admin\Views\Lizt\Html($model);
		$html = $view->render();
		$this->assertInternalType('string', $html);
	}
}