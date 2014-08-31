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
 *
 */

  
function churchresource_main() {
  drupal_add_js(ASSETS.'/js/jquery.history.js'); 
  
  drupal_add_js(CHURCHCORE .'/cc_abstractview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_standardview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_maintainstandardview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_interface.js'); 

  drupal_add_js(CHURCHRESOURCE .'/cr_loadandmap.js');
  drupal_add_js(CHURCHRESOURCE .'/cr_maintainview.js');
  drupal_add_js(CHURCHRESOURCE .'/cr_weekview.js');
  drupal_add_js(CHURCHRESOURCE .'/cr_main.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchresource"));
  
  $content='';

  // Übergabe der ID für den Direkteinstieg eines Eintrags
  if (isset($_GET["id"]) && ($_GET["id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"filter_id\" value=\"".$_GET["id"]."\"/>";

  //$content=$content."<div id=\"cdb_menu\"></div> <div id=\"cdb_filter\"></div> <div id=\"cdb_content\">Fehler: Ist JavaScript deaktiviert?</div>";
  $content=$content.
  $content=$content." 
<div class=\"row-fluid\">
  <div class=\"span3\">
    <div id=\"cdb_menu\"></div>
    <div id=\"cdb_filter\"></div>
  </div>  
  <div class=\"span9\">
    <div id=\"cdb_search\"></div> 
    <div id=\"cdb_group\"></div> 
    <div id=\"cdb_content\"></div>
  </div>
</div>";
  return $content;
}  

function churchresource__ajax() {
  include_once("churchresource_db.php");
  
  $module=new CTChurchResourceModule("churchresource");

  $ajax = new CTAjaxHandler($module);
  $ajax->addFunction("delException", "administer bookings"); 
  $ajax->addFunction("delBooking", "edit masterdata"); 
  $ajax->addFunction("createBooking", "view"); 
  $ajax->addFunction("updateBooking", "view"); 
  
  drupal_json_output($ajax->call());  
}

function churchresource_getAdminForm() {
  global $config;
  
  $model = new CTModuleForm("churchresource");      
  $model->addField("churchresource_entries_last_days","", "INPUT_REQUIRED","Wieviel Tage zur&uuml;ck in ChurchResource-Daten geladen werden");
  $model->fields["churchresource_entries_last_days"]->setValue($config["churchresource_entries_last_days"]);  
  return $model;
}  

function churchresource_getOpenBookings() {
  $txt="";
  if (user_access("administer bookings","churchresource")) {      
    include_once("churchresource_db.php");
    $arr=getOpenBookings();
	if ($arr!=null) {
      foreach ($arr as $val) {
        $txt=$txt."<li><p><a href=\"?q=churchresource&id=$val->id\">$val->text</a> ($val->resource)<br/><small>$val->startdate $val->person_name</small><br/>";
      }
      if ($txt!="") 
        $txt="<ul>$txt</ul>";
	}
  }	
  return $txt;  
}

function churchresource_getCurrentBookings() {
  $txt="";
  if (user_access("view","churchresource")) {      
    include_once("churchresource_db.php");       
    
	// Alle buchungen ab jetzt bis morgen mit Status 2
	$res=getBookings(0, 1, "2");
	if ($res!=null) {
  	  $arr=array();
      $counter=0;
  	  foreach ($res as $r) {
        $r->startdate=new DateTime($r->startdate);
        $r->enddate=new DateTime($r->enddate);
        $ds=getAllDatesWithRepeats($r,0,1);
        if ($ds!=null) {
          foreach ($ds as $d) {
            $counter=$counter+1;
            $a=array();
            $a["realstart"]=new DateTime($d->format('Y-m-d H:i:s'));
            $a["startdate"]=$r->startdate;
            $a["enddate"]=$r->enddate;
            $a["person_name"]=$r->person_name;
            $a["resource_id"]=$r->resource_id;
            $a["repeat_id"]=$r->repeat_id;
            $a["text"]=$r->text;
            $a["id"]=$r->id;
            $arr[]=$a;
          }
        }
  	  }
      
      if ($arr!=null) {
        $resources=churchcore_getTableData("cr_resource");
        function cmp($a, $b) {
          if ($a["realstart"]==$b["realstart"]) return 0;
          else 
            if ($a["realstart"]>$b["realstart"]) return 1; else -1;
        }
        usort($arr, "cmp");
        
        foreach ($arr as $val) {
          $txt.="<li><p><a href=\"?q=churchresource&id=".$val["id"]."\">".$val["text"]."</a> ";
          if ($val["repeat_id"]>0) $txt.='<img title="Serie startet vom '.$val["startdate"]->format('d.m.Y H:i').'" src="'.CHURCHRESOURCE.'/images/recurring.png" width="16px"/> ';        
          $txt.="(".$resources[$val["resource_id"]]->bezeichnung.")<br/><small>".$val["realstart"]->format('d.m.Y H:i')." ".$val["person_name"]."</small><br/>";
        }
        if ($txt!="") 
          $txt="<ul>$txt</ul>"; 
      }
	}
	
  }	
  return $txt;
}

function churchresource_blocks() {
  return (array(
    1=>array(
      "label"=>t("pending.booking.requests"),
      "col"=>3,
      "sortkey"=>1,
      "html"=>churchresource_getOpenBookings()
    ),  
    2=>array(
      "label"=>t("current.bookings"),
      "col"=>3,
      "sortkey"=>2,
      "html"=>churchresource_getCurrentBookings()
    ),  
    ));
} 

function churchresource__printview() {
global $user;
  
  $content="<html><head>";
  
  drupal_add_js(ASSETS."/js/jquery-1.10.2.min.js");
  drupal_add_js(ASSETS."/js/jquery-migrate-1.2.1.min.js");
  
  drupal_add_js(CHURCHCORE .'/shortcut.js'); 
  drupal_add_js(ASSETS.'/js/jquery.history.js'); 
  
  drupal_add_js(ASSETS.'/ui/jquery.ui.core.min.js');
  drupal_add_js(ASSETS.'/ui/jquery.ui.datepicker.min.js');
  drupal_add_js(ASSETS.'/ui/jquery.ui.position.min.js');
  drupal_add_js(ASSETS.'/ui/jquery.ui.widget.min.js');
  drupal_add_js(ASSETS.'/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js(ASSETS.'/ui/jquery.ui.dialog.min.js');
  drupal_add_js(ASSETS.'/ui/jquery.ui.mouse.min.js');
  drupal_add_js(ASSETS.'/ui/jquery.ui.draggable.min.js');
  drupal_add_js(ASSETS.'/ui/jquery.ui.resizable.min.js');
  
  drupal_add_js(CHURCHCORE .'/churchcore.js'); 
  drupal_add_js(CHURCHCORE .'/churchforms.js'); 
  drupal_add_js(CHURCHCORE .'/cc_abstractview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_standardview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_maintainstandardview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_interface.js'); 

  drupal_add_js(CHURCHRESOURCE .'/cr_loadandmap.js');
  drupal_add_js(CHURCHRESOURCE .'/cr_maintainview.js');
  drupal_add_js(CHURCHRESOURCE .'/cr_weekview.js');
  drupal_add_js(CHURCHRESOURCE .'/cr_main.js');
   
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchresource"));
  
  $content=$content.drupal_get_header();
  
  $content=$content.'<link type="text/css" rel="stylesheet" media="all" href="'.phpLUDES.'/churchtools.css" />';
  $content=$content.'<link type="text/css" rel="stylesheet" media="all" href="'.CHURCHRESOURCE.'/cr_printview.css" />';
    
  $content=$content."</head><body>";
  $content=$content."<input type=\"hidden\" id=\"printview\" value=\"true\"/>";

  if (isset($_GET["curdate"]) && ($_GET["curdate"]!=null))
    $content=$content."<input type=\"hidden\" id=\"curdate\" value=\"".$_GET["curdate"]."\"/>";
  
  $content=$content."<div id=\"cdb_f_ilter\"></div></div> <div id=\"cdb_content\">Seite wird aufgebaut...</div>";
  $content=$content."</body></html>";
  echo $content;
}

function churchresource_getAuth() {
  $cc_auth = array();
  $cc_auth=addAuth($cc_auth, 201,'view', 'churchresource', null, 'ChurchResource sehen', 1);
  $cc_auth=addAuth($cc_auth, 306,'create bookings', 'churchresource', null, 'Eigene Buchugsanfragen erstellen', 1);
  $cc_auth=addAuth($cc_auth, 202,'administer bookings', 'churchresource', 'cr_resource', 'Alle Anfragen editieren, ablehnen, etc.', 1);
  $cc_auth=addAuth($cc_auth, 203,'assistance mode', 'churchresource', null, 'Im Auftrag eines anderen Buchungen durchf&uuml;hren', 1);
  $cc_auth=addAuth($cc_auth, 299,'edit masterdata', 'churchresource', null, 'Stammdaten editieren', 1);
  return $cc_auth;
}



function churchresource_getAuthForAjax() {
  $res=null;
  $auth=$_SESSION["user"]->auth["churchresource"];
  
  if (isset($auth["view"]))
    $res["view"]=true;
  if (isset($auth["create bookings"])) {
  	$res["write"]=true;
  }  
  if (isset($auth["administer bookings"])) {
  	$res["write"]=true;
  	//$res["editall"]=true;
  	$res["edit"]=$auth["administer bookings"];
  }
  if (isset($auth["assistance mode"])) {
    $res["assistance mode"]=true;
  }
  
  // For assistance mode
  if (user_access("create person", "churchdb")) {
    $res["create person"]=true;
  }
  
  if (isset($auth["edit masterdata"])) {
    $res["admin"]=true;
  }
  return $res;
}

?>
