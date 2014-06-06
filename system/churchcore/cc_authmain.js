
$(document).ready(function() {
  churchInterface.registerView("AuthView", authView);
  churchInterface.setModulename("churchauth");
  
  // Lade alle Kennzeichentabellen
  loadAuthViewMasterData(function() {
    churchInterface.activateHistory("AuthView");
    churchInterface.sendMessageToAllViews("allDataLoaded");
  });
  
});