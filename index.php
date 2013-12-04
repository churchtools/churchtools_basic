<?php 
/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2013 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt 
 */

if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
  die("ChurchTools requires PHP version 5.2.0");
}

include_once("system/includes/start.php");
churchtools_main(); 

?>
