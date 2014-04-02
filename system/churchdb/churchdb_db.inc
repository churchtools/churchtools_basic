<?php

include_once('./'. drupal_get_path('module', 'churchdb') .'/../churchcore/churchcore_db.inc');


/**
 * 
 * @param $cond zusŠtzlich sql where clause
 * @param fields: hier kann man die felder vom sql per hand setzen
 */
function churchdb_getAllowedPersonData($cond='', 
           $fields="p.id p_id, gp.id gp_id, name, vorname, spitzname, station_id stn_id, status_id sts_id, 
                    email as em, if (telefonhandy='',telefonprivat, telefonhandy) as tl, 
                    geolat as lat, geolng as lng, archiv_yn") {
  global $user;

  if ($cond!='') 
    $where="and ".$cond;
  else $where="";  
  
  $allPersons=null;
  
  // Hole ALLE Bereich-Infos, welche Bereiche welche Person sehen darf
  $sql_dep=db_query("select person_id, bereich_id from {cdb_bereich_person}");
  // Achtung: Nur die Bereiche anzeigen, in denen ich Rechte habe oder in denen ich bin.
  $allowedAndMyDeps=churchdb_getAllowedDeps();  
  $deps=array();
  foreach ($sql_dep as $dep) {
    if (isset($allowedAndMyDeps[$dep->bereich_id])) {
      if (!isset($deps[$dep->person_id]))
        $deps[$dep->person_id]=array();
      $deps[$dep->person_id][$dep->bereich_id]=$dep->bereich_id;
    }
  }
  
  // Hole ALLE Gruppeninfos und matche spŠter
  $sql_g="SELECT gg.gemeindeperson_id gp_id, gg.gruppe_id id, gg.status_no leiter, 
         DATE_FORMAT(gg.letzteaenderung, '%Y-%m-%d') d, gg.aenderunguser user, 
         gg.followup_count_no, gg.followup_add_diff_days, followup_erfolglos_zurueck_gruppen_id, comment
                   FROM {cdb_gemeindeperson_gruppe} gg";
  $grps=db_query($sql_g);
  $arr_grps=array();
  foreach ($grps as $grp) {
    // Wenn kein FollowUp, dann mu§ nix Ÿbertragen werden.    
    if ($grp->followup_count_no==null) unset($grp->followup_count_no);
    if ($grp->followup_add_diff_days==null) unset($grp->followup_add_diff_days);
    if ($grp->comment==null) unset($grp->comment);
    $arr_grps[$grp->gp_id][$grp->id]=$grp;
  }
  
  // Hole ALLE Leute aus meinen VIEWALL Bereichen
  $allowedDeps=user_access("view alldata", "churchdb");
  if ($allowedDeps!=null) {
    $sql_p="SELECT $fields
             FROM {cdb_person} p, {cdb_gemeindeperson} gp 
                    WHERE p.id=gp.person_id and 1=1 ".$where;
    $res=db_query($sql_p);  
    foreach ($res as $p) {
      $res=false;
      foreach ($allowedDeps as $allowedDep) {
        if (isset($deps[$p->p_id][$allowedDep])) {
          $res=true;
        }
      }
      if ($res) {            
        if (isset($deps[$p->p_id])) $p->access=$deps[$p->p_id];
        if (isset($arr_grps[$p->gp_id])) $p->groups=$arr_grps[$p->gp_id];
        $allPersons[$p->p_id]=$p;
      }
    }             
  }
    
  // Hole nun alle Personen in denen ich mit in der Gruppe, bzw. ich Distriktleiter bin
  $myGroups=churchdb_getMyGroups($user->id, true);
  if (count($myGroups)>0) {
    $sql_g="SELECT $fields
             FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg 
                    WHERE p.id=gp.person_id and gpg.gemeindeperson_id=gp.id 
                     and gpg.gruppe_id in (".implode(",", $myGroups).") ".$where;
    $res=db_query($sql_g);
    foreach ($res as $p) {
      if (!isset($allPersons[$p->p_id])) {
        if (isset($deps[$p->p_id])) $p->access=$deps[$p->p_id];
        if (isset($arr_grps[$p->gp_id])) $p->groups=$arr_grps[$p->gp_id];
        $allPersons[$p->p_id]=$p;
      }      
    }
  }
  
  // Now make sure, that I am loaded
  if (!isset($allPersons[$user->id])) {
    $p=db_query("select $fields from {cdb_gemeindeperson} gp, {cdb_person} p 
                 where gp.person_id=p.id and p.id=:p_id", array(":p_id"=>$user->id), false)->fetch();
    if ($p!=false) {
      if (isset($deps[$p->p_id])) $p->access=$deps[$p->p_id];
      if (isset($arr_grps[$p->gp_id])) $p->groups=$arr_grps[$p->gp_id];
      $allPersons[$user->id]=$p; 
    }    
  }
  
  // Reichere sie nun an mit den Distriktleitern
  $db=db_query("select * from {cdb_person_distrikt}");
  foreach ($db as $d) {
    if (isset($allPersons[$d->person_id])) {
      if (isset($allPersons[$d->person_id]->districts))
        $districts=$allPersons[$d->person_id]->districts;
      else $districts=array();  
      $districts[$d->distrikt_id]=$d;
      $allPersons[$d->person_id]->districts=$districts;
    }
  }
  // Reichere sie nun an mit den Gruppentypleitern
  $db=db_query("select * from {cdb_person_gruppentyp}");
  foreach ($db as $d) {
    if (isset($allPersons[$d->person_id])) {
      if (isset($allPersons[$d->person_id]->gruppentypen))
        $gruppentypen=$allPersons[$d->person_id]->gruppentypen;
      else $gruppentypen=array();  
      $gruppentypen[$d->gruppentyp_id]=$d;
      $allPersons[$d->person_id]->gruppentypen=$gruppentypen;
    }
  }
  
  return $allPersons;	
}


function getAuthForPerson($id) {
  return getAuthForDomain($id, "person");
}

function getAuthForDomain($id, $domain_type) {
  if (!user_access("administer persons","churchcore")) return null;
    
  $res=db_query("select * from {cc_domain_auth} where domain_type='$domain_type' and domain_id=".$id);
  $arr_auth=null;
  foreach ($res as $p) {
    if ($p->daten_id==null)
      $arr_auth[$p->auth_id]=$p->auth_id;
    else {
      if (!isset($arr_auth[$p->auth_id]))
        $arr2=array();
      else $arr2=$arr_auth[$p->auth_id];
      $arr2[$p->daten_id]=$p->daten_id;
      $arr_auth[$p->auth_id]=$arr2;
    }  
  }
  return $arr_auth;
}

/**
 * Is Person in Group
 * @param unknown $user_id 
 * @param unknown $groups array of group Ids
 * @return boolean
 */
function churchdb_isPersonInGroups($user_id, $groups) {
  $res=db_query("SELECT count(*) c
               FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg
                      WHERE p.id=gp.person_id and gpg.gemeindeperson_id=gp.id and p.id=:id
                       and gpg.gruppe_id in (".implode(",", $groups).") ", array(':id'=>$user_id))->fetch();
  return ($res->c>0);  
}

/**
 * Holt sich alle Gruppendaten, von den Gruppen die ich sehen darf. Also wenn ich view alldetails habe
 * oder Admin-Groups bin alle, sonst nur Meine Gruppen
 */
function churchdb_getAllowedGroups() {
  global $user;
  if ((user_access("administer groups", "churchdb")) || (user_access("view alldetails", "churchdb")))
    return getAllGroups();
  else
    return churchdb_GetMyGroups($user->id);    
}

/**
 * Hole Array mit Ids der Gruppen in denen $user_pid ist. 
 * Wenn ich keine "view alldetails"-Rechte habe, dann nur wo ich Leiter bin und Abschlu§datum nicht lŠnger als groupnotchoosable ist 
 * oder auch anzeigene_in_meine_gruppen_teilnehmer_yn=1 ist.
 * @param $user_pid
 * @param $only_ids default=false, only return array of IDs, otherweise array of person data
 * @param $onlyIAmLeader default=false, only where I am a leader in the group
 * @param $onlySuperGroups default=false, only where I am a district or grouptype leader
 */
function churchdb_getMyGroups($user_pid, $only_ids=false, $onlyIAmLeader=false, $onlySuperGroups=false) {
  global $user, $config;
  if ($user_pid==null) return array();
  
  $arrs=array();
  if (!$onlySuperGroups) {
    $res=db_query("select g.*, gpg.status_no, gt.anzeigen_in_meinegruppen_teilnehmer_yn, datediff(current_date, g.abschlussdatum) abschlusstage
           from {cdb_gruppe} g, {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp, {cdb_gruppentyp} gt
           where gp.person_id=$user_pid and gpg.gemeindeperson_id=gp.id and gpg.gruppe_id=g.id and g.gruppentyp_id=gt.id");
    foreach ($res as $a) {
      // Entweder nicht nur Leitergruppen oder ich eben Leiter Status>0
      if ((!$onlyIAmLeader) || ($a->status_no>0)) {
        // Entweder auch fŸr TN sichtbar und Status nicht zum lšschen markiert
        if ((($a->anzeigen_in_meinegruppen_teilnehmer_yn==1) && ($a->status_no!=-1))
        // oder wenn ich Leiter bin (status_no>0) und Abschlussdatum nicht zu lange her ist 
            || (($a->status_no>0) && (($a->abschlusstage==null) || ($a->abschlusstage<$config["churchdb_groupnotchoosable"])))
    // => Habe ich erst mal rausgenommen, denn sonst sieht man plštzlich  mit alldetails Leute aus Freizeiten oder mit gleichen Merkmalen!        
            // oder wenn ich alle Details sehen darf 
            //|| ((isset($user->auth["churchdb"])) && (isset($user->auth["churchdb"]["view alldetails"]))))
            )
            // Wenn die Gruppe versteckt ist oder ich Leiter bin
          if (($a->versteckt_yn==0) || (($a->status_no>0) && ($a->status_no<4)))
          if ($only_ids)
              $arrs[$a->id]=$a->id;
            else  
              $arrs[$a->id]=$a;
      }
    }  
    
    // Hole mir nun die Gruppen, wo ich durch View-Berechtitgun habe
    if (!$onlyIAmLeader) {
      $auth=user_access("view group", "churchdb");
      if ($auth!=null) {
        $res=db_query("select g.* from {cdb_gruppe} g where g.id in (".implode(",",$auth).")");
        foreach ($res as $a) {
          if (!isset($arrs[$a->id]))
            if ($only_ids)
              $arrs[$a->id]=$a->id;
            else  
              $arrs[$a->id]=$a;
        }    
      }
    }  
  }
  
  // Hole mir nun die Gruppen, wo ich als Distriktleiter eingsetzt bin
  $res=db_query("select g.* from {cdb_gruppe} g, {cdb_person_distrikt} pd
                           where g.distrikt_id=pd.distrikt_id and pd.person_id=$user_pid");
  foreach ($res as $a) {
    if (!isset($arrs[$a->id]))
      if ($only_ids)
          $arrs[$a->id]=$a->id;
        else  
          $arrs[$a->id]=$a;
  }
  // Hole mir nun die Gruppen, wo ich als Gruppentyp-Leiter eingsetzt bin
  $res=db_query("select g.* from {cdb_gruppe} g, {cdb_person_gruppentyp} pd
                           where g.gruppentyp_id=pd.gruppentyp_id and pd.person_id=$user_pid");
  foreach ($res as $a) {
    if (!isset($arrs[$a->id]))
      if ($only_ids)
          $arrs[$a->id]=$a->id;
        else  
          $arrs[$a->id]=$a;
  }
  return $arrs;  
}

/**
 * Entweder habe ich viewall oder ich bin Teil des Bereiches.
 * return: Array mit Bereich_Ids
 */
function churchdb_getAllowedDeps() {
  $allowedDeps=user_access("view alldata", "churchdb");
  if ($allowedDeps==null)
    $allowedDeps=array();
  // Nehme die wo ich alldata darauf habe und ergŠnze mit denen, wo ich drin bin.
  $res=db_query("SELECT * from {cdb_bereich_person} where person_id=".$_SESSION["user"]->id);
  foreach ($res as $auth) {
    $allowedDeps[$auth->bereich_id]=$auth->bereich_id;
  }
  return $allowedDeps; 
}

function churchdb_addEvent($params) {
  global $user;
  
  $i=new CTInterface();
  $i->setParam("datumvon");
  $i->setParam("datumbis");
  $i->setParam("gruppe_id");
  $i->addModifiedParams();  
  
  $id=db_insert("cdb_gruppentreffen")->fields($i->getDBInsertArrayFromParams($params))->execute(false);        
  return $id;  
}


function _checkPersonAutorisierung($auto, $iAmLeader, $iAmSuperLeader) {
  if ($auto==null) return true;
  $ret=false;
  foreach(explode("||", $auto) as $auth) {
    $auth=trim($auth);
    if ($auth=="admin") {
      if (user_access('edit masterdata',"churchdb")) $ret=true;
    }
    else if ($auth=="viewalldetails") {
      if (user_access('view alldetails',"churchdb")) $ret=true;
    }
    else if ($auth=="leader") {
      if ($iAmLeader) $ret=true;
    }
    else if ($auth=="superleader") {
      if ($iAmSuperLeader) $ret=true;
    }
    else throw new CTException("Unbekanntes Recht: '".$auth."'");
  }
   
  return $ret;
}
function churchdb_getPersonDetails($id) {
  global $user;

  // Entweder habe ich viewall auf den Bereich oder ich bin mit dem in einer Gruppe
  $allowed=$user->id==$id;
  $iAmLeader=false;
  $iAmSuperLeader=false;

  // Am i super leader?
  if (churchdb_isPersonSuperLeaderOfPerson($user->id, $id)) {
    $iAmSuperLeader=true;
    $iAmLeader=true;
    $allowed=true;
  }
  // Prï¿½fe erst, ob ich als Leiter mit dem in der Gruppe bin
  if (churchdb_isPersonLeaderOfPerson($user->id, $id)) {
    $iAmLeader=true;
    $allowed=true;
  }
  // Prï¿½fe nun, ob ich normal mit dem in der Gruppe bin
  if (!$allowed) {
    $myGroups=churchdb_getMyGroups($user->id, true, false);
    if (count($myGroups)>0) {
      $res=db_query("SELECT count(*) c
               FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg
                      WHERE p.id=gp.person_id and gpg.gemeindeperson_id=gp.id and p.id=:id
                       and gpg.gruppe_id in (".implode(",", $myGroups).") ", array(':id'=>$id))->fetch();
      if ($res->c>0) {
        $allowed=true;
      }
    }
  }
  if (!$allowed) {
    $allowedDeps=user_access("view alldata", "churchdb");
    if ($allowedDeps!=null) {
      $res=db_query('select count(*) as c from {cdb_bereich_person}
          where person_id=:p_id and bereich_id in ('.implode(',', $allowedDeps).')'
          , array(':p_id'=>$id), false)->fetch();
      if ($res->c>0) $allowed=true;
    }
  }

  if (!$allowed)
    return "no access";

  $res=db_query("select f.*, fk.intern_code from {cdb_feld} f, {cdb_feldkategorie} fk where f.feldkategorie_id=fk.id
              and fk.intern_code in ('f_address', 'f_church', 'f_category') and aktiv_yn=1");
  $sqlfields=array();
  $sqlfields[]="p.id id";
  $sqlfields[]="gp.id gp_id";
  $sqlfields[]="geolat as lat";
  $sqlfields[]="imageurl";
  $sqlfields[]="geolng as lng";
  $sqlfields[]="cmsuserid";



  foreach ($res as $res2) {
    if (($res2->autorisierung==null) || (_checkPersonAutorisierung($res2->autorisierung, $iAmLeader, $iAmSuperLeader))) {
      if (($res2->intern_code=="f_address") || ($iAmLeader) || (user_access('view alldetails',"churchdb")))
        $sqlfields[]=$res2->db_spalte;
    }
  }
   
  $sql="select ".join($sqlfields,",");
  if (($iAmLeader) || (user_access('view alldetails',"churchdb")) || (user_access('administer persons',"churchcore")))
    $sql.=', p.letzteaenderung, p.aenderunguser, p.createdate, if (loginstr is null , 0 , 1) as einladung, p.active_yn, p.lastlogin';
   
  $sql.=' FROM {cdb_person} p, {cdb_gemeindeperson} gp WHERE p.id=gp.person_id and p.id=:pid';

  $person=db_query($sql, array(':pid'=>$id))->fetch();
  $comments=db_query("select id, text, person_id, datum, comment_viewer_id, relation_name from {cdb_comment}
                      where relation_id=:relid and relation_name like 'person%'
					  order by datum desc", array(':relid'=>$id));
  $auth=user_access("view comments","churchdb");
  if (($comments) && ($auth!=null)) {
    $arrs=null;
    foreach ($comments as $arr) {
      if ((isset($auth[$arr->comment_viewer_id])) &&
      ($auth[$arr->comment_viewer_id]==$arr->comment_viewer_id))
        $arrs[]=$arr;
    }
    $person->comments=$arrs;
  }
  $person->auth=getAuthForPerson($id);
  return $person;
}

function churchdb_isPersonSuperLeaderOfPerson($superleader_id, $person_id) {
  $myGroups=churchdb_getMyGroups($superleader_id, true, false, true);
  if (count($myGroups)>0) {
    return churchdb_isPersonInGroups($person_id, $myGroups);
  }
  return false;
}

function churchdb_isPersonLeaderOfPerson($leader_id, $person_id) {
  $myGroups=churchdb_getMyGroups($leader_id, true, true);
  if (count($myGroups)>0) {
    return churchdb_isPersonInGroups($person_id, $myGroups);
  }
  return false;
}

// Gibt ein Array zurŸck mit allen p_ids in den Gruppen myGroups
// myGroups: array mit gruppen, z.B. zu holen Ÿber churchdb_getMyGroups()
function churchdb_getAllPeopleIdsFromGroups($myGroups) {
  $allPersons=null;
  if (count($myGroups)>0) {
    $sql_g="SELECT p.id p_id
             FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg 
                    WHERE p.id=gp.person_id and gpg.gemeindeperson_id=gp.id 
                     and gpg.gruppe_id in (".implode(",", $myGroups).") ";
    $res=db_query($sql_g);
    foreach ($res as $p) {
      if (!isset($allPersons[$p->p_id])) {
        $allPersons[$p->p_id]=$p->p_id;
      }      
    }
  }  
  return $allPersons;
}


function churchdb_getLastLogId() {
  $arr=db_query("select max(id) id from {cdb_log}")->fetch();
  return $arr->id;  
}

function churchdb_pollForNews($last_id) {
  global $user;
  $res=db_query("select * from {cdb_log} where id > $last_id and person_id!=$user->id");
  $arrs=Array();
  foreach ($res as $arr) {
    $arrs[$arr->id]=$arr;   
  }
  $arr=Array();
  $arr["lastLogId"]=churchdb_getLastLogId();
  $arr["logs"]=$arrs;  
  return $arr;
}

function getAllDepartments() {
  return churchcore_getTableData("cdb_bereich");	
}

function getAllRelations() {
  $res = db_query('SELECT id, vater_id v_id, kind_id k_id, beziehungstyp_id typ_id FROM {cdb_beziehung}');
  $arrs=null;
  foreach ($res as $arr) {
    $arrs[$arr->id]=$arr;		
  }
  return $arrs;	
}

function getAllGroups() {
  $arr = churchcore_getTableData("cdb_gruppe","bezeichnung");
  if ($arr==null) return null;
    
  foreach($arr as $val) {
    $tags=db_query("select tag_id from {cdb_gruppe_tag} gt where gruppe_id=:gruppe_id", array(":gruppe_id"=>$val->id));
    $ids=array();
    foreach ($tags as $tag) {
      $ids[]=$tag->tag_id;      
    }
    $arr[$val->id]->tags=$ids;
    $arr[$val->id]->auth=getAuthForDomain($val->id, "gruppe");
  }	
  return $arr;
}

function getAllTags() {
  return churchcore_getTableData("cdb_tag","bezeichnung"); 
}

/**
 * Holt sich die Gruppeninfos in ein Array 
 * @param $g_id
 */
function getGroupInfo($g_id) {
  $sql_info="select g.id, g.bezeichnung gruppe, gt.bezeichnung gruppentyp, g.mail_an_leiter_yn from 
      {cdb_gruppe} g, {cdb_gruppentyp} gt 
      where g.gruppentyp_id=gt.id and g.id=:g_id";
  return db_query($sql_info, array(':g_id'=>$g_id))->fetch();  	
}

function _churchdb_getGemeindepersonIdFromPersonId($p_id) {
  $person = db_query('SELECT person_id p_id, id gp_id FROM {cdb_gemeindeperson} where person_id=:person_id',array(':person_id'=>$p_id))->fetch();
  return $person->gp_id;
}
function _churchdb_getPersonIdFromGemeindepersonId($gp_id) {
  $person = db_query('SELECT person_id p_id, id gp_id FROM {cdb_gemeindeperson} where id=:id',array(':id'=>$gp_id))->fetch();
  return $person->p_id;
}



function getPersonGroupRelation($gp_id, $g_id) {
  $res=db_query("select * from {cdb_gemeindeperson_gruppe} where gemeindeperson_id=:gp_id and gruppe_id=:g_id", array(':gp_id'=>$gp_id, ':g_id'=>$g_id));
  return $res->fetch();  
}

function getAllRelationTypes() {
  return churchcore_getTableData("cdb_beziehungstyp");	
}

function getAllCommentViewer() {
  return churchcore_getTableData("cdb_comment_viewer","bezeichnung");	
}

function getBirthdayList($diff_from, $diff_to) {

  $status_id=variable_get('churchdb_birthdaylist_status', '1');
  $station_id=variable_get('churchdb_birthdaylist_station', '1,2,3');
  
  $sql="select * from 
             (select *, if (abs(this_year)<abs(next_year), 
                    if (abs(last_year)<abs(this_year),last_year,this_year),
                    if (abs(last_year)<abs(next_year),last_year,next_year)) 
                  as bla from (
                select person_id,
                datediff(DATE_ADD(geburtsdatum,INTERVAL (year(curdate())-year(geburtsdatum)) year),curdate()) this_year, 
                datediff(DATE_ADD(geburtsdatum,INTERVAL (year(curdate())-year(geburtsdatum)-1) year),curdate()) last_year, 
                datediff(DATE_ADD(geburtsdatum,INTERVAL (year(curdate())-year(geburtsdatum)+1) year),curdate()) next_year,  
                datediff(DATE_ADD(geburtsdatum,INTERVAL (year(curdate())-year(geburtsdatum)) year), curdate()) as diff, 
                  name, vorname, spitzname, DATE_FORMAT(geburtsdatum, '%d.%m.%Y') geburtsdatum_d, DATE_FORMAT(geburtsdatum, '%d.%m.') geburtsdatum_compact, geburtsdatum, 
                         (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(geburtsdatum, '%Y') - 
                          (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(geburtsdatum, '00-%m-%d'))) as age,
                      year(geburtsdatum) jahr,
                     s.bezeichnung, gp.imageurl imageurl, status.bezeichnung status
               FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_station} s, {cdb_status} status
                 where p.id=gp.person_id and status_id in (".$status_id.") and gp.station_id in (".$station_id.") 
                 and geburtsdatum is not null and year(geburtsdatum)<7000
                 and status.id=gp.status_id
                         and s.id=gp.station_id and archiv_yn=0) 
		  as t) as t2 where ((bla>=$diff_from) and (bla<=$diff_to))
		    order by bla, name, vorname
		  ";
  
  $sql_bereich="select bp.person_id, bezeichnung from {cdb_bereich_person} bp, {cdb_bereich} b where bp.bereich_id=b.id and bp.person_id=:p_id order by bezeichnung";
  $res = db_query($sql);
  foreach ($res as $arr) {
    if ($arr->jahr==1004) {
      $arr->age="";
      $arr->geburtsdatum_compact="";
      $arr->geburtsdatum="";
      $arr->geburtsdatum_d=substr($arr->geburtsdatum_d,0,6);
    }
    
    $res_bereich=db_query($sql_bereich,array(":p_id"=>$arr->person_id));
    $bereich="";
    foreach ($res_bereich as $arr_bereich) {
      $bereich=$bereich.$arr_bereich->bezeichnung."<br/>";
    }    
    $arr->bereich=$bereich;
    $arrs[]=$arr;
  }
  if (!isset($arrs))
    return null;
  else
    return $arrs;	
}



/**
 * 
 * @param unknown_type $g_id Id der Gruppe
 * @param unknown_type $p_ids Kommasepartierte IDs der person_ids
 */
function churchdb_delFromGroup($g_id, $p_ids) {
  // Wandle PersonIds in GemeindepersonIds
  $sql="select id from {cdb_gemeindeperson} where person_id in (%s)";
  $res=db_query($sql, $p_ids); 

  $gp_ids="-1";
  foreach ($res as $arr) {
    $gp_ids=$gp_ids.",".$arr->id;
  }
  $sql="delete from {cdb_gemeindeperson_gruppe} where gemeindeperson_id in (%s) and gruppe_id=%s";
  db_query($sql, $gp_ids, $g_id);
  return "ok";
}


/**
 * 
 * @param $id
 * @param $username
 */
function setCMSUser($id, $username) {
  if (($id==null) || ($username==null) || ($id=="") || ($username==""))
    return "id ($id) oder username ($username) nicht definiert!";
    
  $sql="select id from {cdb_person} where cmsuserid='$username'";
  if ($arr=db_query($sql)->fetch()) 
    return "Eine Person (".$arr->id.") hat den Usernamen $username schon, bitte wende Dich an den Administrator!";
     
  $sql="select cmsuserid from {cdb_person} where id=$id";
  if ($arr=db_query($sql)->fetch())
    if (($arr!=null) && ($arr->cmsuserid!="")) {    	
    	return "Fehler: Diese Person hat schon einen Usernamen! Bitte wende Dich an einen Adminstrator!";
    }
    
	$sql="update {cdb_person} set cmsuserid='$username' where id=$id";
	db_query($sql);
	return "ok";
}

function getGroupMeeting($id) {
  $sql="select * from {cdb_gruppentreffen} where gruppe_id='$id' order by datumbis";/// limit 12";
  $arrs=null;
  $res = db_query($sql);
  foreach ($res as $arr) {
  	$sql="select gp.person_id p_id, treffen_yn from {cdb_gruppentreffen_gemeindeperson} gtg, {cdb_gemeindeperson} gp 
  	       where gtg.gemeindeperson_id=gp.id and gtg.gruppentreffen_id='$arr->id'";
    $res2 = db_query($sql);
    $arrs2=array();
    foreach ($res2 as $arr2) {
  	  $arrs2[]=$arr2;
    };  
    $arr->entries=$arrs2;
    $arrs[]=$arr;  	
  };
  return $arrs;
}

function createGroupMeetings() {
  $sql = "select id from {cdb_gruppe} g where treffen_yn=1 ";
  $res = db_query($sql);
  $sql = "select * from {cdb_gruppentreffen} where gruppe_id=:id and datumbis>=curdate() order by datumbis desc";
  foreach ($res as $arr) {
  	$res2 = db_query($sql,array(":id"=>$arr->id))->fetch();
  	if ($res2==null) {
      cdb_log("Erstelle Gruppentreffen fuer Gruppe ".$arr->id,3,-1,'cron');
   	  db_query("insert into {cdb_gruppentreffen} (gruppe_id, datumvon, datumbis,eintragerfolgt_yn,ausgefallen_yn) values ($arr->id, curdate() - interval (dayofweek(curdate())-2) day, curdate() + interval (8-dayofweek(curdate())) day,0,0)");
  	}
  }
  return "ok";
}

function cancelGroupMeeting($id) {
  $sql="update {cdb_gruppentreffen} set ausgefallen_yn=1, eintragerfolgt_yn=1 where id=".$id;
  $res = db_query($sql);
  return "ok";
}

function entryGroupMeeting($g_id, $gt_id, $anwesende) {
  global $user;
 
  $sql="select person_id, gemeindeperson_id from {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp 
        where gpg.gemeindeperson_id=gp.id and gruppe_id=".$g_id;
  $res = db_query($sql);
  $dt=new DateTime();
  foreach ($res as $arr) {
    $fields=array("gruppentreffen_id"=>$gt_id,
                  "gemeindeperson_id"=>$arr->gemeindeperson_id,
                  "modified_date"=>$dt->format('Y-m-d H:i:s'),
                  "modified_pid"=>$user->id
                  );
    
  	if (in_array($arr->person_id, $anwesende)) 
      $fields["treffen_yn"]=1;
    else       
      $fields["treffen_yn"]=0;
      
    db_insert("cdb_gruppentreffen_gemeindeperson")->fields($fields)->execute();  
  };  
  
  $sql="update {cdb_gruppentreffen} set ausgefallen_yn=0, eintragerfolgt_yn=1 where id=".$gt_id;
  $res = db_query($sql);
  return "ok";
}

/**
 * 
 * @param unknown_type $id gruppentreffen_id
 */
function deleteGroupMeetingStats($id) {
   db_query("delete from {cdb_gruppentreffen_gemeindeperson} where gruppentreffen_id=:id", 
         array(":id"=>$id));
   db_query("delete from {cdb_gruppentreffen} where id=:id", 
         array(":id"=>$id));
   return "ok";
}

function  editCheckinGroupMeetingStats($params) {
  global $user;
  $dt = new DateTime();
  
  db_query("update {cdb_gruppentreffen} set eintragerfolgt_yn=1 where id=:id", 
     array(":id"=>$params["gruppentreffen_id"]));
  
  $gp_id=_churchdb_getGemeindepersonIdFromPersonId($params["p_id"]);
  db_query("insert into {cdb_gruppentreffen_gemeindeperson} (gruppentreffen_id, gemeindeperson_id, treffen_yn, modified_date, modified_pid) 
            values (:gruppentreffen_id, :gemeindeperson_id, :treffen_yn, :modified_date, :modified_pid) 
              on duplicate key update treffen_yn=:treffen_yn, modified_date=:modified_date, modified_pid=:modified_pid",
     array(":gruppentreffen_id"=>$params["gruppentreffen_id"], 
           ":gemeindeperson_id"=>$gp_id, 
           ":treffen_yn"=>$params["treffen_yn"], 
           ":modified_date"=>$dt->format('Y-m-d H:i:s'),
           ":modified_pid"=>$user->id));
   return "ok";
}


function savePropertiesGroupMeetingStats($params) {
  $i=new CTInterface();
  $i->setParam("id");
  $i->setParam("anzahl_gaeste");
  $i->setParam("kommentar");
  $i->setParam("datumvon", false);
  $i->setParam("datumbis", false);
  $i->addModifiedParams();  
  
  $id=db_update("cdb_gruppentreffen")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
    
  if (isset($params["entries"])) {
    if (entryGroupMeeting($params["g_id"], $params["id"], $params["entries"])!="ok")
      throw new CTException("Problem beim Speichern der einzelnen Teilnahmerdaten");      
  }
}

/**
 * 
 * @param $id -1 bedeutet, es werden alle Gruppen gezogen, ansonsten nur eine bestimmte
 */
function getGroupMeetingStats($id=-1) {
	if ($id!=-1)
	  $where=" and gg.gruppe_id=$id "; 
	else $where="";
	$sql="select gp.person_id id, gg.gruppe_id g_id, sum(ausgefallen_yn) ausgefallen, 
	   count(eintragerfolgt_yn) stattgefunden, sum(gtp.treffen_yn) dabei, max(if (gtp.treffen_yn=1,datumbis,0)) as max_datumbis 
	 from {cdb_gemeindeperson_gruppe} gg, {cdb_gemeindeperson} gp, {cdb_gruppentreffen} gt, {cdb_gruppentreffen_gemeindeperson} gtp 
	 where gg.gruppe_id=gt.gruppe_id and gg.gemeindeperson_id=gp.id 
     and gg.gemeindeperson_id=gtp.gemeindeperson_id   and gtp.gruppentreffen_id=gt.id  and eintragerfolgt_yn=1 $where
  group by gp.person_id, gg.gruppe_id";
  $res = db_query($sql);
  $arrs=null;
  foreach ($res as $arr) {
  	$new_grp["ausgefallen"]=$arr->ausgefallen;
  	$new_grp["stattgefunden"]=$arr->stattgefunden;
    $new_grp["dabei"]=$arr->dabei;
    $new_grp["datum"]=$arr->max_datumbis;
    
  	$new[$arr->g_id]=$new_grp;
  	
    $arrs[$arr->id]=$new;   
  }
  return $arrs; 
}

function cdb_log($txt,$level=3,$domainid=-1,$domaintype=CDB_LOG_PERSON,$schreibzugriff_yn=0,$_user=null) {
  ct_log($txt,$level,$domainid,$domaintype,$schreibzugriff_yn,$_user);
}


function getAllMailNotifys() {
  $res = db_query('SELECT * FROM {cdb_mailnotify} where enabled=1');
  $arrs=null;
  foreach ($res as $arr) {
    $arrs[$arr->id]=$arr;   
  }
  return $arrs; 
}

/**
 * Loescht den User mit allen Abhängigkeiten der Person_Id id
 * @param $id
 */
function deleteUser($p_id) {
  $arr=db_query("select id gp_id from {cdb_gemeindeperson} where person_id=".$p_id)->fetch();
  $gp_id=$arr->gp_id;	
  
  db_query("delete from {cdb_bereich_person} where person_id=$p_id");
  db_query("delete from {cdb_beziehung} where vater_id=$p_id");
  db_query("delete from {cdb_beziehung} where kind_id=$p_id");
  db_query("delete from {cdb_comment} where relation_id=$p_id and relation_name='person'");
  db_query("delete from {cdb_gemeindeperson_gruppe} where gemeindeperson_id=$gp_id");
  db_query("delete from {cdb_gruppentreffen_gemeindeperson} where gemeindeperson_id=$gp_id");
  db_query("delete from {cdb_gemeindeperson} where id=$gp_id");
  db_query("delete from {cc_domain_auth} where domain_type='person' and domain_id=$p_id");
  db_query("delete from {cdb_person} where id=$p_id");
  
  return "ok";
}

function archiveUser($p_id, $undo=false) {
  if ($undo==false) {
    db_update("cdb_person")
      ->fields(array("archiv_yn"=>1))
      ->condition("id", $p_id, "=")
      ->execute();
  }
  else    
    db_update("cdb_person")
      ->fields(array("archiv_yn"=>0))
      ->condition("id", $p_id, "=")
      ->execute();
  
  return "ok";
}
/**
 * 
 * @param $parent_id
 * @param $child_id
 * @param $relation_id
 */
function addRelation($parent_id, $child_id, $relation_id) {
  db_query("insert into {cdb_beziehung} (vater_id, kind_id, beziehungstyp_id, datum) 
            values ($parent_id, $child_id, $relation_id, CURRENT_DATE)");	
  return "ok";	
}


function churchdb_deleteLastGroupStatistik($id) {
  $res=db_query("select id from {cdb_gruppentreffen} 
                where gruppe_id=:g_id and eintragerfolgt_yn=1 order by datumvon desc limit 1",array (":g_id"=>$id))
   ->fetch();
 
  if ($res==false) throw new CTFail("Es ist keine Gruppenteilnahme mehr gepflegt.");
  
  db_query("delete from {cdb_gruppentreffen_gemeindeperson} where gruppentreffen_id=:gruppentreffen_id",
              array(":gruppentreffen_id"=>$res->id));
  db_update("cdb_gruppentreffen")
    ->fields(array("eintragerfolgt_yn"=>0,"ausgefallen_yn"=>0))
    ->condition("id", $res->id, "=")
    ->execute();
}

function delRelation($id) {
  db_query("delete from {cdb_beziehung} where id=$id");	
  return "ok";	
}

  function _churchdb_a($id, $txt) {
    $a=url("churchdb", array('absolute' => TRUE));
    return "<a href=\"$a?id=$id\">$txt</a>";
}	

/**
 * Erstellt einen persönlichen Newslettertext für Person p_id
 * @param $p_id
 */
function getPersonalNews($p_id) {
  
  
	$user;
 
	
  $sql='SELECT p.name, p.vorname, gp.id gp_id, p.cmsuserid FROM {cdb_person} p, {cdb_gemeindeperson} gp where gp.person_id=p.id and p.id='.$p_id;
  $person = db_query($sql)->fetch();
  $txt="";
  if ($person==null) return "";
  if ($person->cmsuserid=="") return "";
  $user=user_load($person->cmsuserid);
  if ($user==null) return "";
  
    
  // Neue Personen in der Gruppe
  $sql_gruppen='SELECT gpg.gruppe_id g_id, g.bezeichnung from {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppe} g
         WHERE g.id=gpg.gruppe_id and gpg.status_no>0 and ((g.abschlussdatum is null) or (datediff(g.abschlussdatum,CURRENT_DATE)>-100 ))
              and gpg.gemeindeperson_id='.$person->gp_id.' order by g.bezeichnung';
  $sql_teilnehmer='SELECT vorname, name, gpg.letzteaenderung, gpg.aenderunguser, g.bezeichnung gruppe, gt.bezeichnung gruppentyp, p.id p_id from {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppe} g, {cdb_gruppentyp} gt
       where p.id=gp.person_id and gpg.gemeindeperson_id=gp.id and gpg.gruppe_id=g.id and g.gruppentyp_id=gt.id
              and gpg.gruppe_id=:gruppen_id and (datediff(gpg.letzteaenderung,CURRENT_DATE)>=-31)';  
  $curtxt="";
  $res = db_query($sql_gruppen);
  foreach ($res as $arr) {
    $res2 = db_query($sql_teilnehmer, array(":gruppen_id"=>$arr->g_id));
    foreach ($res2 as $tln) {
    	$curtxt=$curtxt._churchdb_a($tln->p_id,$tln->vorname." ".$tln->name)." (".$tln->gruppentyp." ".$tln->gruppe." seit ".$tln->letzteaenderung.", eingepflegt von ".$tln->aenderunguser.
    	     ")<br/>";
    }            
  }
  if ($curtxt!="") {
    $txt="<h3>Neue Personen in Deinen Gruppen</h3>".$curtxt;  
  }
  
  // Folgende Personen bitte anschauen
  $sql_teilnehmer='SELECT p.id, gp.id gp_id, vorname, name from {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppe} g, {cdb_gruppentyp} gt
       where p.id=gp.person_id and gpg.gemeindeperson_id=gp.id and gpg.gruppe_id=g.id and g.gruppentyp_id=gt.id and gpg.status_no=0
              and gpg.gruppe_id=:teilnehmer';
  //$sql_beziehung_ich="SELECT * from {cdb_beziehung} where vater_id=$p_id and kind_id=%n and datum+30>CURRENT_DATE";  
  $sql_beziehung_ich="SELECT count(*) c from {cdb_log} where userid='".$user->cmsuserid."' and domain_id=:person_id and domain_type='person' and (datediff(datum,CURRENT_DATE)>-100)";  
  
  $sql_beziehung_alle="SELECT count(*) c from {cdb_log} where domain_id=:person_id and domain_type='person' and (datediff(datum,CURRENT_DATE)>-100)";
  $sql_gruppentreffen="SELECT count(gt.id) c from {cdb_gruppentreffen_gemeindeperson} gtgp, {cdb_gruppentreffen} gt 
                          where gtgp.gruppentreffen_id=gt.id and datumbis+30>CURRENT_DATE and gt.gruppe_id=:g_id and gtgp.gemeindeperson_id=:gp_id";  
  $res = db_query($sql_gruppen);
  $curtxt="";
  foreach ($res as $arr) {
    $res2 = db_query($sql_teilnehmer, array(":teilnehmer"=>$arr->g_id));
    $txt2="";
    foreach ($res2 as $tln) {
    	$count_ich = db_query($sql_beziehung_ich, array(":person_id"=>$tln->id))->fetch();    	
    	$count_bez_alle=db_query($sql_beziehung_alle, array(":person_id"=>$tln->id))->fetch();
    	$count_gruppentreffen=db_query($sql_gruppentreffen, array(":g_id"=>$arr->g_id, ":gp_id"=>$tln->id))->fetch();
    	if (($count_ich->c==0) && ($count_bez_alle->c<3) && ($count_gruppentreffen->c==0))
          $txt2=$txt2._churchdb_a($tln->id,$tln->vorname." ".$tln->name)." ($count_bez_alle->c/$count_gruppentreffen->c)<br/>";      
    }            
    if ($txt2!="") $curtxt=$curtxt."<i>In Gruppe $arr->bezeichnung</i><br/>$txt2<br/>";
  }
  if ($curtxt!="") {
    $txt=$txt."<br/><h3>Folgende Personen bitte einmal anschauen</h3>".$curtxt;
  }
    
  // Geburtstage 
  $sql_geb="select * from (
                SELECT person_id, datediff(DATE_ADD(geburtsdatum,INTERVAL (year(curdate())-year(geburtsdatum)) year),curdate()) as diff, name, 
                  vorname, geburtsdatum, (year(curdate())-year(geburtsdatum) - (RIGHT(CURDATE(),5)<RIGHT(geburtsdatum,5))) as 'alter'
                 FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg
                 where p.id=gp.person_id and geburtsdatum is not null and gpg.gemeindeperson_id=gp.id
                 and gpg.gruppe_id=:gp_id) 
      as t where t.diff>=0 and t.diff<=31 order by t.diff";
      // ORDER BY t.diff, MONTH(geburtsdatum ), DAYOFMONTH(geburtsdatum ), name, vorname";
  
  $res = db_query($sql_gruppen);
  $curtxt="";
  foreach ($res as $arr) {
    $res2 = db_query($sql_geb, array(":gp_id"=>$arr->g_id));
    $txt2="";
    foreach ($res as $tln) {
      $txt2=$txt2._churchdb_a($tln->person_id,"$tln->vorname $tln->name")." $tln->geburtsdatum (in $tln->diff Tagen) <br/>";
    }    
    if ($txt2!="") $curtxt=$curtxt."<i>In Gruppe $arr->bezeichnung</i><br/>$txt2<br/>";
  }
  if ($curtxt!="") {
    $txt=$txt."<br/><h3>Geburtstage Deiner Personen in den n&auml;chsten 31 Tagen</h3>".$curtxt;      
  }
  
  
  $sql_teilnehmer="SELECT vorname, name, c.text, c.datum, c.userid, p.id p_id from {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppe} g, {cdb_gruppentyp} gt, {cdb_comment} c
       where p.id=gp.person_id and gpg.gemeindeperson_id=gp.id and gpg.gruppe_id=g.id and g.gruppentyp_id=gt.id
              and gpg.gruppe_id=:g_id and c.comment_viewer_id=0 and c.relation_id=p.id and c.relation_name='person' and (datediff(c.datum,CURRENT_DATE)>=-31) 
               order by c.datum desc";    
  $res = db_query($sql_gruppen);
  $curtxt="";
  foreach ($res as $arr) {
    $res2 = db_query($sql_teilnehmer, array (":g_id"=>$arr->g_id));
    foreach ($res2 as $tln) {
      $curtxt=$curtxt._churchdb_a($tln->p_id,"$tln->vorname $tln->name")." - \"$tln->text\" ($tln->datum von $tln->userid)<br/>";
    }            
  }
  if ($curtxt!="") {
    $txt=$txt."<br/><h3>Neue Kommentare zu Deinen Personen</h3>".$curtxt;
  }
  
  if ($txt!="")
    $txt="<div style=\"margin:3px;padding:5px\"><h2>Pers&ouml;nliche Infos f&uuml;r ".$person->vorname." ".$person->name."</h2>".$txt."</div>";
  
  return $txt; 
}

function churchdb_countMembersInGroup($g_id) {
  $res=db_query("select count(*) c from {cdb_gemeindeperson_gruppe} p where gruppe_id=$g_id and status_no=0")->fetch();  
  return $res->c;
}




