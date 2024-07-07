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
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\IntegrityChecks;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$document = Factory::getDocument();
$document->addCustomTag('<script src="' . common::UriRoot(true) . '/media/vendor/jquery/js/jquery.min.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_PLUGIN_WEBPATH . 'js/raphael.min.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/diagram.js"></script>');
$document->addCustomTag('<style>
        #canvas_container {
            width: 100%;
            min-height: ' . (count($this->diagram->tables) > 50 ? '4000' : '2000') . 'px;
            border: 1px solid #aaa;
        }
    </style>');
?>

<form action="<?php echo Route::_('index.php?option=com_customtables&view=databasecheck'); ?>" method="post"
      name="adminForm" id="adminForm">


    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">

                <?php
                //$this->filterForm = $this->get('FilterForm');
                //echo $this->filterForm->renderField('tablecategory'); ?>

                <?php echo HTMLHelper::_('uitab.startTabSet', 'schemaTab', ['active' => 'diagram', 'recall' => true, 'breakpoint' => 768]); ?>

                <?php echo HTMLHelper::_('uitab.addTab', 'schemaTab', 'diagram', common::translate('COM_CUSTOMTABLES_TABLES_DIAGRAM')); ?>

                <div id="canvas_container"></div>

                <?php echo HTMLHelper::_('uitab.endTab'); ?>

                <?php echo HTMLHelper::_('uitab.addTab', 'schemaTab', 'checks', common::translate('COM_CUSTOMTABLES_TABLES_CHECKS')); ?>

                <?php
                $result = IntegrityChecks::check($this->ct);

                if (count($result) > 0)
                    echo '<ol><li>' . implode('</li><li>', $result) . '</li></ol>';
                else
                    echo '<p>Database table structure is up-to-date.</p>';

                ?>

                <?php echo HTMLHelper::_('uitab.endTab'); ?>
                <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

                <script>

                    TableCategoryID = <?php echo (int)$this->state->get('list.tablecategory'); ?>;
                    AllTables = <?php echo common::ctJsonEncode($this->diagram->tables); ?>;

                </script>

                <input type="hidden" name="task" value=""/>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>