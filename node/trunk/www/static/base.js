
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
