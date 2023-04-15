<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use JoomlaBasicMisc;
use CustomTables\Catalog;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class CatalogExportCSV
{
    var CT $ct;
    var Catalog $catalog;
    var bool $error = false;

    public function __construct(CT &$ct, Catalog $catalog)
    {
        $this->ct = &$ct;
        $this->catalog = $catalog;

        if (function_exists('mb_convert_encoding')) {
            $this->error = false;
        } else {
            $msg = '"mbstring" PHP extension not installed.<br/>
				You need to install this extension. It depends on of your operating system, here are some examples:<br/><br/>
				sudo apt-get install php-mbstring  # Debian, Ubuntu<br/>
				sudo yum install php-mbstring  # RedHat, Fedora, CentOS<br/><br/>
				Uncomment the following line in php.ini, and restart the Apache server:<br/>
				extension=mbstring<br/><br/>
				Then restart your webs\' server. Example:<br/>service apache2 restart';

            $this->ct->app->appenqueueMessage($msg, 'error');
            $this->error = true;
        }
    }

    public function render(?string $layout = null, ?int $layoutType = null): string
    {
        $pageLayoutContent = $this->catalog->render($layout);
        $pageLayoutContent = preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $pageLayoutContent);
        /*
        $pageLayoutContent = str_ireplace('</tr>', '****linebrake****', $pageLayoutContent);
        */
        if ($this->ct->Params->allowContentPlugins)
            JoomlaBasicMisc::applyContentPlugins($pageLayoutContent);

//$pageLayoutContent = str_ireplace('****linebrake****', "\r" . "\n", $pageLayoutContent);

//echo chr(255).chr(254);
//$bom = pack("CCC", 0xef, 0xbb, 0xbf);
//echo $bom.mb_convert_encoding($pageLayoutContent, 'UTF-16LE', 'UTF-8');
        return $pageLayoutContent;
    }
}