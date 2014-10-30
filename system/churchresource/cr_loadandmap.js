
function cdb_loadMasterData(nextFunction) {
  churchInterface.setStatus("Lade Kennzeichen...");
  timers["startMasterdata"]=new Date();
  churchInterface.jsendRead({ func: "getMasterData" }, function(ok, json) {
    timers["endMasterdata"]=new Date();
    each(json, function(k,a) {
      masterData[k]=json[k];
    });

    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}

function cr_loadBookings(nextFunction) {
  churchInterface.setStatus("Lade Buchungen...");
  churchInterface.jsendRead({func:"getBookings" }, function(ok, json) {
    each(json, function(k,a) {
      allBookings[a.id]=getCRBooking(a);
    });
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}
