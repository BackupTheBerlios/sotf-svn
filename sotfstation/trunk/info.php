<?
	include("classes/packXML.class.php");			# include data wrapper
	$myPack = new packXML('sotfPublish');
	$myPack->addData(array());
	$myPack->toFile("progs/Metadata.xml");
	
	echo "I suppose there were no errors ;)";
?>