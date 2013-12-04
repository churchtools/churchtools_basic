<?php 


function prooveOldPassword($form) {
  if ($form->fields["newpassword1"]->getValue()!=$form->fields["newpassword2"]->getValue()) {
    $form->fields["newpassword1"]->setError(" ");
    $form->fields["newpassword2"]->setError(t("password.does.not.match.the.previous"));
    return false;
  } 
  if ((isset($form->fields["password"])) && ($form->fields["newpassword1"]->getValue()==$form->fields["password"]->getValue())) {
    $form->fields["newpassword1"]->setError(t("please.take.new.password"));
    return false;
  }

  
  
  $res=db_query("select * from {cdb_person} where id=:id", 
     array(":id"=>$_SESSION["user"]->id));
  $ret=$res->fetch();
  if ((isset($form->fields["password"])) && (!user_check_password($form->fields["password"],$ret))) {
    $form->fields["password"]->setError(t("password.is.incorrect"));    
  } 
  else {
    $scrambled_password=scramble_password($form->fields["newpassword1"]->getValue());
    $res=db_query("update {cdb_person} set password=:password where id=:id", 
       array(":id"=>$_SESSION["user"]->id, ":password"=>$scrambled_password));
    $oldpwd=$_SESSION["user"]->password;
    addInfoMessage(t("password.changes.successfully"));
    
    // There is no old password? Then the person logged in with a loginstr and now has to be forwarded to home 
    if ($oldpwd==null)
      header("Location: ?q=home");
  }
}

function profile_main() {
  include_once("system/includes/forms.php");

  $model = new CC_Model("PasswortChangeForm", "prooveOldPassword");
  if ($_SESSION["user"]->password!=null) { 
    $model->setHeader(t("change.password"), t("to.change.password.complete.following.fields"));
    $model->addField("password","", "PASSWORD",t("old.password"));
    $model->addButton(t("change.password"),"ok");
  }
  else {
    $model->setHeader(t("welcome"), t("to.login.later.set.own.password"));
    $model->addButton(t("set.password"),"ok");
  }  
  $model->addField("newpassword1","", "PASSWORD",t("new.password"));
  $model->addField("newpassword2","", "PASSWORD",t("repeat.new.password"));
  return $model->render();
  
}

?>
