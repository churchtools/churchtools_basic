<?php 

  function logout_main() {
    logout_current_user();
    // When called per tool (api), then offer a JSEND-answer
    if (isset($_POST['directtool'])) {
      include_once(drupal_get_path('module', 'churchcore') .'/churchcore_db.inc');
      drupal_json_output(jsend()->success());  
    }
    else  
      header("Location: ?q=".variable_get("site_startpage", "home"));
  }

?>
