/** 
}
 * cr_main.js
 * 
 * @Author Jens Martin Rauen
 * @Version 20110101
 */

// Alle Stammdaten werden hier gespeichert
var masterData = null;

var allBookings = new Object();

//Hilfen fuer Zeitmessungen
var timers = new Array();

jQuery(document).ready(function() {
  churchInterface.registerView("WeekView", weekView);
  churchInterface.registerView("MaintainView", maintainView);
  
  // Lade alle Kennzeichentabellen
  cdb_loadMasterData(function() {
    churchInterface.setModulename(masterData.modulename);
    churchInterface.setLastLogId(masterData.lastLogId);
    
    // Initialisiere Browser-History, ruft damit schon RenderView() auf, falles Parameter uebergeben worden sind
    churchInterface.activateHistory("WeekView");

    // Lade nun Event-Data
    cr_loadBookings(function() {
      churchInterface.sendMessageToAllViews("allDataLoaded");
    });    
  });
}); 
