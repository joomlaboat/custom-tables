var uploaderParams=[];

function updateUploadedFileBox(index){
	if(uploaderParams[index].uploadedFileBox!=null){
		var obj=document.getElementById(uploaderParams[index].uploadedFileBox);

		if (typeof(obj)!== "undefined" && obj!=null){
			if (typeof(obj.style)!== "undefined"){
				if(uploaderParams[index].files_uploaded>0)
					obj.style.display="none";
				else
					obj.style.display="inline-block";
			}
		}
	}
}

function checkIfLogedIn(data){
	if(data.indexOf('Please login first')!=-1){
		alert("Session expired. Please login. You may login in a new tab or windows.");
		return false;
	}
	return true;
}

function ct_getUploader(index,urlstr,maxFileSize,allowedTypes,UploaderForm,SubmitForm,FileUploaderBox,EventMessageBox,tempFileName,fieldValueInputBox,uploadedFileBox)
{
	uploaderParams[index]={files_uploaded:0,ct_uploader_url:urlstr,esUploaderFormID:UploaderForm,AutoSubmitForm:SubmitForm,UploadFileCount:1,fieldValueInputBox:fieldValueInputBox,uploadedFileBox:uploadedFileBox};

	////http://hayageek.com/docs/jquery-upload-file.php#doc

	var showFileCounter_=true;
	var multiple_=true;

	if(uploaderParams[index].UploadFileCount==1)
	{
		showFileCounter_=true;//false;
		multiple_=false;
	}
		
	let uploadFileObject = document.getElementById(FileUploaderBox);
	jQuery(function($)
    {
		
	$("#"+FileUploaderBox).uploadFile({
		url:urlstr,
		multiple:multiple_,
		maxFileSize:maxFileSize,
		dragDrop:false,
		maxFileCount:uploaderParams[index].UploadFileCount,
		allowedTypes:allowedTypes,
		fileName:tempFileName,
		showDelete:true,
		showFileCounter:showFileCounter_,
		onLoad:function(obj){
//				$("#"+EventMessageBox).html($("#"+EventMessageBox).html()+"<br/>Widget Loaded:");
		},
		onSubmit:function(files){
			//$("#"+EventMessageBox).html($("#"+EventMessageBox).html()+"<br/>Submitting:"+JSON.stringify(files));
			//$("#"+EventMessageBox).html("<br/>Submitting:"+JSON.stringify(files));
			//return false;
		},
		onSuccess:function(files,data,xhr,pd){
			var p=uploaderParams[index];
			var data_=data;
			if(checkIfLogedIn(data_)){
				var res=null
				try {
					res=JSON.parse(data_);
				} catch (e) {
					alert("Response is not JSON: "+data_);
					return false;
				}
					
				if(res.status=='success'){
					var filename=res.filename;
					var obj=document.getElementById(p.fieldValueInputBox);
					obj.value=filename;

					$("#"+EventMessageBox).html("");
					p.files_uploaded+=1;
					updateUploadedFileBox(index);
					if(p.AutoSubmitForm)
						document.getElementById(p.esUploaderFormID).submit();
				}
				else
					$("#"+EventMessageBox).html('Error : <span style="color:red;">'+res.error+'</span>');
			}

			if (typeof checkRequiredFields === 'function')
				checkRequiredFields();
		},
			
		afterUploadAll:function(obj)
		{
			/*
				var msg='';
				if(uploaderParams[index].UploadFileCount==1)
					msg='File has been uploaded';
				else
					msg='All files are uploaded';


				$("#"+EventMessageBox).html($("#"+EventMessageBox).html()+"<br/>"+msg);
				*/
		},
		onError: function(files,status,errMsg,pd){
			$("#"+EventMessageBox).html("<br/>Error "+errMsg+" for: "+JSON.stringify(files));
		},
		onCancel:function(files,pd){
			$("#"+EventMessageBox).html("<br/>Canceled  files: "+JSON.stringify(files));
		},
		deleteCallback: function(data,pd)
		{
			var filelist=JSON.parse(data);
			if(!filelist.error!= undefined){
				var filename=filelist.filename;
				$.post(uploaderParams[index].ct_uploader_url,{op:"delete",name:filename},
				function(resp, textStatus, jqXHR){
					var data_=resp;
					if(checkIfLogedIn(data_)){
						if(textStatus=='success')
							var res=JSON.parse(data_);
					}
				});
			}
				
			document.getElementById(uploaderParams[index].fieldValueInputBox).value="";

			$("#"+EventMessageBox).html("");
			pd.statusbar.hide(); //You choice to hide/not.
			uploaderParams[index].files_uploaded-=1;
			updateUploadedFileBox(index);
			checkRequiredFields();
		}
	});
	
	});
}
