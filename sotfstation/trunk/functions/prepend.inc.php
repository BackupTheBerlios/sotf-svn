<?
	/**
	 * pageFinish() - to output contents of the SMARTY template to the screen
	 * 
	 * @return 
	 */
	function pageFinish($action,$errorReporting=TRUE){
		global $smarty,$myNav,$myError;
		$smarty->assign("tot_time",stopTiming());
		$smarty->assign("nav_bar",$myNav->out());
		$smarty->assign("action",$action);
		
		//error reporting?
		if(($myError->getLength()>0) and ($errorReporting)){
			$smarty->assign("error_out",TRUE);
			$smarty->assign("error_list",$myError->getList());
		}
		
		//output!
		$smarty->display('index.htm');
	}
	
	/**
	 * pageFinishPopup() - same as above, but loaded in a pop-up window
	 * 
	 * @return 
	 */
	function pageFinishPopup(){
		global $smarty, $myError;
		$smarty->assign("tot_time",stopTiming());
		$smarty->display('index_popup.htm');
	}
?>