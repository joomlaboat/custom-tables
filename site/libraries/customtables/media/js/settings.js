
function updateScreenshots()
{
    var obj=document.getElementById("imageLoadProgress");
    var url="/administrator/index.php?option=com_templateshop&view=settings";
        
    
    var http = null;
    var params = "";
    params+="&task=settings.RefreshTemplates";
    params+="&clean=1";
    
    
    if (!http)
    {
        http = CreateHTTPRequestObject ();   // defined in ajax.js
    }
    
    if (http)
    {
     //   alert(url);
        http.open("POST", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function()
        {
            
            if (http.readyState == 4)
            {
                var res=http.response;
                
                if(!isJSONValid(res))
                {
                    alert(res);
						return false;
                }	

                var list = JSON && JSON.parse(res) || $.parseJSON(res);
                
                var p=0;
                if(list.completed!=0)
					p=Math.floor(100/(list.total/list.completed));
                    
                //alert(list.total+","+list.completed)
                
                document.getElementById("imageLoadProgress_completed").innerHTML=list.completed;
                document.getElementById("imageLoadProgress_total").innerHTML=list.total;
						
				obj.innerHTML=p+"%";
                obj.style.width=p+"%";
	        
                if(list.total>list.completed)
                {
                    setTimeout(function(){updateScreenshots();} , 100);
                }
            }
        };
        http.send(params);
    }
    else
    {
        obj.innerHTML="<span style='color:red;'>Cannot Save</span>";
    }
}

function updateCategories()
{
    var obj=document.getElementById("refreshcategoriesBox");
    var url="/administrator/index.php?option=com_templateshop&view=settings";

    obj.innerHTML='<p style="color:black;"><i>Updating...</i></p>';        
    
    var http = null;
    var params = "";
    params+="&task=settings.RefreshCategories";
    params+="&clean=1";
    
    
    if (!http)
    {
        http = CreateHTTPRequestObject ();   // defined in ajax.js
    }
    
    if (http)
    {
     //   alert(url);
        http.open("POST", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function()
        {
            
            if (http.readyState == 4)
            {
                var res=http.response;
                
                if(!isJSONValid(res))
                {
                    alert(res);
						return false;
                }	

                var list = JSON && JSON.parse(res) || $.parseJSON(res);
                
                if(list.status!='error')
    				obj.innerHTML='<i>'+list.status+'</i>';
                else
                {
                    obj.innerHTML='<p style="color:red;">Error: <i>'+list.msg+'</i></p>';
                    
                    if(list.msg=='Unauthorized usage')
                    {
                        var obj2=document.getElementById("templateUpdateBox");
                        obj2.innerHTML='<p style="color:red;font-weight:bold;">Please check your Template Monster Affiliate Login and API Password.</p>';
                    }
                }
            }
        };
        http.send(params);
    }
    else
    {
        obj.innerHTML="<span style='color:red;'>Cannot Refresh</span>";
    }
    
}


    function isJSONValid(text)
    {
        if (/^[\],:{}\s]*$/.test(text.replace(/\\["\\\/bfnrtu]/g, '@').
            replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
            replace(/(?:^|:|,)(?:\s*\[)+/g, '')))
        {
            //the json is ok
            return true;
        }
        else
            return false;
    }
