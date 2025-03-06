<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
use Exception;

defined('_JEXEC') or die();


class Pagination
{
	public int $total;
	public int $limit;
	public int $limitStart;
	public string $prefix;
	private bool $showArrowIcons;
	private string $toolbarIcons;

	public function __construct(int $total, int $limitStart, int $limit, string $prefix = '', bool $showArrowIcons = false, string $toolbarIcons = '')
	{
		$this->total = max(0, $total);
		$this->limit = max(1, $limit);
		$this->limitStart = $limitStart;
		$this->prefix = $prefix;
		$this->showArrowIcons = $showArrowIcons;
		$this->toolbarIcons = $toolbarIcons;
	}

	/**
	 * @throws Exception
	 * @since 3.5.7
	 */
	public function render(): string
	{
		if (defined('_JEXEC'))
			return $this->renderJoomla();
		elseif (defined('WPINC'))
			return $this->renderWP();
		else
			throw new Exception("{{ html.pagination }} not supported in this version.");
	}

	public function renderJoomla(): string
	{
		$url = common::curPageURL();
		$url = CTMiscHelper::deleteURLQueryOption($url, $this->prefix . 'start');

		$pages = $this->getPages();
		if ($pages['total_pages'] <= 1) return '';

		$paginationClass = defined('WPINC') ? 'wp-pagenavi' : 'pagination';
		$pageItemClass = defined('WPINC') ? 'pager' : 'page-item';
		$pageLinkClass = defined('WPINC') ? 'page-numbers' : 'page-link';

		$html = '<ul class="' . $paginationClass . '">';

		$html .= '<li class="' . ($pages['current_page'] == 1 ? 'disabled ' : '') . $pageItemClass . '"><a class="' . $pageLinkClass . '" href="' . ($pages['current_page'] > 1 ? $this->buildUrl($url, 1) : '#') . '">' . ($this->showArrowIcons ? Icons::iconStart($this->toolbarIcons) : 'Start') . '</a></li>';

		$html .= '<li class="' . ($pages['prev_page'] ? $pageItemClass : 'disabled ' . $pageItemClass) . '"><a class="' . $pageLinkClass . '" href="' . ($pages['prev_page'] ? $this->buildUrl($url, $pages['prev_page']) : '#') . '">' . ($this->showArrowIcons ? Icons::iconPrev($this->toolbarIcons) : 'Prev') . '</a></li>';

		foreach ($pages['pages'] as $page) {
			if ($page === '...') {
				$html .= "<li class='disabled $pageItemClass'><span class='$pageLinkClass'>...</span></li>";
			} else {
				$class = $page == $pages['current_page'] ? 'active ' . $pageItemClass : $pageItemClass;
				$ariaCurrent = $page == $pages['current_page'] ? ' aria-current="true"' : '';
				$html .= "<li class='$class'><a title='$page' href=\"" . $this->buildUrl($url, $page) . "\" class='$pageLinkClass' $ariaCurrent>$page</a></li>";
			}
		}

		$html .= '<li class="' . ($pages['next_page'] ? $pageItemClass : 'disabled ' . $pageItemClass) . '"><a class="' . $pageLinkClass . '" href="' . ($pages['next_page'] ? $this->buildUrl($url, $pages['next_page']) : '#') . '">' . ($this->showArrowIcons ? Icons::iconNext($this->toolbarIcons) : 'Next') . '</a></li>';

		$html .= '<li class="' . ($pages['current_page'] == $pages['total_pages'] ? 'disabled ' : '') . $pageItemClass . '"><a class="' . $pageLinkClass . '" href="' . ($pages['current_page'] < $pages['total_pages'] ? $this->buildUrl($url, $pages['total_pages']) : '#') . '">' . ($this->showArrowIcons ? Icons::iconEnd($this->toolbarIcons) : 'End') . '</a></li>';

		$html .= '</ul>';
		return $html;
	}

	public function getPages(): array
	{
		$pagesTotal = (int)ceil($this->total / $this->limit);
		$currentPage = (int)($this->limitStart / $this->limit) + 1;
		$pages = [];

		if ($pagesTotal <= 7) {
			$pages = range(1, $pagesTotal);
		} else {
			$pages = [1, 2];
			if ($currentPage > 4) {
				$pages[] = '...';
			}

			for ($i = max(3, $currentPage - 1); $i <= min($pagesTotal - 2, $currentPage + 1); $i++) {
				$pages[] = $i;
			}

			if ($currentPage < $pagesTotal - 3) {
				$pages[] = '...';
			}

			$pages[] = $pagesTotal - 1;
			$pages[] = $pagesTotal;
		}

		return [
			'total_pages' => $pagesTotal,
			'current_page' => $currentPage,
			'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
			'next_page' => $currentPage < $pagesTotal ? $currentPage + 1 : null,
			'pages' => $pages
		];
	}

	private function buildUrl(string $url, int $page): string
	{
		$start = ($page - 1) * $this->limit;
		return $url . (strpos($url, '?') === false ? '?' : '&') . $this->prefix . 'start=' . $start;
	}

	protected function renderWP(): string
	{
		$url = common::curPageURL();
		$url = CTMiscHelper::deleteURLQueryOption($url, $this->prefix . 'start');

		$pages = $this->getPages();
		if ($pages['total_pages'] <= 1) return '';

		$paginationClass = 'navigation pagination';
		$pageContainerClass = 'nav-links';
		$pageItemClass = 'page-numbers';

		$html = "<div class='$paginationClass'><div class='$pageContainerClass'>";

		if ($pages['current_page'] > 1) {
			$html .= '<a class="prev page-numbers" href="' . $this->buildUrl($url, 1) . '">' . ($this->showArrowIcons ? Icons::iconStart($this->toolbarIcons) : '« First') . '</a>';
		}

		if ($pages['prev_page']) {
			$html .= '<a class="prev page-numbers" href="' . $this->buildUrl($url, $pages['prev_page']) . '">' . ($this->showArrowIcons ? Icons::iconPrev($this->toolbarIcons) : '‹ Prev') . '</a>';
		}

		foreach ($pages['pages'] as $page) {
			if ($page == '...') {
				$html .= '<span class="page-numbers dots">…</span>';
			} else {
				$class = $page == $pages['current_page'] ? 'current' : '';
				$ariaCurrent = $page == $pages['current_page'] ? ' aria-current="page"' : '';

				if ($page == $pages['current_page'])
					$html .= "<span class='$pageItemClass $class' $ariaCurrent>$page</span>";
				else
					$html .= "<a href=\"" . $this->buildUrl($url, $page) . "\" class='$pageItemClass $class' $ariaCurrent>$page</a>";
			}
		}

		if ($pages['next_page']) {
			$html .= '<a class="next page-numbers" href="' . $this->buildUrl($url, $pages['next_page']) . '">' . ($this->showArrowIcons ? Icons::iconNext($this->toolbarIcons) : 'Next ›') . '</a>';
		}

		if ($pages['current_page'] < $pages['total_pages']) {
			$html .= '<a class="next page-numbers" href="' . $this->buildUrl($url, $pages['total_pages']) . '">' . ($this->showArrowIcons ? Icons::iconEnd($this->toolbarIcons) : 'Last »') . '</a>';
		}

		$html .= "</div></div>";
		return $html;
	}

	public function renderJoomla_Old(): string
	{
		$url = common::curPageURL();
		$url = CTMiscHelper::deleteURLQueryOption($url, $this->prefix . 'start');


		$pages = $this->getPages();
		if ($pages['total_pages'] <= 1) return '';

		$paginationClass = defined('WPINC') ? 'wp-pagenavi' : 'pagination';
		$pageItemClass = defined('WPINC') ? 'pager' : 'page-item';
		$pageLinkClass = defined('WPINC') ? 'page-numbers' : 'page-link';

		$html = '<ul class="' . $paginationClass . '">';

		$html .= '<li class="' . ($pages['current_page'] == 1 ? 'disabled ' : '') . $pageItemClass . '">';
		$html .= '<a class="' . $pageLinkClass . '" href="' . ($pages['current_page'] > 1 ? $this->buildUrl($url, 1) : '#') . '">';
		$html .= $this->showArrowIcons ? Icons::iconStart($this->toolbarIcons) : 'Start';
		$html .= '</a></li>';

		$html .= '<li class="' . ($pages['prev_page'] ? $pageItemClass : 'disabled ' . $pageItemClass) . '">';
		$html .= '<a class="' . $pageLinkClass . '" href="' . ($pages['prev_page'] ? $this->buildUrl($url, $pages['prev_page']) : '#') . '">';
		$html .= $this->showArrowIcons ? Icons::iconPrev($this->toolbarIcons) : 'Prev';
		$html .= '</a></li>';

		foreach ($pages['pages'] as $page) {
			$class = $page == $pages['current_page'] ? 'active ' . $pageItemClass : $pageItemClass;
			$ariaCurrent = $page == $pages['current_page'] ? ' aria-current="true"' : '';
			$html .= "<li class='$class'><a title='$page' href=\"" . $this->buildUrl($url, $page) . "\" class='$pageLinkClass' $ariaCurrent>$page</a></li>";
		}

		$html .= '<li class="' . ($pages['next_page'] ? $pageItemClass : 'disabled ' . $pageItemClass) . '">';
		$html .= '<a class="' . $pageLinkClass . '" href="' . ($pages['next_page'] ? $this->buildUrl($url, $pages['next_page']) : '#') . '">';
		$html .= $this->showArrowIcons ? Icons::iconNext($this->toolbarIcons) : 'Next';
		$html .= '</a></li>';

		$html .= '<li class="' . ($pages['current_page'] == $pages['total_pages'] ? 'disabled ' : '') . $pageItemClass . '">';
		$html .= '<a class="' . $pageLinkClass . '" href="' . ($pages['current_page'] < $pages['total_pages'] ? $this->buildUrl($url, $pages['total_pages']) : '#') . '">';
		$html .= $this->showArrowIcons ? Icons::iconEnd($this->toolbarIcons) : 'End';
		$html .= '</a></li>';

		$html .= '</ul>';
		return $html;
	}

	public function getPagesOld(): array
	{
		$pagesTotal = (int)ceil($this->total / $this->limit);
		$currentPage = (int)($this->limitStart / $this->limit) + 1;

		return [
			'total_pages' => $pagesTotal,
			'current_page' => $currentPage,
			'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
			'next_page' => $currentPage < $pagesTotal ? $currentPage + 1 : null,
			'pages' => range(1, $pagesTotal)
		];
	}

	protected function renderWP_Old(): string
	{
		$url = common::curPageURL();
		$url = CTMiscHelper::deleteURLQueryOption($url, $this->prefix . 'start');

		$pages = $this->getPages();
		if ($pages['total_pages'] <= 1) return '';

		// Standard WordPress CSS classes
		$paginationClass = 'navigation pagination';
		$pageContainerClass = 'nav-links';
		$pageItemClass = 'page-numbers';

		$html = "<div class='$paginationClass'><div class='$pageContainerClass'>";

		// First Page
		if ($pages['current_page'] > 1) {
			$html .= '<a class="prev page-numbers" href="' . $this->buildUrl($url, 1) . '">';
			$html .= $this->showArrowIcons ? Icons::iconStart($this->toolbarIcons) : '« First';
			$html .= '</a>';
		}

		// Previous Page
		if ($pages['prev_page']) {
			$html .= '<a class="prev page-numbers" href="' . $this->buildUrl($url, $pages['prev_page']) . '">';
			$html .= $this->showArrowIcons ? Icons::iconPrev($this->toolbarIcons) : '‹ Prev';
			$html .= '</a>';
		}

		// Page Numbers
		foreach ($pages['pages'] as $page) {
			if ($page == '...') {
				$html .= '<span class="page-numbers dots">…</span>';
			} else {
				$class = $page == $pages['current_page'] ? 'current' : '';
				$ariaCurrent = $page == $pages['current_page'] ? ' aria-current="page"' : '';
				$html .= "<a href=\"" . $this->buildUrl($url, $page) . "\" class='$pageItemClass $class' $ariaCurrent>$page</a>";
			}
		}

		// Next Page
		if ($pages['next_page']) {
			$html .= '<a class="next page-numbers" href="' . $this->buildUrl($url, $pages['next_page']) . '">';
			$html .= $this->showArrowIcons ? Icons::iconNext($this->toolbarIcons) : 'Next ›';
			$html .= '</a>';
		}

		// Last Page
		if ($pages['current_page'] < $pages['total_pages']) {
			$html .= '<a class="next page-numbers" href="' . $this->buildUrl($url, $pages['total_pages']) . '">';
			$html .= $this->showArrowIcons ? Icons::iconEnd($this->toolbarIcons) : 'Last »';
			$html .= '</a>';
		}

		$html .= "</div></div>";

		return $html;
	}
}
