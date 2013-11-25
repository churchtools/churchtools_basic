/** 
}
 * cdb_main.js
 * 
 * @Author Jens Martin Rauen
 * @Version 20110101
 */

// Alle Stammdaten werden hier gespeichert
var masterData = null;
// MOmentan fŸr Abwesenheit benutzt 
var allPersons = new Object();
var groups = null;
var allFacts=null;
var allSongs=null;
var allEvents = new Object();

//Hilfen fuer Zeitmessungen
var timers = new Array();

jQuery(document).ready(function() {
  churchInterface.setModulename("churchservice");
  churchInterface.registerView("ListView", listView);
  churchInterface.registerView("CalView", calView);
  churchInterface.registerView("FactView", factView);
  churchInterface.registerView("AgendaView", agendaView);
  churchInterface.registerView("SongView", songView);
  //churchInterface.registerView("TestView", testView);
  churchInterface.registerView("MaintainView", maintainView);
  churchInterface.registerView("SettingsView", settingsView);

  // Lade alle Kennzeichentabellen
  cdb_loadMasterData(function() {
    var currentDate_externGesetzt=false;
    if (jQuery("#currentdate").val()!=null) {
      listView.currentDate=jQuery("#currentdate").val().toDateEn(); 
      currentDate_externGesetzt=true;
    }
    churchInterface.setLastLogId(masterData.lastLogId);
    
    // Initialisiere Browser-History, ruft damit schon RenderView() auf, falles Parameter uebergeben worden sind
    churchInterface.activateHistory("ListView");

    // Lade nun Event-Data
    cs_loadEventData(null, function() {
      if (!currentDate_externGesetzt) {
        if ($("#externevent_id").val()!=null) {          
          churchInterface.getCurrentView().currentDate=allEvents[$("#externevent_id").val()].startdate.toDateEn(false);
          churchInterface.getCurrentView().filter["searchEntry"]="#"+$("#externevent_id").val();
          churchInterface.getCurrentView().renderFilter();
          churchInterface.getCurrentView().renderCalendar();
        }
        else {
          var now = new Date(); now.addDays(-1);
          var first = new Date(); first.addDays(1000);
          var doit = false;        
          $.each(allEvents, function(k,a) {
            var d = a.startdate.toDateEn(false);
            if ((d>=now) && (d<first)) {
              first = d;
              doit = true;
            }
          });
          if (doit) listView.currentDate=first;
        }
      }
      
      cs_loadPersonDataFromCdb(function() {
        // Genug Daten um nun die Anwendung zu zeigen. 
        // new: 27.1.13: RenderList reicht, denn Filter Šndert sich nix. 
        churchInterface.getCurrentView().renderList();
        churchInterface.sendMessageToAllViews("allDataLoaded");
        
        // Lade nun alle Personendaten im Hintergrund weiter
        window.setTimeout(function() {
          cs_loadAbsent(function() {
            cs_loadFiles(function() {
            });
          });
        },10);
      });
    }, false);    
  }, false);
}); 
