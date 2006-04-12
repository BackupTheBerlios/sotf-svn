<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 * Update Topic Tree by Martin Schmidt, FH St. Poelten
 */

require("init.inc.php");

// may contain long jobs
set_time_limit(300);

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();

//$page->errorURL = "admin.php";

checkPerm('node', 'change', 'authorize');

// import XBMF
$xbmfFile = sotf_Utils::getParameter('import_xbmf');
if($xbmfFile) {
  $file = $config['xbmfInDir'] . "/$xbmfFile";
  $id = sotf_Programme::importXBMF($file, $config['publishXbmf'], true);
  if($id) {
	 echo "Import succesful: <a target=\"_opener\" href=\"editMeta.php?id=$id\">click here</a>";
	 unlink($file);
  } else {
	 echo "Import failed";
  }
  $page->logRequest();
  exit;
}

// update CVS
if(sotf_Utils::getParameter('updatecvs')) {
  die("doesn't work this way, sorry");
  checkPerm('node', 'change');

  chdir($config['basedir']);
  header("Content-type: text/plain\n");
  system('cvs update');
  //$page->redirect("admin.php");
  $page->logRequest();
  exit;
}

// recompile Smarty templates
if(sotf_Utils::getParameter('retemplate')) {
	checkPerm('node', 'change');
  $smarty->clear_compiled_tpl();
  $page->redirect("admin.php");
  $page->logRequest();
  exit;  
}

// update topic counts
if(sotf_Utils::getParameter('updatetopics')) {
  $vocabularies->updateTopicCounts();
  $page->redirect("admin.php");
  $page->logRequest();
  exit;  
}

// save general data
if(sotf_Utils::getParameter('save_debug')) {
	checkPerm('node', 'change');
  $sotfVars->set('debug', sotf_Utils::getParameter('debug'));
  $sotfVars->set('debug_sql', sotf_Utils::getParameter('debug_sql'));
  $sotfVars->set('debug_smarty', sotf_Utils::getParameter('debug_smarty'));
  $sotfVars->set('smarty_compile_check', sotf_Utils::getParameter('smarty_compile_check'));
  $page->redirect("admin.php");
  $page->logRequest();
  exit;
}

// save network data
if(sotf_Utils::getParameter('save')) {
	checkPerm('node', 'change');
  $desc = sotf_Utils::getParameter('desc');
  $localNode = sotf_Node::getLocalNode();
  $localNode->set('description', $desc);
  $localNode->update();
  $page->redirect("admin.php#network");
  $page->logRequest();
  exit;
}

// sync
if(sotf_Utils::getParameter('sync')) {
	checkPerm('node', 'change');
  // this can be long duty!
  set_time_limit(18000);
  // get neighbour object
  $nid = sotf_Utils::getParameter('nodeid');
  $neighbour = sotf_Neighbour::getById($nid);
  // full sync?
  if(sotf_Utils::getParameter('full'))
	 sotf_NodeObject::newNodeInNetwork($nid);
  // sync
  $neighbour->sync(true);
  if($config['debug'])
	 echo "<h3>Sync completed.</h3>";
  else
	 $page->redirect("admin.php#network");
  $page->logRequest();
}

// delete neighbour
if(sotf_Utils::getParameter('delneighbour')) {
	checkPerm('node', 'change');
  debug("delete neighbour", "!!");
  $nid = sotf_Utils::getParameter('nodeid');
  $neighbour = sotf_Neighbour::getById($nid);
  $neighbour->delete();
  $page->redirect("admin.php#network");
  $page->logRequest();
}

// manage permissions
$delperm = sotf_Utils::getParameter('delperm');
if(sotf_Utils::getParameter('delperm')) {
	checkPerm('node', 'authorize');
  $userid = sotf_Utils::getParameter('userid');
  if(empty($userid) || !is_numeric($userid)) {
    raiseError("Invalid userid: $userid");
  }
  $username = $user->getUsername($userid);
  if(empty($username)) {
    raiseError("Invalid userid: $userid");
  }
  $permissions->delPermission('node', $userid);
  $msg = $page->getlocalizedWithParams("deleted_permissions_for", $username);
  $page->addStatusMsg($msg, false);
  $page->redirect("admin.php");
  $page->logRequest();
  exit;
}

// generate output

$localNode = sotf_Node::getLocalNode();
if(!$localNode) {
	// clear old entry
	$localNode = new sotf_Node();
	$localNode->set('name', $config['nodeName']);
	$localNode->find();
	if($localNode->exists())
		$localNode->delete();
	// create local node entry if does not exist
  $localNode = new sotf_Node();
  $localNode->set('node_id', $config['nodeId']);
  $localNode->set('name', $config['nodeName']);
  $localNode->set('url', $config['rootUrl']);
  $localNode->create();
}

$smarty->assign("LOCAL_NODE", $localNode->getAll());

// nodes
//$nodes = sotf_Node::countAll();
//$smarty->assign('NODES',$nodeData);

// neighbours
//$neighbours = sotf_Neighbour::listAll();
$nodes = sotf_Node::listAll();
while(list(,$node)= each($nodes)) {
  $nodeId = $node->get('node_id');
  if($nodeId == $config['nodeId'])
	 continue;
  $data = $node->getAll();
  $nei = sotf_Neighbour::getById($nodeId);
  if($nei)
    $data['neighbour'] = $nei->getAll();
  $data['pending_objects'] = $db->getOne("select count(*) from sotf_object_status where node_id='$nodeId'");
  $data['pending_forwards'] = $db->getOne("select count(*) from sotf_to_forward where node_id='$nodeId'");
  $neighbors = $data['neighbours'];
  debug("X0", $data['neighbours']);
  $neighbors = str_replace("io","&lt;-&gt;",$neighbors);
  $neighbors = str_replace("i","&lt;-",$neighbors);
  $neighbors = str_replace("o","-&gt;",$neighbors);
  $data['neighbours'] = explode(',', $neighbors);
  debug("XX", $data['neighbours']);
  $nodeData[] = $data;
}
$smarty->assign('NODES',$nodeData);

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized('node'));

// arriving xbmf
$dirPath = $config['xbmfInDir'];
$dir = dir($dirPath);
while($entry = $dir->read()) {
	if ($entry != "." && $entry != "..") {
		$currentFile = $dirPath . "/" .$entry;
		//debug("examining", $currentFile);
		if (!is_dir($currentFile)) {
			$XBMF[] = basename($currentFile);
		}
	}
}
$dir->close();
$smarty->assign("XBMF", $XBMF); 


// UPDATE Topic Tree ADDED BY Martin Schmidt, FH St. Poelten
if(sotf_Utils::getParameter('updatetopictree')) {
	$query = "SELECT * FROM sotf_vars WHERE name='topic_update_done' AND value=1";
	$result = $db->getRow($query);
	if (count($result) == 0) {
		if(is_file($config['basedir'].'/code/share/update_topics.txt') && sotf_Utils::getParameter('confirmed')){
			$db->query("BEGIN;");
			
			// UPDATE sotf_topic_tree_defs, sotf_topics
			$update_statements="";
			$upd_file = fopen ($config['basedir'].'/code/share/update_topics.txt', "r"); 
			while (!feof($upd_file)) {
			   $update_statements .= fgets($upd_file, 4096);
			}
			fclose ($upd_file);
			$db->query($update_statements); 
			
			// UPDATE sotf_prog_topics (OLD TOPIC ID => NEW TOPIC ID)
			$new_topics = array( '001td8' => '001td2', '001td9' => '001td6', '001td10' => '001td8', '001td11' => '001td9', '001td12' => '001td10', '001td13' => '001td11', '001td14' => '001td12', '001td15' => '001td12', '001td16' => '001td13', '001td17' => '001td10', '001td18' => '001td14', '001td19' => '001td15', '001td20' => '001td16', '001td21' => '001td18', '001td22' => '001td19', '001td23' => '001td20', '001td24' => '001td21', '001td25' => '001td22', '001td26' => '001td23', '001td27' => '001td24', '001td28' => '001td18', '001td29' => '001td25', '001td30' => '001td26', '001td31' => '001td27', '001td32' => '001td28', '001td33' => '001td29', '001td34' => '001td18', '001td35' => '001td25', '001td36' => '001td30', '001td37' => '001td18', '001td38' => '001td31', '001td39' => '001td32', '001td40' => '001td18', '001td41' => '001td34', '001td42' => '001td35', '001td43' => '001td35', '001td44' => '001td36', '001td45' => '001td35', '001td46' => '001td34', '001td47' => '001td34', '001td48' => '001td36', '001td49' => '001td26', '001td50' => '001td37', '001td51' => '001td38', '001td52' => '001td39', '001td53' => '001td40', '001td54' => '001td35', '001td55' => '001td38', '001td56' => '001td38', '001td57' => '001td41', '001td58' => '001td42', '001td59' => '001td43', '001td60' => '001td44', '001td61' => '001td41', '001td62' => '001td41', '001td63' => '001td41', '001td64' => '001td45', '001td65' => '001td46', '001td66' => '001td47', '001td67' => '001td48', '001td68' => '001td45', '001td69' => '001td41', '001td70' => '001td49', '001td71' => '001td41', '001td72' => '001td42', '001td73' => '001td50', '001td74' => '001td51', '001td75' => '001td52', '001td76' => '001td51', '001td77' => '001td53', '001td78' => '001td54', '001td79' => '001td55', '001td80' => '001td56', '001td81' => '001td57', '001td82' => '001td58', '001td83' => '001td51', '001td84' => '001td59', '001td85' => '001td60', '001td86' => '001td61', '001td87' => '001td62', '001td88' => '001td63', '001td89' => '001td62', '001td90' => '001td64', '001td91' => '001td65', '001td92' => '001td66', '001td93' => '001td67', '001td94' => '001td68', '001td95' => '001td66', '001td96' => '001td61', '001td97' => '001td69', '001td98' => '001td27', '001td99' => '001td65', '001td100' => '001td70', '001td101' => '001td69', '001td102' => '001td71', '001td103' => '001td72', '001td104' => '001td73', '001td105' => '001td74', '001td106' => '001td75', '001td107' => '001td76', '001td108' => '001td33', '001td109' => '001td111', '001td110' => '001td77', '001td111' => '001td33', '001td112' => '001td106', '001td113' => '001td78', '001td114' => '001td79', '001td115' => '001td80', '001td116' => '001td81', '001td117' => '001td82', '001td118' => '001td83', '001td119' => '001td84', '001td120' => '001td85', '001td121' => '001td86', '001td122' => '001td87', '001td123' => '001td88', '001td124' => '001td89', '001td125' => '001td90', '001td126' => '001td91', '001td127' => '001td92', '001td128' => '001td90', '001td129' => '001td90', '001td130' => '001td93', '001td131' => '001td90', '001td132' => '001td94', '001td133' => '001td91', '001td134' => '001td95', '001td135' => '001td96', '001td136' => '001td97', '001td137' => '001td98', '001td138' => '001td99', '001td139' => '001td98', '001td140' => '001td100', '001td141' => '001td17', '001td142' => '001td17', '001td143' => '001td17', '001td144' => '001td17', '001td145' => '001td17', '001td146' => '001td17', '001td147' => '001td17', '001td148' => '001td17', '001td149' => '001td17', '001td150' => '001td17', '001td151' => '001td17', '001td152' => '001td17', '001td153' => '001td17', '001td154' => '001td17', '001td155' => '001td17', '001td156' => '001td17', '001td157' => '001td17', '001td158' => '001td17', '001td159' => '001td17', '001td160' => '001td17', '001td161' => '001td17', '001td162' => '001td17', '001td163' => '001td17', '001td164' => '001td17', '001td165' => '001td17', '001td166' => '001td17', '001td167' => '001td17', '001td168' => '001td17', '001td169' => '001td17', '001td170' => '001td17', '001td171' => '001td17', '001td172' => '001td17', '001td173' => '001td17', '001td174' => '001td17', '001td175' => '001td17', '001td176' => '001td17', '001td177' => '001td17', '001td178' => '001td17', '001td179' => '001td17', '001td180' => '001td17', '001td181' => '001td17', '001td182' => '001td17', '001td183' => '001td17', '001td184' => '001td17', '001td185' => '001td17', '001td186' => '001td17', '001td187' => '001td17', '001td188' => '001td17', '001td189' => '001td17', '001td190' => '001td17', '001td191' => '001td17', '001td192' => '001td17', '001td193' => '001td17', '001td194' => '001td17', '001td195' => '001td17', '001td196' => '001td17', '001td197' => '001td17', '001td198' => '001td17', '001td199' => '001td17', '001td200' => '001td17', '001td201' => '001td17', '001td202' => '001td17', '001td203' => '001td17', '001td204' => '001td17', '001td205' => '001td17', '001td206' => '001td17', '001td207' => '001td17', '001td208' => '001td17', '001td209' => '001td17', '001td210' => '001td101', '001td211' => '001td102', '001td212' => '001td103', '001td213' => '001td104', '001td214' => '001td105', '001td215' => '001td44', '001td216' => '001td106', '001td217' => '001td106', '001td218' => '001td101', '001td219' => '001td101', '001td220' => '001td103', '001td221' => '001td107', '001td222' => '001td108', '001td223' => '001td103', '001td224' => '001td109', '001td225' => '001td109', '001td226' => '001td110', '001td227' => '001td112', '001td228' => '001td113', '001td229' => '001td114', '001td230' => '001td115', '001td231' => '001td16', '001td232' => '001td116', '001td233' => '001td117', '001td234' => '001td118', '001td235' => '001td119', '001td236' => '001td22', '001td237' => '001td120', '001td238' => '001td119', '001td239' => '001td121', '001td240' => '001td122', '001td241' => '001td113', '001td242' => '001td119', '001td243' => '001td119', '001td244' => '001td123', '001td245' => '001td15', '001td246' => '001td113', '001td247' => '001td124', '001td248' => '001td9', '001td249' => '001td119', '001td250' => '001td125', '001td251' => '001td10', '001td252' => '001td4', '001td253' => '001td126', '001td254' => '001td119', '001td255' => '001td113', '001td256' => '001td16', '001td257' => '001td15', '001td258' => '001td127', '001td259' => '001td18', '001td260' => '001td110');
			
			$old_topics = array_keys($new_topics);
			for($k=0;$k<count($old_topics);$k++){
			
				// get programs with current OLD topic_id
				$q_progs=$db->query("SELECT prog_id FROM sotf_prog_topics WHERE topic_id = '".$old_topics[$k]."';");
				while ($r_progs = $q_progs->fetchRow()) {
					
					// check whether program has already the NEW topic_id
					$q_already_new="SELECT * FROM sotf_prog_topics WHERE prog_id = '".$r_progs['prog_id']."' AND topic_id = '".$new_topics[$old_topics[$k]]."';";
					
					$r_already_new = $db->getRow($q_already_new);
					
					if(count($r_already_new) == 0){
						//echo "UPDATE sotf_prog_topics SET topic_id ='".$new_topics[$old_topics[$k]]."' WHERE prog_id='".$r_progs['prog_id']."' AND topic_id ='".$old_topics[$k]."';<br>";
						$db->query("UPDATE sotf_prog_topics SET topic_id ='".$new_topics[$old_topics[$k]]."' WHERE prog_id='".$r_progs['prog_id']."' AND topic_id ='".$old_topics[$k]."';");
					}
					else {
						//echo "DELETE FROM sotf_prog_topics WHERE prog_id ='".$r_progs['prog_id']."' AND topic_id ='".$old_topics[$k]."';<br>";
						$db->query("DELETE FROM sotf_prog_topics WHERE prog_id ='".$r_progs['prog_id']."' AND topic_id ='".$old_topics[$k]."';");
					}
					
				} // while
			} //for
			
			//echo "INSERT INTO sotf_vars (name, value) VALUES ('topic_update_done', '1');<br>";
			$db->query("INSERT INTO sotf_vars (name, value) VALUES ('topic_update_done', '1');");
			$db->query("COMMIT;");
			$vocabularies->updateTopicCounts();
			$smarty->assign("TTREE_UPD_MESS", '<span style="color:green">The Topic Tree has successfully been updated.</span>');
		} // if is_file && confirmed
		elseif(is_file($config['basedir'].'/code/share/update_topics.txt')){
			$smarty->assign("TTREE_UPD_MESS", '<span style="color:red">Please confirm the update to the new topic tree definitions - Version Spring 2006.<br>Make sure you have made a dump of your node database before!</span><br>&nbsp;<br><a href="admin.php?updatetopictree=1&confirmed=1">Yes, update this node\'s topic tree!</a>');
		}
		else{
			$smarty->assign("TTREE_UPD_MESS", '<span style="color:red">The file '.is_file($config['basedir']).'/code/share/update_topics.txt could not have been found.<br>Get the current version from SVN!</span>');
		}
	} // if count($result)==0
	else {
		$smarty->assign("TTREE_UPD_MESS", '<span style="color:red">The Topic Tree had already been updated before.</span>');
	}
} // if "updatetopictree"


// variables
$smarty->assign("VARS", $sotfVars->getAll());

$page->send();

?>
