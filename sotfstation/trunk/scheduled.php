<?
	include("init.inc.php");		# include the global framework
	
	//anything to start now?
	$now = date("Y-m-d H:i");
	$tostart = $db->getOne("SELECT id FROM programme WHERE intime <= '$now' AND outtime >= '$now' AND special = 'pp' ORDER BY intime LIMIT 1");
	
	//check what is playing now
	$file = fopen('playing.txt', "w");
	$contents = fread($file, filesize('playing.txt'));
	fclose($file);
	
	if($contents != $tostart and $tostart){
		//remove file
		@unlink('playing.txt');
		
		//write new file
		$file = fopen('playing.txt', "w");
		fwrite($file, $tostart);
		fclose($file);
		
		//get audiofile
		$d = dir(PROG_DIR . $tostart . "/XBMF/audio");
		
		//loop through entries
		while (false !== ($entry = $d->read())) {
			if($entry != '.' and $entry != '..'){
				$toplay = $entry;
				break;
			}
		}
		$d->close();
		
		echo "/var/www/sotfstation/progs/" . $tostart . "/XBMF/audio/" . $toplay;
	}
	
	/*******************************************************************************
	* Hey, baby, do you want to kill all human? 8|                                 *
	********************************************************************************
	* 02.06.2003 *
	**************/
?>