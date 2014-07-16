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
  return masterData.masterDataTables;
};

MaintainView.prototype.renderMenu = function() {
  this_object=this;
  menu = new CC_Menu(_("menu"));
  menu.addEntry(_("back.to.main.menu"), "apersonview", "arrow-left");
  menu.addEntry(_("help"), "ahelp", "question-sign");
  
  $("#sidebar").html("");

  var navi = new CC_Navi();
  navi.addEntry(true,"alistview",_("list.of.masterdata"));
  navi.renderDiv("cdb_navi", churchcore_handyformat());
  
  
  if (!menu.renderDiv("cdb_menu"))
    $("#cdb_menu").hide();
  else {
    $("#cdb_precontent").html("");

    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="apersonview") {
        menuDepth="amain";
        churchInterface.setCurrentView(wikiView, false);
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=churchwiki&doc=ChurchWiki-Stammdaten");
      }
      return false;
    });
  }
};


})(jQuery);
