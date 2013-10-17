<?php 

  function logout_main() {
    logout_current_user();
    // Wenn per Tool aufgerufen, dann liedere eine JSEND-Antwort
    if (isset($_POST['directtool'])) {
      include_once(drupal_get_path('module', 'churchcore') .'/churchcore_db.inc');
      drupal_json_output(jsend()->success());  
    }
    else  
      header("Location: ?q=home");
  }

?>
