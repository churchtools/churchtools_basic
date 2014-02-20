/**
 * Stellt eine Schnittstelle zwischen Ajax-PHP-Service und dem JavaScript im Client zur Verfügung 
 */

function ChurchInterface() {
  this.modulename="churchcore";
  this.standardviewname="main";
  this.views=new Object();
  this.currentHistoryArray=null;
  this.currentView=null;
  this.allDataLoaded=false;
  this.lastLogId=null;
  this.hideStatusTimer=null;
  this.errorWindow=null;
  this.fatalErrorOccured=false;
}

var churchInterface = new ChurchInterface();

ChurchInterface.prototype.setModulename = function (modulename) {
  this.modulename=modulename;
};
ChurchInterface.prototype.setLastLogId= function (lastLogId) {
  this.lastLogId=lastLogId;
};
ChurchInterface.prototype.getLastLogId= function () {
  return this.lastLogId;
};

/**
 * 
 * @param message: Entweder: "allDataLoaded" oder "filterChanged" 
 * @param args
 */
ChurchInterface.prototype.sendMessageToAllViews = function (message, args) {
  if (message=="allDataLoaded")
    this.setAllDataLoaded(true);
  jQuery.each(this.getViews(), function(k,a) {
    a.messageReceiver(message, args);
  });  
};

ChurchInterface.prototype._pollForNews = function () {
  var this_object=this;
  this.pollForNews=window.setTimeout(function() {
    this_object.jsendRead({func:"pollForNews", last_id:this_object.lastLogId}, function(ok, json) {
      if (ok) {
        if (json.lastLogId>this_object.lastLogId) {
          this_object.sendMessageToAllViews("pollForNews",json.logs);
          this_object.lastLogId=json.lastLogId;
        }
        this_object._pollForNews();
      }
    });
  },10000);
};

ChurchInterface.prototype.setAllDataLoaded = function (val) {
  var t=this;
  t.allDataLoaded=val;
  if (val) { 
    if (t.lastLogId!=null) t._pollForNews();    
    t.implantCallbacks();
  }
};
ChurchInterface.prototype.isAllDataLoaded = function () {
  return this.allDataLoaded;
};


ChurchInterface.prototype.historyCreateStep = function (hash) {
  jQuery.history.load(hash);
};

/**
 * History Callback fuer jquery.history.js
 * @param hash - Parameter nach dem # der URL
 */
ChurchInterface.prototype.history = function (hash) {
  var this_object=this;
  if (hash==null) hash="";
  var arr=hash.split("/");
  // Wenn kein Hash vorhanden ist, wird erstmal die StandardViewName angezeigt
  if (this.views[arr[0]]==null) {
    arr[0]=this.standardviewname+"/";
    jQuery.history.load(arr.join("/"));
  }
  else {  
    this.currentView=this.views[arr[0]];
    // filter-Wiederherstellung über die Url, für History(back), damit die Filter damit funktionieren.
    // Ist momentan in der Testphase, funktioniert nur für das Suchfeld, erstmal auskommentiert, 
    // Probleme mit Multiselect, da es bei ToString nicht den Wert, sondern ein Array wiedergibt.
    var doRefresh=false;
    jQuery.each(arr, function(k,a) {
      if (a.indexOf("searchEntry:")==0) {
        this_object.currentView.filter["searchEntry"] = a.substr(12,99);
      } 
      else if ((a.indexOf("filter")==0) && (a.indexOf(":")>1)) {
        this_object.currentView.filter[a.substr(0,a.indexOf(":"))] = a.substr(a.indexOf(":")+1,99);
      } 
      else if ((a.indexOf("doc")==0) && (a.indexOf(":")>1)) {
        var newdoc = a.substr(a.indexOf(":")+1,99);
        var re=true;
        if (this_object.currentHistoryArray!=null)
          $.each(this_object.currentHistoryArray, function(i,b) {
            if (b==newdoc) re=false;
          });
        if (re) {
          this_object.currentView.filter[a.substr(0,a.indexOf(":"))]=newdoc;
          doRefresh=true;
        }
      } 
    });
   if ((this.currentHistoryArray==null) || (this.currentHistoryArray[0]!=arr[0]) || (doRefresh)) {
     this.currentView.renderView();
   }
    
   this.currentHistoryArray=arr;
  }  
};

function history(hash) {
  churchInterface.history(hash);
};

ChurchInterface.prototype.activateHistory = function (standardviewname) {
  this.standardviewname=standardviewname;
  jQuery.history.init(history,{ unescape: ",:/"});
};

ChurchInterface.prototype.registerView = function (viewName, viewObject) {
  this.views[viewName]=viewObject;
};

ChurchInterface.prototype.getViews = function () {
  return this.views;
};

/**
 * Wirft einen fatalen Fehler per Modal-Window und führt dann einen Reload aus.
 */
ChurchInterface.prototype.throwFatalError=function(errorText){
  if (this.errorWindow==null) 
    var modal=jQuery(document.createElement('div')).append(jQuery("#content"));
  else modal=this.errorWindow;
  modal.html(errorText);
  modal.dialog({  
    autoOpen:true, 
    modal:true,
    height:500,
    width:600,
    title:"Fehler beim Laden",
    buttons: {
      "Neu laden":function() {modal.dialog("close");}
    },     
    close: function(){ location.reload(true);
    }
  });
  this.errorWindow=modal;
};


/**
 * Schreibt Daten an die Standard-Ajax-Schnittstelle mit dem JSEND Standard
 * http://labs.omniti.com/labs/jsend
 * Es gibt status <i>error</i> für fatale Geschichten (DB nicht in Ordnung)
 * <i>fail</i> und <i>success</i>
 * @param obj - Enthält alle Objecte, vor allem die func
 * @param func - function erhält den status und die daten
 * @param async - asyncron (default=true)
 * @param Get - getmethod (default=true)
 */
ChurchInterface.prototype.jsendWrite = function (obj, func, async, get, overwriteModulename) {
  return this.jsend("Speichern", obj, func, async, get, overwriteModulename);
};
ChurchInterface.prototype.jsendRead = function (obj, func, async, get, overwriteModulename) {
  return this.jsend("Lade", obj, func, async, get, overwriteModulename);
};
ChurchInterface.prototype.jsend = function (name, obj, func, async, get, overwriteModulename) {
  var this_object=this;
  var modulename=this.modulename;
  if (overwriteModulename!=null) modulename=overwriteModulename;
  if (async==null) async=true;
  if (get==null) get=true; 
  if (!this_object.fatalErrorOccured) {
    this.setStatus(name+" Daten...");
    var obj2=new Object();
    $.each(obj, function(k,a) {
      if (a instanceof Date)
        obj2[k]=a.toStringEn(true);
      else 
        obj2[k]=a;
    });
    jQuery.ajax({
      url: "index.php?q="+modulename+"/ajax",
      dataType: 'json',
      data: obj2,
      async: async,
      type: (get?"GET":"POST"),
      success : function(json) {
        this_object.clearStatus();
        // Error = ist was schlimmes passiert!
        if (json.status=="error")  {
          if (json.message=="Session expired!")
            window.location.href="?q=home&message=Session ist abgelaufen, bitte neu anmelden!"
          else
            alert("Fehler beim "+name+" in "+modulename+": "+json.message);
        }
        // Wenn es success oder fail ist, übergebe an die Anwendung zurück.
        else if (func!=null)
          func(json.status=="success", json.data);
      },
      error: function(jqXHR, textStatus, errorThrown) {
        this_object.fatalErrorOccured=true;
        if ((jqXHR.status!=0) || (errorThrown!="")) {
          this_object.setStatus("Fehler aufgetreten!");
          this_object.throwFatalError("<b>Fehlermeldung: "+jqXHR.status+" "+textStatus+(errorThrown!=null?" Fehler: "+errorThrown:'')+"</b><br/><br/>"+jqXHR.responseText);
        }
        else this_object.setStatus("Abbrechen...");
      }
    });
  }
};

ChurchInterface.prototype.saveSetting = function(settingname, val) {
  this.jsendWrite({func:"saveSetting", sub:settingname, val:val});  
};

ChurchInterface.prototype.sendEmail = function (to, subject, body) {
  this.jsendWrite({ func: "send_email",  subject: subject, body:body, to:to});
};

/**
 * Setzt den Status in der Statuszeile
 * @param status
 */
ChurchInterface.prototype.setStatus = function (status, hideAutomatically) {
  var this_object=this;
  if (this.hideStatusTimer!=null) {
    window.clearTimeout(this.hideStatusTimer);
    this.hideStatusTimer=null;
  }
  
  jQuery("#cdb_status").html(status);
  if (hideAutomatically) {
    this.hideStatusTimer=window.setTimeout(function() {
      this_object.clearStatus();
      this.hideStatusTimer=null;
    },10000);
  }
};

/**
 * Loescht den Status in der Statuszeile 
 */
ChurchInterface.prototype.clearStatus = function () {
  var this_object=this;
  dt = new Date();
  jQuery("#cdb_status").html("<i>"+dt.toStringDe(true)+"</i>");
  window.setTimeout(function() {this_object.clearStatus(); },10000);
};


/**
 * Setzt die aktuelle View und baut Anwendung auf, in dem es die History des Browsers ansteuert
 * @param view - Instanz der aktuellen View
 */
ChurchInterface.prototype.setCurrentView = function (view, clearFilter) {
  this.currentView=view;
  if ((clearFilter==null) || (clearFilter)) this.currentView.clearFilter();
  this.currentView.historyCreateStep();
};

ChurchInterface.prototype.getCurrentView = function () {
  return this.currentView;
};

ChurchInterface.prototype.isCurrentView = function (name) {
  return this.currentView.name==name;
};


ChurchInterface.prototype.implantCallbacks = function() {
};



