
$(document).ready(function() {
  churchInterface.registerView("AuthView", authView);
  churchInterface.setModulename("churchauth");
  
  // load all Master Data tables
  loadAuthViewMasterData(function() {
    churchInterface.activateHistory("AuthView");
    churchInterface.sendMessageToAllViews("allDataLoaded");
  });
  
});