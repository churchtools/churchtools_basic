
// CalSourceType ist das Grund-Object. Es ist pro Quell-System einmal zu vererben. 
// Innerhalb des Objektes werden dann die Events gehalten separiert nach Kategorie
function CalSourceType() {
  var t=this;
  // Hält die Daten nach category_id auf
  t.data = new Object();
  // Timer zum Laden der Daten. Erst werden alle Categorien als "needData" markiert und dann lädt er
  t.timer=null;
}

CalSourceType.prototype.prepareCategory = function(category_id, refresh) {
  var t=this;
  if (refresh==null) refresh=false;
  if ((refresh) || (t.data[category_id]==null)) {
    if ((t.data[category_id]!=null) && (t.data[category_id].current_CalEvents!=null))
      send2Calendar("removeEventSource", t.data[category_id].current_CalEvents);
    t.data[category_id]=new Object();
    t.data[category_id].status="new";
    t.data[category_id].events=new Object();
  }
};

CalSourceType.prototype.needData = function(category_id, refresh) {
  var t=this;
  if (refresh==null) refresh=false;
  t.prepareCategory(category_id, refresh);
  if (t.data[category_id].status!="loaded") {
    t.data[category_id].status="needData";
    t.data[category_id].name=t.getName(category_id);
    t.triggerCollectTimer();
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
  t.data[category_id].status="hide";
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
    $.each(t.data, function(k,a) {
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
  $.each(t.data, function(k,a) {
    if (a.status=="needData") ids.push(k);
  });
  if (ids.length>0) {
    t.jsonCall(ids);
  }      
};

CalSourceType.prototype.jsonCall = function(ids) {
  throw new Exception("Not implemented!");
};

Temp.prototype = CalSourceType.prototype;

// ---------------------------------------------------------------------------------------------------------
// Der CalCC-Type lädt die Daten aus der CC_CAL-Tabelle 
//---------------------------------------------------------------------------------------------------------
function CalCCType() {
  CalSourceType.call(this);
}
CalCCType.prototype = new Temp();
var calCCType = new CalCCType();
CalCCType.prototype.jsonCall = function(ids) {
  var t=this;
  churchInterface.jsendRead({func:"getCalPerCategory", category_ids:ids}, function(ok, cats) {
    if (ok) {
      if (cats!=null) {
        $.each(cats, function(k,events) {
          t.data[k].events=events;
          $.each(t.data[k].events, function(i,a) {
            a.startdate=a.startdate.toDateEn(true);
            a.enddate=a.enddate.toDateEn(true);
            if (a.repeat_until!=null)
              a.repeat_until=a.repeat_until.toDateEn(false);        
          });
          if (t.data[k].status!="hide") {
            t.data[k].status="loaded";
            var cs_events=mapEvents(t.data[k].events, true);
            
            t.data[k].current_CalEvents={container:t, category_id:k, events:cs_events, color:"black", editable:categoryEditable(k)};            
            send2Calendar("addEventSource",t.data[k].current_CalEvents);
          }
        });
      }
    }
    else alert("Fehler: "+status);
  });      
};

CalCCType.prototype.getName = function(category_id) {
  return masterData.category[category_id].bezeichnung;  
};


//---------------------------------------------------------------------------------------------------------
//Der CalBirthday-Type lädt die Daten aus der CS_EVENT-Tabelle 
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
      var d = new Date();
      var cs_events= new Array();
      if (json!=null) {
        $.each(json, function(k,a) {
          for (var i=-1;i<=1;i++) {
            var o=Object();
            o.title= a.name;
            o.allDay= true;
            var b = a.birthday.toDateEn();          
            b.setYear(d.getFullYear()+i);
            o.start= b;
            cs_events.push(o);            
          }
        });
      }
      if (t.data[id].status!="hide") {
        t.data[id].status="loaded";
        t.data[id].current_CalEvents={container:t, category_id:id, events:cs_events, color:"lightblue", editable:false};            
        send2Calendar("addEventSource",t.data[id].current_CalEvents);
      }
    }
    else alert("Fehler: "+status);
  });      
};
CalBirthdayType.prototype.getName = function(category_id) {
  return "Geburtstage (Gruppe)";  
};


//---------------------------------------------------------------------------------------------------------
//Der CalAllBirthday-Type lädt die Daten aus der CS_EVENT-Tabelle 
//---------------------------------------------------------------------------------------------------------
function CalAllBirthdayType() {
CalSourceType.call(this);
}
CalAllBirthdayType.prototype = new Temp();
var calAllBirthdayType = new CalAllBirthdayType();
CalAllBirthdayType.prototype.jsonCall = function(ids) {
var t=this;
var id=ids[0];
churchInterface.jsendRead({func:"getBirthdays", all:true}, function(ok, json) {
  if (ok) {
    var d = new Date();
    var cs_events= new Array();
    var i=10;
    if (json!=null) {
      $.each(json, function(k,a) {
        if (a.birthday!=null) {
          for (var i=-1;i<=1;i++) {
            var o=Object();
            o.title= a.name;
            o.allDay= true;
            var b = a.birthday.toDateEn();          
            b.setYear(d.getFullYear()+i);
            o.start= b;
            cs_events.push(o);
          }
        }
      });
      if (t.data[id].status!="hide") {
        t.data[id].status="loaded";
        t.data[id].current_CalEvents={container:t, category_id:id, events:cs_events, color:"lightblue", editable:false};            
        send2Calendar("addEventSource",t.data[id].current_CalEvents);
      }
    }
  }
  else alert("Fehler: "+status);
});      
};
CalAllBirthdayType.prototype.getName = function(category_id) {
return "Geburtstage (Alle)";  
};



//---------------------------------------------------------------------------------------------------------
//Der CalResource-Type lädt die Daten aus der cs_resource-Tabelle 
//---------------------------------------------------------------------------------------------------------
function CalResourceType() {
  CalSourceType.call(this);
}
CalResourceType.prototype = new Temp();
var calResourceType = new CalResourceType();
CalResourceType.prototype.jsonCall = function(ids) {
  var t=this;
  churchInterface.jsendRead({func:"getResource", resource_id:ids}, function(ok, json) {
   if (ok) {
     // Erst nach Ressource_id
     $.each(json, function(k,bookings) {
       var cr= new Array();
       t.data[k].events=bookings;
       $.each(t.data[k].events, function(i,a) {
         a.startdate=a.startdate.toDateEn(true);
         a.enddate=a.enddate.toDateEn(true);
         if (a.repeat_until!=null)
           a.repeat_until=a.repeat_until.toDateEn(false);    
         var diff=a.enddate.getTime()-a.startdate.getTime();
         $.each(churchcore_getAllDatesWithRepeats(a), function(k,d) {
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



//---------------------------------------------------------------------------------------------------------
//Der CalResource-Type lädt die Daten aus der cs_resource-Tabelle 
//---------------------------------------------------------------------------------------------------------
function CalMyServicesType() {
CalSourceType.call(this);
}
CalMyServicesType.prototype = new Temp();
var calMyServicesType = new CalMyServicesType();
CalMyServicesType.prototype.jsonCall = function(id) {
  var t=this;
  churchInterface.jsendRead({func:"getMyServices"}, function(ok, json) {
     if (ok) {
       var cr= new Array();
       t.data[id].events=json;
       $.each(t.data[id].events, function(i,a) {
         var o=Object();
         if (a.zugesagt_yn==1)
           o.title=a.dienst+" ("+a.servicegroup+")"; 
         else       
           o.title="Anfrage: "+a.dienst+" ("+a.servicegroup+")"; 
         o.title=o.title+" "+a.event;
         o.start=a.startdate.toDateEn(true);
         o.end=a.enddate.toDateEn(true);
         o.allDay= false;
         cr.push(o);            
       });
       if (t.data[id].status!="hide") {
         t.data[id].status="loaded";
         t.data[id].current_CalEvents={container:t, category_id:id, events:cr, color:"blue", editable:false};            
         send2Calendar("addEventSource",t.data[id].current_CalEvents);
       }
     }
     else alert("Fehler: "+status);
  });      
};

CalMyServicesType.prototype.getName = function(category_id) {
  return "Meine Dienste";  
};



//---------------------------------------------------------------------------------------------------------
//Der CalAbsent-Type lädt die Daten aus der cs_Absent-Tabelle 
//Es werden nur die Abwesenheiten geholt, für die auch aktivierte Kalender habe
//---------------------------------------------------------------------------------------------------------
function CalAbsentsType() {
  CalSourceType.call(this);
}
CalAbsentsType.prototype = new Temp();
var calAbsentsType = new CalAbsentsType();
CalAbsentsType.prototype.jsonCall = function(id) {
  var t=this;
  cals=new Array();
  $.each(filter["filterGruppenKalender"].selected, function(k,a) {
    if (a>100) cals.push(a-100);
  });
  if (cals.length>0) {
    churchInterface.jsendRead({func:"getAbsents", cal_ids:cals}, function(ok, json) {
       if (ok) {
         var cr= new Array();
         t.data[id].events=json;
         $.each(t.data[id].events, function(i,a) {
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
           cr.push(o);            
         });
         if (t.data[id].status!="hide") {
           t.data[id].status="loaded";
           t.data[id].current_CalEvents={container:t, category_id:id, events:cr, color:"lightgreen", editable:false};            
           send2Calendar("addEventSource",t.data[id].current_CalEvents);
         }
       }
       else alert("Fehler: "+status);
    });
  }
};

CalAbsentsType.prototype.getName = function(category_id) {
return "Abwesenheiten ";  
};


//---------------------------------------------------------------------------------------------------------
//Der CalMyAbsent-Type lädt die Daten aus der cs_Absent-Tabelle 
//Es werden nur die Abwesenheiten geholt, für die auch aktivierte Kalender habe
//---------------------------------------------------------------------------------------------------------
function CalMyAbsentsType() {
  CalSourceType.call(this);
}
CalMyAbsentsType.prototype = new Temp();
var calMyAbsentsType = new CalMyAbsentsType();
CalMyAbsentsType.prototype.jsonCall = function(id) {
  var t=this;
  churchInterface.jsendRead({func:"getAbsents", person_id:masterData.user_pid}, function(ok, json) {
     if (ok) {
       var cr= new Array();
       t.data[id].events=json;
       $.each(t.data[id].events, function(i,a) {
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
         cr.push(o);            
       });
       if (t.data[id].status!="hide") {
         t.data[id].status="loaded";
         t.data[id].current_CalEvents={container:t, category_id:id, events:cr, color:"lightgreen", editable:false};            
         send2Calendar("addEventSource",t.data[id].current_CalEvents);
       }
     }
     else alert("Fehler: "+status);
  });
};

CalMyAbsentsType.prototype.getName = function(category_id) {
  return "Meine Abwesenheiten ";  
};

