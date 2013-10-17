function cdb_loadMasterData(nextFunction) {
  timers["startMasterdata"]=new Date();

/*  if ((masterData==null) && (localStorage.getItem("serviceMasterData")!=null)) {
    masterData=localStorage.getObject("serviceMasterData");
    if (nextFunction!=null) setTimeout(nextFunction,0);
  }
  else {
  */
  churchInterface.setStatus("Lade Kennzeichen...");
  jQuery.getJSON("index.php?q=churchservice/ajax", { func: "getMasterData" }, function(json) {
    timers["endMasterdata"]=new Date();
    masterData=json;
    // Wenn ich sortiere, kann ich nicht mehr per ID darauf zugreifen...
    masterData.service_sorted=churchcore_sortData_numeric(masterData.service,"sortkey");
    if (json.version!=churchservice_js_version)
      alert("Achtung, Versionen unterscheiden sich, bitte Cache loeschen! php:"+json.version+"/js:"+churchservice_js_version);
    
    churchInterface.clearStatus();
  //  localStorage.setObject("serviceMasterData",masterData);
    
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
  churchInterface.jsonRead(obj, function(json) {
    timers["endAllPersons"]=new Date();
    if (json.events!=null) {
      jQuery.each(json.events, function(k,a) {
        allEvents[a.id]=a;
      });
    }  
//    localStorage.setObject("allEvents",allEvents);
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

/**
 * Holt neue Events anhand der Id der Tabelle cs_eventservice
 * @param nextFunction(NewEvents) mit †bergabe als Array mit Event_ids die neu geladen wurden
 * @param lastLogId letzte Id der vorhandenen Log Id
 */
function cs_loadNewEventData(lastLogId, nextFunction) {
  churchInterface.setStatus("Lade Eventdaten...");
  var obj = new Object();
  obj.func="getNewEventData";
  obj.last_id=lastLogId;
  var newEvents = new Array();
  churchInterface.jsonRead(obj, function(json) {
    if (json.events!=null) {
      jQuery.each(json.events, function(k,a) {
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
  jQuery.each(masterData.service, function(k,a) {
    if ((a.cdb_gruppen_ids!=null) && (masterData.auth.viewgroup[a.servicegroup_id])) {      
      jQuery.each(a.cdb_gruppen_ids.split(","), function(i,b) {
        arr[b]=true; 
      });
    }
  });
  var ids="";
  jQuery.each(arr, function(k,a) {
	  // Wenn Feld nicht null sondern nur leer war
	  if (k!="") ids=ids+k+",";
  });
  ids=ids+"-1";
  //Lade Daten!
  churchInterface.jsonRead({func: "getPersonByGroupIds", ids: ids}, function(json) {
    if (groups==null) 
      groups=new Array();
    jQuery.each(json, function(k,a) {
      groups[k]=a;
    });
        
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

function cs_loadAbsent(nextFunction) {
  churchInterface.setStatus("Lade Abwesenheiten...");
  //Lade Daten!
  churchInterface.jsonRead({func: "getAbsent"}, function(json) {
    if (json!=null) {
      jQuery.each(json, function(k,a) {
        if (allPersons[a.person_id]==null)
          allPersons[a.person_id]=new Object();
        if (allPersons[a.person_id].absent==null)
          allPersons[a.person_id].absent=new Array();
        a.startdate=a.startdate.toDateEn(true);
        a.enddate=a.enddate.toDateEn(true);
        allPersons[a.person_id].absent.push(a);
        allPersons[a.person_id].name="moin";
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
  churchInterface.jsonRead({func: "getAllSongs"}, function(json) {
    allSongs=new Object();
    if (json!=null) {
      if (json.songs!=null) {
        jQuery.each(json.songs, function(k,a) {
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
  churchInterface.jsonRead({func: "getAllFacts"}, function(json) {
    if (json!=null) {
      jQuery.each(json, function(k,a) {
        jQuery.each(a, function(k,fact) {
          if (allEvents[fact.id]!=null) {
            if (allEvents[fact.id].facts==null)
              allEvents[fact.id].facts=new Object();
            allEvents[fact.id].facts[fact.fact_id]=fact;
          }
        });
      });
    }        
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

function cs_loadFiles(nextFunction) {
  churchInterface.setStatus("Lade Dateien...");
  //Lade Daten!
  churchInterface.jsonRead({func: "getFiles"}, function(json) {
    if (json!=null) {
      jQuery.each(allEvents, function(k,a) {
        a.files=null;
      });
      jQuery.each(json, function(k,a) {
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


