<?
	$file = fopen('templates/roles.txt', "r");
	$roles = fread($file, filesize('templates/roles.txt'));
	fclose($file);
	$roles = explode("\n",$roles);
	reset($roles);
	
	$myroles = array();
	foreach($roles as $role){
		$role = explode(";",$role);
		$key = $role[0];
		$name = $role[1];
		
		if(!empty($key)){
			$myroles[$name] = $name;
		}
	}
?>