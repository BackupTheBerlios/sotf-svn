<?
	/**
	 * pageFinish() - to output contents of the SMARTY template to the screen
	 * 
	 * @return (echo)
	 */
	function pageFinish($action,$errorReporting=TRUE,$popup=FALSE){
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
			
			//mark permissions
			$smarty->assign('edit_presentbox',$_SESSION['USER']->get("edit_presentbox"));
		}
		
		//output!
		if(!$popup){
			$smarty->display('index.htm');
		}else{
			$smarty->display('indexpopup.htm');
		}
	}
	
	/**
	 * pageFinishPopup() - same as above, but loaded in a pop-up window
	 * 
	 * @return (echo)
	 */
	function pageFinishPopup($action,$errorReporting=TRUE){
		global $smarty, $myError;
		pageFinish($action,$errorReporting,true);
	}
?>