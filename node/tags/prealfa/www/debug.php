<?php

if($_GET['on']) {
  setcookie('debug','yes');
} else {
  setcookie('debug','no');
}

header ("Location: " . $_GET['okURL']);

?>