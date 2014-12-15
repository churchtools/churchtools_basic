var allBookings = new Object();

function getCRBooking(a) {
  var event = new CCEvent(a);
  event.saveSuccess = saveCRSuccess;
  event.saveSplitSuccess = saveSplitCRSuccess;
  event.name = "Booking";
  return event;
}

function saveCRSuccess(a, b, json) {
  if (a.name=="Event") a = CAL2CRType(a); // When it saved over cal resource
  var t=churchInterface.views.WeekView;
  if (json.id!=null) {
    a.id=json.id;
    allBookings[json.id]=a;
  }
  else if (a.id!=null)
    allBookings[a.id]=a;

  // Get IDs for currently created Exceptions
  if (json.exceptions!=null) {
    each(json.exceptions, function(i,e) {
      allBookings[a.id].exceptions[e]=allBookings[a.id].exceptions[i];
      allBookings[a.id].exceptions[e].id=e;
      delete allBookings[a.id].exceptions[i];
    });
  }
  t.buildDates(allBookings);
  t.renderList();
}

function saveSplitCRSuccess(newEvent, pastEvent, originEvent) {
  if (pastEvent == null) delete allBookings[originEvent.booking_id];
  else allBookings[pastEvent.booking_id] = CAL2CRType(pastEvent);
  var newBooking = CAL2CRType(newEvent);
  allBookings[newBooking.id] = CAL2CRType(newEvent);
  t.buildDates(allBookings);
  t.renderList();
}

function cr_loadBookings(nextFunction) {
  churchInterface.setStatus("Lade Buchungen...");
  churchInterface.jsendRead({func:"getBookings" }, function(ok, json) {
    each(json, function(k,a) {
      allBookings[a.id]=getCRBooking(a);
    });
    churchInterface.clearStatus();
    churchInterface.sendMessageToAllViews("allDataLoaded");
    if (nextFunction!=null) nextFunction();
  }, null, null, "churchresource");
}
