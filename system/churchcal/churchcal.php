<?php 

function churchcal_main() {
  global $config, $base_url, $config, $embedded;
  include_once("system/includes/forms.php");
  
  drupal_add_css('system/assets/fullcalendar/fullcalendar.css');
  if (isset($_GET["printview"]))
    drupal_add_css('system/assets/fullcalendar/fullcalendar.print.css');
  
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
    if (variable_get("churchcal_css", "-")!="-") {
      $txt.='<style>'.variable_get("churchcal_css").'</style>';
    }
    if (isset($_GET["cssurl"])) {
      drupal_add_css($_GET["cssurl"]);
    }    
    
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
    if (isset($_GET["printview"]))
      $txt.='<input type="hidden" id="printview" value="true"/>';
    if (isset($_GET["entries"]))
      $txt.='<input type="hidden" id="entries" value="'.$_GET["entries"].'"/>';
    if (isset($_GET["startdate"]))
      $txt.='<input type="hidden" id="init_startdate" value="'.$_GET["startdate"].'"/>';
    if (isset($_GET["enddate"]))
      $txt.='<input type="hidden" id="init_enddate" value="'.$_GET["enddate"].'"/>';
  }
  else   
    $txt.='<div class="row-fluid">
  					<div class="span3"><div id="cdb_filter"></div></div>
  					<div class="span9"><div id="header" class="pull-right"></div><div id="calendar"></div></div>'.
  			  '<p align=right><small>'.
  			  '<a target="_blank" href="'.$base_url.'?q=churchcal&embedded=true&category_id=null">'.$config["churchcal_name"].' einbetten</a>
  			  <a target="_clean" href="http://intern.churchtools.de/?q=churchwiki#WikiView/filterWikicategory_id:0/doc:ChurchCal%C2%A0einbetten/"><i class="icon-question-sign"></i></a>
  			    &nbsp; <a id="abo" href="'.$base_url.'?q=churchcal/ical">'.$config["churchcal_name"].' abonnieren per iCal</a>'.
  			    '</small>';
    
  if (isset($_GET["date"])) $txt.='<input type="hidden" name="viewdate" id="viewdate" value="'.$_GET["date"].'"/>';  
  if (isset($_GET["viewname"])) $txt.='<input type="hidden" name="viewname" id="viewname" value="'.$_GET["viewname"].'"/>';  
  return $txt;    
}

function churchcal_getAdminModel() {
  global $config;

  $model = new CC_ModulModel("churchcal");
  if (!isset($config["churchcal_maincalname"]))
    $config["churchcal_maincalname"]="Gemeindekalender";
  $model->addField("churchcal_maincalname","", "INPUT_REQUIRED","Name des Hauptkalenders");
  $model->fields["churchcal_maincalname"]->setValue($config["churchcal_maincalname"]);

  if (!isset($config["churchcal_css"]))
    $config["churchcal_css"]="";
  $model->addField("churchcal_css","", "TEXTAREA","CSS f&uuml;r das Einbetten des Kalenders");
  $model->fields["churchcal_css"]->setValue($config["churchcal_css"]);
  
  return $model;
}



function churchcal_getUserOpenMeetingRequests() {
  return '<div id="cc_openmeetingrequests"></div>';  
}
function churchcal_getUserMeetings() {
  return '<div id="cc_nextmeetingrequests"></div>';
}

function churchcal_blocks() {
  return (array(
      1=>array(
          "label"=>"Deine offenen Terminanfragen",
          "col"=>2,
          "sortkey"=>1,
          "html"=>churchcal_getUserOpenMeetingRequests(),
          "help"=>"Terminanfragen",
          "class"=>"cal-request"
      ),
      2=>array(
          "label"=>"Deine n&auml;chsten Terminzusagen",
          "col"=>2,
          "sortkey"=>2,
          "html"=>churchcal_getUserMeetings(),
          "help"=>"Terminanfragen",
          "class"=>"cal-request"
      )
      
      ));
}


function churchcal_getAuth() {
  $cc_auth = array();
  $cc_auth=addAuth($cc_auth, 401,'view', 'churchcal', null, 'ChurchCal sehen', 1);
  $cc_auth=addAuth($cc_auth, 403,'view category', 'churchcal', 'cc_calcategory', 'Einzelne Kalender sehen', 0);
  $cc_auth=addAuth($cc_auth, 404,'edit category', 'churchcal', 'cc_calcategory', 'In einzelnen Kalender Termine erstellen, editieren etc.', 0);
  //$cc_auth=addAuth($cc_auth, 407,'create personal category', 'churchcal', null, 'Pers&ouml;nlichen Kalender erstellen', 1);
  //$cc_auth=addAuth($cc_auth, 406,'admin personal category', 'churchcal', null, 'Pers&ouml;nliche Kalender administrieren', 1);
  $cc_auth=addAuth($cc_auth, 408,'create group category', 'churchcal', null, 'Gruppenkalender erstellen', 1);
  $cc_auth=addAuth($cc_auth, 405,'admin group category', 'churchcal', null, 'Gruppenkalender administrieren', 1);
  $cc_auth=addAuth($cc_auth, 402,'admin church category', 'churchcal', null, 'Gemeindekalender administrieren', 1);
  return $cc_auth;
}


function churchcal_getMyServices() {
  global $user;
  include_once(drupal_get_path('module', 'churchservice') .'/churchservice_db.inc');
  
  $res=churchservice_getUserCurrentServices($user->id);
  
  return $res;
}


/**
 * Wen group_id>0 dann nur die Gruppe, ansonsten hole aus allen meinen Gruppen die Daten
 * @param unknown_type $group_id
 * @return string|Ambigous <unknown, multitype:>
 */
function churchcal_getAbsents($params) {
  global $user;
  
  include_once(drupal_get_path('module', 'churchdb').'/churchdb_db.inc');
  $persons=array();
  
  if (isset($params["cal_ids"])) {
    $cal_ids=$params["cal_ids"];
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
  }
  if (isset($params["person_id"])) {
    $persons[$params["person_id"]]=$params["person_id"];
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
  return $arrs;
}

function churchcal_getBirthdays($params) {
  global $user;
  
  $all=(isset($params["all"])) && ($params["all"]==true);
  
  
  include_once("system/churchdb/churchdb_db.inc");
  
  if (!$all) {
    $gpids=churchdb_getMyGroups($user->id, true, false);
    if ($gpids==null) 
      return null;
    $res=db_query("select p.id, gp.geburtsdatum birthday, concat(p.vorname, ' ', p.name) as name 
             from {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp 
        where gpg.gruppe_id in (".implode(',',$gpids).") and gpg.gemeindeperson_id=gp.id and 
          gp.person_id=p.id and p.archiv_yn=0 and gp.geburtsdatum is not null");
    $arrs=array();
    foreach ($res as $a) {
      $arrs[$a->id]=$a;
    }  
    return $arrs;
  }
  else {
    $persons=churchdb_getAllowedPersonData("geburtsdatum is not null", "p.id p_id, p.id, gp.id gp_id, concat(p.vorname, ' ',p.name) as name, geburtsdatum birthday");
    return $persons;
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
    throw new CTNoPermission("Not allowed Category", "churchcal");
  
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
  return $ret;
}


/**
 * [gruppe|person][403|404][[auth,ids]]
 * @param unknown_type $params
 */
function churchcal_saveShares($params) {
  $log="";  
  $orig=churchcal_getShares($params);
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
  
  return $log;
}

function churchcal_addException($params) {
  $i = new CTInterface();
  $i->setParam("cal_id");
  $i->setParam("except_date_start");
  $i->setParam("except_date_end");
  $i->addModifiedParams();

  db_insert("cc_cal_except")->fields($i->getDBInsertArrayFromParams($params))->execute(false);
}

function churchcal_delException($params) {
  $i = new CTInterface();
  $i->setParam("id");

  db_delete("cc_cal_except")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
}

function churchcal_addAddition($params) {
  $i = new CTInterface();
  $i->setParam("cal_id");
  $i->setParam("add_date");
  $i->setParam("with_repeat_yn");
  $i->addModifiedParams();

  db_insert("cc_cal_add")->fields($i->getDBInsertArrayFromParams($params))->execute(false);
}

function churchcal_delAddition($params) {
  ct_log("del add", 1);
  $db=db_query("select cal_id from {cc_cal_add} where id=:id", array(":id"=>$params["id"]))->fetch();
  
  if ($db==false)
    throw new CTException("Manuellen Termin #"+$params["id"]+" nicht gefunden!");
  if (!churchcal_isAllowedToEditEvent($db->cal_id))
    throw new CTNoPermission("AllowToEditEvent", "churchcal");
    
  $i = new CTInterface();
  $i->setParam("id");

  db_delete("cc_cal_add")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
}

function churchcal_deleteEvent($params, $source=null) {
  $id=$params["id"];
  
  if (!churchcal_isAllowedToEditEvent($id))
    throw new CTNoPermission("AllowToEditEvent", "churchcal");
    
  // BENACHRICHTIGE ANDERE MODULE
  if (($source==null) || ($source!="churchresource")) {
    include_once(drupal_get_path('module', 'churchresource') .'/churchresource_db.inc');
    if ($source==null) $source="churchcal";
    $params["cal_id"]=$params["id"];
    churchresource_deleteResourcesFromChurchCal($params, $source);
  }
  if (($source==null) || ($source!="churchservice")) {
    include_once(drupal_get_path('module', 'churchservice') .'/churchservice_db.inc');
    $cs_params=array_merge(array(), $params);
    $cs_params["cal_id"]=$params["id"];
    $cs_params["informDeleteEvent"]=1;
    $cs_params["deleteCalEntry"]=0;    
    if ($source==null) $source="churchcal";
    $db=db_query("select * from {cs_event} where cc_cal_id=:cal_id", array(":cal_id"=>$cs_params["cal_id"]));
    foreach ($db as $cs) {
      $cs_params["id"]=$cs->id;
      churchservice_deleteEvent($cs_params, $source);
    }
  }  
  
  db_query("delete from {cc_cal_except} where cal_id=:id", array(":id"=>$id));
  db_query("delete from {cc_cal_add} where cal_id=:id", array(":id"=>$id));
  db_query("delete from {cc_cal} where id=:id", array(":id"=>$id));
}  


function churchcal_getResource($params) {
  $resource_ids=$params["resource_id"];
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
  
  return $ret;  
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
    
  return $ret;
}

function churchcal_iAmOwner($category_id) {
  global $user;
  if ($category_id==null) return false;
  $res=db_query('select modified_pid from {cc_calcategory} where id=:id', array(":id"=>$category_id))->fetch();
  if (!$res) return false;
  return $res->modified_pid==$user->id;
}

function churchcal_saveCategory($params) {
  global $user;
  $id=null;
  if (isset($params["id"])) $id=$params["id"];
  
  $auth=false;
  if ($params["privat_yn"]==1 && $params["oeffentlich_yn"]==0) {
    if ($id!=null) 
      $auth=user_access("admin personal category", "churchcal") || churchcal_iAmOwner($id);  
    else 
      $auth=user_access("admin personal category", "churchcal") || user_access("create personal category", "churchcal");
  }
  else if ($params["privat_yn"]==0 && $params["oeffentlich_yn"]==0) {
    if ($id!=null) 
      $auth=user_access("admin group category", "churchcal") || churchcal_iAmOwner($id);  
    else 
      $auth=user_access("admin group category", "churchcal") || user_access("create group category", "churchcal");
  }
  else if ($params["privat_yn"]==0 && $params["oeffentlich_yn"]==1) {
    $auth=user_access("admin church category", "churchcal") || churchcal_iAmOwner($id);  
  }
  if (!$auth) throw new CTNoPermission("Admin edit category", "churchcal");   
  
  $i = new CTInterface();
  $i->setParam("bezeichnung");
  $i->setParam("sortkey");
  $i->setParam("color");
  $i->setParam("privat_yn");

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
  return $id;    
}


function churchcal_deleteCategory($params) {
  global $user;
  $id=$params["id"];
  
  $data=db_query("select * from {cc_calcategory} where id=:id", array(":id"=>$id))->fetch();
  if ($data==false) return CTException("Kategorie nicht vorhanden");
  $auth=user_access("edit category", "churchcal");
  if (($data->modified_pid!=$user->id) && (($auth==null) || (!isset($auth[$id]))))
    throw new CTNoPermission("Edit Category", "churchcal");
  
  $c=db_query("select count(*) c from {cs_event} e, {cc_cal} cal where cal.id=e.cc_cal_id and cal.category_id=:id",
     array(":id"=>$id))->fetch();
  if ($c->c>0)
    throw new CTFail("Es sind noch Dienste zu dem Kalender verbunden. Kann ihn deshalb nicht entfernen!");
       
  db_query("delete from {cc_cal} where category_id=:id",  array(":id"=>$id));
  db_query("delete from {cc_calcategory} where id=:id",  array(":id"=>$id));
  db_query("delete from {cc_domain_auth} where auth_id in (403, 404) and daten_id=$id");
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

function churchcal_getCalEvents() {
  $ret=array();
  $ret["csevents"]=churchcal_getEventsFromOtherModules();
  if ($user!=null)
    $ret["calevents"]=churchcal_getAllEvents();
  else  
    $ret["calevents"]=churchcal_getAllEvents("intern_yn=0");
  return $ret;    
}

function churchcal_getAllowedGroups() {
  include_once(drupal_get_path('module', 'churchdb').'/churchdb_db.inc');
  return churchdb_getAllowedGroups();
}
  
function churchcal_getAllowedPersons() {
  include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
  return churchdb_getAllowedPersonData('archiv_yn=0');
}

function churchcal_moveCSEvent() {
  throw new CTException("Noch nicht fertig!");
  db_query("update {cs_event} set startdate=startdate+ TODO  ");
}


class CTChurchCalModule extends CTAbstractModule {
  public function getMasterData() {
    global $user, $base_url;
    $ret=array();
    $ret["modulespath"]=drupal_get_path('module', 'churchcal');
    $ret["churchservice_name"]=variable_get("churchservice_name");
    $ret["churchcal_name"]=variable_get("churchcal_name");
    $ret["churchresource_name"]=variable_get("churchresource_name");
    $ret["maincal_name"]=variable_get("churchcal_maincalname", "Gemeindekalender");
    $ret["base_url"]=$base_url;
    $ret["user_pid"]=$user->id;
    if (user_access("view","churchdb")) {
      $ret["absent_reason"]=churchcore_getTableData("cs_absent_reason");
    }
    if (user_access("view","churchresource") || user_access("create bookings","churchresource")) {
      $ret["resources"]=churchcore_getTableData("cr_resource");
      $ret["resourceTypes"]=churchcore_getTableData("cr_resourcetype");
      $ret["bookingStatus"]=churchcore_getTableData("cr_status");
    }
    $ret["category"]=churchcal_getAllowedCategories(true);
    $ret["settings"]=churchcore_getUserSettings("churchcal", $user->id);
    $ret["repeat"]=churchcore_getTableData("cc_repeat");
    if (count($ret["settings"])==0) {
      $arr["checkboxEvents"]="true";
      $ret["settings"]=$arr;
    }
    $ret["auth"]=churchcal_getAuthForAjax();  
    return $ret;
  } 
  
  
  
  public function getAllowedPeopleForCalender($params) {
    include_once('./'. drupal_get_path('module', 'churchdb') .'/churchdb_db.inc');
    $db=db_query("select * from {cc_domain_auth} where daten_id=:daten_id and auth_id=403",
        array(":daten_id"=>$params["category_id"]));
    $res=array();
    foreach ($db as $d) {
      if ($d->domain_type=="gruppe") {
        $g=array();
        $ids=churchdb_getAllPeopleIdsFromGroups(array($d->domain_id));
        if ($ids!=null) {
          foreach ($ids as $id) {
            $p=churchdb_getPersonDetails($id);
            if ($p!="no access") {
              $g[]=$p;
            }
          }
        }
        if (count($g)>0) {
          $gr=churchcore_getTableData("cdb_gruppe", null, "id=".$d->domain_id);
          if ($gr!=false)
            $res[]=array("type"=>"gruppe", "data"=>$g, "bezeichnung"=>$gr[$d->domain_id]->bezeichnung);
        }        
      }
      else if ($d->domain_type=="person") {
        $p=churchdb_getPersonDetails($d->domain_id);
        if ($p!="no access") {
          $res[]=array("type"=>"person", "data"=>$p);        
        }        
      }
    }
    return $res;
  }
}

function churchcal__ajax() {
  include_once("system/churchcal/churchcal_db.inc");
  
  $module=new CTChurchCalModule("churchcal");
  
  $ajax = new CTAjaxHandler($module);
  $ajax->addFunction("getCalEvents", "view"); 
  $ajax->addFunction("getCalPerCategory", "view");
  $ajax->addFunction("getAbsents", "view");
  $ajax->addFunction("getMyServices", "view", "churchservice");
  $ajax->addFunction("getBirthdays", "view"); 
  $ajax->addFunction("deleteCategory", "view"); 
  $ajax->addFunction("updateEvent", "view"); 
  $ajax->addFunction("createEvent", "view"); 
  $ajax->addFunction("getShares", "view"); 
  $ajax->addFunction("saveShares", "view"); 
  $ajax->addFunction("getResource", "view", "churchresource");
  $ajax->addFunction("getAllowedGroups", "view", "churchdb");
  $ajax->addFunction("getAllowedPersons", "view", "churchdb");
  $ajax->addFunction("saveCategory", "view");  
  $ajax->addFunction("delAddition", "view");
  $ajax->addFunction("deleteEvent", "view");

  // not ready
  $ajax->addFunction("moveCSEvent");
  
  drupal_json_output($ajax->call());
}


function churchcal__ical() {
  global $base_url, $config;
  include_once("system/churchcal/churchcal_db.inc");
  
  drupal_add_http_header('Content-Type','text/calendar;charset=utf-8',false);
  drupal_add_http_header('Content-Disposition','inline;filename="ChurchTools.ics"',false);  
  drupal_add_http_header('Cache-Control','must-revalidate, post-check=0, pre-check=0',false);  
  drupal_add_http_header('Cache-Control','private',true);
  $content=drupal_get_header();
  
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
  $arr=churchcal_getCalPerCategory(array("category_ids"=>$cats), false);
  
  $txt="";
  foreach ($arr as $cats) {
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
        if ($res->link!="")
          $txt.="URL:".$res->link."\r\n";
        else
          $txt.="URL:".$base_url."?q=churchcal\r\n";
        if ($res->ort!="")
          $txt.="LOCATION:".$res->ort."\r\n";
          
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
        
        $txt.='DESCRIPTION:Kalender:'.$cat_names[$res->category_id]->bezeichnung.' - Cal['.$res->id.'] - '.$res->notizen."\r\n"; 
        $txt.="END:VEVENT\r\n";
      } 
    }
  }
    
  echo surroundWithVCALENDER($txt);
}

?>
