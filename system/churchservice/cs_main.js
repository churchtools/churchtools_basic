/**
}
 * cdb_main.js
 *
 * @Author Jens Martin Rauen
 * @Version 20110101
 */

// Alle Stammdaten werden hier gespeichert
var masterData = null;
// MOmentan f�r Abwesenheit benutzt
var allPersons = new Object();
var groups = null;
var allFacts=null;
var allSongs=null;
var allAgendas=null;
var allEvents = new Object();
var currentDate_externGesetzt=false;

//Hilfen fuer Zeitmessungen
var timers = new Array();

jQuery(document).ready(function() {
  churchInterface.setModulename("churchservice");

  // Lade alle Kennzeichentabellen
  churchInterface.loadMasterData(function() {
    masterData.service_sorted=churchcore_sortData_numeric(masterData.service,"sortkey");
    churchInterface.setLastLogId(masterData.lastLogId);

    // Initialisiere Browser-History, ruft damit schon RenderView() auf, falles Parameter uebergeben worden sind
    churchInterface.activateHistory("ListView");
    
  }, false);
});
