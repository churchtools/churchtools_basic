<?php 

function login_main() {
  global $q, $config;
  $txt="";
  
  if ((isset($config["admin_message"])) && ($config["admin_message"]!=""))
    addErrorMessage($config["admin_message"]);
  if ((isset($_GET["message"])) && ($_GET["message"]!=""))
    addInfoMessage($_GET["message"]);
  
  // Sicherstellen, dass keiner eingelogt ist!
  if (!userLoggedIn()) {    
    include_once("system/includes/forms.php");
    if (isset($config["login_message"]))
      addInfoMessage($config["login_message"], true);
    $model = new CC_Model("LoginForm", "prooveLogin", "Login");
    $model->setHeader(t("login.headline"), t("please.fill.following.fields"));    
    $model->addField("email","", "INPUT_REQUIRED",t("email.or.username"), true);
    $model->addField("password","", "PASSWORD",t("password"));
    if ((!isset($config["show_remember_me"])) || ($config["show_remember_me"]==1)) 
      $model->addField("rememberMe","", "CHECKBOX",t("remember.me"));
    $model->addButton(t("login"),"ok");
    
    if (isset($_GET["newpwd"])) {
      $res=db_query("select count(*) c from {cdb_person} where email='".$_GET["email"]."' and archiv_yn=0")->fetch();
      if (($_GET["email"]=="") || ($res->c==0)) {
        $txt.='<div class="alert alert-error"><p>Bitte ein g&uuml;ltige EMail-Adresse angeben, 
          an die das neue Passwort gesendet werden kann! 
          Diese Adresse muss im System schon eingerichtet sein.
          <p>Falls die E-Mail-Adresse schon eingerichtet sein sollte, 
          wende Dich bitte an <a href="'.variable_get("site_mail").'">'.variable_get("site_mail").
        '</a>.</div>';
      } 
      else {              
        $newpwd=random_string(8);
        $scrambled_password=scramble_password($newpwd);
        db_query("update {cdb_person} set password='".$scrambled_password."' where email='".$_GET["email"]."'");
        $content="<h3>Hallo!</h3><p>Ein neues Passwort wurde f&uuml;r die E-Mail-Adresse <i>".$_GET["email"]."</i> angefordert: $newpwd";
        churchcore_systemmail($_GET["email"], "[".variable_get('site_name')."] Neues Passwort", $content, true, 1);
        churchcore_sendMails(1);
        $txt.='<div class="alert alert-info">Hinweis: Ein neues Passwort wurde nun an <i>'.$_GET["email"].'</i> gesendet.</div>';
        ct_log("Neues Passwort angefordert ".$_GET["email"],2,"-1", "login");
      }        
    } 
    // Zugriff Ÿber externe Tools mit GET und zusŠtzlichen direct
    else if ((isset($_POST["email"])) && (isset($_POST["password"])) && (isset($_POST["directtool"]))) {
      include_once("system/churchcore/churchcore_db.inc");
      $sql="select * from {cdb_person} where email=:email and active_yn=1 and archiv_yn=0";
      $res=db_query($sql, array(":email"=>$_POST["email"]))->fetch();
      if ($res==false) {
        drupal_json_output(jsend()->fail("Unbekannte E-Mail-Adresse"));        
      }
      else if (user_check_password($_POST["password"], $res)) {      
      login_user($res);            
        ct_log("Login durch Direct-Tool ".$_POST["directtool"]." mit ".$_POST["email"],2,"-1", "login");
        drupal_json_output(jsend()->success());        
      }
      else drupal_json_output(jsend()->fail("Falsches Passwort"));        
      return;
    }
    // PrŸfe, ob Login Ÿber URL mit loginstr erfolgen soll
    //e.g. http://localhost:8888/bootstrap/?q=profile&loginstr=123&id=8
    else if ((isset($_GET["loginstr"])) && ($_GET["loginstr"]!="") && (isset($_GET["id"]))) {
      // Lšsche alte cc_loginurrls die Šlter sind als 14 tage
      db_query("delete from {cc_loginstr} where DATEDIFF( current_date, create_date ) > 13");
      $sql="select * from {cc_loginstr} where loginstr=:loginstr and person_id=:id";      
      $res=db_query($sql, array(":loginstr"=>$_GET["loginstr"], ":id"=>$_GET["id"]))->fetch();
      if ($res==false) {
        $txt.='<div class="alert alert-info">Fehler: Der verwendete Login-Link ist nicht mehr aktuell und kann deshalb nicht mehr verwendet werden. Bitte mit E-Mail-Adresse und Passwort anmelden!</div>';
      }
      else {
        // Nehme den LoginStr heraus, damit er nicht mi§braucht werden kann.
        $sql="delete from {cc_loginstr} where loginstr=:loginstr and person_id=:id";      
        $res=db_query($sql, array(":loginstr"=>$_GET["loginstr"], ":id"=>$_GET["id"]));
        ct_log("Login User ".$_GET["id"]." erfolgreich mit loginstr ",2,"-1", "login");
        $res=churchcore_getPersonById($_GET["id"]);  
        login_user($res);
      }
    }
    
    
    $txt.=$model->render();
    $txt.='<script>jQuery("#newpwd").click(function(k,a) {
         if (confirm("'.t('want.to.receive.new.password').'")) {
           window.location.href="?newpwd=true&email="+jQuery("#LoginForm_email").val()+"&q='.$q.'";
            }
          });</script>';
    
  }
  // Es ist schon jemand eingelogt!
  else {
    // Wenn man sich ummelden mšchte und zur Familie gehšrt (also gleiche E-Mail-Adresse)
    if (isset($_GET["family_id"])) {
      if (isset($_SESSION["family"][$_GET["family_id"]])) {
        //logout_current_user();
        login_user($_SESSION["family"][$_GET["family_id"]]);
        $txt.='<div class="alert alert-info">Ummelden erfolgreich! Du arbeitest nun mit der Berechtigung von '.$_SESSION["user"]->vorname.' '.$_SESSION["user"]->name.'.</div>';        
      }    
      else $txt.='<div class="alert alert-info">Ummelden zu Id:'.$_GET["family_id"].' hat nicht funktioniert, Session ist leer!</div>';
    } 
    else {
      $txt.='<div class="alert alert-info"><i>Hinweis:</i> Du bist angemeldet als '.$_SESSION["user"]->vorname.', weiter geht es <a href="?q=home">hier</a>!</div>';
    }
  }
  return $txt;
}


function prooveLogin($form) {
  $res=db_query("select * from {cdb_person} where (email=:email or cmsuserid=:email or id=:email) and archiv_yn=0", 
       array(":email"=>$form->fields["email"]->getValue()));
  
  $account_inactive=false;  
  $account_errorcountlogin=false;          
  $wrong_email=true;
  // Hier ist eine Schleife, da E-Mail-Adressen von Familienmitgliedern mehrfach benutzt werden kšnnen.
  foreach ($res as $ret) {
    $wrong_email=false;
    if ($ret->loginerrorcount>6) 
      $account_errorcountlogin=true;
    else {
      if (user_check_password($form->fields["password"]->getValue(),$ret)) {
        if ($ret->active_yn==0) {
          $account_inactive=true;            
        }
        else {
          if (!isset($form->fields["rememberMe"]))
            login_user($ret, false);
          else  
            login_user($ret, $form->fields["rememberMe"]->getValue());
          return null;
        }
      } 
      else {
        db_query("update {cdb_person} set loginerrorcount=loginerrorcount+1 where id=$ret->id");      
      }
    }
  }   
     
  if ($wrong_email) {
    $form->fields["email"]->setError(t('email.or.username.unknown'));
    ct_log("Login vergeblich: Unbekannte E-Mail-Adresse ".$form->fields["email"]->getValue(),2,"-1", "login");
    return false;                    
  }
  else if ($account_inactive) {
    $form->fields["email"]->setError('Der Zugang wurde gesperrt!');
    ct_log("Login vergeblich: Gesperrter Zugang ".$form->fields["email"]->getValue(),1,"-1", "login");
    return false;
  }
  else if ($account_errorcountlogin) {
    $form->fields["email"]->setError('Der Zugang ist kurzzeitig gesperrt, da es zu viele fehlerhafte Anmeldeversuche gab!');
    ct_log("Login vergeblich: Zu viele fehlerhafte Anmeldeversuche ".$form->fields["email"]->getValue(),1,"-1", "login");
    return false;
  }
  // Kein Passwort stimmte
  else {
    $form->fields["password"]->setError(t('wrong.password').' <a href="#" id="newpwd">'.t('forgot.password').'</a>');
    ct_log("Login vergeblich: ".$form->fields["email"]->getValue()." mit falschem Passwort",2,"-1", "login");
    return false;                
  }
}

function login_user($ret, $rember_me=false) {
  global $q, $q_orig;
  
  if (!isset($ret->id)) {
    addErrorMessage("Keine Id vorhanden, Fehler beim Login!");
    return null;
  }    
  $_SESSION["email"]=$ret->email;
 
  if ($ret->cmsuserid=="") {
    $ret->cmsuserid=$ret->vorname." ".$ret->name." [".$ret->id."]";
    db_query("update {cdb_person} set cmsuserid='".$ret->cmsuserid."' where id=$ret->id");      
  }
  if ($ret->loginstr!=null) {
    db_query("update {cdb_person} set loginstr=null where id=$ret->id");      
  }
  
  $ret->auth=getUserAuthorization($ret->id);
  $_SESSION["user"]=$ret;

  // 6 Tage hŠlt der Login
  $ablaufDesCookies = time() + 60 * 60 * 24 * 6;
  
  setcookie("RememberMe", $rember_me, $ablaufDesCookies);            
  $_SESSION["sessionid"]=random_string();
  setcookie("CC_SessionId", $_SESSION["sessionid"], $ablaufDesCookies);
  $dt = new DateTime();
  
  db_query("update {cdb_person} set lastlogin=now(), loginerrorcount=0 where id=".$ret->id);
//  db_query("delete from {cc_session} where person_id=".$ret->id." AND hostname='".$_SERVER["HTTP_HOST"]."'");
  db_query("delete from {cc_session} where datediff(now(), datum)>7");             
  db_query("insert into {cc_session} (person_id, session, hostname, datum) 
            values (".$ret->id.", '".$_SESSION["sessionid"]."', '".$_SERVER["HTTP_HOST"]."', '".$dt->format('Y-m-d H:i:s')."')");

  if ($ret->email!='') {
    // Suche Leute aus der Familie, die die gleiche EMail-Adresse haben.  
    $res=db_query("select * from {cdb_person} where email=:email and archiv_yn=0", array(":email"=>$ret->email));
    $family=null;
    $count=0;
    foreach($res as $p) {
      if ($p->id!=$ret->id) $family[$p->id]=$p;
      $count++;
      if ($count>15) break;
    }
    if ($family!=null) $_SESSION["family"]=$family;
  }
  
  ct_log("Login erfolgreich: ".$ret->email." mit ".(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"Unkown Browser!"),2,-1, "login");
  
  // Wenn es Ummelden war, dann nicht weiterleiten, denn sonst wŠre das ja wieder Login.
  if ($q!=$q_orig) {
    header("Location: ?q=$q_orig");
  }
  else if ($q=="login")
    header("Location: ?q=".variable_get("site_startpage", "home"));
}

?>
