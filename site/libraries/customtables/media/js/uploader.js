/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2025. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

const uploaderParams = [];

function updateUploadedFileBox(index) {
	if (uploaderParams[index].uploadedFileBox != null) {
		const obj = document.getElementById(uploaderParams[index].uploadedFileBox);

		if (typeof (obj) !== "undefined" && obj != null) {
			if (typeof (obj.style) !== "undefined") {
				if (uploaderParams[index].files_uploaded > 0)
					obj.style.display = "none";
				else
					obj.style.display = "inline-block";
			}
		}
	}
}

function checkIfLoggedIn(data) {
	if (data.indexOf('Please login first') !== -1) {
		alert("Session expired. Please login. You may login in a new tab or windows.");
		return false;
	}
	return true;
}

//Used in PHP files
function ct_getUploader(index, URL_String, maxFileSize, allowedTypes, UploaderForm, SubmitForm, FileUploaderBox, EventMessageBox, tempFileName, fieldValueInputBox, uploadedFileBox) {

	uploaderParams[index] = {
		files_uploaded: 0,
		ct_uploader_url: URL_String,
		esUploaderFormID: UploaderForm,
		AutoSubmitForm: SubmitForm,
		UploadFileCount: 1,
		fieldValueInputBox: fieldValueInputBox,
		uploadedFileBox: uploadedFileBox
	};

	////http://hayageek.com/docs/jquery-upload-file.php#doc

	let showFileCounter_ = true;
	let multiple_ = true;

	if (uploaderParams[index].UploadFileCount === 1) {
		showFileCounter_ = true;//false;
		multiple_ = false;
	}

	//let uploadFileObject = document.getElementById(FileUploaderBox);
	jQuery(function ($) {

		$("#" + FileUploaderBox).uploadFile({
			url: URL_String,
			multiple: multiple_,
			maxFileSize: maxFileSize,
			dragDrop: false,
			maxFileCount: uploaderParams[index].UploadFileCount,
			allowedTypes: allowedTypes,
			fileName: tempFileName,
			showDelete: true,
			showFileCounter: showFileCounter_,
			onLoad: function (obj) {
//				$("#"+EventMessageBox).html($("#"+EventMessageBox).html()+"<br/>Widget Loaded:");
			},
			onSubmit: function (files) {
				//$("#"+EventMessageBox).html($("#"+EventMessageBox).html()+"<br/>Submitting:"+JSON.stringify(files));
				//$("#"+EventMessageBox).html("<br/>Submitting:"+JSON.stringify(files));
				//return false;
			},
			onSuccess: function (files, data, xhr, pd) {
				const p = uploaderParams[index];
				const data_ = data;

				if (checkIfLoggedIn(data_)) {
					let res = null;
					try {
						res = JSON.parse(data_);
					} catch (e) {
						alert("Response is not JSON: " + data_);
						return false;
					}

					if (res.status === 'success') {

						document.getElementById(p.fieldValueInputBox).value = res.filename;
						document.getElementById(p.fieldValueInputBox + '_filename').value = res.originalfilename;

						$("#" + EventMessageBox).html("");
						p.files_uploaded += 1;
						updateUploadedFileBox(index);
						if (p.AutoSubmitForm) {

							if (typeof CTEditHelper !== 'undefined') {
								let formObject = document.getElementById(UploaderForm);
								CTEditHelper.checkRequiredFields(formObject);
							}

							document.getElementById(p.esUploaderFormID).submit();
						}
					} else
						$("#" + EventMessageBox).html('Error : <span style="color:red;">' + res.error + '</span>');
				}
			},

			afterUploadAll: function (obj) {

			},
			onError: function (files, status, errMsg, pd) {

				$("#" + EventMessageBox).html("<br/>Error: " + errMsg + " for: " + JSON.stringify(files));
			},
			onCancel: function (files, pd) {
				$("#" + EventMessageBox).html("<br/>Canceled  files: " + JSON.stringify(files));
			},
			deleteCallback: function (data, pd) {
				const filelist = JSON.parse(data);
				if (!filelist.error !== undefined) {
					const filename = filelist.filename;
					$.post(uploaderParams[index].ct_uploader_url, {op: "delete", name: filename},
						function (resp, textStatus, jqXHR) {
							//const data_ = resp;
							//if(checkIfLoggedIn(data_)){
							//	if(textStatus==='success')
							//		const res = JSON.parse(data_);
							//}
						});
				}

				document.getElementById(uploaderParams[index].fieldValueInputBox).value = "";

				$("#" + EventMessageBox).html("");
				pd.statusbar.hide(); //You choose to hide/not.
				uploaderParams[index].files_uploaded -= 1;
				updateUploadedFileBox(index);

				if (typeof CTEditHelper !== 'undefined') {
					let formObject = document.getElementById(UploaderForm);
					CTEditHelper.checkRequiredFields(formObject);
				}
			}
		});
	});
}
