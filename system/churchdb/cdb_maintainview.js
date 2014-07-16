(function($) {
	 
// Constructor
function MaintainView() {
  MaintainStandardView.call(this);
  this.name="MaintainView";
}

Temp.prototype = MaintainStandardView.prototype;
MaintainView.prototype = new Temp();
maintainView = new MaintainView();

MaintainView.prototype.getData = function() {
  masterData.gruppentyp=masterData.groupTypes;
  masterData.followup_typ=masterData.followupTypes;
  masterData.gruppe=masterData.groups;

  return masterData.masterDataTables;
};

MaintainView.prototype.renderMenu = function() {
  this_object=this;
  masterData.gruppentyp=masterData.groupTypes;
  masterData.followup_typ=masterData.followupTypes;
  menu = new CC_Menu(_("menu"));
  menu.addEntry(_("back.to.main.menu"), "apersonview", "arrow-left");
  menu.addEntry(_("help"), "ahelp", "question-sign");
  
  if (!menu.renderDiv("cdb_menu"))
    $("#cdb_menu").hide();
  else {
    $("#cdb_precontent").html("");

    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="apersonview") {
        menuDepth="amain";
        churchInterface.setCurrentView(personView);
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchDB-Stammdaten");
      }
      return false;
    });
  }
};

MaintainView.prototype.editAuth = function(id, masterData_type_id) {
  var data=this.getData()[masterData_type_id];
  personView.editDomainAuth(id, data.shortname, function(id) {
    cdb_loadMasterData(function() {
      churchInterface.getCurrentView().renderView();
    });        
  });      
  return false;  
};

MaintainView.prototype.addFurtherListCallbacks = function() {
  $("#detail a").click(function (a) {
    if ($(this).attr("id").indexOf("filterDistrikt")==0) {
      groupView.clearFilter();
      groupView.setFilter("filterDistrikt",$(this).attr("id").substr(14,99));
      groupView.renderView();
      return false;
    }
    else if ($(this).attr("id").indexOf("filterGruppentyp")==0) {
      groupView.clearFilter();
      groupView.setFilter("filterGruppentyp",$(this).attr("id").substr(16,99));
      groupView.renderView();
      return false;
    }
  });
};

})(jQuery);
