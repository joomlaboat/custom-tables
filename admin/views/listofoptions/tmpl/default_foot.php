<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>

<tfoot>
		<tr>
			<td colspan="<?php echo (count($this->LanguageList)+4); ?>">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
</tfoot>
