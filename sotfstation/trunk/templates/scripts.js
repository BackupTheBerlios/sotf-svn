var confirmMsg  = 'time blocks?';
function confirmLink(theLink, theSqlQuery)
{
    var is_confirmed = confirm('Are you sure you want to delete this?');    
    return is_confirmed;
}

var win = null;
function NewWindow(mypage,myname,w,h,scroll){
	LeftPosition = (screen.width) ? (screen.width-w)/2 : 0;
	TopPosition = (screen.height) ? (screen.height-h)/2 : 0;
	settings = 'height='+h+',width='+w+',top='+TopPosition+',left='+LeftPosition+',scrollbars='+scroll+',resizable=0'
	win = window.open(mypage,myname,settings)
}

function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}