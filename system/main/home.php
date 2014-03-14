<?php 

function home_main() {
  global $config, $files_dir, $mapping;

  if ((isset($config["admin_message"])) && ($config["admin_message"]!=""))
    addErrorMessage($config["admin_message"]);
  
  checkFilesDir();
  
  $btns=churchcore_getModulesSorted();
  
  if (isset($_SESSION["family"])) {
    addInfoMessage('Es sind mehrere Benutzer mit der gleichen EMail-Adresse vorhanden. Diese k&ouml;nnen im Men&uuml; oben rechts gewechselt werden.');
  }
  
  $txt='
  <div class="hero-unit hidden-phone">
    <h1>'.$config["welcome"].'</h1>
    <p class="hidden-phone">'.$config["welcome_subtext"].'</p>
    <p>';
    
    foreach ($btns as $key) {
      if ((isset($config[$key."_startbutton"])) && ($config[$key."_startbutton"]=="1") && (user_access("view", $key)))    
        $txt.=  
        '<a class="btn btn-prim_ary btn-large" href="?q='.$key.'">
          '.$config[$key."_name"].'
        </a>&nbsp;' ;
      }
    $txt.='</p>';
  $txt.='</div>';
    
  $txt.='<div class="well visible-phone">
    <h1>Willkommen!</h1>
    <p>'.$_SESSION["user"]->vorname.', w&auml;hle Deine M&ouml;glichkeiten:</p>
    <ul class="nav nav-pills nav-stacked">';
    
    foreach ($btns as $key) {
      if ((isset($config[$key."_name"])) && ($config[$key."_name"]!="") && (user_access("view", $key)))  {   
        include_once("system/".$mapping[$key]);
        $txt.=  
        '<li><a class="btn btn-prima_ry btn-large" href="?q='.$key.'">
          '.$config[$key."_name"].'
        </a> ' ;
      }
    }
    $txt.='</ul>';
  $txt.='</div>';
    
  // blocks[] : label, col(1,2,3) sortkey, html
  $blocks=null;
  foreach ($btns as $key) {
    
    
    if ((isset($config[$key."_name"])) && ($config[$key."_name"]!="")) {
      include_once("system/".$mapping[$key]);
      if (function_exists($key."_blocks")) {
//        $time=microtime();
        $arr=call_user_func($key."_blocks");
//        echo "<br>$key".(microtime()-$time);
        foreach ($arr as $block) {
          $blocks[$block["col"]][]=$block;
        }
      }
    }     
  }
  $txt.='<div class="row-fluid">';
    for ($i=1;$i<=3;$i++) {
      $txt.='<ul class="span4">';
      if (isset($blocks[$i])) {
        churchcore_sort($blocks[$i], "sortkey");
        foreach($blocks[$i] as $block) {
          if (($block["html"]!=null) && ($block["html"]!="")){
            $txt.='<li class="ct_whitebox';
            if (isset($block["class"])) $txt.=' '.$block["class"];
            $txt.='">';
            $txt.='<label class="ct_whitebox_label">'.$block["label"]."</label>";
            if (isset($block["help"])) {
              $txt.='<div style="float:right;margin:-34px -12px">';
                $txt.='<a href="http://intern.churchtools.de?q=help&doc='.$block["help"].'" title="Hilfe aufrufen" target="_clean"><i class="icon-question-sign"></i></a>';
              $txt.='</div>';
            }
              
            $txt.=$block["html"];
          }
        }
      }
      $txt.='</ul>';
    }   
  $txt.='</div>';
      
  drupal_add_js('system/main/home.js');
  
  return $txt; 
}


function checkFilesDir() {
    global $files_dir;
    if (!file_exists($files_dir."/files")) {
        mkdir($files_dir."/files",0777,true);
    }

    if (!is_writable($files_dir."/files")) {
        addErrorMessage("Das Verzeichnis $files_dir/files muss beschreibbar sein. Bitte Rechte daf&uuml;r setzen!");
    } else {
        if (!file_exists($files_dir."/files/.htaccess")) {
            $handle = fopen($files_dir."/files/.htaccess",'w+');
            if ($handle) {
                fwrite($handle,"Allow from all\n");
                fclose($handle);
            }
        }

        if (!file_exists($files_dir."/fotos/.htaccess")) {
            $handle = fopen($files_dir."/fotos/.htaccess",'w+');
            if ($handle) {
                fwrite($handle,"Allow from all\n");
                fclose($handle);
            }
        }
    }

    if (!file_exists($files_dir."/.htaccess")) {
        $handle = fopen($files_dir."/.htaccess",'w+');
        if ($handle) {
            fwrite($handle,"Deny from all\n");
            fclose($handle);
        }
    }
}


function home_getMemberList() {
  global $base_url, $files_dir;
  $status_id=variable_get('churchdb_memberlist_status', '1');
  if ($status_id=="") $status_id="-1";
  $station_id=variable_get('churchdb_memberlist_station', '1,2,3');
  if ($station_id=="") $station_id="-1";

  $sql='select person_id, name, vorname, strasse, ort, plz, land,
         year(geburtsdatum) year, month(geburtsdatum) month, day(geburtsdatum) day, 
        DATE_FORMAT(geburtsdatum, \'%d.%m.%Y\') geburtsdatum, DATE_FORMAT(geburtsdatum, \'%d.%m.\') geburtsdatum_compact,
         (case when geschlecht_no=1 then \'Herr\' when geschlecht_no=2 then \'Frau\' else \'\' end) "anrede",
         telefonprivat, telefongeschaeftlich, telefonhandy, fax, email, imageurl
         from {cdb_person} p, {cdb_gemeindeperson} gp where gp.person_id=p.id and gp.station_id in ('.$station_id.')
          and gp.status_id in ('.$status_id.') and archiv_yn=0 order by name, vorname';
  $db=db_query($sql);
  $res=array();
  foreach ($db as $r) {
    $res[]=$r;
  }
  return $res;
}

function home__memberlist() {
  global $base_url, $files_dir, $config;
  
  if (!user_access("view memberliste","churchdb")) { 
     addErrorMessage("Keine Berechtigung f&uuml;r die Mitgliederliste!");
     return " ";
  }  
  
  $fields=_home__memberlist_getSettingFields()->fields;
  
  $txt='<small><i><a class="cdb_hidden" href="?q=home/memberlist_printview" target="_clean">Druckansicht</a></i></small>';
  if (user_access("administer settings","churchcore"))
    $txt.='&nbsp; <small><i><a class="cdb_hidden" href="?q=home/memberlist_settings">Admin-Einstellung</a></i></small>';
  
  $txt.="<table class=\"table table-condensed\"><tr><th><th>Anrede<th>Name<th>Adresse<th>Geb.<th>Kontaktdaten</tr><tr>";
  $link = $base_url;
  
  $res=home_getMemberList();
  foreach ($res as $arr) {
    
    if ($arr->imageurl==null) $arr->imageurl="nobody.gif";        
    $txt.="<tr><td><img width=\"65px\"src=\"$base_url$files_dir/fotos/".$arr->imageurl."\"/>";         
    $txt.='<td><div class="dontbreak">'.$arr->anrede.'<br/>&nbsp;</div><td><div class="dontbreak">';
    
    if ((user_access("view","churchdb")) && (user_access("view alldata","churchdb")))
      $txt.="<a href=\"$link?q=churchdb#PersonView/searchEntry:#".$arr->person_id."\">".$arr->name.", ".$arr->vorname."</a>";
    else
      $txt.=$arr->name.", ".$arr->vorname;      
    
    $txt.='<br/>&nbsp;</div><td><div class="dontbreak">'.$arr->strasse."<br/>".$arr->plz." ".$arr->ort."</div>";  
       
    $birthday="";
    if ($arr->geburtsdatum!=null) {
      if ($arr->year<7000) 
        $birthday="$arr->day.$arr->month.";
      if ($arr->year!=1004 && $fields["memberlist_birthday_full"]->getValue()) {
        if ($arr->year<7000)
          $birthday=$birthday.$arr->year;
        else  
          $birthday=$birthday.$arr->year-7000;
      }
    } 
    
    
    $txt.="<td><div class=\"dontbreak\">$birthday<br/>&nbsp;</div><td><div class=\"dontbreak\">";
    if (($fields["memberlist_telefonprivat"]->getValue()) && ($arr->telefonprivat!="")) 
      $txt.=$arr->telefonprivat."<br/>";
    if (($fields["memberlist_telefonhandy"]->getValue()) && ($arr->telefonhandy!="")) 
      $txt.=$arr->telefonhandy."<br/>";
    if (($arr->telefonprivat=="") && ($arr->telefonhandy=="")) {  
      if (($fields["memberlist_telefongeschaeftlich"]->getValue()) && ($arr->telefongeschaeftlich!="")) 
        $txt.=$arr->telefongeschaeftlich."<br/>";
      if (($fields["memberlist_fax"]->getValue()) && ($arr->fax!="")) 
        $txt.=$arr->fax." (Fax)<br/>";
    }
    if (($fields["memberlist_email"]->getValue()) && ($arr->email!="")) 
      $txt.='<a href="mailto:'.$arr->email.'">'.$arr->email.'</a><br/>';
    $txt.="</div>";
  }
  
  $txt.="</table>";
  return $txt;
}  


function home__memberlist_printview() {
  global $base_url, $files_dir, $config;
  //  $content='<html><head><meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />';
//   drupal_add_css('system/bootstrap/css/bootstrap.min.css');
//  drupal_add_css(drupal_get_path('module', 'churchdb').'/cdb_printview.css');
//  $content=$content.drupal_get_header();
  if (!user_access("view memberliste","churchdb")) { 
     addErrorMessage("Keine Berechtigung f&uuml;r die Mitgliederliste!");
     return " ";
  }  

  require_once('system/assets/fpdf17/fpdf.php');
  $compact=true;
  if (isset($_GET["compact"])) $compact=$_GET["compact"];
  
  class PDF extends FPDF {
    //Kopfzeile
    function Header() {
      //Logo
//      $this->Image('system/assets/img/ct-icon_256.png',10,8,33);
      //Arial fett 15
      $this->SetFont('Arial','B',9);
      //nach rechts gehen
      $this->Cell(12,7,'',0);
      //Titel
      $this->Cell(13,8,'Anrede',0,0,'L');
      $this->Cell(48,8,'Name',0,0,'L');
      $this->Cell(45,8,'Adresse',0,0,'L');
      $this->Cell(20,8,'Geb.',0,0,'L');
      $this->Cell(30,8,'Kontaktdaten',0,0,'L');
      $fields=_home__memberlist_getSettingFields()->fields;
      if ($fields["memberlist_telefonhandy"]->getValue())
        $this->Cell(30,8,'Handy',0,0,'L');
      //Zeilenumbruch
      $this->SetLineWidth(0.1);
      $this->SetDrawColor(200, 200, 200);
      $this->Line(8,$this->GetY(),204,$this->GetY());
      $this->Ln(9);
      $this->Line(8,$this->GetY()-1,204,$this->GetY()-1);
    }
  
    //Fusszeile
    function Footer() {
      //Position 1,5 cm von unten
      $this->SetY(-10);
      //Arial kursiv 8
      $this->SetFont('Arial','I',8);
      //Seitenzahl
      $this->Cell(0,5,'Seite '.$this->PageNo().'/{nb}',0,0,'C');
    }
  }
  
  //Instanciation of inherited class
  $pdf=new PDF('P','mm','A4');
  $pdf->AliasNbPages();
  $pdf->AddPage();
  $pdf->SetFont('Arial','',9);
  $res=home_getMemberList();
  $pdf->SetLineWidth(0.4);
  $pdf->SetDrawColor(200, 200, 200);
  $fields=_home__memberlist_getSettingFields()->fields;
  foreach ($res as $p) {
      $pdf->Line(8,$pdf->GetY()-1,204,$pdf->GetY()-1);
      $pdf->Cell(10,10,"",0);
      if (($p->imageurl==null) || (!file_exists("$files_dir/fotos/$p->imageurl"))) 
        $p->imageurl="nobody.gif";        
      $pdf->Image("$files_dir/fotos/$p->imageurl",$pdf->GetX()-10,$pdf->GetY()+1,9);
      $pdf->Cell(2);
      $pdf->Cell(13,9,$p->anrede,0,0,'L');
      $pdf->Cell(48,9,utf8_decode("$p->name, $p->vorname"),0,0,'L');
      $pdf->Cell(45,9,utf8_decode("$p->strasse"),0,0,'L');
      
      $birthday="";
      if ($p->geburtsdatum!=null) {
        if ($p->year<7000)
          $birthday="$p->day.$p->month.";
        if ($p->year!=1004 && $fields["memberlist_birthday_full"]->getValue()) {
          if ($p->year<7000)
            $birthday=$birthday.$p->year;
          else
            $birthday=$birthday.$p->year-7000;
        }
      }      
      $pdf->Cell(20,9,$birthday,0,0,'L');
      
      if (($fields["memberlist_telefonprivat"]->getValue()) && ($p->telefonprivat!="")) 
         $pdf->Cell(30,9,$p->telefonprivat,0,0,'L');
      else if (($fields["memberlist_telefongeschaeftlich"]->getValue()) && ($p->telefongeschaeftlich!="")) 
         $pdf->Cell(30,9,$p->telefongeschaeftlich,0,0,'L');
      else if (($fields["memberlist_telefongeschaeftlich"]->getValue()) && ($p->fax!="")) 
         $pdf->Cell(30,9,$p->fax." (Fax)",0,0,'L');
      else $pdf->Cell(30,9,"",0,0,'L');
      if (($fields["memberlist_telefonhandy"]->getValue()) && ($p->telefonhandy!="")) 
         $pdf->Cell(30,9,$p->telefonhandy,0,0,'L');
      
      //Zeilenumbruch
      $pdf->Ln(5);
      $pdf->Cell(73);
      $pdf->Cell(48,10,"$p->plz ".utf8_decode($p->ort),0,0,'L');
      $pdf->Cell(17);
      if (($fields["memberlist_email"]->getValue()) && ($p->email!="")) {
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(30,9,$p->email);
        $pdf->SetFont('Arial','',9);
      }
      $pdf->Ln(12);
      
  }
  $pdf->Output('mitgliederliste.pdf','I');  
}


function home__memberlist_saveSettings($form) {
  if (isset($_POST["btn_1"])) {
    header("Location: ?q=home/memberlist");
    return null;
  }
  else {
    foreach ($form->fields as $key=>$value) {
      db_query("insert into {cc_config} (name, value) values (:name,:value) on duplicate key update value=:value",
      array(":name"=>$key, ":value"=>$value));
    }
    loadDBConfig();
  }
}

function _home__memberlist_getSettingFields() {
  global $config;
  include_once("system/includes/forms.php");

  $model = new CC_Model("AdminForm", "home__memberlist_saveSettings");
  $model->setHeader("Einstellungen f&uuml;r die Mitgliederliste", "Der Administrator kann hier Einstellung vornehmen.");
  $model->addField("churchdb_memberlist_status","", "INPUT_REQUIRED","Kommaseparierte Liste mit Status-Ids f&uuml;r Mitgliederliste");
  $model->fields["churchdb_memberlist_status"]->setValue($config["churchdb_memberlist_status"]);
  $model->addField("churchdb_memberlist_station","", "INPUT_REQUIRED","Kommaseparierte Liste mit Station-Ids f&uuml;r Mitgliederliste");
  $model->fields["churchdb_memberlist_station"]->setValue($config["churchdb_memberlist_station"]);

  $model->addField("memberlist_telefonprivat","", "CHECKBOX","Anzeige der privaten Telefonnummer");
  $model->fields["memberlist_telefonprivat"]->setValue((isset($config["memberlist_telefonprivat"])?$config["memberlist_telefonprivat"]:true));
  $model->addField("memberlist_telefongeschaeftlich","", "CHECKBOX","Anzeige der gesch&auml;ftlichen Telefonnummer");
  $model->fields["memberlist_telefongeschaeftlich"]->setValue((isset($config["memberlist_telefongeschaeftlich"])?$config["memberlist_telefongeschaeftlich"]:true));
  $model->addField("memberlist_telefonhandy","", "CHECKBOX","Anzeige der Mobil-Telefonnumer");
  $model->fields["memberlist_telefonhandy"]->setValue((isset($config["memberlist_telefonhandy"])?$config["memberlist_telefonhandy"]:true));
  $model->addField("memberlist_fax","", "CHECKBOX","Anzeige der FAX-Nummer");
  $model->fields["memberlist_fax"]->setValue((isset($config["memberlist_fax"])?$config["memberlist_fax"]:true));
  $model->addField("memberlist_email","", "CHECKBOX","Anzeige der EMail-Adresse");
  $model->fields["memberlist_email"]->setValue((isset($config["memberlist_email"])?$config["memberlist_email"]:true));
  $model->addField("memberlist_birthday_full","", "CHECKBOX","Anzeige des gesamten Geburtsdatums (inkl. Geburtsjahr)");
  $model->fields["memberlist_birthday_full"]->setValue((isset($config["memberlist_birthday_full"])?$config["memberlist_birthday_full"]:false));

  return $model;
}

function home__memberlist_settings() {
  $model=_home__memberlist_getSettingFields();
  $model->addButton("Speichern","ok");
  $model->addButton("Zur&uuml;ck","arrow-left");

  return $model->render();
}



class CTHomeModule extends CTAbstractModule {
  public function getMasterData() {
    global $user, $base_url, $files_dir, $config;
    include_once('./'. drupal_get_path('module', 'churchdb') .'/churchdb_db.inc');
    $res["mygroups"]=churchdb_getMyGroups($user->id, false, true);
    include_once('./'. drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
    $res["meetingRequests"]=churchcal_getMyMeetingRequest();
    return $res;    
  }
  public function updateEventService($params) {
    include_once('./'. drupal_get_path('module', 'churchservice') .'/churchservice_ajax.inc');
    return churchservice_updateEventService($params);    
  }  
  public function undoLastUpdateEventService($params) {
    global $user;
    if ($params["old_id"]!=$params["new_id"]) {
      $db=db_query("select * from {cs_eventservice} where id=:id and modified_pid=:user_id",
        array(':id'=>$params["new_id"], ':user_id'=>$user->id))->fetch();
      if ($db==false)
        throw new CTNoPermission("undoLastUpdateEventService","home");        
      
      db_query('delete from {cs_eventservice} where id=:id and modified_pid=:user_id', 
          array(':id'=>$params["new_id"], ':user_id'=>$user->id));
      db_query('update {cs_eventservice} set valid_yn=1 where id=:id ', 
          array(':id'=>$params["old_id"]));
    }
    else {
      db_query('update {cs_eventservice} set valid_yn=1, cdb_person_id=:user_id, 
           zugesagt_yn=0, name=:name where id=:id and modified_pid=:user_id', 
            array(':id'=>$params["old_id"], ':user_id'=>$user->id, 'name'=>"$user->vorname $user->name"));
    }
  }  
  public function addReasonToEventService($params) {
    global $user;
    db_query('update {cs_eventservice} set reason=:reason where id=:id and modified_pid=:user_id', 
        array(':reason'=>$params["reason"], ':id'=>$params["id"], ':user_id'=>$user->id));
  }
  public function sendEMail($params) {
    global $user;
    include_once('./'. drupal_get_path('module', 'churchdb') .'/churchdb_db.inc');
    $groups=churchdb_getMyGroups($user->id, true, true);
    if (empty($groups[$params["groupid"]])) 
      throw new CTException("Gruppe nicht erlaubt!");
    $ids=churchdb_getAllPeopleIdsFromGroups(array($params["groupid"]));
    churchcore_sendEMailToPersonids(implode(",", $ids), "[".variable_get('site_name', 'drupal')."] Nachricht von $user->vorname $user->name", $params["message"], null, true);
  }
  public function updateMeetingRequest($params) {
    include_once('./'. drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
    churchcal_updateMeetingRequest($params);
  }
}

function home__ajax() {
  $module=new CTHomeModule("home");
  $ajax = new CTAjaxHandler($module);
  
  drupal_json_output($ajax->call());
}

?>
