<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @version 1.6.1
 * @author Ivan Komlev< <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

function getToolBarForm($Itemid,$returnto)
{

    echo '
		<form action="" method="post" name="escatalogform" id="escatalogform">
		<input type="hidden" name="option" value="com_customtables" />
		<input type="hidden" name="view" id="view" value="" />
		<input type="hidden" name="layout" id="layout" value="" />
		<input type="hidden" name="task" id="task" value="" />
        <input type="hidden" name="listing_id" id="listing_id" value="" />
		<input type="hidden" name="Itemid" id="Itemid" value="'.$Itemid.'" />
		<input type="hidden" name="returnto" id="returnto" value="'.base64_encode ($returnto).'" />
		';


			echo '
		<script type="text/javascript" language="javascript">

        function DeletePropertyObject(ObjectName, objid)
        {
                if (confirm(\''.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DO_U_WANT_TO_DELETE').' "\'+ObjectName+\'" ?\')) {

						o1=document.getElementById("view");
						o2=document.getElementById("layout");
                        o3=document.getElementById("task");
                        o4=document.getElementById("listing_id");

						o1.value="catalog";
						o2.value="currentuser";
						o3.value="delete";
						o4.value=objid;


						o5=document.getElementById("escatalogform")
						o5.submit();

                }
        }

		</script>
		';

}
