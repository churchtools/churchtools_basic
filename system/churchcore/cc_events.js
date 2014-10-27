/**
 * Format the result of getEventChangeImpact()
 * @param {[type]} data
 * @param {[type]} func if confirm(true) otherwise (false)
 */
function confirmImpactOfEventChange(data, func) {
  if (data.warning || data.hint!=null) {
    var rows = new Array();
    rows.push('<legend>Achtung, die Änderung hat Auswirkungen!</legend>');
    if (data.hint!=null) {
      rows.push('<p>' + data.hint);
    }

    if (churchcore_countObjectElements(data.bookings)>0) {
      rows.push('<h4>Buchungen</h4>');
      each(data.bookings, function(k,a) {
        rows.push('<li>' + a);
      });
    }
    if (churchcore_countObjectElements(data.services)>0) {
      rows.push('<h4>Dienstanfragen</h4>');
      rows.push('<ul>');
      each(data.services, function(k,a) {
        rows.push('<li>' + a.date.toDateEn().toStringDe(true) +" " + a.service + ": " + a.name);
        if ( !a.confirmed ) rows.push('? <i>(unbestätigt)</i>');
        else rows.push('<i> (bestätigt)</i>');
      });
      rows.push('</ul>');
      rows.push('<p><small>Bestätigte Anfragen werden durch die Änderungen wiede unbestätigt!</small>');
    }

    var elem = form_showDialog("Bestätigung der Auswirkungen", rows.join(""), 500, 500, {
      "Ausführen": function() {
        elem.dialog("close");
        func(true);
      }
    });
    elem.dialog("addcancelbutton");
  }
  else {
    func(true);
  }
}

function cloneEvent(event) {
  var e = jQuery.extend(true, {}, event);
  e.startdate = event.startdate;
  e.enddate = event.enddate;
  if (event.repeat_until!=null)
    e.repeat_until=new Date(event.repeat_until.getTime());
  each(event.csevents, function(k,a) {
    a.startdate = new Date(a.startdate.getTime());
  });
  return e;
}

function eventDifferentDates(a, b) {
  if (a.startdate.getTime()!=b.startdate.getTime() ||
      a.enddate.getTime()!=b.enddate.getTime())
    return true;

  if (a.repeat_id!=b.repeat_id)
    return true;

  if (a.repeat_id>0) {
    if ((a.repeat_until==null && b.repeat_id!=null) ||
      (a.repeat_until!=null && b.repeat_id==null) ||
      (a.repeat_until!=null && b.repeat_until!=null && a.repeat_until.getTime()!=b.repeat_until.getTime()))
    return true;
  }

  if (JSON.stringify(a.exceptions)!=JSON.stringify(b.exceptions))
    return true;

  if (JSON.stringify(a.additions)!=JSON.stringify(b.additions))
    return true;

  return false;
}

/**
 * Ask user to splitEvent
 * @param {[type]} myEvent
 * @param {[type]} position (clientX, clientY)
 * @param {[type]} func (null = cancel, false=sinlge, true=untilEnd)
 */
function askSplitEvent(myEvent, position, func) {
  if (!isSeries(myEvent)) func(false);
  else {
    $("#popupmenu").popupmenu({
      entries: ["Nur diesen Termin ändern", "Diesen und zukünftige ändern", "Abbruch"],
      pos: position,
      remove: function() {
        func(null);
      },
      click: function(result) {
        if (result==0) func(false);
        else if (result==1) func(true);
        else func(null);
      }
    });
    $("#popupmenu").popupmenu("show");
  }
}

/**
 * Splits the current Event into two, one for the past and one for the new one created.
 * If this is not a series, just return a cloned event and pastEvent is null
 * func(newEvent, pastEvent)
 */
function splitEvent(myEvent, splitDate, untilEnd, func) {
  var newEvent = cloneEvent(myEvent);

  // if this is not a series, nothing to do!
  if (!isSeries(myEvent)) {
    func(newEvent, null);
  }
  else {
    splitDate = splitDate.withoutTime();
    var delta = splitDate.getTime() - myEvent.startdate.withoutTime().getTime();
    var pastEvent = cloneEvent(myEvent);
    newEvent.startdate = new Date( newEvent.startdate.getTime() + delta );
    newEvent.enddate = new Date( newEvent.enddate.getTime() + delta );
    if (!untilEnd) {
      delete newEvent.id;
      _addException(pastEvent, splitDate, true);
      newEvent.repeat_id = 0;
      deleteOlderExceptionsAndAdditions(newEvent, splitDate, true);
      deleteNewerExceptionsAndAdditions(newEvent, splitDate, true);
      delete newEvent.repeat_until;
      delete newEvent.exceptions;
      delete newEvent.additions;
      func(newEvent, pastEvent);
    }
    else {
      // If editing first of series, change the whole series, no new Event is neccesary
      if (myEvent.startdate.withoutTime().getTime() == splitDate.getTime()) func(newEvent, null);
      else {
        delete newEvent.id;
        deleteOlderExceptionsAndAdditions(newEvent, splitDate, true);
        // Change repeat until date
        var d = new Date(splitDate.getTime());
        d.addDays(-1);
        pastEvent.repeat_until = d;
        deleteNewerExceptionsAndAdditions(pastEvent, splitDate, true);
        func(newEvent, pastEvent);
      }
    }
  }
}

function isSeries(myEvent) {
  return myEvent.repeat_id != null && myEvent.repeat_id > 0;
}

function getCSEventId(currentEvent, date, withoutTime) {
  var id=null;
  if (withoutTime==null) withoutTime=false;
  each(currentEvent.csevents, function(k,e) {
    if ((withoutTime && e.startdate.withoutTime().getTime()==date.withoutTime().getTime())
        || (!withoutTime && e.startdate.getTime()==date.getTime())) {
      id=k;
      return false;
    }
  });
  return id;
}

function addCSEvent(currentEvent, csevent) {
  if (currentEvent.newCSEventId == null) currentEvent.newCSEventId = 0;
  currentEvent.newCSEventId = currentEvent.newCSEventId - 1;
  if (currentEvent.csevents == null) currentEvent.csevents = new Object();
  currentEvent.csevents[currentEvent.newCSEventId] = csevent;
}

/**
 * [_addException description]
 * @param {[type]} currentEvent
 * @param {[type]} date
 * @param {[type]} deleteCS default false, true means csevents will be deleted, otherwise action:delete
 */
function _addException(currentEvent, date, deleteCS) {
  if (currentEvent.exceptions==null) currentEvent.exceptions = new Object();
  if (currentEvent.exceptionids==null) currentEvent.exceptionids = 0;
  currentEvent.exceptionids = currentEvent.exceptionids-1;
  currentEvent.exceptions[currentEvent.exceptionids]
        ={id:currentEvent.exceptionids, except_date_start:date.toStringEn(), except_date_end:date.toStringEn()};
  // Add Exception for CS Events
  var csId = getCSEventId(currentEvent, date, true);
  if (csId!=null) {
    if (deleteCS==null || !deleteCS) currentEvent.csevents[csId].action="delete";
    else delete currentEvent.csevents[csId];
  }
}

/**
 * [deleteNewerExceptionsAndAdditions description]
 * @param {[type]} event
 * @param {[type]} date
 * @param {[type]} deleteCS default false, true means csevents will be deleted, otherwise action:delete
 */
function deleteNewerExceptionsAndAdditions(event, date, deleteCS) {
  each(event.exceptions, function(k,a) {
    if (a.except_date_start.toDateEn(false).getTime() > date.getTime())
      delete event.exceptions[k];
  });
  each(event.additions, function(k,a) {
    if (a.add_date.toDateEn(false).getTime() > date.getTime())
      delete event.additions[k];
  });
  each(event.csevents, function(k,a) {
    if (a.startdate.withoutTime().getTime() > date.getTime()) {
      if (deleteCS==null || !deleteCS) event.csevents[k].action="delete";
      else delete event.csevents[k];
    }
  })
}

/**
 * [deleteOlderExceptionsAndAdditions description]
 * @param {[type]} event
 * @param {[type]} date
 * @param {[type]} deleteCS default false, true means csevents will be deleted, otherwise action:delete
 */
function deleteOlderExceptionsAndAdditions(event, date, deleteCS) {
  each(event.exceptions, function(k,a) {
    if (a.except_date_start.toDateEn(false).getTime() < date.getTime())
      delete(event.exceptions[k]);
  });
  each(event.additions, function(k,a) {
    if (a.add_date.toDateEn(false).getTime() < date.getTime())
      delete(event.additions[k]);
  });
  each(event.csevents, function(k,a) {
    if (a.startdate.withoutTime().getTime() < date.getTime()) {
      if (deleteCS==null || !deleteCS) event.csevents[k].action="delete";
      else delete event.csevents[k];
    }
  })
}
