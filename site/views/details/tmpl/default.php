<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\TwigProcessor;

$this->ct->document->addScript(JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/base64.js');
$this->ct->document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/catalog.js" type="text/javascript"></script>');
$this->ct->document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/ajax.js"></script>');
$this->ct->document->addCustomTag('<link href="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/css/style.css" type="text/css" rel="stylesheet" >');

if ($this->ct->Env->legacysupport) {
    require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'tagprocessor' . DIRECTORY_SEPARATOR . 'itemtags.php');
    $LayoutProc = new LayoutProcessor($this->ct);
    $LayoutProc->layout = $this->layoutDetailsContent;
    $this->layoutDetailsContent = $LayoutProc->fillLayout($this->row);
}

$twig = new TwigProcessor($this->ct, $this->layoutDetailsContent);
$results = $twig->process($this->row);

if ($this->ct->Params->allowContentPlugins)
    JoomlaBasicMisc::applyContentPlugins($results);

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
