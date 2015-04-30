<?php

use Fabrik\Admin\Models;

/**
 * Created by PhpStorm.
 * User: rob
 * Date: 30/04/2015
 * Time: 11:20
 */
class fabriktest extends PHPUnit_Framework_TestCase
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

	public function testGetItem ()
	{
		require JPATH_COMPONENT_ADMINISTRATOR . '/models/base.php';
		$model = new Fabrik\Admin\Models\Base;
		$item = $model->getItem();
		$this->assertObjectHasAttribute('list', $item, 'item does not have list property');
		$this->assertObjectHasAttribute('form', $item, 'item does not have form property');
	}
}