<?
	/**
	 * pageFinish() - to output contents of the SMARTY template to the screen
	 * 
	 * @return 
	 */
	function pageFinish(){
		global $smarty;
		$smarty->assign("tot_time",stopTiming());
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