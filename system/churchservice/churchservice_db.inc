<?php

include_once('./'. drupal_get_path('module', 'churchservice') .'/../churchcore/churchcore_db.inc');

/**
 * Checks if the agenda is a template
 * @param unknown $agenda_id
 * @throws CTException - When Agenda could not be found
 * @return boolean
 */
function churchservice_isAgendaTemplate($agenda_id) {
  $agenda=db_query("select * from {cs_agenda} where id=:id", array(":id"=>$agenda_id))->fetch();
  if (!$agenda) throw new CTException("Agenda konnte nicht gefunden werden");
  return $agenda->template_yn==1;  
}

/**
 * Check, if I have a service in this event
 * @param unknown $event_id
 */
function churchservice_amIInvolved($event_id) {
  global $user;
  $db=db_query("select * from {cs_eventservice} where event_id=:event_id and cdb_person_id=:p_id
               and valid_yn=1", array(":event_id"=>$event_id, ":p_id"=>$user->id))->fetch();
  return $db!=false; 
}

function churchservice_copyEventByCalId($orig_cal_id, $new_cal_id, $new_startdate, $allservices=true) {
  $event=db_query('select * from {cs_event} where cc_cal_id=:cal_id', array(":cal_id"=>$orig_cal_id))->fetch();
  if ($event!=false) {
    $new_id=db_insert("cs_event")
            ->fields(array("cc_cal_id"=>$new_cal_id, "startdate"=>$new_startdate, "special"=>$event->special, "admin"=>$event->admin))
            ->execute(false);
    if ($allservices) {
      $services=db_query("select * from {cs_eventservice} where event_id=$event->id and valid_yn=1");
      $fields=array();
      $fields["event_id"]=$new_id;
      foreach ($services as $s) {
        $fields["service_id"]=$s->service_id;
        $fields["counter"]=$s->counter;
        $fields["valid_yn"]=$s->valid_yn;
        $fields["zugesagt_yn"]=$s->zugesagt_yn;
        $fields["name"]=$s->name;
        $fields["cdb_person_id"]=$s->cdb_person_id;
        $fields["reason"]=$s->reason;
        // Nicht gesetzt, damit eine neue Anfrage fŸr das andere Datum gesendet wird.
//        $fields["mailsenddate"]=$s->mailsenddate;
        $fields["modified_date"]=$s->modified_date;
        $fields["modifieduser"]=$s->modifieduser;
        $fields["modified_pid"]=$s->modified_pid;
        db_insert("cs_eventservice")
            ->fields($fields)
            ->execute(false);
      }        
    }
  }  
  else {
    throw new CTException("Event in ".$config["churchservice_name"]." konnte nicht kopiert werden, da es nicht gefunden wurde.");
  }
}


function _convertCTDateTimeToObjects($params) {
  $o=(object) $params;
  $o->startdate=new DateTime($o->startdate);
  $o->enddate=new DateTime($o->enddate);
  $o->diff=$o->enddate->format("U")-$o->startdate->format("U");
  if (!isset($o->repeat_until)) $o->repeat_until=$o->enddate->format('Y-m-d H:i:s');
  
  // Convert Exceptions and Additions to Object
  if (isset($o->exceptions) && $o->exceptions!=null) {
    foreach ($o->exceptions as $key=>$exc) {
      $o->exceptions[$key]=(object) $exc;
    }
  }
  if (isset($o->additions) && $o->additions!=null) {
    foreach ($o->additions as $key=>$exc) {
      $o->additions[$key]=(object) $exc;
    }
  }
  return $o;  
}

function churchservice_createEventFromChurchCal($params, $source=null) {
  $o=_convertCTDateTimeToObjects($params);  
  foreach (getAllDatesWithRepeats(_convertCTDateTimeToObjects($params),-1000,+1000) as $d) {
    $params["startdate"]=$d->format('Y-m-d H:i:s');
    $enddate=clone $d;
    $enddate->modify("+$o->diff seconds");
    $params["enddate"]=$enddate->format('Y-m-d H:i:s');
    // Wenn es kopiert werden soll
    if ((isset($params["copychurchservice"])) && ($params["copychurchservice"]=="true")) {
      churchservice_copyEventByCalId($params["orig_id"], $params["cal_id"], $params["startdate"], true);
    }
    // Ansonsten eben neu anlegen
    else if (isset($params["eventTemplate"])) {
      churchservice_saveEvent($params, "churchcal");
    }
  }  
}


/*
 * This function is called by ChurchCal when changes in calendar entries 
 * First it will use old_startdate to move all events. Then it checks if there is create or delete
 * event through changes in repeats, exceptions or additions
 */
function churchservice_updateEventFromChurchCal($params, $source=null) {
  $diff=null;
  // When $params["old_startdate"] is set, first the events are moved 
  // to the diff of startdate-old_startdate
  if (isset($params["old_startdate"])) {
    $startdate=new DateTime($params["startdate"]);
    $old_startdate=new DateTime($params["old_startdate"]);
    $diff=$startdate->format("U")-$old_startdate->format("U");    
    $db=db_query("select id, startdate from {cs_event} e where e.cc_cal_id=:cal_id", array(":cal_id"=>$params["cal_id"]));
    foreach ($db as $e) {
      $sd=new DateTime($e->startdate);
      $sd->modify("+$diff seconds");
      db_update("cs_event")
        ->fields(array("startdate"=>$sd->format('Y-m-d H:i:s')))
        ->condition('id',$e->id,"=")
        ->execute();      
    }    
  }
  
  // When repeat_id is not given, this is only a time shift. So we can end the processing here.
  if (!isset($params["repeat_id"])) return;  
  
  // Collect events in one array to collect the info which has to be created/deleted/updated
  $events=array();
  // Get all mapped events from DB
  $db=db_query("select id, startdate from {cs_event} e where e.cc_cal_id=:cal_id", array(":cal_id"=>$params["cal_id"]));
  foreach ($db as $e) {
    $sd=new DateTime($e->startdate);
    $events[$sd->format('Y-m-d')]=array("status"=>"delete", "id"=>$e->id);
  }
  $o=_convertCTDateTimeToObjects($params);
  foreach (getAllDatesWithRepeats($o,-1000,+1000) as $d) {
    $sd=$d->format('Y-m-d');
    if (isset($events[$sd])) {
      // Event was already moved above through old_startdate
      $events[$sd]["status"]="ok";
    }
    else          
      $events[$sd]=array("status"=>"create");    
    $events[$sd]["startdate"]=$d->format('Y-m-d H:i:s');
  }
  $template=null;
  if (isset($params["eventTemplate"])) $template=$params["eventTemplate"];
  foreach ($events as $key=>$do) {
    if ($do["status"]=="delete") {
      $params["id"]=$do["id"];
      $params["informDeleteEvent"]=1;
      $params["deleteCalEntry"]=0;
      churchservice_deleteEvent($params, $source);
    }
    else if ($do["status"]=="create" && $template!=null) {
      $params["id"]=null;
      $params["startdate"]=$do["startdate"];
      $params["eventTemplate"]=$template;
      churchservice_saveEvent($params, $source);
    }
  }
}


// Erstellt oder Updated ein Event
// Wenn eventTemplate Ÿbergeben wird, dann holt er die Daten aus dem Template, ansonsten nutzt er services 
function churchservice_saveEvent($params, $source=null) {
  global $user;
  
  include_once(drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
  $cal_id=null;
  if (($source=="churchcal") && ($params["id"]==null)) {
    $cal_id=$params["cal_id"];  
  }
  else {
    // Hole mir erst mal die zugehšrige cc_cal_id, falls es das Event schon gibt.
    if (isset($_GET["id"])) {
      $cal_id=db_query("select cc_cal_id from {cs_event} where id=:id", array(":id"=>$_GET["id"]))->fetch()->cc_cal_id;
    }   
  }

  // Erst mal das cs_event updated/inserten
  $fields=array();
  $fields["startdate"]=$params["startdate"];
  if ($source==null) {
    $fields["special"]=(isset($params["special"])?$params["special"]:"");
    $fields["admin"]=(isset($params["admin"])?$params["admin"]:"");
  }
  
  if (isset($params["eventTemplate"])) {
    $db=db_query('select special, admin from {cs_eventtemplate} where id=:id', array(":id"=>$params["eventTemplate"]))->fetch();
    if ($db!=false) {
      if ((!isset($fields["special"])) ||Ê($fields["special"]==""))
        $fields["special"]=$db->special;
      if ((!isset($fields["admin"])) ||Ê($fields["admin"]==""))
        $fields["admin"]=$db->admin;
    }    
  }
  
  
  if (isset($params["id"])) {
    $event_id=$params["id"];
    db_update("cs_event")
      ->fields($fields)
      ->condition('id',$params["id"],"=")
      ->execute();
      
    // BENACHRICHTIGE ANDERE MODULE  
    if ($source==null) {
      $cal_params=array_merge(array(), $params);
      $cal_params["event_id"]=$event_id;
      $cal_params["id"]=$cal_id; 
      churchcal_updateEvent($cal_params, "churchservice");
    }
  }
  else {
    if ($source==null) {
      $params["repeat_id"]=0;
      $params["intern_yn"]=0;
      $params["notizen"]="";
      $params["link"]="";
      $params["ort"]="";        
      $cal_id=churchcal_createEvent($params, "churchservice");
    }
    $fields["cc_cal_id"]=$cal_id;
    if (isset($params["eventTemplate"]))
      $fields["created_by_template_id"]=$params["eventTemplate"];
    $event_id=db_insert("cs_event")->fields($fields)->execute();
  }  
  
  if ((!isset($params["eventTemplate"])) && (isset($params["services"]))) {
    // Nun die Eintrï¿½ge updaten/inserten
    $rm_services = array();
    $new_services = array();
    $fields=array();
    
    $fields["event_id"]=$event_id;
    $dt = new datetime();
    $fields["valid_yn"]=1;
    $fields["modified_date"]=$dt->format('Y-m-d H:i:s');
    $fields["modified_pid"]=$user->id;
    foreach ($params["services"] as $key=>$arr) {
      $fields["service_id"]=$key;
      $fields["counter"]=null;
      if ($arr==1) {
        db_insert("cs_eventservice")->fields($fields)->execute();          
      }    
      else {
        $i=$arr;
        while ($i>0) {
          $fields["counter"]=$i;
          $i--;
          db_insert("cs_eventservice")->fields($fields)->execute();                    
        }
      }
    }    
  }
  // Also wenn ein Template Ÿbergeben wurde
  else if (isset($params["eventTemplate"])){
    if (isset($params["id"])) {
      print_r($params);
      throw new CTException("Es kann kein Template uebergeben werden, wenn der Service schon existiert!");      
    } 
      
    $fields=array();
    $fields["event_id"]=$event_id;
    $fields["valid_yn"]=1;
    $dt = new datetime();
    $fields["modified_date"]=$dt->format('Y-m-d H:i:s');
    $fields["modified_pid"]=$user->id;
    $db=db_query("select * from {cs_eventtemplate_service} where eventtemplate_id=:eventtemplate_id",
       array(':eventtemplate_id'=>$params["eventTemplate"]));
    foreach($db as $d) {
      $fields["service_id"]=$d->service_id;
      if ($d->count==1) {
        $fields["counter"]=null;
        db_insert("cs_eventservice")->fields($fields)->execute();
      }
      else {
        $i=$d->count;
        while ($i>0) {
          $fields["counter"]=$i;
          $i--;
          db_insert("cs_eventservice")->fields($fields)->execute();         
        }      
      }           
    }
    ct_log("[ChurchService] Lege Template an ".$params["eventTemplate"]." fuer Event",2,$event_id,"service");
    
  }
}


function churchservice_getUserCurrentServices($user_id) {  
  $arr=db_query("SELECT cal.bezeichnung event, cal.ort, s.bezeichnung dienst, es.id eventservice_id,".
                "sg.bezeichnung servicegroup,".
                 "DATE_FORMAT(es.modified_date, '%Y%m%dT%H%i00') modified_date, ".
                "p.vorname, p.name, es.modified_pid, zugesagt_yn,
                e.startdate startdate,
                DATE_FORMAT(e.startdate, '%Y%m%dT%H%i00') datum_start,  
                adddate(e.startdate, interval timediff(cal.enddate, cal.startdate)  HOUR_SECOND) enddate,
                DATE_FORMAT(adddate(e.startdate, interval timediff(cal.enddate, cal.startdate)  HOUR_SECOND), '%Y%m%dT%H%i00') datum_end                
                 FROM {cs_event} e, {cc_cal} cal, {cs_eventservice} es, {cs_service} s, {cs_servicegroup} sg, {cdb_person} p
             WHERE cal.id=e.cc_cal_id and es.event_id=e.id and es.service_id=s.id and sg.id=s.servicegroup_id 
             and es.modified_pid=p.id and es.valid_yn=1 and e.startdate>current_date - INTERVAL 61 DAY and es.cdb_person_id=:userid",
      array(":userid"=>$user_id));
      
  $res=array();
  foreach($arr as $a) {
    $res[$a->eventservice_id]=$a;    
  }     
  return $res;  
}


/**
 *
 * @param $subject
 * @param $message
 * @param $to
 */
function churchservice_send_mail ($subject, $message, $to) {
  churchcore_systemmail($to, $subject, $message, true);
}

/**
 * Delete CS-Event and inform people about deleted event and delete calender entry
 *
 * @param $params["id"] id of Event
 * @param $params["informDeleteEvent"] 1=inform people. Default=0
 * @param $params["deleteCalEntry"] 1=delete Calender entry. Default=0
 * @throws CTException if Event or Calender Entry could not be found
 * @throws CTNoPermission
 */
function churchservice_deleteEvent($params, $source=null) {
  global $user;

  if ($source==null) {
    if (!user_access("edit events", "churchservice"))
      throw new CTNoPermission("edit events", "churchservice");
    ct_log("[ChurchService] Entferne Event!",2,$params["id"],"service");
  }
    
  $db_event=db_query("select e.*, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') date_de from {cs_event} e where id=:event_id", array(":event_id"=>$params["id"]))->fetch();
  if (!$db_event) {
    if ($params["id"]!=null) 
      throw new CTException("Event nicht gefunden!");
    else 
      return;
  }

  // Inform people about the deleted event
  if (isset($params["informDeleteEvent"]) && ($params["informDeleteEvent"]==1)) {
    $db_cal=db_query("select * from {cc_cal} where id=:cal_id", array(":cal_id"=>$db_event->cc_cal_id))->fetch();
    if (!$db_cal) throw new CTException("Event im Kalender nicht gefunden!");
    $db=db_query("select p.* from {cs_eventservice} es, {cdb_person} p
         where event_id=:event_id and valid_yn=1 and
              p.id=es.cdb_person_id and es.cdb_person_id is not null and p.email!=''",
        array(":event_id"=>$params["id"]));
    foreach ($db as $p) {
      $subject="[".variable_get('site_name')."] Absage von ".$db_cal->bezeichnung." ".$db_event->date_de;
      $txt='<h3>Hallo '.$p->vorname."!</h3>";
      $txt.='Das Event '.$db_cal->bezeichnung." am ".$db_event->date_de.' wurde von <i>'.$user->vorname.' '.$user->name.'</i> abgesagt. Deine Dienstanfrage wurde entsprechend entfernt.';
      churchservice_send_mail($subject, $txt, $p->email);
    }
  }

  db_query("delete from {cs_eventservice} where event_id=:event_id", array(":event_id"=>$params["id"]), false);
  db_query("delete from {cs_event} where id=:event_id", array(":event_id"=>$params["id"]), false);
  if ((isset($params["deleteCalEntry"])) && ($params["deleteCalEntry"]==1)) {
    db_query("delete from {cc_cal} where id=:id and repeat_id=0", array(":id"=>$db_event->cc_cal_id));
  }
}

