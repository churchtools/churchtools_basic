<?php 

 include_once("system/churchcore/churchcore_db.inc");
 

// Source gibt an, ob es aus einem anderen Modul kommt. Dies steuert die Zusammenarbeit
function churchcal_createEvent($params, $source=null) {
  // Wenn es von anderem Modul kommt, habe ich da die Rechte schon geprŸft!  
  if (($source==null) && (!churchcal_isAllowedToEditCategory($params["category_id"]))) 
    throw new CTNoPermission("Keine Rechte beim Erstellen mit Id:".$params["category_id"], "churchcal");
  
  $i = new CTInterface();
  $i->setParam("startdate");
  $i->setParam("enddate");
  $i->setParam("bezeichnung");
  $i->setParam("category_id");
  $i->setParam("repeat_id");
  $i->setParam("repeat_until", false);
  $i->setParam("repeat_frequence", false);
  $i->setParam("repeat_option_id", false);
  $i->setParam("intern_yn");
  $i->setParam("notizen");
  $i->setParam("link");
  $i->setParam("ort");
  $i->addModifiedParams();
  
  $new_id=db_insert("cc_cal")->fields($i->getDBInsertArrayFromParams($params))->execute(false);
  
  if (isset($params["exceptions"])) {
    foreach ($params["exceptions"] as $exception) {
      $res=churchcal_addException(array("cal_id"=>$new_id, 
                               "except_date_start"=>$exception["except_date_start"], 
                               "except_date_end"=>$exception["except_date_end"]));
      if ($res["status"]!="success") 
        return $res;
    }
  }  
  if (isset($params["additions"])) {
    foreach ($params["additions"] as $addition) {
      $res=churchcal_addAddition(array("cal_id"=>$new_id, 
                               "add_date"=>$addition["add_date"],
                               "with_repeat_yn"=>$addition["with_repeat_yn"]));
      if ($res["status"]!="success") 
        return $res;
    }
  }  
  // BENACHRICHTIGE ANDERE MODULE
  if (($source==null) || ($source!="churchresource")) {
    include_once(drupal_get_path('module', 'churchresource') .'/churchresource_db.inc');
    $params["id"]=$new_id;
    churchresource_updateResourcesFromChurchCal($params, "churchcal");           
  }
  if (($source==null) || ($source!="churchservice")) {
    if ((!isset($params["repeat_id"]) || ($params["repeat_id"]==0))) {
      include_once(drupal_get_path('module', 'churchservice') .'/churchservice_db.inc');
      $cs_params=array_merge(array(), $params); 
      $cs_params["cal_id"]=$new_id;
      $cs_params["id"]=null; 

      
      $o=(object) $params;
      $o->startdate=new DateTime($o->startdate);
      $o->enddate=new DateTime($o->enddate);
      $diff=$o->enddate->format("U")-$o->startdate->format("U");
      if (!isset($o->repeat_until)) $o->repeat_until=$o->enddate->format('Y-m-d H:i:s');
      // Das mit repeat_id wird noch nicht von JavaScript angesteiuert. Das ist zu kompliziert mit
      // Wiederholungsterminen, falls es €nderungen gibt. 
      // Ist nur schon mal pro forma drin. Gibt Probleme bei Addition wg. Array/Object-Typ...
      foreach (getAllDatesWithRepeats($o,-1000,+1000) as $d) {
        $cs_params["startdate"]=$d->format('Y-m-d H:i:s');
        $enddate=clone $d;
        $enddate->modify("+$diff seconds"); 
        $cs_params["enddate"]=$enddate->format('Y-m-d H:i:s');
        // Wenn es kopiert werden soll
        if ((isset($cs_params["copychurchservice"])) && ($cs_params["copychurchservice"]=="true")) {
          churchservice_copyEventByCalId($params["orig_id"], $new_id, $cs_params["startdate"], true);
        } 
        // Ansonsten eben neu anlegen
        else {
          churchservice_saveEvent($cs_params, "churchcal");               
        }
      }
    }      
  }
  return $new_id;  
}  

function churchcal_isAllowedToEditCategory($category_id) {
  if ($category_id==null) return false;
 
  $arr=churchcal_getAuthForAjax();
  if (!isset($arr["edit category"])) return false;
  if (isset($arr["edit category"][$category_id])) return true;
  return false;    
}

function churchcal_updateEvent($params, $source=null) {
  $arr=array();
  
  // Nur Rechte prŸfen, wenn ich source bin, denn sonst hat das das Ursprungsmodul schon erledigt
  if ($source==null) {
    // PrŸfe, ob ich auf neue Kategorie schrieben darf
    if (!churchcal_isAllowedToEditCategory($params["category_id"])) 
      return CTNoPermission("AllowedToEditCategory[".$params["category_id"]."]", "churchcal");
    // PrŸfe, ob ich auf die vorhandene Kategorie schreiben darf
    $old_cal=db_query("select category_id, startdate from {cc_cal} where id=:id", array(":id"=>$params["id"]))->fetch();
    if (!churchcal_isAllowedToEditCategory($old_cal->category_id)) 
      return CTNoPermission("AllowedToEditCategory[".$old_cal->category_id."]", "churchcal");
      }
  
  // Wenn es nur eine Verschiebung auf dem Kalender ist
  if (!isset($params["repeat_id"])) {
    $i = new CTInterface();
    $i->setParam("startdate");
    $i->setParam("enddate");
    $i->setParam("bezeichnung", false);
    $i->setParam("category_id", false);
    db_update("cc_cal")->fields($i->getDBInsertArrayFromParams($params))
      ->condition("id", $params["id"], "=")
      ->execute();
  }
  else {   
    $arr[":event_id"]=$params["id"];
    $arr[":startdate"]=$params["startdate"];
    $arr[":enddate"]=$params["enddate"];               
    $arr[":bezeichnung"]=$params["bezeichnung"];
    $arr[":ort"]=$params["ort"];
    $arr[":intern_yn"]=$params["intern_yn"];
    $arr[":notizen"]=str_replace('\"','"',$params["notizen"]);
    $arr[":link"]=$params["link"];
    $arr[":category_id"]=$params["category_id"];    
    if (isset($params["repeat_id"])) $arr[":repeat_id"]=$params["repeat_id"]; else $arr[":repeat_id"]=null;
    if (isset($params["repeat_until"])) $arr[":repeat_until"]=$params["repeat_until"]; else $arr[":repeat_until"]=null;
    if (isset($params["repeat_frequence"])) $arr[":repeat_frequence"]=$params["repeat_frequence"]; else $arr[":repeat_frequence"]=null;
    if (isset($params["repeat_option_id"])) $arr[":repeat_option_id"]=$params["repeat_option_id"]; else $arr[":repeat_option_id"]=null;
    
    db_query("update {cc_cal} set startdate=:startdate, enddate=:enddate, bezeichnung=:bezeichnung, ort=:ort,
      notizen=:notizen, link=:link, category_id=:category_id, intern_yn=:intern_yn, category_id=:category_id, 
      repeat_id=:repeat_id, repeat_until=:repeat_until, repeat_frequence=:repeat_frequence,
      repeat_option_id=:repeat_option_id 
        where id=:event_id", $arr);
    
    // Hole alle Exceptions aus der DB
    $exc=churchcore_getTableData("cc_cal_except",null, "cal_id=".$params["id"]);
    // Vergleiche erst mal welche schon in der DB sind oder noch nicht in der DB sind.         
    if (isset($params["exceptions"])) {
      foreach ($params["exceptions"] as $exception) {
        if ($exception["id"]>0) {
          $exc[$exception["id"]]->vorhanden=true;
        }
        else {
          $res=churchcal_addException(array("cal_id"=>$params["id"], 
                                   "except_date_start"=>$exception["except_date_start"], 
                                   "except_date_end"=>$exception["except_date_end"]));
          if ($res["status"]!="success") 
            return $res;
        }
      }
    }  
    // Lšsche nun alle, die in der DB sind, aber nicht mehr vorhanden sind.
    if ($exc!=false) {
      foreach ($exc as $e) {
        if (!isset($e->vorhanden))
          churchcal_delException(array("id"=>$e->id));
      }
    }

    
    // Hole alle Additions aus der DB
    $add=churchcore_getTableData("cc_cal_add",null, "cal_id=".$params["id"]);
    // Vergleiche erst mal welche schon in der DB sind oder noch nicht in der DB sind.         
    if (isset($params["additions"])) {
      foreach ($params["additions"] as $addition) {
        if ($addition["id"]>0) {
          $add[$addition["id"]]->vorhanden=true;
        }
        else {
          $res=churchcal_addAddition(array("cal_id"=>$params["id"], 
                                   "add_date"=>$addition["add_date"],
                                   "with_repeat_yn"=>$addition["with_repeat_yn"]));
          if ($res["status"]!="success") 
            return $res;
        }
      }
    }  
    // Lšsche nun alle, die in der DB sind, aber nicht mehr vorhanden sind.
    if ($add!=false) {
      foreach ($add as $a) {
        if (!isset($a->vorhanden))
          churchcal_delAddition(array("id"=>$a->id));
      }
    }
  }
  
  // BENACHRICHTIGE ANDERE MODULE
  if (($source==null) || ($source!="churchresource")) {
    include_once(drupal_get_path('module', 'churchresource') .'/churchresource_db.inc');
    if ($source==null) $source="churchcal";
    $params["cal_id"]=$params["id"];
    churchresource_updateResourcesFromChurchCal($params, $source);           
  }
  if (($source==null) || ($source!="churchservice")) {
    include_once(drupal_get_path('module', 'churchservice') .'/churchservice_db.inc');
    $cs_params=array_merge(array(), $params); 
    $cs_params["cal_id"]=$params["id"];
    $cs_params["id"]=null;   
    $cs_params["old_startdate"]=$old_cal->startdate;
    if ($source==null) $source="churchcal";
    churchservice_saveEvent($cs_params, $source);    
  }        
}  

 
function churchcal_getAuthForAjax() {
  global $user;
    
  $ret=array();
  if (($user!=null) && (isset($_SESSION["user"]->auth["churchcal"]))) {
    $ret=$_SESSION["user"]->auth["churchcal"];
    
    include_once("system/churchdb/churchdb_db.inc");
    $mygroups=churchdb_getMyGroups($user->id, true, true);
    if (isset($ret["view category"])) {
      foreach ($ret["view category"] as $key=>$edit) {
        $db=db_query("select * from {cc_domain_auth} where domain_type='gruppe'  
                        and auth_id=403 and daten_id=:daten_id", array(":daten_id"=>$edit));
        foreach ($db as $auth) {
          if (in_array($auth->domain_id, $mygroups)) 
            $ret["edit category"][$key]=$edit;
        }
      }        
    }
    
    
    // Wenn man edit-Rechte hat, bekommt man auch automatisch View-Rechte.
    if (isset($ret["edit category"])) {
      foreach ($ret["edit category"] as $key=>$edit) {
        $ret["view category"][$key]=$edit;
      }        
    }
  }
  
  if (user_access("view", "churchservice") && user_access("edit events", "churchservice"))
    $ret["view churchservice"]=true;
  
  if (user_access("view", "churchdb")) {
    $ret["view churchdb"]=true;
    if (user_access("view alldata", "churchdb")!=null)
      $ret["view alldata"]=true;
  }
  
  if (user_access("view", "churchresource"))
    $ret["view churchresource"]=true;
  
  if (user_access("create bookings", "churchresource"))
    $ret["create bookings"]=true;
  
  if (user_access("administer bookings", "churchresource"))
    $ret["administer bookings"]=true;
  
  return $ret;    
}

function churchcal_getAllowedCategories($withPrivat=true, $onlyIds=false) {
  global $user;

  include_once("system/churchdb/churchdb_db.inc");
  $db=db_query("select * from {cc_calcategory}");

  $res=array();
  $auth=churchcal_getAuthForAjax();

  $privat_vorhanden=false;
    
  foreach ($db as $category) {
    if (($category->privat_yn==1) && ($category->modified_pid==$user->id))
      $privat_vorhanden=true;
      
    if (($category->privat_yn==0) || ($withPrivat)) {
      // Zugriff, weil ich View-Rechte auf die Kategorie habe
      if (((isset($auth["view category"])) && (isset($auth["view category"][$category->id])))
           || ((isset($auth["edit category"])) && (isset($auth["edit category"][$category->id]))))  {
        if ($onlyIds) $res[$category->id]=$category->id;
        else $res[$category->id]=$category;
      }
/*      // Zugriff, weil ich ihn erstellt habe
      else if (($user->id!=-1) && ($category->modified_pid==$user->id)) {
        if ($onlyIds) $res[$category->id]=$category->id;
        else $res[$category->id]=$category;
      }*/
    }
  }   
  if ((!$privat_vorhanden) && ($user->id>0) && (user_access("personal category", "churchcal"))) {
    $dt=new datetime();
    $id=db_insert("cc_calcategory")
    ->fields(array("bezeichnung"=>$user->vorname."s Kalender", "sortkey"=>0, 
           "oeffentlich_yn"=>0, "privat_yn"=>1, "color"=>"black", "modified_date"=>$dt->format('Y-m-d H:i:s'), "modified_pid"=>$user->id))
    ->execute();
    // ErgŠnze noch das Recht fŸr die Person
    db_query("insert into {cc_domain_auth} (domain_type, domain_id, auth_id, daten_id)
                  values ('person', $user->id, 404, $id)");

    $_SESSION["user"]->auth=getUserAuthorization($_SESSION["user"]->id);              
    churchcore_saveUserSetting("churchcal", $user->id, "filterMeineKalender", "[".($id+100)."]");
    return churchcal_getAllowedCategories($withPrivat, $onlyIds);
  }
  else { 
    return $res;
  }  
}


function churchcal_getCalPerCategory($params, $withintern=true) {
  $data=array();
  
  $res=db_query("select cal.*, e.id event_id, 
                 b.id booking_id, b.startdate booking_startdate, b.enddate booking_enddate, b.resource_id booking_resource_id, b.status_id booking_status_id 
               from {cc_cal} cal
               left join {cs_event} e on (cal.id=e.cc_cal_id) 
               left join {cr_booking} b on (cal.id=b.cc_cal_id) 
               where cal.category_id in (".implode(",",$params["category_ids"]).")
                ".(!$withintern?" and intern_yn=0":"")." 
               order by category_id");
  $data=null;
  
  // Agreggiere, falls es mehrere Bookings pro calendareintrag gibt.
  foreach ($res as $arr) {
    if (isset($data[$arr->id])) {
      $elem=$data[$arr->id];
    }
    else $elem=$arr;
    if ($arr->booking_id!=null) {      
      $elem->bookings[$arr->booking_resource_id]=
        array("id"=>$arr->booking_id, "minpre"=>(strtotime($arr->startdate)-strtotime($arr->booking_startdate))/60, 
                                      "minpost"=>(strtotime($arr->booking_enddate)-strtotime($arr->enddate))/60, 
                "resource_id"=>$arr->booking_resource_id, "status_id"=>$arr->booking_status_id);
    }
    $data[$arr->id]=$elem;      
  }

  if ($data==null) {
    return array();
  }
  
  $excepts=churchcore_getTableData("cc_cal_except");
  if ($excepts!=null)
    foreach ($excepts as $val) {
      // Kann sein, dass es Exceptions gibt, wo es kein Termin mehr gibt.
      if (isset($data[$val->cal_id])) {
        if (!isset($data[$val->cal_id]->exceptions))
          $a=array();
        else $a=$data[$val->cal_id]->exceptions;
        $b=new stdClass();
        $b->id=$val->id;
        $b->except_date_start=$val->except_date_start;
        $b->except_date_end=$val->except_date_end;
        $a[$val->id]=$b;          
        $data[$val->cal_id]->exceptions=$a;
      }
    }
  $excepts=churchcore_getTableData("cc_cal_add");
  if ($excepts!=null)
    foreach ($excepts as $val) {
      // Kann sein, dass es Additions gibt, wo es kein Termin mehr gibt.
      if (isset($data[$val->cal_id])) {
        if (!isset($data[$val->cal_id]->additions))
          $a=array();
        else $a=$data[$val->cal_id]->additions;
        $b=new stdClass();
        $b->id=$val->id;
        $b->add_date=$val->add_date;
        $b->with_repeat_yn=$val->with_repeat_yn;
        $a[$val->id]=$b;          
        $data[$val->cal_id]->additions=$a;
      }
    }    
    
  $ret=array();  
  foreach ($params["category_ids"] as $cat) {
    $ret[$cat]=array();
    foreach ($data as $d) {
      if ($d->category_id==$cat)
        $ret[$cat][$d->id]=$d;      
    }       
  }
  
  return $ret;  
}

?>
