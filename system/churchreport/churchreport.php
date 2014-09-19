<?php

/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2014 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt
 *
 * ChurchReport Module
 * Depends on ChurchCore, ChurchDB
 */

/**
 * main function for churchreport
 * @return string
 */
function churchreport_main() {
  global $files_dir;

  drupal_add_js(ASSETS . '/js/jquery.history.js');

  drupal_add_js(CHURCHCORE . '/cc_abstractview.js');
  drupal_add_js(CHURCHCORE . '/cc_standardview.js');
  drupal_add_js(CHURCHCORE . '/cc_maintainstandardview.js');

  drupal_add_js(ASSETS . '/pivottable/pivot.js');
  drupal_add_css(ASSETS . '/pivottable/pivot.css');

  drupal_add_js(CHURCHREPORT . '/report_maintainview.js');
  drupal_add_js(CHURCHREPORT . '/churchreport.js');

  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchreport"));

  $content = '
    <div class="row-fluid">
      <div class="span12">
      <div id="cdb_navi"></div>
        <div id="cdb_menu"></div>
        <div id="cdb_content"></div>
      </div>
   </div>';

  return $content;
}

/**
 * get admin form for churchreport
 * @return CTModuleForm
 */
function churchreport_getAdminForm() {
  $form = new CTModuleForm("churchreport");
  return $form;
}

/**
 * get current version number
 * @param int $doc_id
 * @param int $wikicategory_id
 * @return int
 */
function churchreport_getCurrentNo($doc_id, $wikicategory_id = 0) {
  $res = db_query("SELECT MAX(version_no) c
                   FROM {cc_wiki}
                   WHERE doc_id=:doc_id and wikicategory_id=:wikicategory_id",
                   array (":doc_id" => $doc_id, ":wikicategory_id" => $wikicategory_id))
                   ->fetch();
  if ($res == false) return -1;
  else return $res->c;
}

/**
 * get auth
 * @return unknown
 */
function churchreport_getAuth() {
  $cc_auth = array ();
  $cc_auth = addAuth($cc_auth, 701, 'view', 'churchreport', null, t('view.x', 'ChurchReport'), 1);
  $cc_auth = addAuth($cc_auth, 799, 'edit masterdata', 'churchreport', null, t('edit.masterdata'), 1);
  return $cc_auth;
}

function churchreport_cron() {
}

/**
 * some functions moved into class
 */


function churchreport__ajax() {
  $module = new CTChurchReportModule("churchreport");
  $ajax = new CTAjaxHandler($module);
  $res = $ajax->call();
  drupal_json_output($res);
}
