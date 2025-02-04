/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// Add event listeners to toolbar buttons
document.addEventListener('DOMContentLoaded', function () {
	// Find all toolbar buttons
	const buttons = document.querySelectorAll('.button-task');
	buttons.forEach(button => {
		button.addEventListener('click', function (e) {
			const task = this.getAttribute('data-task') || this.getAttribute('onclick')?.match(/['"](.*?)['"]/)?.[1];
			if (task) {
				Joomla.submitbutton(task, e);
			}
		});
	});
});

Joomla.submitbutton = function (task, e) {
	if (task === 'listoftables.createFromSchema') {
		if (e) e.preventDefault();
		let defaultSchema = 'CREATE TABLE IF NOT EXISTS `planets` (\n `id` int unsigned NOT NULL AUTO_INCREMENT,\n  `name` varchar(50)  DEFAULT NULL COMMENT "Planet name",\n	PRIMARY KEY (`id`)\n) COMMENT="Planets"';

		createCustomPrompt('Please enter MySQL CREATE TABLE schema here:', defaultSchema)
			.then(result => {
				if (result) {
					// Create hidden input for schema
					const form = document.getElementById('adminForm');
					let schemaInput = form.querySelector('input[name="schema"]');

					if (!schemaInput) {
						schemaInput = document.createElement('input');
						schemaInput.type = 'hidden';
						schemaInput.name = 'schema';
						form.appendChild(schemaInput);
					}

					// Set the schema value
					schemaInput.value = result;

					// Submit the form
					Joomla.submitform(task, form);
				}
			});

		return false;
	}

	Joomla.submitform(task, document.getElementById('adminForm'));
	return true;
}

// Solution 2: Using Custom Modal
function createCustomPrompt(message, defaultValue = '') {
	// Create modal container
	const modal = document.createElement('div');
	modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    `;

	// Create modal content
	const content = document.createElement('div');
	content.style.cssText = `
        background: white;
        padding: 20px;
        border-radius: 8px;
        max-width: 700px;
        width: 90%;
    `;

	content.innerHTML = `
        <p>${message}</p>
        <textarea style="width: 100%; min-height: 200px; margin: 10px 0;" placeholder="${defaultValue}"></textarea>
        <div style="text-align: right;">
            <button id="submit" class="button-save btn btn-success">Create</button>
            <button id="cancel" class="button-cancel btn btn-danger">Cancel</button>
        </div>
    `;

	modal.appendChild(content);

	return new Promise((resolve) => {
		const textarea = content.querySelector('textarea');
		const submitBtn = content.querySelector('#submit');
		const cancelBtn = content.querySelector('#cancel');

		submitBtn.addEventListener('click', () => {
			document.body.removeChild(modal);
			resolve(textarea.value);
		});

		cancelBtn.addEventListener('click', () => {
			document.body.removeChild(modal);
			resolve(null);
		});

		document.body.appendChild(modal);
		textarea.focus();
	});
}
