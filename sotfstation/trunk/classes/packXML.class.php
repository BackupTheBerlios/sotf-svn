<?
	/*
	* class - packXML
	* 				will prepare data from DB for XML export and generate
	* 				neccessary file upon request.
	* 
	* @author Kulikov Alexey <alex@pvl.at, alex@ita-studio.com>
	* @date		01.02.03
	* @version 1.0
	* @requires DOMXML extension enabled in PHP
	****/
	class packXML{
		var $xml;									// DOM XML OBJECT
		var $root;								// DOM XML root node
		
		/**
		 * packXML::packXML() - constructor
		 * 
		 * @return void 
		 */
		function packXML($rootName){
			//create xml parser
			$this->xml = domxml_new_xmldoc('1.0');
			
			//set root node
			$this->root = domxml_add_root($this->xml,$rootName);
		}
		
		
		/**
		 * packXML::addSeriesData() - fill xml document with series related data
		 * 
		 * @param $sData (array)
		 * @return (void)
		 */
		function addSeriesData($sData){
			$sNode = $this->root->new_child('series',null);
			$sNode->new_child('id',$sData['series_id']);		
			$sNode->new_child('title',$sData['series_title']);
			$sNode->new_child('description',$sData['series_desc']);		
		}
		
		
		/**
		 * packXML::addProgrammeData() - fill xml document with programme related data
		 * 
		 * @param $pData (array)
		 * @return (void)
		 */
		function addProgrammeData($pData){
			$pNode = $this->root->new_child('programme',null);
			$pNode->new_child('id',$pData['prog_id']);
			$pNode->new_child('title',$pData['prog_title']);
			$pNode->new_child('alternative_title',$pData['prog_alt_title']);
			
			//sub-child -- creator
			$creator = $pNode->new_child('creator',null);
			$creator->new_child('name',$pData['user_name']);
			$creator->new_child('role',$pData['user_role']);
			
			//sub-child -- keywords
			if(!empty($pData['prog_keywords'])){
				$keywords = $pNode->new_child('keywords',null);
				$pData['prog_keywords'] = explode(",",$pData['prog_keywords']);
				reset($pData['prog_keywords']);
				while(list($key,$val) = each($pData['prog_keywords'])){
					$child = $keywords->new_child('keyword',trim($val));
					$child->set_attribute('id',$key);
				}
			}
			
			//back in track
			$pNode->new_child('description',$pData['prog_desc']);
			
			//sub-child -- publisher
			$pub = $pNode->new_child('publisher',null);
			$pub->new_child('name',SOTF_PUB);
			$pub->new_child('uri',SOTF_PUB_URI);
			$pub->new_child('logo',SOTF_PUB_LOGO);
			
			//sub-child -- contributors
			if(!empty($pData['prog_contrib'])){
				$contrib = $pNode->new_child('contributors',null);
				$pData['prog_contrib'] = explode(",",$pData['prog_contrib']);
				reset($pData['prog_contrib']);
				while(list($key,$val) = each($pData['prog_contrib'])){
					$child = $contrib->new_child('contributor',trim($val));
					$child->set_attribute('id',$key);			
				}
			}
			
			//sub-child -- dates
			$dates = $pNode->new_child('dates',null);
			$dates->new_child('initial',$pData['prog_intime']);
			$dates->new_child('created',$pData['prog_datecreated']);
			$dates->new_child('issued',$pData['prog_dateissued']);
			$dates->new_child('available',date("Y-m-d H:i:s"));
			
			//back in track
			$pNode->new_child('language',$pData['prog_lang']);
			$pNode->new_child('topic',$pData['prog_topic']);
			$pNode->new_child('genre',$pData['prog_genre']);
			
			//sub child -- rights
			if(!empty($pData['prog_rights'])){
				$rights = $pNode->new_child('rights',null);
				$pData['prog_rights'] = explode(",",$pData['prog_rights']);
				reset($pData['prog_rights']);
				while(list($key,$val) = each($pData['prog_rights'])){
					$child = $rights->new_child('rights_holder',trim($val));
					$child->set_attribute('id',$key);
				}
			}
		}
			
		
		/**
		 * packXML::process() - create XML data from arrayz
		 * 
		 * @return (string) or FALSE 
		 */
		function process(){
			return $this->xml->dumpmem();
		}
		
		
		/**
		 * packXML::toScreen() - output XML Data to browser
		 * 
		 * @return (echo) 
		 */
		function toScreen(){
			if($content = $this->process()){
				echo "<pre>";
				echo nl2br(htmlspecialchars($content));
				echo "</pre>";
				
				echo $content;
			}else{
				trigger_error("An error occured during XML Data generation",1);
			}
		}
		
		
		/**
		 * packXML::toFile() - output XML Data to file
		 * 
		 * @return (bool) 
		 */
		function toFile($fileName = 'export.xml'){
			if($content = $this->process()){
				$fp = fopen('sync/' . $fileName,'w');
				fwrite($fp,$content);
				fclose($fp);
				return true;
			}else{
				trigger_error("An error occured during XML Data generation",1);
			}			
		} 
	}//end class
?>