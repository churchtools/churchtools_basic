
// CalSourceType ist das Grund-Object. Es ist pro Quell-System einmal zu vererben.
// Innerhalb des Objektes werden dann die Events gehalten separiert nach Kategorie
function CalSourceType() {
  var t=this;
  // H�lt die Daten nach category_id auf
  t.data = new Object();
  // Timer zum Laden der Daten. Erst werden alle Categorien als "needData" markiert und dann l�dt er
  t.timer=null;
}

CalSourceType.prototype.prepareCategory = function(category_id, refresh) {
  var t=this;
  if (refresh==null) refresh=false;
  if ((refresh) || (t.data[category_id]==null)) {
    if ((t.data[category_id]!=null) && (t.data[category_id].current_CalEvents!=null))
      send2Calendar("removeEventSource", t.data[category_id].current_CalEvents);
    t.data[category_id]=new Object();
    t.data[category_id].hide=false;
    t.data[category_id].status="new";
    t.data[category_id].events=new Object();
  }
};

CalSourceType.prototype.needData = function(category_id, refresh) {
  var t=this;
  if (refresh==null) refresh=false;
  t.prepareCategory(category_id, refresh);
  if (t.data[category_id].status!="loaded" && t.data[category_id].status!="loadData") {
    t.data[category_id].status="needData";
    t.data[category_id].name=t.getName(category_id);
    t.triggerCollectTimer();
  }
  else if (t.data[category_id].hide && t.data[category_id].status=="loaded") {
    t.data[category_id].hide=false;
    t.showEventsOnCal(category_id);
  }
};

CalSourceType.prototype.getName = function(category_id) {
  return "Unknown["+category_id+"]";
};

CalSourceType.prototype.triggerCollectTimer = function() {
  var t=this;
  if (t.timer!=null)
    window.clearTimeout(t.timer);
  t.timer=window.setTimeout(function() {
    t.collectData();
    t.timer=null;
  }, 100);
};

CalSourceType.prototype.hideData = function(category_id) {
  var t=this;
  t.prepareCategory(category_id);
  t.data[category_id].hide=true;
  if (t.data[category_id].current_CalEvents!=null)
    send2Calendar("removeEventSource", t.data[category_id].current_CalEvents);
};

/**
 *
 * @param category_id entweder eine konkrete oder null, dann werden all refreshed
 * @param needRefresh default=false;
 */
CalSourceType.prototype.refreshView = function(category_id, needRefresh) {
  var t=this;
  if (needRefresh==null) needRefresh=false;
  if (category_id!=null) {
    this.hideData(category_id);
    this.needData(category_id, needRefresh);
  }
  else {
    each(t.data, function(k,a) {
      if (a.status=="loaded") {
        t.hideData(k);
        t.needData(k, needRefresh);
      }
    });
  }
};

CalSourceType.prototype.collectData = function() {
  var t=this;
  var ids=new Array();
  each(t.data, function(k,a) {
    if (a.status=="needData") {
      ids.push(k);
      t.data[k].status="loadData";
    }
  });
  if (ids.length>0) {
    t.jsonCall(ids);
  }
};

CalSourceType.prototype.jsonCall = function(ids) {
  throw new Exception("Not implemented!");
};
CalSourceType.prototype.showEventsOnCal = function(category_id) {
};

function _getEventsFromDate(cs_events, start, end) {
  var go = start.format(DATEFORMAT_EN).toDateEn(false);
  var e = new Date();
  if (end==null) {
    e=new Date(); e.addDays(1000);
  }
  else e = end.format(DATEFORMAT_EN).toDateEn(false);
  var arr = new Array();
  while (go.getTime()<=e.getTime()) {
    if (cs_events!=null && cs_events[go.getFullYear()]!=null &&
        cs_events[go.getFullYear()][go.getMonth()+1]!=null &&
        cs_events[go.getFullYear()][go.getMonth()+1][go.getDate()]!=null)
      arr=arr.concat(cs_events[go.getFullYear()][go.getMonth()+1][go.getDate()]);
    go.addDays(1);
  }
  return arr;
}

function _addEventsToDateIndex(cs_events, o) {
  var year=cs_events[o.start.getFullYear()];
  if (year==null) year=new Array();

  var month=year[o.start.getMonth()+1];
  if (month==null) month=new Array();

  var day=month[o.start.getDate()];
  if (day==null) day=new Array();

  day.push(o);
  month[o.start.getDate()]=day;
  year[o.start.getMonth()+1]=month;
  cs_events[o.start.getFullYear()]=year;
}

// ---------------------------------------------------------------------------------------------------------
// Der CalCC-Type l�dt die Daten aus der CC_CAL-Tabelle
//---------------------------------------------------------------------------------------------------------
function CalCCType() {
  CalSourceType.call(this);
}
Temp.prototype = CalSourceType.prototype;
CalCCType.prototype = new Temp();
var calCCType = new CalCCType();


function mapEvents(allEvents) {
  var cs_events= new Array();
  each(allEvents, function(k,a) {
    if ((filterCategoryIds==null) || (churchcore_inArray(a.category_id, filterCategoryIds))) {
      if ((!embedded) || (a.intern_yn==0)) {
        each(churchcore_getAllDatesWithRepeats(a), function(k,d) {
          var o=Object();
          o.id= a.id;  // Id muss eindeutig sein, sonst macht er daraus einen Serientermin!
          o.title= '<span class="event-title">'+a.bezeichnung+'</span>';
          if ((a.notizen!=null) && (a.notizen!="")) o.notizen=a.notizen;
          if ((a.link!=null) && (a.link!="")) o.link=a.link;
          if ((a.ort!=null) && (a.ort!='')) o.title=o.title+' <span class="event-location">'+a.ort+'</span>';
          // Now get the service texts out of the events
          if (a.events!=null) {
            each(a.events, function(i,e) {
              if ((e.service_texts!=null) &&
                   (e.startdate.toDateEn(false).toStringDe(false)==d.startdate.toStringDe(false))) {
                o.title=o.title+' <span class="event-servicetext">'+e.service_texts.join(", ")+'</span>';
                return false;
              }
            });
          }
          if (a.bookings!=null && masterData.resources!=null) {
            o.title=o.title+'<span class="event-resources">';
            each(a.bookings, function(i,e) {
              if (masterData.resources[e.resource_id]!=null)
                o.title=o.title+'<br/>'+masterData.resources[e.resource_id].bezeichnung.trim(20);
            });
            o.title=o.title+'</span>';
          }
          o.start= d.startdate;
          o.end = d.enddate;
          // Tagestermin?
          if (churchcore_isAllDayDate(o.start, o.end)) {
            o.allDay=churchcore_isAllDayDate(o.start, o.end);
            // Add 1 day, because fullCalendar 2.0 works with exclusive end date!
            o.end.addDays(1);
          }

          if ((a.category_id!=null) && (masterData.category[a.category_id].color!=null))
            o.color=masterData.category[a.category_id].color;

          _addEventsToDateIndex(cs_events, o);
        });
      }
    }
  });
  return cs_events;
}


CalCCType.prototype.jsonCall = function(ids) {
  var t=this;
  churchInterface.jsendRead({func:"getCalPerCategory", category_ids:ids}, function(ok, cats) {
    if (ok) {
      if (cats!=null) {
        each(cats, function(k,events) {
          t.data[k].events=events;
          // Important conversations
          each(t.data[k].events, function(i,a) {
            a.startdate=a.startdate.toDateEn(true);
            a.enddate=a.enddate.toDateEn(true);
            if (a.repeat_until!=null)
              a.repeat_until=a.repeat_until.toDateEn(false);
          });

          t.data[k].status="loaded";
          if (!t.data[k].hide) {
            t.showEventsOnCal(k);
          }
        });
      }
    }
    else alert("Fehler: "+status);
  });
};

CalCCType.prototype.showEventsOnCal = function(k) {
  var t = this;
  var cs_events = mapEvents(t.data[k].events, true);

  t.data[k].current_CalEvents={container:t, category_id:k, color:"black", editable:categoryEditable(k),
      events:function (start, end, timezone, callback) {
        callback(_getEventsFromDate(cs_events, start, end));
      }
  };
  send2Calendar("addEventSource",t.data[k].current_CalEvents);
};


CalCCType.prototype.getName = function(category_id) {
  return masterData.category[category_id].bezeichnung;
};


//---------------------------------------------------------------------------------------------------------
//Der CalBirthday-Type l�dt die Daten aus der CS_EVENT-Tabelle
//---------------------------------------------------------------------------------------------------------
function CalBirthdayType() {
CalSourceType.call(this);
}
CalBirthdayType.prototype = new Temp();
var calBirthdayType = new CalBirthdayType();

CalBirthdayType.prototype.jsonCall = function(ids) {
  var t=this;
  var id=ids[0];
  churchInterface.jsendRead({func:"getBirthdays"}, function(ok, json) {
    if (ok) {
      t.data[id].events=json;
      t.data[id].status="loaded";
      if (t.data[id].status!="hide") {
        t.showEventsOnCal(id);
      }
    }
    else alert("Fehler: "+status);
  });
};
CalBirthdayType.prototype.getName = function(category_id) {
  return "Geburtstage (Gruppe)";
};
CalBirthdayType.prototype.showEventsOnCal = function(id) {
  var t=this;
  var d = new Date();
  var cs_events= new Array();
  if (t.data[id].events!=null) {
    each(t.data[id].events, function(k,a) {
      for (var i=-1;i<=1;i++) {
        var o=Object();
        o.title= a.name;
        o.allDay= true;
        var b = a.birthday.toDateEn();
        b.setYear(d.getFullYear()+i);
        o.start= b;
        _addEventsToDateIndex(cs_events, o);
      }
    });
  }
  t.data[id].current_CalEvents={container:t, category_id:id, color:"lightblue", editable:false,
      events:function (start, end, timezone, callback) {
        callback(_getEventsFromDate(cs_events, start, end));
      }
  };
  send2Calendar("addEventSource",t.data[id].current_CalEvents);
};

//---------------------------------------------------------------------------------------------------------
//Der CalAllBirthday-Type nutzt den CalBirthday, lädt nur alle!
//---------------------------------------------------------------------------------------------------------
function CalAllBirthdayType() {
  CalBirthdayType.call(this);
}
Temp.prototype = CalBirthdayType.prototype;
CalAllBirthdayType.prototype = new Temp();
calAllBirthdayType = new CalAllBirthdayType();

CalAllBirthdayType.prototype.jsonCall = function(ids) {
  var t=this;
  var id=ids[0];
  churchInterface.jsendRead({func:"getBirthdays", all:true}, function(ok, json) {
    if (ok) {
      t.data[id].events=json;
      t.data[id].status="loaded";
      if (t.data[id].status!="hide") {
        t.showEventsOnCal(id);
      }
    }
    else alert("Fehler: "+status);
  });
};
CalAllBirthdayType.prototype.getName = function(category_id) {
  return "Geburtstage (Alle)";
};



//---------------------------------------------------------------------------------------------------------
//Der CalResource-Type l�dt die Daten aus der cs_resource-Tabelle
//---------------------------------------------------------------------------------------------------------
function CalMyServicesType() {
  CalSourceType.call(this);
}
Temp.prototype = CalSourceType.prototype;

CalMyServicesType.prototype = new Temp();
var calMyServicesType = new CalMyServicesType();

CalMyServicesType.prototype.jsonCall = function(id) {
  var t=this;
  churchInterface.jsendRead({func:"getMyServices"}, function(ok, json) {
     if (ok) {
       t.data[id].events=json;
       t.data[id].status="loaded";
       if (t.data[id].status!="hide") {
         t.showEventsOnCal(id);
       }
     }
     else alert("Fehler: "+status);
  });
};


CalMyServicesType.prototype.showEventsOnCal = function(id) {
  var t=this;
  var cr = new Array();
  each(t.data[id].events, function(i,a) {
    var o=Object();
    if (a.zugesagt_yn==1)
      o.title=a.dienst+" ("+a.servicegroup+")";
    else
      o.title="Anfrage: "+a.dienst+" ("+a.servicegroup+")";
    o.title=o.title+" "+a.event;
    o.start=a.startdate.toDateEn(true);
    o.end=a.enddate.toDateEn(true);
    o.allDay= false;
    _addEventsToDateIndex(cr, o);
  });
  if (t.data[id].status!="hide") {
    t.data[id].status="loaded";
    t.data[id].current_CalEvents={container:t, category_id:id, color:"blue", editable:false,
        events:function (start, end, timezone, callback) {
          callback(_getEventsFromDate(cr, start, end));
        }
    };
    send2Calendar("addEventSource",t.data[id].current_CalEvents);
  }
};

CalMyServicesType.prototype.getName = function(category_id) {
  return "Meine Dienste";
};



//---------------------------------------------------------------------------------------------------------
//Der CalAbsent-Type l�dt die Daten aus der cs_Absent-Tabelle
//Es werden nur die Abwesenheiten geholt, f�r die auch aktivierte Kalender habe
//---------------------------------------------------------------------------------------------------------
function CalAbsentsType() {
  CalSourceType.call(this);
}
Temp.prototype = CalSourceType.prototype;
CalAbsentsType.prototype = new Temp();
var calAbsentsType = new CalAbsentsType();
CalAbsentsType.prototype.jsonCall = function(id) {
  var t=this;
  var cals=churchcore_getArrStrAsArray(masterData.settings.filterGruppenKalender);
  if (cals.length>0) {
    churchInterface.jsendRead({func:"getAbsents", cal_ids:cals}, function(ok, json) {
       if (ok) {
         t.data[id].events=json;
         t.data[id].status="loaded";
         if (t.data[id].status!="hide") {
           t.showEventsOnCal(id);
         }
       }
       else alert("Fehler: "+status);
    });
  }
};
CalAbsentsType.prototype.showEventsOnCal = function(id) {
  var t=this;
  var cr= new Array();
  each(t.data[id].events, function(i,a) {
    var o=Object();
    o.id=a.p_id;
    o.title=a.vorname+" "+a.name;
    o.start=a.startdate.toDateEn(true);
    o.end=a.enddate.toDateEn(true);
    if (masterData.absent_reason[a.reason_id]!=null) {
      if (masterData.absent_reason[a.reason_id].color!="")
        o.color=masterData.absent_reason[a.reason_id].color;
      o.bezeichnung=masterData.absent_reason[a.reason_id].bezeichnung;
    }
    if ((o.start.getHours()==0) && (o.end.getHours()==0))
      o.allDay=true;
    else
      o.allDay= false;
    _addEventsToDateIndex(cr, o);
  });
  if (t.data[id].status!="hide") {
    t.data[id].status="loaded";
    t.data[id].current_CalEvents={container:t, category_id:id, color:"lightgreen", editable:false,
        events:function (start, end, timezone, callback) {
          callback(_getEventsFromDate(cr, start, end));
        }
    };
    send2Calendar("addEventSource",t.data[id].current_CalEvents);
  }
};


CalAbsentsType.prototype.getName = function(category_id) {
return "Abwesenheiten ";
};


//---------------------------------------------------------------------------------------------------------
//Der CalMyAbsent-Type l�dt die Daten aus der cs_Absent-Tabelle
//Es werden nur die Abwesenheiten geholt, f�r die auch aktivierte Kalender habe
//---------------------------------------------------------------------------------------------------------
function CalMyAbsentsType() {
  CalAbsentsType.call(this);
}
Temp.prototype = CalAbsentsType.prototype;
CalMyAbsentsType.prototype = new Temp();
var calMyAbsentsType = new CalMyAbsentsType();

CalMyAbsentsType.prototype.jsonCall = function(id) {
  var t=this;
  churchInterface.jsendRead({func:"getAbsents", person_id:masterData.user_pid}, function(ok, json) {
    if (ok) {
      t.data[id].events=json;
      t.data[id].status="loaded";
      if (t.data[id].status!="hide") {
        t.showEventsOnCal(id);
      }
    }
    else alert("Fehler: "+status);
 });
};

CalMyAbsentsType.prototype.getName = function(category_id) {
  return "Meine Abwesenheiten ";
};


// NEXT IS CURRENTY NOT IN USE BUT PRESERVE FOR LATER PERHAPS

//---------------------------------------------------------------------------------------------------------
//Der CalResource-Type l�dt die Daten aus der cs_resource-Tabelle
//---------------------------------------------------------------------------------------------------------
/*function CalResourceType() {
CalSourceType.call(this);
}
Temp.prototype = CalSourceType.prototype;

CalResourceType.prototype = new Temp();
var calResourceType = new CalResourceType();

CalResourceType.prototype.jsonCall = function(ids) {
var t=this;
churchInterface.jsendRead({func:"getResource", resource_id:ids}, function(ok, json) {
 if (ok) {
   // Erst nach Ressource_id
   each(json, function(k,bookings) {
     var cr= new Array();
     t.data[k].events=bookings;
     each(t.data[k].events, function(i,a) {
       a.startdate=a.startdate.toDateEn(true);
       a.enddate=a.enddate.toDateEn(true);
       if (a.repeat_until!=null)
         a.repeat_until=a.repeat_until.toDateEn(false);
       var diff=a.enddate.getTime()-a.startdate.getTime();
       each(churchcore_getAllDatesWithRepeats(a), function(k,d) {
         var o=Object();
         //o.id= a.id,
         var repeat=(a.repeat_id>0?'{R}':"");
         o.title= a.bezeichnung+repeat+" ("+masterData.resources[a.resource_id].bezeichnung+")";
         if (a.status_id==1) o.title='<font color="lightgray">'+o.title+"?</font>";
         else if (a.status_id==3) o.title='<span style="color:lightgray;text-decoration:line-through;">'+o.title+"</span>";
         o.status=a.status;
         o.start= d.startdate;
         o.end = d.enddate;
         o.allDay= false;
         cr.push(o);
       });
     });
     if (t.data[k].status!="hide") {
       t.data[k].status="loaded";
       t.data[k].current_CalEvents={container:t, category_id:k, events:cr, color:"green", editable:false};
       send2Calendar("addEventSource",t.data[k].current_CalEvents);
     }
   });
 }
 else alert("Fehler: "+status);
});
};

CalResourceType.prototype.getName = function(category_id) {
return masterData.resources[category_id].bezeichnung;
};

*/
