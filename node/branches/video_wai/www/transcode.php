<?php

$output = shell_exec("ffmpeg -i /var/www/sotf/node_3/users/tm02103/mpg.mpg -r 16 -s qcif -ar 22050 -ab 48 -ac 1 /var/www/sotf/node_3/users/tm02103/mpg.flv");
print $output;

?>