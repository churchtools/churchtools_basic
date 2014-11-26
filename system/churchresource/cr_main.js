/**
}
 * cr_main.js
 *
 * @Author Jens Martin Rauen
 * @Version 20110101
 */

jQuery(document).ready(function() {
  churchInterface.setModulename("churchresource");

  // Lade alle Kennzeichentabellen
  churchInterface.loadMasterData(function() {
    churchInterface.setLastLogId(masterData.lastLogId);

    // Initialisiere Browser-History, ruft damit schon RenderView() auf, falles Parameter uebergeben worden sind
    churchInterface.activateHistory("WeekView");

/*    // Lade nun Event-Data
    cr_loadBookings(function() {
      churchInterface.sendMessageToAllViews("allDataLoaded");
    });*/
  });
});
