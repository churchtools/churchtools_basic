<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title><?= $sitename?> - <?= (($n = getConf($q."_name")) ? $n : $q) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="ChurchTools">
  <meta name="author" content="">
  
  <link href="<?= ASSETS ?>/ui/custom-theme/jquery-ui-1.10.3.custom.css" rel="stylesheet">
  <link href="<?= BOOTSTRAP ?>/css/bootstrap.min.css" rel="stylesheet">
  <!--  link href="<?= ASSETS ?>/ui/jquery-ui-1.8.18.custom.css" rel="stylesheet"-->
  <link href="<?= INCLUDES ?>/churchtools.css" rel="stylesheet">
    
<? if (!$embedded): ?>
  <style> body {padding-top: 60px; padding-bottom: 40px; } </style>
<? endif; ?>

  <link href="<?= BOOTSTRAP ?>/css/bootstrap-responsive.min.css" rel="stylesheet">
   <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  <script src="<?= ASSETS ?>/js/jquery-1.10.2.min.js"></script>
  <script src="<?= ASSETS ?>/js/jquery-migrate-1.2.1.min.js"></script>
  
  <script src="<?= CHURCHCORE ?>/shortcut.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.core.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.position.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.widget.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.menu.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.autocomplete.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.datepicker.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.dialog.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.mouse.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.draggable.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.droppable.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.sortable.min.js"></script>
  <script src="<?= ASSETS ?>/ui/jquery.ui.resizable.min.js"></script>
  <script src="<?= CHURCHCORE ?>/churchcore.js"></script>
  <script src="<?= CHURCHCORE ?>/churchforms.js"></script>
  <script src="<?= CHURCHCORE ?>/cc_interface.js"></script>
  <script src="<?= createI18nFile("churchcore") ?>"></script>
  <script> 
      var settings=new Object();
      settings.files_url="<?= $base_url . $files_dir ?>";
      settings.base_url="<?= $base_url ?>";
      settings.q="<?= $q ?>";
      settings.user=new Object();
<? if (isset($user)): ?>
      settings.user.id="<?= $user->id ?>";
      settings.user.vorname="<?= $user->vorname ?>";
      settings.user.name="<?= $user->name ?>";
<? endif; ?>
      version=<?= getConf("version") ?>;
  </script>

 <link rel="shortcut icon" href="<?= ASSETS ?>/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="<?= ASSETS ?>/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?= ASSETS ?>/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?= ASSETS ?>/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="<?= ASSETS ?>/ico/apple-touch-icon-57-precomposed.png">
    <?= $add_header; ?>
</head>

<? if (!$embedded): ?>

<body>
    <div class="navbar navbar-fixed-top <?= $simulate ?  '' : 'navbar-inverse' ?>">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href=".">
            <? if ($logo): ?><img src="<?= $logo ?>" style="max-width:100px;max-height:32px;margin:-10px 4px -8px 0px"/><? endif; ?>
            <?= $sitename ?>
          </a>

  <? if (userLoggedIn()): ?>
          <div class="btn-group pull-right">
            <? if ($simulate) :?><a class="btn" href="?q=simulate&link=<? $q ?>"><?= t('exit.simulation') ?></a><? endif; ?>   
            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
              <i class="icon-user"></i>&nbsp;<span class="hidden-phone"><?= $user->vorname?> <?= $user->name ?></span>
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
    <? if (isset($_SESSION["family"])): ?>
    <?php // TODO: in bootstrap 3.2.0 this looks nice, in CT dropdown-header is missed - manually added to bootstrap for now ?>
              <li class="dropdown-header"> <?= t('change.to') ?></li>
      <? foreach ($_SESSION["family"] as $family): ?>
              <li><a href="?q=login&family_id=<?= $family->id?>"><?= $family->vorname?> <?= $family->name ?></a></li>                       
      <? endforeach; ?>                       
              <li class="divider"></li>
    <? endif; ?>
    
              <li><a href="?q=profile"><?= $user->password ? t("change.password"): t("set.password") ?></a></li>
    <? if (user_access("view", "churchdb") && !empty($user->email)): ?>
              <li><a href="?q=churchdb/mailviewer"><?= t('sent.messages')?></a></li>
    <? endif; ?>
              <li class="divider"></li>

    <? if (user_access("administer settings", "churchcore")): ?>
              <li><a href="?q=admin"><?= t('admin.settings')?></a></li>
    <? endif; ?>                       
    <? if (user_access("administer persons",  "churchcore")): ?>
              <li><a href="?q=churchauth"><?= t('admin.permissions')?></a></li>
    <? endif; ?>                       
    <? if (user_access("administer settings", "churchcore")): ?>
              <li><a href="?q=cron&manual=true"><?= t('start.cronjob')?></a></li>
    <? endif; ?>                       
    <? if (user_access("view logfile",        "churchcore")): ?>
              <li><a href="?q=churchcore/logviewer"><?= t('logviewer')?></a></li>
    <? endif; ?>                       
    <? if (user_access("administer settings", "churchcore")): ?>
              <li class="divider"></li>
    <? endif; ?>                       
  
             <li><a href="?q=about"><?= t('about')?> <?= $sitename ?></a></li>
             <li class="divider"></li>
             <li><a href="?q=logout"><?= t('logout') ?></a></li>
           </ul>
         </div>
         <div class="pull-right">
           <ul class="nav">
             <li class="active"><p><div id="cdb_status" style="color:#999"></div></li>
           </ul>
         </div>
  <? else: ?>
         <div class="pull-right">
           <ul class="nav">
              <li<?= ($q == "login") ? ' class="active"' : '' ?>>
                <a href="?q=login"><i class="icon-user icon-white"></i>&nbsp;<?= t("login") ?></a>
              </li>';
           </ul>
         </div>
  <? endif; ?>
          <div class="nav-collapse">
            <ul class="nav">
  <? foreach (churchcore_getModulesSorted() as $key): ?>
    <? if (getConf($key."_name") && getConf($key."_inmenu") == "1"
           && (user_access("view", $key) || in_array($key, $mapping["page_with_noauth"]))):?>
                <li <?= ($q == $key) ? 'class="active"' : "" ?>>
                <a href="?q=<?= $key ?>"><?= getConf($key."_name") ?></a></li>
    <? endif; ?>                       
  <? endforeach; ?>                       
            </ul>
              <!--form class="navbar-search pull-right">
                <input type="text" class="search-query" placeholder="Search">
              </form-->  
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>    
    <div class="container-fluid" id="page">
<? else: ?>                       
     <body style="background:none"> 
     <div>
<? endif; ?>                       
<? if (getConf("site_offline") == 1): ?>
     <div class="alert alert-info"><?= t("offline.mode.is.active") ?></div>
<? endif; ?>
