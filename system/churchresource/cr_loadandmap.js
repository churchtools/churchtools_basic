
function cdb_loadMasterData(nextFunction) {
  churchInterface.setStatus("Lade Kennzeichen...");
  timers["startMasterdata"]=new Date();
  churchInterface.jsendRead({ func: "getMasterData" }, function(ok, json) {
    timers["endMasterdata"]=new Date();
    $.each(json, function(k,a) {
      masterData[k]=json[k];  
    });
    
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}


function cr_mapJsonBookings(a) {
  booking=new Object();
  booking.id=a.id;
  booking.resource_id=a.resource_id;
  booking.person_id=a.person_id;
  booking.person_name=a.person_name;
  booking.startdate=a.startdate.toDateEn();
  booking.enddate=a.enddate.toDateEn();
  booking.repeat_id=a.repeat_id;
  booking.repeat_frequence=a.repeat_frequence;
  booking.repeat_until=a.repeat_until;
  if (a.repeat_until!=null) booking.repeat_until=a.repeat_until.toDateEn();
  booking.repeat_option_id=a.repeat_option_id;
  booking.status_id=a.status_id;
  booking.text=a.text;
  booking.location=a.location;
  booking.note=a.note;
  booking.exceptions=a.exceptions;
  booking.additions=a.additions;
  booking.show_in_churchcal_yn=a.show_in_churchcal_yn;
  booking.cc_cal_id=a.cc_cal_id;
  booking.category_id=a.category_id;
  return booking;
}

function cr_loadBookings(nextFunction) {
  churchInterface.setStatus("Lade Buchungen...");
  churchInterface.jsendRead({func:"getBookings" }, function(ok, json) {
    jQuery.each(json,function(k,a) {
      allBookings[a.id]=cr_mapJsonBookings(a);       
    });
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}
