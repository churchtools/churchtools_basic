<?php

/**
 * 
 * @param CTForm $form
 */
function prooveEMail($form) {
  $res = churchcore_getPersonByEMail($form->fields["email"]);
  if (!$res) $form->fields["email"]->setError(t("email.not.found"));
  else {
    addInfoMessage(t("now.user.x.will.be.simulated", $form->fields["email"]));
    _simulateUser($res);
  }
}

function _simulateUser($res) {
  unset($_SESSION["family"]);
  $_SESSION["simulate"] = $_SESSION["user"]->id;
  if (isset($_GET["back"])) $_SESSION["back"] = $_GET["back"];
  $res->auth = getUserAuthorization($res->id);
  $_SESSION["user"] = $res;
}

function simulate_main() {
  if (isset($_SESSION["simulate"])) {
    // End simulation
    $user = churchcore_getPersonById($_SESSION["simulate"]);
    $user->auth = getUserAuthorization($user->id);
    $_SESSION["user"] = $user;
    unset($_SESSION["simulate"]);
    if (isset($_SESSION["back"])) {
      header("Location: ?q=" . $_SESSION["back"]);
      unset($_SESSION["back"]);
    }
    else
      header("Location: ?q=" . $_GET["link"]);
  }
  if (isset($_GET["id"])) {
    $res = churchcore_getPersonById($_GET["id"]);
    if ($res) {
      _simulateUser($res);
      header("Location: ?q=" . $_GET["location"]);
      return "";
    }
  }
  
  $form = new CTForm("SimulateUserForm", "prooveEmail");
  $form->setHeader(t('simulate.user'), t("simulate.information.text") . " " . t("please.enter.valid.email") . ":");
  $form->addField("email", "", "EMAIL", "EMail");
  $form->addButton(t('simulate.user'), "ok");
  return $form->render();
}
