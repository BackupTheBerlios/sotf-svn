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
		var $xml;								// DOM XML OBJECT
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
		function addData($data){
			//encode array
			while(list($key,$val) = each($data)){
				$data[$key] = utf8_encode($val);
			}
			
			$this->root->new_child('title',$data['prog_title']);
			$this->root->new_child('alternative',$data['prog_alt_title']);
			
			//series data
			$series = $this->root->new_child('series',null);
			$series->new_child('id',$data['series_id']);
			$series->new_child('title',$data['series_title']);
			$series->new_child('description',$data['series_desc']);
			
			$this->root->new_child('stationid',SOTF_STATION_ID);
			$this->root->new_child('language',$data['prog_lang']);
			$this->root->new_child('rights',$data['prog_rights']);
			$this->root->new_child('genre',$data['prog_genre']);
			$this->root->new_child('topic',$data['prog_topic']);
			
			$this->root->new_child('description',$data['prog_desc']);
			$this->root->new_child('contributor',$data['prog_contrib']);
			$this->root->new_child('identifier',$data['prog_id']);
			
			$creator = $this->root->new_child('creator',null);
			$entity = $creator->new_child('entity',null);	
			$entity->set_attribute('type','organisation');
			$entity_name = $entity->new_child('name',SOTF_PUB);
			$entity_name->set_attribute('type','organizationname');
			$entity_acronym = $entity->new_child('name',SOTF_PUB_ACR);
			$entity_acronym->set_attribute('type','organizationacronym');
			$entity->new_child('e-mail',SOTF_PUB_MAIL);
			$entity->new_child('address',SOTF_PUB_ADR);
			$entity->new_child('logo',SOTF_PUB_LOGO);
			$entity->new_child('uri',SOTF_PUB_URI);
			
			$publisher = $this->root->new_child('publisher',null);
			$entity = $publisher->new_child('entity',null);	
			$entity->set_attribute('type','organisation');
			$entity_name = $entity->new_child('name',SOTF_PUB);
			$entity_name->set_attribute('type','organizationname');
			$entity_acronym = $entity->new_child('name',SOTF_PUB_ACR);
			$entity_acronym->set_attribute('type','organizationacronym');
			$entity->new_child('e-mail',SOTF_PUB_MAIL);
			$entity->new_child('address',SOTF_PUB_ADR);
			$entity->new_child('logo',SOTF_PUB_LOGO);
			$entity->new_child('uri',SOTF_PUB_URI);
			
			$date = $this->root->new_child('date',$data['prog_datecreated']);
			$date->set_attribute('type','created');
			
			$date = $this->root->new_child('date',$data['prog_dateissued']);
			$date->set_attribute('type','issued');
			
			$date = $this->root->new_child('date',$data['prog_dateavailable']);
			$date->set_attribute('type','available');
			
			$date = $this->root->new_child('date',$data['prog_datemodified']);
			$date->set_attribute('type','modified');
			
			$owner = $this->root->new_child('owner',null);
			$owner->new_child('auth_id',$data['owner_user_authid']);
			$owner->new_child('login',$data['owner_user_login']);
			$owner->new_child('name',$data['owner_user_name']);
			$owner->new_child('role',$data['owner_user_role']);
			
			$publisher = $this->root->new_child('publishedby',null);
			$publisher->new_child('auth_id',$data['owner_user_authid']);
			$publisher->new_child('login',$data['owner_user_login']);
			$publisher->new_child('name',$data['owner_user_name']);
			$publisher->new_child('role',$data['owner_user_role']);
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
				$fp = fopen($fileName,'w');
				fwrite($fp,$content);
				fclose($fp);
				return true;
			}else{
				trigger_error("An error occured during XML Data generation",1);
			}			
		} 
	}//end class
?>