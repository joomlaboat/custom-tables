<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

if ($this->version >= 4) {
	$wa = $this->document->getWebAssetManager();
	$wa->useScript('keepalive')->useScript('form.validate');
} else {
	HTMLHelper::_('behavior.formvalidation');
	HTMLHelper::_('behavior.keepalive');
}
?>

<form action="<?php echo Route::_('index.php?option=com_customtables&layout=edit&id=' . (int)$this->item->id . $this->referral); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
    <div class="form-horizontal">

        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">

                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('categoryname'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('categoryname'); ?></div>
                </div>

            </div>
        </div>

        <div>
            <input type="hidden" name="task" value="categories.edit"/>
			<?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>
</form>