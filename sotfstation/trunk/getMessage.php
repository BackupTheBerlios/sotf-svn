<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Template Page Using the pre-build page generation framework
	*----------------------------------------
	* Purpose of page goes here
	************************/
	include("init.inc.php");										# include the global framwork
	
	if($_GET['general'] == 'intro'){
		$file = $config['recorded_dir']."/intro.wav";
	}elseif($_GET['general'] == 'help'){
		$file = $config['recorded_dir']."/help.wav";
	}elseif($_GET['general'] == 'navigation'){
		$file = $config['recorded_dir']."/navigation.wav";
	}elseif($_GET['general'] == 'applicant_help'){
		$file = $config['recorded_dir']."/applicant_help.wav";
	}elseif($_GET['general'] == 'applicant_intro'){
		$file = $config['recorded_dir']."/applicant_intro.wav";
	}
	
	header("Content-type: audio/wav\n");
	header("Content-transfer-encoding: binary\n"); 
	header("Content-length: " . filesize($file) . "\n");   

	// send file
	readfile($file);

		/**
 * 	header ("Location: " . VBBASEURL . "/message_dir/" . $vbid . "/" . $fileName);
			}else if($_GET['action'] == 'deletefile'){
				$vdb->query("DELETE FROM vb_messages WHERE msg_id = '$_GET[id]'");
				header ("Location: voicemail.php");
			}
 */
?>
