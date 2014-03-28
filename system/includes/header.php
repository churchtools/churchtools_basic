<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<title><?php echo $config["site_name"].(isset($config["test"])?" TEST ":"")." - ".(isset($config[$q."_name"])?$config[$q."_name"]:$q); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	    <meta name="description" content="ChurchTools">
    <meta name="author" content="">

	
    <link href="system/assets/ui/custom-theme/jquery-ui-1.10.3.custom.css" rel="stylesheet">
    <link href="system/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!--  link href="system/assets/ui/jquery-ui-1.8.18.custom.css" rel="stylesheet"-->
    <link href="system/includes/churchtools.css" rel="stylesheet">
    
   <?php if (!$embedded) {?>
    <style>
    body {
      padding-top: 60px;
      padding-bottom: 40px;
    }
    </style>
   <?php } ?>

  <link href="system/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
   <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
<script src="system/assets/js/jquery-1.10.2.min.js"></script>
<script src="system/assets/js/jquery-migrate-1.2.1.min.js"></script>

<script src="system/churchcore/shortcut.js"></script>
<script src="system/assets/ui/jquery.ui.core.min.js"></script>
<script src="system/assets/ui/jquery.ui.position.min.js"></script>
<script src="system/assets/ui/jquery.ui.widget.min.js"></script>
<script src="system/assets/ui/jquery.ui.menu.min.js"></script>
<script src="system/assets/ui/jquery.ui.autocomplete.min.js"></script>
<script src="system/assets/ui/jquery.ui.datepicker.min.js"></script>
<script src="system/assets/ui/jquery.ui.dialog.min.js"></script>
<script src="system/assets/ui/jquery.ui.mouse.min.js"></script>
<script src="system/assets/ui/jquery.ui.draggable.min.js"></script>
<script src="system/assets/ui/jquery.ui.droppable.min.js"></script>
<script src="system/assets/ui/jquery.ui.sortable.min.js"></script>
<script src="system/assets/ui/jquery.ui.resizable.min.js"></script>
<script src="system/churchcore/churchcore.js"></script>
<script src="system/churchcore/churchforms.js"></script>
<script src="system/churchcore/cc_interface.js"></script>
<script> <?php
  echo "var settings=new Object();"; 
  echo "settings.files_url=\"$base_url$files_dir\";"; 
  echo "settings.base_url=\"$base_url\";"; 
  echo "settings.q=\"$q\";"; 
  echo "settings.user=new Object();";
  if (isset($user)) {
    echo "settings.user.id=\"$user->id\";";
    echo "settings.user.vorname=\"$user->vorname\";";
    echo "settings.user.name=\"$user->name\";";
  }
  echo 'version='.$config["version"];
  
?></script>
<script src="<?php echo createI18nFile("churchcore"); ?>"></script>

 <link rel="shortcut icon" href="system/assets/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="system/assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="system/assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="system/assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="system/assets/ico/apple-touch-icon-57-precomposed.png">
    <?php echo $add_header; ?>
</head>

   <?php if (!$embedded) {?>
    <body>

   
    <div class="navbar navbar-fixed-top <?php if (!isset($_SESSION["simulate"])) echo "navbar-inverse" ?>">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href=".">
            <?php if (isset($config["site_logo"]) && $config["site_logo"]!="") echo '<img src="'.$files_dir."/files/logo/".$config["site_logo"].'" style="max-width:100px;max-height:32px;margin:-10px 4px -8px 0px"/>' ?>
            <?php echo $config["site_name"].(isset($config["test"])?" TEST ":"") ?>
          </a>

            <?php if (userLoggedIn()) { ?>
              <div class="btn-group pull-right">
                <?php if (isset($_SESSION["simulate"])) {?>
                  <a class="btn" href="?q=simulate&link=<?php echo $q?>">
                    <?php echo t('exit.simulation')?>
                  </a>
                  <?php } ?>   
                <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                  <i class="icon-user"></i>&nbsp;<span class="hidden-phone"><?php echo $_SESSION["user"]->vorname." ".$_SESSION["user"]->name ?> </span>
                  <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                  <?php //Andere Familienmitglieder mit der gleichen E-Mail-Adresse
                   if (isset($_SESSION["family"])) {
                     echo('<caption>'.t('change.to').'</caption>');
                     foreach ($_SESSION["family"] as $family) {
                        echo('<li><a href="?q=login&family_id='.$family->id.'">'.$family->vorname.' '.$family->name.'</a></li>');                        
                      }                       
                      echo('<li class="divider"></li>');
                    }                                    
                  ?>
                  <li><a href="?q=profile">
                  <?php 
                    if (isset($user->password)) echo t("change.password");
                    else echo t("set.password"); 
                  ?></a></li>
                  <?php if ((user_access("view", "churchdb")) && (isset($user)) && ($user->email!='')) { 
                    echo '<li><a href="?q=churchdb/mailviewer">'.t('sent.messages').'</a></li>';
                  } ?>  
                  <li class="divider"></li>
                  <?php 
                  
                    if (user_access("administer settings", "churchcore")) {
                      echo '<li><a href="?q=admin">'.t('admin.settings').'</a></li>';                      
                    }                  
                    if (user_access("administer persons", "churchcore")) {
                      echo '<li><a href="?q=churchauth">'.t('admin.permissions').'</a></li>';
                    } 
                    if (user_access("administer settings", "churchcore")) {
                      echo '<li><a href="?q=cron&manual=true">'.t('start.cronjob').'</a></li>';
                    }                  
                    if (user_access("view logfile", "churchcore")) {
                      echo '<li><a href="?q=churchcore/logviewer">'.t('logviewer').'</a></li>';
                    }
                    if (user_access("administer settings", "churchcore")) {
                      echo '<li class="divider"></li>';
                    }                  
                    ?>   
                  <li><a href="?q=about"><?php echo t('about')." ".$config['site_name']; ?></a></li>
                  <li class="divider"></li>
                  <li><a href="?q=logout"><?php echo t('logout');?></a></li>
                </ul>
              </div>
             <div class="pull-right">
               <ul class="nav">
                 <li class="active"><p><div id="cdb_status" style="color:#999"></div></li>
               </ul>
             </div>
            <?php } 
            else { ?>
             <div class="pull-right">
               <ul class="nav">
                  <?php echo '<li ';
                        if ($q=="login") echo ' class="active"'; 
                        echo '><a href="?q=login"><i class="icon-user icon-white"></i> '.t("login").'</a></li>';
	               ?>
               </ul>
             </div>
             <?php } ?>
              <div class="nav-collapse">
                <ul class="nav">
                  <?php
                    $arr=churchcore_getModulesSorted();
                    foreach ($arr as $key) {
                      if ((isset($config[$key."_name"])) && (isset($config[$key."_inmenu"])) && ($config[$key."_inmenu"]=="1") 
                             && ((user_access("view", $key)) || (in_array($key,$mapping["page_with_noauth"])))) {
                        echo "<li ";
                        if ($q==$key) echo 'class="active"';
                        echo '><a href="?q='.$key.'">';
                        echo $config[$key."_name"];
                        echo "</a></li>";
                      }                      
                    }  
                   ?>                  
                </ul>
                  <!--form class="navbar-search pull-right">
                    <input type="text" class="search-query" placeholder="Search">
                  </form-->  
              </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>    
    <div class="container-fluid" id="page">
  <?php 
    } else {
      echo '<body style="background:none">';    
      echo '<div>';
    }
    if ((isset($config["site_offline"]) && ($config["site_offline"]==1))) {   
      echo '<div class="alert alert-info">'.t("offline.mode.is.active").'</div>';
    } 
    ?>
    