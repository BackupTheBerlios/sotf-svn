<?
	//get language array
	$file = fopen('templates/langs.txt', "r");
	$contents = fread($file, filesize('templates/langs.txt'));
	fclose($file);
	$contents = explode("\n",$contents);
	$langs = array();
	reset($contents);
	while(list($key,$val) = each($contents)){
		$val = explode(";",$val);
		$myKey = trim($val[1]);
		$langs[$myKey] = trim($val[0]);
	}
	
	//get topics array
	$file = fopen('templates/topics.txt', "r");
	$topics = fread($file, filesize('templates/topics.txt'));
	fclose($file);
	$topics = explode("\n",$topics);
	reset($topics);
	while(list($key,$val) = each($topics)){
		$val2 = trim($val);
		$mytopics[$val2] = str_replace("\t","-- ",ucfirst($val));
	}
	
	//get genres array
	$file = fopen('templates/genres.txt', "r");
	$genres = fread($file, filesize('templates/genres.txt'));
	fclose($file);
	$genres = explode("\n",$genres);
	reset($genres);
	while(list($key,$val) = each($genres)){
		$mygenres[$val] = $val;
	}
	//##### END GET DATA ########
?>