<?
	/*******************************************************
	*  Class: SendMail
	*  Purpose: To act as a gateway between the PHP mail() function
	* 					and all the possible user wants and needs. This includes
	* 					automatic generation of mail headers, and optimizing
	* 					the use of sendmail() on the server;
	*  Author: Koulikov Alexey - alex@koulikov.cc
	*  Date: 20.11.2001
	*  Version: 0.7
	******************/
	class sendMail{
	
		var $from_mail;
		var $from_name;
		var $to = "great";		
		var $cc;
		var $bcc;
		var $subject;
		var $message;
		var $attachment;
		var $message_type;
		
		/********************
		* Constructor
		**/
		function sendMail($to,$from,$subject,$message){
			$to = $this->setTo($to);
			$from_mail = $this->setFrom($from);
			$from_name = $this->getFromName();
			$subject = $this->setSubject($subject);
			$message = $this->setMessage($message);
			$this->setType();
			$this->setFromName();
		}
		
		/********************
		* Actually send the e-mail, return true if sent, 
		* and false otherwise
		**/
		function send(){
			$to = $this->getTo();
			$header = $this->getHeader();
			$subject = $this->getSubject();
			$msg = $this->getMessage();
			if(@mail($to,$subject,$msg,$header)){
				return true;
			}else{
				return false;
			}			
		}
		
		/********************
		* Returns all the official recepients
		**/
		function getTo(){
			return $this->to;
		}
		
		/********************
		* Return all the CC recepients
		**/
		function getCC2(){
			return $this->cc;		
		}
		
		/********************
		* Return all the BCC recepients
		**/
		function getBCC2(){
			return $this->bcc;
		}
		
		/********************
		* Compose a mail header
		**/
		function getHeader(){
			$header = "From: " . $this->getFromName() . " <" . $this->getFrom() . ">\r\n";
			$header .= "Reply-To: " . $this->getFrom() . "\r\n";
			$header .= "X-Mailer: PHP/" . phpversion() . "\r\n";
			$header .= "MIME-Version: 1.0\r\n";	
			if($this->getType()==0){				
			}else{
				$header .= "Content-type: text/html; charset=iso-8859-1\r\n";
			}
			
			if(strlen($this->getCC())>0){
				$header .= "Cc: " . $this->getCC() . "\r\n";
			}
			
			if(strlen($this->getBCC())){
				$header .= "Bcc: " . $this->getBCC() . "\r\n";
			}
			
			return $header;
		}
		
		/********************
		* Get the actual message
		**/
		function getMessage(){
			return $this->message;
		}
		
		/********************
		* Get the mail subject
		**/
		function getSubject(){
			return $this->subject;
		}
		
		/********************
		* Set the mail subject
		**/
		function setSubject($subject_toset,$default="No Subject"){
			if(strlen($subject_toset)>0)
			{
				$this->subject=$subject_toset;
			}
			else
			{
				$this->subject=$default;	
			}
		}
		
		/********************
		* Set the actual message
		**/
		function setMessage($message){
			$this->message=$message;
		}
		
		/********************
		* Set all the recepients of this e-mail
		**/
		function setTo($to_set){
			$this->to=$to_set;
		}
		
		/********************
		* Set the sender of the e-mail
		**/
		function setFrom($to_set="noreply"){
			$this->from_mail = $to_set;
		}
		
		/********************
		* Set the sender's name
		**/
		function setFromName($to_set="noreply"){
			$this->from_name = $to_set;
		}
		
		/********************
		* Get the senders e-mail
		**/
		function getFrom(){
			return $this->from_mail;
		}
		
		/********************
		* Get the sender's name
		**/
		function getFromName(){
			return $this->from_name;
		}
		
		/********************
		* Set all the CC recepients of this e-mail
		**/
		function setCC($cc){
			$this->cc = $cc;
		}
		
		/********************
		* Set all the BCC recepients of this email
		**/
		function setBCC($bcc){
			$this->bcc = $bcc;
		}
		
		/********************
		* Get all the CC recepients of this e-mail
		**/
		function getCC(){
			return $this->cc;
		}
		
		/********************
		* Get all the BCC recepients of this email
		**/
		function getBCC(){
			return $this->bcc;
		}
		
		/********************
		* Get the message type, returns 0 for text, 1 for HTML;
		**/
		function getType(){
			return $this->message_type;
		}
		
		/********************
		* Set message type, either text or HTML
		**/
		function setType($type=0){
			switch ($type){
				case 0:{$type = 0; break;}
				default:{$type = 0;}
			}
			$this->message_type = $type;
		}			
	}//end class
?>