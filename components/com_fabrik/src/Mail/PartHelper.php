<?php
/**
 * @package     Fabrik\Component\Fabrik\Mail
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Fabrik\Component\Fabrik\Mail;

/**
 * @package     Fabrik\Component\Fabrik\Mail
 *
 * @since       4.0
 */
class PartHelper
{
	/**
	 * Parse the email into its parts
	 *
	 * @param   object $structure Mail
	 * @param   string $prefix    Prefix?
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	public static function createPartArray($structure, $prefix = "")
	{
		$part_array = [];

		if (isset($structure->parts) && count($structure->parts) > 0)
		{
			// There some sub parts
			foreach ($structure->parts as $count => $part)
			{
				self::addPartToArray($part, $prefix . ($count + 1), $part_array, $prefix);
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
	 * @param   object   $obj        Sub part of Mail object
	 * @param   int      $partno     Part Number
	 * @param   array   &$part_array Array of parts
	 * @param string     $prefix
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	private static function addPartToArray($obj, $partno, &$part_array, $prefix = '')
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
							self::addPartToArray($part2, $partno . "." . ($count2 + 1), $part_array);
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
				$part_array[] = array('part_number' => $prefix . '.1', 'part_object' => $obj);
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
						self::addPartToArray($p, $partno . "." . ($count + 1), $part_array);
					}
				}
			}
		}
	}
}