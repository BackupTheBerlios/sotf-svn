<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/**
	 * authorize()
	 * 
	 * check to see whether this user may work with this section of the web site
	 * 
	 * @param $section (string)
	 * @return (bool||redirect)
	 */
	function authorize($section){
		global $ERR, $myError;
		if($_SESSION['USER']->get($section)==2){					# user has full access to this section
			//nothing
			return true;
		}else if($_SESSION['USER']->get($section)==1){		# user has read only access to this section
			if((count($_POST) > 0) or (isset($_GET['action']))){		# there has been a POST call or an action
				//unset the action calls
				unset($_POST);
				unset($_GET['action']);
			}
			
			//add error to 'error bin'
			$myError->add($ERR['403']);
			
			//notify client of problem ;)
			return false;
		}else{																						# user has absolutely no access to this section
			//redirect to no access page
			header('Location: noaccess.php');
			exit;
		}
	}
?>