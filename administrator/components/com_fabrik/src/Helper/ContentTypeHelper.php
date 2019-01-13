<?php
/**
 * Fabrik Content Type Helper
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */

namespace Fabrik\Component\Fabrik\Administrator\Helper;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;

/**
 * Fabrik Content Type Helper
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class ContentTypeHelper
{
	/**
	 * Convert a DOM node's properties into an array
	 *
	 * @param   \DOMElement $node
	 * @param   array      $data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public static function domNodeAttributesToArray($node, $data = array())
	{
		if ($node->hasAttributes())
		{
			foreach ($node->attributes as $attr)
			{
				$data[$attr->nodeName] = $attr->nodeValue;
			}
		}

		return $data;
	}

	/**
	 * Create a params object based on a XML dom node.
	 *
	 * @param   \DOMElement $node
	 *
	 * @return \stdClass
	 *
	 * @since 4.0
	 */
	public static function nodeParams($node)
	{
		$params = $node->getElementsByTagName('params');
		$return = new \stdClass;
		$i      = 0;

		foreach ($params as $param)
		{
			// Avoid nested descendants when asking for group params
			if ($i > 0)
			{
				continue;
			}

			$i++;
			if ($param->hasAttributes())
			{
				foreach ($param->attributes as $attr)
				{
					$name  = $attr->nodeName;
					$value = (string) $attr->nodeValue;

					if (Worker::isJSON($value))
					{
						$value = json_decode($value);
					}

					$return->$name = $value;
				}
			}
		}

		return $return;
	}

	/**
	 * Create an export node presuming that the array has a params property which should be split into a child
	 * node
	 *
	 * @param   \DomDocument $doc
	 * @param   string      $nodeName
	 * @param   array       $data
	 * @param   array       $ignore Array of keys to ignore when creating attributes
	 *
	 * @return \DOMElement
	 *
	 * @since 4.0
	 */
	public static function buildExportNode($doc, $nodeName, $data,
		$ignore = array('created_by', 'created_by_alias', 'group_id', 'modified', 'modified_by',
			'checked_out', 'checked_out_time'))
	{
		$node = $doc->createElement($nodeName);
		foreach ($data as $key => $value)
		{
			if (in_array($key, $ignore))
			{
				continue;
			}
			// Ensure elements are never listed as children.
			if ($key === 'parent_id')
			{
				$value = '0';
			}
			if ($key === 'params')
			{
				$params = Worker::JSONtoData($value);
				$p      = $doc->createElement('params');
				foreach ($params as $pKey => $pValue)
				{
					if (in_array($pKey, $ignore))
					{
						continue;
					}
					if (is_string($pValue) || is_numeric($pValue))
					{
						$p->setAttribute($pKey, $pValue);
					}
					else
					{
						$p->setAttribute($pKey, json_encode($pValue));
					}
				}
				$node->appendChild($p);
			}
			else
			{
				$node->setAttribute($key, $value);
			}
		}

		return $node;
	}

	/**
	 * Initialise the table XML section.
	 * Add the source list name. Needed on import for mapping join table info from
	 * source main table to target main table
	 *
	 * @param   \DomDocument $doc
	 * @param   string      $mainTable
	 *
	 * @return \DOMElement
	 *
	 * @since 4.0
	 */
	public static function iniTableXML($doc, $mainTable)
	{
		$tables = $doc->createElement('database');
		$source = $doc->createElement('source', $mainTable);
		$tables->appendChild($source);

		return $tables;
	}
}
