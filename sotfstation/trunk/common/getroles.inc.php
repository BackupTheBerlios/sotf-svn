<?
	$file = fopen('templates/roles.txt', "r");
	$roles = fread($file, filesize('templates/roles.txt'));
	fclose($file);
	$roles = explode("\n",$roles);
	reset($roles);
	while(list($key,$val) = each($roles)){
		$myroles[$val] = $val;
	}
?>