<?php 

function churchcal_main() {
  global $config, $base_url, $config, $embedded;
  include_once("system/includes/forms.php");
  
  
  drupal_add_css('system/assets/fullcalendar/fullcalendar.css');
  drupal_add_css('system/assets/simplecolorpicker/jquery.simplecolorpicker.css');
  drupal_add_js('system/assets/simplecolorpicker/jquery.simplecolorpicker.js');
  
  drupal_add_js('system/assets/fullcalendar/fullcalendar.min.js');
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js('system/churchcal/eventview.js');
  drupal_add_js('system/churchcal/yearview.js');
  drupal_add_js('system/churchcal/calendar.js');
  drupal_add_js('system/churchcal/cal_sources.js');
  
  $txt='';

  if ((isset($_GET["category_id"])) && ($_GET["category_id"]!="") && ($_GET["category_id"]!="null"))
    $txt.='<input type="hidden" id="filtercategory_id" name="category_id" value="'.$_GET["category_id"].'"/>';
    
  if ($embedded) {
    if ((isset($_GET["category_select"])) && ($_GET["category_select"]!="") && ($_GET["category_select"]!="null"))
      $txt.='<input type="hidden" id="filtercategory_select" name="category_select" value="'.$_GET["category_select"].'"/>';
    if ((isset($_GET["minical"]) && ($_GET["minical"]=="true")))
      $txt.='<input type="hidden" id="isminical"/>';
    $txt.='<div class="row-fluid">';
    $txt.='<div id="cdb_filter"></div>';
    $txt.='</div>';
    $txt.='<div id="calendar"></div>';
    $txt.='<input type="hidden" id="isembedded"/>';
    if (isset($_GET["title"]))
      $txt.='<input type="hidden" id="embeddedtitle" value="'.$_GET["title"].'"/>';
    if (isset($_GET["entries"]))
      $txt.='<input type="hidden" id="entries" value="'.$_GET["entries"].'"/>';
  }
  else   
    $txt.='<div class="row-fluid">
  					<div class="span3"><div id="cdb_filter"></div></div>
  					<div class="span9"><div id="header" class="pull-right"></div><div id="calendar"></div></div>
  			  </div><p align=right><small><a target="_blank" href="'.$base_url.'?q=churchcal&embedded=true&category_id=null">'.$config["churchcal_name"].' einbetten</a>
  			  <a target="_clean" href="http://intern.churchtools.de/?q=churchwiki#WikiView/filterWikicategory_id:0/doc:ChurchCal%C2%A0einbetten/"><i class="icon-question-sign"></i></a>
  			    &nbsp; <a id="abo" href="'.$base_url.'?q=churchcal/ical">'.$config["churchcal_name"].' abonnieren per iCal</a></small>';
    
  if (isset($_GET["date"])) $txt.='<input type="hidden" name="viewdate" id="viewdate" value="'.$_GET["date"].'"/>';  
  if (isset($_GET["viewname"])) $txt.='<input type="hidden" name="viewname" id="viewname" value="'.$_GET["viewname"].'"/>';  
  return $txt;    
}

function churchcal_getAdminModel() {
  $model = new CC_ModulModel("churchcal");      
  return $model;
}



function churchcal_getMyServices() {
  global $user;
  include_once(drupal_get_path('module', 'churchservice') .'/churchservice_db.inc');
  
  $res=churchservice_getUserCurrentServices($user->id);
  
  return jsend()->success($res);
}


/**
 * Wen group_id>0 dann nur die Gruppe, ansonsten hole aus allen meinen Gruppen die Daten
 * @param unknown_type $group_id
 * @return string|Ambigous <unknown, multitype:>
 */
function churchcal_getAbsents($cal_ids) {
  global $user;
  
  include_once(drupal_get_path('module', 'churchdb').'/churchdb_db.inc');
  
  $persons=array();
  // Wer hat explizit Freigaben fŸr den Kalender?
  $db=db_query("select * from  {cc_domain_auth} d where d.auth_id=403 and d.daten_id in (".implode(",",$cal_ids).")");
  if ($db!=false) { 
    foreach ($db as $auth) {
      if ($auth->domain_type=="person")
        $persons[$auth->domain_id]=$auth->domain_id;
      else if ($auth->domain_type=="gruppe") {
        $allPersonIds=churchdb_getAllPeopleIdsFromGroups(array($auth->domain_id));
        if ($allPersonIds!=false) {
          foreach ($allPersonIds as $id) {
            $persons[$id]=$id;
          }
        }
      }          
    }
  }  
  
  $arrs=array();
  if (count($persons)>0) {
    // Hole nun die Abwesenheit dazu
    $res=db_query("select p.id p_id, a.startdate, a.enddate, p.vorname, p.name, absent_reason_id reason_id
             from {cs_absent} a, {cdb_person} p 
               where p.id in (".implode(',',$persons).") and a.person_id=p.id");
    foreach ($res as $a) {
      $arrs[]=$a;
    }
  }  
  return jsend()->success($arrs);
}

function churchcal_getBirthdays($all=false) {
  global $user;
  
  include_once("system/churchdb/churchdb_db.inc");
  
  if (!$all) {
    $gpids=churchdb_getMyGroups($user->id, true, false);
    if ($gpids==null) 
      return jsend()->success();
    $res=db_query("select p.id, gp.geburtsdatum birthday, concat(p.vorname, ' ', p.name) as name 
             from {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp 
        where gpg.gruppe_id in (".implode(',',$gpids).") and gpg.gemeindeperson_id=gp.id and 
          gp.person_id=p.id and p.archiv_yn=0 and gp.geburtsdatum is not null");
    $arrs=array();
    foreach ($res as $a) {
      $arrs[$a->id]=$a;
    }  
    return jsend()->success($arrs);
  }
  else {
    $persons=churchdb_getAllowedPersonData("geburtsdatum is not null", "p.id p_id, p.id, gp.id gp_id, concat(p.vorname, ' ',p.name) as name, geburtsdatum birthday");
    return jsend()->success($persons);
  }
}  

/**
 * [gruppe|person][403|404][[auth,ids]]
 * @param unknown_type $params
 */
function churchcal_getShares($params) {
  // 403=read, 404=edit
  $cat=churchcal_getAllowedCategories(true, true);
  if (!in_array($params["cat_id"], $cat))  
    return jsend()->fail("Keine Rechte!");
  
  $db=db_query("select * from {cc_domain_auth} where auth_id in (403,404) and domain_type in ('gruppe','person') and daten_id=:cat_id",
      array(":cat_id"=>$params["cat_id"]));
  $ret=array();
  if ($db!=false)
    foreach($db as $auth) {
      $domaintype=array();
      if (isset($ret[$auth->domain_type])) $domaintype=$ret[$auth->domain_type];
      $authid=array();
      if (isset($domaintype[$auth->auth_id])) $authid=$domaintype[$auth->auth_id];
      $authid[]=$auth->domain_id;
      $domaintype[$auth->auth_id]=$authid;
      $ret[$auth->domain_type]=$domaintype;      
    }    
  return jsend()->success($ret);
}


/**
 * [gruppe|person][403|404][[auth,ids]]
 * @param unknown_type $params
 */
function churchcal_saveShares($params) {
  $log="";  
  $orig2=churchcal_getShares($params);
  $orig=$orig2["data"];
  // Ich gehe das ursprŸngliche durch 
  $domaintypes=array();
  $domaintypes[]="person";
  $domaintypes[]="gruppe";
  foreach ($domaintypes as $domaintype) {
    if (isset($orig[$domaintype])) {
      foreach ($orig[$domaintype] as $key_authid=>$authid) {
        // Ich schaue was nicht mehr dabei ist und lšsche
        foreach ($authid as $domainid) {
          if ((!isset($params[$domaintype])) || (!isset($params[$domaintype][$key_authid])) || (!in_array($domainid, $params[$domaintype][$key_authid]))) {
            $log.="<p>Entferne $domaintype, $key_authid, $domainid";
              db_query("delete from {cc_domain_auth} where domain_type=:domaintype and domain_id=:domain_id
                          and auth_id=:auth_id and daten_id=:daten_id",
                array(':domain_id'=>$domainid, ":domaintype"=>$domaintype, ":auth_id"=>$key_authid, ":daten_id"=>$params["cat_id"]));
          }
        }
      }          
    }
    // Ich schaue was neu dabei ist und fŸge hinzu!
    if (isset($params[$domaintype])) {
      foreach ($params[$domaintype] as $key_authid=>$authid) {
        foreach ($authid as $domainid) {
          $log.="<p>Suche $domaintype, $key_authid, $domainid";
          if ((!isset($orig[$domaintype])) || (!isset($orig[$domaintype][$key_authid])) || (!in_array($domainid, $orig[$domaintype][$key_authid]))) {
            $log.="<p>Erganze $domaintype, $key_authid, $domainid";
              db_query("insert into {cc_domain_auth} (domain_type, domain_id, auth_id, daten_id)
               values( :domaintype, :domain_id, :auth_id, :daten_id)",
              array(':domain_id'=>$domainid, ":domaintype"=>$domaintype, ":auth_id"=>$key_authid, ":daten_id"=>$params["cat_id"]));
          }
        }
      }
    }     
  }  
  
  return jsend()->success($log);
}

function churchcal_addException($params) {
  $i = new CTInterface();
  $i->setParam("cal_id");
  $i->setParam("except_date_start");
  $i->setParam("except_date_end");
  $i->addModifiedParams();

  try {
    db_insert("cc_cal_except")->fields($i->getDBInsertArrayFromParams($params))->execute(false);
  } 
  catch (Exception $e) {
    return jsend()->error($e);      
  }
  return jsend()->success();  
}
function churchcal_delException($params) {
  $i = new CTInterface();
  $i->setParam("id");

  try {
    db_delete("cc_cal_except")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("id", $params["id"], "=")
      ->execute(false);
  } 
  catch (Exception $e) {
    return jsend()->error($e);      
  }
  return jsend()->success();  
}
function churchcal_addAddition($params) {
  $i = new CTInterface();
  $i->setParam("cal_id");
  $i->setParam("add_date");
  $i->setParam("with_repeat_yn");
  $i->addModifiedParams();

  try {
    db_insert("cc_cal_add")->fields($i->getDBInsertArrayFromParams($params))->execute(false);
  } 
  catch (Exception $e) {
    return jsend()->error($e);      
  }
  return jsend()->success();  
}
function churchcal_delAddition($params) {
  $i = new CTInterface();
  $i->setParam("id");

  try {
    db_delete("cc_cal_add")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("id", $params["id"], "=")
      ->execute(false);
  } 
  catch (Exception $e) {
    return jsend()->error($e);      
  }
  return jsend()->success();  
}

function churchcal_deleteEvent($id) {
  db_query("delete from {cc_cal_except} where cal_id=:id", array(":id"=>$id));
  db_query("delete from {cc_cal_add} where cal_id=:id", array(":id"=>$id));
  db_query("delete from {cc_cal} where id=:id", array(":id"=>$id));
  return jsend()->success();  
}  


function churchcal_getResource($resource_ids) {
  $res=db_query("select r.id resource_id, r.bezeichnung ort, s.bezeichnung status, b.status_id, 
       b.id, b.startdate, b.enddate, b.repeat_id, b.repeat_frequence, b.repeat_until, b.repeat_option_id, b.text bezeichnung 
    from {cr_resource} r, {cr_booking} b, {cr_status} s 
    where b.status_id!=99 and s.id=b.status_id and b.resource_id=r.id and r.id in (".implode(",",$resource_ids).")");
  $excs=churchcore_getTableData("cr_exception", "except_date_start");
  $adds=churchcore_getTableData("cr_addition", "add_date");
  $arrs=array();
  foreach ($res as $a) {
    if ($excs!=false) {
      foreach($excs as $exc) {
        if ($a->id==$exc->booking_id) {
          $a->exceptions[$exc->id]=$exc;      
        }      
      }
    }
    if ($adds!=false) {
      foreach($adds as $add) {
        if ($a->id==$add->booking_id) {
          $a->additions[$add->id]=$add;      
        }      
      }
    }
    $arrs[]=$a;
  }  
  
  $ret=array();  
  foreach ($resource_ids as $id) {
    $ret[$id]=array();
    foreach ($arrs as $d) {
      if ($d->resource_id==$id)
        $ret[$id][$d->id]=$d;      
    }       
  }
    
  
  return jsend()->success($ret);  
}

function churchcal_getEventsFromOtherModules() {
  $res = db_query("SELECT e.id, e.datum startdate, e.bezeichnung, category_id FROM {cs_event} e, {cs_category} c where e.category_id=c.id and c.show_in_churchcal_yn=1");
  $arrs=null;
  foreach ($res as $arr) {
    $arrs[$arr->id]=$arr;
  }
  // HOle nun noch ressourcentermine, die im Kalendar explizit angezeigt werden sollen
  $res=db_query("select r.id resource_id, r.bezeichnung ort,
       b.id, b.startdate, b.enddate, b.repeat_id, b.repeat_frequence, b.repeat_until, b.text bezeichnung 
    from {cr_resource} r, {cr_booking} b 
    where b.status_id!=99 and b.resource_id=r.id and b.show_in_churchcal_yn=1");
  $excs=churchcore_getTableData("cr_exception", "except_date_start");
  $adds=churchcore_getTableData("cr_addition", "add_date");
  //$arrs=array();
  foreach ($res as $a) {
    if ($excs!=false) {
      foreach($excs as $exc) {
        if ($a->id==$exc->booking_id) {
          $a->exceptions[$exc->id]=$exc;      
        }      
      }
    }
    if ($adds!=false) {
      foreach($adds as $add) {
        if ($a->id==$add->booking_id) {
          $a->additions[$add->id]=$add;      
        }      
      }
    }
    $arrs[]=$a;
  }  
  return $arrs;   
}


function churchcal_getAllEvents($cond="") {
  $ret=array();
  
  $ret=churchcore_getTableData("cc_cal","",$cond);

  $excepts=churchcore_getTableData("cc_cal_except");
  if ($excepts!=null)
    foreach ($excepts as $val) {
      // Kann sein, dass es Exceptions gibt, wo es kein Termin mehr gibt.
      if (isset($ret[$val->cal_id])) {
        if (!isset($ret[$val->cal_id]->exceptions))
          $a=array();
        else $a=$ret[$val->cal_id]->exceptions;
        $b=new stdClass();
        $b->id=$val->id;
        $b->except_date_start=$val->except_date_start;
        $b->except_date_end=$val->except_date_end;
        $a[$val->id]=$b;          
        $ret[$val->cal_id]->exceptions=$a;
      }
    }
  $excepts=churchcore_getTableData("cc_cal_add");
  if ($excepts!=null)
    foreach ($excepts as $val) {
      // Kann sein, dass es Additions gibt, wo es kein Termin mehr gibt.
      if (isset($ret[$val->cal_id])) {
        if (!isset($ret[$val->cal_id]->additions))
          $a=array();
        else $a=$ret[$val->cal_id]->additions;
        $b=new stdClass();
        $b->id=$val->id;
        $b->add_date=$val->add_date;
        $b->with_repeat_yn=$val->with_repeat_yn;
        $a[$val->id]=$b;          
        $ret[$val->cal_id]->additions=$a;
      }
    }    
    
  return jsend()->success($ret);
}

function churchcal_saveCategory($params) {
  global $user;
  
  $i = new CTInterface();
  $i->setParam("bezeichnung");
  $i->setParam("sortkey");
  $i->setParam("color");
  $i->setParam("privat_yn");

  try {
    if ((!isset($params["id"])) || ($params["id"]==null)) {
      // Offentlich wird nur beim Insert festgelegt
      $i->addModifiedParams();
      $i->setParam("oeffentlich_yn");
      $i->setParam("randomurl");
      $params["randomurl"]=random_string(32);  
      $id=db_insert("cc_calcategory")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->execute(false);

      // ErgŠnze noch das Recht fŸr den Autor
      db_query("insert into {cc_domain_auth} (domain_type, domain_id, auth_id, daten_id)
                    values ('person', $user->id, 404, $id)");
      $_SESSION["user"]->auth=getUserAuthorization($_SESSION["user"]->id);     

      if ((isset($params["accessgroup"])) && ($params["accessgroup"]!="")) {
        if ((isset($params["writeaccess"])) && ($params["writeaccess"]==true)) {
          db_query("insert into {cc_domain_auth} (domain_type, domain_id, auth_id, daten_id)
                    values ('gruppe', ".$params["accessgroup"].", 404, $id)");          
        }
        else {
          db_query("insert into {cc_domain_auth} (domain_type, domain_id, auth_id, daten_id)
                    values ('gruppe', ".$params["accessgroup"].", 403, $id)");          
        }
      }
      
    }
    else {  
      $c=db_query("select * from {cc_calcategory} where id=:id", array(":id"=>$params["id"]))->fetch();
      $id=$params["id"];
      db_update("cc_calcategory")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("id", $params["id"], "=")
      ->execute(false);
    }
  } 
  catch (Exception $e) {
    return jsend()->error($e);      
  }
  return jsend()->success($id);    
}


function churchcal_deleteCategory($id) {
  global $user;
  
  $data=db_query("select * from {cc_calcategory} where id=:id", array(":id"=>$id))->fetch();
  if ($data==false) return jsend()->error("Kategorie nicht vorhanden");
  $auth=user_access("edit category", "churchcal");
  if (($data->modified_pid!=$user->id) && (($auth==null) || (!isset($auth[$id]))))
    return jsend()->error("Keine Rechte");
  
  $c=db_query("select count(*) c from {cs_event} e, {cc_cal} cal where cal.id=e.cc_cal_id and cal.category_id=:id",
     array(":id"=>$id))->fetch();
  if ($c->c>0)
    return jsend()->fail("Es sind noch Dienste zu dem Kalender verbunden. Kann ihn deshalb nicht entfernen!");
       
  db_query("delete from {cc_cal} where category_id=:id",  array(":id"=>$id));
  db_query("delete from {cc_calcategory} where id=:id",  array(":id"=>$id));
  db_query("delete from {cc_domain_auth} where auth_id in (403, 404) and daten_id=$id");
  return jsend()->success();
}

/**
 * 
 * @param $id Id von cc_cal.
 * Entweder ist man Autor des Termins oder man hat EditCategory-Rechte
 */
function churchcal_isAllowedToEditEvent($id) {
  global $user;
  
  $data=db_query("select * from {cc_cal} where id=:id", array(":id"=>$id))->fetch();  
  if ($data==false) throw new CTException("Termin #$id nicht gefunden!");  
  $auth=user_access("edit category", "churchcal");

  // Wenn ich es angelegt habe, darf ich es weiter editieren!
  if (($data!=false) && ($data->modified_pid==$user->id))
    return true;
     
  if (($auth!=null) && (!isset($auth[$id])))
    return true;
    
  return false;      
}

function churchcal__ajax() {
  global $user, $base_url;
   
  $user_pid=$user->id;
  include_once("system/churchcal/churchcal_db.inc");
  if (isset($_GET["func"]))
    $func=$_GET["func"];
  else   
    $func=$_POST["func"];
  $ret=null;
  if ($func=="getCalEvents") {
    $ret["csevents"]=churchcal_getEventsFromOtherModules();
    if ($user!=null)
      $ret["calevents"]=churchcal_getAllEvents();
    else  
      $ret["calevents"]=churchcal_getAllEvents("intern_yn=0");      
  }
  if ($func=="getCalPerCategory") {
    $ret=churchcal_getCalPerCategory($_GET["category_ids"]);      
  }
  else if ($func=="getAbsents") {
    $ret=churchcal_getAbsents($_GET["cal_ids"]);  
  }
  else if ($func=="getMyServices") {
    if (!user_access("view","churchservice"))
      $ret=jsend()->fail("Nicht genug Rechte!");
    else  
      $ret=churchcal_getMyServices();  
  }
  else if ($func=="getBirthdays") {
    if (user_access("view","churchdb"))
      $ret=churchcal_getBirthdays((isset($_GET["all"])) && ($_GET["all"]==true));
    else $ret=jsend()->fail("Keine Rechte!");   
  }
  else if ($func=="deleteCategory") {
    $ret=churchcal_deleteCategory($_GET["id"]);
  }
  else if ($func=="saveCategory") {
    // Wenn er existiert
    if ((isset($_GET["id"])) && (churchcal_isAllowedToEditCategory($_GET["id"])))
      $ret=churchcal_saveCategory($_GET);
    else if (($_GET["privat_yn"]==1) && ($_GET["oeffentlich_yn"]==0) && (user_access("personal category", "churchcal")))  
      $ret=churchcal_saveCategory($_GET);
    else if (($_GET["privat_yn"]==0) && ($_GET["oeffentlich_yn"]==0) && (user_access("group category", "churchcal")))  
      $ret=churchcal_saveCategory($_GET);
    else if (($_GET["privat_yn"]==0) && ($_GET["oeffentlich_yn"]==1) && (user_access("church category", "churchcal")))  
      $ret=churchcal_saveCategory($_GET);
    else $ret=jsend()->fail("Keine Rechte!");   
  }
  else if ($func=="delAddition") {
    if (churchcal_isAllowedToEditEvent($_GET["id"])) {
      $ret=churchcal_delAddition($_GET);
    }
    else $ret=jsend()->fail("Keine Rechte!");   
  }
  else if ($func=="getResource") {
    if (user_access("view","churchresource"))
      $ret=churchcal_getResource($_GET["resource_id"]);
    else $ret=jsend()->fail("Keine Rechte!");   
  }
  else if ($func=="getAllowedGroups") {
    include_once(drupal_get_path('module', 'churchdb').'/churchdb_db.inc');
    $ret=jsend()->success(churchdb_getAllowedGroups());
  }
  else if ($func=="getAllowedPersons") {
    include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
    $ret=jsend()->success(churchdb_getAllowedPersonData('archiv_yn=0'));
  }
  else if ($func=="updateEvent") {
    $ret=churchcal_updateEvent($_POST);  
  }
  else if ($func=="createEvent") {
    $ret=churchcal_createEvent($_POST);  
  }
  else if ($func=="getShares") {
    $ret=churchcal_getShares($_GET);  
  }
  else if ($func=="saveShares") {
    $ret=churchcal_saveShares($_GET);  
  }
  else if ($func=="moveCSEvent") {
    $ret=jsend()->error("Noch nicht fertig!");
    db_query("update {cs_event} set startdate=startdate+ TODO  ");
  }
  else if ($func=="deleteEvent") {
    if (churchcal_isAllowedToEditEvent($_GET["id"]))
      $ret=churchcal_deleteEvent($_GET["id"]);  
    else $ret=jsend()->fail("Keine Rechte!");   
  }
  else if ($func=="getMasterData") {
    $ret=array();
    $ret["modulespath"]=drupal_get_path('module', 'churchcal');
    $ret["churchservice_name"]=variable_get("churchservice_name");
    $ret["churchcal_name"]=variable_get("churchcal_name");
    $ret["churchresource_name"]=variable_get("churchresource_name");
    $ret["base_url"]=$base_url;
    $ret["user_pid"]=$user->id;
    if (user_access("view","churchdb")) {
      $ret["absent_reason"]=churchcore_getTableData("cs_absent_reason");
    }
    if (user_access("view","churchresource")) {
      $ret["resourcen"]=churchcore_getTableData("cr_resource");
      $ret["resourceTypes"]=churchcore_getTableData("cr_resourcetype");
    }
    $ret["category"]=churchcal_getAllowedCategories(true);
      $ret["settings"]=churchcore_getUserSettings("churchcal", $user_pid);
    $ret["repeat"]=churchcore_getTableData("cc_repeat");
    if (count($ret["settings"])==0) {
      $arr["checkboxEvents"]="true";
      $ret["settings"]=$arr;
    }
    $ret["auth"]=churchcal_getAuthForAjax();
    
  }
  else if ($func=="saveSetting") {
    churchcore_saveUserSetting("churchcal", $user_pid, $_GET["sub"], $_GET["val"]);
    $ret="ok";
  }
  else $ret="Error: Unkown function!";
  echo json_encode($ret);
}


function churchcal__ical() {
  global $base_url;
  include_once("system/churchcal/churchcal_db.inc");
  
  drupal_add_http_header('Content-Type','text/calendar;charset=utf-8',false);
  drupal_add_http_header('Content-Disposition','inline;filename="ChurchTools.ics"',false);  
  drupal_add_http_header('Cache-Control','must-revalidate, post-check=0, pre-check=0',false);  
  drupal_add_http_header('Cache-Control','private',true);
  $content=drupal_get_header();

  $txt="BEGIN:VCALENDAR\r\n"; 
  $txt.="VERSION:2.0\r\n"; 
  $txt.="PRODID:-//ChurchTools//DE\r\n"; 
  $txt.="CALSCALE:GREGORIAN\r\n"; 
  $txt.="X-WR-CALNAME:".variable_get('site_name', 'drupal')." ChurchCal-Kalender\r\n";
  $txt.="X-WR-TIMEZONE:Europe/Berlin\r\n"; 
  $txt.="METHOD:PUSH\r\n"; 
  
  /* nicht mehr notwendig, sind ja nun im cal drin!
  $arr=db_query("SELECT cal.bezeichnung event, e.id id ,
                DATE_FORMAT(e.startdate, '%Y%m%dT%H%i00') datum_start,  DATE_FORMAT(e.datum+ INTERVAL 90 MINUTE, '%Y%m%dT%H%i00') datum_end
                 FROM {cs_event} e, {cc_cal} cal,  {cs_category} c
             WHERE e.cc_cal_id=cal.id and e.category_id=c.id and c.show_in_churchcal_yn=1 and 
                  e.datum>current_date - INTERVAL 61 DAY order by e.datum");
    
  foreach ($arr as $res) {
    $txt.="BEGIN:VEVENT\r\n"; 
    $txt.="ORGANIZER:MAILTO:".variable_get('site_mail', '')."\r\n";
    $txt.="SUMMARY:".$res->event."\r\n";
    //$txt.="X-MICROSOFT-CDO-BUSYSTATUS:BUSY\r\n"; 
    $txt.="URL:".$base_url."?q=churchcal\r\n"; 
    $txt.="UID:".$res->id."\r\n"; 
    $txt.="DTSTART:".$res->datum_start."\r\n"; 
    $txt.="DTEND:".$res->datum_end."\r\n"; 
    $txt.="DESCRIPTION:CS[$res->id]\r\n"; 
    $txt.="END:VEVENT\r\n"; 
  }*/
  
  $cat_names=null;
  
  if ((isset($_GET["security"]) && (isset($_GET["id"])))) {
    $db=db_query("select * from {cc_calcategory} where id=:id and randomurl=:randomurl",
       array(":id"=>$_GET["id"], ":randomurl"=>$_GET["security"]))->fetch();
    if ($db!=false) {
      $cat_names[$_GET["id"]]=new stdClass();
      $cat_names[$_GET["id"]]->bezeichnung=$db->bezeichnung;  
      $cats=array(0=>$_GET["id"]);
    }
  }
  
  if ($cat_names==null) {
    $cats=churchcal_getAllowedCategories(false, true);
    $cat_names=churchcal_getAllowedCategories(false, false);
  }
  $arr=churchcal_getCalPerCategory($cats, false);
  
  
  foreach ($arr["data"] as $cats) {
    foreach ($cats as $res) {
    
      $res->startdate=new DateTime($res->startdate);
      $res->enddate=new DateTime($res->enddate);
      $diff=$res->enddate->format("U")-$res->startdate->format("U");
      $subid=0;
      
      foreach (getAllDatesWithRepeats($res, -90, 400) as $d) {
        $txt.="BEGIN:VEVENT\r\n"; 
        $txt.="ORGANIZER:MAILTO:".variable_get('site_mail', '')."\r\n";
        $txt.="SUMMARY:".$res->bezeichnung."\r\n";
        //$txt.="X-MICROSOFT-CDO-BUSYSTATUS:BUSY\r\n"; 
        $txt.="URL:".$base_url."?q=churchcal\r\n";
        $subid++; 
        $txt.="UID:".$res->id."_$subid\r\n";
        $txt.="DTSTAMP:".churchcore_stringToDateICal($res->modified_date)."\r\n";
        $ts=$diff+$d->format("U");
        //$enddate=new DateTime("@$ts");
        $enddate=clone $d;
        $enddate->modify("+$diff seconds"); 
        
        // Ganztagestermin
        if (($res->startdate->format('His')=="000000") && ($res->enddate->format('His')=="000000")) {        
          $txt.="DTSTART;VALUE=DATE:".$d->format('Ymd')."\r\n";
          $txt.="DTEND;VALUE=DATE:".date('Ymd', strtotime('+1 day', $enddate->format("U")))."\r\n";        
        }
        else { 
          $txt.="DTSTART:".$d->format('Ymd\THis')."\r\n";
          $txt.="DTEND:".$enddate->format('Ymd\THis')."\r\n";
        }
        
        $txt.="DESCRIPTION:Kalender:".$cat_names[$res->category_id]->bezeichnung."; Cal[$res->id]\r\n"; 
        $txt.="END:VEVENT\r\n";
      } 
    }
  }
    
  $txt.="END:VCALENDAR\r\n";

  echo $txt;
}

?>
