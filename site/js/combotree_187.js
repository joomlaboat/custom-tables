function comboSERefreshMe(baseurl,me, mename, objectname, aLink,establename,esfieldname,optionname,innerjoin,cssstyle,parentname,where,langpostfix,onchange,prefix,isrequired,requirementdepth)
    {
       var al=document.getElementById(objectname);
		var a2=document.getElementById(mename);
        
		var mevalue=a2.value;
        
        var urlquery="";
        if(aLink!="")urlquery+=aLink+"&";
        
        urlquery+=mename+"="+mevalue;
        urlquery+="&establename="+establename;
        urlquery+="&esfieldname="+esfieldname;
        urlquery+="&optionname="+optionname;
		urlquery+="&innerjoin="+innerjoin;
		urlquery+="&cssstyle="+cssstyle;
		urlquery+="&where="+where;
		urlquery+="&onchange="+onchange;
		urlquery+="&langpostfix="+langpostfix;
		urlquery+="&prefix="+prefix;
		urlquery+="&isrequired="+isrequired;
		urlquery+="&requirementdepth="+requirementdepth;
		
		
        //Load Data
        var q=baseurl+"/components/com_customtables/libraries/combotreeloader.php?objectname="+objectname+"&"+urlquery;
        //q must be a full link (http://)
		
        if (window.XMLHttpRequest)
        {
            // code for IE7+, Firefox, Chrome, Opera, Safari
             objXml =new XMLHttpRequest();
        }
        else if (window.ActiveXObject)
        {
            // code for IE6, IE5
            objXml =new ActiveXObject("Microsoft.XMLHTTP");
        }
	
		//objXml = new ActiveXObject("Microsoft.XMLHTTP");
		objXml.open("GET", q, true);
		objXml.onreadystatechange=function()
		{
			if (objXml.readyState==4) {
				
				var rsp=objXml.responseText;
                
                al.innerHTML=rsp;
				
				if(onchange!='')
				{
					
				
					onchange=unescape(onchange);
					if(mevalue=='')
					{
						
						if(parentname.indexOf('.')==-1)
						{
							onchange=onchange.replace("me.value",'""');
						}
						else
							onchange=onchange.replace("me.value","'"+parentname+"'");
					}
					else
						onchange=onchange.replace("me.value","'"+parentname+"."+mevalue+"'");	
					
					
					
					eval(onchange);
				}
	  		}
			//else
				//al.innerHTML="";
		}
		objXml.send(null);
			
        
    }
