<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 



/*  

 * $Id$

 * Created for the StreamOnTheFly project (IST-2001-32226)

 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 

 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu

 */



require("init.inc.php");

$page->forceLogin();



$playlist = new sotf_UserPlaylist;



if (sotf_Utils::getParameter("close") == "true")

{	

	$lista = sotf_Utils::getParameter("lista");

	$list = split("\|", $lista);

	$max = count($list);

	for($i=1; $i<$max; $i++)

	{

		$l = split(":", $list[$i]);

		if ($l[1] != $i)

		{

			//print($l[0].":".$l[1]." -> ".$i."<br>");

      $playlist->setOrder($l[0], $i);

		}



	}

	$page->redirect("closeAndRefresh.php");

	//var_dump($lista);

	//die("<HTML><HEAD></HEAD><BODY onload='javascript:window.opener.location.reload();window.close();'></BODY></HTML>");

}



$result = $playlist->load();



$programmes = array();

foreach($result as $value)

{

	$programmes[$value["id"].":".$value["order_id"]] = $value["title"];

}



$smarty->assign("result", $result);

$smarty->assign("count", count($result));

$smarty->assign("programmes", $programmes);



$page->sendPopup("playlistPopup.htm");

?>