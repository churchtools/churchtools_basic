/** 
}
 * cdb_main.js
 * 
 * @Author Jens Martin Rauen
 * @Version 20110101
 */

var churchdb_js_version = "7.x-1.97";

// Alle Stammdaten werden hier gespeichert
var masterData = null;

// Alle Personendaten werden gespeichert, allerdings nur die Namen etc. Erst nach Klick auf Detail  
// werden weitere Daten nachgeladen und hier angereichert.
var allPersons = new Object();

// Infos ueber Teilnahme an Gruppen
var groupMeetingStats = null;
    

// Aktuelle Menutiefe, amain ist der Starteinstieg.
var menuDepth="amain";

// Hilfen fuer Zeitmessungen
var timers = new Array();


function start() {
  if (geocoder) {  
    var image = new google.maps.MarkerImage("http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png",
        new google.maps.Size(32, 32),
        new google.maps.Point(0, 0),
        new google.maps.Point(8, 16),
        new google.maps.Size(16, 16)
        );

    var latlng = new google.maps.LatLng(masterData.home_lat, masterData.home_lng);
    map=cdb_prepareMap("cdb_content",latlng);

    if (!masterData.auth.viewalldata)
      _cdb_limitZoom(map);

    cdb_addGroupsToMap(map, null, null, function(a) {
      var g=masterData.groups[a];
      var form = new CC_Form();
      var title="<h4>Infos zur Gruppe "+g.bezeichnung+'</h4>';
      title=title+"<small><ul>";
      if (g.distrikt_id!=null)
        title=title+"<li>Distrikt "+masterData.districts[g.distrikt_id].bezeichnung;
      if (g.treffzeit!="") title=title+"<li>Zeit des Treffens: "+g.treffzeit;
      if ((g.treffpunkt!=null) && (g.treffpunkt!="")) title=title+", Ort: "+g.treffpunkt;
      if (g.treffname!="") title=title+"<li>Treffen bei: "+g.treffname;
      if (g.zielgruppe!="") title=title+"<li>Zielgruppe: "+g.zielgruppe;        
      title=title+"</ul></small>";
      form.addHtml('<p><div class="well">'+title+'</div>');
      
      if (g.offen_yn==1) {
        form.addHtml('<legend>Nehme hier gerne Kontakt auf!</legend>');
        form.addInput({label:"Vorname",required:true});
        form.addInput({label:"Nachname",required:true});
        form.addInput({label:"E-Mail-Adresse",required:true});
        form.addInput({label:"Telefon"});
        form.addTextarea({label:"Kommentar", rows:3});
        form.addCaption({text:"<p><small>Die Daten werden f&uuml;r interne Zwecke gespeichert.</small>"})
        form.addHidden({name:"g_id", value:a});
        var elem = form_showDialog("Gruppe anfragen", form.render(null, "horizontal"), 500, 610, {
          "Absenden": function() {
             var obj=form.getAllValsAsObject();
             obj.func="sendEMail";
  
             churchInterface.jsendWrite(obj, function(ok, data) {
               if (ok) {
                 elem.dialog("close");
                 alert(data);             
               }
               else {
                 alert("Fehler: "+data);
               }
             });
           },
           "Abbruch": function() {
             elem.dialog("close");
           }
        });
      }
      else {
        var elem = form_showDialog("Infos zur Gruppe", form.render(null, "horizontal"), 500, 350, {
           "Schliessen": function() {
             elem.dialog("close");
           }
        });
      } 
    });
  }
  
}

jQuery(document).ready(function() {
//  churchInterface.registerView("MapView", mapView);
  churchInterface.setModulename("externmapview");
  
  cdb_initializeGoogleMaps();
  // Lade alle Kennzeichentabellen
  churchInterface.jsendWrite({func:"loadMasterData"}, function(status, data) {
    masterData=data;
    masterData.auth=new Object();
    masterData.settings=new Object();
   
    // Initialisiere Browser-History, ruft damit schon RenderView() auf, falles Parameter uebergeben worden sind
    //churchInterface.activateHistory("PersonView");
    $("#cdb_content").html();
    start();

  });
}); 
