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
	$mytopics = array();
	foreach($topics as $topic){
		$topic = explode(";",$topic);
		
		$key = $topic[0];
		$name = $topic[1];
		$offset = $topic[2];
		
		$add = '';
		if($offset > 0){
			for($i=0;$i<$offset;$i++){
				$add .= "--" . $add;
			}
		}
		
		if(!empty($key)){
			$mytopics[$key] = $add . $name;
		}
	}
	$mytopics['zzzzzzzzzzzzz'] = "Unknown / Undefined";
	
	//get genres array
	$file = fopen('templates/genres.txt', "r");
	$genres = fread($file, filesize('templates/genres.txt'));
	fclose($file);
	$genres = explode("\n",$genres);
	reset($genres);
	$mygenres = array();
	foreach($genres as $genre){
		$genre = explode(";",$genre);
		$key = $genre[0];
		$name = $genre[1];
		
		if(!empty($key)){
			$mygenres[$key] = $name;
		}
	}
	$mygenres['zzzzzzzzzzzzz'] = "Unknown / Undefined";
	//##### END GET DATA ########
?>