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
         if(pos >= 0) {
           url = url.substring(0,pos);
         }
         //url = url + '#' + '<?php echo $_GET['part'] ?>';
         url = url + '#perms';
         op.location.href = url;
         op.location.reload();
         /*
         op.focus();
         op.location.hash = '<?php echo $_GET['part'] ?>';
         //op.location.reload();
         */
       }
       window.close();
		}
	  </script>
	  <body onLoad="closeRefresh()" >
	  </body>
	</html>
