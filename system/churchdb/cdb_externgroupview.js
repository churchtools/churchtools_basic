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

function editGroup(a) {
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
  
  if (g.offen_yn==1) {
    form.addHtml('<legend>Anmeldung zu '+g.bezeichnung+'</legend>');
    form.addHtml('<p><div class="well">'+title+'</div>');
    form.addInput({label:"Vorname",required:true});
    form.addInput({label:"Nachname",required:true});
    form.addInput({label:"E-Mail-Adresse",required:true});
    form.addInput({label:"Telefon"});
    form.addTextarea({label:"Kommentar", rows:3});
    form.addHidden({name:"g_id", value:a});
    form.addHtml('<p><small>Die Daten werden f&uuml;r interne Zwecke gespeichert. <br/>Wenn Vorname, Name und E-Mail dem System bekannt sind, wird die Teilnahme automatisch beantragt. Andernfalls wird der Leiter per E-Mail informiert.</small>');
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
    form.addHtml('<legend>'+g.bezeichnung+'</legend>');
    form.addHtml('<p><div class="well">'+title+'</div>');
    var elem = form_showDialog("Infos zur Gruppe", form.render(null, "horizontal"), 500, 350, {
       "Schliessen": function() {
         elem.dialog("close");
       }
    });
  } 

}

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
      editGroup(a);
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
    var rows = new Array();
    
    if ($("#g_id").val()!=null) {
      var gid=$("#g_id").val();
      if (masterData.groups==null || masterData.groups[gid]==null) {
        rows.push('<div class="alert alert-error">Gruppe '+gid+" muss &ouml;ffentlich sein und darf nicht versteckt sein!");
      }
      else {
        // Logged in
        if (masterData.user_pid>0 && masterData.groups[gid].offen_yn==1) {
          if (masterData.groups[gid].status_no!=null && masterData.groups[gid].status_no!=-1) {
            rows.push('<div class="alert alert-error">'+masterData.vorname+", Du bist bereits f&uuml;r <i>"+masterData.groups[gid].bezeichnung+"</i> angemeldet!</div>");
          }
          else {
            var func="addPersonGroupRelation";
            if (masterData.groups[gid].status_no==-1) 
              func="editPersonGroupRelation";
            churchInterface.jsendWrite({func:func, g_id:gid}, function(ok, data) {
              var rows = new Array();
              if (ok) {
                rows.push('<div class="alert alert-info">Hallo '+masterData.vorname+", <br>");
                rows.push('Deine Anmeldung f&uuml;r <i>'+masterData.groups[gid].bezeichnung+'</i> wurde aufgenommen.<br/>Vielen Dank!</div>');                
              }
              else 
                rows.push('<div class="alert alert-error">Fehler aufgetreten:'+data+"</div>");
              $("#cdb_content").html(rows.join(""));
            });
          }
        }
        else {
          editGroup(gid);        
        }
      }
      $("#cdb_content").html(rows.join(""));
    }
    else {
      start();
    }

  });
}); 
