<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Parameter;

use Joomla\Input\Input;

class ParameterBag
{
	/**
	 * @var null|string
	 * @since 4.0
	 */
	private $viewName;

	/**
	 * @var int
	 * @since 4.0
	 */
	private $id = 0;

	/**
	 * @var string
	 * @since 4.0
	 */
	private $layout = 'default';

	/**
	 * @var string|null
	 * @since 4.0
	 */
	private $rowId;

	/**
	 * @var null|string
	 * @since 4.0
	 */
	private $element;

	/**
	 * @var int|string
	 * @since 4.0
	 */
	private $repeatCounter = 0;

	/**
	 * @var int|null
	 * @since 4.0
	 */
	private $listId;

	/**
	 * @var null|int
	 * @since 4.0
	 */
	private $limit;

	/**
	 * @var string|null
	 * @since 4.0
	 */
	private $useKey;

	/**
	 * @var bool
	 * @since 4.0
	 */
	private $showFilters = false;

	/**
	 * @var bool
	 * @since 4.0
	 */
	private $clearFilters = false;

	/**
	 * @var bool
	 * @since version
	 */
	private $resetFilters = false;

	/**
	 * @var bool
	 * @since 4.0
	 */
	private $ajax = true;

	/**
	 * @var array
	 * @since 4.0
	 */
	private $unused = [];

	/**
	 * ParameterBag constructor.
	 *
	 * @param Input $input
	 *
	 * @since 4.0
	 */
	public function __construct(Input $input)
	{
		// Allow plugin to reference the origin rowid in the URL
		$this->rowId = $input->get('rowid');

		// Was defaulting to 1 but that forced filters to show in cal viz even with showfilter=no option turned on
		$this->showFilters  = $input->get('showfilters', null);
		$this->clearFilters = $input->get('clearfilters', 0);
		$this->resetFilters = $input->get('resetfilters', 0);
	}

	/**
	 * @return string|null
	 *
	 * @since 4.0
	 */
	public function getViewName(): ?string
	{
		return $this->viewName;
	}

	/**
	 * @param string|null $viewName
	 *
	 * @since 4.0
	 */
	public function setViewName(?string $viewName): void
	{
		$this->viewName = $viewName;
	}

	/**
	 * @return int
	 *
	 * @since 4.0
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @since 4.0
	 */
	public function setId(int $id): void
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getLayout(): string
	{
		return $this->layout;
	}

	/**
	 * @param string $layout
	 *
	 * @since 4.0
	 */
	public function setLayout(string $layout): void
	{
		$this->layout = $layout;
	}

	/**
	 * @return string|null
	 *
	 * @since 4.0
	 */
	public function getElement(): ?string
	{
		return $this->element;
	}

	/**
	 * @param string|null $element
	 *
	 * @since 4.0
	 */
	public function setElement(?string $element): void
	{
		$this->element = $element;
	}

	/**
	 * @return int|string
	 *
	 * @since 4.0
	 */
	public function getRepeatCounter()
	{
		return $this->repeatCounter;
	}

	/**
	 * @param int|string $repeatCounter
	 *
	 * @since 4.0
	 */
	public function setRepeatCounter($repeatCounter): void
	{
		$this->repeatCounter = $repeatCounter;
	}

	/**
	 * @return int|null
	 *
	 * @since 4.0
	 */
	public function getListId(): ?int
	{
		return $this->listId;
	}

	/**
	 * @param int|null $listId
	 *
	 * @since 4.0
	 */
	public function setListId(?int $listId): void
	{
		$this->listId = $listId;
	}

	/**
	 * @return int|null
	 *
	 * @since 4.0
	 */
	public function getLimit(): ?int
	{
		return $this->limit;
	}

	/**
	 * @param int|null $limit
	 *
	 * @since 4.0
	 */
	public function setLimit(?int $limit): void
	{
		$this->limit = $limit;
	}

	/**
	 * @return string|null
	 *
	 * @since 4.0
	 */
	public function getUseKey(): ?string
	{
		return $this->useKey;
	}

	/**
	 * @param string|null $useKey
	 *
	 * @since 4.0
	 */
	public function setUseKey(?string $useKey): void
	{
		$this->useKey = $useKey;
	}

	/**
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function getShowFilters(): bool
	{
		return $this->showFilters;
	}

	/**
	 * @param bool $showFilters
	 *
	 * @since 4.0
	 */
	public function setShowFilters(bool $showFilters): void
	{
		$this->showFilters = $showFilters;
	}

	/**
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function getClearFilters(): bool
	{
		return $this->clearFilters;
	}

	/**
	 * @param bool $clearFilters
	 *
	 * @since 4.0
	 */
	public function setClearFilters(bool $clearFilters): void
	{
		$this->clearFilters = $clearFilters;
	}

	/**
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function getResetFilters(): bool
	{
		return $this->resetFilters;
	}

	/**
	 * @param bool $resetFilters
	 *
	 * @since 4.0
	 */
	public function setResetFilters(bool $resetFilters): void
	{
		$this->resetFilters = $resetFilters;
	}

	/**
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function isAjax(): bool
	{
		return $this->ajax;
	}

	/**
	 * @param bool $ajax
	 *
	 * @since 4.0
	 */
	public function setAjax(bool $ajax): void
	{
		$this->ajax = $ajax;
	}

	/**
	 * @return string|null
	 *
	 * @since 4.0
	 */
	public function getRowId(): ?string
	{
		return $this->rowId;
	}

	/**
	 * @param string $rowId
	 *
	 * @since 4.0
	 */
	public function setRowId(string $rowId): void
	{
		$this->rowId = $rowId;
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
	 * @param string $key
	 * @param mixed  $value
	 *
	 *
	 * @since 4.0
	 */
	public function appendToUnused(string $key, $value): void
	{
		$this->unused[$key] = $value;
	}
}