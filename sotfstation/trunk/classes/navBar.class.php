<?
	/**************************************************************************
	* Copyright (C) 2002 - Koulikov Alexey - ak@ita-studio.com                *
	***************************************************************************
	* Made in ITA Studio - Vienna, 2002 *
	*************************************/
	
	/********
	* Class: Pair
	* Purpose: To represent a part of a string and a URL
	* 
	* Alexey Koulikov - 15.02.2002 - alex@koulikov.cc
	*****/
	class pair{
		var $name;
		var $link;
		
		/**
		 * pair::pair() - constructor
		 * 
		 * @param $name - string
		 * @param $link - string
		 * @return void
		 * 
		 * Alexey Koulikov - 15.02.2002 - alex@koulikov.cc
		 */
		function pair($name="No Name", $link=""){
			$this->setName($name);
			$this->setLink($link);
		}
		
		/**
		 * pair::setName() - to set the name of the pair
		 * 
		 * @param $name
		 * @return void
		 * 
		 * Alexey Koulikov - 15.02.2002 - alex@koulikov.cc
		 */
		function setName($name){
			$this->name = $name;
		}
		
		
		/**
		 * pair::setLink() - to set the URL of the pair
		 * 
		 * @param $link
		 * @return void
		 * 
		 * Alexey Koulikov - 15.02.2002 - alex@koulikov.cc
		 */
		function setLink($link){
			$this->link = $link;
		}
		
		/**
		 * pair::getName() - get the name of the current pair
		 * 
		 * @return string
		 * 
		 * Alexey Koulikov - 15.02.2002 - alex@koulikov.cc
		 */
		function getName(){
			return $this->name;
		}
		
		/**
		 * pair::getLink() - get the URL of the current pair
		 * 
		 * @return string
		 * 
		 * Alexey Koulikov - 15.02.2002 - alex@koulikov.cc
		 */
		function getLink(){
			return $this->link;
		}
	}
	
	class navBar{
		var $list = array();
		
		/**
		 * navBar::navBar() - el constructor
		 * 
		 * @return 
		 * 
		 * Koulikov Alexey - 15.02.2002 - alex@koulikov.cc
		 */
		function navBar($message='',$URL=''){
			if(!empty($message)){
				$this->add($message,$URL);
			}
		}
		
		/**
		 * navBar::add_nav()
		 * 
		 * @param $message - string
		 * @param $URL - string
		 * @return void
		 * 
		 * Koulikov Alexey - 15.02.2002 - alex@koulikov.cc
		 */
		function add($message,$URL){
			$topush = new pair($message,$URL);
			array_push($this->list, $topush);
		}
		
		/**
		 * navBar::get_nav() - return a navigation object at a given index
		 * 
		 * @param $n
		 * @return object (pair)
		 * 
		 * Koulikov Alexey - 15.02.2002 - alex@koulikov.cc
		 */
		function get($n=0){
			return $this->list[$n];
		}
		
		function getName($n=0){
			return $this->list[$n]->getName();
		}
		
		function getLink($n=0){
			return $this->list[$n]->getLink();
		}
		
		/**
		 * navBar::length() - return overall length of the navigation stack
		 * 
		 * @return int
		 */
		function length(){
			return count($this->list);
		}
		
		/**
		 * navBar::out() - just for tesing... please use external template handling
		 * 								 routines for data processing...
		 * 
		 * @return echo
		 */
		function out(){
			$tot = $this->length();
			for($x=0;$x<$tot - 1;$x++){				
				$toreturn .= "<a href=\"" . $this->get_nav_link($x) . "\" class=\"time3\">" . $this->get_nav_name($x) . "</a> &raquo; ";
			}
			$toreturn .= "<span class=\"time3\">" . $this->get_nav_name($x) . "</span>";
			return $toreturn;
		}
	}
?>