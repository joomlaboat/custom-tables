{headtag:"<script src='/components/com_oxfordsms/js/ajax.js' type='text/javascript'></script>"}
{headtag:"<script src='/components/com_tosstudyguides/js/studyguidesedit_134.js' type='text/javascript'></script>"}
{headtag:"<link rel='stylesheet' href='/components/com_tosstudyguides/css/studyguides_134.css' type='text/css' />"}

   <h2>Edit My Study Guide</h2>



{customtablescatalog=teachers,iteacherpage,iteacheritem,showpublished,userid=={currentuserid},1}


    <script>
    //Get my Streaming Groups
    {customtablescatalog=streaminggroups,streamingclassidpage,streamingclassiditem,showpublished,user=={currentuserid},1}
    </script>
    
{tab:General} 
    
<div class="form-horizontal">
  
    <div class="control-group"><div class="control-label">*title*</div><div class="controls OxfordSMSLongInput">[title]</div></div>
  
  <div class="control-group"><div class="control-label">*type*</div><div class="controls">[type]</div></div>
  
<div class="control-group" style="color:black;border:1px solid #27b227;background-color:#95f895;border-right:none;">
			<div class="control-label">*preparationtime*</div>
<div class="controls">[preparationtime:,,"H:i"] This represents how much time (hh:mm) the student will have to spend <b>at home</b> to be prepared for this assessment.</div>
	</div>
  
<div class="control-group">
			<div class="control-label" id="streaminggroupbox1">*streaminggroup*</div>
<div class="controls" id="streaminggroupbox3">[streaminggroup] <i>*optional*</i></div>
<div id="streaminggroupbox2"></div>
	</div>
  
</div>


    <div style="display:block;background-color:#eeeeee;padding:3px;border:1px solid #dddddd;margin-bottom:5px;" id="GradeAndSubjectBox" >
      <div class="form-horizontal">
        
        <div class="control-group">
			<div class="control-label">*grade* / *subject*</div><div class="controls">[subject]</div>
	</div>
        
        <div class="control-group">
			<div class="control-label">*classes*</div>
<div class="controls">[classes] <div style="margin-left:5px;"><i>*holdcontroltoselectmultipleclasses*<br/><span style="color:red;">*notrecommended*</span></i></div></div>
	</div>
       
      </div>
      
      <div class="form-horizontal">
        	  
  
  	<div class="control-group">
			<div class="control-label">*date*</div><div class="controls OxfordSMSDateButton">[date]<div id="weeklybox" style="display:none;">*weekly*: [weekly]</div></div>
	</div>
  
  	<div class="control-group">
			<div class="control-label">*assesment*</div><div class="controls">[assesment]  If you change assessment to Summative then it will be moved to Grading</div>
	</div>
  
</div>

    

    
    <p><br/></p>
    <p><span style="color: #FB1E3D; ">*</span> *labelrequiredfields*</p>
    

{tab:Content} 
[text]
{tab:Youtube} 
<div class="form-horizontal">
  	<div class="control-group">
			<div class="control-label">*youtubevideolink*</div><div class="controls OxfordSMSLongInput">[youtubevideolink]</div>
	</div>
</div>

{tab:Zoom} 
<div class="form-horizontal">
  <div class="control-group"><div class="control-label">*zoomlink*</div><div class="controls OxfordSMSLongInput">[zoomlink]</div>	</div>
  	<div class="control-group">			<div class="control-label">*zoompassword*</div><div class="controls OxfordSMSLongInput">[zoompassword]</div>	</div>
</div>


{tab:Attach File} 
[file]
{/tabs}

<div style="display:none;">[grade]</div>
<div style="display:none;" id="default_comes_text">1. Content of assessed task:\n\r2. Format of assessment (eg. oral presentation):\n\r3. Method of evaluation (eg. rubric):\n\r4. Students should refer to:\n\r5. Any additional comments:\n\r</div>

<div style="text-align:center;">
<div id="saveButtonsLabel"></div>
<div id="saveButtons" style="display:block;">
    <input type="button" class="btn button-apply btn-success validate" onClick="saveStudyGuide()" value="Save" />
  {if:"{id}"!=0}
    <input type="button" class="btn button-apply btn-success validate" onClick="saveStudyGuideAsNew('{id}')" value="Save As New (Make Copy)" /> 
  {endif}
  	<input type="button" class="btn button-cancel" onClick="cancelStudyGuide('/index.php/studyguides?userid={currentuserid}')" value="Cancel" />

</div>
    </div>

<!--
    tab Grades
    <table class="ESTableBasic" style="text-transform: capitalize;">
customtablescatalog=marks,reportstudentmarkspage,simplestudentmarkline,showpublished,studyguide=={id},1,student}
    </table>
    <br/>
    <p>Note: -1.0 mean incomplete.</p>

  /tabs
-->
 
    
    
    <script>
        
        {customtablescatalog=streaminggroups,streaminggroupsjspage,streaminggroupsjsitem,showpublished}
        {customtablescatalog=subjects,subjectsjspage,subjectsjsitem,showpublished}    

        current_date_and_time=new Date("{php: date('D M d Y H:i:s O');}");
        studyguide_date_and_time=new Date("{php: date('D M d Y H:i:s O');}");
    
        checkStudyGuide=true;
        checkDate=true;


        if("{id}"=="0" || "{id}"=="")
        {
            document.getElementById('comes_text').value=document.getElementById('default_comes_text').innerHTML;
        }
        else
        {
            studyguide_date_and_time=document.getElementById('comes_date').value;
        }
    

    var v="block";
    if(streaminggroup_grades.length==0)
        v="none";
    
    document.getElementById('streaminggroupbox1').style.display=v;
    document.getElementById('streaminggroupbox2').style.display=v;
    document.getElementById('streaminggroupbox3').style.display=v;
    

    
    var assesment = "formative";
    var element = document.getElementById('eseditForm');
    var streamingbox = document.getElementById('comes_streaminggroup');
    
    
    {customtablescatalog=streaminggroups,streamingclassidpage,streamingclassiditem,showpublished,user=={currentuserid},1}
    
    deleteNotYourGroups("comes_streaminggroup");
    deleteNotYourGrades("comes_subjectSQLJoinLink");
    deleteNotYourClasses("comes_classes");
    
    StreamingGroupChanged();
    
    grade=document.getElementById('comes_grade').value;
    document.getElementById('comes_subjectSQLJoinLink').value=grade;
    
    showHideGradeAndSubject();
    StreamingGroupChanged();
    
    if(element.addEventListener)
    { 
        streamingbox.addEventListener("change", function(evt)
        {
            StreamingGroupChanged();
            return true;
        },true);
    }
    else
    {

        streamingbox.attachEvent("onchange", function(evt)
        {
            StreamingGroupChanged();
            return true;
        },true);
    }
    
    
    </script>


