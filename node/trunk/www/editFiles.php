<?php
require("init.inc.php");

if(sotf_Utils::getParameter('newProg')) {
  $smarty->assign("PAGETITLE", $page->getlocalized("New_prog_step1"));
} else {
  $smarty->assign("PAGETITLE", $page->getlocalized("Edit_files"));
}
    
$page->forceLogin();

sotf_Utils::registerGlobalParameters('id', 'okURL', 'copy');

$ok = sotf_Utils::getParameter('ok');
$okURL = sotf_Utils::getParameter('okURL');
$send = sotf_Utils::getParameter('send');
$selectedUserFiles = sotf_Utils::getParameter('userfiles');
$selectedOtherFiles = sotf_Utils::getParameter('otherfiles');
$deluser = sotf_Utils::getParameter('deluser');
$delother = sotf_Utils::getParameter('delother');

$itemtoftp = sotf_Utils::getParameter('itemtoftp');
$ftptoaudio = sotf_Utils::getParameter('ftptoaudio');
$ftptoother = sotf_Utils::getParameter('ftptoother');
$ftptoicon = sotf_Utils::getParameter('ftptoicon');

$smarty->assign("OKURL",$okURL);

$prg = & new sotf_Programme($id);

// admins or owners can change files
if(!$prg->isEditable()) {
  raiseError("no permission to change files in this programme");
  exit;
}

if ($ok)
	if ($okURL)
		$page->redirect($okURL);
if ($delother)
{
	for($i=0;$i<count($selectedOtherFiles);$i++)
		$retval = $prg->deleteFile($selectedOtherFiles[$i]);
}
elseif ($deluser)
{
	for($i=0;$i<count($selectedUserFiles);$i++)
		$retval = $user->deleteFile($selectedUserFiles[$i]);
}
elseif ($send)
{
	$success = move_uploaded_file($_FILES['userfile']['tmp_name'], $user->getUserDir() . '/' . $_FILES['userfile']['name']);
	if (!$success)
	{
		$status = "&uploaderror=1";
	}
	$page->redirect($_SERVER['PHP_SELF'] . "?id=".rawurlencode($id)."&okURL=".rawurlencode($okURL).$status);
}
elseif ($ok)
{
	$page->redirect($okURL);
}
elseif ($itemtoftp)
{
	foreach($_POST as $name => $value)
		if (substr($name,0,6) == 'tosel-')
		{
			$postvar = sotf_Utils::getParameter($name);
			for($i=0;$i<count($postvar);$i++)
				$retval = $prg->getAudio($postvar[$i],$copy);
		}
	for($i=0;$i<count($selectedOtherFiles);$i++)
		$retval = $prg->getOtherFile($selectedOtherFiles[$i],$copy);
	$page->redirect($PHP_SELF . "?id=".rawurlencode($id)."&okURL=".rawurlencode($okURL));
}
elseif ($ftptoaudio)
{
	for($i=0;$i<count($selectedUserFiles);$i++)
		$retval = $prg->setAudio($selectedUserFiles[$i],$copy);
	$page->redirect($PHP_SELF . "?id=".rawurlencode($id)."&okURL=".rawurlencode($okURL));
}
elseif ($ftptoother)
{
	for($i=0;$i<count($selectedUserFiles);$i++)
		$retval = $prg->setOtherFile($selectedUserFiles[$i],$copy);
	$page->redirect($PHP_SELF . "?id=".rawurlencode($id)."&okURL=".rawurlencode($okURL));
}
if ($status)
{
	$smarty->assign("STATUS",$status);
}

$smarty->assign('OTHERFILES', $prg->listOtherFiles());
$smarty->assign('AUDIOFILES', $prg->listAudioFiles());
$smarty->assign("USERFILES",$user->getUserFiles());

$userFtpUrl = str_replace('ftp://', "ftp://".$user->name."@", "$userFTP$userid");
$smarty->assign("USERFTPURL", $userFtpUrl); 

$smarty->assign('ID',$id);

$page->send();


?>
