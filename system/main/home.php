<?php

/**
 * home main function
 *
 * @return string html content
 */
function home_main() {
  global $config, $files_dir, $mapping;
  
  if ($m = getConf("admin_message")) addErrorMessage($m);
  
  checkFilesDir();
  
  $modules = churchcore_getModulesSorted();
  
  if (isset($_SESSION["family"])) addInfoMessage(t('there.are.more.users.with.the.same.email'));
  
  // Start buttons for Desktop-View
  $txt = '
  <div class="hero-unit hidden-phone">
    <h1>' . $config["welcome"] . '</h1>
    <p class="hidden-phone">' . $config["welcome_subtext"] . '</p>
    <p>';
  
  // module buttons normal
  foreach ($modules as $m)
    if (getConf($m . "_startbutton") && user_access("view", $m)) {
      $txt .= "<a class='btn btn-large' href='?q=$m'>" . $config[$m . "_name"] . '</a>&nbsp;';
    }
  $txt .= '
    </p>
  </div>';
  
  // Start buttons for Mobile-View
  $txt .= '
  <div class="well visible-phone">
    <h1>' . t("welcome") . '!</h1>
    <p>' . $_SESSION["user"]->vorname . ', ' .
       t("chose.your.possibilities") . ':</p>
    <ul class="nav nav-pills nav-stacked">';
  
  // module buttons mobile
  foreach ($modules as $m) {
    if (getConf($m . "_name") && getConf($m . "_startbutton")=="1" && user_access("view", $m)) {
      $txt .= "<li><a class='btn btn-large' href='?q=$m'>" . $config[$m . "_name"] . '</a> ';
    }
  }
  $txt .= '</ul>' . NL;
  $txt .= '</div>' . NL;
  
  // blocks[]: label, col(1,2,3) sortkey, html
  $blocks = null;
  foreach ($modules as $m) {
    if (getConf($m . "_name")) {
      include_once (SYSTEM . '/' . $mapping[$m]);
      
      //TODO: this functions are actually only config arrays - handle them as such and put them on a place a admin may even change them.
      if (function_exists($m . "_blocks")) {
        $b = call_user_func($m . "_blocks");
        foreach ($b as $block) $blocks[$block["col"]][] = $block;
      }
    }
  }
  $txt .= '<div class="row-fluid">';
  for($i = 1; $i <= 3; $i++) {
    $txt .= '<ul class="span4">';
    if (isset($blocks[$i])) {
      churchcore_sort($blocks[$i], "sortkey"); //TODO: why not put them in the right order where they are defined?
      foreach ($blocks[$i] as $block) {
        if ($block["html"]) {
          $txt .= '<li class="ct_whitebox '.$block["class"] . '">';
          $txt .= '<label class="ct_whitebox_label">' . $block["label"] . "</label>";
          
          if ($block["help"]) {
            $txt .= '<div style="float:right;margin:-34px -12px">';
            $txt .= '<a href="http://intern.churchtools.de?q=help&doc=' . $block["help"] . '" title="' . t("open.help") .
                 '" target="_clean"><i class="icon-question-sign"></i></a>';
            $txt .= '</div>';
          }
          $txt .= $block["html"];
        }
      }
    }
    $txt .= '</ul>' . NL;
  }
  $txt .= '</div>' . NL;
  
  drupal_add_js(MAIN . '/home.js');
  
  return $txt;
}

/**
 * check if needed site directories exists and writeable
 * create them if needed
 *
 * TODO: is this related to home or need it to be tested on install only?
 */
function checkFilesDir() {
  global $files_dir;
  if (!file_exists($files_dir . "/files")) mkdir($files_dir . "/files", 0777, true);
  
  if (!is_writable($files_dir . "/files")) {
    addErrorMessage(t('directory.x.has.to.be.writable', "$files_dir/files"));
  }
  else {
    if (!file_exists($files_dir . "/files/.htaccess")) {
      $handle = fopen($files_dir . "/files/.htaccess", 'w+');
      if ($handle) {
        fwrite($handle, "Allow from all\n");
        fclose($handle);
      }
    }
    
    if (!file_exists($files_dir . "/fotos/.htaccess")) {
      $handle = fopen($files_dir . "/fotos/.htaccess", 'w+');
      if ($handle) {
        fwrite($handle, "Allow from all\n");
        fclose($handle);
      }
    }
  }
  
  if (!file_exists($files_dir . "/.htaccess")) {
    $handle = fopen($files_dir . "/.htaccess", 'w+');
    if ($handle) {
      fwrite($handle, "Deny from all\n");
      fclose($handle);
    }
  }
}

/**
 * get member list ordered by name
 *
 * @return array
 */
function home_getMemberList() {
  global $base_url, $files_dir;
  
  $status_id = getConf('churchdb_memberlist_status', '1');
  if ($status_id == "") $status_id = "-1"; //TODO: delete, should never occure for default value 1?
  $station_id = getConf('churchdb_memberlist_station', '1,2,3');
  if ($station_id == "") $station_id = "-1"; //should never occure for default value 1,2,3?
  $res = db_query('SELECT person_id, name, vorname, strasse, ort, plz, land,
                     YEAR(geburtsdatum) year, MONTH(geburtsdatum) month, DAY(geburtsdatum) day,
                     DATE_FORMAT(geburtsdatum, \'%d.%m.%Y\') geburtsdatum, DATE_FORMAT(geburtsdatum, \'%d.%m.\') geburtsdatum_compact,
                     (CASE WHEN geschlecht_no=1 THEN "' . t("mr.") . '" WHEN geschlecht_no=2 THEN "' . t("mrs.") . '" ELSE "" END) "anrede",
                     telefonprivat, telefongeschaeftlich, telefonhandy, fax, email, imageurl
                   FROM {cdb_person} p, {cdb_gemeindeperson} gp
                   WHERE gp.person_id=p.id and gp.station_id IN ('.$station_id.') AND gp.status_id in ('.$status_id.') AND archiv_yn=0
                   ORDER BY name, vorname');
  $return = array ();
  foreach ($res as $p) $return[] = $p;
  
  return $return;
}

/**
 * get member list as html
 *
 * @return string html content
 */
function home__memberlist() {
  global $base_url, $files_dir, $config;
  
  if (!user_access("view memberliste", "churchdb")) {
    addErrorMessage(t("no.permission.for", t("list.of.members")));
    return " ";
  }
  //TODO: use template
  $fields = _home__memberlist_getSettingFields()->fields;
  
  $txt = '<small><i><a class="cdb_hidden" href="?q=home/memberlist_printview" target="_clean">' . t("printview") .
       '</a></i></small>';
  if (user_access("administer settings", "churchcore")) {
    $txt .= '&nbsp; <small><i><a class="cdb_hidden" href="?q=home/memberlist_settings">' . t("admin.settings") . '</a></i></small>';
  }
  
  $txt .= '<table class="table table-condensed"><tr><th><th>' . t("salutation") . '<th>' . t("name") . '<th>' .
       t("address") . '<th>' . t("DOB") . '<th>' . t("contact.information") . '</tr><tr>';
  $link = $base_url;
  
  $res = home_getMemberList();
  foreach ($res as $m) {
    if (!$m->imageurl) $m->imageurl = "nobody.gif";
    $txt .= "<tr><td><img width=\"65px\"src=\"$base_url$files_dir/fotos/" . $m->imageurl . "\"/>";
    $txt .= '<td><div class="dontbreak">' . $m->anrede . '<br/>&nbsp;</div><td><div class="dontbreak">';
    
    if ((user_access("view", "churchdb")) && (user_access("view alldata", "churchdb"))) $txt .= "<a href='$link?q=churchdb#PersonView/searchEntry:#" .
         $m->person_id . "'>" . $m->name . ", " . $m->vorname . "</a>";
    else $txt .= $m->name . ", " . $m->vorname;
    
    $txt .= '<br/>&nbsp;</div><td><div class="dontbreak">' . $m->strasse . "<br/>" . $m->plz . " " . $m->ort . "</div>";
    
    $birthday = "";
    if ($m->geburtsdatum) {
      if ($m->year < 7000) $birthday = "$m->day.$m->month.";
      if ($m->year != 1004 && $fields["memberlist_birthday_full"]->getValue()) {
        if ($m->year < 7000) $birthday = $birthday . $m->year;
        else $birthday = $birthday . $m->year - 7000;
      }
    }
    $txt .= "<td><div class=\"dontbreak\">$birthday<br/>&nbsp;</div><td><div class=\"dontbreak\">";
    if ($fields["memberlist_telefonprivat"]->getValue() && $m->telefonprivat) $txt .= $m->telefonprivat .
         "<br/>";
    if ($fields["memberlist_telefonhandy"]->getValue() && $m->telefonhandy) $txt .= $m->telefonhandy . "<br/>";
    if (!$m->telefonprivat && !$m->telefonhandy) {
      if ($fields["memberlist_telefongeschaeftlich"]->getValue() && $m->telefongeschaeftlich) $txt .= $m->telefongeschaeftlich . "<br/>";
      if ($fields["memberlist_fax"]->getValue() && $m->fax) $txt .= $m->fax . " (Fax)<br/>";
    }
    if ($fields["memberlist_email"]->getValue() && $m->email) $txt .= '<a href="mailto:' . $m->email . '">' . $m->email . '</a><br/>';
    $txt .= "</div>";
  }
  $txt .= "</table>";
  
  return $txt;
}

/**
 * get member list as pdf
 *
 * TODO: maybe using ISO-8859-1 is not a good idea on going international
 *
 * @return string
 */
function home__memberlist_printview() {
  global $base_url, $files_dir, $config;
  // $content='<html><head><meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />';
  // drupal_add_css(BOOTSTRAP.'/css/bootstrap.min.css');
  // drupal_add_css(CHURCHDB.'/cdb_printview.css');
  // $content=$content.drupal_get_header();
  if (!user_access("view memberliste", "churchdb")) {
    addErrorMessage(t("no.permission.for", t("list.of.members")));
    return " ";
  }
  
  require_once (ASSETS . '/fpdf17/fpdf.php');
  $compact = true;
  $compact = getVar("compact");
  
  // instanziate inherited class
  $pdf = new PDF('P', 'mm', 'A4');
  $pdf->AliasNbPages();
  $pdf->AddPage();
  $pdf->SetFont('Arial', '', 9);
  $res = home_getMemberList();
  $pdf->SetLineWidth(0.4);
  $pdf->SetDrawColor(200, 200, 200);
  
  $fields = _home__memberlist_getSettingFields()->fields;
  foreach ($res as $p) {
    $pdf->Line(8, $pdf->GetY() - 1, 204, $pdf->GetY() - 1);
    $pdf->Cell(10, 10, "", 0);
    if (!$p->imageurl || !file_exists("$files_dir/fotos/$p->imageurl")) $p->imageurl = "nobody.gif";
    $pdf->Image("$files_dir/fotos/$p->imageurl", $pdf->GetX() - 10, $pdf->GetY() + 1, 9);
    $pdf->Cell(2);
    $pdf->Cell(13, 9, $p->anrede, 0, 0, 'L');
    $pdf->Cell(48, 9, utf8_decode("$p->name, $p->vorname"), 0, 0, 'L');
    $pdf->Cell(45, 9, utf8_decode("$p->strasse"), 0, 0, 'L');
    
    // TODO: second occurence of code part - whats this for?
    $birthday = "";
    if ($p->geburtsdatum != null) {
      if ($p->year < 7000) $birthday = "$p->day.$p->month.";
      if ($p->year != 1004 && $fields["memberlist_birthday_full"]->getValue()) {
        if ($p->year < 7000) $birthday = $birthday . $p->year;
        else $birthday = $birthday . $p->year - 7000;
      }
    }
    $pdf->Cell(20, 9, $birthday, 0, 0, 'L');
    
    if ($fields["memberlist_telefonprivat"]->getValue() && $p->telefonprivat) $pdf->Cell(30, 9, $p->telefonprivat, 0, 0, 'L');
    else if ($fields["memberlist_telefongeschaeftlich"]->getValue() && $p->telefongeschaeftlich) $pdf->Cell(30, 9, $p->telefongeschaeftlich, 0, 0, 'L');
    else if ($fields["memberlist_telefongeschaeftlich"]->getValue() && $p->fax) $pdf->Cell(30, 9, $p->fax . " (Fax)", 0, 0, 'L');
    else $pdf->Cell(30, 9, "", 0, 0, 'L');
    if ($fields["memberlist_telefonhandy"]->getValue() && $p->telefonhandy) $pdf->Cell(30, 9, $p->telefonhandy, 0, 0, 'L');
    
    // linebreak
    $pdf->Ln(5);
    $pdf->Cell(73);
    $pdf->Cell(48, 10, "$p->plz " . utf8_decode($p->ort), 0, 0, 'L');
    $pdf->Cell(17);
    if ($fields["memberlist_email"]->getValue() && $p->email) {
      $pdf->SetFont('Arial', '', 8);
      $pdf->Cell(30, 9, $p->email);
      $pdf->SetFont('Arial', '', 9);
    }
    $pdf->Ln(12);
  }
  $pdf->Output(t("list.of.members") . '.pdf', 'I');
}

/**
 * save settings for member list
 * TODO: should return success
 *
 * @param CTForm $form
 * @return
 */
function home__memberlist_saveSettings($form) {
  if (getVar("btn_1")!==false) {
    header("Location: ?q=home/memberlist");
    return null;
  }
  else {
    foreach ($form->fields as $key => $value) {
      db_query("INSERT INTO {cc_config} (name, value)
                VALUES (:name,:value) ON DUPLICATE KEY UPDATE value=:value",
                array (":name" => $key, ":value" => $value));
    }
    loadDBConfig();
  }
}

function _home__memberlist_getSettingFields() {
  global $config;
  
  $form = new CTForm("AdminForm", "home__memberlist_saveSettings");
  $form->setHeader(t('preferences.for.memberlist'), t('admin.could.change.preferences.here'));

  // TODO: use checkboxes with status texts
  $form->addField("churchdb_memberlist_status", "", "INPUT_REQUIRED", t('status.ids.for.birthdaylist.comma.separated'))
    ->setValue(getConf("churchdb_memberlist_status"));
  
  // TODO: use checkboxes with status texts
  $form->addField("churchdb_memberlist_station", "", "INPUT_REQUIRED", t('station.ids.for.birthdaylist.comma.separated'))
    ->setValue(getConf("churchdb_memberlist_station"));
  
  $form->addField("memberlist_telefonprivat", "", "CHECKBOX", t('show.fon.number'))
    ->setValue(getConf("memberlist_telefonprivat", true));
  
  $form->addField("memberlist_telefongeschaeftlich", "", "CHECKBOX", t('show.business.fon.number'))
    ->setValue(getConf("memberlist_telefongeschaeftlich", true));
  
  $form->addField("memberlist_telefonhandy", "", "CHECKBOX", t('show.mobile.number'))
    ->setValue(getConf("memberlist_telefonhandy", true));
  
  $form->addField("memberlist_fax", "", "CHECKBOX", t('show.fax.number'))
    ->setValue(getConf("memberlist_fax", true));
  
  $form->addField("memberlist_email", "", "CHECKBOX", t('show.email'))
    ->setValue(getConf("memberlist_email", true));
  
  $form->addField("memberlist_birthday_full", "", "CHECKBOX", t('show.complete.birthday.including.year'))
    ->setValue(getConf("memberlist_birthday_full", false));
  
  return $form;
}

/**
 *
 * @return string html content of form
 */
function home__memberlist_settings() {
  $form = _home__memberlist_getSettingFields();
  $form->addButton(t('save'), "ok");
  $form->addButton(t('back'), "arrow-left");
  
  return $form->render();
}

function home__ajax() {
  $module = new CTHomeModule("home");
  $ajax = new CTAjaxHandler($module);
  
  drupal_json_output($ajax->call());
}
