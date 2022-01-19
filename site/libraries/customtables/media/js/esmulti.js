function ESShowHide(objname)
{
    var obj=document.getElementById(objname);
    
    if(obj.style.display=="block")
        obj.style.display="none";
    else
        obj.style.display="block";
    
}


function CustomTablesChildClick(BoxName,DivName)
{
    var obj=document.getElementById(BoxName);
    var divobj=document.getElementById(DivName);
    
    if(obj.checked){
        divobj.style.display="block";
    }
    else
    {
        divobj.style.display="none";
    }
    
    return 0;
}


function ESCheckAll(prefix,aList)
{
    for(i=0;i<aList.length;i++)
    {
        var obj=document.getElementById(prefix+"_"+aList[i]);
        obj.checked=true;
    }
}

function ESUncheckAll(prefix,aList)
{
    for(i=0;i<aList.length;i++)
    {
        var obj=document.getElementById(prefix+"_"+aList[i]);
        obj.checked=false;
    }
}




function ESsmart_float(el, evt, decimals)
{
    if(decimals<1)
        return true;
    
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57))
        return true;

    
    if (charCode == 8 || charCode == 13)
        return true;
    

    
    

    var v=el.value;
    
    /*
    if (charCode == 48 && v=='')
    {
        el.value='0.';
        return true;
    }
    */
    
    
    if(el.selectionEnd-el.selectionStart>0)
    {
        return true;
    }
    
    if(el.selectionStart<v.length)
        return true;

    
    if(el.maxLength==v.length)
        return true;
    
    
    if (charCode == 46)
    {
        v=v.replace(".","");
        var p=parseFloat(v);
        if(isNaN(p))
            p=0;
        
        el.value=p;
        return true;
    }
    
    
    //check selection
    
  
    
    
    
    var d=(v.split('.')[1] || []).length;
    if(d>decimals)
        d=decimals;
     
    var p=parseFloat(v);
    if(isNaN(p))
        p=0;
    
    
    if(d==0)
    {
        var g=""+p;
        
        if(v=='0')
        {
            el.value=p+'.';
            return true;
        }
        
        if(g.length!=0)
        {
            if(g.length==1 && p!=0)
                el.value=p+'.';
                
            return true;
        }
        
            
        var vv='0.';
        
        
        for(var d=1;d<decimals;d++)
            vv+='0';
            
        el.value=vv;
        
        return true;
    }
    
    if(d==decimals)
    {
        p=p*10;
        el.value=p.toFixed(decimals-1);
        return true;
    }
    
    return true;
   
}
