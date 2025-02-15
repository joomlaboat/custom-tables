<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage views/records/tmpl/edit.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\Edit;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

if (CUSTOMTABLES_JOOMLA_MIN_4) {
	$document = Factory::getApplication()->getDocument();
	$wa = $document->getWebAssetManager();
	$wa->useScript('keepalive')->useScript('form.validate');
} else {
	HTMLHelper::_('behavior.formvalidation');
	HTMLHelper::_('behavior.keepalive');
}

common::loadJSAndCSS($this->ct->Params, $this->ct->Env, $this->ct->Table->fieldInputPrefix);

$editForm = new Edit($this->ct);
$editForm->layoutContent = $this->pageLayout;
echo $editForm->render($this->row, $this->formLink, 'adminForm');
