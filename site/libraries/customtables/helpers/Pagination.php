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
defined('_JEXEC') or die();


class Pagination
{
	public int $total;
	public int $limit;
	public int $limitStart;
	public string $prefix;
	private bool $showArrowIcons;
	private string $toolbarIcons;

	public function __construct(int $total, int $limitStart, int $limit, string $prefix = '', bool $showArrowIcons = false, string $toolbarIcons)
	{
		$this->total = max(0, $total);
		$this->limit = max(1, $limit);
		$this->limitStart = $limitStart;
		$this->prefix = $prefix;
		$this->showArrowIcons = $showArrowIcons;
		$this->toolbarIcons = $toolbarIcons;
	}

	public function render(): string
	{
		$url = common::curPageURL();
		$url = CTMiscHelper::deleteURLQueryOption($url, $this->prefix . 'start');

		$pages = $this->getPages();
		if ($pages['total_pages'] <= 1) return '';

		$html = '<div style="display:inline-block;"><ul class="pagination">';

		$html .= '<li class="' . ($pages['current_page'] == 1 ? 'disabled ' : '') . 'page-item">';
		$html .= '<a class="page-link" href="' . ($pages['current_page'] > 1 ? $this->buildUrl($url, 1) : '#') . '">';
		$html .= $this->showArrowIcons ? Icons::iconStart($this->toolbarIcons) : 'Start';
		$html .= '</a></li>';

		$html .= '<li class="' . ($pages['prev_page'] ? 'page-item' : 'disabled page-item') . '">';
		$html .= '<a class="page-link" href="' . ($pages['prev_page'] ? $this->buildUrl($url, $pages['prev_page']) : '#') . '">';
		$html .= $this->showArrowIcons ? Icons::iconPrev($this->toolbarIcons) : 'Prev';
		$html .= '</a></li>';

		foreach ($pages['pages'] as $page) {
			$class = $page == $pages['current_page'] ? 'active page-item' : 'page-item';
			$ariaCurrent = $page == $pages['current_page'] ? ' aria-current="true"' : '';
			$html .= "<li class='$class'><a title='$page' href=\"" . $this->buildUrl($url, $page) . "\" class='page-link' $ariaCurrent>$page</a></li>";
		}

		$html .= '<li class="' . ($pages['next_page'] ? 'page-item' : 'disabled page-item') . '">';
		$html .= '<a class="page-link" href="' . ($pages['next_page'] ? $this->buildUrl($url, $pages['next_page']) : '#') . '">';
		$html .= $this->showArrowIcons ? Icons::iconNext($this->toolbarIcons) : 'Next';
		$html .= '</a></li>';

		$html .= '<li class="' . ($pages['current_page'] == $pages['total_pages'] ? 'disabled ' : '') . 'page-item">';
		$html .= '<a class="page-link" href="' . ($pages['current_page'] < $pages['total_pages'] ? $this->buildUrl($url, $pages['total_pages']) : '#') . '">';
		$html .= $this->showArrowIcons ? Icons::iconEnd($this->toolbarIcons) : 'End';
		$html .= '</a></li>';

		$html .= '</ul></div>';
		return $html;
	}

	public function getPages(): array
	{
		$pagesTotal = (int)ceil($this->total / $this->limit);
		echo '$pagesTotal:' . $pagesTotal . '<br/>';
		echo '$this->limitStart:' . $this->limitStart . '<br/>';
		echo '$this->limit:' . $this->limit . '<br/>';
		$currentPage = (int)($this->limitStart / $this->limit) + 1;

		echo '$currentPage:' . $currentPage . '*<br/>';

		return [
			'total_pages' => $pagesTotal,
			'current_page' => $currentPage,
			'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
			'next_page' => $currentPage < $pagesTotal ? $currentPage + 1 : null,
			'pages' => range(1, $pagesTotal)
		];
	}

	private function buildUrl(string $url, int $page): string
	{
		$start = ($page - 1) * $this->limit;
		return $url . (strpos($url, '?') === false ? '?' : '&') . $this->prefix . 'start=' . $start;
	}
}
