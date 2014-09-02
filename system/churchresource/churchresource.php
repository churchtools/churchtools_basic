<?php

/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2014 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt
 *
 * ChurchResource Module
 * Depends on ChurchCore, ChurchCal
 */

/**
 * main function for churchresource
 * @return string
 */
function churchresource_main() {
  drupal_add_js(ASSETS . '/js/jquery.history.js');
  
  drupal_add_js(CHURCHCORE . '/cc_abstractview.js');
  drupal_add_js(CHURCHCORE . '/cc_standardview.js');
  drupal_add_js(CHURCHCORE . '/cc_maintainstandardview.js');
  drupal_add_js(CHURCHCORE . '/cc_interface.js');
  
  drupal_add_js(CHURCHRESOURCE . '/cr_loadandmap.js');
  drupal_add_js(CHURCHRESOURCE . '/cr_maintainview.js');
  drupal_add_js(CHURCHRESOURCE . '/cr_weekview.js');
  drupal_add_js(CHURCHRESOURCE . '/cr_main.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchresource"));
  
  $content = '';
  
  // id for calling a distinct entry
  if ($id = getVar("id")) $content .= "<input type='hidden' id='filter_id' value='$id'>";
  
  // $content=$content."<div id='cdb_menu'></div> <div id='cdb_filter'></div> <div id='cdb_content'>Fehler: Ist
  // JavaScript deaktiviert?</div>";
  
  $content .= ' 
<div class="row-fluid">
  <div class="span3">
    <div id="cdb_menu"></div>
    <div id="cdb_filter"></div>
  </div>  
  <div class="span9">
    <div id="cdb_search"></div> 
    <div id="cdb_group"></div> 
    <div id="cdb_content"></div>
  </div>
</div>';
      
  return $content;
}

/**
 * 
 */
function churchresource__ajax() {
  include_once ("churchresource_db.php");
  
  $module = new CTChurchResourceModule("churchresource");
  
  $ajax = new CTAjaxHandler($module);
  $ajax->addFunction("delException", "administer bookings");
  $ajax->addFunction("delBooking", "edit masterdata");
  $ajax->addFunction("createBooking", "view");
  $ajax->addFunction("updateBooking", "view");
  
  drupal_json_output($ajax->call());
}

/**
 * form for admin seddings
 * @return CTModuleForm
 */
function churchresource_getAdminForm() {
  global $config;
  
  $model = new CTModuleForm("churchresource");
  $model->addField("churchresource_entries_last_days", "", "INPUT_REQUIRED", "Wieviel Tage zur&uuml;ck in ChurchResource-Daten geladen werden");
  $model->fields["churchresource_entries_last_days"]->setValue($config["churchresource_entries_last_days"]);
  return $model;
}

/**
 * get open bookings
 * @return string
 */
function churchresource_getOpenBookings() {
  $txt = "";
  if (user_access("administer bookings", "churchresource")) {
    include_once ("churchresource_db.php");
    if ($bookings = getOpenBookings()) {
      foreach ($bookings as $val) {
        $txt .= "<li><p><a href='?q=churchresource&id=$val->id'>$val->text</a> ($val->resource)<br/>
                 <small>$val->startdate $val->person_name</small><br/>";
      }
      if ($txt) $txt = "<ul>$txt</ul>";
    }
  }
  return $txt;
}

/**
 * get current bookings
 * @return number|string
 */
function churchresource_getCurrentBookings() {
  $txt = "";
  if (user_access("view", "churchresource")) {
    include_once ("churchresource_db.php");
    
    // all bookings from now up tomorrow with status 2
    $res = getBookings(0, 1, "2");
    if ($res != null) {
      $arr = array ();
      $counter = 0;
      foreach ($res as $r) {
        $r->startdate = new DateTime($r->startdate);
        $r->enddate   = new DateTime($r->enddate);
        $ds = getAllDatesWithRepeats($r, 0, 1);
        if ($ds) foreach ($ds as $d) {
          $counter++; //TODO: not used
          $arr[] = array ("realstart" => new DateTime($d->format('Y-m-d H:i:s')),
                          "startdate" => $r->startdate,
                          "enddate" => $r->enddate,
                          "person_name" => $r->person_name,
                          "resource_id" => $r->resource_id,
                          "repeat_id" => $r->repeat_id,
                          "text" => $r->text,
                          "id" => $r->id,
                   );
        }
      }
      
      if (count($arr)) {
        $resources = churchcore_getTableData("cr_resource");

        // custom sort function; TODO: wh not order by in db query???
        function cmp($a, $b) {
          if ($a["realstart"] == $b["realstart"]) return 0;
          else if ($a["realstart"] > $b["realstart"]) return 1;
          else -1;
        }
        usort($arr, "cmp");
        
        foreach ($arr as $val) {
          $txt .= "<li><p><a href='?q=churchresource&id=$val[id]'>$val[text]</a> ";
          if ($val["repeat_id"] > 0) {
            $txt .= '<img title="Serie startet vom ' . $val["startdate"]->format('d.m.Y H:i') .
                    '" src="' . CHURCHRESOURCE . '/images/recurring.png" width="16px"/> ';
          }
          $txt .= "(" . $resources[$val["resource_id"]]->bezeichnung . ")<br/><small>" .
               $val["realstart"]->format('d.m.Y H:i') . " " . $val["person_name"] . "</small><br/>";
        }
        if ($txt) $txt = "<ul>$txt</ul>";
      }
    }
  }
  return $txt;
}

/**
 * get blocks for home
 * @return array
 */
function churchresource_blocks() {
  return (array (
    1 => array (
      "label" => t("pending.booking.requests"), 
      "col" => 3, 
      "sortkey" => 1, 
      "html" => churchresource_getOpenBookings(),
      "help" => '',
      "class" => '',
    ), 
    2 => array (
      "label" => t("current.bookings"), 
      "col" => 3, 
      "sortkey" => 2, 
      "html" => churchresource_getCurrentBookings(),
      "help" => '',
      "class" => '',
    ),
  ));
}

/**
 * print view
 * echo html content
 */
function churchresource__printview() {
  global $user;
  
  drupal_add_js(ASSETS . "/js/jquery-1.10.2.min.js");
  drupal_add_js(ASSETS . "/js/jquery-migrate-1.2.1.min.js");
  
  drupal_add_js(CHURCHCORE . '/shortcut.js');
  drupal_add_js(ASSETS . '/js/jquery.history.js');
  
  drupal_add_js(ASSETS . '/ui/jquery.ui.core.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.datepicker.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.position.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.widget.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.dialog.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.mouse.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.draggable.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.resizable.min.js');
  
  drupal_add_js(CHURCHCORE . '/churchcore.js');
  drupal_add_js(CHURCHCORE . '/churchforms.js');
  drupal_add_js(CHURCHCORE . '/cc_abstractview.js');
  drupal_add_js(CHURCHCORE . '/cc_standardview.js');
  drupal_add_js(CHURCHCORE . '/cc_maintainstandardview.js');
  drupal_add_js(CHURCHCORE . '/cc_interface.js');
  
  drupal_add_js(CHURCHRESOURCE . '/cr_loadandmap.js');
  drupal_add_js(CHURCHRESOURCE . '/cr_maintainview.js');
  drupal_add_js(CHURCHRESOURCE . '/cr_weekview.js');
  drupal_add_js(CHURCHRESOURCE . '/cr_main.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchresource"));
  
  $content = "<html><head>" . drupal_get_header();
  $content .= '<link type="text/css" rel="stylesheet" media="all" href="' . phpLUDES . '/churchtools.css" />';
  $content .= '<link type="text/css" rel="stylesheet" media="all" href="' . CHURCHRESOURCE . '/cr_printview.css" />';
  $content .= "</head><body>";
  
  $content .= "<input type='hidden' id='printview' value='true'/>";
  
  if ($curdate = getVar("curdate")) $content .= "<input type='hidden' id='curdate' value='$curdate'/>";
  
  $content .= "<div id='cdb_f_ilter'></div></div> <div id='cdb_content'>Seite wird aufgebaut...</div>";
  $content .= "</body></html>";
  
  echo $content;
}

/**
 * get auth
 * @return auth
 */
function churchresource_getAuth() {
  $cc_auth = array ();
  $cc_auth = addAuth($cc_auth, 201, 'view', 'churchresource', null, t('view.modulname', 'ChurchResource'), 1);
  $cc_auth = addAuth($cc_auth, 306, 'create bookings', 'churchresource', null, t('create.bookings'), 1);
  $cc_auth = addAuth($cc_auth, 202, 'administer bookings', 'churchresource', 'cr_resource', t('administer.bookings'), 1);
  $cc_auth = addAuth($cc_auth, 203, 'assistance mode', 'churchresource', null, t('assistant.mode'), 1);
  $cc_auth = addAuth($cc_auth, 299, 'edit masterdata', 'churchresource', null, t('edit.masterdata'), 1);
  return $cc_auth;
}

/**
 * get auth for ajax
 * @return array auth
 */
function churchresource_getAuthForAjax() {
  $res = null;
  $auth = $_SESSION["user"]->auth["churchresource"];
  
  if (isset($auth["view"])) $res["view"] = true;
  if (isset($auth["create bookings"])) {
    $res["write"] = true;
  }
  if (isset($auth["administer bookings"])) {
    $res["write"] = true;
    // $res["editall"]=true;
    $res["edit"] = $auth["administer bookings"];
  }
  if (isset($auth["assistance mode"])) {
    $res["assistance mode"] = true;
  }
  
  // For assistance mode
  if (user_access("create person", "churchdb")) {
    $res["create person"] = true;
  }
  
  if (isset($auth["edit masterdata"])) {
    $res["admin"] = true;
  }
  return $res;
}

?>
