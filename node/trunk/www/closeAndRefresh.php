	<html>
	  <head>
		<title>
		  Close this window automatically
		</title>
	  </head>
	  <script>
		function closeRefresh() {
		   var op = window.opener;
		   if (op) {
         op.location.reload();
         op.focus();
       }
       window.close();
		}
	  </script>
	  <body onLoad="closeRefresh()" >
	  </body>
	</html>
