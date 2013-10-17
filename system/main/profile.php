<?php 


function prooveOldPassword($form) {
  if ($form->fields["newpassword1"]->getValue()!=$form->fields["newpassword2"]->getValue()) {
    $form->fields["newpassword1"]->setError(" ");
    $form->fields["newpassword2"]->setError("Das Passwort stimmt nicht mit dem vorigen &uuml;berein!");
    return false;
  } 
  if ((isset($form->fields["password"])) && ($form->fields["newpassword1"]->getValue()==$form->fields["password"]->getValue())) {
    $form->fields["newpassword1"]->setError("Als Passwort wurde das alte genommen. Bitte ein neues Passwort setzen!");
    return false;
  }

  
  
  $res=db_query("select * from {cdb_person} where id=:id", 
     array(":id"=>$_SESSION["user"]->id));
  $ret=$res->fetch();
  if ((isset($form->fields["password"])) && (!user_check_password($form->fields["password"],$ret))) {
    $form->fields["password"]->setError("Das Passwort stimmt nicht.");    
  } 
  else {
    $res=db_query("update {cdb_person} set password=:password where id=:id", 
       array(":id"=>$_SESSION["user"]->id, ":password"=>md5($form->fields["newpassword1"]->getValue())));
    $oldpwd=$_SESSION["user"]->password;
    addInfoMessage("Passwort wurde erfolgreich ge&auml;ndert");
    // Wenn es Ÿber ein Loginstr war, dann leite nun auf die Startseite weiter, es scheint ein neuer Benutzer zu sein! 
    if ($oldpwd==null)
      header("Location: ?q=home");
  }
}

function profile_main() {
  include_once("system/includes/forms.php");

  $model = new CC_Model("PasswortChangeForm", "prooveOldPassword");
  if ($_SESSION["user"]->password!=null) { 
    $model->setHeader("Passwort &auml;ndern", "Zum Anpassen des Passwortes bitte die folgenden Felder ausf&uuml;llen:");
    $model->addField("password","", "PASSWORD","Altes Passwort");
    $model->addButton("Passwort &auml;ndern","ok");
  }
  else {
    $model->setHeader("Herzlich willkommen!", "Damit Du Dich zum sp&auml;teren Zeitpunkt wieder anmelden kannst, bitte nun ein sicheres Passwort w&auml;hlen und in beide Felder eintragen:");
    $model->addButton("Passwort festlegen","ok");
  }  
  $model->addField("newpassword1","", "PASSWORD","Neues Passwort");
  $model->addField("newpassword2","", "PASSWORD","Best&auml;tigung des neuen Passwortes");
  return $model->render();
  
}

?>
