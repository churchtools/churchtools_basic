 (function($) {

  
// Constructor
function SettingsView() {
  StandardTableView.call(this);
  this.name="SettingsView";
}

Temp.prototype = StandardTableView.prototype;
SettingsView.prototype = new Temp();
settingsView = new SettingsView();

SettingsView.prototype.renderMenu = function() {
  this_object=this;
  menu = new CC_Menu("Men&uuml;");
  menu.addEntry("Zur&uuml;ck zum Dienstplan", "apersonview", "arrow-left");
  $("#cdb_precontent").html("");

  if (!menu.renderDiv("cdb_menu"))
    $("#cdb_menu").hide();
  else {    
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="apersonview") {
        menuDepth="amain";
        churchInterface.setCurrentView(listView, false);
      }
      return false;
    });
  }
};


SettingsView.prototype.renderFilter = function() {
  var rows = new Array();
  rows.push("<div id=\"cdb_filtercover\"></div>");
  $("#cdb_filter").html(rows.join("")); 
  
  $.each(this.filter, function(k,a) {
    $("#"+k).val(a);
  });

  // Callbacks 
  this.implantStandardFilterCallbacks(this, "cdb_filter");
};

SettingsView.prototype.renderListMenu = function() {
  var navi = new CC_Navi();
  navi.addEntry(true,"id1","Einstellungen");
  navi.renderDiv("cdb_search");
};


SettingsView.prototype.checkFilter = function(id) {
  return true;
};


SettingsView.prototype.renderList = function() {
  this_object=this;
  var rows = new Array();
  rows.push("<h2>E-Mail Erinnerungen</h2>");
  rows.push("<table>");
  rows.push("<tr><td width=\"50%\">Erinnere mich an meine zugesagten Dienste per E-Mail");
  rows.push('<td><select id="remindMe">');
    rows.push('<option value="0" '+(masterData.settings.remindMe==0?"selected":"")+'>Nein');
    rows.push('<option value="1" '+(masterData.settings.remindMe==1?"selected":"")+'>Ja');
  rows.push('</select>');
  if (masterData.settings.informInquirer!=null) {
    rows.push("<tr><td width=\"50%\">Informiere mich wenn bei meinen Anfragen einer zu- oder absagt.");
    rows.push('<td><select id="informInquirer">');
      rows.push('<option value="0" '+(masterData.settings.informInquirer==0?"selected":"")+'>Nein');
      rows.push('<option value="1" '+(masterData.settings.informInquirer==1?"selected":"")+'>Ja');
    rows.push('</select>');
  }
  if (masterData.settings.informLeader!=null) {
    rows.push("<tr><td width=\"50%\">Informiere mich w&ouml;chentlich &uuml;ber offene Dienste in meinen Gruppen");
    rows.push('<td><select id="informLeader">');
      rows.push('<option value="0" '+(masterData.settings.informLeader==0?"selected":"")+'>Nein');
      rows.push('<option value="1" '+(masterData.settings.informLeader==1?"selected":"")+'>Ja');
    rows.push('</select>');
  }
  rows.push("</table>");
  
  rows.push("<h2>Einstellungen zur Anzeige</h2>");
  rows.push("<table>");
  
  rows.push('<tr><td width="50%"><td>');
  rows.push(form_renderCheckbox({
    cssid:"listViewTableHeight", label:"Titelleiste der Tabelle fixieren",
    checked: (masterData.settings.listViewTableHeight==null) || (masterData.settings.listViewTableHeight==1)
  }));
  $.each(churchcore_sortData(masterData.servicegroup,"sortkey"), function(k,a) {
    if (masterData.auth.viewgroup[a.id]!=null) {
      rows.push('<tr><td width="50%"><td>');
      rows.push(form_renderCheckbox({
        cssid:"viewgroup"+a.id, label:a.bezeichnung+' anzeigen', controlgroup:false,
        checked: (masterData.settings["viewgroup"+a.id]==null) || (masterData.settings["viewgroup"+a.id]==1)
      }));
    }
  });  
  rows.push("</table>");
  rows.push("<h2>Abonnieren</h2>");
  rows.push("<table>");
  rows.push('<tr><td width="50%"><td>');
  rows.push('<a class="abo_ical" href="#">'+form_renderCaption({text:"Dienstplan abonnieren"})+'</a>');
  rows.push("</table>");

  
  $("#cdb_content").html(rows.join(""));
  
  $("#cdb_content select").change(function(c) {
    masterData.settings[$(this).attr("id")]=$(this).val();
    churchInterface.jsonWrite({func:"saveSetting", sub:$(this).attr("id"), val:$(this).val()});      
  });  
  $("#cdb_content input:checkbox").click(function(c) {
    masterData.settings[$(this).attr("id")]=($(this).attr("checked")=="checked"?1:0);
    churchInterface.jsonWrite({func:"saveSetting", sub:$(this).attr("id"), val:masterData.settings[$(this).attr("id")]});      
  });  
  $("#cdb_content a.abo_ical").click(function(c) {
    ical_abo();
    return false;
  });
  

};

})(jQuery);
