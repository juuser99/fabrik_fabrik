<?php
/**
 * PDF Fabrik List view class, including closures
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Views\Lizt;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\String;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper;
use \stdClass;
use \JRoute;
use \JFabrikFeedItem;

/**
 * PDF Fabrik List view class, including closures
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Fabrikfeed extends Base
{
	/**
	 * Display the Feed
	 *
	 * @return void
	 */
	public function render()
	{
		$app     = $this->app;
		$input   = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$itemId  = Worker::itemId();
		$model   = $this->model;
		$model->setOutPutFormat('feed');

		$app->allowCache(true);

		if (!parent::access($model))
		{
			exit;
		}

		$document            = $this->doc;
		$document->_itemTags = array();

		// $$$ hugh - modified this so you can enable QS filters on RSS links
		// by setting &incfilters=1
		$input->set('incfilters', $input->getInt('incfilters', 0));
		$table = $model->getTable();
		$model->render();
		$params = $model->getParams();

		if ($params->get('rss') == '0')
		{
			return '';
		}

		$formModel       = $model->getFormModel();
		$aJoinsToThisKey = $model->getJoinsToThisKey();

		// Get headings
		$aTableHeadings = array();
		$groupModels    = $formModel->getGroupsHierarchy();
		$titleEl        = $params->get('feed_title');
		$dateEl         = (int) $params->get('feed_date');

		$titleEl = $formModel->getElement($titleEl, true);
		$dateEl  = $formModel->getElement($dateEl, true);
		$title   = $titleEl === false ? '' : $titleEl->getFullName(true, false);
		$date    = $dateEl === false ? '' : $dateEl->getFullName(true, false);
		$dateRaw = $date . '_raw';

		foreach ($groupModels as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element  = $elementModel->getElement();
				$elParams = $elementModel->getParams();

				if ($elParams->get('show_in_rss_feed') == '1')
				{
					$heading = $element->get('label');

					if ($elParams->get('show_label_in_rss_feed') == '1')
					{
						$aTableHeadings[$heading]['label'] = $heading;
					}
					else
					{
						$aTableHeadings[$heading]['label'] = '';
					}

					$aTableHeadings[$heading]['colName'] = $elementModel->getFullName();
					$aTableHeadings[$heading]['dbField'] = $element->name;

					// $$$ hugh - adding enclosure stuff for podcasting
					if ($element->get('plugin') == 'fileupload' || $elParams->get('use_as_rss_enclosure', '0') == '1')
					{
						$aTableHeadings[$heading]['enclosure'] = true;
					}
					else
					{
						$aTableHeadings[$heading]['enclosure'] = false;
					}
				}
			}
		}

		foreach ($aJoinsToThisKey as $element)
		{
			$element  = $elementModel->getElement();
			$elParams = $elementModel->getParams();

			if ($elParams->get('show_in_rss_feed') == '1')
			{
				$heading = $element->get('label');

				if ($elParams->get('show_label_in_rss_feed') == '1')
				{
					$aTableHeadings[$heading]['label'] = $heading;
				}
				else
				{
					$aTableHeadings[$heading]['label'] = '';
				}

				$aTableHeadings[$heading]['colName'] = $element->get('db_table_name') . "___" . $element->get('name');
				$aTableHeadings[$heading]['dbField'] = $element->get('name');

				// $$$ hugh - adding enclosure stuff for podcasting
				if ($element->plugin == 'fileupload' || $elParams->get('use_as_rss_enclosure', '0') == '1')
				{
					$aTableHeadings[$heading]['enclosure'] = true;
				}
				else
				{
					$aTableHeadings[$heading]['enclosure'] = false;
				}
			}
		}

		$w    = new Worker;
		$rows = $model->getData();

		$document->title       = htmlentities($w->parseMessageForPlaceHolder($table->get('list.label')), ENT_COMPAT, 'UTF-8');
		$document->description = htmlspecialchars(trim(strip_tags($w->parseMessageForPlaceHolder($table->get('list.introduction')))));
		$document->link        = JRoute::_('index.php?option=com_' . $package . '&view=list&listid=' . $table->get('id') . '&Itemid=' . $itemId);

		$this->addImage($document, $params);

		// Check for a custom css file and include it if it exists
		$template = $input->get('layout', $table->get('list.template'));
		$cssPath  = COM_FABRIK_FRONTEND . 'views/list/tmpl/' . $template . '/feed.css';

		if (file_exists($cssPath))
		{
			$document->addStyleSheet(COM_FABRIK_LIVESITE . 'components/com_fabrik/views/list/tmpl/' . $template . '/feed.css');
		}

		$view = $model->canEdit() ? 'form' : 'details';

		// List of tags to look for in the row data
		// If they are there don't put them in the desc but put them in as a separate item param
		$rssTags = array(
			'<georss:point>' => 'xmlns:georss="http://www.georss.org/georss"'
		);

		foreach ($rows as $group)
		{
			foreach ($group as $row)
			{
				// Get the content
				$str2       = '';
				$str        = '';
				$tstart     = '<table style="margin-top:10px;padding-top:10px;">';
				$title      = '';
				$item       = new JFabrikFeedItem;
				$enclosures = array();

				foreach ($aTableHeadings as $heading => $dbColName)
				{
					if ($dbColName['enclosure'])
					{
						// $$$ hugh - diddling around trying to add enclosures
						$colName       = $dbColName['colName'] . '_raw';
						$enclosure_url = $row->$colName;

						if (!empty($enclosure_url))
						{
							$remote_file = false;

							// Element value should either be a full path, or relative to J! base
							if (strstr($enclosure_url, 'http://') && !strstr($enclosure_url, COM_FABRIK_LIVESITE))
							{
								$enclosure_file = $enclosure_url;
								$remote_file    = true;
							}
							elseif (strstr($enclosure_url, COM_FABRIK_LIVESITE))
							{
								$enclosure_file = str_replace(COM_FABRIK_LIVESITE, COM_FABRIK_BASE, $enclosure_url);
							}
							elseif (preg_match('#^' . COM_FABRIK_BASE . "#", $enclosure_url))
							{
								$enclosure_file = $enclosure_url;
								$enclosure_url  = str_replace(COM_FABRIK_BASE, '', $enclosure_url);
							}
							else
							{
								$enclosure_file = COM_FABRIK_BASE . $enclosure_url;
								$enclosure_url  = COM_FABRIK_LIVESITE . str_replace('\\', '/', $enclosure_url);
							}

							if ($remote_file || (file_exists($enclosure_file) && !is_dir($enclosure_file)))
							{
								if ($enclosure_type = Worker::getPodcastMimeType($enclosure_file))
								{
									$enclosure_size = $this->get_filesize($enclosure_file, $remote_file);
									$enclosures[]   = array(
										'url' => $enclosure_url,
										'length' => $enclosure_size,
										'type' => $enclosure_type
									);
									/**
									 * No need to insert the URL in the description, as feed readers should
									 * automagically show 'media' when they see an 'enclosure', so just move on ..
									 */
									continue;
								}
							}
						}
					}

					if ($title == '')
					{
						// Set a default title
						$title = $row->$dbColName['colName'];
					}

					// Rob - was stripping tags - but aren't they valid in the content?
					$rssContent = $row->$dbColName['colName'];
					$found      = false;

					foreach ($rssTags as $rssTag => $namespace)
					{
						if (strstr($rssContent, $rssTag))
						{
							$found  = true;
							$rssTag = String::substr($rssTag, 1, String::strlen($rssTag) - 2);

							if (!strstr($document->_namespace, $namespace))
							{
								$document->_itemTags[] = $rssTag;
								$document->_namespace .= $namespace . " ";
							}

							break;
						}
					}

					if ($found)
					{
						$item->{$rssTag} = $rssContent;
					}
					else
					{
						if ($dbColName['label'] == '')
						{
							$str2 .= $rssContent . "<br />\n";
						}
						else
						{
							$str .= "<tr><td>" . $dbColName['label'] . ":</td><td>" . $rssContent . "</td></tr>\n";
						}
					}
				}

				if (isset($row->$title))
				{
					$title = $row->$title;
				}

				if (ArrayHelper::getValue($dbColName, 'label') != '')
				{
					$str = $tstart . $str . "</table>";
				}
				else
				{
					$str = $str2;
				}

				// Url link to article
				$link = JRoute::_('index.php?option=com_' . $package . '&view=' . $view . '&listid=' . $table->get('id')
					. '&rowid=' . $row->slug
				);
				$guid = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&view=' . $view . '&listid=' . $table->get('id')
					. '&rowid=' . $row->slug;

				// Strip html from feed item description text
				$author = @$row->created_by_alias ? @$row->created_by_alias : @$row->author;

				if ($date != '')
				{
					$item->date = $row->$date ? date('r', strtotime(@$row->$dateRaw)) : '';
				}

				// Load individual item creator class

				$item->title       = $title;
				$item->link        = $link;
				$item->guid        = $guid;
				$item->description = $str;

				// $$$ hugh - not quite sure where we were expecting $row->category to come from.  Comment out for now.
				// $item->category = $row->category;

				foreach ($enclosures as $enclosure)
				{
					$item->setEnclosure($enclosure);
				}

				// Loads item info into rss array
				$res = $document->addItem($item);
			}
		}
	}

	/**
	 * Add <image> to document
	 *
	 * @param   object $document JDocument
	 * @param   object $params   JRegistry list parameters
	 *
	 * @return  document
	 */
	private function addImage(&$document, $params)
	{
		$imageSrc = $params->get('feed_image_src', '');

		if ($imageSrc !== '')
		{
			$image              = new stdClass;
			$image->url         = $imageSrc;
			$image->title       = $document->title;
			$image->link        = $document->link;
			$image->width       = '';
			$image->height      = '';
			$image->description = '';
			$document->image    = $image;
		}

		return $document;
	}

	/**
	 * Get file size
	 *
	 * @param   string $path   File path
	 * @param   bool   $remote Remote file, if true attempt to load file via Curl
	 *
	 * @return mixed|number
	 */
	protected function get_filesize($path, $remote = false)
	{
		if ($remote)
		{
			$ch = curl_init($path);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			$data = curl_exec($ch);
			$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
			curl_close($ch);

			return $size;
		}
		else
		{
			return filesize($path);
		}
	}
}
