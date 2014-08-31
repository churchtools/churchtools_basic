<?php 


function prooveEMail($form) {
  $res=churchcore_getPersonByEMail($form->fields["email"]);
  if ($res==false)
    $form->fields["email"]->setError(t("email.not.found"));
  else {
    addInfoMessage(t("now.will.be.simulated", $form->fields["email"]));
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
  $model = new CTForm("SimulateUserForm", "prooveEmail");
  $model->setHeader("Benutzer simulieren", t("simulate.information.text")." ".t("please.enter.valid.email").":");    
  $model->addField("email","", "EMAIL","EMail");
  $model->addButton("Simulieren","ok");
  return $model->render();
}

?>
