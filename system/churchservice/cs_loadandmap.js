function cs_loadListViewData(funcReady) {
  // Lade nun Event-Data
  cs_loadEventData(null, function() {
    if (!currentDate_externGesetzt) {
      if ($("#externevent_id").val()!=null && allEvents[$("#externevent_id").val()]!=null) {
        churchInterface.getCurrentView().currentDate=allEvents[$("#externevent_id").val()].startdate.withoutTime();
        churchInterface.getCurrentView().filter["searchEntry"]="#"+$("#externevent_id").val();
        churchInterface.getCurrentView().renderFilter();
        churchInterface.getCurrentView().renderCalendar();
      }
      else {
        var now = new Date(); now.addDays(-1);
        var first = new Date(); first.addDays(1000);
        var doit = false;
        each(allEvents, function(k,a) {
          if ((a.startdate>=now) && (a.startdate<first)) {
            first = new Date(a.startdate.getTime());
            doit = true;
          }
        });
        if (doit) churchInterface.getCurrentView().currentDate=first;
      }
    }

    cs_loadPersonDataFromCdb(function() {
      // Genug Daten um nun die Anwendung zu zeigen.
      // new: 27.1.13: RenderList reicht, denn Filter �ndert sich nix.
      if (funcReady!=null) funcReady();
//      churchInterface.getCurrentView().renderList();
      churchInterface.sendMessageToAllViews("allDataLoaded");

      // Lade nun alle Personendaten im Hintergrund weiter
      window.setTimeout(function() {
        cs_loadAbsent(function() {
          cs_loadFiles(function() {
          });
        });
      },10);
    });
  }, false);
}


/**
 *
 * @param nextFunction
 * @param forceReload (default = true)
 */
function cs_loadEventData(id, nextFunction, forceReload) {
  timers["startAllPersons"]=new Date();

  if (forceReload==null) forceReload=true;

  churchInterface.setStatus("Lade Eventdaten...");
  var obj = new Object();
  obj.func="getAllEventData";
  if (id!=null) obj.id=id;
  churchInterface.jsendRead(obj, function(ok, json) {
    if (!ok) {
      alert("Fehler beim Laden der Eventdaten: "+json);
    }
    else {
      timers["endAllPersons"]=new Date();
      if (json!=null) {
        each(json, function(k,a) {
          allEvents[a.id] = getCSEvent(a);
        });
      }
  //    localStorage.setObject("allEvents",allEvents);
      churchInterface.clearStatus();
      if (nextFunction!=null) nextFunction();
    }
  });
}

/**
 * Holt neue Events anhand der Id der Tabelle cs_eventservice
 * @param nextFunction(NewEvents) mit �bergabe als Array mit Event_ids die neu geladen wurden
 * @param lastLogId letzte Id der vorhandenen Log Id
 */
function cs_loadNewEventData(lastLogId, nextFunction) {
  churchInterface.setStatus("Lade Eventdaten...");
  var obj = new Object();
  obj.func="getNewEventData";
  obj.last_id=lastLogId;
  var newEvents = new Array();
  churchInterface.jsendRead(obj, function(ok, json) {
    if ((ok) && (json!=null)) {
      each(json, function(k,a) {
        newEvents.push(a.id);
        allEvents[a.id]=getCSEvent(a);
      });
    }
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction(newEvents);
  });
}

function cs_loadPersonDataFromCdb(nextFunction) {
  churchInterface.setStatus("Lade Personendaten...");
  var arr = new Object();
  each(masterData.service, function(k,a) {
    if ((a.cdb_gruppen_ids!=null) && (masterData.auth.viewgroup[a.servicegroup_id])) {
      each(a.cdb_gruppen_ids.split(","), function(i,b) {
        arr[b]=true;
      });
    }
  });
  var ids="";
  each(arr, function(k,a) {
	  // Wenn Feld nicht null sondern nur leer war
	  if (k!="") ids=ids+k+",";
  });
  ids=ids+"-1";
  //Lade Daten!
  churchInterface.jsendRead({func: "getPersonByGroupIds", ids: ids}, function(ok, json) {
    if (groups==null)
      groups=new Array();
    each(json, function(k,a) {
      groups[k]=a;
    });

    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

function cs_loadAbsent(nextFunction) {
  churchInterface.setStatus("Lade Abwesenheiten...");
  //Lade Daten!
  churchInterface.jsendRead({func: "getAbsent"}, function(ok, json) {
    if (json!=null) {
      each(json, function(k,a) {
        if (allPersons[a.person_id]==null)
          allPersons[a.person_id]=new Object();
        if (allPersons[a.person_id].absent==null)
          allPersons[a.person_id].absent=new Object();
        a.startdate=a.startdate.toDateEn(true);
        a.enddate=a.enddate.toDateEn(true);
        allPersons[a.person_id].absent[a.id]=a;
      });
    }
    churchInterface.getCurrentView().renderCalendar();

    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

function cs_loadSongs(nextFunction) {
  churchInterface.setStatus("Lade Songs...");
  //Lade Daten!
  churchInterface.jsendRead({func: "getAllSongs"}, function(ok, json) {
    allSongs=new Object();
    if (json!=null) {
      if (json.songs!=null) {
        each(json.songs, function(k,a) {
          allSongs[a.id]=a;
        });
      }
    }
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

function cs_loadSongStats(nextFunction) {
  churchInterface.setStatus("Lade Songs...");
  churchInterface.jsendRead({func: "getSongStatistic"}, function(ok, json) {
    each(json, function(k,a) {
      each(allSongs, function(i,s) {
        if (s.arrangement[k]!=null) s.arrangement[k].statistics = a;
      });
    });
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

function cs_loadFacts(nextFunction) {
  churchInterface.setStatus("Lade Fakten...");
  //Lade Daten!
  churchInterface.jsendRead({func: "getAllFacts"}, function(ok, json) {
    if (!ok) alert("Fehler beim Laden der Fakten: "+json);
    else {
      if (json!=null) {
        each(json, function(k,a) {
          each(a, function(k,fact) {
            if (allEvents[fact.id]!=null) {
              if (allEvents[fact.id].facts==null)
                allEvents[fact.id].facts=new Object();
              allEvents[fact.id].facts[fact.fact_id]=fact;
            }
          });
        });
      }
    }
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

function cs_loadFiles(nextFunction) {
  churchInterface.setStatus("Lade Dateien...");
  //Lade Daten!
  churchInterface.jsendRead({func: "getFiles"}, function(ok, json) {
    if (json!=null) {
      each(allEvents, function(k,a) {
        a.files=null;
      });
      each(json, function(k,a) {
        if (allEvents[a.domain_id]!=null) {
          if (allEvents[a.domain_id].files==null)
            allEvents[a.domain_id].files=new Object();
          allEvents[a.domain_id].files[a.id]=a;
        }
      });
    }
    churchInterface.getCurrentView().renderFiles();

    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}
