/**
 * Offers a interface between JavaScript and PHP 
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
 * @param message: e.g. "allDataLoaded", "filterChanged" 
 * @param args
 */
ChurchInterface.prototype.sendMessageToAllViews = function (message, args) {
  if (message=="allDataLoaded") {
    this.setAllDataLoaded(true);
    if ($("#printview").val()!=null) {
      window.setTimeout(function() {
        window.print();
      }
      ,1000);
    }
  }
  jQuery.each(this.getViews(), function(k,a) {
    a.messageReceiver(message, args);
  });  
};

ChurchInterface.prototype._pollForNews = function () {
  var t=this;
  this.pollForNews=window.setTimeout(function() {
    t.jsendRead({func:"pollForNews", last_id:t.lastLogId}, function(ok, json) {
      if (ok) {
        if (json.lastLogId>t.lastLogId) {
          t.sendMessageToAllViews("pollForNews",json.logs);
          t.lastLogId=json.lastLogId;
        }
        t._pollForNews();
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
  var t=this;
  if (hash==null) hash="";
  var arr=hash.split("/");
  // Wenn kein Hash vorhanden ist, wird erstmal die StandardViewName angezeigt
  if (this.views[arr[0]]==null) {
    arr[0]=this.standardviewname+"/";
    jQuery.history.load(arr.join("/"));
  }
  else {  
    this.currentView=this.views[arr[0]];
    // filter-Wiederherstellung �ber die Url, f�r History(back), damit die Filter damit funktionieren.
    // Ist momentan in der Testphase, funktioniert nur f�r das Suchfeld, erstmal auskommentiert, 
    // Probleme mit Multiselect, da es bei ToString nicht den Wert, sondern ein Array wiedergibt.
    var doRefresh=false;
    jQuery.each(arr, function(k,a) {
      if (a.indexOf("searchEntry:")==0) {
        t.currentView.filter["searchEntry"] = a.substr(12,99);
      } 
      else if ((a.indexOf("filter")==0) && (a.indexOf(":")>1)) {
        t.currentView.filter[a.substr(0,a.indexOf(":"))] = a.substr(a.indexOf(":")+1,99);
      } 
      else if ((a.indexOf("doc")==0) && (a.indexOf(":")>1)) {
        var newdoc = a.substr(a.indexOf(":")+1,99);
        var re=true;
        if (t.currentHistoryArray!=null)
          $.each(t.currentHistoryArray, function(i,b) {
            if (b==newdoc) re=false;
          });
        if (re) {
          t.currentView.filter[a.substr(0,a.indexOf(":"))]=newdoc;
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
 * Wirft einen fatalen Fehler per Modal-Window und f�hrt dann einen Reload aus.
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
    buttons:{},
    title:_("error.occured"),
  });
  modal.dialog("addbutton", _("reload"), function() {
    location.reload(true);
  })
  this.errorWindow=modal;
};


/**
 * Schreibt Daten an die Standard-Ajax-Schnittstelle mit dem JSEND Standard
 * http://labs.omniti.com/labs/jsend
 * Es gibt status <i>error</i> f�r fatale Geschichten (DB nicht in Ordnung)
 * <i>fail</i> und <i>success</i>
 * @param obj - Enth�lt alle Objecte, vor allem die func
 * @param func - function erh�lt den status und die daten
 * @param async - asyncron (default=true)
 * @param Get - getmethod (default=true)
 */
ChurchInterface.prototype.jsendWrite = function (obj, func, async, get, overwriteModulename) {
  return this.jsend(_("save.data"), obj, func, async, get, overwriteModulename);
};
ChurchInterface.prototype.jsendRead = function (obj, func, async, get, overwriteModulename) {
  return this.jsend(_("load.data"), obj, func, async, get, overwriteModulename);
};
ChurchInterface.prototype.jsend = function (name, obj, func, async, get, overwriteModulename) {
  var t=this;
  var modulename=this.modulename;
  if (overwriteModulename!=null) modulename=overwriteModulename;
  if (async==null) async=true;
  if (get==null) get=true; 
  if (!t.fatalErrorOccured) {
    this.setStatus(name);
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
        t.clearStatus();
        // Error = ist was schlimmes passiert!
        if (json.status=="error")  {
          if (json.message=="Session expired!")
            window.location.href="?q=home&message="+_("session.expired.please.login")
          else
            alert(_("error.occured")+": "+name+"."+modulename+": "+json.message);
        }
        // Wenn es success oder fail ist, �bergebe an die Anwendung zur�ck.
        else if (func!=null)
          func(json.status=="success", json.data);
      },
      error: function(jqXHR, textStatus, errorThrown) {
        t.fatalErrorOccured=true;
        if ((jqXHR.status!=0) || (errorThrown!="")) {
          t.setStatus(_("error.occured"));
          t.throwFatalError("<b>"+_("description")+": "+jqXHR.status+" "+textStatus+(errorThrown!=null?errorThrown:'')+"</b><br/><br/>"+jqXHR.responseText);
        }
        else t.setStatus(_("cancel")+"...");
      }
    });
  }
};

ChurchInterface.prototype.saveSetting = function(settingname, val) {
  this.jsendWrite({func:"saveSetting", sub:settingname, val:val});  
};

ChurchInterface.prototype.deleteSetting = function(settingname) {
  this.jsendWrite({func:"saveSetting", sub:settingname, remove:true});  
};

ChurchInterface.prototype.sendEmail = function (to, subject, body) {
  this.jsendWrite({ func: "send_email",  subject: subject, body:body, to:to});
};

/**
 * Setzt den Status in der Statuszeile
 * @param status
 */
ChurchInterface.prototype.setStatus = function (status, hideAutomatically) {
  var t=this;
  if (this.hideStatusTimer!=null) {
    window.clearTimeout(this.hideStatusTimer);
    this.hideStatusTimer=null;
  }
  
  jQuery("#cdb_status").html(status);
  if (hideAutomatically) {
    this.hideStatusTimer=window.setTimeout(function() {
      t.clearStatus();
      this.hideStatusTimer=null;
    },10000);
  }
};

/**
 * Loescht den Status in der Statuszeile 
 */
ChurchInterface.prototype.clearStatus = function () {
  var t=this;
  dt = new Date();
  jQuery("#cdb_status").html("<i>"+dt.toStringDe(true)+"</i>");
  window.setTimeout(function() {t.clearStatus(); },10000);
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



