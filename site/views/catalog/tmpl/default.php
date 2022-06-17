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

if ($this->ct->Env->frmt and is_null($this->ct->Params->listing_id)) //there is no need to have a header if we are loading a single record.
{
    if ($this->ct->Params->showPageHeading) {
        $title = JoomlaBasicMisc::JTextExtended($this->ct->Params->pageTitle);
        echo '
		<div class="page-header' . $this->ct->Params->pageClassSFX . '">
			<h2 itemprop="headline">' . $title . '</h2>
		</div>
		';
    }
}

echo $this->catalog->render();

?>

<!-- Modal content -->
<div id="ctModal" class="ctModal">
    <div id="ctModal_box" class="ctModal_content">
        <span id="ctModal_close" class="ctModal_close">&times;</span>
        <div id="ctModal_content"></div>
    </div>
</div>
<!-- end of the modal -->