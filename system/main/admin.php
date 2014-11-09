<?php

/**
 * main function for admin
 *
 * @return string
 */
function admin_main() {
  global $config;

  drupal_add_css(ASSETS . '/fileuploader/fileuploader.css');
  drupal_add_js(ASSETS . '/fileuploader/fileuploader.js');

  $form = new CTForm('AdminForm', 'admin_saveSettings');

  $form->addField('site_name', '', 'INPUT_REQUIRED', t('site.name'))
    ->setValue($config['site_name']);

  $form->addField('site_logo', '', 'FILEUPLOAD', t('site.logo'))
    ->setValue(getConf('site_logo'));

  $form->addField('welcome', '', 'INPUT_REQUIRED', t('welcome.message'))
    ->setValue($config['welcome']);

  $form->addField('welcome_subtext', '', 'INPUT_REQUIRED', t('subtitle.welcome.message'))
    ->setValue($config['welcome_subtext']);

  $form->addField('login_message', '', 'INPUT_REQUIRED', t('welcome.message.before.login'))
    ->setValue($config['login_message']);

  $form->addField('invite_email_text', '', 'TEXTAREA', t('text.of.invitation.email'))
    ->setValue($config['invite_email_text']);

  $form->addField('admin_message', '', 'INPUT_OPTIONAL', t('admin.message.on.home.and.login.pages.for.planned.downtimes'))
    ->setValue(getConf('admin_message', ''));

  if (!isset($config['site_startpage'])) $config['site_startpage'] = 'home';
  $form->addField('site_startpage', '', 'INPUT_REQUIRED', t('startpage.for.siteX.standard.is.y', getConf('site_name'), '<i>home</i>'))
    ->setValue($config['site_startpage']);

  $form->addField('site_mail', '', 'EMAIL', t('emailaddress.for.site.as.sender.for.emails'))
    ->setValue($config['site_mail']);

  $form->addField('admin_mail', '', 'EMAIL', t('admin.emails.for.user.requests'))
    ->setValue(isset($config['admin_mail']) ? $config['admin_mail'] : $config['site_mail']);

  // iterate through modules for naming them
  $modules = churchcore_getModulesSorted(false, true);
  foreach ($modules as $module) {
    $form->addField($module . '_name', '', 'INPUT_OPTIONAL', t('name.for.moduleX.keep.empty.to.deactivate', "<i>$module</i>"))
      ->setValue(getConf($module . '_name', ''));
  }

  $form->addField('max_uploadfile_size_kb', '', 'INPUT_REQUIRED', t('max.upload.size.in.kb'))
    ->setValue($config['max_uploadfile_size_kb']);

  $form->addField('cronjob_delay', '', 'INPUT_REQUIRED', t('time.in.seconds.beetwen.cronjobs.with.explanation'))
    ->setValue($config['cronjob_delay']);

  $form->addField('timezone', '', 'INPUT_REQUIRED', t('standard.timezone.like.europe.berlin'))
    ->setValue($config['timezone']);

  $form->addField('show_remember_me', '', 'CHECKBOX', t('show.remember.me.on.login.page', '<i>'. t('remember.me') . '</i>'))
    ->setValue($config['show_remember_me']);

  $form->addField('mail_enabled', '', 'CHECKBOX', t('enable.sending.emails'))
    ->setValue($config['mail_enabled']);

  $form->addField('site_offline', '', 'CHECKBOX', t('disable.site'))
    ->setValue($config['site_offline']);

  $form->addButton(t('save'), 'ok');

  $txtCommonForm =  $form->render(false);

  // iterate through modules getting the admin forms
  $m = array ();
  foreach ($modules as $module) {
    include_once (constant(strtoupper($module)) . "/$module.php");
    if (function_exists($module . "_getAdminForm")) {
      $form = call_user_func($module . "_getAdminForm");
      if ($form) $m[$module] = $form->render();
    }
  }

  $txt = '<h1>' . t("settings.for", getConf("site_name")) . '</h1>
      <p>' . t('admin.settings.info.text') . '</p>
      <div class="tabbable">
        <ul class="nav nav-tabs">
          <li class="active"><a href="#tab1" data-toggle="tab">' . t("general") . '</a></li>';
  foreach ($modules as $module) {
    if (isset($m[$module]) && getConf($module . "_name")) {
      $txt .= '
          <li><a href="#tab' . $module . '" data-toggle="tab">' . getConf($module . "_name") . '</a></li>';
    }
  }
  $txt .= '
        </ul>
        <div class="tab-content">
        <div class="tab-pane active" id="tab1">' . $txtCommonForm . '</div>';

  foreach ($modules as $module) if (isset($m[$module])) {
    $txt .= '<div class="tab-pane" id="tab' . $module . '">' . $m[$module] . '</div>';
  }

  $txt .= '</div></div>';

  return $txt;
}

/**
 * save admin settings and reload config
 *
 * TODO: feature: automatically downsize logo file
 *
 * @param CTForm $form
 */
function admin_saveSettings($form) {
  foreach ($form->fields as $key => $value) {
    db_query("INSERT INTO {cc_config} (name, value)
              VALUES (:name,:value)
              ON DUPLICATE KEY UPDATE value=:value", array (":name" => $key, ":value" => $value));
  }
  // TODO: test if max_uploadfile_size_kb is bigger then allowed in php.ini
  loadDBConfig();
}


function admin__uploadfile() {
  global $files_dir, $config;

  include_once (CHURCHCORE . "/uploadFile.php");
  churchcore__uploadFile();
}

function admin__ajax() {
  $module = new CTAdminModule("admin");
  $ajax = new CTAjaxHandler($module);

  drupal_json_output($ajax->call());
}
