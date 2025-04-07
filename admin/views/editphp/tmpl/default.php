<form action="index.php" method="post" id="adminForm">

	<?php if (empty($this->encodedPath)) : ?>
		<div class="control-group">
			<label for="filename">New File Name (e.g. myfile.php):</label>
			<input type="text" name="filename" id="filename" required/>
		</div>
	<?php endif; ?>

	<textarea name="code"
			  id="code"
			  style="width: 100%; height: 500px;"><?php echo htmlspecialchars($this->fileContent); ?></textarea>


	<input type="hidden" name="option" value="com_customtables"/>
	<input type="hidden" name="task" value="editphp.save"/>
	<input type="hidden" name="file" value="<?php echo $this->encodedPath; ?>"/>

	<?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>

	<button type="submit" class="btn btn-primary">Save</button>
</form>

<?php
/*
$doc = \Joomla\CMS\Factory::getDocument();
$doc->addScriptDeclaration("
	document.addEventListener('DOMContentLoaded', function () {
		if (typeof CodeMirror !== 'undefined') {
			CodeMirror.fromTextArea(document.getElementById('code'), {
				lineNumbers: true,
				mode: 'application/x-httpd-php',
				matchBrackets: true,
				indentUnit: 4,
				indentWithTabs: true,
				lineWrapping: true
			});
		}
	});
");
*/

