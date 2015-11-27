<?php

/**
 * execute cron job for all modules
 */
function do_cron() {
  global $files_dir;
  ct_log("Cron-Job started.", 2, -1, 'cron');

  //delete temporary files (and sessions) older than one day
  $tempDir = $files_dir . '/tmp/';
  foreach (array_slice(scandir($tempDir), 2) as $file) {
    $path = $tempDir . $file;
    if (is_file($path) && filemtime($path) < time() - (3600 * 24)) {
      unlink($path);
    }
  }
  //launch the cronjobs of the individual CT modules
  $modulesSorted = churchcore_getModulesSorted(false, false);
  foreach ($modulesSorted as $key) {
    include_once (constant(strtoupper($key)) . "/$key.php");
    if (function_exists($key . "_cron") && getConf($key . "_name")) {
      call_user_func($key . "_cron");
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
  
  // always send reminders and reminders
  churchcore_sendReminders();
  churchcore_sendMails();
  
  if (getVar("standby")) {
    // email with feedback image
    if ($id = getVar("mailqueue_id")) {
      db_query("UPDATE {cc_mail_queue} 
                SET reading_count = reading_count + 1 
                WHERE id=:id", 
                array (":id" => $id));
    }
    // Check if it's time to do a normal cron job
    if (getConf("cronjob_delay") > 0) {
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
    }
    
    header('Content-Type: image/gif');
    //tiniest transparent GIF, credit: http://probablyprogramming.com/2009/03/15/the-tiniest-gif-ever
    echo base64_decode('R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
  }
  else {
    do_cron();
    if (getVar("manual")) {
      addInfoMessage(t("cronjob.succeed"));
      return " ";
    }
  }
} 
