<?php

/**
 * TODO: set some password requirements - setting something like aa as password shouldnt be allowed.
 * TODO: On changing password i will be asked which user i want to change - cmsid or email (of the same user!)
 * 
 * @param CTForm $form
 * @return bool
 */
function prooveOldPassword($form) {
  if ($form->fields["newpassword1"]->getValue() != $form->fields["newpassword2"]->getValue()) {
    $form->fields["newpassword1"]->setError(" ");
    $form->fields["newpassword2"]->setError(t("password.does.not.match.the.previous"));
    
    return false;
  }
  if (isset($form->fields["password"]) && $form->fields["newpassword1"]->getValue() == $form->fields["password"]->getValue()) {
    $form->fields["newpassword1"]->setError(t("please.take.new.password"));
    
    return false;
  }
  
  $res = db_query("SELECT * FROM {cdb_person} 
                   WHERE id=:id", 
                   array (":id" => $_SESSION["user"]->id));
  $ret = $res->fetch();
  if (isset($form->fields["password"]) && !user_check_password($form->fields["password"], $ret)) {
    $form->fields["password"]->setError(t("password.is.incorrect"));
  }
  else {
    $scrambled_password = scramble_password($form->fields["newpassword1"]->getValue());
    $res = db_query("UPDATE {cdb_person} SET password=:password 
                     WHERE id=:id", 
                     array (":id" => $_SESSION["user"]->id, 
                            ":password" => $scrambled_password,
                     ));
    $oldpwd = $_SESSION["user"]->password;
    addInfoMessage(t("password.changes.successfully"));
    
    // There is no old password? Then the person logged in with a loginstr and now has to be forwarded to home
    if ($oldpwd == null) header("Location: ?q=home");
  }
}

function profile_main() {
  $form = new CTForm("PasswortChangeForm", "prooveOldPassword");
  if ($_SESSION["user"]->password != null) {
    $form->setHeader(t("change.password"), t("to.change.password.complete.following.fields"));
    $form->addField("password", "", "PASSWORD", t("old.password"));
    $form->addButton(t("change.password"), "ok");
  }
  else {
    $form->setHeader(t("welcome"), t("to.login.later.set.own.password"));
    $form->addButton(t("set.password"), "ok");
  }
  $form->addField("newpassword1", "", "PASSWORD", t("new.password"));
  $form->addField("newpassword2", "", "PASSWORD", t("repeat.new.password"));
  
  return $form->render();
}
