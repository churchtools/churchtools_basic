<?php 


function prooveEMail($form) {
  $res=churchcore_getPersonByEMail($form->fields["email"]);
  if ($res==false)
    $form->fields["email"]->setError("EMail-Adresse nicht vorhanden");
  else {
    addInfoMessage("Ab sofort wird der Benutzer ".$form->fields["email"]." simuliert. Um zum vorigen Benutzer zur&uuml;ckzukehren, bitte im Benutzer-Men&uuml; 'Simulieren beenden' anklicken.");
    _simulateUser($res);
  }      
}

function _simulateUser($res) {
  unset($_SESSION["family"]);
  $_SESSION["simulate"]=$_SESSION["user"]->id;
  if (isset($_GET["back"]))
    $_SESSION["back"]=$_GET["back"];
  $res->auth=getUserAuthorization($res->id);      
  $_SESSION["user"]=$res;  
}

function simulate_main() {
  include_once("system/includes/forms.php");
  
  if (isset($_SESSION["simulate"])) {
    $user=churchcore_getPersonById($_SESSION["simulate"]);
    $user->auth=getUserAuthorization($user->id);
    $_SESSION["user"]=$user;
    unset($_SESSION["simulate"]);
    if (isset($_SESSION["back"])) {
      header("Location: ?q=".$_SESSION["back"]);
      unset($_SESSION["back"]);
    }
    else  
      header("Location: ?q=".$_GET["link"]);
  }  
  if (isset($_GET["id"])) {
    $res=churchcore_getPersonById($_GET["id"]);
    if ($res!=false) {
      _simulateUser($res);
      header("Location: ?q=".$_GET["location"]);
      return "";
    }
  }
  $model = new CC_Model("SimulateUserForm", "prooveEmail");
  $model->setHeader("Benutzer simulieren", "Hierdurch kann ein Administrator die Berechtigungen eines anderen Benutzer testen, in dem er simuliert wird. Bitte gew&uuml;nschte E-Mail-Adresse des Benutzers eingeben:");    
  $model->addField("email","", "EMAIL","EMail-Adresse");
  $model->addButton("Simulieren","ok");
  return $model->render();
}

?>
