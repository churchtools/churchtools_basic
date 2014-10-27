function cdb_loadMasterData(nextFunction) {
  timers["startMasterdata"]=new Date();

  churchInterface.setStatus("Lade Kennzeichen...");
  churchInterface.jsendRead({ func: "getMasterData" }, function(ok, json) {
    timers["endMasterdata"]=new Date();
    masterData=json;
    // Wenn ich sortiere, kann ich nicht mehr per ID darauf zugreifen...
    masterData.service_sorted=churchcore_sortData_numeric(masterData.service,"sortkey");

    churchInterface.clearStatus();

    if (nextFunction!=null) nextFunction();
  });
  //}
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
          a.startdate = new Date(a.startdate);
          a.enddate = new Date(a.enddate);
          a.repeat_until = new Date(a.repeat_until);
          a.cal_startdate = new Date(a.cal_startdate);
          a.cal_enddate = new Date(a.cal_enddate);
          allEvents[a.id]=a;
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
        allEvents[a.id]=a;
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
    listView.renderCalendar();

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
    listView.renderFiles();

    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}
