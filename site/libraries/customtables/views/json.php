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

defined('_JEXEC') or die();

class ViewJSON
{
	var CT $ct;

	function __construct(CT &$ct)
	{
		$this->ct = &$ct;
	}

	function render(string $pageLayoutContent, bool $obEndClean = true): ?string
	{
		$twig = new TwigProcessor($this->ct, $pageLayoutContent, false, true);
		$pageLayoutContent = $twig->process();

		if ($twig->errorMessage !== null)
			return (object)array('msg' => $twig->errorMessage, 'status' => 'error');

		if ($this->ct->Params->allowContentPlugins)
			CTMiscHelper::applyContentPlugins($pageLayoutContent);

		if ($obEndClean) {

			if (ob_get_contents()) ob_end_clean();

			$filename = $this->ct->Params->pageTitle;
			if (is_null($filename))
				$filename = 'ct';

			$filename = CTMiscHelper::makeNewFileName($filename, 'json');

			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Type: application/json; charset=utf-8');
			header("Pragma: no-cache");
			header("Expires: 0");

			die($pageLayoutContent);
		}
		return $pageLayoutContent;
	}
}
