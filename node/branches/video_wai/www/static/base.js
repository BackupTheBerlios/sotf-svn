
// confirmUnsaved() is declared in main.htm and main_popup.htm as it needs localization via Smarty

function popup(url, name, height, width, scroll) {
  if(!confirmUnsaved())
    return;
	var features = "screenX=350,screenY=300,width=" + width + ",height=" + height +
		",menubar=no,resizable=yes,status=no,locationbar=no";
	if (scroll) {
		features +=",scrollbars=yes";
	}
	var win = window.open(url, name, features);
	win.focus();
}

/*
function redir2(f, url) {
  f.action = url;
  f.submit();
}
*/

function redir(f, url) {
  if(!confirmUnsaved())
    return;
  window.location.href = url;
}

function checkIfEmpty(input, message) {
  if(!input.value) {
    alert(message);
    return false;
  } else
    return true;
}

function deleteConfirm(msg) {
    return confirm(msg);
}

function getElementsByStyleClass (className) {
  var all = document.all ? document.all :
    document.getElementsByTagName('*');
  var elements = new Array();
  for (var e = 0; e < all.length; e++)
    if (all[e].className == className)
      elements[elements.length] = all[e];
  return elements;
}

function toggleAllInfo (e, styleclass)
{
	var nodelist = getElementsByStyleClass(styleclass);
	var arrowlist = getElementsByStyleClass("pulldown");
	
	if(e.firstChild.nodeValue == "close all details"){
		for(var i = 0; i < nodelist.length; i++){
			nodelist[i].style.display = "none";
			var temp = arrowlist[i].firstChild.src;
			var b=temp.match(/[\/|\\]([^\\\/]+)$/);
			if(b[1] == "pullup.gif"){
				arrowlist[i].firstChild.src = arrowlist[i].firstChild.name;
				arrowlist[i].firstChild.name = temp;
			}
		}
		e.firstChild.nodeValue = "show all details...";
	}
	
	else {
		for(var i = 0; i < nodelist.length; i++){
			nodelist[i].style.display = "block";
			var temp = arrowlist[i].firstChild.src;
			var b=temp.match(/[\/|\\]([^\\\/]+)$/);
			if(b[1] == "pulldown.gif"){
				arrowlist[i].firstChild.src = arrowlist[i].firstChild.name;
				arrowlist[i].firstChild.name = temp;
			}
		}
		e.firstChild.nodeValue = "close all details";
	}
		
				


		
	
}

	function toggleInfo (e)
{
	var node = e.parentNode.nextSibling;
	var count = 0;
	while(count < 3){		
		if (node.nodeName == "DIV"){
			if(node.getAttribute("name") == "additional_info"){
				if(node.style.display == "none"){
					e.firstChild.nodeValue = "close Details";
					node.style.display = "block";
				}
				else{
					node.style.display = "none";
					e.firstChild.nodeValue = "show more...";
				}
			return;
			}
		}
		else{
			node = node.nextSibling;
			count++;
		}
		
	}
}

	function toggleInfoDirect (e, styleclass)
{
	var nodelist = e.parentNode.parentNode.parentNode.parentNode.getElementsByTagName("div");
	
		var node = nodelist[2];
			if(node.getAttribute("name") == "additional_info"){
				if(node.style.display == "none"){
					var temp = e.getElementsByTagName("img")[0].src;
					e.getElementsByTagName("img")[0].src = e.getElementsByTagName("img")[0].name;
					e.getElementsByTagName("img")[0].name = temp;
					node.style.display = "block";
				}
				else{
					node.style.display = "none";
					var temp = e.getElementsByTagName("img")[0].src;
					e.getElementsByTagName("img")[0].src = e.getElementsByTagName("img")[0].name;
					e.getElementsByTagName("img")[0].name = temp;
				}
			
			}
	
}