 (function($) {

  
// Constructor
function SettingsView() {
  StandardTableView.call(this);
  this.name="SettingsView";
}

Temp.prototype = StandardTableView.prototype;
SettingsView.prototype = new Temp();
settingsView = new SettingsView();

SettingsView.prototype.getData = function() {
  return allPersons;
};

SettingsView.prototype.renderMenu = function() {
  this_object=this;
  menu = new CC_Menu("Men&uuml;");
  menu.addEntry("Zur&uuml;ck zur Personenliste", "apersonview", "arrow-left");
  $("#cdb_precontent").html("");
  $("#cdb_group").html("");
  $("#cdb_todos").html("");

  if (!menu.renderDiv("cdb_menu"))
    $("#cdb_menu").hide();
  else {    
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="apersonview") {
        menuDepth="amain";
        churchInterface.setCurrentView(personView, true);
      }
      return false;
    });
  }
};

SettingsView.prototype.renderListMenu = function() {
  var navi = new CC_Navi();
  navi.addEntry(true,"id1","Einstellungen");
  navi.renderDiv("cdb_search");
};

SettingsView.prototype.renderFilter = function() {
  var rows = new Array();

  $("#cdb_filter").html(rows.join("")); 
  
  $.each(this.filter, function(k,a) {
    $("#"+k).val(a);
  });


  // Callbacks 
  this.implantStandardFilterCallbacks(this, "cdb_filter");
};


SettingsView.prototype.checkFilter = function(id) {
  return true;
};


SettingsView.prototype.renderList = function() {
  this_object=this;
  var rows = new Array();
  rows.push("<h2>E-Mailer</h2>");
  rows.push("<table>");
  rows.push("<tr><td width=\"50%\">Welche Mailer-Variante soll verwendet werden: ");
  rows.push('<td><select id="mailerType">');
    rows.push('<option value="0" '+(masterData.settings.mailerType==0?"selected":"")+'>Direkter Aufruf des lokalen E-Mail-Programms (Begrenzung bei Windows!)');
    rows.push('<option value="1" '+(masterData.settings.mailerType==1?"selected":"")+'>Export der E-Mail-Addressen in ein neues Fenster');
    rows.push('<option value="2" '+(masterData.settings.mailerType==2?"selected":"")+'>E-Mail-Versand direkt &uuml;ber ChurchTools (inkl. Serienbrieffelder!)');
  rows.push('</select>');
  rows.push("<tr><td width=\"50%\">Welches Trennzeichen soll verwendet werden: ");
  rows.push('<td><select id="mailerSeparator">');
    rows.push('<option value="0" '+(masterData.settings.mailerSeparator==0?"selected":"")+'>Semikolon (bei Windows-PCs sinnvoll)');
    rows.push('<option value="1" '+(masterData.settings.mailerSeparator==1?"selected":"")+'>Komma (bei z.Bsp Mac oder GMail sinnvoll)');
  rows.push('</select>');

  // Kann nur BCC senden, wenn ich auch die E-Mailadresse vom Sender habe, damit ich diese an An setzen kann
  if ((masterData.user_pid!=null) && (allPersons[masterData.user_pid]!=null)) {
    rows.push("<tr><td width=\"50%\">Welches Feld soll bei Mailer mit direktem Aufruf verwendet werden: ");
    rows.push('<td><select id="mailerBcc">');
      if (masterData.settings.mailerBcc==null)
        masterData.settings.mailerBcc=0;
      rows.push('<option value="0" '+(masterData.settings.mailerBcc==0?"selected":"")+'>An');
      rows.push('<option value="1" '+(masterData.settings.mailerBcc==1?"selected":"")+'>Bcc');
    rows.push('</select>');
  }
  
  
  rows.push('</table>');

  
  var showFU=false;
  if ((masterData.user_pid!=null) && (allPersons[masterData.user_pid]!=null) && (allPersons[masterData.user_pid].gruppe!=null)) {
    $.each(allPersons[masterData.user_pid].gruppe, function(k,a) {
      if ((a.leiter>0) && (masterData.groups[a.id].followup_typ_id!=null) && (masterData.groups[a.id].followup_typ_id!=0))
        showFU=true;
    });    
  }
  if (showFU) {
    rows.push("<h2>Follow-Ups</h2><table>")
    rows.push("<tr><td width=\"50%\">Umgang mit &uuml;berf&auml;lligen FollowUps: ");
    rows.push('<td><select id="automaticActivateFollowupOverdue">');
    if (masterData.settings.automaticActivateFollowupOverdue==null)
      masterData.settings.automaticActivateFollowupOverdue=1;
      rows.push('<option value="0" '+(masterData.settings.automaticActivateFollowupOverdue==0?"selected":"")+'>Filter nicht automatisch aktivieren');
      rows.push('<option value="1" '+(masterData.settings.automaticActivateFollowupOverdue==1?"selected":"")+'>Filter automatisch aktivieren');
    rows.push('</select></table>');
  }  
  
  rows.push("<h2>Personenliste</h2><table>")
  rows.push("<tr><td width=\"50%\">Zus&auml;tzliche Layer bei der Anzeige von Google Karten: ");
  rows.push('<td><select id="googleMapLayer">');
  if (masterData.settings.googleMapLayer==null)
    masterData.settings.googleMapLayer=0;
    rows.push('<option value="0" '+(masterData.settings.googleMapLayer==0?"selected":"")+'>Keine weiteren Layer');
    rows.push('<option value="1" '+(masterData.settings.googleMapLayer==1?"selected":"")+'>Verkehrsdichte anzeigen');
    rows.push('<option value="2" '+(masterData.settings.googleMapLayer==2?"selected":"")+'>Fahrradwege anzeigen');
    rows.push('<option value="3" '+(masterData.settings.googleMapLayer==3?"selected":"")+'>Verkehrsnetzplan (Transit) anzeigen');
  rows.push('</select>');

  if (masterData.auth.viewalldata) {  
    rows.push("<tr><td width=\"50%\">Personen mit folgendem Status sollen ausgeblendet werden: ");
    rows.push('<td><select id="hideStatus">');
    if (masterData.settings.hideStatus==null)
      masterData.settings.hideStatus=-1;
    rows.push('<option value="-1">');    
    $.each(masterData.status, function (k,a) {
      rows.push('<option value="'+a.id+'" '+(masterData.settings.hideStatus==a.id?"selected":"")+'>'+a.bezeichnung);    
    });
    rows.push('</select>');
  }

  rows.push("</table>");
  
  
  
  
  rows.push("<h2>Signatur</h2>");
  rows.push('<div id="editor">');
  if (masterData.settings.signature!=null)
    rows.push(masterData.settings.signature);
  rows.push("</div><br/><p>");  
  rows.push(form_renderButton({label:"Signatur speichern", cssid:"savesignature"}));  
    
  $("#cdb_content").html(rows.join(""));
  form_implantWysiwygEditor("editor", false);
  $("#savesignature").click(function(k) {
    masterData.settings["signature"]=CKEDITOR.instances.editor.getData();
    churchInterface.jsonWrite({func:"saveSetting", sub:"signature", val:CKEDITOR.instances.editor.getData()});          
  });
  
  $("#cdb_content select").change(function(c) {
    masterData.settings[$(this).attr("id")]=$(this).val();
    churchInterface.jsonWrite({func:"saveSetting", sub:$(this).attr("id"), val:$(this).val()});      
  });  

};

})(jQuery);
