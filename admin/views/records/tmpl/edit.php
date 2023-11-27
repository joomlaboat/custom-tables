<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage views/records/tmpl/edit.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Joomla\CMS\Factory;

if ($this->ct->Env->version >= 4) {
	$wa = $this->document->getWebAssetManager();
	$wa->useScript('keepalive')->useScript('form.validate');
} else {
	JHtml::_('behavior.formvalidation');
	JHtml::_('behavior.keepalive');
}

$document = Factory::getDocument();
$document->addStyleSheet(JURI::root(true) . "/components/com_customtables/libraries/customtables/media/css/style.css");

$editForm = new Edit($this->ct);
$editForm->layoutContent = $this->pageLayout;
echo $editForm->render($this->row, $this->formLink, 'adminForm');
