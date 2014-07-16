(function($) {

// Constructor
function MaintainView() {
  StandardTableView.call(this);
  this.name="MaintainView";
}

Temp.prototype = MaintainStandardView.prototype;
MaintainView.prototype = new Temp();
maintainView = new MaintainView();

MaintainView.prototype.getData = function() {
  masterData.resourcetype=masterData.resourceTypes;

  return masterData.masterDataTables;
};


MaintainView.prototype.renderMenu = function() {
  this_object=this;
  masterData.resourcetype=masterData.resTypes;
  menu = new CC_Menu(_("menu"));
  menu.addEntry(_("back.to.main.menu"), "apersonview", "arrow-left");
  menu.addEntry(_("help"), "ahelp", "question-sign");

  if (!menu.renderDiv("cdb_menu"))
    $("#cdb_menu").hide();
  else {
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="apersonview") {
        churchInterface.setCurrentView(weekView, false);
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchResource-Stammdaten");
      }
      return false;
    });
  }
};

})(jQuery);
