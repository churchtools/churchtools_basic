<?php 

function home_main() {
  global $config, $files_dir, $mapping;

  if ((isset($config["admin_message"])) && ($config["admin_message"]!=""))
    addErrorMessage($config["admin_message"]);
  
  checkFilesDir();
  
/* $user=$_SESSION["user"];  
 $user->auth=getUserAuthorization($user->id);
 print_r($user->auth);
 $_SESSION["user"]=$user;*/
  
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
      if (($config[$key."_name"]!="") && (user_access("view", $key)))  {   
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
    if ($config[$key."_name"]!="") {
      //include_once("system/$key/$key.php");
      include_once("system/".$mapping[$key]);
      if (function_exists($key."_blocks")) {
        $arr=call_user_func($key."_blocks");
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

?>
