<?
	class helpBox{
		var $title;
		var $content;
		var $id;
		var $width;
		
		
		/**
		 * helpBox::helpBox()
		 * 
		 * el Constructor
		 * 
		 * @param $id (int)
		 * @param $width (int)
		 * @return (void)
		 */
		function helpBox($id, $width=300){
			$this->setWidth($width);
			if($this->fetch($id)){
				$this->out();
			}
		}
		
		
		/**
		 * helpBox::fetch()
		 * 
		 * get all the data from the database, will return TRUE
		 * on success
		 * 
		 * @param $id (int)
		 * @return (bool) 
		 */
		function fetch($id){
			global $db;
			
			//execure query
			$res = $db->getRow("SELECT * FROM help_texts WHERE id = '$id'",DB_FETCHMODE_ASSOC);
			
			//has there been a hit?
			if(!empty($res)){	#yes
				$this->setID($res['id']);
				$this->setTitle($res['title']);
				$this->setContent($res['content']);
				return true;
			}else{						#no
				return false;
			}
		}
		
		
		/**
		 * helpBox::setID()
		 * 
		 * set the current help topic ID
		 * 
		 * @param $id (int)
		 * @return (void)
		 */
		function setID($id){
			$this->id = $id;
		}
		
		
		/**
		 * helpBox::setTitle()
		 * 
		 * set the current topic Title
		 * 
		 * @param $title (string)
		 * @return (void)
		 */
		function setTitle($title){
			$this->title = $title;
		}
		
		
		/**
		 * helpBox::setContent()
		 * 
		 * set the current topic Content
		 * 
		 * @param $content (string)
		 * @return (void)
		 */
		function setContent($content){
			$this->content = $content;
		}
		
		
		/**
		 * helpBox::setWidth()
		 * 
		 * set the width of the output helpbox
		 * 
		 * @param $width (int)
		 * @return (void)
		 */
		function setWidth($width){
			$this->width = $width;
		}
		
		
		/**
		 * helpBox::getID()
		 * 
		 * returns the current topic ID
		 * 
		 * @return (int)
		 */
		function getID(){
			return $this->id;
		}
		
		
		/**
		 * helpBox::getTitle()
		 * 
		 * returns the title of the current help topic
		 * 
		 * @return (string)
		 */
		function getTitle(){
			return $this->title;
		}
		
		
		/**
		 * helpBox::getContent()
		 * 
		 * return the contents of the current help topic
		 * 
		 * @return (string)
		 */
		function getContent(){
			return $this->content;
		}
		
		
		/**
		 * helpBox::getText()
		 * 
		 * this is an alias to getContent()
		 * 
		 * @return (string)
		 */
		function getText(){
			return $this->getContent();
		}
		
		
		/**
		 * helpBox::getWidth()
		 * 
		 * returns the numerical value for the width of the new help box
		 * 
		 * @return (int)
		 */
		function getWidth(){
			return $this->width;
		}
		
		
		/**
		 * helpBox::out()
		 * 
		 * output the current help box to smarty
		 * 
		 * @return (void)
		 */
		function out(){
			global $smarty;
			$smarty->assign(array(
														'show_help'=>TRUE,
														'help_title'=>$this->getTitle(),
														'help_text'=>$this->getText(),
														'help_width'=>$this->getWidth()
														));
		}
	}
?>