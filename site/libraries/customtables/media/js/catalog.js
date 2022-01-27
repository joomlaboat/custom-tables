
    var es_LinkLoading=false;

    function ctCreateUser(msg,listing_id, toolbarboxid){
        if (confirm(msg)){
			var obj=document.getElementById(toolbarboxid);
			obj.innerHTML='';
			
            var returnto=btoa(window.location.href);
            var link=esPrepareLink(['task','listing_id','returnto','ids'],['task=createuser','listing_id='+listing_id,'returnto='+returnto]);
            window.location.href = link;
        }
    }
	
	function ctResetPassword(msg,listing_id, toolbarboxid){
        if (confirm(msg)){
			var obj=document.getElementById(toolbarboxid);
			obj.innerHTML='';
					
            var returnto=btoa(window.location.href);
            var link=esPrepareLink(['task','listing_id','returnto','ids'],['task=resetpassword','listing_id='+listing_id,'returnto='+returnto]);
            window.location.href = link;
        }
    }

    function esPrepareLink(deleteParams,addParams,custom_link){
        var link='';
		
		if(custom_link && custom_link!=='')
			link = custom_link;
		else
			link = window.location.href;

        var pair=link.split('#');
        link=pair[0];

        for(var i=0;i<deleteParams.length;i++)
            link=removeURLParameter(link, deleteParams[i]);

        for(var a=0;a<addParams.length;a++){

            if(link.indexOf("?")==-1)
                link+="?"; else link+="&";

            link+=addParams[a];
        }

        return link;
    }

    function esEditObject(objid, toolbarboxid, Itemid, tmpl, returnto){
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
    }
	
	function runTheTask(task, tableid,recordid,url,responses,last){
		
		let params = "";
		let http = CreateHTTPRequestObject ();   // defined in ajax.js

		if (http){
			http.open("GET", url, true);
			http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			http.onreadystatechange = function(){
				if (http.readyState == 4){
					let res=http.response;
					if(responses.indexOf(res) != -1){
						
						let element_tableid_tr = "ctTable_" + tableid + '_' + recordid;
						let index = findRowIndexById("ctTable_" + tableid,element_tableid_tr);
						
						if(task == 'delete')
							document.getElementById("ctTable_" + tableid).deleteRow(index); 
						else
							ctCatalogUpdate(tableid, recordid, index);
						
						es_LinkLoading=false;
						
						if(last){
							let toolbarboxid='esToolBar_'+task+'_box_'+tableid;
							document.getElementById(toolbarboxid).style.visibility='visible';
						}
						
					}
					else
						alert(res);
				}
			}
			http.send(params);
		}
	}
	
	function ctRefreshRecord(tableid, recordid, toolbarboxid){
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;

        var obj=document.getElementById(toolbarboxid);
        obj.innerHTML='';
		
		let element_tableid_tr = "ctTable_" + tableid + '_' + recordid;
		
		let tr_object = document.getElementById(element_tableid_tr);
		if(tr_object){
			let url = esPrepareLink(['task','listing_id','returnto','ids'],['task=refresh','listing_id='+recordid,'clean=1','tmpl=component']);
			runTheTask('refresh', tableid,recordid,url,['refreshed'], false);
		}
		else{
			var returnto=btoa(window.location.href);
			var link=esPrepareLink(['task','listing_id','returnto','ids'],['task=refresh','listing_id='+objid,'returnto='+returnto]);
			window.location.href = link;
		}

        return;
    }

	function ctOrderChanged(object){
		var current_url=esPrepareLink(['returnto','task','orderby'],[]);
        var returnto=btoa(current_url);
		
        var link=esPrepareLink(['task'],['task=setorderby','orderby=' + object.value,'returnto='+returnto]);
        window.location.href = link;
	}
	
	function ctPublishRecord(tableid, recordid, toolbarboxid, publish){
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
		
		let element_tableid_tr = "ctTable_" + tableid + '_' + recordid;
		let tr_object = document.getElementById(element_tableid_tr);
		
		if(tr_object){
			let url = esPrepareLink(['task','listing_id','returnto','ids'],[task,'listing_id='+recordid,'clean=1','tmpl=component']);
			runTheTask((publish == 0 ? 'unpublish' : 'publish'), tableid,recordid,url,['published','unpublished'], false);
		}
		else{
			var returnto=btoa(window.location.href);
			var link=esPrepareLink(['task','listing_id','returnto','ids'],[task,'listing_id='+recordid,'returnto='+returnto]);
			window.location.href = link;
		}
		
        return;
    }

	function findRowIndexById(tableid,rowid){

		let rows = document.getElementById(tableid).rows;
		for(let i=0;i<rows.length;i++){
			if(rows.item(i).id == rowid)
				return i;
		}
		return -1;
	}

	function ctDeleteRecord(msg, tableid, recordid, toolbarboxid, custom_link){
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;
		
		if (confirm(msg)){
			let obj=document.getElementById(toolbarboxid).innerHTML='';

			let element_tableid_tr = "ctTable_" + tableid + '_' + recordid;
			
			let tr_object = document.getElementById(element_tableid_tr);
			if(tr_object){
				
				let url = esPrepareLink(['task','listing_id','returnto','ids'],['task=delete','listing_id='+recordid,'clean=1','tmpl=component']);
				runTheTask('delete', tableid,recordid,url,['deleted'], false);
			}
			else{
				let returnto=btoa(window.location.href);
				let link=esPrepareLink(['task','listing_id','returnto','ids'],['task=delete','listing_id='+recordid,'returnto='+returnto],custom_link);
				window.location.href = link;
			}
        }
        else
            es_LinkLoading=false;
    }

    function es_SearchBoxKeyPress(e){
		if(e.keyCode==13)//enter key pressed
		    ctSearchBoxDo();
	}

    function ctSearchBoxDo(){
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;
		let w=[];
		let allSearchElements = document.querySelectorAll('[ctSearchBoxField]');
		
		for(let i=0;i<allSearchElements.length;i++){
			let n=allSearchElements[i].getAttribute('ctSearchBoxField').split(":");
			let obj = document.getElementById(n[0]);
			
			if(obj){
				var o=obj.value;
				if(o!=="" && o!=="0"){
					if(n[2]===""){
    					if(o.indexOf("-to-")!=-1){
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

    function esCheckboxAllclicked(tableid){
        var checkboxobj=document.getElementById("esCheckboxAll"+tableid);
        var elements = document.getElementsByName("esCheckbox"+tableid);

        var ids=[];
        for(var i=0;i<elements.length;i++){
            var d=parseInt(elements[i].value);

            if(ids.indexOf(d)==-1){
                ids.push(d);
                var obj = document.getElementById(elements[i].id);
                obj.checked=checkboxobj.checked;
            }
        }
    }

    function getListOfSelectedRecords(tableid){
        var selectedIds=[];
        var elements = document.getElementsByName("esCheckbox"+tableid);

        for(var i=0;i<elements.length;i++){
            var obj = document.getElementById(elements[i].id);

            if(obj.checked){
                var d=parseInt(elements[i].value);

                if(selectedIds.indexOf(d)==-1)
                    selectedIds.push(d);
            }
        }
        return selectedIds;
    }

    function ctToolBarDO(task,tableid)
    {
        if(es_LinkLoading)
            return;

        es_LinkLoading=true;
        var elements = getListOfSelectedRecords(tableid);

        if (elements.length===0){
            alert("Please select records first.");
            es_LinkLoading=false;
            return;
        }

        if(task=='delete'){
            if (!confirm('Do you want to delete '+elements.length+' records?')){
                es_LinkLoading=false;
                return;
            }
        }

		var toolbarboxid='esToolBar_'+task+'_box_'+tableid;
		document.getElementById(toolbarboxid).style.visibility='hidden';
		
		let element_tableid = "ctTable_" + tableid;
		let tr_object = document.getElementById(element_tableid);
		if(tr_object){
			
			for(let i=0;i<elements.length;i++)
			{
				let recordid = elements[i];
				let url = esPrepareLink(['task','listing_id','returnto','ids'],['task=' + task,'listing_id='+recordid,'clean=1','tmpl=component']);
				let accept_responses = [];
				if(task == 'refresh')
					accept_responses = ['refreshed'];
				else if(task == 'publish' || task == 'unpublish')
					accept_responses = ['published','unpublished'];
				else if(task == 'delete')
					accept_responses = ['published','deleted'];
				
				let last = i == elements.length - 1;
				runTheTask(task, tableid,recordid,url,accept_responses, last);
			}
			
			
		}else{
			var returnto=btoa(window.location.href);
			var link=esPrepareLink(['task','listing_id','returnto','ids'],['task='+task,'ids='+elements.toString(),'returnto='+returnto]);
			window.location.href = link;
		}
        return;
    }

    //https://stackoverflow.com/a/1634841
    function removeURLParameter(url, parameter){
        //prefer to use l.search if you have a location/link object
        var urlparts= url.split('?');
        if (urlparts.length>=2){
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
    
    function ct_UpdateSingleValue(WebsiteRoot,Itemid,fieldname_,record_id,postfix){
        var fieldname=fieldname_.split('_')[0];
        var url=WebsiteRoot+'index.php?option=com_customtables&amp;view=edititem&amp;Itemid='+Itemid;
		var params = "";
		var obj_checkbox_off=document.getElementById("com_"+record_id+"_"+fieldname_+"_off");
		if(obj_checkbox_off){
			//Bit confusing. But this is needed to save Unchecked values
			params="comes_"+fieldname_+"_off="+obj_checkbox_off.value;
			
			if(parseInt(obj_checkbox_off.value)==1)
				params+="&comes_"+fieldname_+"="+document.getElementById("com_"+record_id+"_"+fieldname_).value;
		}
		else{
			let objectName = "comes_"+record_id+"_"+fieldname_;
			params+="&comes_"+fieldname_+"="+document.getElementById(objectName).value;
		}
		
        params+="&task=save";
        params+="&Itemid="+Itemid;
        params+="&listing_id="+record_id;
    
        var obj=document.getElementById("com_"+record_id+"_"+fieldname+postfix+"_div");
		if(obj)
			obj.className = "ct_loader";

        let http = CreateHTTPRequestObject ();   // defined in ajax.js

        if (http){
            http.open("POST", url+"&clean=1", true);
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            http.onreadystatechange = function(){
                if (http.readyState == 4){
                    let res=http.response;
					res = res.replace(/<[^>]*>?/gm, '').trim();

                    if(res.indexOf("saved")!=-1){
                        obj.className = "ct_checkmark ct_checkmark_hidden";//+css_class;
                    }
                    else{
                        obj.className = "ct_checkmark_err ";
                        
                        if(res.indexOf('<div class="alert-message">Nothing to save</div>')!=-1)
                            alert('Nothing to save. Check Edit From layout.');
                        else if(res.indexOf('view-login')!=-1)
                            alert('Session expired. Please login again.');
                    }
                }
            };
            http.send(params);
        }
    }
	
	function ctCatalogUpdate(tableid, recordsId, row_index) {
	
		let element_tableid = "ctTable_" + tableid;

		let url = esPrepareLink(['task','listing_id','returnto','ids','clean','component','frmt'],['listing_id='+recordsId,'number='+row_index]);
		
		let params = "";
        let http = CreateHTTPRequestObject ();   // defined in ajax.js

        if (http)
        {
            http.open("GET", url, true);
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            http.onreadystatechange = function()
            {

                if (http.readyState == 4)
                {
                    let res=http.response;
					
					let rows = document.getElementById(element_tableid).rows;
					rows[row_index].innerHTML = res;
				}
			}
			http.send(params);
		}
	}
	
	function getContainerElementIDTable(obj)
	{
		while(true){
			
			let parts = obj.id.split('_');
			if(parts[0] == 'ctTable'){
				return parts;
			}

			obj = obj.parentElement;
			if(obj == null)
				return null;
		}
		return null;
	}
	
	function ctCatalogOnDrop(event) {
		event.preventDefault();
		const importFromId = event
			.dataTransfer
			.getData('text');
			
		let to_parts = getContainerElementIDTable(event.target);
		if(to_parts == null)
			return false;
		
		let to_id = to_parts.join("_");
			
		if(importFromId == to_id)
				return false;
	
		if (confirm("Do you want to copy field content to target record?") == true) {

			let from_parts = importFromId.split('_');
			
			let from = from_parts[2] + '_' + from_parts[3];
			let to = to_parts[2] + '_' + to_parts[3];
			
			let element_tableid_tr = "ctTable_" +  to_parts[1] + '_' + to_parts[2];
			let index = findRowIndexById("ctTable_" + to_parts[1],element_tableid_tr);

			let url = esPrepareLink(['task','listing_id','returnto','ids','clean','component','frmt'],['task=copycontent','from='+from,'to='+to,'clean=1','tmpl=component','frmt=json']);
			
			fetch(url)
				.then(r => r.json())
				.then(r => {
					if(r.error)
					{
						alert(r.error);
						return false;
					}
					else
						ctCatalogUpdate(to_parts[1],to_parts[2], index);
					
				})
				.catch(error => console.error("Error", error));
			
			return true;
		} else {
			return false;
		}
	}
		
	function ctCatalogOnDragStart(event) {
		event
			.dataTransfer
			.setData('text/plain', event.target.id);
	}

	function ctCatalogOnDragOver(event) {
		event.preventDefault();
	}

	function ctEditModal(url){
		
		let new_url = url + '&modal=1';
		let params = "";
        let http = CreateHTTPRequestObject ();   // defined in ajax.js

        if (http)
        {
            http.open("GET", new_url, true);
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            http.onreadystatechange = function()
            {
                if (http.readyState == 4)
                {
                    let res=http.response;
					
					//let content_html = '<div style="overflow-y: scroll;overflow-x: hidden;height: 100%;width:100%;">' + res + '</div>';
					let content_html = res;
					ctShowPopUp(content_html,true);
				}
			}
			http.send(params);
		}
	}
