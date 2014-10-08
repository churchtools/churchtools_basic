<?php

/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2014 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt
 *
 * ChurchWiki Module
 * Depends on ChurchCore
 */

/**
 * get form for admin settings
 *
 * @return CTModuleForm
 */
function churchwiki_getAdminForm() {
  $form = new CTModuleForm("churchwiki");
  return $form;
}

/**
 *
 * @param string $doc_id
 * @param int $wikicategory_id
 * @return int
 */
function churchwiki_getCurrentNo($doc_id, $wikicategory_id = 0) {
  $res = db_query("SELECT MAX(version_no) c FROM {cc_wiki}
                   WHERE doc_id=:doc_id and wikicategory_id=:wikicategory_id",
                   array (":doc_id" => $doc_id,
                          ":wikicategory_id" => $wikicategory_id,
                   ))->fetch();
  
  return $res ? $res->c : -1;
}

/**
 * get auth
 * @return auth
 */
function churchwiki_getAuth() {
  $cc_auth = array ();
  $cc_auth = addAuth($cc_auth, 501, 'view', 'churchwiki', null, t('view.x', getConf("churchwiki_name")), 1);
  $cc_auth = addAuth($cc_auth, 502, 'view category', 'churchwiki', 'cc_wikicategory', t('view.wiki.category'), 1);
  $cc_auth = addAuth($cc_auth, 503, 'edit category', 'churchwiki', 'cc_wikicategory', t('edit.wiki.category'), 1);
  $cc_auth = addAuth($cc_auth, 599, 'edit masterdata', 'churchwiki', null, t('edit.masterdata'), 1);
  
  return $cc_auth;
}

/**
 *
 * @param array $params
 */
function churchwiki_setShowonstartpage($params) {
  $i = new CTInterface();
  $i->setParam("doc_id");
  $i->setParam("version_no");
  $i->setParam("wikicategory_id");
  $i->setParam("auf_startseite_yn");
  
  db_update("cc_wiki")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("doc_id", $params["doc_id"], "=")
    ->condition("version_no", $params["version_no"], "=")
    ->condition("wikicategory_id", $params["wikicategory_id"], "=")
    ->execute(false);
}

/**
 * load content of wiki page
 *
 * @param string $doc_id
 * @param int $wikicategory_id
 * @param int $version_no
 * @return db result
 */
function churchwiki_load($doc_id, $wikicategory_id, $version_no = null) {
  
  if (!$version_no) $version_no = churchwiki_getCurrentNo($doc_id, $wikicategory_id);
  ct_log("Aufruf Hilfeseite $wikicategory_id:$doc_id ($version_no)", 2, "-1", "help");
  
  $data = db_query("SELECT p.vorname, p.name, doc_id, version_no, wikicategory_id, text, modified_date, modified_pid, auf_startseite_yn
                    FROM {cc_wiki} w LEFT JOIN {cdb_person} p ON (w.modified_pid=p.id)
                    WHERE version_no=:version_no AND doc_id=:doc_id AND wikicategory_id=:wikicategory_id",
                    array (':doc_id' => $doc_id,
                           ':wikicategory_id' => $wikicategory_id,
                           ':version_no' => $version_no,
                    ))->fetch();
  if (isset($data->text)) {
    $data->text = preg_replace('/\\\/', "", $data->text);
    $data->text = preg_replace('/===([^===]*)===/', "<h3>$1</h3>", $data->text);
    $data->text = preg_replace('/==([^==]*)==/', "<h2>$1</h2>", $data->text);
    $data->files = churchcore_getFilesAsDomainIdArr("wiki_" . $wikicategory_id, $doc_id);
  }
  return $data;
}

/**
 * cron
 * TODO: rework sql to one query only?
 */
function churchwiki_cron() {
  // get all dada older then 90 days and the newest version_no
  $db = db_query("SELECT MAX(version_no) version_no, wikicategory_id, doc_id FROM {cc_wiki}
                  WHERE DATE_ADD( modified_date, INTERVAL 90  DAY ) < NOW( )
                  GROUP BY wikicategory_id, doc_id");
  foreach ($db as $e) {
    db_query("DELETE FROM {cc_wiki}
              WHERE wikicategory_id=$e->wikicategory_id AND doc_id='$e->doc_id' AND version_no<$e->version_no");
  }
}

function churchwiki__filedownload() {
  include_once (CHURCHCORE . "/churchcore.php");
  churchcore__filedownload();
}

function churchwiki_getAuthForAjax() {
  $auth["view"] = user_access("view category", "churchwiki");
  $auth["edit"] = user_access("edit category", "churchwiki");
  $auth["admin"] = user_access("edit masterdata", "churchwiki");
  // TODO: reduce (((())))
  if (((isset($mapping["page_with_noauth"])) && (in_array("churchwiki", $mapping["page_with_noauth"]))) ||
       ((isset($config["page_with_noauth"]) && (in_array("churchwiki", $config["page_with_noauth"]))))) {
    if (!isset($auth["view"])) $auth["view"] = array ();
    $auth["view"][0] = "0";
  }
  
  return $auth;
}

/**
 * main function for ajax calls
 * @throws CTNoPermission
 */
function churchwiki__ajax() {
  global $user, $files_dir, $base_url, $mapping, $config;
  
  $auth = churchwiki_getAuthForAjax();
  
  if ((!user_access("view", "churchwiki"))
      && (!in_array("churchwiki", $mapping["page_with_noauth"]))
      && (!in_array("churchwiki", $config["page_with_noauth"])))
      throw new CTNoPermission("view", "churchwiki");
  
  $module = new CTChurchWikiModule("churchwiki");
  $ajax = new CTAjaxHandler($module);
  $res = $ajax->call();
  drupal_json_output($res);
}

/**
 * TODO: explain function
 * @return string
 */
function churchwiki_getWikiOnStartpage() {
  if (!user_access("view", "churchwiki")) return "";
  $ids = user_access("view category", "churchwiki");
  if (!$ids) return "";
  $res = db_query("SELECT w.wikicategory_id, w.doc_id, wc.bezeichnung, version_no,
                     DATE_FORMAT(w.modified_date , '%d.%m.%Y %H:%i') date, CONCAT(p.vorname, ' ', p.name) user
                   FROM {cc_wiki} w, {cc_wikicategory} wc, {cdb_person} p
                   WHERE wikicategory_id in (" . db_implode($ids) . ") AND w.wikicategory_id=wc.id AND w.modified_pid=p.id AND w.auf_startseite_yn=1
                   ORDER BY w.wikicategory_id, modified_date Asc");
  $arr = array();
  foreach ($res as $wiki) {
    // Hole nun die max. Version_no
    $w = db_query("SELECT MAX(version_no) version_no FROM {cc_wiki}
                   WHERE doc_id=:doc_id AND wikicategory_id=:wikicategory_id",
                   array (":doc_id" => $wiki->doc_id,
                          ":wikicategory_id" => $wiki->wikicategory_id,
                   ))->fetch();
    if ($w->version_no == $wiki->version_no) $arr[$wiki->bezeichnung][$wiki->doc_id] = $wiki;
  }
  $txt = "";
  foreach ($arr as $key => $cat) {
    $txt .= '<li><p>' . getConf("churchwiki_name") . ' ' . $key;
    foreach ($cat as $wiki) {
      $txt .= '<br/><small><a href="?q=churchwiki#WikiView/filterWikicategory_id:' . $wiki->wikicategory_id . '/doc:' .
               $wiki->doc_id . '">' . ($wiki->doc_id == "main" ? "Hauptseite" : $wiki->doc_id) . "</a>";
      $txt .= " - $wiki->date $wiki->user</small>";
    }
  }
  if ($txt) $txt = "<ul>" . $txt . "</ul>";
  
  return $txt;
}

/**
 *
 * @return string
 */
function churchwiki_getWikiInfos() {
  if (!user_access("view", "churchwiki")) return "";
  $ids = user_access("view category", "churchwiki");
  if (!$ids) return "";
  
  $res = db_query("SELECT w.wikicategory_id, w.doc_id, wc.bezeichnung, DATE_FORMAT(w.modified_date , '%d.%m.%Y %H:%i') date,
                     CONCAT(p.vorname, ' ', p.name) user
                   FROM {cc_wiki} w, {cc_wikicategory} wc, {cdb_person} p
                   WHERE wikicategory_id in (" . db_implode($ids) . ") AND w.wikicategory_id=wc.id
                     AND w.modified_pid=p.id AND DATEDIFF(NOW(),modified_date)<2
                   ORDER BY w.wikicategory_id, modified_date ASC");
  $arr = array ();
  foreach ($res as $wiki) $arr[$wiki->bezeichnung][$wiki->doc_id] = $wiki;

  $txt = "";
  foreach ($arr as $key => $cat) {
    $txt .= '<li><p>' . getConf("churchwiki_name","Wiki") . ' ' . $key;
    foreach ($cat as $wiki) {
      $txt .= '<br/><small><a href="?q=churchwiki#WikiView/filterWikicategory_id:' . $wiki->wikicategory_id . '/doc:' .
               $wiki->doc_id . '">' . ($wiki->doc_id == "main" ? "Hauptseite" : $wiki->doc_id) . "</a>";
      $txt .= " - $wiki->date $wiki->user</small>";
    }
  }
  if ($txt!="") $txt = "<ul>" . $txt . "</ul>";
  return $txt;
}

/**
 *
 * @return array
 */
function churchwiki_blocks() {
  global $config;
  
  return (array (
    1 => array (
      "label" => t("important.from", $config["churchwiki_name"]),
      "col" => 2,
      "sortkey" => 1,
      "html" => churchwiki_getWikiOnStartpage(),
      "help" => '',
      "class" => '',
    ),
    2 => array (
      "label" => t("news.from", $config["churchwiki_name"]),
      "col" => 2,
      "sortkey" => 8,
      "html" => churchwiki_getWikiInfos(),
      "help" => '',
      "class" => '',
    ),
  ));
}

/**
 *
 * @return string
 */
function churchwiki__create() {
  $form = new CTForm("EditHtml", "editHtml");
// TODO: help entry or better wiki entry?
  $form->setHeader(t('edit.help.entry'), t('edit.help.entry.subtitle'));
  $form->addField("doc_id", "", "INPUT_REQUIRED", "Doc-Id");
  $form->addField("text", "", "TEXTAREA", "Text");
  if ($doc = getVar("doc")) {
    $form->fields["doc_id"]->setValue($doc);
    $res = db_query("SELECT text FROM {cc_wiki}
                     WHERE doc_id=:doc_id",
                     array (":doc_id" => $doc))
           ->fetch();
    if ($res) {
      $res->text = preg_replace('/\\\/', "", $res->text);
      $form->fields["text"]->setValue($res->text);
    }
  }
  $form->addButton(t('save'), t('ok'));
  
  return $form->render();
}

/**
 * edit
 * @param CTForm $form
 * TODO: why notu using REPLACE?
 */
function editHtml($form) {
  global $user;
  $dt = new DateTime();
  db_query("INSERT INTO {cc_wiki} (doc_id, text, modified_date, modified_pid)
            VALUES (:doc_id, :text, :date, :pid)
            ON DUPLICATE KEY UPDATE text=:text, modified_date=:date, modified_pid=:pid",
            array (":text" => $form->fields["text"]->getValue(),
                   ":doc_id" => $form->fields["doc_id"]->getValue(),
                   ":date" => $dt->format('Y-m-d H:i:s'),
                   ":pid" => $user->id,
            ));
  ct_log("Aktualisierung Hilfeseite " . $form->fields["doc_id"]->getValue(), 2, "-1", "help");
  header("Location: ?q=churchwiki&doc=" . $form->fields["doc_id"]->getValue());
}


function help_main() {
  return churchwiki_main();
}

/**
 * main function for churchwiki
 * @return string html content
 */
function churchwiki_main() {
  global $files_dir;
  
  drupal_add_js(ASSETS . '/js/jquery.history.js');
  
  drupal_add_css(ASSETS . '/fileuploader/fileuploader.css');
  drupal_add_js(ASSETS . '/fileuploader/fileuploader.js');
  
  drupal_add_js(ASSETS . '/tablesorter/jquery.tablesorter.min.js');
  drupal_add_js(ASSETS . '/tablesorter/jquery.tablesorter.widgets.min.js');
  
  drupal_add_js(ASSETS . '/mediaelements/mediaelement-and-player.min.js');
  drupal_add_css(ASSETS . '/mediaelements/mediaelementplayer.css');
  
  drupal_add_js(CHURCHCORE . '/cc_abstractview.js');
  drupal_add_js(CHURCHCORE . '/cc_standardview.js');
  drupal_add_js(CHURCHCORE . '/cc_maintainstandardview.js');
  
  drupal_add_js(ASSETS . '/ckeditor/ckeditor.js');
  drupal_add_js(ASSETS . '/ckeditor/lang/de.js');
  drupal_add_js(CHURCHWIKI . '/wiki_maintainview.js');
  drupal_add_js(CHURCHWIKI . '/churchwiki.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchwiki"));
  
  $doc_id = getVar("doc");
  
  $content = '
    <div class="row-fluid">
      <div class="span3 bs-docs-sidebar">
        <div class="bs-docs-sidebar" id="cdb_menu"></div>
        <div class="bs-docs-sidebar" id="sidebar"></div>
      </div>
      <div class="span9">
        <div id="cdb_navi"></div>
        <div id="cdb_content"></div>';
  if ($doc_id) $content .= '
        <input type="hidden" id="doc_id" name="doc_id" value="' . $doc_id . '"/>';
  $content .= '
      </div>
    </div>';
 
  return $content;
}

/**
 * churchwiki print view
 * @return string html content
 */
function churchwiki__printview() {
  global $files_dir;
  
  drupal_add_js(ASSETS . '/js/jquery.history.js');
  
  drupal_add_css(ASSETS . '/fileuploader/fileuploader.css');
  drupal_add_js(ASSETS . '/fileuploader/fileuploader.js');
  
  drupal_add_js(ASSETS . '/tablesorter/jquery.tablesorter.min.js');
  drupal_add_js(ASSETS . '/tablesorter/jquery.tablesorter.widgets.min.js');
  
  drupal_add_js(CHURCHCORE . '/shortcut.js');
  
  drupal_add_js(CHURCHCORE . '/cc_abstractview.js');
  drupal_add_js(CHURCHCORE . '/cc_standardview.js');
  drupal_add_js(CHURCHCORE . '/cc_maintainstandardview.js');
  
  drupal_add_js(ASSETS . '/ckeditor/ckeditor.js');
  drupal_add_js(ASSETS . '/ckeditor/lang/de.js');
  drupal_add_js(CHURCHWIKI . '/wiki_maintainview.js');
  drupal_add_js(CHURCHWIKI . '/churchwiki.js');
  
  $doc_id = "main";
  if (isset($_GET["doc"])) $doc_id = $_GET["doc"];
  
  $content = '
  <div id="cdb_content"></div>
  <input type="hidden" id="doc_id" name="doc_id" value="' . $doc_id . '"/>
  <input type="hidden" id="printview" name="doc_id" value="true"/>
  <div class="row-fluid">
    <div class="span12">' . $text . '</div>
  </div>';
  
  return $content;
}
