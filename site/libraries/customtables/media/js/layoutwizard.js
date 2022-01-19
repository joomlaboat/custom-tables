/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/js/layoutwizard.js
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
var tableselector_id="";
var field_box_id="";
var tableselector_obj=null;

var current_table_id=0;
var wizardFields=[];
var wizardLayouts=[];

var joomlaVersion = 3;

var languages=[];

function loadLayout(version)
{
	joomlaVersion = version;
	var obj=document.getElementById("allLayoutRaw");
	if(obj)
		wizardLayouts=JSON.parse(obj.innerHTML);
}

function openLayoutWizard()
{
	FillLayout();
}

function loadFields(tableselector_id_,field_box_id_)
{
	tableselector_id=tableselector_id_;
	field_box_id=field_box_id_;
	tableselector_obj=document.getElementById(tableselector_id);
	loadFieldsUpdate();
}

function loadFieldsUpdate()
{
	
	var tableid=tableselector_obj.value;
	if(tableid!==current_table_id)
	{
		
		loadFieldsData(tableid);
	}
}

function loadFieldsData(tableid)
{
	current_table_id=0;
	tableid=parseInt(tableid);
	if(isNaN(tableid) || tableid===0)
		return;//table not selected

	var url=websiteroot+"index.php?option=com_customtables&view=api&frmt=json&task=getfields&tableid="+tableid;
	
	if (typeof fetch === "function")
	{
		fetch(url, {method: 'GET',mode: 'no-cors',credentials: 'same-origin' }).then(function(response)
		{
			if(response.ok)
			{
				response.json().then(function(json)
				{
					wizardFields=Array.from(json);
					current_table_id=tableid;
					updateFieldsBox();
				});
			}
			else
			{
				console.log('Network request for products.json failed with response ' + response.status + ': ' + response.statusText);
			}
		}).catch(function(err)
		{
			console.log('Fetch Error :-S', err);
		});
	}
	else
	{
		//for IE
		var http = null;
		var params = "";

		if (!http)
		{
		    http = CreateHTTPRequestObject ();   // defined in ajax.js
		}

		if (http)
		{
		    http.open("GET", url, true);
		    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		    http.onreadystatechange = function()
		    {

			    if (http.readyState == 4)
			    {
			        var res=http.response;
					wizardFields=JSON.parse(res);
					current_table_id=tableid;
					updateFieldsBox();
				}
			};
			http.send(params);
		}
	}
}

function updateFieldsBox()
{
	//var result=renderFieldsBox();
	//result+='<p>Position cursor to the code editor where you want to insert a new dynamic tag and click on the Tag Button.</p>';
	
	//field_box_obj.innerHTML='';//<div class="dynamic_values">'+result+'</div>';
	
}

function renderTabs(tabset_id, tabs)
{
	// Tabs is the the array of tab elements [{"title":"Tab Title","id":"Tab Name","content":"Tab Content"}...]
	
	if(joomlaVersion < 4)
	{
		let result_li='';
		let result_div='';
		//let activeTabSet=true;
	
		for(let i=0;i<tabs.length;i++)
		{
			let tab = tabs[i];
		
			let cssclass="";
			if(i==0)
				cssclass="active";
			
			result_li+='<li' + (cssclass != '' ? ' class="' + cssclass + '"' : '') + '><a href="#' + tab.id + '" onclick="resizeModalBox();" data-toggle="tab">' + tab.title + '</a></li>';
			result_div+='<div id="' + tab.id + '" class="tab-pane' + (i==0 != '' ? ' active' : '') + '">'+tab.content+'</div>';
		}
	
		return '<ul class="nav nav-tabs" >'+result_li+'</ul>\n\n<div class="tab-content" id="' + tabset_id + '">'+result_div+'</div>';
	}
	else
	{
		let result_li='';
		let result_div='';

		for(let i=0;i<tabs.length;i++)
		{
			let tab = tabs[i];
			
			let cssclass="";
			if(i==0)
				cssclass="active";
				
			//result_li = result_li + '<button aria-controls="' + tab.id + '" role="tab" type="button" ' + (i==0 ? ' aria-expanded="true"' : '')+ '>'+tab.title+'</button>';
				
			result_div += '<joomla-tab-element' + (i==0 ? ' active' : '') +' id="' + tab.id + '" name="'+tab.title+'">'+tab.content+'</joomla-tab-element>';
		}
		
		let result_div_li='<div role="tablist">'+result_li+'</div>';
		//result_div_li +
		return '<joomla-tab id="' + tabset_id + '" orientation="horizontal" recall="" breakpoint="768">' + result_div + '</joomla-tab>';
	}
}

function renderFieldsBox()
{
	//1 - Simple Catalog
	//2 - Edit Form
	//3 - Record Link
	//4 - Details
	//5 - Catalog Page
	//6 - Catalog Item
	//7 - Email Message
	//8 - XML File
	//9 - CSV File
	//10 - JSON - File

	let tabs = [];

	current_table_id=parseInt(current_table_id);
	if(isNaN(current_table_id) || current_table_id===0)
	{
		//field_box_obj.innerHTML='<p>Table not selected. Select Table.</p>';
		return;
	}

	var l=wizardFields.length;
	if(l===0)
	{
		//field_box_obj.innerHTML='<div class="FieldTagWizard"><p>There are no Fields in selected table.</p></div>';
		return;
	}

	var result='';
	

	var a=[1,3,4,6,7,8,9,10];//Layout Types that may have Field Values.
	
	var fieldtypes_to_skip=['log','phponview','phponchange','phponadd','md5','id','server','userid','viewcount','lastviewtime','changetime','creationtime','imagegallery','filebox','dummy'];

	if(a.indexOf(current_layout_type)!==-1)
	{
		tabs.push({'id':'layouteditor_fields_value','title':'Field Values',
			'content':'<p>Dynamic Field Tags that produce Field Values:</p>'+renderFieldTags('[',']',['dummy'],'valueparams')
		});
	}

	//Any Layout Type
	tabs.push({'id':'layouteditor_fields_titles','title':'Field Titles',
			'content':'<p>Dynamic Field Tags that produce Field Titles (Language dependable):</p>' + renderFieldTags('*','*',[],'titleparams')
	});

	if(a.indexOf(current_layout_type)!==-1)
	{
		tabs.push({'id':'layouteditor_fields_purevalue','title':'Field Pure Values',
			'content':'<p>Dynamic Field Tags that returns pure Field Values (as it stored in database):</p>' + renderFieldTags('[_value:',']',['string','md5','changetime','creationtime','lastviewtime','viewcount','id','phponadd','phponchange','phponview','server','multilangstring','text','multilangtext','int','float','email','date','filelink','creationtime','dummy'],'')
		});
		
		tabs.push({'id':'layouteditor_fields_ajaxedit','title':'Input Edit (Update on change)',
			'content':'<p>Renders input/select box for selected field. It works in all types of layout except Edit Form:</p>' + renderFieldTags('[_edit:',']',fieldtypes_to_skip,'')
		});
	}
	
	
	if(current_layout_type===2)
	{
		let fieldtypes_to_skip=['log','phponview','phponchange','phponadd','md5','id','server','userid','viewcount','lastviewtime','changetime','creationtime','imagegallery','filebox','dummy'];
		
		let label = '<p>Dynamic Field Tags that renders an input field where the user can enter data.<span style="font-weight:bold;color:darkgreen;">(more <a href="https://joomlaboat.com/custom-tables-wiki?document=04.-Field-Types" target="_blank">here</a>)</span></p>';
		tabs.push({'id':'layouteditor_fields_edit','title':'Input/Edit',
			'content':label + renderFieldTags('[',']',fieldtypes_to_skip,'editparams')
		});


		tabs.push({'id':'layouteditor_fields_valueineditform','title':'Fields Values inside Edit form',
			'content':'<p>Dynamic Field Tags that produce Field Values (if the record is alredy created ID!=0):</p>' + renderFieldTags('|','|',['dummy'],'valueparams')
		});
	}
	
	if(tabs.length >0)
		return renderTabs('layouteditor_fields', tabs);
	else
		return '<div class="FieldTagWizard"><p>No Field Tags available for this Layout Type</p></div>';
}

function findFieldObjectByName(fieldname)
{
	var l=wizardFields.length;
	for (var index=0;index<l;index++)
	{
		var field=wizardFields[index];

		if(field.fieldname===fieldname)
			return field;

	}

	return null;
}

function renderFieldTags(startchar,endchar,fieldtypes_to_skip,param_group)
{
	var result='';

	var l=wizardFields.length;

	for (var index=0;index<l;index++)
	{
		var field=wizardFields[index];

		if(fieldtypes_to_skip.indexOf(field.type)===-1)
		{
	        var t=field.fieldname;
			var p=0;
			var alt=field.fieldtitle;

			var button_value="";
			var typeparams=findTheType(field.type);
			if(typeparams!=null)
			{

				var type_att=typeparams["@attributes"];
				alt+=' ('+type_att.label+')';

				if(param_group!='')
				{


					var param_group_object=typeparams[param_group];
					if (typeof(param_group_object) != "undefined")
					{
						var params=getParamOptions(param_group_object.params,'param');
						p=params.length;

						if(p>0)
							t=field.fieldname+':<span>Params</span>';
					}
				}

				button_value=startchar+t+endchar;
			}
			else
			{
				alt+=' (UNKNOW FIELD TYPE)';

				button_value='<span class="text_error">'+startchar+t+endchar+'</span>';
			}

	        result+='<div style="vertical-align:top;display:inline-block;">';
			result+='<div style="display:inline-block;">';
			
			if(joomlaVersion < 4)
				result+='<a href=\'javascript:addFieldTag("0","'+startchar+'","'+endchar+'","'+btoa(field.fieldname)+'",'+p+');\' class="btn" alt="'+alt+'" title="'+alt+'">'+button_value+'</a>';
			else
				result+='<a href=\'javascript:addFieldTag("0","'+startchar+'","'+endchar+'","'+btoa(field.fieldname)+'",'+p+');\' class="btn-primary" alt="'+alt+'" title="'+alt+'">'+button_value+'</a>';
			
			
		    result+='</div>';
	                //result+='<div style="display:inline-block;">'+tag.description+'</div>';
	        result+='</div>';
		}

	}

	return result;
}

function getParamGroup(tagstartchar,tagendchar)
{
	var param_group='';


	var a=[1,3,4,6,7,8,9,10];

	if(current_layout_type!==5 && tagstartchar==='*' && tagendchar==='*')
		param_group='titleparams';
	else if(a.indexOf(current_layout_type)!==-1 && tagstartchar==='[' && tagendchar===']')
		param_group='valueparams';
	else if(current_layout_type===2)
		param_group='editparams';

	return param_group;
}

function showModalTagsList(e)
{
	var result=do_render_current_TagSets();

	var modalcontentobj=document.getElementById("layouteditor_modal_content_box");	
	modalcontentobj.innerHTML=result;
	showModal();
	return;
}

function showModalDependenciesList(e)
{
	var result=document.getElementById("dependencies_content").innerHTML;
	
	modalcontentobj=document.getElementById("layouteditor_modal_content_box").innerHTML=result;
	showModal();
	return;
}


function showModalFieldTagsList(e)
{
	var result=renderFieldsBox();

	result='<div class="dynamic_values">'+result+'</div>';
	
	var modalcontentobj=document.getElementById("layouteditor_modal_content_box");	
	modalcontentobj.innerHTML=result;
	showModal();
	return;
}


function showModalFieldTagForm(tagstartchar,tagendchar,tag,top,left,line,positions,isnew)
{
	var modalcontentobj=document.getElementById("layouteditor_modal_content_box");

    var tag_pair=parseQuote(tag,':',false);

    temp_params_tag=tag_pair[0];
	var field=findFieldObjectByName(temp_params_tag);
	if(field==null)
	{
		modalcontentobj.innerHTML='<p>Cannot find the field. Probably the field does not belong to selected table.</p>';
		showModal();
		return;
	}

	var param_group=getParamGroup(tagstartchar,tagendchar);
	
	if(param_group==='')
	{
		modalcontentobj.innerHTML='<p>Something went wrong. Field Type Tag should not have any parameters in this Layout Type. Try to reload the page.</p>';
		showModal();
		return;
	}

	var fieldtypeobj=findTheType(field.type);
	if(fieldtypeobj===null)
	{
		modalcontentobj.innerHTML='<p>Something went wrong. Field Type Tag doesnot not have any parameters. Try to reload the page.</p>';
		showModal();
		return;
	}
	var fieldtype_att=fieldtypeobj["@attributes"];

	var group_params_object=fieldtypeobj[param_group];
	
	if(!group_params_object || !group_params_object.params)
	{
		modalcontentobj.innerHTML='<p>Field Type Tag doesn\'t have parameters.</p>';
		showModal();
		return;
	}

	var param_array=getParamOptions(group_params_object.params,'param');

    var countparams=param_array.length;

    var paramvaluestring="";
    if(tag_pair.length==2)
        paramvaluestring=tag_pair[1];

    var form_content=getParamEditForm(group_params_object,line,positions,isnew,countparams,tagstartchar,tagendchar,paramvaluestring);

    if(form_content==null)
        return false;

	var result='<h3>Field "<b>'+field.fieldtitle+'</b>"  <span style="font-size:smaller;">(<i>Type: '+fieldtype_att.label+'</i>)</span>';

    if (typeof(fieldtype_att.helplink) !== "undefined")
		result+=' <a href="'+fieldtype_att.helplink+'" target="_blank">Read more</a>';

	result+='</h3>';



    modalcontentobj.innerHTML=result+form_content;

    jQuery(function($)
    {
        $(modalcontentobj).find(".hasPopover").popover({"html": true,"trigger": "hover focus","layouteditor_modal_content_box": "body"});
    });

    updateParamString("fieldtype_param_",1,countparams,"current_tagparameter",null,false);

    showModal();
 }

function addFieldTag(index_unused,tagstartchar,tagendchar,tag,param_count)
{
    var index=0;
	var cm=codemirror_editors[index];
    
	if(param_count>0)
    {
        var cr=cm.getCursor();

        var positions=[cr.ch,cr.ch];
        var mousepos=cm.cursorCoords(cr,"window");

        showModalFieldTagForm(tagstartchar,tagendchar,atob(tag),mousepos.top,mousepos.left,cr.line,positions,1);
    }
    else
	{
        updateCodeMirror(tagstartchar+atob(tag)+tagendchar);
		
		//in case modal window is open
		var modal = document.getElementById('layouteditor_Modal');
        modal.style.display = "none";
		
		cm.focus();
	}
}
			function FillLayout()
			{
				var editor = codemirror_editors[codemirror_active_index];
				var t = parseInt(document.getElementById("jform_layouttype").value);
				if(isNaN(t) || t===0)
				{
					alert("Type not selected.");
					return;
				}

				var tableid = parseInt(document.getElementById("jform_tableid").value);
				if(isNaN(tableid) || tableid===0)
				{
					alert("Table not selected.");
					return;
				}

				var layout_obj = document.getElementById(codemirror_active_areatext_id);
				layout_obj.value=editor.getValue();

				var v=layout_obj.value;
				if(v!=='')
				{
					alert("Layout Content is not empty, delete it first.");
					return;
				}

				switch(t)
				{
					case 1:
						layout_obj.value=getLayout_SimpleCatalog();
					break;

					case 2:
						layout_obj.value=getLayout_Edit();
					break;

					case 3:
						layout_obj.value=getLayout_Record();
					break;

					case 4:
						layout_obj.value=getLayout_Details();
					break;

					case 5:
						layout_obj.value=getLayout_Page();
					break;

					case 6:
						layout_obj.value=getLayout_Item();
					break;

					case 7:
						layout_obj.value=getLayout_Email();
					break;

					case 8:
						layout_obj.value=getLayout_XML();
					break;

					case 9:
						layout_obj.value=getLayout_CSV();
					break;

					case 10:
						layout_obj.value=getLayout_JSON();
					break;


				}

				//'<!-- Automatacally created layout -->\r\n'+
				editor.getDoc().setValue(layout_obj.value);

			}


			function getLayout_Page()
			{
				var result="";
				var l=wizardFields.length;

				result+='<style>\r\n';
				result+='.datagrid th{text-align:left;}\r\n';
				result+='.datagrid td{text-align:left;}\r\n';
				result+='</style>\r\n';
				
				result+='<legend>{table:title}</legend>\r\n';
				
				
				result+='<div style="float:right;">{recordcount}</div>\r\n';
				result+='<div style="float:left;">{add}</div>\r\n';
				result+='\r\n';
				result+='<div style="text-align:center;">{print}</div>\r\n';
				result+='<div class="datagrid">\r\n';
				result+='<div>{batchtoolbar:edit,publish,unpublish,refresh,delete}</div>\r\n\r\n';

				result+='<table><thead><tr>';

				var fieldtypes_to_skip=['log','imagegallery','filebox','dummy'];
				var fieldtypes_withsearch=['email','string','multilangstring','text','multilangtext','int','float','sqljoin','records'];

				result+='<th>{checkbox}</th>\r\n';
				result+='<th>#</th>\r\n';

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{
						if(fieldtypes_withsearch.indexOf(field.type)===-1)
							result+='<th>*'+field.fieldname+'*</th>\r\n';
						else
							result+='<th>*'+field.fieldname+'*<br/>{search:'+field.fieldname+'}</th>\r\n';
					}
				}

				result+='<th>Action<br/>{search:button}</th>\r\n';

				result+='</tr></thead>\r\n\r\n';
				result+='<tbody>\r\n\r\n';

				result+='{catalog:,notable}\r\n\r\n';

				result+='</tbody>\r\n';
				result+='</table>\r\n';

				result+='</div>\r\n\r\n';
				result+='<br/><div style=\'text-align:center;\'>{pagination}</div>\r\n';

				return result;
			}


			function getLayout_Item()
			{
				var result="";
				var l=wizardFields.length;

				var fieldtypes_to_skip=['log','imagegallery','filebox','dummy'];
				var user_fieldtypes=['user','userid'];
				
				result+='<td>{toolbar:checkbox}</td>\r\n';
				result+='<td>{id}</td>\r\n';
				
				let user_field = '';

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
						result+='<td>['+field.fieldname+']</td>\r\n';
						
					if(user_fieldtypes.indexOf(field.type)!==-1)
						user_field = field.fieldname;
				}
				
				if(user_field=='')
					result+='<td>{toolbar:edit,publish,refresh,delete}</td>\r\n';
				else
				{
					result+='<td>\r\n';
					result+='\t<!-- The "if" statement is to show the toolbar to the record author only. -->\r\n';
					result+='\t{if:"[_value:' + user_field + ']"="{currentuserid}"} <!-- Where "' + user_field + '" is the user type field name. -->\r\n';
					result+='\t\t<!-- toolbar -->\r\n';
					result+='\t\t{toolbar:edit,publish,refresh,delete}\r\n';
					result+='\t\t<!-- end of toolbar -->\r\n';
					result+='\t{endif}\r\n';
					result+='</td>\r\n';
				}


				return '<tr>\r\n' + result + '</tr>\r\n';
			}

			function getLayout_SimpleCatalog()
			{
				var result="";
				var l=wizardFields.length;

				result+='<style>\r\n.datagrid th{text-align:left;}\r\n.datagrid td{text-align:left;}\r\n</style>\r\n';
				result+='<div style="float:right;">{recordcount}</div>\r\n';
				result+='<div style="float:left;">{add}</div>\r\n';
				result+='\r\n';
				result+='<div style="text-align:center;">{print}</div>\r\n';
				result+='<div class="datagrid">\r\n';
				result+='<div>{batchtoolbar:publish,unpublish,refresh,delete}</div>';
				result+='\r\n\r\n{catalogtable:\r\n';
				result+='"#<br/>{checkbox}":"{id}<br/>{toolbar:checkbox}",\r\n';

				var fieldtypes_to_skip=['log','imagegallery','filebox','dummy'];
				var fieldtypes_withsearch=['email','string','multilangstring','text','multilangtext','int','float','sqljoin','records'];

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{
						if(fieldtypes_withsearch.indexOf(field.type)===-1)
							result+='"*'+field.fieldname+'*":"['+field.fieldname+']",\r\n';
						else
							result+='"*'+field.fieldname+'*<br/>{search:'+field.fieldname+'}":"['+field.fieldname+']",\r\n';
					}
				}

				result+='"Action<br/>{search:button}":"{toolbar:edit,publish,refresh,delete}";\r\n';
				result+='css_class_name\r\n';
				result+='}\r\n';
				result+='</div>\r\n';
				result+='<br/><div style=\'text-align:center;\'>{pagination}</div>\r\n';

				return result;
			}

			function getLayout_Edit()
			{
				var result="";

				var l=wizardFields.length;

				result+='<legend>{table:title}</legend>\r\n\r\n<div class="form-horizontal">\r\n\r\n';

				var fieldtypes_to_skip=['log','phponview','phponchange','phponadd','md5','id','server','userid','viewcount','lastviewtime','changetime','creationtime','imagegallery','filebox','dummy'];

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{
						result+='\t<div class="control-group">\r\n';
						result+='\t\t<div class="control-label">*'+field.fieldname+'*</div><div class="controls">['+field.fieldname+']</div>\r\n';
						result+='\t</div>\r\n\r\n';
					}
				}

				result+='</div>\r\n';

				result+='\r\n';

				for (var index2=0;index2<l;index2++)
				{
					var field2=wizardFields[index2];

					if(field2.fieldtyue==="dummy")
					{
						result+='<p><span style="color: #FB1E3D; ">*</span> *'+field2.fieldname+'*</p>\r\n';
						break;
					}
				}


				result+='<div style="text-align:center;">{button:save}{button:saveandclose}{button:saveascopy}{button:cancel}</div>\r\n';

				return result;
			}

			function getLayout_Details()
			{
				var result="";

				var l=wizardFields.length;

				result+='{gobackbutton}\r\n\r\n<div class="form-horizontal">\r\n\r\n';

				var fieldtypes_to_skip=['log','imagegallery','filebox','dummy'];

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{
						result+='\t<div class="control-group">\r\n';
						result+='\t\t<div class="control-label">*'+field.fieldname+'*</div><div class="controls">['+field.fieldname+']</div>\r\n';
						result+='\t</div>\r\n\r\n';
					}
				}

				result+='</div>\r\n';

				result+='\r\n';

				return result;
			}

			function getLayout_Email()
			{
				var result="";

				var l=wizardFields.length;

				result+='<p>New form entry registered:</p>\r\n\r\n';

				var fieldtypes_to_skip=['log','imagegallery','filebox','dummy'];

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{

						result+='\t\t<p>*'+field.fieldname+'*: ['+field.fieldname+']</p>\r\n';

					}
				}


				return result;
			}

			function getLayout_CSV()
			{
								var result="";
				var l=wizardFields.length;

				result+='{catalogtable:\r\n';
				result+='"#":"{id}",\r\n';

				var fieldtypes_to_skip=['log','imagegallery','filebox','dummy'];

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{
						result+='"*'+field.fieldname+'*":"['+field.fieldname+']"';

						if(index<l-1)
							result+=',\r\n';
						else
							result+=';';
					}
				}

				result+='}\r\n';

				return result;
			}

			function getLayout_JSON()
			{
								var result="";
				var l=wizardFields.length;

				result+='{catalogtable:\r\n';
				result+='"id_":"{id}",\r\n';

				var fieldtypes_to_skip=['log','imagegallery','filebox','dummy'];

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{
						result+='"'+field.fieldname+'":"['+field.fieldname+']"';

						if(index<l-1)
							result+=',\r\n';
						else
							result+=';';
					}
				}

				result+='}\r\n';

				return result;
			}

			function getLayout_XML()
			{
				var result="";
				var l=wizardFields.length;

				result+='<?xml version="1.0" encoding="utf-8"?>\r\n<document>\r\n{catalogtable:\r\n';

				var fieldtypes_to_skip=['log','imagegallery','filebox','dummy'];

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{
						var v='\t<field name=\''+field.fieldname+'\' label=\'*'+field.fieldname+'*\'>['+field.fieldname+']</field>\r\n';
						if(index==0)
							result+='"":"<record id=\'{id}\'>\r\n'+v+'"';
						else if(index==l-1)
							result+='"":"'+v+'</record>\r\n"';
						else
							result+='"":"'+v+'"';

						if(index<l-1)
							result+=',';
						else
							result+=';';
					}
				}
				result+='}\r\n</document>';
				return result;
			}

			function getLayout_Record()
			{
				var result="";

				var l=wizardFields.length;

				var fieldtypes_to_skip=['log','dummy'];
				var fieldtypes_to_purevalue=['image','imagegallery','filebox','file'];

				for (var index=0;index<l;index++)
				{
					var field=wizardFields[index];

					if(fieldtypes_to_skip.indexOf(field.type)===-1)
					{
						if(fieldtypes_to_purevalue.indexOf(field.type)===-1)
							result+='\t<div>['+field.fieldname+']</div>\r\n';
						else
							result+='\t<div>[_value:'+field.fieldname+']</div>\r\n';
					}
				}

				return result;
			}
