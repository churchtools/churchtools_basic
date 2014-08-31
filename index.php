<?php
/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2013 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt 
 */
try {
  
  if (! version_compare ( PHP_VERSION, '5.3.0', '>=' )) {
    throw new Exception ( "Software requires PHP version 5.3.0 or newer" );
  }
  //TODO: find a good place for constants.php
  require ("system/includes/constants.php");
  include_once (CHURCHCORE ."/functions.php");
  include_once (INCLUDES."/start.php");
  churchtools_main ();
}

catch ( SqlException $e ) {
//  TODO: get sql and show it to admin only
//  if (DEBUG) {
//  echo "<h3>PDO-Error:</h3>", $db->errorCode(), "<br>", $db->lastQuery(), '<br>';
//  }
//  else {
//  echo "<h3>Database-Error:</h3>", "There is an error";
//  }
  
  CTException::reportError ( $e );
}
catch ( CTException $e ) {
  $e->reportError ( $e );
}
catch ( Exception $e ) {
  echo '
	<div style="margin:2em;padding:2em;background-color:#ffdddd">
      <h3>Sorry, but there is an Error:</h3>
      <p><br/>'. $e->getMessage (). '</p>
    </div>';
}

?>
