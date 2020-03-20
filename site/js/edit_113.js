jQuery(function($) {
	"use strict";

	$(document)
		.on('click', ".btn-group label:not(.active)", function() {

			var $label = $(this);
			var $input = $('#' + $label.attr('for'));

			if ($input.prop('checked'))
				return;

			$label.closest('.btn-group').find("label").removeClass('active btn-success btn-danger btn-primary');

			var btnClass = 'primary';


			if ($input.val() != '')
			{
				var reversed = $label.closest('.btn-group').hasClass('btn-group-reversed');
				btnClass = ($input.val() == 0 ? !reversed : reversed) ? 'danger' : 'success';
			}

			$label.addClass('active btn-' + btnClass);
			$input.prop('checked', true).trigger('change');
		});

});


/* ----------------------------------------------------------------------------------------------------------- */


function setTask(task,returnlink,submitForm)
{
 
 if(returnlink!="")
 {
		var obj=document.getElementById('returnto');
		if(obj)
			obj.value=returnlink;
 }
 
	var obj2=document.getElementById('task');
	if(obj2)
			obj2.value=task;
		else
			alert("Task Element not found.");
	
	if(submitForm)
	{
		var objForm=document.getElementById('eseditForm');
		if(objForm)
			objForm.submit();
		else
			alert("Form not found.");
	}
	
	
}

function recaptchaCallback()
{
    var obj1=document.getElementById("customtables_submitbutton");
    if (typeof obj1 != "undefined")
        obj1.removeAttribute('disabled');

    var obj2=document.getElementById("customtables_submitbuttonasnew");
    if (typeof obj2 != "undefined")
        obj2.removeAttribute('disabled');
}

function checkRequiredFields()
{

    var requiredFields=document.getElementsByClassName("required");

    for(var i=0;i<requiredFields.length;i++)
    {
        if (typeof requiredFields[i].id != "undefined")
        {
            if (requiredFields[i].id.indexOf("sqljoin_table_comes_")!=-1)
            {
                if(!CheckSQLJoinRadioSelections(requiredFields[i].id))
                    return false;

            }
            if (requiredFields[i].id.indexOf("ct_ubloadfile_box_")!=-1)
            {
                if(!CheckImageUploader(requiredFields[i].id))
                    return false;

            }

        }

        if (typeof requiredFields[i].name != "undefined")
        {
                var n=requiredFields[i].name.toString();

                if(n.indexOf("comes_")!=-1)
                {
                    var objname=n.replace('_selector','');

                    var lbln=objname.replace('[]','');
                    var lblobj=document.getElementById(lbln+"-lbl");
                    var label="One field";

                    if (typeof lblobj != "undefined" && lblobj!=null)
                        label=lblobj.innerHTML;

                    if(requiredFields[i].type=="select-one")
                    {
                        var obj=document.getElementById(objname);
                        var count=obj.options.length;

                        if(count===0)
                        {
                            alert(label+" not selected.");
                            return false;
                        }
                    }
                    else if(requiredFields[i].type=="select-multiple")
                    {
                        var count_multiple_obj=document.getElementById(lbln);

                        var options=count_multiple_obj.options;

                        var count_multiple = 0;
                        for (var i2=0; i2 < options.length; i2++)
                        {
                            if (options[i2].selected)
                                count_multiple++;
                        }

                        if(count_multiple===0)
                        {
                            alert(label+" not selected.");
                            return false;
                        }
                    }




                }
        }
    }

    return true;
}

        function SetUsetInvalidClass(id,isOk)
        {
            var frameObj=document.getElementById(id);

            var c=frameObj.className;
            if(c.indexOf("invalid")==-1)
            {
                if(!isOk)
                {
                    if(c=="")
                        c="invalid";
                    else
                        c=c+" invalid";
                }

            }
            else
            {
                if(isOk)
                {
                    c=c.replace("invalid","");
                }
            }

            frameObj.className=c;
        }

        function CheckImageUploader(id)
        {

            var objid=id.replace("ct_ubloadfile_box_","comes_");
            var obj=document.getElementById(objid);
            if(obj.value==="")
            {
                SetUsetInvalidClass(id,false);
                return false;
            }

            SetUsetInvalidClass(id,true);
            return true;
        }

        function CheckSQLJoinRadioSelections(id)
        {
            var field_name=id.replace('sqljoin_table_comes_','');
            var obj_name='comes_'+field_name;
            var radios = document.getElementsByName(obj_name);

            var selected=false;
            for(var i=0;i<radios.length;i++)
            {
                if (radios[i].type === 'radio' && radios[i].checked)
                {
                    selected=true;
                    break;
                }
            }

            if(!selected)
            {
                var lblobj=document.getElementById(obj_name+"-lbl");
                var label=lblobj.innerHTML;
                alert(label+" not selected.");
                return false;
            }

            return true;
        }


		function clearListingID()
		{

			var obj=document.getElementById("listing_id");
			obj.value="";

			var frm=document.getElementById("eseditForm");
			frm.submit();

		}
