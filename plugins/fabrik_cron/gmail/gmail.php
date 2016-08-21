<?php
/**
 * A cron task to import gmail emails into a specified list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.gmail
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Cron;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\StringHelper;
use Fabrik\Helpers\Text;
use \RuntimeException;
use \JFile;
use \JUserHelper;
use \JFactory;

/**
 * A cron task to import gmail emails into a specified list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.gmail
 * @since       3.0.7
 */
class Gmail extends Cron
{
	/**
	 * Do the plugin action
	 *
	 * @param   array               &$data       Data
	 * @param   \FabrikFEModelList  &$listModel  List model
	 *
	 * @return  int  number of records updated
	 */

	public function process(&$data, &$listModel)
	{
		$params = $this->getParams();
		$input = $this->app->input;
		$email = $params->get('plugin-options.email');
		$pw = $params->get('plugin-options.password');

		if ($email == '' || $pw == '')
		{
			return;
		}

		$server = $params->get('plugin-options.server', '{imap.gmail.com:993/imap/ssl}');
		$inBoxes = explode(',', $params->get('plugin-options.inboxes', 'INBOX'));
		$deleteMail = false;

		$fromField = $params->get('plugin-options.from');
		$titleField = $params->get('plugin-options.title');
		$dateField = $params->get('plugin-options.date');
		$contentField = $params->get('plugin-options.content');
		$numProcessed = 0;

		foreach ($inBoxes as $inbox)
		{
			$url = $server . $inbox;
			$mailBox = imap_open($url, $email, $pw);

			if (!$mailBox)
			{
				throw new RuntimeException(Text::_("PLG_CRON_GMAIL_ERROR_CONNECT") . imap_last_error());
			}

			$MC = imap_check($mailBox);
			$mailboxes = imap_list($mailBox, $server, '*');
			$lastId = $params->get('plugin-options.lastid', 0);

			if ($lastId == 0)
			{
				$result = imap_fetch_overview($mailBox, "1:$MC->Nmsgs");
				echo $lastId;

				// Retrieve emails by message number
				$mode = 0;
			}
			else
			{
				// Retrieve emails by message id;
				$result = imap_fetch_overview($mailBox, "$lastId:*", FT_UID);

				if (count($result) > 0)
				{
					unset($result[0]);
				}
			}
			// Fetch an overview for all messages in INBOX
			// $result = imap_fetch_overview($mailBox, "1:$lastId", $mode);

			$numProcessed += count($result);

			foreach ($result as $overview)
			{
				if ($overview->uid > $lastId)
				{
					$lastId = $overview->uid;
				}

				$content = '';
				$thisData = array();

				preg_match("/<(.*)>/", $overview->from, $matches);

				$thisData[$fromField] = $overview->from;
				$thisData[$titleField] = $this->getTitle($overview);
				$thisData[$dateField] = JFactory::getDate($overview->date)->toSql();
				$thisData['imageFound'] = false;

				$thisData[$fromField] = (empty($matches)) ? $overview->from : "<a href=\"mailto:$matches[1]\">$overview->from</a>";

				// Use server time for all incoming messages.
				$date = JFactory::getDate();

				$thisData['processed_date'] = $date->toSql();
				$struct = imap_fetchstructure($mailBox, $overview->msgno);
				$parts = Create_Part_array($struct);

				foreach ($parts as $part)
				{
					// Type 5 is image - full list here http://algorytmy.pl/doc/php/function.imap-fetchstructure.php
					if ($part['part_object']->type == 5)
					{
						imap_fetchbody($mailBox, $overview->msgno, $part['part_number']);
						$attachmentName = '';
						$pName = 'parameters';

						if (is_object($part['part_object']->parameters))
						{
							// Can be in dparamenters instead?
							$pName = 'dparameters';
						}

						$attarray = $part['part_object']->$pName;

						if ($attarray[0]->value == "us-ascii" || $attarray[0]->value == "US-ASCII")
						{
							if ($attarray[1]->value != "")
							{
								$attachmentName = $attarray[1]->value;
							}
						}
						elseif ($attarray[0]->value != "iso-8859-1" && $attarray[0]->value != "ISO-8859-1" && $attarray[0]->value != 'utf-8')
						{
							$attachmentName = $attarray[0]->value;
						}

						if ($attachmentName != '')
						{
							// Randomize file name
							$ext = JFile::getExt($attachmentName);
							$name = JFile::stripExt($attachmentName);
							$name .= '-' . JUserHelper::genRandomPassword(5) . '.' . $ext;
							$thisData['attachmentName'] = $name;
							$thisData['imageFound'] = true;
							$fileContent = imap_fetchbody($mailBox, $overview->msgno, 2);
							$thisData['imageBuffer'] = imap_base64($fileContent);
						}
					}
					/*
					 * Message parts - third param in imap_fetchbody
					 * (empty) - Entire message
					    0 - Message header
					    1 - MULTIPART/ALTERNATIVE
					    1.1 - TEXT/PLAIN
					    1.2 - TEXT/HTML
					    2 - file.ext
					 */

					// Html
					$content = @imap_fetchbody($mailBox, $overview->msgno, 1.2);

					if (strip_tags($content) == '')
					{
						// Plain text
						$content = @imap_fetchbody($mailBox, $overview->msgno, 1.1);
					}

					/*
					 * This encodes text with  =20 correctly i think
					 * may need to test that $part['encoding'] = 4	(QUOTED-PRINTABLE)
					 */
					//
					$content = imap_qprint($content);

					/*
					 * Hmm this seemed to include encoded text which imap_base64 couldn't sort out
					 * as the encoding was too long for insert query - shouts were not getting through
					 * think it might be to do with $part being type 5 (image)
					 * now only adding if part type is 0
					 */

					if (strip_tags($content) == '')
					{
						if ($part['part_object']->type == 0)
						{
							// Multipart alternative
							$content = @imap_fetchbody($mailBox, $overview->msgno, 1);
						}
					}
				}

				$content = $this->removeReplyText($content);

				// Remove any style sheets
				$content = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $content);
				$thisData[$contentField] = $content;

				foreach ($thisData as $key => $val)
				{
					$input->set($key, $val);
				}

				unset($listModel->getFormModel()->formData);
				$listModel->getFormModel()->process();

				// TEST!!!!!!!

				if ($deleteMail)
				{
					imap_delete($mailBox, $overview->msgno);
				}
			}
		}

		$params->set('plugin-options.lastid', $lastId);
		$this->row->params = $params->toString();
		$this->row->store();

		if (isset($mailBox))
		{
			imap_expunge($mailBox);
			imap_close($mailBox);
		}

		return $numProcessed;
	}

	/**
	 * Try to remove reply text from emails
	 *
	 * @param   string  $content  Mail content
	 *
	 * @return  string  content
	 */

	protected function removeReplyText($content)
	{
		// Try to remove reply text
		$content = preg_replace("/\n\>(.*)/", '', $content);
		$content = explode("\n", $content);

		for ($i = count($content) - 1; $i >= 0; $i--)
		{
			if (trim($content[$i]) == '')
			{
				unset($content[$i]);
			}
		}

		$last = array_pop($content);
		$content = implode("\n", $content);

		/*
		 * Test for date and message that precedes reply text
		 * e.g. "2009/9/2 Dev Site for Play Simon Games "
		 */
		$matches = array();
		$res = preg_match("/[0-9]{4}\/[0-9]{1,2}\/[0-9]{1,2}/", $last, $matches);

		if ($res == 0)
		{
			$content .= "\n$last";
		}

		return $content;
	}

	/**
	 * Get subject of email
	 *
	 * @param   object  $overview  Mail overview
	 *
	 * @return  string  email subject
	 */

	private function getTitle($overview)
	{
		$title = $overview->subject;

		// Remove 'RE: ' from title
		if (StringHelper::strtoupper(substr($title, 0, 3)) == 'RE:')
		{
			$title = StringHelper::substr($title, 3, StringHelper::strlen($title));
		}

		return $title;
	}
}

/**
 * Parse the email into its parts
 *
 * @param   object  $structure  Mail
 * @param   string  $prefix     Prefix?
 *
 * @return  array
 */

function Create_Part_array($structure, $prefix = "")
{
	$part_array = array();
	if (isset($structure->parts) && count($structure->parts) > 0)
	{
		// There some sub parts
		foreach ($structure->parts as $count => $part)
		{
			Add_Part_To_array($part, $prefix . ($count + 1), $part_array);
		}
	}
	else
	{
		// Email does not have a separate mime attachment for text
		$part_array[] = array('part_number' => $prefix . '1', 'part_object' => $structure);
	}

	return $part_array;
}

/**
 * Sub function for Create_Part_array(). Only called by Create_Part_array() and itself.
 *
 * @param   object  $obj          Sub part of Mail object
 * @param   int     $partno       Part Number
 * @param   array   &$part_array  Array of parts
 *
 * @return  void
 */

function Add_Part_To_array($obj, $partno, &$part_array)
{
	$part_array[] = array('part_number' => $partno, 'part_object' => $obj);

	if ($obj->type == 2)
	{
		// Check to see if the part is an attached email message, as in the RFC-822 type
		if (count($obj->parts) > 0)
		{
			// Check to see if the email has parts
			foreach ($obj->parts as $count => $part)
			{
				// Iterate here again to compensate for the broken way that imap_fetchbody() handles attachments
				if (count($part->parts) > 0)
				{
					foreach ($part->parts as $count2 => $part2)
					{
						Add_Part_To_array($part2, $partno . "." . ($count2 + 1), $part_array);
					}
				}
				else
				{
					// Attached email does not have a separate mime attachment for text
					$part_array[] = array('part_number' => $partno . '.' . ($count + 1), 'part_object' => $obj);
				}
			}
		}
		else
		{
			// Not sure if this is possible
			$part_array[] = array('part_number' =>  '.1', 'part_object' => $obj);
		}
	}
	else
	{
		// If there are more sub-parts, expand them out.
		if (isset($obj->parts) && is_array($obj->parts))
		{
			if (count($obj->parts) > 0)
			{
				foreach ($obj->parts as $count => $p)
				{
					Add_Part_To_array($p, $partno . "." . ($count + 1), $part_array);
				}
			}
		}
	}
}
