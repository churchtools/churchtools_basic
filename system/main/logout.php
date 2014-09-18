<?php 

  function logout_main() {
    logout_current_user();
    // When called per tool (api), then offer a JSEND-answer
    if (isset($_POST['directtool'])) {
      include_once(CHURCHCORE .'/churchcore_db.php');
      drupal_json_output(jsend()->success());  
    }
    else  
      header("Location: ?q=".getConf("site_startpage", "home"));
  }

