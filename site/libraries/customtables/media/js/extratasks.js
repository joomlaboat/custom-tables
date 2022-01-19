/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/js/extratasks.js
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

var ct_updateimages_count=0;
var ct_updateimages_startindex=0;
var ct_updateimages_stepsize=10;

function ctExtraUpdateImages(old_params,new_params,fieldid,tabletitle,fieldtitle)
{
	let result='<h3>Processing image files...</h3>';
	
	//Delete non ASCII characters, just in case.
	let op = Base64.decode(old_params).replace(/[^ -~]+/g, "");
	let np = Base64.decode(new_params).replace(/[^ -~]+/g, "");
	
	result+='<p><b>Table:</b> '+tabletitle+'<br/><b>Field:</b> '+fieldtitle+'</p>';
	result+='<table><tbody><tr><td><b>Old Parameters:</b></td><td>'+op+'</td></tr><tr><td><b>New Parameters:</b></td><td>'+np+'</td></tr></tbody></table>';
	result+='<div id="ctStatus"></div><br/>';
		
	result+='<div class="progress progress-striped active"><div id="ct_progressbar" class="bar" role="progressbar" style="width: 0%;"></div></div><br/><p>Please keep this window open.</p>';
	
	ctShowPopUp(result,false);
	ctQueryAPI(old_params,new_params,fieldid);
}

function ctQueryAPI(old_params,new_params,fieldid)
{
	let parts=location.href.split("/administrator/");
	let websiteroot=parts[0]+"/administrator/";
	
	let url=websiteroot+"index.php?option=com_customtables&view=api&frmt=json&task=updateimages&old_typeparams="+old_params+"&new_typeparams="+new_params+"&fieldid="+fieldid+"&startindex="+ct_updateimages_startindex+"&stepsize="+ct_updateimages_stepsize;
	
	if (typeof fetch === "function")
	{
		fetch(url, {method: 'GET',mode: 'no-cors',credentials: 'same-origin' }).then(function(response)
		{
			if(response.ok)
			{
				response.json().then(function(json)
				{
					if(json.success==1)
					{
						if(ct_updateimages_count==0)
						{
							ct_updateimages_count=json.count;
							if(ct_updateimages_count==0)
							{
								document.getElementById("ctStatus").innerHTML="No images found.";
								setTimeout(function(){ ctHidePopUp(); }, 500);
								return;
							}
						}
						
						if(json.stepsize<ct_updateimages_stepsize)
							ct_updateimages_stepsize=json.stepsize;
						
						document.getElementById("ctStatus").innerHTML="File"+(ct_updateimages_count==1 ? "" : "s")+": "+(ct_updateimages_startindex+ct_updateimages_stepsize)+" of "+ct_updateimages_count;
						
						let bar=document.getElementById("ct_progressbar");
						let p=Math.floor(100*(ct_updateimages_startindex+ct_updateimages_stepsize)/ct_updateimages_count);
						
						bar.style="width: "+p+"%;";
						
						if(ct_updateimages_startindex==ct_updateimages_count)
						{
							setTimeout(function(){ 
							
								document.getElementById("ctStatus").innerHTML="Completed.";
								ctHidePopUp(); 
							
							}, 500);
							
							return;
						}
						
						ct_updateimages_startindex+=ct_updateimages_stepsize;
							
						if(ct_updateimages_startindex+ct_updateimages_stepsize>ct_updateimages_count)
							ct_updateimages_stepsize=ct_updateimages_count-ct_updateimages_startindex;
						
						setTimeout(function(){ ctQueryAPI(old_params,new_params,fieldid); }, 500);
					}
					else					
					{
						document.getElementById("ctStatus").innerHTML="ERROR: "+JSON.stringify(json);
					}
				});
			}
			else
			{
				console.log('Network request for products.json failed with response ' + response.status + ': ' + response.statusText);
			}
		}).catch(function(err)
		{
			console.log('Fetch Error :', err);
		});
	}
}
