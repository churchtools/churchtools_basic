(function($) {

// Constructor
function MaintainView() {
  StandardTableView.call(this);
  this.name="MaintainView";
}

Temp.prototype = MaintainStandardView.prototype;
MaintainView.prototype = new Temp();
maintainView = new MaintainView();

MaintainView.prototype.renderMenu = function() {
  this_object=this;
  
  if (masterData.cdb_gruppen==null) {
    var elem = form_showCancelDialog("Masterdaten werden geladen...","Bitte warten..");
    churchInterface.jsonRead({func:"getChurchDBMasterData" }, function(json) {
      $.each(json, function(k,a) {
        masterData[k]=a;
      });
      elem.dialog("close");
      this_object.renderList();
    });
  }
  
  menu = new CC_Menu("Men&uuml;");
  menu.addEntry("Zur&uuml;ck zur Liste", "apersonview", "arrow-left");
  menu.addEntry("Hilfe", "ahelp", "question-sign");

  if (!menu.renderDiv("cdb_menu"))
    $("#cdb_menu").hide();
  else {
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="apersonview") {
        churchInterface.setCurrentView(listView, false);
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchService-Stammdaten");
      }
      return false;
    });
  }  
};

})(jQuery);
