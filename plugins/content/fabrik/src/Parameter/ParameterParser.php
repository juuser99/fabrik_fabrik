<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Parameter;


use Fabrik\Helpers\Worker;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use Joomla\String\StringHelper;

class ParameterParser
{
	/**
	 * @var Input
	 * @since 4.0
	 */
	private $input;

	/**
	 * @var User
	 * @since 4.0
	 */
	private $user;

	/**
	 * @var array
	 * @since 4.0
	 */
	private $unused = [];

	/**
	 * ParameterParser constructor.
	 *
	 * @param Input $input
	 * @param User  $user
	 *
	 * @since 4.0
	 */
	public function __construct(Input $input, User $user)
	{
		$this->input = $input;
		$this->user  = $user;
	}

	/**
	 * @param array        $tagMatch
	 * @param ParameterBag $bag
	 *
	 * @since 4.0
	 */
	public function parse(array $tagMatch, ParameterBag $bag)
	{
		foreach ($this->getParameters($tagMatch[0]) as $match)
		{
			list($key, $value) = explode('=', $match);

			// $$$ hugh - deal with %20 as space in arguments
			$value = urldecode($value);

			switch ($key)
			{
				case 'view':
					$bag->setViewName(StringHelper::strtolower($value));
					break;
				case 'id':
				case 'formid':
				case 'listid':
					// Cast to int in case there are two spaces after value.
					$bag->setId((int) $value);
					break;
				case 'layout':
					$bag->setLayout($value);
					break;
				case 'row':
				case 'rowid':
					$row = $value;

					// When printing the content the rowid can't be passed in the querystring so don't set here
					if ($row !== '{rowid}')
					{
						if ($row === -1)
						{
							$row = $this->user->get('id');
						}
					}

					$bag->setRowId((string) $row);

					break;
				case 'element':
					// {fabrik view=element list=3 rowid=364 element=fielddatatwo}
					$bag->setViewName('list');
					$bag->setElement($value);
					break;
				case 'table':
				case 'list':
					$bag->setListId($value);
					break;
				case 'limit':
					$bag->setLimit($value);
					break;
				case 'usekey':
					$bag->setUseKey($value);
					break;
				case 'repeatcounter':
					$bag->setRepeatCounter($value);
					break;
				case 'showfilters':
					$bag->setShowFilters((bool) $value);
					break;
				case 'ajax':
					$bag->setAjax((bool) $value);
					break;
				// $$$ rob for these 2 grab the qs var in priority over the plugin settings
				case 'clearfilters':
					$clearFilters = (bool) $this->input->get('clearfilters', $value);
					$bag->setClearFilters($clearFilters);
					break;
				case 'resetfilters':
					$resetFilters = (bool) $this->input->get('resetfilters', $value);
					$bag->setResetFilters($resetFilters);
					break;
				default:
					/**
					 * These are later set as app->input vars if present in list view
					 * html_entity_decode to allow for content plugin values to contain &nbsp;
					 * Urlencode the value for plugin statements such as: asylum_events___start_date[condition]=>
					 */
					$this->unused[] = trim($key) . '=' . urlencode(html_entity_decode($value, ENT_NOQUOTES));
			}
		}
	}

	/**
	 * @return array
	 *
	 * @since 4.0
	 */
	public function getUnused(): array
	{
		return $this->unused;
	}

	/**
	 * @param string $tag
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	private function getParameters(string $tag): array
	{
		$tag = trim($tag, "{");
		$tag = trim($tag, "}");
		preg_replace('/[^A-Z|a-z|0-9]/', '_', $tag);

		$parsedTag  = $this->parseTag($tag);
		$parameters = explode(" ", $parsedTag);
		array_shift($parameters);

		return $parameters;
	}

	/**
	 * Parse the {fabrik} tag
	 *
	 * @param string $tag {fabrik} preg match
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function parseTag(string $tag): string
	{
		// $$$ hugh - see if we can remove formatting added by WYSIWYG editors
		$match = strip_tags($tag);
		$w     = new Worker();
		$match = preg_replace('/\s+/', ' ', $match);
		/* $$$ hugh - only replace []'s in value, not key, so we handle
		 * ranged filters and 'complex' filters
		 */
		$match2 = array();

		foreach (explode(" ", $match) as $m)
		{
			if (strstr($m, '='))
			{
				list($key, $val) = explode('=', $m);
				$val      = str_replace('[', '{', $val);
				$val      = str_replace(']', '}', $val);
				$match2[] = $key . '=' . $val;
			}
			else
			{
				$match2[] = $m;
			}
		}

		$match = implode(' ', $match2);
		$w->replaceRequest($match);

		// Stop [] for ranged filters from being removed
		// $match = str_replace('{}', '[]', $match);
		$match = $w->parseMessageForPlaceHolder($match);

		return $match;
	}
}