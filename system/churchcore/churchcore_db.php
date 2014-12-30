<?php

// TODO: this file contains functions not related to database, maybe they should put in a separate functions file?
define("CDB_LOG_PERSON", 'person');
define("CDB_LOG_GROUP", 'group');
define("CDB_LOG_MASTERDATA", 'masterData');
define("CDB_LOG_TAG", 'tag');

/**
 * Delete i18n related .js files
 *
 * TODO: not needed, since only used one time in db_update function?
 */
function cleanI18nFiles() {
  global $files_dir;
  cleandir("$files_dir/files/messages/");
}

/**
 * TODO: only used one time -> not needed
 *
 * @param string $modulename
 */
/*
 * function loadI18nFile($modulename) {
 *   global $config;
 *   $i18n = new TextBundle("system/$modulename/resources/messages");
 *   $i18n->load($modulename, $config["language"]);
 *   return $i18n;
 *  }
 */

/**
 * Get file name for i18n javascript file.
 *
 * If not exists, it will be created.
 * TODO: should be renamed to getI18nFile
 *
 * @param string $modulename
 *
 * @return string; filename
 */
function createI18nFile($modulename) {
  global $config, $files_dir;

  if (!file_exists("$files_dir/files/messages/")) mkdir("$files_dir/files/messages", 0777, true);

  $filename = "$files_dir/files/messages/$modulename" . "_" . $config["language"] . ".js";
  if (!file_exists($filename)) {
    $i18n = new TextBundle("system/$modulename/resources/messages");
    $i18n->load($modulename, $config["language"]);
    $i18n->writeJSFile($filename, $modulename);
  }
  return $filename;
}

/**
 * Get information to a domain in database domain_type like person, event
 * @param unknown $domainType
 * @throws CTException
 * @return multitype:string
 */
function churchcore_getInfosForDomain($domainType) {
  switch ($domainType) {
    case 'person' : return array("tablename"=>"cdb_person");
    case 'event' : return array("tablename"=>"cc_cal", "modulename"=>"churchcal");
  }
  throw new CTException("Domain $domainType not found!");
}

/**
 * Send all pending reminders. Will be called by cron job
 */
function churchcore_sendReminders() {
  $reminders = db_query("SELECT r.*, p.id person_id, p.email, p.vorname, p.name, p.spitzname FROM {cc_reminder} r, {cdb_person} p
                         WHERE reminddate < now() AND r.person_id = p.id AND mailsenddate IS NULL");
  foreach ($reminders as $reminder) {
    $domaininfos = churchcore_getInfosForDomain($reminder->domain_type);
    $raw = db_query("SELECT * FROM {".$domaininfos["tablename"]."}
                     WHERE id = :id", array(":id" => $reminder->domain_id))->fetch();
    $domain = array();
    foreach ($raw as $key=>$d) {
      if ($d!=null && $d!="")
      switch ($key) {
      	case "bezeichnung": $domain[t("caption")] = $d; break;
      	case "startdate"  : $domain[t("start.date")] = churchcore_stringToDateDe($d); break;
      	case "enddate"  : $domain[t("end.date")] = churchcore_stringToDateDe($d); break;
      	case "repeat_until"  : $domain[t("repeat.to")] = churchcore_stringToDateDe($d); break;
      }
    }
    if ($reminder->email && $domain) {
      $data = array(
        'surname'     => $reminder->vorname,
        'name'        => $reminder->name,
        'nickname'    => ($reminder->spitzname ? $reminder->spitzname : $reminder->vorname),
        'caption'     => $domain->bezeichnung,
        'notifyName'  => t($reminder->domain_type),
        'link'        => $site_url."?q=".$domaininfos["modulename"]."&id=".$reminder->domain_id,
        'fields'      => $domain
      );
      $content = getTemplateContent('email/reminder', 'churchcore', $data);
      churchcore_systemmail($p->email, "[" . getConf('site_name') . "] " . t('reminder.for.x', t($reminder->domain_type)), $content, true);
      echo "sened mail";
    }
    db_query("UPDATE {cc_reminder} SET mailsenddate=NOW()
              WHERE person_id = :person_id AND domain_type = :domain_type
                           AND domain_id = :domain_id",
                         array(':person_id' => $reminder->person_id,
                               ':domain_type' => $reminder->domain_type,
                               ':domain_id' => $reminder->domain_id));
  }
}

/**
 * Get reminders for person person_id
 * @param unknown $person_id
 * @param unknown $listOfDomainTypes List of domainTypes, seperate by comma
 * @return multitype:
 */
function ct_getMyReminders($person_id, $listOfDomainTypes) {
  $reminders = db_query("SELECT * FROM {cc_reminder} r
                         WHERE person_id = :person_id AND domain_type IN ('$listOfDomainTypes')",
                         array(':person_id' => $person_id));
  $ret = array();
  foreach ($reminders as $reminder) {
    $ret[$reminder->domain_type][$reminder->domain_id] = $reminder->reminddate;
  }
  return $ret;
}

/**
 * Saves the reminder or delete if no reminddate is given. used by AJAX call
 * @param unknown $params
 * @return multitype:unknown
 */
function churchcore_saveReminder($params) {
  global $user;
  $params["person_id"] = $user->id;

  $i = new CTInterface();
  $i->setParam("domain_id");
  $i->setParam("domain_type");
  $i->setParam("person_id");
  $i->setParam("reminddate", false);

  if (!isset($params["reminddate"])) {
    $id = db_delete("cc_reminder")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("person_id", $params["person_id"], "=")
      ->condition("domain_id", $params["domain_id"], "=")
      ->condition("domain_type", $params["domain_type"], "=")
      ->execute(false);
  }
  else {
    $reminder = db_query("SELECT * FROM {cc_reminder} r
                         WHERE person_id = :person_id AND domain_type = :domain_type
                           AND domain_id = :domain_id",
                         array(':person_id' => $person_id,
                               ':domain_type' => $params["domainType"],
                               ':domain_id' => $params["domainId"]))->fetch();
    if (!$reminder) {
      $id = db_insert("cc_reminder")
          ->fields($i->getDBInsertArrayFromParams($params))
          ->execute(false);
    }
    else {
      $params["id"] = db_insert("cc_reminder")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->condition("id", $params["id"], "=")
        ->execute(false);
    }
  }
  return array("id" => $params["id"]);
}

/**
 * log $txt into cdb_log and call ct_sendPendingNotifications
 *
 * @param string $domain_type
 * @param int $domain_id
 * @param string $txt
 * @param int $loglevel; default: 2
 */
function ct_notify($domain_type, $domain_id, $txt, $loglevel = 2) {
  global $user;
  ct_log($txt, $loglevel, $domain_id, $domain_type);

  // TODO: please explain
  $notify = db_query('SELECT * FROM {cc_notification} n, {cc_notificationtype} nt
                      WHERE n.notificationtype_id = nt.id AND n.person_id = :p_id AND n.domain_id = :domain_id
                        AND n.domain_type = :domain_type AND nt.delay_hours = 0',
                      array (':p_id' => $user->id,
                             ":domain_id" => $domain_id,
                             ":domain_type" => $domain_type,
                      ))->fetch();
  if ($notify) {
    ct_sendPendingNotifications(0);
  }
}

/**
 * Send pending notifications.
 *
 * @param string $max_delayhours; default null for all.
 *
 */
function ct_sendPendingNotifications($max_delayhours = null) {
  $res = churchcore_getTableData("cc_notificationtype", "delay_hours", ($max_delayhours != null ? "delay_hours<=$max_delayhours" : ""));

  foreach ($res as $n) {
    // Check if there is a pending notifications for a person and domain_type
    $personANDtypes = db_query(
         'SELECT n.person_id, n.domain_type FROM {cc_notification} n
          WHERE n.notificationtype_id=:nt_id AND (lastsenddate IS NULL OR
          (TIME_TO_SEC(TIMEDIFF(NOW(), lastsenddate)) / 3600)>:delay_hours)
          GROUP BY n.person_id, n.domain_type',
          array (':nt_id' => $n->id,
                 ':delay_hours' => $n->delay_hours
          ));

    // Collect all notifications in this type for each person and domain_type
    foreach ($personANDtypes as $personANDtype) {
      $notis = db_query('SELECT * FROM {cc_notification} n
                         WHERE n.person_id=:person_id and n.notificationtype_id=:nt_id and n.domain_type=:dt_id',
                         array (':person_id' => $personANDtype->person_id,
                                ':nt_id' => $n->id,
                                ':dt_id' => $personANDtype->domain_type
                         ));

      // Get all logs for each notification after? each lastsenddate
      foreach ($notis as $noti) {
        $logs = db_query("SELECT l.txt AS text, DATE_FORMAT(datum, '%e.%c.%Y %H:%i') date FROM {cdb_log} l
                          WHERE domain_type=:domain_type AND domain_id=:domain_id
                            AND (:lastsenddate IS NULL OR TIME_TO_SEC(TIMEDIFF(datum, :lastsenddate))>0)
                          ORDER BY datum DESC",
                          array (":domain_type" => $personANDtype->domain_type,
                                 ":domain_id" => $noti->domain_id,
                                 ":lastsenddate" => $noti->lastsenddate,
                          ));
        $messages = array();
        foreach ($logs as $log) $messages[] = $log;
      }
      if (count($messages)) {
        $p = churchcore_getUserById($personANDtype->person_id);
        if ($p && $p->email) {
          $data = array(
            'surname'     => $p->vorname,
            'name'        => $p->name,
            'nickname'    => ($p->spitzname ? $p->spitzname : $p->vorname),
            'notifyName'  => t($personANDtype->domain_type),
            'notifyType'  => $n->bezeichnung,
            'messages'    => $messages,
          );
          $content = getTemplateContent("email/notification", 'churchcore', $data);
          churchcore_systemmail($p->email, "[" . getConf('site_name') . "] " . t('news.for.abo.x', t($personANDtype->domain_type)), $content, true);
        }
      }

      // update send date for notification
      $notis = db_query('UPDATE {cc_notification} n SET lastsenddate=NOW()
                         WHERE n.person_id=:person_id AND n.notificationtype_id=:nt_id AND n.domain_type=:dt_id',
                         array (':person_id' => $personANDtype->person_id,
                                ':nt_id' => $n->id,
                                ':dt_id' => $personANDtype->domain_type,
                         ));
    }
  }
}

/**
 * check if a new email should be send, write something into DB
 *
 * @param int $personId
 * @param string $mailtype, f.e. remindService
 * @param int $domainId
 * @param int $interval
 * @return boolean
 */
function ct_checkUserMail($personId, $mailtype, $domainId, $interval) {
  $res = db_query("SELECT letzte_mail FROM {cc_usermails}
                   WHERE person_id=:person_id AND mailtype=:mailtype AND domain_id=:domain_id",
      array (":person_id" => $personId,
          ":mailtype" => $mailtype,
          ":domain_id" => $domainId,
      ))->fetch();
      $dt = new DateTime();
      if (!$res) {
        db_insert("cc_usermails")
        ->fields(array ("person_id" => $personId,
        "mailtype"  => $mailtype,
        "domain_id" => $domainId,
        "letzte_mail" => $dt->format('Y-m-d H:i:s'),
        ))->execute();

        return true; //TODO: use on duplicate update or replace
      }
      else {
        $lm = new DateTime($res->letzte_mail);
        $dt = new DateTime(date("Y-m-d", strtotime("-" . $interval . " hour")));
        if ($lm < $dt) {
          $dt = new DateTime();
          db_query("UPDATE {cc_usermails} SET letzte_mail=:dt
                WHERE person_id=:person_id AND mailtype=:mailtype AND domain_id=:domain_id",
                array(":person_id" => $personId,
                ":mailtype"  => $mailtype,
                ":domain_id" => $domainId,
                ":dt" => $dt->format('Y-m-d H:i:s'),
                ));
                return true;
        }
      }
      return false;
}

/**
 *
 * Function renamed to prepareForLog and moved into class CTAbstractModule, where its used only
 *
 * @param unknown $params
 * @return string
 *
 * function makeParamsLoggable($params) {
 */

function ajax() {
  return new CTAjaxHandler();
}

/**
 * not used anywhere
 */
class JSONResultObject {
  public $ok = false;

  function setStatus($ok = true) {
    $this->ok = $ok;
  }

}

/**
 * shorthand
 *
 * @return new JSEND
 */
function jsend() {
  return new JSEND();
}

/**
 * description
 */
class JSEND {

  /**
   *
   * @param string $data
   * @return array
   */
  function success($data = null) {
    return array ("status" => "success", "data" => $data); // ."" weggenommen am 26.3.2013 f�r Datenr�ckgabe. Warum .""??
  }

  /**
   * Info about failed action, lightweight errors like record already exists.
   *
   * @param unknown $data
   * @return array
   */
  function fail($data) {
    return array ("status" => "fail", "data" => $data . "");
  }

  /**
   * Errorbox for serious errors, restart application!
   *
   * @param unknown $message
   * @param string $data
   * @return array
   */
  function error($message, $data = null) {
    if ($data == null) return array ("status" => "error", "message" => $message . "");
    else return array ("status" => "fail", "message" => $message . "", "data" => $data);
  }

}

/**
 * TODO: why not use date('Y-m-d H:i:s') rather then this function and put 'Y-m-d H:i:s' into a constant?
 */
function current_date() {
  $dt = new DateTime();
  return $dt->format('Y-m-d H:i:s');
}

/**
 * Replaces links and linebreaks with <a> and <br>
 *
 * @param string $text
 *
 * @return string
 */
function htmlize($text) {
  $text = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $text);
  $text = nl2br($text);
  return $text;
}

/**
 * Create html link
 *
 * TODO: why not use http_build_query?
 *
 * @param string $name
 * @param string $url
 * @param mixed $params, string, array or object
 *
 * @return string
 */
function l($name, $url, $params = null) {

//   if ($params == null) $param = "";
//   else {
//     $param = "?";
//     $first = true;
//     foreach ($params as $key => $p) {
//       if (!$first) $param .= "&";
//       $first = false;
//       $param .= "$key=$p";
//     }
//   }

  $params = ($params ? '?' . http_build_query($parameter, '', '&') : '');
  return '<a href="' . $url . $param . '">' . $name . '</a>';
}

/**
 * Add error div with message to $content
 *
 * @param string $message
 */
function addErrorMessage($message) {
  global $content;
  $content .= "<div class='alert alert-error'>$message</div>";
}

/**
 * Add info div with message to $content
 *
 * TODO: use a constant with content hide_automatically in function calls?
 *
 * @param string $message
 * @param string $hide; default:false
 */
function addInfoMessage($message, $hide = false) {
  global $content;
  $content .= "<div class='alert alert-info " . ($hide ? "hide_automatically" : "") . "'>$message</div>";
  // $content.="<div class='alert alert-info $hide">$message</div>";
}

/**
 * get translation of $txt, insert $data into placeholders like {0}
 * changed to use more then one placeholder by passing all args to getText.
 *
 * @param string $txt
 * @param mixed $data; as much arguments as needed to replace placeholders
 *
 * @return string, translation of $txt
 */
function t($txt) {
  global $i18n;

  if (isset($i18n)) {
    // calls the function with the values in $data as arguments like $i18n->getText($data[0], $data[1], ...)
    $return = call_user_func_array(array ($i18n, "getText"), func_get_args());
  }
  return $return ? $return : $txt;
}

/**
 * TODO: add code or delete it
 * @return NULL
 */
function language_default() {
  return null;
}

/**
 * Write user actions to log table
 *
 * @param string $txt
 * @param int $level; default:3; 2, 1. 3=small 2=appears in person details 1=important!!
 * @param int $personid; needed if related to PersonId
 */
function ct_log($txt, $level = 3, $domainid = -1, $domaintype = CDB_LOG_PERSON, $writeaccess_yn = 0, $_user = null) {
  global $user;
  if ($_user == null) $_user = $user;
  $dt = new DateTime();
  db_query("INSERT INTO {cdb_log} (person_id, level, datum, domain_id, domain_type, schreiben_yn, txt)
            VALUES (:person_id, :level, :datum, :domain_id, :domain_type, :schreiben_yn, :txt)",
            array (":person_id" => (isset($_user->id) ? $_user->id : -1),
                   ":level" => $level,
                   ":datum" => $dt->format('Y-m-d H:i:s'),
                   ":domain_id" => $domainid,
                   ":domain_type" => $domaintype,
                   ":schreiben_yn" => $writeaccess_yn,
                   ":txt" => substr($txt, 0, 1999),
            ));
}

/**
 * FIXME: use this instead?
 * http://de2.php.net/manual/en/function.quoted-printable-decode.php
 *
 * @param string $str
 *
 * @return string
 */
function php_quot_print_encode($str) {
  $lp = 0;
  $return = '';
  $hex = "0123456789ABCDEF";
  $length = strlen($str);
  $str_index = 0;

  while ($length--) {
    if ((($c = $str[$str_index++]) == "\015") && ($str[$str_index] == "\012") && $length > 0) {
      $return .= "\015";
      $return .= $str[$str_index++];
      $length--;
      $lp = 0;
    }
    else {
      if (ctype_cntrl($c)
          || (ord($c) == 0x7f)
          || (ord($c) & 0x80)
          || ($c == '=')
          || (($c == ' ')
          && (isset($str[$str_index]))
          && ($str[$str_index] == "\015"))) {
        if (($lp += 3) > PHP_QPRINT_MAXL) {
          $return .= '=';
          $return .= "\015";
          $return .= "\012";
          $lp = 3;
        }
        $return .= '=';
        $return .= $hex[ord($c) >> 4];
        $return .= $hex[ord($c) & 0xf];
      }
      else {
        if ((++$lp) > PHP_QPRINT_MAXL) {
          $return .= '=';
          $return .= "\015";
          $return .= "\012";
          $lp = 1;
        }
        $return .= $c;
      }
    }
  }
  return $return;
}

/**
 * Is the module acticated
 * @param String $modulename
 * @return true|false
 */
function churchcore_isModuleActivated($modulename) {
  return getConf($modulename . "_name", false) != false;
}

/**
 * Get array with sorted modules from content of dir system
 *
 * @param string $withCoreModule
 * @param string $withOfflineModules
 *
 * @return array
 */
function churchcore_getModulesSorted($withCoreModule = false, $withOfflineModules = false) {
  global $config;

  if ($withCoreModule) $config["churchcore_sortcode"] = 0;

  // Get module names out of the file system in directory system/*
  // name contains church, is not churchcore and is a directory
  $modules = array ();
  foreach (scandir("system") as $file) {
    if (strPos($file, 'church') !== false
        && (($file != "churchcore" || $withCoreModule))
        && is_dir("system/" . $file)) {
      $modules[] = $file;
    }
  }

  $sort_arr = array ();
  $mysort = 1000;
  foreach ($modules as $module) {
    // TODO: can getConf be used here or not?
    if (!isset($config[$module . "_name"]) || $config[$module . "_name"] != "" || $withOfflineModules) {
      if ((!isset($config[$module . "_sortcode"])) || isset($sort_arr[$config[$module . "_sortcode"]])) {
        $mysort++;
        $sort_arr[$mysort] = $module;
      }
      else
        $sort_arr[$config[$module . "_sortcode"]] = $module;
    }
  }
  ksort($sort_arr);

  return $sort_arr;
}

/**
 * Thats the current mail methode!
 *
 * @param string $from
 * @param string $to
 * @param string $subject
 * @param string $content
 * @param bool $htmlmail, default:false
 * @param bool $withTemplate, default:true
 * @param int $priority, default:2, 1, 2; 1=now, 2=soon, 3=after sending more important mails
 */
function churchcore_mail($from, $to, $subject, $content, $htmlmail = false, $withTemplate = true, $priority = 2) {
  global $base_url, $files_dir, $user;

  $header = "";
  $body = "";
  // $header.='MIME-Version: 1.0' . "\n";
  if ($htmlmail) {
    // $header.='Content-type: text/html; charset=utf-8' . "\n"; //'Content-Transfer-Encoding: quoted-printable'. "\n" .
    if ($withTemplate) {
      if (file_exists("$files_dir/mailtemplate.html")) $body = file_get_contents("$files_dir/mailtemplate.html");
      else $body = file_get_contents("system/includes/mailtemplate.html");
    }
    else
      $body = "%content";
  }
  else {
    // $header.='Content-type: text/plain; charset=utf-8' . "\n"; //'Content-Transfer-Encoding: quoted-printable'. "\n"
    // .
    if ($withTemplate) {
      if (file_exists("$files_dir/mailtemplate.plain")) $body = file_get_contents("$files_dir/mailtemplate.plain");
      else $body = file_get_contents("system/includes/mailtemplate.plain");
    }
    else
      $body = "%content";
  }
  // $header.="From: $from\n";

  // $header.='X-Mailer: PHP/' . phpversion();

  $variables = array (
    '%username' => (isset($user->cmsuserid) ? $user->cmsuserid : "anonymus"),
    '%useremail' => (isset($user->email) ? $user->email : "anonymus"),
    '%sitename' => getConf('site_name'),
    '%sitemail' => getConf('site_mail', 'info@churchtools.de'), '%siteurl' => $base_url,
  );
  // replace variables in content
  $content = strtr($content, $variables);
  // add content to body
  $variables["%content"] = $content;

  ct_log("Speichere Mail an $to von $from - $subject", 2, -1, "mail");
  // mail($to, "=?utf-8?Q?".php_quot_print_encode($subject)."?=\n", strtr($body, $variables), $header);
  $dt = new DateTime();
  if ($to == null) $to = "";
  db_query("INSERT INTO {cc_mail_queue} (receiver, sender, subject, body, htmlmail_yn, priority, modified_date, modified_pid)
            VALUES (:receiver, :sender, :subject, :body, :htmlmail_yn, :priority, :modified_date, :modified_pid)",
            array (":receiver" => $to,
                   ":sender" => $from,
                   ":subject" => php_quot_print_encode($subject),
                   ":body" => strtr($body, $variables),
                   ":htmlmail_yn" => ($htmlmail ? 1 : 0),
                   ":priority" => $priority,
                   ":modified_date" => $dt->format('Y-m-d H:i:s'),
                   ":modified_pid" => (isset($user) ? $user->id : -1),
            ));
}

/**
 * System plain email with sender from admin and info attachement
 *
 * @param string $recipients; one or more, comma separated
 * @param string $subject
 * @param string $content
 */
function churchcore_systemmail($recipients, $subject, $content, $htmlmail = false, $priority = 2) {
  if (getConf("mail_enabled")) {
    $recipients = explode(",", $recipients);
    foreach ($recipients as $recipient) {
      churchcore_mail(getConf('site_mail',
                      ini_get('sendmail_from')),
                      trim($recipient),
                      $subject, $content,
                      $htmlmail,
                      true,
                      $priority
      );
    }
  }
}

/**
 * send mails per PHP
 *
 * @param number $maxmails; default MAX_MAILS
 */
function churchcore_sendMails_PHPMAIL($maxmails = MAX_MAILS) {
  global $config, $base_url;
  $db = db_query("SELECT value FROM {cc_config}
                  WHERE name='currently_mail_sending'")
                  ->fetch();
  if (!$db) {
    db_query("INSERT INTO {cc_config}
              VALUES ('currently_mail_sending', '0')");
    $db = new stdClass();
    $db->value = 0;
  }

  if ($db->value == "0") {
    db_query("UPDATE {cc_config} SET value='1'
              WHERE name='currently_mail_sending'");

    $db = db_query("SELECT * FROM {cc_mail_queue}
                    WHERE send_date IS NULL
                    ORDER BY priority LIMIT $maxmails");
    if ($db) {
      $counter = 0;
      $counter_error = 0;
      foreach ($db as $mail) {
        $header = 'MIME-Version: 1.0' . "\n";
        $body = $mail->body;
        if ($mail->htmlmail_yn == 1) {
          $header .= 'Content-type: text/html; charset=utf-8' . "\n"; // 'Content-Transfer-Encoding: quoted-printable'."\n" .
          $body .= '<img src="' . $base_url . '?q=cron&standby=true&mailqueue_id=' . $mail->id . '"/>';
        }
        else
          $header .= 'Content-type: text/plain; charset=utf-8' . "\n"; // 'Content-Transfer-Encoding: quoted-printable'."\n" .

        // See churchtools.example.config for more details
        if (getVar("mail_with_user_from_address", "0") == "0") {
          $header.="From: ".getConf('site_mail', 'info@churchtools.de')."\n";
          if ($mail->sender!=getConf('site_mail', 'info@churchtools.de')) {
            $header.="Reply-To: $mail->sender\n";
            $header.="Return-Path: $mail->sender\n";
          }
          ct_log($header, 1);
        }
        else {
          $header.="From: ".$mail->sender."\n";
        }

        $header .= 'X-Mailer: PHP/' . phpversion();
        $error = 0;
        $counter++;
        // if test is set, do not send real mails, only simulate it!
        if (!isset($config["test"])) {
          if (!mail($mail->receiver, "=?utf-8?Q?" . $mail->subject . "?=\n", $body, $header)) {
            $counter_error++;
            $error = 1;
          }
        }
        db_query("UPDATE {cc_mail_queue} SET send_date=NOW(), error=$error WHERE id=$mail->id");
      }
      if ($counter > 0) ct_log("$counter E-Mails wurden gesendet. " .
           ($counter_error > 0 ? "$counter_error davon konnten nicht gesendet werden!" : ""), 2, -1, "mail");
    }
    db_query("UPDATE {cc_config} SET value='0' WHERE name='currently_mail_sending'");
  }
  // To many errors, so the process was killed or something like that.
  else if ($db->value > "10") {
    db_query("UPDATE {cc_config} SET value='0' WHERE name='currently_mail_sending'");
  }
  else {
    // Increment
    db_query("UPDATE {cc_config} SET value=value+1 WHERE name='currently_mail_sending'");
  }
}

/**
 * send mails per PEAR
 * TODO: doesnt work, files missing - remove?
 *
 * @param int $maxmails; default: MAX_MAILS
 */
function churchcore_sendMails_PEARMAIL($maxmails = MAX_MAILS) {
  global $config, $base_url;
  ct_log("starte senden5");

  include_once 'Mail.php'; //dont exists!
  include_once 'Mail/mime.php';

  $db = db_query("SELECT value FROM {cc_config} WHERE name='currently_mail_sending'")->fetch();
  if (!$db) {
    db_query("INSERT INTO {cc_config} VALUES ('currently_mail_sending', '0')");
    $db = new stdClass();
    $db->value = 0;
  }

  if ($db->value == "0") {
    db_query("UPDATE {cc_config} SET value='1' WHERE name='currently_mail_sending'");
    $db = db_query("SELECT * FROM {cc_mail_queue} WHERE send_date IS NULL ORDER BY priority LIMIT $maxmails");
    if ($db != false) {
      ct_log("starte senden0");
      $counter = 0;
      $counter_error = 0;
      foreach ($db as $mail) {
        $headers = array (
          'From' => getConf('site_mail', 'info@churchtools.de'),
          'Reply-To' => $mail->sender,
          'Return-Path' => $mail->sender,
          'Subject' => $mail->subject,
          'Content-Type' => 'text/html; charset=UTF-8', 'X-Mailer' => 'PHP/' . phpversion(),
        );

        $mime_params = array ('text_encoding' => '7bit',
                              'text_charset' => 'UTF-8',
                              'html_charset' => 'UTF-8',
                              'head_charset' => 'UTF-8',
                       );

        $mime = new Mail_mime();

        if ($mail->htmlmail_yn == 1) {
          $html = $mail->body;
          $html .= '<img src="' . $base_url . '?q=cron&standby=true&mailqueue_id=' . $mail->id . '"/>';
          $mime->setHTMLBody($html);
        }
        else {
          $text = $mail->body;
          $mime->setTXTBody($text);
        }

        $error = 0;
        $counter++;
        ct_log("starte senden");
        // Wenn test gesetzt ist, soll er keine Mails senden, sondern nur so tun!
        if (!isset($config["test"])) {
          $body = $mime->get($mime_params);
          $headers = $mime->headers($headers);
          $mail_object = & Mail::factory(getConf('mail_pear_type', 'mail'), (getConf("mail_pear_args", null)));
          $ret = @$mail_object->send($mail->receiver, $headers, $body);
          if (@PEAR::isError($ret)) {
            $counter_error++;
            $error = 1;
            ct_log("Fehler beim Senden einer Mail: " . $ret->getMessage(), 1);
          }
        }
        db_query("UPDATE {cc_mail_queue} SET send_date=NOW(), error=$error WHERE id=$mail->id");
      }
      if ($counter > 0) ct_log("$counter E-Mails wurden gesendet. " .
           ($counter_error > 0 ? "$counter_error davon konnten nicht gesendet werden!" : ""), 2, -1, "mail");
    }
    db_query("UPDATE {cc_config} SET value='0' WHERE name='currently_mail_sending'");
  }
}

/**
 * send mails
 *
 * @param int $maxmails; default: MAX_MAILS
 */
function churchcore_sendMails($maxmails = MAX_MAILS) {
  if (getConf('mail_type', 'phpmail') == "phpmail") churchcore_sendMails_PHPMAIL($maxmails);
  else churchcore_sendMails_PEARMAIL($maxmails);
}

/**
 * create one time login string for emails and save it into DB
 *
 * @param int $id
 *
 * @return string, login string
 */
function churchcore_createOnTimeLoginKey($id) {
  $loginstr = random_string(60);
  db_query("UPDATE {cdb_person} SET loginstr='1'
            WHERE id=:id AND loginstr IS NULL",
            array(':id' => $id));

  db_query("INSERT INTO {cc_loginstr} (person_id, loginstr, create_date)
            VALUES (:id, :loginstr, current_date)",
            array(':id' => $id, ':loginstr' => $loginstr));

  return $loginstr;
}

/**
 * send emails to persons with given id(s)
 *
 * @param string $ids; comma separated
 * @param string $subject
 * @param string $content
 * @param string $from; default: null (use current users email)
 * @param string $htmlmail; default: false
 * @param string $withtemplate; default: true
 */
function churchcore_sendEMailToPersonIDs($ids, $subject, $content, $from = null, $htmlmail = false, $withtemplate = true) {
  global $base_url;

  if ($ids==null || $ids=="") { 
    ct_log("Konnte Email $subject nicht senden, kein Empfänger angegeben!", 1);
    return;
  }
  
  if (!$from) {
    $user_pid = $_SESSION["user"]->id;
    $res = db_query("SELECT vorname, name, email FROM {cdb_person}
                     WHERE id=$user_pid")->fetch();
    $from = "$res->vorname $res->name <$res->email>";
  }
  $persons = db_query("SELECT * FROM {cdb_person} WHERE id IN ($ids)");
  $error = array ();
  foreach ($persons as $p) {
    $mailtxt = $content;
    if (empty($p->email)) $error[] = $p->vorname . " " . $p->name;
    else {
      $mailtxt = str_replace('\"', '"', $mailtxt);
      $mailtxt = churchcore_personalizeTemplate($mailtxt, $p);
      // ct_log("[ChurchCore] - Sende Mail an $p->email $mailtxt",2,-1,"mail");

      churchcore_mail($from, $p->email, $subject, $mailtxt, $htmlmail, $withtemplate);
    }
  }
  if (count($error)) {
    throw new CTFail(t('following.persons.have.no.email.address') . ' ' . implode($error, ", "));
  }
}

/**
 * insert person data into template
 * TODO: use eval or output buffering to personalize templates
 *
 * @param string $txt
 * @param object $p
 *
 * @return string
 */
function churchcore_personalizeTemplate($txt, $p) {
  if ($p == null) return $txt;
  $txt = str_replace("[Vorname]", $p->vorname, $txt);
  $txt = str_replace("[Nachname]", $p->name, $txt);
  $txt = str_replace("[Titel]", $p->titel, $txt);
  $txt = str_replace("[Spitzname]", ($p->spitzname ? $p->spitzname : $p->vorname), $txt);
  $txt = str_replace("[Id]", $p->id, $txt);
  $txt = str_replace("[Initialen]", substr($p->vorname,0,1) . substr($p->name,0,1), $txt);
  $txt = str_replace("[Benutzername]", $p->cmsuserid, $txt);
  return $txt;
}

/**
 * if DB update is needed, update!
 *
 * @return boolean
 */
function checkForDBUpdates() {
  global $mapping;

// TODO: should be the same as next two lines, not tested
//  if (!$software_version = getConf(["churchtools_version"]) die("churchtools_version nicht gefunden!");

  if (!$mapping["churchtools_version"]) die("churchtools_version nicht gefunden!");
  $software_version = $mapping["churchtools_version"];

  $db_version = "nodb";

  try {
    // test if cc_config table is present
    $a = db_query("SELECT * FROM {cc_config} WHERE name='version'", null, false);
    // if we arrive here, it is, so fetch current database version
    $db_version = $a->fetch()->value;
  }
  catch (Exception $e) {
    try {
      /* if cdb_person is present, but not cc_config, it's a pre-2.0 database */
      $a = db_query("select * from {cdb_person}", null, false);
      $db_version = "pre-2.0";
    }
    catch (Exception $e) {
      /* still not? start from scratch */
      $db_version = "nodb";
    }
  }
  /* anything to do? */
  if ($db_version == $software_version) return true;

  include_once ("system/includes/db_updates.php");
  return run_db_updates($db_version);
}

/**
 * sort $array by values in $array[$key]
 *
 * @param array $array by reference
 * @param string $key
 */
function churchcore_sort(&$array, $key) {
  $sorter = array ();
  $return = array ();
  reset($array);
  foreach ($array as $k => $val) {
    $sorter[$k] = $val[$key];
  }
  asort($sorter);
  foreach ($sorter as $k => $val) {
    $return[$k] = $array[$k];
  }
  $array = $return;
}

/**
 * implodes a distinct class property of objects in $array
 *
 * @param array $array, containing objects
 * @param string $glue; f.e. "::"
 * @param string $property; f.e. "bezeichnung"
 *
 * @return string
 */
function implode_array($array, $glue, $property) {
  $return = array ();
  foreach ($array as $a) {
    $return[] = $a->$property;
  }
  return implode($return, $glue);
}

//TODO: remove drupal from names?
function drupal_add_css($str) {
  global $add_header;
  $add_header .= '<link href="' . $str . '?' . JS_VERSION . '" rel="stylesheet">';
}

function drupal_add_js($str) {
  global $add_header, $config;
  $add_header .= '<script src="' . $str . '?' . JS_VERSION . '"></script>';
}

function drupal_get_header() {
  global $add_header;
  return $add_header;
}

function drupal_add_header($header) {
  global $add_header;
  $add_header .= $header . "\n";
}

function drupal_add_http_header($name, $val, $replace) {
  header("$name: $val", $replace);
}

// ----------------------------------------
// --- JSON_TOOLS
// ----------------------------------------
function drupal_json_output($mixed) {
  header('Content-Type: application/json');
  echo json_encode($mixed, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
}

/**
 * read data from DB
 *
 * TODO: maybe always return an array to prevent problems with foreach on the results withouth further testing?
 * Is it intended to use rather then db_query on many places?
 * Whats the advantage to have the statement split in several variables?
 * A 'true' sql statement is much much more readable then
 * churchcore_getTableData('cs_service','','cdb_tag_ids is not null')
 *
 * at least write it like this
 * churchcore_getTableData(
 *    $tablename = 'cs_service',
 *    $order = '',
 *    $where = 'cdb_tag_ids is not null')
 *
 * @param string $tablename
 * @param string $order
 * @param string $where
 * @param string $cols
 *
 * @return object
 */
function churchcore_getTableData($tablename, $order = "", $where = "", $cols = "*") {
  $res = db_query("SELECT $cols FROM `{" . $tablename . "}`" .
                  ($where ? " WHERE $where" : '') .
                  ($order ? " ORDER BY $order" : ''));
  $return = null;
  foreach ($res as $arr) {
    if (isset($arr->id)) $return[$arr->id] = $arr;
    else $return[] = $arr;
  }
  return $return;
}

/**
 * FIXME: maybe always return an array to prevent problems with foreach on the results.
 * (occured on deleting song)
 *
 * @param unknown $domain_type
 * @return object
 */
function churchcore_getFiles($domain_type) {
  $res = db_query("SELECT f.*, concat(p.vorname,' ',p.name) AS modified_username
                   FROM {cc_file} f LEFT JOIN {cdb_person} p ON (f.modified_pid=p.id)
                   WHERE f.domain_type=:domain_type",
                   array(":domain_type" => $domain_type));
  $return = null;
  foreach ($res as $arr) {
    $return[$arr->id] = $arr;
  }
  return $return;
}

/**
 * Holt sich die Dateien als Array per DomainId
 *
 * @param mixed $domain_type
 * @param mixed $domain_id
 * @return files
 */
function churchcore_getFilesAsDomainIdArr($domain_type, $domain_id = null) {
  $sql = "SELECT f.*, CONCAT(p.vorname,' ',p.name) AS modified_username
          FROM {cc_file} f LEFT JOIN {cdb_person} p ON (f.modified_pid=p.id)
          WHERE f.domain_type=:domain_type";
  $params = array (':domain_type' => $domain_type);
  if ($domain_id){
    $sql .= " and domain_id=:domain_id";
    $params[':domain_id'] = $domain_id;
  }
  $res = db_query($sql, $params);
  $files = array ();
  foreach ($res as $file) {
    if (!isset($files[$file->domain_id])) $files[$file->domain_id] = array ();
    $files[$file->domain_id][$file->id] = $file;
  }
  return $files;
}

/**
 * copy file to other domain id
 *
 * @param mixed $id
 * @param mixed $domain_ids
 * @throws CTFail
 */
function churchcore_copyFileToOtherDomainId($id, $domain_ids) {
  global $files_dir;
  $res = db_query("SELECT * FROM {cc_file}
                   WHERE id=:id",
                   array (":id" => $id), false)
                   ->fetch();
  if (!$res) throw new CTFail(t('file.not.found.in.DB'));

  $arr = explode(",", $domain_ids);
  foreach ($arr as $val) if ($val) {
    if (!file_exists("$files_dir/files/$res->domain_type/$val")) mkdir("$files_dir/files/$res->domain_type/$val", 0777, true);
    if (!copy("$files_dir/files/$res->domain_type/$res->domain_id/$res->filename", "$files_dir/files/$res->domain_type/$val/$res->filename")) {
      throw new CTFail("Datei konnte nicht nach $files_dir/files/$res->domain_type/$val/$res->filename kopiert werden!");
    }
    db_query("INSERT INTO {cc_file} (domain_type, domain_id, bezeichnung, filename, modified_date, modified_pid)
              VALUES (:domain_type, :domain_id, :bezeichnung, :filename, :modified_date, :modified_pid)",
              array (":domain_type" => $res->domain_type,
                     ":domain_id" => $val,
                     ":bezeichnung" => $res->bezeichnung,
                     ":filename" => $res->filename,
                     ":modified_date" => $res->modified_date,
                     ":modified_pid" => $res->modified_pid,
              ));
  }
}

/**
 * rename file
 *
 * @param mixed $id
 * @param mixed $filename
 * @throws CTFail
 */
function churchcore_renameFile($id, $filename) {
  global $files_dir;
  $res = db_query("SELECT * FROM {cc_file} WHERE id=:id",
                   array (":id" => $id), false)
                   ->fetch();
  if (!$res) throw new CTFail(t('file.not.found.in.DB'));

  db_query("UPDATE {cc_file} SET bezeichnung=:bezeichnung
            WHERE id=:id",
            array (":id" => $id, ":bezeichnung" => $filename), false);
}

/**
 * delete file
 * @param mixed $id
 * @throws CTFail
 */
function churchcore_delFile($id) {
  global $files_dir;
  $res = db_query("SELECT * FROM {cc_file}
                   WHERE id=:id",
                   array (":id" => $id), false)
                   ->fetch();
  if (!$res) throw new CTFail(t('file.not.found.in.DB'));

  db_query("DELETE FROM {cc_file}
            WHERE id=:id",
            array (":id" => $id), false);
  if (!unlink("$files_dir/files/$res->domain_type/$res->domain_id/$res->filename")) throw new CTFail("Datei konnte auf dem Server nicht entfernt werden.");
}

function churchcore_renderFile($file) {
  $i = strrpos($file->bezeichnung, '.');
  $ext = "paperclip";
  if ($i > 0) {
    switch (substr($file->bezeichnung, $i, 99)) {
      case '.mp3':
        $ext = "mp3";
        break;
      case '.m4a':
        $ext = "mp3";
        break;
      case '.pdf':
        $ext = "pdf";
        break;
      case '.doc':
        $ext = "word";
        break;
      case '.docx':
        $ext = "word";
        break;
      case '.rtf':
        $ext = "word";
        break;
    }
  }
  $txt = '<a target="_clean" href="?q=churchservice/filedownload&id=' . $file->id . '&filename=' . $file->filename .
         '" title="' . $file->bezeichnung . '">' . churchcore_renderImage("$ext.png", 20) . '</a>';

  return $txt;
}

/**
 * get html image tag for churchcore image
 *
 * @param string $filename
 * @param int $width; default: 24
 *
 * @return string; html
 */
function churchcore_renderImage($filename, $width = 24) {
  global $base_url;
  return '<img src="' . $base_url . '/system/churchcore/images/' . $filename . '" style="max-width:' . $width . 'px"/>';
}

/**
 * get person independently of authorisazion
 *
 * @param int $id
 * @return person object
 */
function churchcore_getPersonById($id) {
  $res = db_query("SELECT * FROM {cdb_person}
                   WHERE id=:id",
                   array (":id" => $id))
                   ->fetch();
  return $res;
}

/**
 * TODO: whats the main difference to the function above??? only one time used
 *
 * @param int $id
 * @return unknown|NULL
 */
function churchcore_getUserById($id) {
  $res = db_query("SELECT * FROM {cdb_person}
                   WHERE id=:id",
                   array (":id" => $id))
                   ->fetch();
  if ($res !== false) return $res;
  else return null;
}

/**
 * get user ids from DB
 * TODO: is empty $CMSID allowed? If not, how can you get more then one user?
 *
 * @param string $CMSID
 * @param bool $multiple; if false return first user, else return comma separated list
 * @return one or more ids, null if none found
 */
function churchcore_getUserByCMSId($CMSID, $multiple = false) {
  $sql = "SELECT id FROM {cdb_person}
          WHERE cmsuserid=:cmsId";
  $params = array(':cmsId' => $CMSID);
  if (!$multiple) {
    if ($obj = db_query($sql, $params)->fetch()) return $obj->id;
    else return null;
  }
  else {
    $obj = db_query($sql, $params);
    $res = array ();
    foreach ($obj as $p) $res[] = $p->id;
    if (count($res)) return $res;
    else return null;
  }
}

/**
 * TODO: sql is the only difference to churchcore_getUserByCMSId, use one for both?
 * not used anywhere!
 *
 * @param string $CMSID
 * @param bool $multiple; if false return first user, else return comma separated list
 * @return one or more ids, null if none found
 */
function churchcore_getCompleteUserByCMSId($CMSID, $multiple = false) {
  $sql = "SELECT id, email, vorname, name
          FROM {cdb_person}
          WHERE cmsuserid=:cmsId";
  $params = array(':cmsId' => $CMSID);

  if (!$multiple) {
    if ($obj = db_query($sql, $params)->fetch()) {
    return $obj;
  }
    else return null;
  }
  else {
    $obj = db_query($sql, $params);
    $res = array ();
    foreach ($obj as $p) $res[] = $p;
    if (count($res) > 0) return $res;
    else return null;
  }
}

/**
 * which advantage has this function over using $_SESSION["user"]->id? used 3 times, changed there
 */
/*function churchcore_getCurrentUserPid() {
  return $_SESSION["user"]->id;
}*/

/**
 * read user settings from DB
 * TODO: add example how advanced settings may look
 *
 * @param string $modulename
 * @param int $user_pid; or array which holds pid in first key
 *
 * @return array
 */
function churchcore_getUserSettings($modulename, $user_pid) {
  if (!$user_pid) return array ();
  if (is_array($user_pid)) $user_pid = $user_pid[0];

  $res = db_query("SELECT attrib, value, serialized_yn
                   FROM {cc_usersettings}
                   WHERE modulename=:module AND person_id=:id",
                   array('module' => $modulename, ':id' => $user_pid));
  $settings = array ();
  $bundles = array ();
  foreach ($res as $entry) {
    if ($entry->serialized_yn == 1) $val = unserialize($entry->value);
    else $val = preg_replace('/\\\/', "", $entry->value);
    $i = strpos($entry->attrib, "[");
    if ($i > 0) {
      $bundle_name = substr($entry->attrib, 0, $i);
      $bundle_key = substr($entry->attrib, $i + 1, strpos($entry->attrib, "]") - $i - 1);
      if (!isset($bundles[$bundle_name])) $bundles[$bundle_name] = array ();
      $bundles[$bundle_name][$bundle_key] = $val;
    }
    else $settings[$entry->attrib] = $val;
  }
  foreach ($bundles as $key => $bundle) $settings[$key] = $bundle;

  return $settings;
}

/**
 * Save User Setting to table cc_usersetting.
 * If $val == null delete setting
 *
 * @param string $modulename
 * @param int $pid
 * @param string $attrib
 * @param mixed $val
 */
function _churchcore_savePidUserSetting($modulename, $pid, $attrib, $val) {
  if ($val == null) {
    db_query("DELETE FROM {cc_usersettings}
        WHERE modulename=:modulename AND person_id=:pid AND attrib=:attrib",
        array (":modulename" => $modulename,
               ":attrib" => $attrib,
               ":pid" => $pid,
        ));
  }
  else {
    $serizaled = 0;
    if (is_array($val)) {
      $val = serialize($val);
      $serizaled = 1;
    }

    $res = db_query("SELECT * FROM {cc_usersettings}
                     WHERE modulename='$modulename' AND person_id=$pid AND attrib='$attrib'")
                     ->fetch();
    if (!$res) {
      db_query("INSERT INTO {cc_usersettings} (person_id, modulename, attrib, value, serialized_yn)
                VALUES ($pid, '$modulename', '$attrib', :val, $serizaled)",
                array (":val" => $val));
    } //TODO: use ON DUPLICATE KEY UPDATE rather then two separate queries?
    else db_query("UPDATE {cc_usersettings} SET value=:val, serialized_yn=$serizaled
                   WHERE modulename='$modulename' and person_id=$pid and attrib='$attrib'",
                   array (":val" => $val));
  }
}

/**
 * Convert array or object of typical cc event data to string list with <ul><li>
 * @param unknown $res
 * @return string
 */
function churchcore_CCEventData2String($res) {
  $res = (object) $res;

  $txt = '<ul>';
  $txt .= "<li>" . t('caption') . ": " . $res->bezeichnung;
  $txt .= "<li>" . t('start') . ": " . churchcore_stringToDateDe($res->startdate);
  $txt .= "<li>" . t('end') . ": " . churchcore_stringToDateDe($res->enddate);
  if ($res->repeat_id>0) {
    $rep = db_query("select * from {cc_repeat} where id=:id", array(":id" => $res->repeat_id)) -> fetch();
    $txt .= "<li>" . $rep->bezeichnung . ", " . t('until') .": " . churchcore_stringToDateDe($res->repeat_until);
  }
  if (!empty($res->ort)) $txt .= "<li>" . t('note') . ": " . $res->ort;
  if (!empty($res->intern_yn)) $txt .= "<li>" . t('note') . ": " . $res->ort;
  if (!empty($res->notizen)) $txt .= "<li>" . t('comment') . ": " . $res->note;
  if (!empty($res->link)) $txt .= "<li>Link: " . $res->link;
  $txt .= '</ul>';
  return $txt;
}

/**
 * Notifications for mailing on updates
 * Optional parameters are $domain_type, $domain_id and $person_id
 */
function churchcore_getMyNotifications() {
  global $user;

  $res = db_query("SELECT id, notificationtype_id, domain_id, domain_type, lastsenddate
                  FROM {cc_notification}
                  WHERE person_id=:p_id",
                  array (":p_id" => $user->id));
  $abos = array ();
  foreach ($res as $abo) {
//     // TODO: check if it does the same as below
//     // is isset needed or can we simple assign $abos[$abo->domain_type][$abo->domain_id]
//     if (!isset($abos[$abo->domain_type])) $abos[$abo->domain_type] = array ();
//     if (!isset($abos[$abo->domain_type][$abo->domain_id])) $abos[$abo->domain_type][$abo->domain_id] = array ();

//     $abos[$abo->domain_type][$abo->domain_id]["notificationtype_id"] = $abo->notificationtype_id;
//     $abos[$abo->domain_type][$abo->domain_id]["lastsenddate"] = $abo->lastsenddate;


    if (!isset($abos[$abo->domain_type])) $domaintype = array ();
    else $domaintype = $abos[$abo->domain_type];

    if (!isset($domaintype[$abo->domain_id])) $domain_id = array ();
    else $domain_id = $domaintype[$abo->domain_id];

    $domain_id["notificationtype_id"] = $abo->notificationtype_id;
    $domain_id["lastsenddate"] = $abo->lastsenddate;

    $domaintype[$abo->domain_id] = $domain_id;
    $abos[$abo->domain_type] = $domaintype;
  }
  return $abos;
}

/**
 * Save user settings
 *
 * @param string $modulename
 * @param int $user_pid; or array which holds pid in first key
 * @param string $attrib
 * @param mixed $val
 */
function churchcore_saveUserSetting($modulename, $user_pid, $attrib, $val) {
  if (($user_pid == null) || ($user_pid <= 0)) return;

  //TODO:  simplify by foreach ((array) $user_pid)?
  if (is_array($user_pid)) {
    foreach ($user_pid as $pid)
      _churchcore_savePidUserSetting($modulename, $pid, $attrib, $val);
  }
  else
    _churchcore_savePidUserSetting($modulename, $user_pid, $attrib, $val);
}

//FIXME: what does it here
$res["note"] = churchcore_getTextField("Notiz", "Notiz", "note");

/**
 *
 * @param $longtext; on edit
 * @param $shorttext; on view
 * @param $column_name; DB column
 * @param string $eol; default: <br/>
 * @param string $auth; default: null
 *
 * @return string
 */
function churchcore_getTextField($longtext, $shorttext, $column_name, $eol = '<br/>', $auth = null) {

  return array( "type"      => "text",
                "text"      => $longtext,
                "shorttext" => $shorttext,
                "eol"       => $eol ? $eol : "&nbsp;",
                "sql"       => $column_name,
                "auth"      => $auth,
  );
}

/**
 * description
 * used from getBookingFields()
 * //TODO nearly the same as getTextField - use one for both with parameter type
 *
 * @param $longtext; on edit
 * @param $shorttext; on view
 * @param $column_name; DB column
 * @param string $eol; default: <br/>
 * @param string $auth; default: null
 */
function churchcore_getDateField($longtext, $shorttext, $column_name, $eol = '<br/>', $auth = null) {
  return array( "type"      => "date",
                "text"      => $longtext,
                "shorttext" => $shorttext,
                "eol"       => $eol ? $eol : "&nbsp;",
                "sql"       => $column_name,
                "auth"      => $auth,
  );
}

/**
 * Returns a readable list with all changes or NULL
 * TODO: is != null needed or is if $oldVal sufficient?
 *
 * @param array $fields
 * @param array $oldArr
 * @param array $newArr
 * @param string $cutDates; default: true
 * @return string|NULL
 */
function churchcore_getFieldChanges($fields, $oldArr, $newArr, $cutDates = true) {
  $txt = "";
  foreach ($newArr as $name => $value) {
    $oldVal = null;
    if (isset($fields[$name])) {
      if ($oldArr != null) $oldVal = $oldArr->$fields[$name]["sql"];

      if ($fields[$name]["type"] == "date") {
        // only compare year and day of dates, time doesn't matter
        if ($cutDates && $fields[$name]["type"] == "date") $oldVal = substr($oldVal, 0, 10);

        $oldVal = churchcore_stringToDateDe($oldVal);
        $value  = churchcore_stringToDateDe($value);
      }
      //TODO: != null probably can be omitted
      if ($oldVal != null && $value != $oldVal) $txt .= $fields[$name]["text"] .
           ": $value  (" . t('previously') . ": $oldVal)\n";
      else if ($oldVal == null && $value != null) $txt .= $fields[$name]["text"] . ": $value (" . t('new') . ")\n";
    }
    // For infos which are not in the field-set
    else {
      if ($oldArr != null && isset($oldArr->$name)) {
        $oldVal = $oldArr->$name;
      }
      if ($oldVal == null && $value != null) $txt .= "$name: $value (Neu)\n";
      else if ($oldVal != $value) $txt .= "$name: $value (" . t('previously') . ": $oldVal)\n";
    }
  }

  return $txt ? $txt : null;
}

/**
 * TODO: need $desc to be an object? Maybe the using code can be changed to Field/Type rather?
 *
 * @param int $id; e.g. 3
 * @param string $bezeichnung; e.g. Service Group
 * @param string $shortname; e.g. servicegroup
 * @param string $tablename; e.g. cs_servicegroup
 * @param string $order; e.g. sortkey
 *
 * @return array()
 */
function churchcore_getMasterDataEntry($id, $bezeichnung, $shortname, $tablename, $sql_order = "") {
  $return = array(
    "id"          => $id,
    "bezeichnung" => $bezeichnung,
    "shortname"   => $shortname,
    "tablename"   => $tablename,
    "sql_order"   => $sql_order,
  );

  $tabledesc = db_query("DESCRIBE {" . $tablename . "}");
  foreach ($tabledesc as $desc) {
    // Seit Drupal 7,14 first letter of array keys is uppercase
//      if (isset($desc->Field)) $desc->field = $desc->Field;
//      if (isset($desc->Type)) $desc->type = $desc->Type;

    $return["desc"][$desc->Field] = array_change_key_case((array) $desc, CASE_LOWER); //seems to work properly
  }
  return $return;
}

/**
 * get formated Date from string
 * TODO: maybe rename? Use constants for formats? Not for de only but for any local date format?
 *
 * @param string $string; date(time)
 * @param bool $withTime; default: true
 * @return string; date(time)
 */
function churchcore_stringToDateDe($string, $withTime = true) {
  if (!$string) return null;
  if (!is_string($string)) {
    echo "churchcore_StringToDateDe() expected a String..<br/>";
    print_r($string);
    return "-";
  }

  if (strlen($string) < 11) $string .= " 00:00:00";
  $dt = new Datetime($string);
  if ($withTime) return $dt->format('d.m.Y H:i');
  else return $dt->format('d.m.Y');
}

/**
 * get ical formated Date from string
 * TODO: maybe rename? Use constants for formats?
 *
 * @param string $string; date(time)
 * @return string; date(time)
 */
function churchcore_stringToDateICal($string) {
  $dt = new Datetime($string);
  return $dt->format('Ymd\THis');
}

function churchcore_icalToDate($ical) {
  if (($timestamp = strtotime($ical)) === false) {
    return "";
  }
  else {
    return date('Y-m-d H:i', $timestamp);
  }
}

/**
 * get dateTime object from string - only used 2 times - replaced by new Datetime($string)
 *
 * @param string $string; date(time)
 * @return DateTime
 */
// function churchcore_stringToDateTime($string) {
//   return $dt = new Datetime($string);
// }

function isFullDay($start, $end) {
  if (($start->format('H:i:s') == "00:00:00") && ($end == null || $end->format('H:i:s') == "00:00:00")) return true;
  return false;
}

/**
 * Return if s1 and s2 are the same day
 *
 * @param String $s1; english format //TODO: not any format datetime understands?
 * @param String $s2; english format
 * @return boolean
 */
function churchcore_isSameDay($s1, $s2) {
  $d1 = new Datetime($s1);
  $d2 = new Datetime($s2);
  return $d1->format("Ymd") == $d2->format("Ymd");
}

/**
 * check if two dates conflict with each other
 * TODO: seems like $_enddate/$_enddate2 is not really needed
 *
 * @param DateTime $startdate
 * @param DateTime $enddate
 * @param DateTime $startdate2
 * @param DateTime $enddate2
 *
 * @return boolean
 */
function datesInConflict($startdate, $enddate, $startdate2, $enddate2) {
  $_enddate = $enddate;
  $_enddate2 = $enddate2;
  if (isFullDay($startdate, $enddate)) {
    $_enddate->modify("+1 day");
    $_enddate->modify("-1 second");
  }
  if (isFullDay($startdate2, $enddate2)) {
    $_enddate2->modify("+1 day");
    $_enddate2->modify("-1 second");
  }
  // enddate2 inside date
  //TODO: not tested, but > is higher in the operator list then && :-)
  if (($_enddate2 > $startdate && $_enddate2 < $_enddate)
      // or startdate2 inside date
      || ($startdate2 > $startdate && $startdate2 < $_enddate)
      // or date2 completely outside date
      || ($startdate2 <= $startdate) && ($_enddate2 >= $_enddate)
      // or date2 completely inside date
      || ($startdate2 >= $startdate) && ($_enddate2 <= $_enddate)) {
    return true;
  }
  return false;
}

/**
 * get age for date
 * @param string $date
 * @return number
 */
function churchcore_getAge($date) {
  $d = new DateTime($date);
  $age = floor((date("Ymd") - date("Ymd", $d->getTimestamp())) / 10000);

  return $age;
}

/**
 * get all repeating events/bookings?
 * TODO: change function name, in a distinct view bookings are events too
 * use constants for repeat ids (better readable)
 *
 * Maybe rewrite function:
 * look at php date objects like DatePeriod, DateInterval:
 * $period = new DatePeriod($start, $interval, $recurrences);
 * foreach ($period as $date) {
 *     echo $date->format('Y-m-d')."\n";
 * }
 * you can use dates like "first tuesday of july 2008", "next Monday 2012-04-01"
 * http://de1.php.net/manual/de/datetime.formats.relative.php
 *
 * @param object $r
 * @param int $_from; default: -1, days to add to fromDate
 * @param int $_to; default: 1, days to add to fromDate
 * @return
 */
function getAllDatesWithRepeats($r, $_from = -1, $_to = 1, $fromDate = null) {
  // $dates later will contain all date occurence.
  $dates = array ();
  // $max prevents an endless loop on erors
  $max = 999;

  if ($fromDate == null) $fromDate = new DateTime();
  else $fromDate = new DateTime($fromDate->format('d.m.Y'));
  $to = new DateTime($fromDate->format('d.m.Y H:i'));

  $fromDate->modify("+$_from days");
  $to->modify("+$_to days");

  $r->startdate = new DateTime(($r->startdate instanceof DateTime ? $r->startdate->format('d.m.Y H:i') : $r->startdate));
  $d = clone $r->startdate;
  $r->enddate = new DateTime(($r->enddate instanceof DateTime ? $r->enddate->format('d.m.Y H:i') : $r->enddate));
  $e = clone $r->enddate;

  if (!isset($r->repeat_until)) $r->repeat_until = $d->format("d.m.Y H:i");
  $repeat_until = new DateTime($r->repeat_until);
  $repeat_until = $repeat_until->modify('+1 day'); // include given day!
  if ($to < $repeat_until) $repeat_until = $to;

  if (isset($r->additions)) $additions = $r->additions;
  else $additions = array ();

  $my = new stdClass();
  $my->add_date = $d->format('d.m.Y H:i');
  $my->with_repeat_yn = 1;

  $additions[0] = $my;
  
  // array_unshift($additions, $my);
  foreach ($additions as $key => $add) {
    $d = new DateTime(substr($add->add_date, 0, 10) . " " . $d->format('H:i:s'));
    $e = new DateTime(substr($add->add_date, 0, 10) . " " . $e->format('H:i:s'));

    // Mark exception as used, so datesInConflict() will be called only for new exceptions to save time!
    if (isset($r->exceptions)) foreach ($r->exceptions as $key => $exc) {
      if (is_array($r->exceptions[$key])) $r->exceptions[$key] = (object) $r->exceptions[$key];
      $r->exceptions[$key]->used = false;
    }

    do {
      $exception = false;
      if (isset($r->exceptions)) foreach ($r->exceptions as $exc) {
        // if exception is not used proof conflict with exception date
        if (!$exception && !$exc->used && datesInConflict(new DateTime($exc->except_date_start), new DateTime($exc->except_date_end), $d, $e)) {
          $exception = true;
          $exc->used = true;
        }
      }
      if (!$exception) { //why two ifs?
        if (($d <= $fromDate && $e >= $fromDate) || ($e >= $fromDate && $e <= $to)) {
          $dates[] = new DateTime($d->format('Y-m-d H:i:s'));
        }
      }
      // f.e. each second week is 7*2 => 14 days
      if ($r->repeat_id == 1 || $r->repeat_id == 7) {
        $repeat = $r->repeat_id * $r->repeat_frequence;
        $d->modify("+$repeat days");
        $e->modify("+$repeat days");
      }
      // monthly by date
      else if ($r->repeat_id == 31) {
        $counter = 0;
        do {
          $tester = new DateTime($d->format('Y-m-d H:i:s'));
          $modify = "+ " . ($counter + 1 * $r->repeat_frequence) . " month";
          $tester->modify($modify);
          if ($tester->format('d') == $d->format('d')) {
            $d->modify($modify);
            $e->modify($modify);
            $counter = 999; //TODO: why not using break here?
          }
          $counter = $counter + 1;
        }
        while ($counter < 99);
      }
      // monthly by weekday
      else if ($r->repeat_id == 32) {
        // first find last weekday
        if ($r->repeat_option_id == 6) {
          // go some days back, so we dont jump into the next month and therefor miss one month
          $d->modify("- 5 days");
          $e->modify("- 5 days");
          // add months
          $d->modify("+ " . (1 + 1 * $r->repeat_frequence) . " month"); // what means 1 + 1 * x?
          $e->modify("+ " . (1 + 1 * $r->repeat_frequence) . " month");
          // first go back to first day of month
          //TODO: use sort of $d->setDate(2014, 12, 1) and calculate value for $e?;
          while ($d->format('d') > 1) {
            $d->modify("-1 day");
            $e->modify("-1 day");
          }
          $d->modify("-1 day");
          $e->modify("-1 day");
          // then search for same weekday
          while ($d->format('N') != $r->startdate->format('N')) {
            $d->modify("-1 day");
            $e->modify("-1 day");
          }
        }
        // distinct weekday, f.e. the a.repeat_option_id th weekday of month, if exists
        else {
          $counter = 0;
          // add months
          $d->setDate($d->format('Y'), $d->format('m') + (1 * $r->repeat_frequence), 0);
          $e->setDate($e->format('Y'), $e->format('m') + (1 * $r->repeat_frequence), 0);
          while ($counter < $r->repeat_option_id) {
            $m = $d->format("m");
            $d->modify("+1 day");
            $e->modify("+1 day");
            // test if jumped in next month, then the month has to few days and the event is dropped, f.e. on 5th
            // weekday/month
            if ($d->format("m") != $m) $counter = 0;
            if ($d->format("N") == $r->startdate->format("N")) $counter = $counter + 1;
          }
        }
      }
      else if ($r->repeat_id == 365) {
        $counter = 0;
        $d->modify("+ " . ($counter + 1 * $r->repeat_frequence) . " year");
        $e->modify("+ " . ($counter + 1 * $r->repeat_frequence) . " year");
      }

      $max = $max - 1;
      if ($max == 0) {
        addErrorMessage("Zu viele Wiederholungen in getAllDatesWithRepeats! [$r->id]");
        return false;
      }
    }
    while (($d < $repeat_until) && ($add->with_repeat_yn == 1) && (isset($r->repeat_id)) && ($r->repeat_id > 0) &&
         (isset($r->repeat_frequence)) && ($r->repeat_frequence > 0));
  }
  return $dates;
}

function cleanICal($txt) {
  $str = str_replace("\n", "\\n", $txt);
  // Now do folding see RFC 5545: http://tools.ietf.org/html/rfc5545
  $res = "";
  while ($str!="") {
    $sub = substr($str, 0, 70);
    $str = substr($str, 70, 9999);
    if (strlen($str)>0) $sub .= NL . " ";
    $res .= $sub;
  }
  return $res;
}

function surroundWithVCALENDER($txt) {
  global $config;

  return "BEGIN:VCALENDAR" . NL
       . "VERSION:2.0" . NL
       . "PRODID:-//ChurchTools//DE" . NL
       . "CALSCALE:GREGORIAN" . NL
       . "X-WR-CALNAME:".getConf('site_name')." ChurchCal-Kalender" . NL
       . "X-WR-TIMEZONE:".$config["timezone"] . NL
       . "METHOD:PUSH" . NL
       . $txt
       . "END:VCALENDAR" . NL;  }

function createAnonymousUser() {
  $user = new stdClass();
  $user->id = -1;
  $user->name = "Anonymous";
  $user->vorname = "";
  $user->email = "";
  $user->cmsuserid = "anonymous";
  $user->auth = getUserAuthorization($user->id);
  $_SESSION['user'] = $user;
}

/**
 * looks up if one number of array1 exists in array2
 *
 * @param array $array1
 * @param array $in_array2
 *
 * @return boolean
 */
function array_in_array($array1, $in_array2) {
  $found = false;
  foreach ($array1 as $id) {
    if (in_array($id, $in_array2)) {
      $found = true;
    }
  }
  return $found;
}

/**
 *
 * @param array $auth_table
 * @param bool $IamAdmin
 * @param unknown $res
 * @param unknown $auth
 * @return Ambigous <boolean, multitype:NULL , NULL>
 */
function _implantAuth($auth_table, $IamAdmin, $res, $auth) {
  foreach ($res as $entry) {
    $auth_entry = null;
    if (isset($auth_table[$entry->auth_id])) {
      $auth_entry = $auth_table[$entry->auth_id];
    }
    // Only when I am not admin or I am admin and admindarfsehen = false otherwise already set!
    if ($auth_entry != null && (!$IamAdmin || $auth_entry->admindarfsehen_yn == 0)) {
      if ($entry->daten_id == null) {
        $auth[$auth_entry->modulename][$auth_entry->auth] = true;
      }
      else {
        // Wenn ich alles sehen darf, dann ist daten_id==-1
        if ($entry->daten_id == -1) {
          $res2 = db_query("select id from {" . $auth_entry->datenfeld . "}");
          $auth2 = null;
          foreach ($res2 as $entry2) {
            $auth2[$entry2->id] = $entry2->id;
          }
          $auth[$auth_entry->modulename][$auth_entry->auth] = $auth2;
        }
        else {
          $arr = array ();
          if (isset($auth[$auth_entry->modulename][$auth_entry->auth])) {
            $arr = $auth[$auth_entry->modulename][$auth_entry->auth];
          }
          // Datenautorisierung nicht mit true, sondern mit [id]=id. 1. Implode geht und 2. Direkter Zugriff geht!

          $arr[$entry->daten_id] = $entry->daten_id;
          $auth[$auth_entry->modulename][$auth_entry->auth] = $arr;
        }
      }
    }
  }
  return $auth;
}

/**
 *
 * @param int $user_id
 * @return auth
 */
function getUserAuthorization($user_id) {
  global $config;
  $auth = null;

  if ($user_id == null) return null;

  $auth_table = getAuthTable();
  $IamAdmin = false;
  if (in_array($user_id, $config["admin_ids"])) $IamAdmin = true;

  // Wenn ich in den Admin-Mails bin, dann schuster ich mir alle Rechte zu, die der Admin sehen darf
  if ($IamAdmin) {
    foreach ($auth_table as $entry) {
      if ($entry->admindarfsehen_yn == 1) {
        if ($entry->datenfeld == null) {
          $auth[$entry->modulename][$entry->auth] = true;
        }
        else {
          $res2 = db_query("SELECT id FROM {" . $entry->datenfeld . "}");
          $auth2 = null;
          foreach ($res2 as $entry2) {
            $auth2[$entry2->id] = $entry2->id;
          }
          $auth[$entry->modulename][$entry->auth] = $auth2;
        }
        $auth[$entry->modulename]["view"] = true;
      }
    }
  }

  // F�r normale Benutzer und bei Admins nach Where nur die, wo es nicht f�r Admin alles gibt.
  // Autorisierung �ber direkte Personenzuordnung
  $res = db_query("SELECT daten_id, auth_id
                   FROM {cc_domain_auth} pa
                   WHERE pa.domain_type='person' AND pa.domain_id=:id",
                   array(':id' => $user_id));
  $auth = _implantAuth($auth_table, $IamAdmin, $res, $auth);

  // Autorisierung �ber Status
  $res = db_query("SELECT daten_id, auth_id
                   FROM {cdb_gemeindeperson} gp, {cc_domain_auth} da
                   WHERE da.domain_type='status' AND da.domain_id=gp.status_id AND gp.person_id=:id",
                   array(':id' => $user_id));
  $auth = _implantAuth($auth_table, $IamAdmin, $res, $auth);

  // Autorisierung �ber Gruppen
  $res = db_query("SELECT daten_id, auth_id
                   FROM {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cc_domain_auth} da
                   WHERE da.domain_type='gruppe' and gpg.gemeindeperson_id=gp.id and gpg.status_no>=0
                     AND da.domain_id=gpg.gruppe_id and gp.person_id=:id",
                   array(':id' => $user_id));
  $auth = _implantAuth($auth_table, $IamAdmin, $res, $auth);

  // Wenn es kein Anonymous ist
  if ($user_id > 0) {
    $auth["home"]["view"] = true;
    $auth["logout"]["view"] = true;
    $auth["login"]["view"] = true;
    $auth["profile"]["view"] = true;
    $auth["help"]["view"] = true;
    $auth["cron"]["view"] = true;
    $auth["ical"]["view"] = true;
    $auth["churchauth"]["view"] = true;
    if (isset($auth["churchcore"]) && isset($auth["churchcore"]["administer persons"])) {
      $auth["simulate"]["view"] = true;
    }
    if (isset($auth["churchcore"]) && isset($auth["churchcore"]["administer settings"])) {
      $auth["admin"]["view"] = true;
    }
    if (isset($auth["churchcore"]) && isset($auth["churchcore"]["view logfile"])) {
      $auth["churchcore"]["view"] = true;
    }

    if (isset($_SESSION["simulate"])) $auth["simulate"]["view"] = true;
  }

  return $auth;
}

/**
 * logout current user
 */
function logout_current_user() {
  if (isset($_SESSION["sessionid"])) {
    db_query("DELETE FROM {cc_session} WHERE session=:id",
              array(':id' => $_SESSION["sessionid"]));
    session_destroy();
  }
  if (isset($_SESSION["user"])) {
    $user = $_SESSION["user"];
    if ($user->id > 0) ct_log(t('logout.successful'). ": " . $user->email, 2, -1, "login"); //TODO use language of admin
    unset($_SESSION["user"]);
  }
  // on logout delete remember me cookie!
  setcookie("RememberMe", 0);
  createAnonymousUser();
}

/**
 * check if user can access module $modulename and look for $auth
 *
 * what is in $auths[$modulename][$auth]?
 *
 * @param string $auth; f.e. view
 * @param string $modulename; f.e. churchservice
 * @return mixed auth; may be bool, int or array in form of 0=>0, 1=>1
 */
function user_access($auth, $modulename) {
  global $config;
  if (!$modulename) {
    addErrorMessage("Bei user_access wurde der Modulename nicht gesetzt");
    return false;
  }
  // if no user logged in only login allowed
  if (!isset($_SESSION["user"])) {
    if ($auth == "view" && $modulename == "login") return true;
  }
  else {
    $auths = $_SESSION["user"]->auth;
    if (isset($auths) && isset($auths[$modulename]) && isset($auths[$modulename][$auth])) {
      return $auths[$modulename][$auth];
    }
  }
  return false;
}

/**
 *
 * @return boolean
 */
function userLoggedIn() {
  global $user;
  return (isset($user) && isset($_SESSION['user']) && $_SESSION['user']->id > 0);
}

/**
 * TODO: maybe use $auth_arr as reference, no return needed.
 *
 * @param array $auth_arr
 * @param int $id
 * @param array $auth
 * @param string $modulename
 * @param unknown $datafield
 * @param string $desc
 * @param bool $adminallowed
 * @throws CTException
 * @return array
 */
function addAuth($auth_arr, $id, $auth, $modulename, $datafield, $desc, $adminallowed = 1) {
  if (isset($auth_arr[$id])) throw new CTException("Auth ID $id already set!");
  $auth_arr[$id] = new stdClass();
  $auth_arr[$id]->id = $id;
  $auth_arr[$id]->auth = $auth;
  $auth_arr[$id]->modulename = $modulename;
  $auth_arr[$id]->datenfeld = $datafield;
  $auth_arr[$id]->bezeichnung = $desc;
  $auth_arr[$id]->admindarfsehen_yn = $adminallowed;

  return $auth_arr;
}

/**
 *
 * @return array
 */
function getAuthTable() {
  $modules = churchcore_getModulesSorted(true, false);
  $auth = array();
  $sortkey = 0;
  foreach ($modules as $module) {
    include_once ("system/$module/$module.php");
    if (function_exists($module . "_getAuth")) {
      $res = call_user_func($module . "_getAuth");
      foreach ($res as $key => $val) {
        $val->sortkey = $sortkey;
        $sortkey++;
        $auth[$key] = $val;
      }
    }
  }
  return $auth;
}

/**
 * get person by email
 * @param string $email
 * @return user object or false
 */
function churchcore_getPersonByEMail($email) {
  $res = db_query("SELECT * FROM {cdb_person}
                   WHERE email='$email'")
                   ->fetch();
  return $res;
}

/**
 * get a random string
 * @param int $l;default: 20, string length
 * @return string
 */
function random_string($l = 20) {
  $c = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxwz0123456789";
  $s = "";
  for(; $l > 0; $l--) $s .= $c{rand(0, strlen($c) - 1)};

  return str_shuffle($s);
}

/**
 * trim $str. to $l characters and add .. if needed
 *
 * @param string $str
 * @param int $l;default: 20, string length
 * @return string
 */
function shorten_string($str, $l = 20) {
  if (strlen($str) > $l) $str = substr($str, 0, $l - 1) . "..";
  return $str;
}

//TODO: no function, move it to another file
if (!function_exists('password_hash') && function_exists('crypt')) {
  /* try if we can use the polyfill */
  $hash = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
  $test = crypt("password", $hash);
  if ($test == $hash) {
    require_once ('system/assets/password_hash-polyfill/password.php');
  }
}

/**
 * scramble password
 * @param string $plain_password
 * @return string
 */
function scramble_password($plain_password) {
  if (empty($plain_password)) return null;
  if (function_exists('password_hash')) {
    $val = password_hash($plain_password, PASSWORD_DEFAULT);
    if ($val == FALSE) return null;
    return $val;
  }
  else
    return md5(trim($plain_password));
}

/**
 *
 * @param string $plain_password
 * @param object $user
 * @return bool
 */
function user_check_password($plain_password, $user) {
  $stored_password = $user->password;
  if (empty($plain_password)) return null;
  if (function_exists('password_verify')) {
    if (password_verify($plain_password, $stored_password)) {
      /* maybe the parameters changed, so we should rekey */
      if (password_needs_rehash($stored_password, PASSWORD_DEFAULT)) {
        $new_stored_password = scramble_password($plain_password);
        db_query("UPDATE {cdb_person}
                  SET password=:password
                  WHERE id=:id",
                  array (":id" => $user->id, ":password" => $new_stored_password), false);
      }
      return true;
    }
    else {
      /* maybe the password is still MD5? If so, rekey */
      $compare = md5(trim($plain_password));
      if ($compare == $stored_password) {
        $new_stored_password = scramble_password($plain_password);
        db_query("UPDATE {cdb_person}
                  SET password=:password
                  WHERE id=:id",
                  array (":id" => $user->id, ":password" => $new_stored_password), false);
        return true;
      }
      return false;
    }
  }
  else {
    /* no password_verify, use old md5 method */
    $compare = md5(trim($plain_password));
    return $compare == $stored_password;
  }
}

function user_save() {
  addInfoMessage("<i>user_save</i> not implemented!");
}

// ----------------------------------------
// --- DATABASE TOOLS
// ----------------------------------------

$db_pdo;

/* TODO: remove all users */
function escape_string($str) {
  global $db_pdo;
  return $db_pdo->quote($str);
}

/**
 * Connect to the MySQL database.
 * Use persistent connection and set names utf8
 */
function db_connect() {
  global $config, $db_pdo;
  try {
    $db_pdo = new PDO("mysql:host=${config["db_server"]};dbname=${config["db_name"]};charset=utf8", $config["db_user"], $config["db_password"],
                       array ( PDO::ATTR_PERSISTENT => TRUE, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
  }
  catch (PDOException $e) {
    $error_message = "<h3>Database connection error</h3>";
    $error_message .= "<p><strong>Reason: </strong>" . $e->getMessage() . "</p>";
    $error_message .= "<div class='alert alert-info'>";
    $error_message .= "Please edit your default configuration file <code>" . $config["_current_config_file"] . "</code>, perhaps?";
    $error_message .= "</div>";

    addErrorMessage($error_message);
    return false;
  }

  return true;
}

/**
 * Allow only numbers and commata
 *
 * TODO: rename to reflect function, f.e. cleanIDsForDB. With the UNALLOWED CHARS:[$ids] $ids is still not "clean".
 * throw a sort of exception instead?
 *
 * @param int|array $ids
 * @return mixed
 */
function db_cleanParam($ids) {
  return preg_replace("/[^0-9\,-]/iu" , "UNALLOWED CHARS:[$ids]" , $ids);
}

/**
 * Implode $arr to comma separated WHERE condition and clean string
 * @param unknown $arr
 */
function db_implode($arr) {
  return db_cleanParam( implode(",", $arr) );
}


/**
 * ChurchTools primary db access.
 *
 * @param string $sql;
 * @param array $params
 * @param bool $print_error; if true echo error message, else throw SQLException
 *
 * @return object db_accessor
 */
function db_query($sql, $params = null, $print_error = true) {
  global $db_pdo, $config;

  $sql = str_replace("{", $config["prefix"], $sql);
  $sql = str_replace("}", "", $sql);
  $res = $db_pdo->prepare($sql);
  if ($res === FALSE) {
    $err = $db_pdo->errorInfo();
    if ($print_error) {
      echo $err[2] . "\nGesamtes SQL: " . $sql;
    }
    else throw new SQLException($err[2] . "\nGesamtes SQL: " . $sql);
    return false;
  }
  $d = new db_accessor($res, $params, $sql, $print_error);
  return $d;
}

/**
 * TODO: Whats the advantage of this class? Fetch is the only used function.
 * Returning an array instead probably would simplify result handling.
 *
 */
class db_accessor implements Iterator {
  private $res = null;
  private $current = null;
  private $print_error = false;

  public function __construct($_res, $params = null, $sql = null, $print_error = false) {
    $this->res = $_res;
    $this->print_error = $print_error;
    if (!$this->res instanceof PDOStatement) {
      return null;
    }
    if (!$this->res->execute($params)) {
      $err = $this->res->errorInfo();
      if (!$this->print_error) throw new SQLException($err[2] . "\nSQL: $sql\n" . print_r($params, true));
      else echo "<p>" . $err[2] . "\nSQL: $sql\n" . print_r($params, true);
    }
    $this->next();
  }

  // TODO: shouldn there be a next() after fetching?
  // You always get the same row on fetching without manual calling next()
  public function fetch() {
    return $this->current;
  }

//not used
  function rewind() {
  }

//not used
  function current() {
    return $this->current;
  }

//not used
  function key() {
    return null;
  }

// only used in __construct
  function next() {
    $this->current = $this->res->fetchObject();
  }

//not used
  function valid() {
    return $this->current != null;
  }

  /* FIXME: unused, but also not useful right now in "basic" version */
  function getResult() {
    return $this->res;
  }

}

/**
 * db_update("cs_event")
 * ->fields($fields)
 * ->condition('id',$_GET["id"],"=")
 * ->execute();
 *
 * @param string $tablename
 * @return db_updatefields
 */
function db_update($tablename) {
  return new db_updatefields($tablename);
}

/**
 * TODO: i dont understand the design/naming of this classes.
 * I thought classes are made to represent objects, not methods?
 */
class db_fields {
  protected $tablename;

  public function __construct($tablename) {
    $this->tablename = $tablename;
  }

}

class db_updatefields extends db_fields {

  function fields($arr) {

//     //TODO: maybe use this code using array / implode, not tested
//     $s = array();
//     foreach ($arr as $key => $val) {
//       $s[] = "$key=" . (!isset($val) ? "NULL" : (is_string($val) ? escape_string($val) : $val));
//     }
//     $sql = "UPDATE {" . $this->tablename . "}
//             SET " implode(',', $s) . "
//             WHERE 1 ";

    $sql = "UPDATE {" . $this->tablename . "} SET ";
    $first = true;
    foreach ($arr as $key => $val) {
      if ($first) $first = false;
      else $sql .= ", ";
      $sql .= "$key=";
      if (!isset($val)) $sql .= "NULL";
      else {
        if (is_string($val)) $sql .= escape_string($val);
        else $sql .= "$val";
      }
    }
    $sql .= " WHERE 1 ";
    return new db_execute($sql);
  }

}

function db_insert($tablename) {
  return new db_insertfields($tablename);
}

class db_insertfields extends db_fields {

  function fields($arr) {
    $sql = "INSERT INTO {" . $this->tablename . "} (";
    $first = true;
    foreach ($arr as $key => $val) {
      if ($first) $first = false;
      else $sql .= ", ";
      $sql .= "$key";
    }
    $sql .= ") VALUES (";
    $first = true;
    foreach ($arr as $val) {
      if ($first) $first = false;
      else $sql .= ", ";
      if (!isset($val)) $sql .= "NULL";
      else {
        if (is_string($val)) $sql .= escape_string($val);
        else $sql .= "$val";
      }
    }
    $sql .= ")";
    return new db_execute($sql);
  }

}

function db_delete($tablename, $print_error = true) {
  return new db_deletefields($tablename, $print_error);
}

class db_deletefields extends db_fields {

  function fields($arr) {
    $sql = "DELETE FROM {" . $this->tablename . "}";
    $sql .= " WHERE 1 ";
    return new db_execute($sql);
  }

}

class db_execute {
  private $sql;

  /**
   * @param string $sql
   */
  public function __construct($sql) {
    $this->sql = $sql;
  }

/**
 *
 * @param string $field
 * @param mixed $value
 * @param string $eq
 * @return db_execute
 */
  function condition($field, $value, $eq) {
    if (!is_string($value)) $this->sql .= " AND $field $eq $value";
    else $this->sql .= " AND $field $eq '$value'";
    return new db_execute($this->sql);
  }

  function execute($print_error = true) {
    db_query($this->sql, null, $print_error);
    $return = db_query("SELECT LAST_INSERT_ID( ) as a", null, $print_error)->fetch()->a;
    return $return;
  }

}

/**
 *
 * @param string $table
 * @return boolean
 */
function isCTDBTable($table) {
  global $config;
  $return = (strpos($table, $config["prefix"] . "cc_")  !== false
          || strpos($table, $config["prefix"] . "cr_")  !== false
          || strpos($table, $config["prefix"] . "cdb_") !== false
          || strpos($table, $config["prefix"] . "crp_") !== false
          || strpos($table, $config["prefix"] . "cs_")  !== false
  );
  return $return;
}

/**
 * db backup into file $files_dir . "/db_backup"
 * @return boolean
 */
function dump_database() {
  global $files_dir;

  $dir = $files_dir . "/db_backup";
  if (!file_exists($dir)) mkdir($dir, 0700, true);
  if (!is_writable($dir)) {
    addErrorMessage(t('permission.denied.write.dir', "<i>$dir</i>"));
  }
  else {
    if (!file_exists($dir . "/.htaccess")) {
      $handle = fopen($dir . "/.htaccess", 'w+');
      fwrite($handle, "Deny from all");
      fclose($handle);
    }

    $tables = array ();
    $res = db_query('SHOW TABLES');
    foreach ($res as $row) {
      $table = "";
      foreach ($row as $key => $val) {
        $table = $val;
        break;
      }
      if (isCTDBTable($table)) $tables[] = $table;
    }
    $return = "";
    $dt = new DateTime();

    $filename = $dir . '/db-backup-' . $dt->format('YmdHi') . '-' . (md5(implode(',', $tables))) . '.sql';
    $handle = fopen($filename, 'w+');

    foreach ($tables as $table) {
      $return .= 'DROP TABLE IF EXISTS ' . $table . ';';
      $row2 = db_query('SHOW CREATE TABLE ' . $table)->fetch();
      $row2 = (array) $row2;
      $return .= "\n" . $row2["Create Table"] . ";\n\n";

      $result = db_query('SELECT * FROM ' . $table);
      foreach ($result as $content) {
        $return .= 'INSERT INTO ' . $table . ' VALUES(';
        $arr = array ();
        foreach ($content as $key => $val) {
          if (!isset($val)) $val = "NULL";
          else $val = '"' . addslashes($val) . '"';
          $arr[] = $val;
        }
        $return .= implode(",", $arr) . ");\n";
      }
      $return .= "\n\n\n";
      fwrite($handle, $return);
      $return = "";
    }

    // save file
    fclose($handle);
    $zip = new ZipArchive();
    if ($zip->open($dir . '/db-backup-' . $dt->format('YmdHi') . '.zip', ZIPARCHIVE::OVERWRITE) !== true) {
      return false;
    }
    $zip->addFile($filename);
    $zip->close();
    unlink($filename);

    // delete files older then 30 days
    if ($handle = opendir($dir)) {
      $now = new DateTime();
      while (false !== ($file = readdir($handle))) {
        if (preg_match('/\.sql|zip$/i', $file)) {
          $date = DateTime::createFromFormat('YmdHi', substr($file, 10, strpos($file, ".") - 10));
          if ($date != null) {
            $interval = $date->diff($now);
            if ($interval->format('%a') > 30) unlink($dir . "/" . $file);
          }
        }
      }
    }
  }
}

/**
 *
 *   $masterDataTables[1] = array("id"          => $id,
 *                                 "bezeichnung" => $bezeichnung,
 *                                 "shortname"   => $shortname,
 *                                 "tablename"   => $tablename,
 *                                 "sql_order"   => $sql_order,
 *                          );
 * @param array $masterDataTables
 * @param string $tablename
 * @return boolean
 */
function churchcore_isAllowedMasterData($masterDataTables, $tablename) {
  foreach ($masterDataTables as $table) {
    if ($table["tablename"] == $tablename) return true;
  }
  return false;
}

/**
 * TODO: move into CTAbstractModule
 *
 * Update or Insert depending on $id set or not
 * If Value=null "null", will be inserted.
 *
 * FIXME: TEST USER INPUT!!! NEVER TRUST USER INPUT!! USER INPUT SOULD ALWAYS BE SANITIZED!!!
 * Use :values
 * @param int $id
 * @param string $table
 */
function churchcore_saveMasterData($id, $table) {
  // id not null, UPDATE
  if ($id != "null" && $id != "") {
    $i = 0;
    $sql = "UPDATE {" . $table . "} SET ";
    while (isset($_POST["col" . $i])) {
      if ($_POST["value" . $i] != "null") {
        $sql .= $_POST["col" . $i] . "='" . str_replace("'", "\'", $_POST["value" . $i]) . "', ";
      }
      else $sql .= $_POST["col" . $i] . "=null, ";
      $i++;
    }
    $sql = substr($sql, 0, strlen($sql) - 2);
    $sql = $sql . " WHERE id=$id";
  }
  // id is null => INSERT
  else {
    // get MaxId for new record. We dont use auto_inecrement, so the IDs can be choosen
    $arr = db_query("SELECT MAX(id) id FROM {" . $table . "}")->fetch();
    $max_id = $arr->id + 1;

    $sql = "INSERT INTO {" . $_POST["table"] . "} (id, "; // NEVER TRUST USER INPUT!!!
    // Build Cols
    $i = 0;
    while (isset($_POST["col" . $i])) {
      $sql = $sql . $_POST["col" . $i] . ", ";
      $i++;
    }
    $sql = substr($sql, 0, strlen($sql) - 2);
    // build values
    $sql = $sql . ") values (" . $max_id . ",";
    $i = 0;
    while (isset($_POST["col" . $i])) {
      if ($_POST["value" . $i] != "null") $sql = $sql . "'" . $_POST["value" . $i] . "', "; // NEVER TRUST USER INPUT!!!
      else $sql = $sql . "null, ";
      $i++;
    }
    $sql = substr($sql, 0, strlen($sql) - 2);
    $sql = $sql . ") ";
  }
  db_query($sql);
}
