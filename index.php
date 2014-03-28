<?php 
/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2013 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt 
 */

if (!version_compare(PHP_VERSION, '5.3.0', '>=')) {
  die("Software requires PHP version 5.3.0 or newer");
}

include_once("system/includes/start.php");
churchtools_main(); 

?>
