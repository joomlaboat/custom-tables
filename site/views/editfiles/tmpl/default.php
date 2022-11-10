<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

?>
<script>
    var idList = [<?php echo implode(',', $this->idList) ?>];

    function DeleteFiles(fileid) {
        var count = 0;
        var fileids = "";

        for (var i = 0; i < idList.length; i++) {
            if (document.getElementById("esfile" + idList[i]).checked) {
                count++;
                fileids += "*" + idList[i];
            }
        }
        if (count == 0) {
            alert("<?php echo JoomlaBasicMisc::JTextExtended("Select Files First"); ?>");
            return false;
        }

        if (confirm("<?php echo JoomlaBasicMisc::JTextExtended("Are you sure to delete?"); ?> " + count + " <?php echo JoomlaBasicMisc::JTextExtended("file(s)"); ?>?")) {

            document.getElementById("fileedit_task").value = "delete";
            document.getElementById("fileids").value = fileids;
            document.getElementById("eseditfiles").submit();
        }

        return true;
    }

    function SelectAll(s) {


        for (var i = 0; i < idList.length; i++) {
            document.getElementById("esfile" + idList[i]).checked = s;

        }
    }

    function SaveOrder() {

        document.getElementById("fileedit_task").value = "saveorder";
        document.getElementById("eseditfiles").submit()

    }

    function ShowAddFile() {
        var obj = document.getElementById("addfileblock");
        if (obj.style.display == "block")
            obj.style.display = "none";
        else
            obj.style.display = "block";
    }

</script>


<h2><?php echo $this->FileBoxTitle; ?></h2>

<form action="index.php?Itemid=<?php echo $this->jinput->get('Itemid', 0, 'INT'); ?>" method="POST" name="eseditfiles"
      id="eseditfiles" enctype="multipart/form-data">
    <?php
    $toolbar = '
	<div style="height:40px;">
		<div style="float:left;">
			<input type="button" class="button" value="' . JoomlaBasicMisc::JTextExtended('Finish') . '" onClick=\'this.form.task.value="cancel";this.form.submit()\'>
		</div>
		<div style="float:right;">
			<input type="button" class="button" value="' . JoomlaBasicMisc::JTextExtended('Delete') . '" onClick=\'DeleteFiles()\'>
		</div>
	</div>
	';

    echo $toolbar;

    ?>

    <fieldset class="adminform">
        <legend><?php echo JoomlaBasicMisc::JTextExtended("File Manager"); ?></legend>

        <div name="addfileblock" id="addfileblock" style="display:block;">
            <h2><?php echo JoomlaBasicMisc::JTextExtended("Add New File"); ?></h2>
            <table class="bigtext">
                <tr>
                    <td><?php echo JoomlaBasicMisc::JTextExtended("Upload File"); ?>:<br/></td>
                    <td>
                        <?php //<input type="hidden" name="max File Size" value="<?php echo $max_file_size;  ?>
                        <input name="uploadedfile" type="file"/><input type="button" class="button" value="Upload"
                                                                       onClick='this.form.task.value="add";this.form.submit()'>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php echo JoomlaBasicMisc::JTextExtended("COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE") . ': ' . JoomlaBasicMisc::formatSizeUnits($this->max_file_size); ?>
                        <br/>
                        <?php echo JoomlaBasicMisc::JTextExtended("File Formats"); ?>:
                        <b><?php echo str_replace(' ', ', ', $this->allowedExtensions); ?></b>
                    </td>
                </tr>
            </table>
            <br/>
        </div>

        <?php echo $this->drawFiles(); ?>

        <input type="hidden" name="option" value="com_customtables"/>
        <input type="hidden" name="view" value="editfiles"/>
        <input type="hidden" name="Itemid" value="<?php echo $this->jinput->get('Itemid', 0, 'INT'); ?>"/>
        <input type="hidden" name="returnto" value="<?php echo $this->jinput->get('returnto', '', 'BASE64');; ?>"/>

        <input type="hidden" name="vlu" id="vlu" value=""/>
        <input type="hidden" name="task" id="fileedit_task" value=""/>
        <input type="hidden" name="fileids" id="fileids" value=""/>
        <input type="hidden" name="listing_id" id="listing_id" value="<?php echo $this->listing_id; ?>"/>


        <input type="hidden" name="fileboxname" id="fileboxname" value="<?php echo $this->fileboxname; ?>"/>

    </fieldset>
</form>
