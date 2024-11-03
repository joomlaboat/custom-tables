<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

defined('_JEXEC') or die();

use Exception;

class Tables
{
    var CT $ct;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
    }

    /**
     * @throws Exception
     * @since 3.2.9
     */
    function loadRecords(string $filter = '', ?string $orderby = null, int $limit = 0, string $groupBy = ''): ?bool
    {
        $this->ct->Table->recordcount = 0;
        $this->ct->setFilter($filter, 2);

        //Grouping
        $this->ct->GroupBy = null;
        if (!empty($groupBy)) {
            $tempFieldRow = $this->ct->Table->getFieldByName($groupBy);
            if ($tempFieldRow !== null)
                $this->ct->GroupBy = $tempFieldRow['realfieldname'];
        }

        $this->ct->Ordering->ordering_processed_string = $orderby ?? '';
        $this->ct->Ordering->parseOrderByString();

        $this->ct->Limit = $limit;
        $this->ct->LimitStart = 0;

        $this->ct->getRecords(false, $limit);
        return true;
    }
}
