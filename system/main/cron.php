<?php 


function do_cron() {
  global $config;
  ct_log("Cron-Job started.",2,-1,'cron');
  $btns= churchcore_getModulesSorted(false, false);
  
  foreach ($btns as $key) {
    include_once(constant(strtoupper($key))."/$key.php");
    if (function_exists($key."_cron")) {
      if ((isset($config[$key."_name"])) && ($config[$key."_name"]!=""))
        $arr=call_user_func($key."_cron");
    }
  } 
  ct_sendPendingNotifications();
  ct_log("Cron-Job finished.",2,-1,'cron');
}




function cron_main() {
  global $config;
  
    // Mails sollen jedes mal gesendet werden!
  churchcore_sendMails();
  
  if (isset($_GET["standby"])) {
    // E-Mail with feedback image 
    if (isset($_GET["mailqueue_id"])) {      
      db_query("update {cc_mail_queue} set reading_count=reading_count+1 where id=:id", 
           array(":id"=>$_GET["mailqueue_id"])); 
    }
    // Check if is time to do a normal cron job 
    if ((isset($config["cronjob_delay"]) && ($config["cronjob_delay"]>0))) {
      $last_cron=db_query("select value old,  UNIX_TIMESTAMP() act from {cc_config} where name='last_cron'")->fetch();
      if ($last_cron!=false) {
        if ($last_cron->act-$config["cronjob_delay"]>$last_cron->old) {
          db_query("update {cc_config} set value= UNIX_TIMESTAMP() where name='last_cron'");
          do_cron();
        }
      }
      else 
        db_query("insert into {cc_config} (name, value) values ('last_cron', UNIX_TIMESTAMP())");
    }  
    header('Content-Type: image/jpeg');
    echo file_get_contents(ASSETS.'/img/1x1.png');
  }
  else {
    do_cron();    
    if (isset($_GET["manual"])) {
      addInfoMessage(t("cronjob.succeed"));
      return " ";
    }
  }
}

?>
