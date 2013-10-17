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


jQuery(document).ready(function() {
  churchInterface.registerView("PersonView", personView);
  churchInterface.registerView("ArchiveView", archiveView);
  churchInterface.registerView("GroupView", groupView);
  churchInterface.registerView("StatisticView", statisticView);
  churchInterface.registerView("SettingsView", settingsView);  
  churchInterface.registerView("MapView", mapView);
  churchInterface.registerView("MaintainView", maintainView);
  
  cdb_initializeGoogleMaps();
  // Lade alle Kennzeichentabellen
  cdb_loadMasterData(function() {
    churchInterface.setModulename(masterData.modulename);

    // Initialisiere Browser-History, ruft damit schon RenderView() auf, falles Parameter uebergeben worden sind
    churchInterface.activateHistory((masterData.settings.churchdbInitView!=null?masterData.settings.churchdbInitView:"PersonView"));
  /*
    var p=localStorage.getObject("allPersons["+masterData.user_pid+"]");
    if (p!=null) {
      console.log("Load from Cache");
      allPersons=p.data;
      churchInterface.setLastLogId(p.lastLogId);  
      // Nur noch ein Refresh der Liste ist notwendig
      churchInterface.getCurrentView().renderView();

      // Lade nun noch weitere Restdaten, refreshListNecessary legt fest, ob nochmal die Liste zu aktualisieren ist 
      cdb_loadRelations(function(refreshListNecessary) {
        cdb_loadSearch(function() {
          cdb_loadGroupMeetingStats(churchInterface.getCurrentView().filter, null, function(refreshListNecessary2) {
            masterData.allDataLoaded=true;
            churchInterface.sendMessageToAllViews("allDataLoaded", new Array(refreshListNecessary || refreshListNecessary2));
          });
        });
      });
    } 
    else */{  
      churchInterface.setLastLogId(masterData.last_log_id);
    
      cdb_loadPersonData(function() {
        // Genug Daten um die ersten 50 Personen und Meine Gruppen anzuzeigen.
        churchInterface.getCurrentView().renderView(false);
        jQuery("#searchEntry").focus();
        
        // Lade nun alle Personendaten im Hintergrund weiter
//        cdb_loadPersonData(function() {
          //var p = {data:allPersons, lastLogId:masterData.last_log_id};
          //localStorage.setObject("allPersons["+masterData.user_pid+"]",p);
          //console.log("Save Cache: "+"allPersons["+masterData.user_pid+"]");
          // Nur noch ein Refresh der Liste ist notwendig
          //churchInterface.getCurrentView().renderView(false);
  
          // Lade nun noch weitere Restdaten, refreshListNecessary legt fest, ob nochmal die Liste zu aktualisieren ist 
          cdb_loadRelations(function(refreshListNecessary) {
            cdb_loadSearch(function() {
              cdb_loadGroupMeetingStats(churchInterface.getCurrentView().filter, null, function(refreshListNecessary2) {
                masterData.allDataLoaded=true;
                churchInterface.sendMessageToAllViews("allDataLoaded", new Array(refreshListNecessary || refreshListNecessary2));
              });
            });
          });
 //       });
      },50);
    }
  });
}); 
