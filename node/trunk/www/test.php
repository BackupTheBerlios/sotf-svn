<?php
require("init.inc.php");

//$res = $userdb->getOne("SELECT auth_id FROM authenticate WHERE username = 'akazcs'");
//echo "'$res'";

#$page->send();


echo mime_content_type("test.php");

?>

<SCRIPT language="javascript">
<!--
function setvalue(name, id, value){
var popurl="updatedb.php?name="+name+"&id="+id+"&value="+value
winpops=window.open(popurl,"","width=100,height=100,left=320,top=200")
}

function setcaption(id, value){
  window.location.href = this.location.href+"?id="+id+"&value="+value;
  //return false;
  //var popurl="updatedb.php?name=caption&id="+id+"&value="+value
  //winpops=window.open(popurl,"","width=100,height=100,left=320,top=200")
  //setTimeout("window.location.replace(this.location)",3000)
}
// -->
</SCRIPT>

<a href="javascript:str = prompt('Caption: ', '{$item.caption}'); if(str) setcaption('asdfg', escape(str));">TEST</a>
