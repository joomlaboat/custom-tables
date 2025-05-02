/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2025. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

if (typeof globalThis.CustomTablesEdit === 'undefined') {
	class CustomTablesEdit {

		//Always used as "CTEditHelper"
		constructor(cmsName = 'Joomla', cmsVersion = 5, itemId = null, websiteRoot = null) {
			this.GoogleDriveTokenClient = [];
			this.GoogleDriveAccessToken = null;
			this.cmsName = cmsName;
			this.cmsVersion = cmsVersion;
			this.itemId = itemId;

			this.ct_signaturePad_fields = [];
			this.ct_signaturePad = [];
			this.ct_signaturePad_formats = [];

			this.ctInputBoxRecords_dynamic_filter = [];

			this.ctLinkLoading = false;

			this.websiteRoot = websiteRoot;//With trailing front slash /
		}

		GoogleDriveInitClient(fieldName, GoogleDriveAPIKey, GoogleDriveClientId) {
			this.GoogleDriveTokenClient[fieldName] = google.accounts.oauth2.initTokenClient({
				client_id: GoogleDriveClientId,
				scope: "https://www.googleapis.com/auth/drive.readonly",
				callback: (tokenResponse) => {
					if (tokenResponse && tokenResponse.access_token) {
						this.GoogleDriveAccessToken = tokenResponse.access_token;
						CTEditHelper.GoogleDriveLoadPicker(fieldName, GoogleDriveAPIKey, tokenResponse.access_token);
					}
				},
			});
		}

		GoogleDriveLoadPicker(fieldName, GoogleDriveAPIKey, access_token) {
			gapi.load("picker", {
				callback: function () {
					CTEditHelper.GoogleDriveCreatePicker(fieldName, GoogleDriveAPIKey, access_token);
				}
			});
		}

		GoogleDriveCreatePicker(fieldName, GoogleDriveAPIKey, access_token) {
			if (access_token) {
				const pickerBuilder = new google.picker.PickerBuilder()
					.addView(google.picker.ViewId.DOCS)
					.addView(google.picker.ViewId.FOLDERS)
					.addView(new google.picker.DocsView(google.picker.ViewId.DOCS)
						.setIncludeFolders(true)
						.setOwnedByMe(false)
						.setLabel("Shared with me"))
					.setOAuthToken(access_token)
					.setDeveloperKey(GoogleDriveAPIKey)
					.setCallback(function (data) {
						CTEditHelper.GoogleDrivePickerCallback(fieldName, data, access_token)
					});

				if (google.picker.ViewId.SHARED_DRIVES) {
					pickerBuilder.addView(google.picker.ViewId.SHARED_DRIVES);
				}

				const picker = pickerBuilder.build();
				picker.setVisible(true);
			}
		}

		GoogleDrivePickerCallback(fieldName, data, access_token) {

			if (data[google.picker.Response.ACTION] === google.picker.Action.PICKED) {
				if (data[google.picker.Response.DOCUMENTS] && data[google.picker.Response.DOCUMENTS].length > 0) {
					const file = data[google.picker.Response.DOCUMENTS][0];
					CTEditHelper.GoogleDriveGetFileMetadata(fieldName, file.id, access_token);
				} else {
					console.log("No file was selected or the response format has changed.");
					document.getElementById("ct_eventsmessage_" + fieldName).innerHTML = "No file was selected.";
				}
			} else if (data[google.picker.Response.ACTION] === google.picker.Action.CANCEL) {
				console.log("User closed the Picker or canceled selection.");
				document.getElementById("ct_eventsmessage_" + fieldName).innerHTML = "File selection was canceled.";
			}
		}

		formatFileSize(bytes) {
			if (bytes === 0) return '0 Bytes';
			const k = 1024;
			const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			const i = Math.floor(Math.log(bytes) / Math.log(k));
			return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
		}

		emptyContainers(boxId, className) {
			const parentElement = document.getElementById(boxId);

			if (parentElement) {
				const containers = parentElement.getElementsByClassName(className);

				Array.from(containers).forEach(container => {
					container.innerHTML = '';
				});
			}
		}

		GoogleDriveGetFileMetadata(fieldName, fileId, access_token) {

			gapi.client.load("drive", "v3", () => {
				gapi.client.drive.files.get({
					fileId: fileId,
					fields: "id, name, mimeType, webContentLink, size"
				}).then(function (response) {

					CTEditHelper.emptyContainers("ct_uploadfile_box_" + fieldName, "ajax-file-upload-error");
					CTEditHelper.emptyContainers("ct_uploadfile_box_" + fieldName, "ajax-file-upload-container");

					let buttonId = "CustomTablesGoogleDrivePick_" + fieldName;
					const file = response.result;
					let prefix;
					const button = document.getElementById(buttonId);
					if (button) {
						const acceptValue = button.dataset.accept;
						if (acceptValue) {
							let parts = file.name.toLowerCase().split(".");
							let fileExtension = parts[parts.length - 1];
							let acceptTypes = acceptValue.split(' ');
							if (acceptTypes.indexOf(fileExtension) === -1) {
								let content = '<div class="ajax-file-upload-error"><b>' + file.name + '</b> is not allowed. Allowed extensions: ' + acceptValue + '</div>';
								document.getElementById("ct_eventsmessage_" + fieldName).innerHTML = content;
								return;
							}
						} else {
							console.error('Accept file extensions not found.', error);
							return;
						}

						prefix = button.dataset.prefix;
						if (!prefix) {
							console.error('Prefix not found.', error);
							return;
						}
					} else {
						console.error('Button "' + buttonId + '" not found.', error);
						return;
					}

					let fileSize = CTEditHelper.formatFileSize(file.size);
					let content = '<div class="ajax-file-upload-statusbar"><div class="ajax-file-upload-filename">1). ' + file.name + ' (' + fileSize + ')</div></div>';
					document.getElementById("ct_eventsmessage_" + fieldName).innerHTML = content;
					document.getElementById(prefix + fieldName + '_filename').value = file.name;

					let data = JSON.stringify({
						fileId: file.id,
						fileName: file.name,
						//mimeType: file.mimeType,
						//size: file.size,
						//downloadUrl: file.webContentLink,
						accessToken: access_token
					})
					document.getElementById(prefix + fieldName + '_data').value = data;
				}, function (error) {
					console.error("Error getting file metadata:", error);
					document.getElementById("ct_eventsmessage_" + fieldName).innerHTML = "Error getting file metadata.";
				});
			});
		}

		//A method to create or update table records using JavaScript. CustomTables handles data sanitization and validation.

		postRequest(url, postData, successCallback, errorCallback) {

			fetch(url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: postData,
			})
				.then(response => {
					if (response.redirected) {
						if (errorCallback && typeof errorCallback === 'function') {
							errorCallback('Login required or not authorized.');
						} else {
							console.error('Login required or not authorized. Error status code 200: Redirect.');
						}
						return null;
					}

					// Read the response as text for debugging
					return response.text().then(text => {
						let data;
						try {
							data = JSON.parse(text);
						} catch (e) {
							console.warn(text);
							if (errorCallback && typeof errorCallback === 'function')
								errorCallback({'success': false, 'message': 'Response is not valid JSON'});
							else
								console.error({'success': false, 'message': 'Response is not valid JSON'});

							return;
						}

						if (response.ok) {
							if (data.success) {
								if (successCallback && typeof successCallback === 'function')
									successCallback(data);
								else
									console.log(data);
							} else {
								if (errorCallback && typeof errorCallback === 'function')
									errorCallback(data);
								else
									console.error(data);
							}
						} else {
							if (errorCallback && typeof errorCallback === 'function')
								errorCallback(data);
							else
								console.error(data);
						}
					});


				})
				.then(data => {

					//console.log('Step 3');

				})
				.catch(error => {
					if (errorCallback && typeof errorCallback === 'function') {
						errorCallback({
							success: false,
							message: error,
							url: url
						});
					} else {
						console.log('Step 10');
						console.error('Error', error);
						console.log(url);
					}
				});
		}

		addRecord(fieldsAndValues, successCallback, errorCallback, url = null, fieldPrefix = null) {

			let deleteParams = ['view', 'task', 'tmpl', 'clean'];
			let addParams = ['view=edit', 'task=save'];
			url = esPrepareLink(deleteParams, addParams, url);

			let postData = new URLSearchParams();

			let fieldInputPrefix = ctFieldInputPrefix;

			if (fieldPrefix !== null)
				fieldInputPrefix = 'com' + fieldPrefix;

			// Iterate over keysObject and append each key-value pair
			for (const key in fieldsAndValues) {
				if (fieldsAndValues.hasOwnProperty(key)) {
					postData.append(fieldInputPrefix + key, fieldsAndValues[key]);
				}
			}

			console.log('postData:', postData);

			this.postRequest(url, postData, successCallback, errorCallback);
		}

		loadRecord(listing_id, successCallback, errorCallback, url = null) {

			let deleteParams = ['view', 'task', 'tmpl', 'clean'];
			let addParams = ['view=record'];
			if (Array.isArray(listing_id))
				addParams.push('ids=' + listing_id.join(","));
			else
				addParams.push('listing_id=' + listing_id);

			url = esPrepareLink(deleteParams, addParams, url);
			let postData = new URLSearchParams();

			this.postRequest(url, postData, successCallback, errorCallback);
		}

		loadRecordLayout(listing_id, layout, successCallback, errorCallback) {

			let deleteParams = ['view', 'task', 'tmpl', 'clean', 'layout'];
			let addParams = ['view=record', 'layout=' + layout];
			if (Array.isArray(listing_id))
				addParams.push('ids=' + listing_id.join(","));
			else
				addParams.push('listing_id=' + listing_id);

			let url = esPrepareLink(deleteParams, addParams);
			let postData = new URLSearchParams();

			this.postRequest(url, postData, successCallback, errorCallback);
		}

		//A method to create or update table records using JavaScript. CustomTables handles data sanitization and validation.
		saveRecord(fieldsAndValues, listing_id, successCallback, errorCallback, url = null) {

			let deleteParams = ['view', 'task', 'tmpl', 'clean'];
			let addParams = ['view=edit', 'task=save', 'listing_id=' + listing_id];
			url = esPrepareLink(deleteParams, addParams, url);

			let postData = new URLSearchParams();

			// Iterate over keysObject and append each key-value pair
			for (const key in fieldsAndValues) {
				if (fieldsAndValues.hasOwnProperty(key)) {
					postData.append(ctFieldInputPrefix + key, fieldsAndValues[key]);
				}
			}

			this.postRequest(url, postData, successCallback, errorCallback);
		}

		publishRecord(listing_id, successCallback, errorCallback, url = null) {
			this.setTaskRecord(listing_id, 'publish', successCallback, errorCallback, url);
		}

		unpublishRecord(listing_id, successCallback, errorCallback, url = null) {
			this.setTaskRecord(listing_id, 'unpublish', successCallback, errorCallback, url);
		}

		setTaskRecord(listing_id, task, successCallback, errorCallback, url = null) {

			let deleteParams = ['view', 'task', 'tmpl', 'clean'];
			let addParams = ['view=edit', 'task=' + task];
			if (Array.isArray(listing_id))
				addParams.push('ids=' + listing_id.join(","));
			else
				addParams.push('listing_id=' + listing_id);

			url = esPrepareLink(deleteParams, addParams, url);
			let postData = new URLSearchParams();

			this.postRequest(url, postData, successCallback, errorCallback);
		}

		refreshRecord(listing_id, status, successCallback, errorCallback, url = null) {
			this.setTaskRecord(listing_id, 'refresh', successCallback, errorCallback, url);
		}

		copyRecord(listing_id, status, successCallback, errorCallback, url = null) {
			this.setTaskRecord(listing_id, 'copy', successCallback, errorCallback, url);
		}

		deleteRecord(listing_id, successCallback, errorCallback, url = null) {
			this.setTaskRecord(listing_id, 'delete', successCallback, errorCallback, url);
		}

		//TODO: no usages found
		async refreshRecordOld(url, listing_id, successCallback, errorCallback, ModuleId) {
			let completeURL = url + '?tmpl=component&clean=1&task=refresh';
			if (listing_id !== undefined && listing_id !== null)
				completeURL += '&ids=' + listing_id;

			try {
				const response = await fetch(completeURL);
				if (!response.ok) {
					throw new Error('Network response was not ok');
				}
				const data = await response.json();
				console.log(data);
			} catch (error) {
				console.error('There was a problem with the fetch operation:', error);
			}

			//let postData = new URLSearchParams();
			//postData.append('task', 'refresh');

			fetch(completeURL, {
				method: 'GET'
			})
				.then(response => {

					if (response.redirected) {
						if (errorCallback && typeof errorCallback === 'function') {
							errorCallback('Login required or not authorized.');
						} else {
							console.error('Login required or not authorized. Error status code 200: Redirect.');
						}
						return null;
					}

					if (!response.ok) {
						// If the HTTP status code is not successful, throw an error object that includes the response
						throw {status: 'error', message: 'HTTP status code: ' + response.status, response: response};
					}
					return response.json();
				})
				.then(data => {
					if (data === null)
						return;

					if (data.status === 'saved') {
						if (successCallback && typeof successCallback === 'function') {
							successCallback(data);
						} else {

						}
					} else if (data.status === 'error') {
						if (errorCallback && typeof errorCallback === 'function') {
							errorCallback(data);
						} else {
							console.error(data.message);
						}
					}
				})
				.catch(error => {
					if (errorCallback && typeof errorCallback === 'function') {
						errorCallback({
							status: 'error',
							message: 'An error occurred during the request.',
							error: error,
							url: completeURL
						});
					} else {
						console.error('Error 145:', error);
						console.log(completeURL);
					}
				});
		}

		//Reloads a particular table row (record) after changes have been made. It identifies the table and the specific row based on the provided listing_id and then triggers a refresh to update the displayed data.
		reloadRecord(listing_id) {

			// Select all table elements whose id attribute starts with 'ctTable_'
			const tables = document.querySelectorAll('table[id^="ctTable_"]');
			tables.forEach(table => {
				let parts = table.id.split("_");
				if (parts.length === 2) {
					let tableId = parts[1];
					let trId = 'ctTable_' + tableId + '_' + listing_id;
					const records = table.querySelectorAll('tr[id^="' + trId + '"]');
					if (records.length == 1) {
						let table_object = findTableByRowId(tableid + '_' + listing_id);
						let index = findRowIndexById(table_object, tableId, listing_id, 'ctEditIcon');
						ctCatalogUpdate(tableId, listing_id, index, ModuleId);
					}
				}
			});
		}

		ImageGalleryInitImagePreviews(inputId) {
			const input = document.getElementById(inputId);

			input.onchange = function (event) {
				const previewContainer = document.getElementById(inputId + '_previewNew');
				previewContainer.innerHTML = '';

				Array.from(event.target.files).forEach((file, index) => {
					if (file.type.startsWith('image/')) {
						const reader = new FileReader();
						const div = document.createElement('div');
						div.className = 'preview-item';
						div.dataset.fileIndex = index;

						reader.onload = function (e) {
							div.innerHTML = `
                        <img src="${e.target.result}" class="preview-image" />
                        <button type="button" class="remove-btn" 
                                onclick="CTEditHelper.ImageGalleryRemoveFile(this, '${inputId}', ${index})">×</button>
                    `;
						};

						previewContainer.appendChild(div);
						reader.readAsDataURL(file);
					}
				});
			};
		}

		ImageGalleryRemoveFile(button, inputId, fileIndex) {

			if (fileIndex < 0) {
				//mark to delete existing file
				const input = document.getElementById(inputId + '_uploaded');
				if (input) {
					let files = input.value.split(',');
					let newFiles = [];

					for (let i = 0; i < files.length; i++) {
						if (parseInt(files[i]) != -fileIndex)
							newFiles.push(files[i]);
						else
							newFiles.push(fileIndex);
					}

					input.value = newFiles.join(',');
					const container = button.closest('.preview-item');
					container.remove();
				}
			} else {
				const input = document.getElementById(inputId);
				const container = button.closest('.preview-item');

				const dt = new DataTransfer();
				const files = input.files;

				for (let i = 0; i < files.length; i++) {
					if (i !== fileIndex) {
						dt.items.add(files[i]);
					}
				}

				input.files = dt.files;
				container.remove();

				// Reindex remaining previews
				const previews = document.querySelectorAll('.preview-item');
				previews.forEach((preview, index) => {
					preview.dataset.fileIndex = index;
					const removeBtn = preview.querySelector('.remove-btn');
					removeBtn.setAttribute('onclick', `CTEditHelper.ImageGalleryRemoveFile(this, '${inputId}', ${index})`);
				});
			}
		}

		checkRequiredFields(formObject, fieldInputPrefix) {

			if (!checkFilters(fieldInputPrefix))
				return false;

			if (this.ct_signaturePad_fields.length > 0) {
				if (!CTEditHelper.ctInputbox_signature_apply()) {
					event.preventDefault();
					return false;
				}
			}

			let requiredFields = formObject.getElementsByClassName("required");
			let label = "One field";

			for (let i = 0; i < requiredFields.length; i++) {
				if (typeof requiredFields[i].id != "undefined") {
					if (requiredFields[i].id.indexOf("sqljoin_table_" + fieldInputPrefix) !== -1) {
						if (!CheckSQLJoinRadioSelections(requiredFields[i].id, fieldInputPrefix))
							return false;
					}
					if (requiredFields[i].id.indexOf("ct_uploadfile_box_") !== -1) {
						if (!CheckImageUploader(requiredFields[i].id, fieldInputPrefix)) {
							let d = requiredFields[i].dataset;
							if (d.label)
								label = d.label;
							else
								label = "Unlabeled field";

							let imageObjectName = requiredFields[i].id + '_image';
							let imageObject = document.getElementById(imageObjectName);

							if (imageObject)
								return true;

							alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
							return false;
						}
					}
				}

				if (typeof requiredFields[i].name != "undefined") {
					let n = requiredFields[i].name.toString();

					if (n.indexOf(fieldInputPrefix) !== -1) {

						let objName = n.replace('_selector', '');

						let d = requiredFields[i].dataset;
						if (d.label)
							label = d.label
						else
							label = "Unlabeled field";

						if (d.type === 'sqljoin') {
							if (requiredFields[i].type === "hidden") {
								let obj = document.getElementById(objName);

								if (obj.value === '') {
									alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
									return false;
								}
							}

						} else if (requiredFields[i].type === "text") {
							let obj = document.getElementById(objName);
							if (obj.value === '') {
								alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
								return false;
							}
						} else if (requiredFields[i].type === "select-one") {
							let obj = document.getElementById(objName);

							if (obj.value === null || obj.value === '') {
								alert(TranslateText('COM_CUSTOMTABLES_NOT_SELECTED', label));
								return false;
							}
						} else if (requiredFields[i].type === "select-multiple") {
							let count_multiple_obj = document.getElementById(lbln);
							let options = count_multiple_obj.options;
							let count_multiple = 0;

							for (let i2 = 0; i2 < options.length; i2++) {
								if (options[i2].selected)
									count_multiple++;
							}

							if (count_multiple === 0) {
								alert(TranslateText('COM_CUSTOMTABLES_NOT_SELECTED', label));
								return false;
							}
						} else if (d.selector == 'switcher') {
							//Checkbox element with Yes/No visual effect
							if (d.label)
								label = d.label;
							else
								label = "Unlabeled field";

							if (requiredFields[i].value === "1") {

								if (d.valuerulecaption && d.valuerulecaption !== "")
									alert(d.valuerulecaption);
								else
									alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
								return false;
							}
						} else if (d.type == 'checkbox') {
							//Simple HTML Checkbox element
							if (d.label)
								label = d.label;
							else
								label = "Unlabeled field";

							if (!requiredFields[i].checked) {
								if (d.valuerulecaption && d.valuerulecaption !== "")
									alert(d.valuerulecaption);
								else
									alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
								return false;
							}
						}
					}
				}
			}
			return true;
		}

		convertDateTypeValues(elements) {
			for (let i = 0; i < elements.length; i++) {
				if (elements[i].name && elements[i].name !== '' && elements[i].name !== 'returnto') {
					if (elements[i].dataset.type === "date") {
						if (elements[i].dataset.format !== "%Y-%m-%d" && elements[i].dataset.format !== "%Y-%m-%d %H:%M:%S") {
							//convert date to %Y-%m-%d
							let dateValue = elements[i].value;
							// Remove time if present (keep only date part)
							dateValue = dateValue.split(" ")[0];

							if (dateValue) {
								// Parse the format string
								let format = elements[i].dataset.format;

								if (typeof format === "undefined")
									format = "%Y-%m-%d";

								let day, month, year;

								// Convert Joomla's format to parts
								let parts = dateValue.split(/[-/.]/);
								// Remove time if present (keep only date part)
								let formatWithoutTime = format.split(" ")[0];

								let formatParts = formatWithoutTime.split(/[-/.]/);

								// Map the parts to corresponding values
								formatParts.forEach((part, index) => {
									if (part === '%d') day = parts[index];
									else if (part === '%m') month = parts[index];
									else if (part === '%Y') year = parts[index];
								});

								// Create standardized date string
								elements[i].value = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
							}
						}
					}
				}
			}
		}

		ctInputbox_signature(inputbox_id, width, height, format) {

			let canvas = document.getElementById(inputbox_id + '_canvas');

			this.ct_signaturePad_fields.push(inputbox_id);
			this.ct_signaturePad[inputbox_id] = new SignaturePad(canvas, {
				backgroundColor: "rgb(255, 255, 255)"
			});

			this.ct_signaturePad_formats[inputbox_id] = format;

			canvas.width = width;
			canvas.height = height;
			canvas.getContext("2d").scale(1, 1);

			document.getElementById(inputbox_id + '_clear').addEventListener('click', function () {
				this.ct_signaturePad[inputbox_id].clear();
			});
		}

		ctInputbox_signature_apply() {

			if (this.ct_signaturePad_fields.length === 0)
				return true;

			let inputbox_id = this.ct_signaturePad_fields[0];

			if (this.ct_signaturePad[inputbox_id].isEmpty()) {
				alert(TranslateText('COM_CUSTOMTABLES_JS_SIGNATURE_REQUIRED'));
				return false;
			} else {

				let format = this.ct_signaturePad_formats[inputbox_id];

				let dataURL = this.ct_signaturePad[inputbox_id].toDataURL('image/' + format);
				document.getElementById(inputbox_id).setAttribute("value", dataURL);
				return true;
			}
		}

		ctInputbox_UpdateSQLJoinLink(control_name, control_name_postfix) {
			//Old calls replaced
			setTimeout(this.ctInputbox_UpdateSQLJoinLink_do(control_name, control_name_postfix), 100);
		}

		ctInputbox_UpdateSQLJoinLink_do(control_name, control_name_postfix) {
			//Old calls replaced
			let controlElement = document.getElementById(control_name);
			let selectedControlElements = Array.from(controlElement.options)
				.filter(option => option.selected)
				.map(option => option.value);

			let l = document.getElementById(control_name + control_name_postfix);
			let o = document.getElementById(control_name + 'SQLJoinLink');
			let v = '';

			if (o) {
				if (o.selectedIndex === -1)
					return false;

				v = o.options[o.selectedIndex].value;
			}

			let selectedValue = null
			let ctInputBoxRecords_current_value = document.getElementById(control_name + '_ctInputBoxRecords_current_value');

			if (ctInputBoxRecords_current_value)
				selectedValue = String(ctInputBoxRecords_current_value.innerHTML);

			ctInputBoxRecords_removeOptions(l);

			if (control_name_postfix !== '_selector') {
				let opt = document.createElement("option");
				opt.value = '0';
				opt.innerHTML = TranslateText('COM_CUSTOMTABLES_SELECT');
				l.appendChild(opt);
			}

			let elements = JSON.parse(document.getElementById(control_name + control_name_postfix + '_elements').textContent);
			let elementsID = document.getElementById(control_name + control_name_postfix + '_elementsID').innerHTML.split(",");
			let elementsPublished = document.getElementById(control_name + control_name_postfix + '_elementsPublished').innerHTML.split(",");

			let filterElement = document.getElementById(control_name + control_name_postfix + '_elementsFilter');
			let elementsFilter = []
			if (filterElement)
				elementsFilter = filterElement.innerHTML.split(";");

			for (let i = 0; i < elements.length; i++) {
				let f = elementsFilter[i];

				if (elements[i] !== "") {

					let eid = String(elementsID[i]);
					if (selectedControlElements.indexOf(eid) === -1) {

						let published = parseInt(elementsPublished[i]);

						if (typeof f != "undefined") {
							let f_list = f.split(",");

							if (f_list.indexOf(v) !== -1) {
								let opt = document.createElement("option");
								opt.value = eid;
								if (eid === selectedValue)
									opt.selected = true;

								if (published === 0)
									opt.style.cssText = "color:red;";

								opt.innerHTML = elements[i];
								l.appendChild(opt);
							}
						} else {

							let opt = document.createElement("option");
							opt.value = eid;
							if (eid === selectedValue)
								opt.selected = true;

							if (published === 0)
								opt.style.cssText = "color:red;";

							opt.innerHTML = elements[i];
							l.appendChild(opt);
						}
					}
				}
			}

			return true;
		}

		ctInputBoxRecords_addItem(control_name, control_name_postfix) {

			let o = document.getElementById(control_name + control_name_postfix);
			o.selectedIndex = 0;

			if (this.ctInputBoxRecords_dynamic_filter[control_name] !== '') {

				let ctInputBoxRecords_current_value = document.getElementById(control_name + '_ctInputBoxRecords_current_value');
				if (ctInputBoxRecords_current_value)
					ctInputBoxRecords_current_value.innerHTML = '';

				let SQLJoinLink = document.getElementById(control_name + control_name_postfix + 'SQLJoinLink');
				if (SQLJoinLink)// {
					SQLJoinLink.selectedIndex = 0;

				this.ctInputbox_UpdateSQLJoinLink(control_name, control_name_postfix);
			}

			document.getElementById(control_name + '_addButton').style.visibility = "hidden";
			document.getElementById(control_name + '_addBox').style.visibility = "visible";
		}
	}

	globalThis.CustomTablesEdit = CustomTablesEdit; // Store globally
}

function setTask(event, task, returnLink, submitForm, formName, isModal, modalFormParentField, ModuleId, fieldInputPrefix) {

	event.preventDefault();

	let objForm = document.getElementById(formName);

	if (objForm) {
		if (returnLink !== "") {
			let returnToObject = document.getElementById('returnto' + (ModuleId !== null ? ModuleId : ''));
			if (returnToObject)
				returnToObject.value = returnLink;
		}

		let TaskObject = document.getElementById('task' + (ModuleId !== null ? ModuleId : ''));
		if (TaskObject)
			TaskObject.value = task;
		else {
			alert('Task Element "' + 'task' + ModuleId + '"not found.');
			return;
		}

		const tasks_with_validation = ['saveandcontinue', 'save', 'saveandprint', 'saveascopy'];
		if (isModal && task !== 'saveascopy') {
			let hideModelOnSave = true;
			if (task === 'saveandcontinue')
				hideModelOnSave = false;

			if (tasks_with_validation.includes(task)) {
				if (CTEditHelper.checkRequiredFields(objForm, fieldInputPrefix)) {
					CTEditHelper.convertDateTypeValues(objForm.elements);
					submitModalForm(objForm.action, objForm.elements, objForm.dataset.tableid, objForm.dataset.recordid, hideModelOnSave, modalFormParentField, returnLink, ModuleId, fieldInputPrefix)
				}
			} else {
				CTEditHelper.convertDateTypeValues(objForm.elements);
				submitModalForm(objForm.action, objForm.elements, objForm.dataset.tableid, objForm.dataset.recordid, hideModelOnSave, modalFormParentField, returnLink, ModuleId, fieldInputPrefix)
			}

			return false;
		} else {
			if (tasks_with_validation.includes(task)) {
				if (CTEditHelper.checkRequiredFields(objForm, fieldInputPrefix)) {
					CTEditHelper.convertDateTypeValues(objForm.elements);
					objForm.submit();
				}
			} else {
				CTEditHelper.convertDateTypeValues(objForm.elements);
				objForm.submit();
			}
		}
	} else
		alert("Form not found.");
}

function stripInvalidCharacters(str) {
	// This regular expression matches all non-printable ASCII characters
	return str.replace(/[^\x20-\x7E]/g, '');
}

function submitModalForm(url, elements, tableid, recordId, hideModelOnSave, modalFormParentField, returnLinkEncoded, ModuleId, fieldInputPrefix) {

	let fieldsProcessed = [];
	let params = new URLSearchParams();

	let opt;
	for (let i = 0; i < elements.length; i++) {
		if (elements[i].name && elements[i].name !== '' && elements[i].name !== 'returnto' && fieldsProcessed.indexOf(elements[i].name) === -1) {

			if (elements[i].type === "select-multiple") {

				const options = elements[i] && elements[i].options;

				for (let x = 0; x < options.length; x++) {
					opt = options[x];
					if (opt.selected)
						params.append(elements[i].name, opt.value);
				}

			} else if (elements[i].type === "checkbox") {
				// Handle checkboxes: add "true" if checked, "false" if unchecked
				params.append(elements[i].name, (elements[i].checked ? "true" : "false"));
			} else if (elements[i].type === "radio") {
				// Handle radio buttons: Check if any radio button with the same name is selected
				const radios = document.getElementsByName(elements[i].name);
				let radioChecked = false;

				for (let r = 0; r < radios.length; r++) {
					if (radios[r].checked) {
						params.append(radios[r].name, radios[r].value);
						radioChecked = true;
						break;  // No need to check further once one is selected
					}
				}

				// If no radio button is selected, set a default value (if desired)
				if (!radioChecked) {
					params.append(elements[i].name, "none");
				}
			} else {
				params.append(elements[i].name, elements[i].value);
			}
			fieldsProcessed.push(elements[i].name);
		}
	}

	let http = CreateHTTPRequestObject();   // defined in ajax.js

	if (http) {
		params.append('task', "save");
		params.append('clean', "1");
		params.append('frmt', "json");
		params.append('ctmodalform', "1");
		params.append('load', "1");

		let clean_url = url.replace('%addRecord%', '');
		console.log("clean_url:", clean_url)
		console.log("params:", params.toString())

		http.open("POST", clean_url, true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.setRequestHeader("X-Requested-With", "XMLHttpRequest"); // Prevent full-page redirects

		http.onreadystatechange = function () {
			if (http.readyState === 4) {
				let response;

				try {
					let responseString = http.response.toString();
					console.log('responseString:', responseString);
					response = JSON.parse(http.response.toString());
				} catch (e) {

					let r = http.response.toString();
					if (r.indexOf('view-login') !== -1) {
						alert('Session expired. Please login again.');
						location.reload();
						return;
					} else {
						console.log(clean_url);
						console.log(http.response.toString());
						return console.error(e);
					}
				}

				if (response.success) {
					//let element_tableid_tr = "ctTable_" + tableid + '_' + recordId;
					let table_object = document.getElementById("ctTable_" + tableid);

					if (table_object) {
						let index = findRowIndexById(table_object, tableid, recordId, 'ctEditIcon');
						ctCatalogUpdate(tableid, recordId, index, ModuleId);
					}

					if (modalFormParentField !== null) {
						console.warn("modalFormParentField:", modalFormParentField)
						let parts = modalFormParentField.split('.');
						let parentField = parts[1];
						let parentFieldInputPrefix = parts[2];
						location.reload();
						//refreshTableJoinField(parentField, response, parentFieldInputPrefix);
					}

					if (hideModelOnSave) {
						ctHidePopUp();
						return;
					}

					if (returnLinkEncoded !== "")
						location.href = stripInvalidCharacters(Base64.decode(returnLinkEncoded));

				} else {
					/*
					if (http.response.indexOf('<div class="alert-message">Nothing to save</div>') !== -1)
						alert('Nothing to save. Check Edit From layout.');
					else if (http.response.indexOf('view-login') !== -1)
						alert(TranslateText('COM_CUSTOMTABLES_JS_SESSION_EXPIRED'));
					else
						*/
					alert(response.message);
				}
			}
		};
		http.send(params.toString());
	}
}

function checkFilters(fieldInputPrefix) {

	let passed = true;
	let inputs = document.getElementsByTagName('input');

	for (let i = 0; i < inputs.length; i++) {
		let t = inputs[i].type.toLowerCase();

		if (t === 'text' && inputs[i].value !== "") {
			//let n = inputs[i].name.toString();
			let d = inputs[i].dataset;
			let label = "";

			if (d.label)
				label = d.label;

			if (d.sanitizers)
				doSanitanization(inputs[i], d.sanitizers);

			if (d.filters) {
				passed = doFilters(inputs[i], label, d.filters);
				if (!passed)
					return false;
			}

			if (d.valuerule) {
				let caption = "";
				if (d.valuerulecaption)
					caption = d.valuerulecaption;

				passed = doValueRules(inputs[i], label, d.valuerule, caption, fieldInputPrefix);
				if (!passed)
					return false;
			}
		}
	}
	return passed;
}

//https://stackoverflow.com/questions/5717093/check-if-a-javascript-string-is-a-url
function isValidURL(str) {
	let regex = /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/;
	return regex.test(str);
}

function doValueRules(obj, label, valueRules, caption, fieldInputPrefix) {
	let ct_fieldName = obj.name.replaceAll(fieldInputPrefix, '');
	let value_rules_and_arguments = doValuerules_ParseValues(valueRules, ct_fieldName, fieldInputPrefix);

	if (value_rules_and_arguments === null)
		return true;

	let result = false;

	let rules_str = "return " + value_rules_and_arguments.new_valuerules;

	try {
		let rules = new Function(rules_str); // this |x| refers global |x|
		result = rules(value_rules_and_arguments.new_args);
	} catch (error) {
		return true;//TODO replace it with JS Twig
	}

	if (result)
		return true;

	if (caption === '')
		caption = 'Invalid value for "' + label + '"';

	alert(caption);
	return false;
}

function doValuerules_ParseValues(valuerules, ct_fieldName, fieldInputPrefix) {
	//let matches=valuerules.match(/(?<=\[)[^\][]*(?=])/g);  Doesn't work on Safari
	let matches = valuerules.match(/\[(.*?)\]/g); // return example: ["[subject]","[date]"]

	if (matches == null) {
		valuerules = '[' + ct_fieldName + ']' + valuerules;
		matches = valuerules.match(/\[(.*?)\]/g); // return example: ["[subject]","[date]"]

		if (matches == null)
			return null;
	}

	let args = [];

	for (let i = 0; i < matches.length; i++) {
		let fieldname = matches[i].replace("[", "").replace("]", "");
		let objID = fieldInputPrefix + fieldname;

		let obj = document.getElementById(objID);

		if (obj) {
			valuerules = valuerules.replaceAll("[" + fieldname + "]", 'arguments[0][' + i + ']');
			args[i] = obj.value;
		}
	}
	return {new_valuerules: valuerules, new_args: args};
}

function doFilters(obj, label, filters_string) {
	let filters = filters_string.split(",");
	let value = obj.value;

	for (let i = 0; i < filters.length; i++) {
		let filter_parts = filters[i].split(':');
		let filter = filter_parts[0];

		if (filter === 'email') {
			// /^[^\s@]+@[^\s@]+\.[^\s@]+$/

			let lastAtPos = value.lastIndexOf('@');
			let lastDotPos = value.lastIndexOf('.');
			let isEmailValid = (lastAtPos < lastDotPos && lastAtPos > 0 && value.indexOf('@@') === -1 && lastDotPos > 2 && (value.length - lastDotPos) > 2);
			if (!isEmailValid) {
				alert(TranslateText('COM_CUSTOMTABLES_JS_EMAIL_INVALID', label, value));
				return false;
			}
		} else if (filter === 'url') {
			if (!isValidURL(value)) {
				alert(TranslateText('COM_CUSTOMTABLES_JS_URL_INVALID', label, value));
				return false;
			}
		} else if (filter === 'https') {
			if (value.indexOf("https") !== 0) {
				alert(TranslateText('COM_CUSTOMTABLES_JS_SECURE_URL_INVALID', label, value));
				return false;
			}
		} else if (filter === 'domain' && filter_parts.length > 1) {
			let domain = filter_parts[1].split(",");
			let hostname = "";

			try {
				hostname = (new URL(value)).hostname;

			} catch (err) {
				alert(TranslateText('COM_CUSTOMTABLES_JS_URL_INVALID', label, value));
				return false;
			}

			hostname = hostname.trim().replaceAll('www.', '').toLowerCase();
			domain = domain.toString().trim().replaceAll('www.', '').toLowerCase();

			if (domain !== hostname) {
				alert(TranslateText('COM_CUSTOMTABLES_JS_HOSTNAME_INVALID', value, label, filter_parts[1]));
				return false;
			}
		}
	}
	return true;
}

function doSanitanization(obj, sanitizers_string) {
	let sanitizers = sanitizers_string.split(",");
	let value = obj.value;

	for (let i = 0; i < sanitizers.length; i++) {
		if (sanitizers[i] === 'trim')
			value = value.trim();
	}

	obj.value = value;
}


function TranslateText() {
	if (arguments.length === 0)
		return 'Nothing to translate';

	let str;
	const key = arguments[0];

	str = ctTranslationScriptObject[key];

	// Handle placeholders
	if (arguments.length === 1)
		return str;

	for (let i = 1; i < arguments.length; i++)
		str = str.replace('%s', arguments[i]);

	return str;
}

function SetUnsetInvalidClass(id, isValid) {
	let obj = document.getElementById(id);
	if (isValid) {
		obj.classList.remove("invalid");
	} else {
		obj.classList.add("invalid");
	}
}

function CheckImageUploader(id, fieldInputPrefix) {

	let obj1 = document.getElementById(id);

	if (obj1) {

		if (obj1.value === "") {
			SetUnsetInvalidClass(id, false);
			return false;
		}
		SetUnsetInvalidClass(id, true);
		return true;
	}

	let objId = id.replace("ct_uploadfile_box_", fieldInputPrefix);

	let obj2 = document.getElementById(objId);

	if (obj2) {

		if (obj2.value === "") {
			SetUnsetInvalidClass(id, false);
			return false;
		}
		SetUnsetInvalidClass(id, true);
		return true;
	}
}

function CheckSQLJoinRadioSelections(id, fieldInputPrefix) {
	let field_name = id.replace('sqljoin_table_' + fieldInputPrefix, '');
	let obj_name = fieldInputPrefix + field_name;
	let radios = document.getElementsByName(obj_name);

	let selected = false;
	for (let i = 0; i < radios.length; i++) {
		if (radios[i].type === 'radio' && radios[i].checked) {
			selected = true;
			break;
		}
	}

	if (!selected) {
		let labelObject = document.getElementById(obj_name + "-lbl");
		let label = labelObject.innerHTML;
		alert(Joomla.JText._('COM_CUSTOMTABLES_NOT_SELECTED', label));
		return false;
	}

	return true;
}

function recaptchaCallback() {
	let buttons = ['save', 'saveandclose', 'saveandprint', 'saveandcopy', 'delete'];
	for (let i = 0; i < buttons.length; i++) {
		let button = 'customtables_button_' + buttons[i];
		let obj = document.getElementById(button);

		if (obj)
			obj.disabled = false;
	}
}

function decodeHtml(html) {
	let txt = document.createElement("textarea");
	txt.innerHTML = html;
	return txt.value;
}

function ctRenderTableJoinSelectBox(control_name, r, index, execute_all, sub_index, parent_object_id, formId, forceValue) {

	let wrapper = document.getElementById(control_name + "Wrapper");
	let filters = [];
	if (wrapper.dataset.valuefilters !== '') {
		let decodedFilterString = Base64.decode(wrapper.dataset.valuefilters).replace(/[^ -~]+/g, "");
		filters = JSON.parse(decodedFilterString);
	}

	let attributesStringDataSet = Base64.decode(wrapper.dataset.attributes);

	//The code searches the string for any characters that are not in the printable ASCII range (from space to tilde).
	//It replaces any such characters with an empty string, effectively removing them from the string.
	let attributesStringClean = attributesStringDataSet.replace(/[^ -~]+/g, "");
	//let attributes = JSON.parse(attributesStringClean);
	//let onchange = Base64.decode(wrapper.dataset.onchange).replace(/[^ -~]+/g, "");

	let next_index = index;
	let next_sub_index = sub_index;
	let val;

	if (Array.isArray(filters[index])) {
		//Self Parent field
		next_sub_index += 1;
		if (next_sub_index === filters[index].length) {
			// Max sub index reached
			/*
			next_sub_index = 0;
			next_index += 1;

			if(Array.isArray(filters[next_index]))
				val = filters[next_index][next_sub_index];
			else
				val = filters[next_index];
			*/
			val = null;
		} else
			val = filters[next_index][next_sub_index];

	} else {
		next_index += 1;
		val = filters[next_index];
	}

	if (!execute_all)
		val = null;

	if (r.error) {
		alert(r.error);
		return false;
	}

	if (r.length === 0) {
		if (Array.isArray(filters[next_index])) {

			next_sub_index = 0;
			next_index += 1;

			if (next_index < filters.length) {
				document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = '<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';
				ctUpdateTableJoinLink(control_name, next_index, false, next_sub_index, parent_object_id, formId, false, null);
				return false;
			} else {
				let selectorObject = document.getElementById(control_name + "Selector" + index + '_' + sub_index);

				if (selectorObject) {
					selectorObject.innerHTML = '';//No items to select';//..<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';
				} else {
					return false;
				}
			}
		} else {

			/*
			let NoItemsText;

			if (typeof wrapper.dataset.addrecordmenualias !== 'undefined' && wrapper.dataset.addrecordmenualias !== '') {
				let js = 'ctTableJoinAddRecordModalForm(\'' + control_name + '\',' + sub_index + ');';
				let addText = TranslateText('COM_CUSTOMTABLES_ADD');
				NoItemsText = addText + '<a href="javascript:' + js + '" className="toolbarIcons"><img src="' + CTEditHelper.websiteRoot + 'components/com_customtables/libraries/customtables/media/images/icons/new.png" alt="' + addText + '" title="' + addText + '"></a>';
			} else
				NoItemsText = TranslateText('COM_CUSTOMTABLES_SELECT_NOTHING')

			document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = NoItemsText;

			*/
			return false;
		}
	}

	let result = '';
	let cssClass = 'form-control form-select valid form-control-success';
	if (CTEditHelper.cmsName === 'Joomla' && CTEditHelper.cmsVersion < 4)
		cssClass = 'inputbox';

	//Add select box
	let current_object_id = control_name + index + (Array.isArray(filters[index]) ? '_' + sub_index : '');

	if (r.length > 0) {

		let updateValueString = (index + 1 === filters.length ? 'true' : 'false');
		let onChangeFunction = 'ctUpdateTableJoinLink(\'' + control_name + '\', ' + next_index + ', false, ' + next_sub_index + ',\'' + current_object_id + '\', \'' + formId + '\', ' + updateValueString + ',null);'
		let onChangeAttribute = ' onChange="' + onChangeFunction + onchange + '"';
		//[' + index + ',' + filters.length + ']
		result += '<select id="' + current_object_id + '"' + onChangeAttribute + ' class="' + cssClass + '">';
		result += '<option value="">- ' + TranslateText('COM_CUSTOMTABLES_SELECT') + '</option>';

		if (typeof wrapper.dataset.addrecordmenualias !== 'undefined' && wrapper.dataset.addrecordmenualias !== '')
			result += '<option value="%addRecord%">- ' + TranslateText('COM_CUSTOMTABLES_ADD') + '</option>';

		for (let i = 0; i < r.length; i++) {
			let optionLabel = decodeHtml(r[i].label);
			result += '<option value="' + r[i].value + '">' + optionLabel + '</option>';
		}

		result += '</select>';

		//Prepare the space for next elements
		result += '<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';
	}

	//Add content to the element
	//if (document.getElementById(control_name + "Selector" + index + '_' + (sub_index + 1)))
	//    document.getElementById(control_name + "Selector" + index + '_' + (sub_index + 1)).innerHTML = result;
	if (document.getElementById(control_name + "Selector" + index + '_' + (sub_index)))
		document.getElementById(control_name + "Selector" + index + '_' + (sub_index)).innerHTML = result;

	if (forceValue !== null) {
		let obj = document.getElementById(current_object_id);
		obj.value = forceValue;
	}

	if (r.length > 0) {
		if (execute_all && next_index + 1 < filters.length && val != null) {
			ctUpdateTableJoinLink(control_name, next_index, true, next_sub_index, null, formId, false, null);
		}
	}
}

function ctTableJoinAddRecordModalForm(control_name, sub_index) {

	let wrapper = document.getElementById(control_name + "Wrapper");

	let query = CTEditHelper.websiteRoot + 'index.php' + (wrapper.dataset.addrecordmenualias.indexOf('/') === -1 ? '/' : '') + wrapper.dataset.addrecordmenualias;
	if (wrapper.dataset.addrecordmenualias.indexOf('?') === -1)
		query += '?';
	else
		query += '&';

	query += 'view=edititem';

	let parentObjectValue = null;
	let sub_indexObject = document.getElementById(control_name + sub_index);
	if (sub_indexObject) {
		parentObjectValue = sub_indexObject.value;
		query += '&es_' + sub_indexObject.dataset.childtablefield + '=' + parentObjectValue;
	}
	ctEditModal(query, wrapper.dataset.formname + '.' + wrapper.dataset.fieldname + '.' + ctFieldInputPrefix)
}

function ctUpdateTableJoinLink(control_name, index, execute_all, sub_index, object_id, formId, updateValue, forceValue) {

	let wrapper = document.getElementById(control_name + "Wrapper");
	let url;

	if (CTEditHelper.cmsName === "Joomla") {
		let link = location.href.split('administrator/index.php?option=com_customtables');

		if (link.length === 2)//to make sure that it will work in the back-end
			url = CTEditHelper.websiteRoot + 'administrator/index.php?option=com_customtables&view=records&frmt=json&key=' + wrapper.dataset.key + '&index=' + index;
		else
			url = CTEditHelper.websiteRoot + 'index.php?option=com_customtables&view=catalog&tmpl=component&frmt=json&key=' + wrapper.dataset.key + '&index=' + index;

	} else if (CTEditHelper.cmsName === "WordPress") {
		url = CTEditHelper.websiteRoot + 'index.php?page=customtables-api-tablejoin&key=' + wrapper.dataset.key + '&index=' + index;
	}

	let filters = [];
	if (wrapper.dataset.valuefilters !== '') {
		let decodedFilterString = Base64.decode(wrapper.dataset.valuefilters).replace(/[^ -~]+/g, "");
		filters = JSON.parse(decodedFilterString);
	}

	if (execute_all) {
		if (Array.isArray(filters[index])) {
			//Self Parent field
			if (filters[index][sub_index] !== '')
				url += '&subfilter=' + filters[index][sub_index];
		} else if (filters[index] !== '')
			url += '&filter=' + filters[index];
	} else {

		let valueObj = document.getElementById(control_name);
		let obj = document.getElementById(object_id);

		if (forceValue !== null) {
			valueObj.value = forceValue;
		} else {
			if (updateValue) {
				if (obj.value === "") {

					let indexTemp = index;
					let sub_indexTemp = sub_index;

					if (sub_indexTemp > 0)
						sub_indexTemp -= 2;
					else {
						//TODO: descend IndexTemp
					}

					if (sub_indexTemp >= 0) {
						let tempCurrent_object_id = control_name + indexTemp + (Array.isArray(filters[indexTemp]) ? '_' + sub_indexTemp : '');
						let objTemp = document.getElementById(tempCurrent_object_id);

						if (objTemp === null)
							valueObj.value = obj.value;
						else
							valueObj.value = objTemp.value;
					} else
						valueObj.value = obj.value;
				} else
					valueObj.value = obj.value;
			}

			if (obj.value === "") {
				//Empty everything after
				document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = '';//"Not selected";
				return false;
			} else if (obj.value === "%addRecord%") {
				obj.value = '';
				ctTableJoinAddRecordModalForm(control_name, sub_index);
				return;
			}
		}

		if (Array.isArray(filters[index]))
			url += '&subfilter=' + obj.value;
		else
			url += '&filter=' + obj.value;
	}

	if (index >= filters.length)
		return false;

	ctRenderTableJoinSelectBoxLoadRecords(url, control_name, index, execute_all, sub_index, object_id, formId, forceValue)
}

function ctRenderTableJoinSelectBoxLoadRecords(url, control_name, index, execute_all, sub_index, object_id, formId, forceValue) {

	let http = CreateHTTPRequestObject();   // defined in ajax.js

	if (http) {
		http.open("GET", url, true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function () {
			if (http.readyState === 4) {
				let response;

				try {
					response = JSON.parse(http.response.toString());
				} catch (e) {
					return console.error(e);
				}
				ctRenderTableJoinSelectBox(control_name, response, index, execute_all, sub_index, object_id, formId, forceValue);
			}
		};
		http.send();
	}
}

// --------------------- Inputbox: Records


function ctInputBoxRecords_removeOptions(selectobj) {
	//Old calls replaced
	for (let i = selectobj.options.length - 1; i >= 0; i--) {
		selectobj.remove(i);
	}
}


function ctInputBoxRecords_DoAddItem(control_name, control_name_postfix) {
	//Old calls replaced
	let o = document.getElementById(control_name + control_name_postfix);
	if (o.selectedIndex === -1)
		return;

	let r = o.options[o.selectedIndex].value;
	let t = o.options[o.selectedIndex].text;
	let p = 1;

	if (document.getElementById(control_name + control_name_postfix + '_elementsPublished')) {
		let elementsPublished = document.getElementById(control_name + control_name_postfix + '_elementsPublished').innerHTML.split(",");
		let elementsID = document.getElementById(control_name + control_name_postfix + '_elementsID').innerHTML.split(",");

		for (let i = 0; i < elementsPublished.length; i++) {
			if (elementsID[i] === r)
				p = elementsPublished[i];
		}
	}

	for (let x = 0; x < ctInputBoxRecords_r[control_name].length; x++) {
		if (ctInputBoxRecords_r[control_name][x] === r) {
			alert("Item already exists");
			return false;
		}
	}

	ctInputBoxRecords_r[control_name].push(r);
	ctInputBoxRecords_v[control_name].push(t);
	ctInputBoxRecords_p[control_name].push(p);

	o.remove(o.selectedIndex);
	ctInputBoxRecords_showMultibox(control_name, control_name_postfix);
}

function ctInputBoxRecords_cancel(control_name) {
	//Old calls replaced
	document.getElementById(control_name + '_addButton').style.visibility = "visible";
	document.getElementById(control_name + '_addBox').style.visibility = "hidden";
}

function ctInputBoxRecords_deleteItem(control_name, control_name_postfix, index) {
	//Old calls replaced
	ctInputBoxRecords_r[control_name].splice(index, 1);
	ctInputBoxRecords_v[control_name].splice(index, 1);
	ctInputBoxRecords_p[control_name].splice(index, 1);

	ctInputBoxRecords_showMultibox(control_name, control_name_postfix);

	const addButton = document.getElementById(control_name + '_addButton');
	const isHidden = addButton.style.visibility === 'hidden';

	if (isHidden) {
		ctInputBoxRecords_cancel(control_name, '_selector');
		CTEditHelper.ctInputBoxRecords_addItem(control_name, '_selector')
	}
}

function ctInputBoxRecords_showMultibox(control_name, control_name_postfix) {
	//Old calls replaced

	let l = document.getElementById(control_name);// + control_name_postfix);
	ctInputBoxRecords_removeOptions(l);

	let opt1 = document.createElement("option");
	opt1.value = '0';
	opt1.innerHTML = "";
	opt1.setAttribute("selected", "selected");
	l.appendChild(opt1);

	let v = '<table style="width:100%;"><tbody>';
	for (let i = 0; i < ctInputBoxRecords_r[control_name].length; i++) {
		v += '<tr><td style="border-bottom:1px dotted grey;">';
		if (ctInputBoxRecords_p[control_name][i] == 0)
			v += ctInputBoxRecords_v[control_name][i];
		else
			v += ctInputBoxRecords_v[control_name][i];

		v += '</td>';

		let deleteImage;

		if (CTEditHelper.cmsName === "Joomla")
			deleteImage = CTEditHelper.websiteRoot + 'components/com_customtables/libraries/customtables/media/images/icons/cancel.png';
		else if (CTEditHelper.cmsName === "WordPress")
			deleteImage = CTEditHelper.websiteRoot + 'wp-content/plugins/customtables/libraries/customtables/media/images/icons/cancel.png';

		v += '<td style="border-bottom:1px dotted grey;min-width:16px;">';
		let onClick = "ctInputBoxRecords_deleteItem('" + control_name + "','" + control_name_postfix + "'," + i + ")";
		v += '<img src="' + deleteImage + '" alt="Delete" title="Delete" style="width:16px;height:16px;cursor: pointer;" onClick="' + onClick + '" />';
		v += '</td>';
		v += '</tr>';

		const opt = document.createElement("option");
		opt.value = ctInputBoxRecords_r[control_name][i];
		opt.innerHTML = ctInputBoxRecords_v[control_name][i];
		opt.style.cssText = "color:red;";
		opt.setAttribute("selected", "selected");

		l.appendChild(opt);
	}
	v += '</tbody></table>';

	document.getElementById(control_name + "_box").innerHTML = v;
}

/* -------------------------- Filtering --------------------------- */

function ctInputbox_removeEmptyParents(control_name, control_name_postfix) {
	//Old calls replaced
	let selectObj = document.getElementById(control_name + 'SQLJoinLink');
	let elementsFilter = document.getElementById(control_name + control_name_postfix + '_elementsFilter').innerHTML.split(";");

	for (let o = selectObj.options.length - 1; o >= 0; o--) {
		let c = 0;
		let v = selectObj.options[o].value;

		for (let i = 0; i < control_name + elementsFilter.length; i++) {
			let f = elementsFilter[i];
			if (typeof f != "undefined") {

				let f_list = f.split(",");

				if (f_list.indexOf(v) !== -1)
					c++;
			}
		}
	}
}


// ------------------------ Google Map coordinates

function ctInputbox_googlemapcoordinates(inputbox_id) {

	let map_obj = document.getElementById(inputbox_id + "_map");

	if (typeof google === 'undefined') {
		map_obj.innerHTML = 'Custom Tables Configuration: Google Map API Key not provided.';
		map_obj.style.display = "block";
		return false;
	}

	let val = document.getElementById(inputbox_id).value;
	let val_list = val.split(",");

	let def_latval = (val_list[0] !== '' ? parseFloat(val_list[0]) : -8);
	let def_longval = (val_list.length > 1 && val_list[1] !== '' ? parseFloat(val_list[1]) : -79);

	let def_zoomval = (val_list.length > 2 && val_list[2] !== '' ? parseFloat(val_list[2]) : 10);
	if (def_zoomval === 0)
		def_zoomval = 10;

	let curpoint = new google.maps.LatLng(def_latval, def_longval);


	if (map_obj.style.display === "block") {
		map_obj.style.display = "none";
		map_obj.innerHTML = "";
		return false;
	}

	gmapdata[inputbox_id] = new google.maps.Map(map_obj, {
		center: curpoint,
		zoom: def_zoomval,
		mapTypeId: 'roadmap'
	});

	gmapmarker[inputbox_id] = new google.maps.Marker({
		map: gmapdata[inputbox_id],
		position: curpoint
	});

	infoWindow = new google.maps.InfoWindow;

	google.maps.event.addListener(gmapdata[inputbox_id], 'click', function (event) {
		document.getElementById(inputbox_id).value = event.latLng.lat().toFixed(6) + "," + event.latLng.lng().toFixed(6);
		gmapmarker[inputbox_id].setPosition(event.latLng);
	});

	google.maps.event.addListener(gmapmarker[inputbox_id], 'click', function () {
		infoWindow.open(gmapdata[inputbox_id], gmapmarker[inputbox_id]);
	});

	map_obj.style.display = "block";

	return false;
}


/*
function download(dataURL, filename) {
  var blob = dataURLToBlob(dataURL);
  var url = window.URL.createObjectURL(blob);

  var a = document.createElement("a");
  a.style = "display: none";
  a.href = url;
  a.download = filename;

  document.body.appendChild(a);
  a.click();

  window.URL.revokeObjectURL(url);
}

// One could simply use Canvas#toBlob method instead, but It's just to show
// that it can be done using result of SignaturePad#toDataURL.
function dataURLToBlob(dataURL) {
  // Code taken from https://github.com/ebidel/filer.js
  var parts = dataURL.split(\';base64,\');
  var contentType = parts[0].split(":")[1];
  var raw = window.atob(parts[1]);
  var rawLength = raw.length;
  var uInt8Array = new Uint8Array(rawLength);

  for (var i = 0; i < rawLength; ++i) {
    uInt8Array[i] = raw.charCodeAt(i);
  }

  return new Blob([uInt8Array], { type: contentType });
}
*/

//---------------------------------

!function (a, b) {
	"function" == typeof define && define.amd ? define([], function () {
		return a.SignaturePad = b()
	}) : "object" == typeof exports ? module.exports = b() : a.SignaturePad = b()
}(this, function () {/*!
 * Signature Pad v1.3.5 | https://github.com/szimek/signature_pad
 * (c) 2015 Szymon Nowak | Released under the MIT license
 */
	const a = function (a) {
		const d = function (a, b, c, d) {
			this.startPoint = a, this.control1 = b, this.control2 = c, this.endPoint = d
		};
		const c = function (a, b, c) {
			this.x = a, this.y = b, this.time = c || (new Date).getTime()
		};
		"use strict";
		const b = function (a, b) {
			const c = b || {};
			this.velocityFilterWeight = c.velocityFilterWeight || .7, this.minWidth = c.minWidth || .5, this.maxWidth = c.maxWidth || 2.5, this.dotSize = c.dotSize || function () {
				return (this.minWidth + this.maxWidth) / 2
			}, this.penColor = c.penColor || "black", this.backgroundColor = c.backgroundColor || "rgba(0,0,0,0)", this.onEnd = c.onEnd, this.onBegin = c.onBegin, this._canvas = a, this._ctx = a.getContext("2d"), this.clear(), this._handleMouseEvents(), this._handleTouchEvents()
		};
		b.prototype.clear = function () {
			const a = this._ctx, b = this._canvas;
			a.fillStyle = this.backgroundColor, a.clearRect(0, 0, b.width, b.height), a.fillRect(0, 0, b.width, b.height), this._reset()
		}, b.prototype.toDataURL = function () {
			const a = this._canvas;
			return a.toDataURL.apply(a, arguments)
		}, b.prototype.fromDataURL = function (a) {
			const b = this, c = new Image, d = window.devicePixelRatio || 1, e = this._canvas.width / d,
				f = this._canvas.height / d;
			this._reset(), c.src = a, c.onload = function () {
				b._ctx.drawImage(c, 0, 0, e, f)
			}, this._isEmpty = !1
		}, b.prototype._strokeUpdate = function (a) {
			const b = this._createPoint(a);
			this._addPoint(b)
		}, b.prototype._strokeBegin = function (a) {
			this._reset(), this._strokeUpdate(a), "function" == typeof this.onBegin && this.onBegin(a)
		}, b.prototype._strokeDraw = function (a) {
			const b = this._ctx, c = "function" == typeof this.dotSize ? this.dotSize() : this.dotSize;
			b.beginPath(), this._drawPoint(a.x, a.y, c), b.closePath(), b.fill()
		}, b.prototype._strokeEnd = function (a) {
			const b = this.points.length > 2, c = this.points[0];
			!b && c && this._strokeDraw(c), "function" == typeof this.onEnd && this.onEnd(a)
		}, b.prototype._handleMouseEvents = function () {
			const b = this;
			this._mouseButtonDown = !1, this._canvas.addEventListener("mousedown", function (a) {
				1 === a.which && (b._mouseButtonDown = !0, b._strokeBegin(a))
			}), this._canvas.addEventListener("mousemove", function (a) {
				b._mouseButtonDown && b._strokeUpdate(a)
			}), a.addEventListener("mouseup", function (a) {
				1 === a.which && b._mouseButtonDown && (b._mouseButtonDown = !1, b._strokeEnd(a))
			})
		}, b.prototype._handleTouchEvents = function () {
			const b = this;
			this._canvas.style.msTouchAction = "none", this._canvas.addEventListener("touchstart", function (a) {
				const c = a.changedTouches[0];
				b._strokeBegin(c)
			}), this._canvas.addEventListener("touchmove", function (a) {
				a.preventDefault();
				const c = a.changedTouches[0];
				b._strokeUpdate(c)
			}), a.addEventListener("touchend", function (a) {
				const c = a.target === b._canvas;
				c && b._strokeEnd(a)
			})
		}, b.prototype.isEmpty = function () {
			return this._isEmpty
		}, b.prototype._reset = function () {
			this.points = [], this._lastVelocity = 0, this._lastWidth = (this.minWidth + this.maxWidth) / 2, this._isEmpty = !0, this._ctx.fillStyle = this.penColor
		}, b.prototype._createPoint = function (a) {
			const b = this._canvas.getBoundingClientRect();
			return new c(a.clientX - b.left, a.clientY - b.top)
		}, b.prototype._addPoint = function (a) {
			let b, c, e, f, g = this.points;
			g.push(a), g.length > 2 && (3 === g.length && g.unshift(g[0]), f = this._calculateCurveControlPoints(g[0], g[1], g[2]), b = f.c2, f = this._calculateCurveControlPoints(g[1], g[2], g[3]), c = f.c1, e = new d(g[1], b, c, g[2]), this._addCurve(e), g.shift())
		}, b.prototype._calculateCurveControlPoints = function (a, b, d) {
			const e = a.x - b.x, f = a.y - b.y, g = b.x - d.x, h = b.y - d.y,
				i = {x: (a.x + b.x) / 2, y: (a.y + b.y) / 2}, j = {x: (b.x + d.x) / 2, y: (b.y + d.y) / 2},
				k = Math.sqrt(e * e + f * f), l = Math.sqrt(g * g + h * h), m = i.x - j.x, n = i.y - j.y,
				o = l / (k + l), p = {x: j.x + m * o, y: j.y + n * o}, q = b.x - p.x, r = b.y - p.y;
			return {c1: new c(i.x + q, i.y + r), c2: new c(j.x + q, j.y + r)}
		}, b.prototype._addCurve = function (a) {
			let b, c, d = a.startPoint, e = a.endPoint;
			b = e.velocityFrom(d), b = this.velocityFilterWeight * b + (1 - this.velocityFilterWeight) * this._lastVelocity, c = this._strokeWidth(b), this._drawCurve(a, this._lastWidth, c), this._lastVelocity = b, this._lastWidth = c
		}, b.prototype._drawPoint = function (a, b, c) {
			const d = this._ctx;
			d.moveTo(a, b), d.arc(a, b, c, 0, 2 * Math.PI, !1), this._isEmpty = !1
		}, b.prototype._drawCurve = function (a, b, c) {
			let d, e, f, g, h, i, j, k, l, m, n, o = this._ctx, p = c - b;
			for (d = Math.floor(a.length()), o.beginPath(), f = 0; d > f; f++) g = f / d, h = g * g, i = h * g, j = 1 - g, k = j * j, l = k * j, m = l * a.startPoint.x, m += 3 * k * g * a.control1.x, m += 3 * j * h * a.control2.x, m += i * a.endPoint.x, n = l * a.startPoint.y, n += 3 * k * g * a.control1.y, n += 3 * j * h * a.control2.y, n += i * a.endPoint.y, e = b + i * p, this._drawPoint(m, n, e);
			o.closePath(), o.fill()
		}, b.prototype._strokeWidth = function (a) {
			return Math.max(this.maxWidth / (a + 1), this.minWidth)
		};
		c.prototype.velocityFrom = function (a) {
			return this.time !== a.time ? this.distanceTo(a) / (this.time - a.time) : 1
		}, c.prototype.distanceTo = function (a) {
			return Math.sqrt(Math.pow(this.x - a.x, 2) + Math.pow(this.y - a.y, 2))
		};
		return d.prototype.length = function () {
			let a, b, c, d, e, f, g, h, i = 10, j = 0;
			for (a = 0; i >= a; a++) b = a / i, c = this._point(b, this.startPoint.x, this.control1.x, this.control2.x, this.endPoint.x), d = this._point(b, this.startPoint.y, this.control1.y, this.control2.y, this.endPoint.y), a > 0 && (g = c - e, h = d - f, j += Math.sqrt(g * g + h * h)), e = c, f = d;
			return j
		}, d.prototype._point = function (a, b, c, d, e) {
			return b * (1 - a) * (1 - a) * (1 - a) + 3 * c * (1 - a) * (1 - a) * a + 3 * d * (1 - a) * a * a + e * a * a * a
		}, b
	}(document);
	return a
});

function activateJoomla3Tabs() {
	jQuery(function ($) {
		const tabs$ = $(".nav-tabs a");

		$(window).on("hashchange", function () {
			const hash = window.location.hash, // get current hash
				menu_item$ = tabs$.filter('[href="' + hash + '"]'); // get the menu element

			menu_item$.tab("show"); // call bootstrap to show the tab
		}).trigger("hashchange");

		const hash = window.location.hash;
		hash && $('ul.nav a[href="' + hash + '"]').tab('show');

		$('.nav-tabs a').click(function (e) {
			$(this).tab('show');
			const scrollMe = $('body').scrollTop() || $('html').scrollTop();
			window.location.hash = this.hash;
			$('html,body').scrollTop(scrollMe);
		});
	});
}

// Looks like this method is unused
function setUpdateChildTableJoinField(childFieldName, parentFieldName, childFilterFieldName) {
	document.getElementById(ctFieldInputPrefix + parentFieldName + '0').addEventListener('change', function () {
		updateChildTableJoinField(childFieldName, parentFieldName, childFilterFieldName, fieldInputPrefix);
	});
}

// Looks like this method is unused
function updateChildTableJoinField(childFieldName, parentFieldName, childFilterFieldName, fieldInputPrefix) {
	//This function updates the list of items in Table Join field based on its parent value;
	let parentValue = document.getElementById(fieldInputPrefix + parentFieldName).value;
	let wrapper = document.getElementById(fieldInputPrefix + childFieldName + 'Wrapper');
	let key = wrapper.dataset.key;
	let where = childFilterFieldName + '=' + parentValue;
	let url;

	if (CTEditHelper.cmsName === "Joomla") {
		let link = location.href.split('administrator/index.php?option=com_customtables');

		if (link.length === 2)//to make sure that it will work in the back-end
			url = CTEditHelper.websiteRoot + 'administrator/index.php?option=com_customtables&view=catalog&tmpl=component&from=json&key=' + key + '&index=0&where_base64=' + encodeURIComponent(where);
		else
			url = CTEditHelper.websiteRoot + 'index.php?option=com_customtables&view=catalog&tmpl=component&from=json&key=' + key + '&index=0&where_base64=' + encodeURIComponent(where);

	} else if (CTEditHelper.cmsName === "WordPress") {
		url = CTEditHelper.websiteRoot + 'index.php?page=customtables-api-tablejoin&key=' + key + '&index=0&where_base64=' + encodeURIComponent(where);
		console.error(url);
		console.error("updateChildTableJoinField is going to be supported by WP yet.");
		alert("updateChildTableJoinField is going to be supported by WP yet.")
	}

	fetch(url)

		.then(r => r.json())
		.then(r => {
			ctRenderTableJoinSelectBox(fieldInputPrefix + childFieldName, r, 0, false, 0, fieldInputPrefix + childFieldName + '0', wrapper.dataset.formname, null);
		})
		.catch(error => console.error("Error", error));
}

function refreshTableJoinField(fieldName, response, fieldInputPrefix) {

	console.log("fieldInputPrefix:", fieldInputPrefix)
	console.log("fieldName:", fieldName)

	let valueObject = document.getElementById(fieldInputPrefix + fieldName);
	valueObject.value = response['id'];
	if (valueObject.onchange) {
		valueObject.dispatchEvent(new Event('change'));
	}

	let wrapper = document.getElementById(fieldInputPrefix + fieldName + 'Wrapper');
	if (wrapper === null)
		return;

	//let valueFiltersStr = Base64.decode(wrapper.dataset.valuefilters).replace(/[^\x00-\x7F]/g, "");
	let valueFiltersNamesStr = Base64.decode(wrapper.dataset.valuefiltersnames).replace(/[^\x00-\x7F]/g, "");
	let valueFiltersNames = JSON.parse(valueFiltersNamesStr);
	let NewValueFilters = [];

	for (let i = 0; i < valueFiltersNames.length; i++) {
		if (valueFiltersNames[i] !== null) {
			console.warn("response", response['record']);
			console.warn("valueFiltersNames[i]", valueFiltersNames[i]);

			let value = "";
			if (response['record']) {
				value = response['record']['es_' + valueFiltersNames[i]];
				NewValueFilters.push(value);
			}

			let index = i - 1;
			let selectorID = fieldInputPrefix + fieldName + index;
			let selector = document.getElementById(selectorID);
			selector.value = value;
		} else {
			NewValueFilters.push(null);
		}
	}
	let newValueFiltersStr = JSON.stringify(NewValueFilters);
	wrapper.dataset.valuefilters = Base64.encode(newValueFiltersStr);

	let index = NewValueFilters.length - 1;
	ctUpdateTableJoinLink(fieldInputPrefix + fieldName, index, true, 0, fieldInputPrefix + fieldName + '0', wrapper.dataset.formname, true, response.id);
}

//Virtual Select
async function onCTVirtualSelectServerSearch(searchValue, virtualSelect) {

	let selectorElement = document.getElementById(virtualSelect.dropboxWrapper);
	let wrapper = document.getElementById(selectorElement.dataset.wrapper);
	let key = wrapper.dataset.key;
	let url;

	if (CTEditHelper.cmsName === "Joomla") {
		let link = location.href.split('administrator/index.php?option=com_customtables');

		if (link.length === 2)//to make sure that it will work in the back-end
			url = CTEditHelper.websiteRoot + 'administrator/index.php?option=com_customtables&view=catalog&tmpl=component&clean=1&from=json&key=' + key + '&index=0&limit=20&';
		else
			url = CTEditHelper.websiteRoot + 'index.php?option=com_customtables&view=catalog&tmpl=component&clean=1&from=json&key=' + key + '&index=0&limit=20&';

	} else if (CTEditHelper.cmsName === "WordPress") {
		console.error("onCTVirtualSelectServerSearch is not supported by WP yet.");
		alert("onCTVirtualSelectServerSearch is not supported by WP yet.")
		return;
	}

	if (searchValue !== "")
		url += "&search=" + searchValue;

	let newList = [];

	const response = await fetch(url);
	const contentType = response.headers.get("content-type");
	if (!contentType || !contentType.includes("application/json")) {
		console.warn(url);
		console.warn(contentType);
		console.warn(response);
		console.warn(response.toString());
		throw new TypeError("Oops, we haven't got JSON!");
	}

	let jsonData;

	try {
		jsonData = await response.json();
	} catch (error) {
		console.warn("Error:  SyntaxError: JSON.parse");
		console.warn(response);
		return;
	}

	if (jsonData.success) {
		for (let i = 0; i < jsonData.data.length; i++) {
			let item = jsonData.data[i];

			//let doc = new DOMParser().parseFromString(item.label, 'text/html');
			//let label = doc.documentElement.textContent;
			//newList.push({value: item.value, label: decodeURI(label)});

			newList.push({value: item.value, label: item.label});
		}

		virtualSelect.setServerOptions(newList);
	} else {
		console.warn("Error:", jsonData.errors);
	}

}



