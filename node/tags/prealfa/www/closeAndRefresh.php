	<html>
	  <head>
		<title>
		  Close this window automatically
		</title>
	  </head>
	  <script language="Javascript">
		function closeRefresh() {
		   var op = window.opener;
		   if (op) {
         
         url = op.location.href;
         pos = url.indexOf('#');
         if(pos >= 0) url = url.substring(0,pos);	//remove anchor
         pos = url.indexOf('&t=');
         if(pos >= 0) url = url.substring(0,pos);	//remove pervious timestamp
         pos = url.indexOf('?t=');
         if(pos >= 0) url = url.substring(0,pos);	//remove pervious timestamp

	 t = new Date();
         pos = url.indexOf('?');
         if(pos >= 0) timestr = '&t=' + t.getTime();
         else timestr = '?t=' + t.getTime();
         url = url + timestr + '#' + '<?php echo $_GET['anchor'] ?>';
         op.location.href = url;
         op.focus();

         //op.location.reload();
         //url = url + '&a=c' + '#perms';
         //op.location.hash = '<?php echo $_GET['part'] ?>';
         //op.location.reload();
       }
       window.close();
		}
	  </script>
	  <body onLoad="closeRefresh()" >
	  </body>
	</html>
