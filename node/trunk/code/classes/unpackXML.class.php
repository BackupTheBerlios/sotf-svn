<?php

	/*
	* class - unpackXML
	* 				will create an associative array from an XML file.
	* 
	* @author 	Kulikov Alexey <alex@pvl.at, alex@ita-studio.com>
	* @date			01.02.03
	* @version 	1.0
	* @requires DOMXML extension enabled in PHP
	****/
	class unpackXML{
		var $xml;
		var $root;
		var $error = false;
		var $data = array();
		var $encoding;
		var $outencoding = "iso-8859-1";
	
		/**
		 * unpackXML::unpackXML() - constructor
		 * 
		 * @param $file (absolute path to file from htdocs root)
		 * @return 
		 */
		function unpackXML($file){
			if(!$this->xml = domxml_open_file($file)){
				$this->error = true;
				return array('error'=>'File ' . $file . ' not found at specified location. DOMXML needs an absolute path to this file starting from htdocs root or a URI!');
			}else{
				//get encoding - doesn't
				$myfile = fopen($file, "r");
				$contents = fread($myfile, 64);
				fclose($myfile);
				eregi("encoding=\"(.*)\"\?>", $contents, $encoding);
				
				if(!empty($encoding[1]) and $encoding[1] != 'iso-8859-1'){
					$this->encoding = $encoding[1];
				}else{	
					$this->encoding = "UTF-8";
				}
				
				//set root
				$this->root = $this->xml->root();
			}
		}
		
		
		/**
		 * unpackXML::process()
		 * 
		 * intialize the recursive parser and return result
		 * 
		 * @return (array) 
		 */
		function process(){
			$this->parse($this->root, $this->data);
			if(!empty($this->data)){
				return $this->data;
			}
			return false;
		}
		
		
		/**
		 * unpackXML::parse()
		 * 
		 * now, this is some sweet piece of recursive code that will parse
		 * any xml file and create a multi-level associative array. All done
		 * in 15 lines of code. It was worth thinking about, respect!
		 * 
		 * @access private
		 * @param $reference (XMLDOM Object)
		 * @param $data (array)
		 * @return recursive call 
		 */
		function parse($reference, &$data){
			$children = $reference->children();
			if(count($children)>1){
				foreach($children as $child){
					//not interested in DOMTEXT elements
					if($child->type == 3){
						continue;
					}
					
					//type cast
					if($child->get_attribute("type") != ''){
						$name = $child->get_attribute("type");
					}else{
						//id cast
						if($child->get_attribute("id") != ''){
							$name = $child->get_attribute("id");
						}else{
							$name = $child->node_name();
						}
					}
					$data[$name] = array();
					$this->parse($child,$data[$name]);
				}
			}else{
				//charset converter
				if($this->encoding == $this->outencoding){	//don't convert
					$data = $reference->get_content();
				}else{
					if(!$data = iconv($this->encoding,$this->outencoding,$reference->get_content())){
						$data = $reference->get_content();
					}	//convert
				}
				$data = str_replace("%%rgt%%",">",$data);
				$data = str_replace("%%lgt%%","<",$data);
				$data = ereg_replace("[\r\n]{2,}","<br />",$data);
				//$data = nl2br($data);
			}
		}
	}
	
	/*
	//the domxml extension NEEDS an absolute path to the xml file
	//either local or a URI
	//example file -- http://www.streamonthefly.com/307.xml
	$myPack = new unpackXML("http://www.streamonthefly.com/307.xml");
	
	echo "<pre>";
	print_r($myPack->process());
	echo "</pre>";
	*/
?>