<?
	/**
	 * pageFinish() - to output contents of the SMARTY template to the screen
	 * 
	 * @return 
	 */
	function pageFinish($action){
		global $smarty,$myNav;
		$smarty->assign("tot_time",stopTiming());
		$smarty->assign("nav_bar",$myNav->out());
		$smarty->assign("action",$action);
		$smarty->display('index.htm');
	}
	
	/**
	 * pageFinishPopup() - same as above, but loaded in a pop-up window
	 * 
	 * @return 
	 */
	function pageFinishPopup(){
		global $smarty;
		$smarty->assign("tot_time",stopTiming());
		$smarty->display('index_popup.htm');
	}
?>