<?
	/**
	 * pageFinish() - to output contents of the SMARTY template to the screen
	 * 
	 * @return (echo)
	 */
	function pageFinish($action,$errorReporting=TRUE){
		global $smarty,$myNav,$myError,$_SESSION;
		$smarty->assign("tot_time",stopTiming());
		$smarty->assign("nav_bar",$myNav->out());
		$smarty->assign("action",$action);
		
		//error reporting?
		if(($myError->getLength()>0) and ($errorReporting)){
			$smarty->assign("error_out",TRUE);
			$smarty->assign("error_list",$myError->getList());
		}
		
		//user logged in?
		if(is_object($_SESSION['USER'])){
			if($_SESSION['USER']->get("name")){
				$smarty->assign('logged_in',TRUE);
				$smarty->assign('user_name',ucfirst($_SESSION['USER']->get("name")));
			}
		}
		
		//output!
		$smarty->display('index.htm');
	}
	
	/**
	 * pageFinishPopup() - same as above, but loaded in a pop-up window
	 * 
	 * @return (echo)
	 */
	function pageFinishPopup(){
		global $smarty, $myError;
		$smarty->assign("tot_time",stopTiming());
		$smarty->display('index_popup.htm');
	}
?>