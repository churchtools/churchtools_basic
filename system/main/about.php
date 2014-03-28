<?php 


function about_main() {
  global $config;
  include_once("system/includes/forms.php");

  if (isset($_GET["consistentcheck"])) {
    return check_db_constraints(false);    
  }
  
  $txt='<div class="row-fluid">';
  $txt.='<div class="span3 bs-docs-sidebar">';   
  
    $txt.='<ul id="navlist" class="nav nav-list bs-docs-sidenav affix-top">';
    $txt.='<li><a href="#log1">'.t("about")." ".variable_get("site_name").'</a>';
    if (user_access("administer persons","churchcore")) {
      $txt.='<li><a href="#log2">'.t("current.permissions").'</a>';
      $txt.='<li><a href="#log3">'.t("current.config").'</a>';    
      $txt.='<li><a href="#log4">'.t("consistence.check").'</a>';
    }
    $txt.='</div>';
  $txt.='<div class="span9">';

  
$txt.='<anchor id="log1"/><h1>'.t("about")." ".variable_get("site_name").'</h1><div class="well">';
$txt.='
<p>'.t("churchtools.claim").'
<br>'.t("read.more").': <a href="http://www.churchtools.de" target="_clean">www.churchtools.de</a>
</p>
ChurchTools 2.0  is licensed under the following license: MIT license
<br/>The MIT License (MIT)
<br/>Copyright (c) 2014 Jens Martin Rauen
<br/>Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
<br/>The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
<br/>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
<br/>ChurchTools Pro is licensed under commercial licence.
<br/>(C) 2014 Jens Martin Rauen
</p></div>
';

  $txt.="<p>PHP-Version: ".phpversion();
  $txt.="<br>Browser: ".$_SERVER['HTTP_USER_AGENT'];
  $txt.="<br>ChurchTools2.0-Version: ".$config["version"];

  if (isset($_SESSION["user"])) {
    $user=$_SESSION["user"];
    $txt.="<p>".t("logged.in.as", "$user->vorname $user->name [$user->id]");
    $txt.=" - $user->email";
    
    ob_start();  
    print_r($user->auth); 
    // ob_get_clean() returns the contents of the last buffer opened.  The first "blah" and the output of var_dump are flushed from the top buffer on exit
    $var=preg_replace('/\n/', "<br>", ob_get_clean());
    $var=preg_replace('/ /', "&nbsp; ", $var); 
    $txt.='<anchor id="log2"/><h2>'.t("current.permissions").'</h2><p >'.$var;
    
    if (user_access("administer persons","churchcore")) {
      $config["password"]="****";
      $config["encryptionkey"]="****";
      if (isset($config["mail_pear_args"]))
        $config["mail_pear_args"]["password"]="****";
      ob_start();  
      print_r($config); 
      // ob_get_clean() returns the contents of the last buffer opened.  The first "blah" and the output of var_dump are flushed from the top buffer on exit
      $var=preg_replace('/\n/', "<br>", ob_get_clean());
      $var=preg_replace('/ /', "&nbsp; ", $var); 
      $txt.='<anchor id="log3"/><h2>'.t("current.config").'</h2><p >'.$var;
      
      $txt.='<anchor id="log4"/><h2>'.t("current.db.consistence.check").'</h2><p >';
      $res=check_db_constraints();
      if ($res=="")  $txt.="<p>".t("no.problem.found");
      else {
        $txt.=$res;
        $txt.='<p><a href="?q=about&consistentcheck=true" class="btn">'.t("detailed.report").'</a>';
      }
    }    
  }
  
      
  
  $txt.='</div>';
  
  
  return $txt;
  
}

function check_constraint($table, $column, $target_table, $target_column) {
  $txt="";
//   echo "checking " . $table . "." . $column . " ...<br \>";
   $query = "SELECT " . $column . " id FROM {" . $table . "} WHERE " . $column . " IS NOT NULL AND " . $column . " NOT IN (SELECT " . $target_column . " FROM {" . $target_table . "})";


   $rows = db_query($query);
   
   $info=false;

   if($rows) {
      foreach ($rows as $row) {
        if (!$info) {
          $info=true;
          $txt.="<p>".$query . "<br \>";
          
        }
         $txt.="found dead constraint in " . $table . "." . $column . "<br \>";
         $txt.="entry with id " .$row->id . " referenzes non existing value in " . $target_table . "." . $target_column . "<br \>";
      }
   }
   else {
      $txt.='Unallowed access: ' . mysql_error();
   }
//   $txt.="<br \> ---------------------------------------------------------------------------------------------------------- <br \><br \>";
   return $txt;
}


function check_db_constraints($small=true) {
  $to_check = array(
   array('cdb_bereich_person', 'bereich_id', 'cdb_bereich', 'bereich_id'),
   array('cdb_bereich_person', 'person_id', 'cdb_person', 'person_id'),
   array('cdb_beziehung', 'vater_id', 'cdb_person', 'id'),
   array('cdb_beziehung', 'kind_id', 'cdb_person', 'id'),
   array('cdb_beziehung', 'beziehungstyp_id', 'cdb_beziehungstyp', 'id'),
   array('cdb_comment', 'person_id', 'cdb_person', 'id'),
   array('cdb_comment', 'comment_viewer_id', 'cdb_comment_viewer', 'id'),
   array('cdb_distrikt', 'gruppentyp_id', 'cdb_gruppentyp', 'id'),
   array('cdb_feld', 'feldkategorie_id', 'cdb_feldkategorie', 'id'),
   array('cdb_feld', 'feldtyp_id', 'cdb_feldtyp', 'id'),
   array('cdb_followup_typ', 'comment_viewer_id', 'cdb_comment_viewer', 'id'),
   array('cdb_followup_typ_intervall', 'followup_typ_id', 'cdb_followup_typ', 'id'),
   array('cdb_gemeindeperson', 'person_id', 'cdb_person', 'id'),
   array('cdb_gemeindeperson', 'nationalitaet_id', 'cdb_nationalitaet', 'id'),
   array('cdb_gemeindeperson', 'station_id', 'cdb_station', 'id'),
   array('cdb_gemeindeperson', 'status_id', 'cdb_status', 'id'),
   array('cdb_gemeindeperson', 'familienstand_no', 'cdb_familienstand', 'id'),
   array('cdb_gemeindeperson', 'nationalitaet_id', 'cdb_nationalitaet', 'id'),
   array('cdb_gemeindeperson_gruppe', 'gemeindeperson_id', 'cdb_gemeindeperson', 'id'),
   array('cdb_gemeindeperson_gruppe', 'gruppe_id', 'cdb_gruppe', 'id'),
   array('cdb_gemeindeperson_gruppe', 'followup_count_no', 'cdb_followup_typ', 'id'),
   array('cdb_gemeindeperson_gruppe_archive', 'gemeindeperson_id', 'cdb_gemeindeperson', 'id'),
   array('cdb_gemeindeperson_gruppe_archive', 'gruppe_id', 'cdb_gruppe', 'id'),
   array('cdb_gemeindeperson_tag', 'gemeindeperson_id', 'cdb_gemeindeperson', 'id'),
   array('cdb_gemeindeperson_tag', 'tag_id', 'cdb_tag', 'id'),
   array('cdb_gruppe', 'gruppentyp_id', 'cdb_gruppentyp', 'id'),
   array('cdb_gruppe', 'distrikt_id', 'cdb_distrikt', 'id'),
   array('cdb_gruppe', 'followup_typ_id', 'cdb_followup_typ', 'id'),
   //array('cdb_gruppe', 'fu_nachfolge_typ_id', '', ''),
   //array('cdb_gruppe', 'fu_nachfolge_objekt_id', '', ''),
   array('cdb_gruppe_tag', 'gruppe_id', 'cdb_gruppe', 'id'),
   array('cdb_gruppe_tag', 'tag_id', 'cdb_tag', 'id'),
   array('cdb_gruppenteilnehmer_email', 'gruppe_id', 'cdb_gruppe', 'id'),
   array('cdb_gruppentreffen', 'gruppe_id', 'cdb_gruppe', 'id'),
   array('cdb_gruppentreffen_gemeindeperson', 'gruppentreffen_id', 'cdb_gruppentreffen', 'id'),
   array('cdb_gruppentreffen_gemeindeperson', 'gemeindeperson_id', 'cdb_gemeindeperson', 'id'),
   array('cdb_person', 'geschlecht_no', 'cdb_geschlecht', 'id')
//TODO allow value null when checking (null must not be found in id field of referenced table)
);

  $txt="";
  foreach($to_check as $entry) {
    $res=check_constraint($entry[0], $entry[1], $entry[2], $entry[3]);
    if ($res!="") {
      if ($small)
        $txt.="<p>".t("found.problem.with.tables", "$entry[0] => $entry[2]");
      else $txt.=$res;
    }
  }
  return $txt;  
}

// For footer e-mail function
function about__ajax() {
  global $config, $user;
  $params=$_POST;
  if ($params["func"]=="sendEmailToAdmin") {
    churchcore_sendEMailToPersonids(implode(",",$config["admin_ids"]), $params["subject"], $params["text"]);
    $res=jsend()->success();    
  }
  else if ($params["func"]=="amILoggedIn") {
    if ($user==null) $res=jsend()->success(false);
    else $res=jsend()->success($user->id!=-1);    
  }
  else $res=jsend()->error("Unkown call: ".$params["func"]);
  drupal_json_output($res);
}

?>
