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
  this.loadedJSFiles = new Array();
  this.loadedDataObjects = new Array();
  this.reminderTimer = null;
}

var churchInterface = new ChurchInterface();

ChurchInterface.prototype.setModulename = function (modulename) {
  this.modulename=modulename;
};

ChurchInterface.prototype.loadMasterData = function(nextFunction) {
  var t = this;
  churchInterface.setStatus("Lade Kennzeichen...");
  churchInterface.jsendRead({ func: "getMasterData" }, function(ok, json) {
    if (masterData == null) masterData = new Object();
    each(json, function(k,a) {
      masterData[k] = json[k];
    });
    if (masterData.reminder!=null) {
      if (t.reminderTimer!=null) window.clearTimeout(t.reminderTimer);
      t.acticateReminderPopup();
    }
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
};

ChurchInterface.prototype.acticateReminderPopup = function (delay) {
  var t = this;
  t.reminderTimer = window.setTimeout(function() {
    var dt = new Date();
    each(masterData.reminder, function(k, domain) {
      each(domain, function(id, reminder) {
        if (reminder!=null && reminder.toDateEn(true).getTime()<dt.getTime()) {
          t.showReminderPopup(k, id);
          delete masterData.reminder[k];
        }
      });
    });
    t.acticateReminderPopup(10000);
  }, (delay == null ? 1000 : delay));
};

ChurchInterface.prototype.showReminderPopup = function(domainType, domainId) {
  if (domainType=="event") {
    churchInterface.jsendRead({func: "getEvent", id: domainId}, function(ok, data) {
      if (data == null) { // Event was deleted
        churchInterface.jsendWrite({func: "saveReminder", domain_id: domainId, domain_type: domainType});
      }
      else {
        var rows = new Array();
        rows.push("<legend>"+data.bezeichnung + " (" + masterData.category[data.category_id].bezeichnung+')</legend>');
        rows.push("<p>Startzeit: "+data.startdate.toDateEn(true).toStringDe(true));
        rows.push("<p>Endzeit: "+data.enddate.toDateEn(true).toStringDe(true));
        if (calCCType.data[data.category_id]!=null)
          rows.push("<p>"+form_renderButton({label:"Event öffnen", cssid:"openEvent"}));
        var elem = form_showDialog("Erinnerung an " + _(domainType), rows.join(""), 300, 300);
        elem.dialog("addbutton", _("close"), function() {
          churchInterface.jsendWrite({func: "saveReminder", domain_id: domainId, domain_type: domainType}, function(ok, data) {
            if (!ok) alert(data);
          });
          elem.dialog("close");
        });
        elem.dialog("addbutton", "Erneut erinnern", function() {
          elem.dialog("close");
        });
        elem.find("#openEvent").click(function() {
          var event = calCCType.data[data.category_id].events[domainId];
          var pos = elem.find("#openEvent").offset();
          editEvent(event, true, event.startdate, {clientX: pos.left, clientY: pos.top});
        });
      }
    }, null, null, "churchcal");
  }
};

ChurchInterface.prototype.setLastLogId= function (lastLogId) {
  this.lastLogId=lastLogId;
};
ChurchInterface.prototype.getLastLogId= function () {
  return this.lastLogId;
};

/**
 * Checks if JSFile is already loaded
 * @param {[type]} arr
 */
ChurchInterface.prototype.JSFilesLoaded = function(arr) {
  var t = this;
  var loaded = true;
  each(arr, function(k, filename) {
    if (!churchcore_inArray("system"+filename, t.loadedJSFiles)) loaded = false;
  });
  return loaded;
};

/**
* Load all JSFiles in arr from system/assets/
* @param {[type]} arr Array of Strings
* @param {[type]} func Will be executed if all loaded
*/
ChurchInterface.prototype.loadJSFiles = function (arr, funcWhenReady) {
  var t = this;
  if (arr.length==0) funcWhenReady();
  else {
    var filename = "system"+arr[0];
    arr.shift();

    // Check if file is not loaded already
    if (!churchcore_inArray(filename, t.loadedJSFiles)) {
      t.loadedJSFiles.push(filename);
      $.getCTScript(filename, function() {
        t.loadJSFiles(arr, funcWhenReady);
      });
    }
    else t.loadJSFiles(arr, funcWhenReady);
  }
};

ChurchInterface.prototype.loadDataObjects = function(arr, funcWhenReady) {
  var t = this;
  if (arr == null || arr.length == 0) {
    if (funcWhenReady != null) funcWhenReady();
  }
  else {
    var objectname = arr[0];
    arr.shift();
    if (!churchcore_inArray(objectname, t.loadedDataObjects)) {
      t.loadedDataObjects.push(objectname);
      var func = window[objectname];
      func(function() {
        t.loadDataObjects(arr, funcWhenReady);
      });
    }
    else t.loadDataObjects(arr, funcWhenReady);
  }
};

/**
 * Make a JavaScript download function.
 * For Safari und Firefox it will not be running with downloadLink (only open data in new window, not downloading)
 * It will store file on server and give link in new window.
 * @param filename
 * @param filesuffix
 * @param data
 */
ChurchInterface.prototype.downloadFile = function(filename, filesuffix, data) {
  var t = this;
  t.jsendWrite({func:"makeDownloadFile", filename:filename, suffix: filesuffix, data:data}, function(ok, data) {
    var Fenster = window.open(data);
    // Timer for deleting file
    window.setTimeout(function() {
      t.jsendWrite({func:"makeDownloadFile", remove:true, filename:data});
    }, 1000);
  });

  /* THIS IS NOT SUPPORTED BY SAFARI UND FIREFOX
  var uri = 'data:text/col;charset=utf-8,' + escape(add+"\n"+exp.join(""));

  var downloadLink = document.createElement("a");
  downloadLink.href = uri;
  downloadLink.download = "gruppenteilnehmerliste.csv";

  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
  */
};

/**
 * Generates a pdf of the current page and opens it in a new window
 * @param basename: e.g. "agenda"
 */
ChurchInterface.prototype.generatePDF = function (basename) {
  var t = this;
  var getDocTypeAsString = function () {
    var node = document.doctype;
    return node ? "<!DOCTYPE "
     + node.name
     + (node.publicId ? ' PUBLIC "' + node.publicId + '"' : '')
     + (!node.publicId && node.systemId ? ' SYSTEM' : '')
     + (node.systemId ? ' "' + node.systemId + '"' : '')
     + '>\n' : '';
  };
  var html = getDocTypeAsString() + document.documentElement.outerHTML;
  html = html.replace(/<script.*?\/script>/g, "");
  html = html.replace(/="system\//g, '="../../../system/');
  html = html.replace(/bootstrap.min.css/g, "bootstrap-pdf.min.css");
  html = html.replace(/churchtools.css/g, "churchtools-pdf.css");
  t.jsendWrite({func:"generatePDF", html:html, basename:basename}, function(ok, data) {
    if (data != null) {
      var fenster = window.open(data);
      fenster.focus();
      // Timer for deleting file
      window.setTimeout(function() {
        t.jsendWrite({func:"makeDownloadFile", remove:true, filename:data});
      }, 1000);
    }
  });
};

/**
 * Checks if the PDF generator is available and calls the callback with a boolean argument
 * @param callback: needs to accept a boolean parameter
 */
ChurchInterface.prototype.checkPDFGenerator = function (callback) {
  this.jsendWrite({func:"hasPDFGenerator"}, function(ok, data) {
    callback(data);
  });
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
      ,2000);
    }
  }
  each(this.getViews(), function(k,a) {
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
  if (!hash) {
    var uri = getCurrentURLAsObject();
    if (uri.searchObject && uri.searchObject["view"]) {
      hash = uri.searchObject["view"];
    }
    else hash = "";
  }
  var arr = hash.split("/");
  // Wenn kein Hash vorhanden ist, wird erstmal die StandardViewName angezeigt
  if (t.views[arr[0]]==null && (masterData.views==null || masterData.views[arr[0]]==null)) {
    arr[0]=t.standardviewname+"/";
    jQuery.history.load(arr.join("/"));
  }
  else {
    if (t.views[arr[0]] == null) {
      t.loadLazyView(arr[0], function(currentView) {
        t.currentView = currentView;
        t.progressURLFilter(arr);
      });
    }
    else {
      t.currentView = this.views[arr[0]];
      t.progressURLFilter(arr);
    }
  }
};

/**
 * Read URL and set the filter and if necessary call renderView
 * @param {[type]} arr
 */
ChurchInterface.prototype.progressURLFilter = function (arr) {
  var t = this;
  var doRefresh = false;
  each(arr, function(k,a) {
    if (a.indexOf("searchEntry:")==0) {
      t.currentView.filter["searchEntry"] = a.substr(12,99);
    }
    else if ((a.indexOf("filter")==0) && (a.indexOf(":")>1)) {
      t.currentView.filter[a.substr(0,a.indexOf(":"))] = a.substr(a.indexOf(":")+1,99);
    }
    else if ((a.indexOf("doc")==0) && (a.indexOf(":")>1)) {
      var docUrlencoded = a.substr(a.indexOf(":")+1,99);
      var newdoc = decodeURIComponent(docUrlencoded.replace(/\+/g, ' '));
      var re=true;
      if (t.currentHistoryArray!=null)
        each(t.currentHistoryArray, function(i,b) {
          if (b==newdoc) re=false;
        });
      if (re) {
        t.currentView.filter[a.substr(0,a.indexOf(":"))]=newdoc;
        doRefresh=true;
      }
    }
  });
  if ((t.currentHistoryArray == null || t.currentHistoryArray[0] != arr[0]) || doRefresh) {
    t.currentView.renderView();
  }

  t.currentHistoryArray=arr;
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
    title:_("error.occured")
  });
  modal.dialog("addbutton", _("reload"), function() {
    location.reload(true);
  });
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
 * @param Get - getmethod (default=false)
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
  if (get==null) get=false;
  if (!t.fatalErrorOccured) {
    this.setStatus(name);
    jQuery.ajax({
      url: "index.php?q="+modulename+"/ajax",
      dataType: 'json',
      data: transformAllDatesToString(obj),
      async: async,
      type: (get?"GET":"POST"),
      success : function(json) {
        if (json==null)
          alert(_("error.occured")+": JSON-Result is null!");

        t.clearStatus();
        // Error = ist was schlimmes passiert!
        if (json.status=="error")  {
          if (json.message=="Session expired!")
            window.location.href="?q=home&message="+_("session.expired.please.login");
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

ChurchInterface.prototype.saveSetting = function(settingname, val, func) {
  this.jsendWrite({func:"saveSetting", sub:settingname, val:val}, func);
};

ChurchInterface.prototype.setCookie = function(settingname, val, func) {
  this.jsendWrite({func:"setCookie", sub:settingname, val:val}, func);
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


ChurchInterface.prototype.loadLazyView = function (view, callWhenLoaded) {
  var t = this;
  if (!masterData.views[view]) { alert("View "+view+" not in List!"); return; }
  if (masterData.views[view].loaded == null) {
    $.getCTScript("system/"+this.modulename+"/"+masterData.views[view].filename+".js", function() {
      var func = window["get"+view];
      if (func==null) { alert("Cannot find function get"+view+"()"); return; }
      masterData.views[view].loaded=true;
      masterData.views[view].instance = func();
      t.views[view] = masterData.views[view].instance;
      if (callWhenLoaded != null) callWhenLoaded(masterData.views[view].instance);
    });
  }
  else {
    if (callWhenLoaded != null) callWhenLoaded(masterData.views[view].instance);
  }
};

/**
 * Setzt die aktuelle View und baut Anwendung auf, in dem es die History des Browsers ansteuert
 * @param view - Instanz der aktuellen View
 */
ChurchInterface.prototype.setCurrentView = function (view, clearFilter) {
  this.currentView=view;
  if (clearFilter == null || clearFilter) this.currentView.clearFilter();
  this.currentView.historyCreateStep();
};

ChurchInterface.prototype.setCurrentLazyView = function (view, clearFilter, func) {
  var t = this;
  t.loadLazyView(view, function(currentView) {
    if (func != null) func(currentView);
    t.setCurrentView(currentView, clearFilter);
  });
};

ChurchInterface.prototype.getCurrentView = function () {
  return this.currentView;
};

ChurchInterface.prototype.isCurrentView = function (name) {
  return this.currentView.name==name;
};


ChurchInterface.prototype.implantCallbacks = function() {
};
