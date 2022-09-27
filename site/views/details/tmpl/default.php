<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

$this->ct->loadJSAndCSS();

$results = $this->details->render();

if ($this->ct->Env->clean) {
    if ($this->ct->Env->frmt == 'csv') {
        $filename = JoomlaBasicMisc::makeNewFileName($this->ct->document->getTitle(), 'csv');

        if (ob_get_contents())
            ob_end_clean();

        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: text/csv; charset=utf-8');
        header("Pragma: no-cache");
        header("Expires: 0");

        echo mb_convert_encoding($results, 'UTF-16LE', 'UTF-8');

        die;//clean exit
    } elseif ($this->ct->Env->frmt == 'xml') {
        $filename = JoomlaBasicMisc::makeNewFileName($this->ct->document->getTitle(), 'xml');

        if (ob_get_contents())
            ob_end_clean();

        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: text/xml; charset=utf-8');
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    echo $results;
    die;//clean exit
}

if ($this->ct->Params->showPageHeading) : ?>
    <div class="page-header<?php echo $this->escape($this->ct->Params->pageClassSFX); ?>">
        <h2 itemprop="headline"><?php echo JoomlaBasicMisc::JTextExtended($this->ct->document->getTitle()); ?></h2>
    </div>
<?php endif;

echo $results;

?>

<!-- Modal content -->
<div id="ctModal" class="ctModal">
    <div id="ctModal_box" class="ctModal_content">
        <span id="ctModal_close" class="ctModal_close">&times;</span>
        <div id="ctModal_content"></div>
    </div>
</div>
<!-- end of the modal -->
