
function popup(url, name, height, width, scroll) {
  //alert(url);
	var features = "screenX=350,screenY=300,width=" + width + ",height=" + height +
		",menubar=no,resizable=yes,status=no,locationbar=no";
	if (scroll) {
		features +=",scrollbars=yes";
	}
	var win = window.open(url,name, features);
	win.focus();
}

/*
function call2(f, url) {
  f.action = url;
  f.submit();
}
*/

function redir(f, url) {
  window.location.href = url;
}
