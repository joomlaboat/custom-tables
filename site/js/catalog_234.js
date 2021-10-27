
    var es_LinkLoading=false;

    function ctCreateUser(msg,listing_id, toolbarboxid)
    {
        if (confirm(msg))
        {
			var obj=document.getElementById(toolbarboxid);
			obj.innerHTML='';
			
            var returnto=btoa(window.location.href);
            var link=esPrepareLink(['task','listing_id','returnto','ids'],['task=createuser','listing_id='+listing_id,'returnto='+returnto]);
            window.location.href = link;
        }
    }
	
	function ctResetPassword(msg,listing_id, toolbarboxid)
    {
        if (confirm(msg))
        {
			var obj=document.getElementById(toolbarboxid);
			obj.innerHTML='';
					
            var returnto=btoa(window.location.href);
            var link=esPrepareLink(['task','listing_id','returnto','ids'],['task=resetpassword','listing_id='+listing_id,'returnto='+returnto]);
            window.location.href = link;
        }
    }

    function esPrepareLink(deleteParams,addParams,custom_link)
    {
        var link='';
		
		if(custom_link && custom_link!=='')
			link = custom_link;
		else
			link = window.location.href;

        var pair=link.split('#');
        link=pair[0];

        for(var i=0;i<deleteParams.length;i++)
        {
            link=removeURLParameter(link, deleteParams[i]);
        }

        for(var a=0;a<addParams.length;a++)
        {

            if(link.indexOf("?")==-1)
                link+="?"; else link+="&";

            link+=addParams[a];
        }

        return link;
    }

    function esEditObject(objid, toolbarboxid, Itemid, tmpl, returnto)
    {
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;

        var obj=document.getElementById(toolbarboxid);
        obj.innerHTML='';

        var returnto=btoa(window.location.href);
        var link='/index.php?option=com_customtables&view=edititem&listing_id='+objid+'&Itemid='+Itemid+'&returnto='+returnto;

        if(tmpl!=='')
            link+='&tmpl='+tmpl;

        link+='&returnto='+returnto;

        window.location.href = link;

        //es_LinkLoading=false;

    }

    function esRefreshObject(objid, toolbarboxid)
    {
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;

        var obj=document.getElementById(toolbarboxid);
        obj.innerHTML='';

        var current_url=esPrepareLink(['returnto','ids'],[]);
        var returnto=btoa(current_url);
        var link=esPrepareLink(['task','listing_id','returnto','ids'],['task=refresh','listing_id='+objid,'returnto='+returnto]);


        window.location.href = link;
        return;
    }

	function ctOrderChanged(object)
    {
		//var returnto=btoa(window.location.href);
		var current_url=esPrepareLink(['returnto','task','orderby'],[]);
        var returnto=btoa(current_url);
		
        var link=esPrepareLink(['task'],['task=setorderby','orderby=' + object.value,'returnto='+returnto]);
        window.location.href = link;
	}

    function esPublishObject(objid, toolbarboxid,publish)
    {
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;

        var obj=document.getElementById(toolbarboxid);
        obj.innerHTML='';

        var task='';
        if(publish==1)
            task='task=publish';
        else
            task='task=unpublish';

        var returnto=btoa(window.location.href);
        var link=esPrepareLink(['task','listing_id','returnto','ids'],[task,'listing_id='+objid,'returnto='+returnto]);


        window.location.href = link;
        return;

    }


    function esDeleteObject(msg, objid, toolbarboxid, custom_link)
    {
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;

        if (confirm(msg))
        {
			let obj=document.getElementById(toolbarboxid).innerHTML='';

            let returnto=btoa(window.location.href);
			let link=esPrepareLink(['task','listing_id','returnto','ids'],['task=delete','listing_id='+objid,'returnto='+returnto],custom_link);

	        window.location.href = link;
        }
        else
            es_LinkLoading=false;
    }

    function es_SearchBoxKeyPress(e)
	{
		if(e.keyCode==13)//enter key pressed
		    es_SearchBoxDo();
	}


    function es_SearchBoxDo()
	{
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;

		var w=[];
		var elt = document.getElementById("esSearchBoxFields").value;
		var flds=elt.split(",");
		for(var i=0;i<flds.length;i++)
		{
			var n=flds[i].split(":");
			var obj = document.getElementById(n[0]);
			if(obj)
			{
				var o=obj.value;

				if(o!=="" && o!=="0")//oInt!==0)
				{
					if(n[2]==="")
					{
    					if(o.indexOf("-to-")!=-1)
						{
							if(o!="-to-")
								w.push(n[1]+"_r_="+o);
						}
						else
							w.push(n[1]+"="+o);
					}
					else
						w.push(n[1]+"="+n[2]+"."+o);//Custom Tables Structure
				}
			}
			else
				alert('Element "'+n[0]+'" not found.');
		}

		var link=esPrepareLink(['where','task','listing_id','returnto'],["where="+Base64.encode(w.join(" and "))]);

        window.location.href = link;
	}


    function esCheckboxAllclicked(tableid)
    {
        var checkboxobj=document.getElementById("esCheckboxAll"+tableid);
        var elements = document.getElementsByName("esCheckbox"+tableid);

        var ids=[];
        for(var i=0;i<elements.length;i++)
        {
            var d=parseInt(elements[i].value);

            if(ids.indexOf(d)==-1)
            {
                ids.push(d);

                var obj = document.getElementById(elements[i].id);
                obj.checked=checkboxobj.checked;
            }
        }
    }

    function getListOfSelectedRecords(tableid)
    {
        var selectedIds=[];
        var elements = document.getElementsByName("esCheckbox"+tableid);

        for(var i=0;i<elements.length;i++)
        {
            var obj = document.getElementById(elements[i].id);

            if(obj.checked)
            {
                var d=parseInt(elements[i].value);

                if(selectedIds.indexOf(d)==-1)
                    selectedIds.push(d);
            }
        }
        return selectedIds;
    }

    function esToolBarDO(task,tableid)
    {
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;

        var elements = getListOfSelectedRecords(tableid);

        if (elements.length===0)
        {
            alert("Please select records first.");
            es_LinkLoading=false;
            return;
        }

        if(task=='delete')
        {
            if (!confirm('Do you want to delete '+elements.length+' records?'))
            {
                es_LinkLoading=false;
                return;
            }
        }

        var toolbarboxid='esToolBar_'+task+'_box_'+tableid;

        var obj=document.getElementById(toolbarboxid);
        obj.style.visibility='hidden';

        var current_url=esPrepareLink(['returnto','ids'],[]);
        var returnto=btoa(current_url);
        //var returnto=btoa(window.location.href);

        var link=esPrepareLink(['task','listing_id','returnto','ids'],['task='+task,'ids='+elements.toString(),'returnto='+returnto]);
        window.location.href = link;
        return;


    }


    //https://stackoverflow.com/a/1634841
    function removeURLParameter(url, parameter)
    {
        //prefer to use l.search if you have a location/link object
        var urlparts= url.split('?');
        if (urlparts.length>=2)
        {

            var prefix= encodeURIComponent(parameter)+'=';
            var pars= urlparts[1].split(/[&;]/g);

            //reverse iteration as may be destructive
            for (var i= pars.length; i-- > 0;) {
                //idiom for string.startsWith
                if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                    pars.splice(i, 1);
                }
            }

            url= urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
            return url;
        } else {
            return url;
        }
    }
    
    function ct_UpdateSingleValue(WebsiteRoot,Itemid,fieldname_,record_id,postfix)
    {
        var fieldname=fieldname_.split('_')[0];
        var url=WebsiteRoot+'index.php?option=com_customtables&amp;view=edititem&amp;Itemid='+Itemid;
        var http = null;
		var params = "";
		
		var obj_checkbox_off=document.getElementById("com_"+record_id+"_"+fieldname_+"_off");
		if(obj_checkbox_off)
		{
			//Bit confusing. But this is needed to save Unchecked values
			params="comes_"+fieldname_+"_off="+obj_checkbox_off.value;
			
			if(parseInt(obj_checkbox_off.value)==1)
				params+="&comes_"+fieldname_+"="+document.getElementById("com_"+record_id+"_"+fieldname_).value;
		}
		else
			params+="&comes_"+fieldname_+"="+document.getElementById("com_"+record_id+"_"+fieldname_).value;
		
		
		
        params+="&task=save";
        params+="&Itemid="+Itemid;
        params+="&listing_id="+record_id;
    
        var obj=document.getElementById("com_"+record_id+"_"+fieldname+postfix+"_div");
		if(obj)
			obj.className = "ct_loader";

        if (!http)
            http = CreateHTTPRequestObject ();   // defined in ajax.js

        if (http)
        {
            http.open("POST", url+"&clean=1", true);
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            http.onreadystatechange = function()
            {

                if (http.readyState == 4)
                {
                    var res=http.response;

                    if(res=="saved")
                    {
                        obj.className = "ct_checkmark ct_checkmark_hidden";//+css_class;
                    }
                    else
                    {
                        obj.className = "ct_checkmark_err ";
                        
                        if(res.indexOf('<div class="alert-message">Nothing to save</div>')!=-1)
                            alert('Nothing to save. Check Edit From layout.');
                        else if(res.indexOf('view-login')!=-1)
                            alert('Session expired. Please login again.');
                        //else
                            //alert(res);
                    }
                }
            };
            http.send(params);
        }
        else
        {
        }
    }
