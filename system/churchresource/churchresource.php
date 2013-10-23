<?php

function churchresource__ajax() {
  include_once(drupal_get_path('module', 'churchresource').'/churchresource_ajax.inc');
  call_user_func("churchresource_ajax");
}

function churchresource_getAuth() {
  return "view churchresource";
}

function churchresource_getName() {
  global $config;
  return $config["churchresource_name"];
}

function churchresource_getAdminModel() {
  global $config;
  
  $model = new CC_ModulModel("churchresource");      
  $model->addField("churchresource_entries_last_days","", "INPUT_REQUIRED","Wieviel Tage zur&uuml;ck in ChurchResource-Daten geladen werden");
  $model->fields["churchresource_entries_last_days"]->setValue($config["churchresource_entries_last_days"]);  
  return $model;
}
  
  
function churchresource_main() {
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_interface.js'); 

  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_loadandmap.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_maintainview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_weekview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_main.js');
  
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

function churchresource_getOpenBookings() {
  $txt="";
  if (user_access("administer bookings","churchresource")) {      
    include_once("churchresource_db.inc");
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

    include_once("churchresource_db.inc");       
    
	// Alle buchungen ab jetzt bis morgen mit Status 2
	$res=getBookings(0, 1, "2");
	
	if ($res!=null) {
  	$arr=array();
      foreach ($res as $r) {
        $r->startdate=new DateTime($r->startdate);
        $r->enddate=new DateTime($r->enddate);
        foreach (getAllDatesWithRepeats($r,0,1) as $d) {
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
          if ($val["repeat_id"]>0) $txt.='<img title="Serie startet vom '.$val["startdate"]->format('d.m.Y H:i').'" src="system/churchresource/images/recurring.png" width="16px"/> ';        
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
      "label"=>"Offene Buchungsanfragen",
      "col"=>3,
      "sortkey"=>1,
      "html"=>churchresource_getOpenBookings()
    ),  
    2=>array(
      "label"=>"Aktuelle Buchungen",
      "col"=>3,
      "sortkey"=>2,
      "html"=>churchresource_getCurrentBookings()
    ),  
    ));
} 

function churchresource__printview() {
global $user;
  
  $content="<html><head>";
  
  drupal_add_js("system/assets/js/jquery.js");

  drupal_add_js(drupal_get_path('module', 'churchcore') .'/shortcut.js'); 
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_js('system/assets/ui/jquery.ui.core.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.datepicker.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.position.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.widget.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.dialog.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.mouse.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.draggable.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.resizable.min.js');
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchcore.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchforms.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_interface.js'); 

  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_loadandmap.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_maintainview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_weekview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_main.js');
   
  $content=$content.drupal_get_header();
  
//  drupal_add_css(drupal_get_path('module', 'churchresource').'/cr_printview.css');
//  $content=$content.drupal_get_css();
  $content=$content.'<link type="text/css" rel="stylesheet" media="all" href="'.drupal_get_path('module', 'churchcore').'/churchcore.css" />';
  $content=$content.'<link type="text/css" rel="stylesheet" media="all" href="'.drupal_get_path('module', 'churchresource').'/cr_printview.css" />';
    
  $content=$content."</head><body>";
  $content=$content."<input type=\"hidden\" id=\"printview\" value=\"true\"/>";

  if (isset($_GET["curdate"]) && ($_GET["curdate"]!=null))
    $content=$content."<input type=\"hidden\" id=\"curdate\" value=\"".$_GET["curdate"]."\"/>";
  
  $content=$content."<div id=\"cdb_f_ilter\"></div></div> <div id=\"cdb_content\">Seite wird aufgebaut...</div>";
  $content=$content."</body></html>";
  echo $content;
}

?>
