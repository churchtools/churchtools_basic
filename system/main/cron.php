<?php

/**
 * execute cron job for all modules
 */
function do_cron() {
  global $config;
  ct_log("Cron-Job started.", 2, -1, 'cron');
  
  $btns = churchcore_getModulesSorted(false, false);
  foreach ($btns as $key) {
    include_once (constant(strtoupper($key)) . "/$key.php");
    if (function_exists($key . "_cron")) {
      if (getConf($key . "_name")) $arr = call_user_func($key . "_cron");
    }
  }
  ct_sendPendingNotifications();
  ct_log("Cron-Job finished.", 2, -1, 'cron');
}

/**
 * main cron function
 * @return string
 */
function cron_main() {
  global $config;
  
  // always send mails
  churchcore_sendMails();
  
  if (readVar("standby")) {
    // email with feedback image
    if ($id = readVar("mailqueue_id")) {
      db_query("UPDATE {cc_mail_queue} 
                SET reading_count=reading_count+1 
                WHERE id=:id", 
                array (":id" => $id));
    }
    // Check if it's time to do a normal cron job
    if (readConf("cronjob_delay") > 0) {
      $last_cron = db_query("SELECT value old,  UNIX_TIMESTAMP() act 
                             FROM {cc_config} 
                             WHERE name='last_cron'")
                   ->fetch();
      if ($last_cron) {
        if ($last_cron->act - $config["cronjob_delay"] > $last_cron->old) {
          db_query("UPDATE {cc_config} 
                    SET VALUE= UNIX_TIMESTAMP() 
                    WHERE name='last_cron'");
          do_cron();
        }
      }
      else
        db_query("INSERT INTO {cc_config} (name, value) 
                  VALUES ('last_cron', UNIX_TIMESTAMP())");
    }
    
    header('Content-Type: image/jpeg');
    echo file_get_contents(ASSETS . '/img/1x1.png');
  }
  else {
    do_cron();
    if (readVar("manual")) {
      addInfoMessage(t("cronjob.succeed"));
      return " ";
    }
  }
} 
