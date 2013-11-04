masterData=new Object();
calendar=null;
currentEvent=null;
allEvents=null;
allPersons=null;
allData=new Object();
viewName="calView";
filterName="";
monthNames= ['Januar', 'Februar', 'M&auml;rz', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
filterCategoryIds=null;
var filter=new Object();

var embedded=false;
var minical=false;
var max_entries=50;

function mapEvents(allEvents) {
  var cs_events= new Array();
  $.each(allEvents, function(k,a) {
    if ((filterCategoryIds==null) || (churchcore_inArray(a.category_id, filterCategoryIds))) {
      if ((!embedded) || (a.intern_yn==0)) {
        $.each(churchcore_getAllDatesWithRepeats(a), function(k,d) {
          var o=Object();
          o.id= a.id;  // Id muss eindeutig sein, sonst macht er daraus einen Serientermin!
          o.title= a.bezeichnung;
          if ((a.notizen!=null) && (a.notizen!="")) o.notizen=a.notizen;
          if ((a.link!=null) && (a.link!="")) o.link=a.link;
          if ((a.ort!=null) && (a.ort!='')) o.title=o.title+' ('+a.ort+')';
     //     o.editable=editable;
          o.start= d.startdate;
            o.end = d.enddate;
          // Tagestermin?
          o.allDay=churchcore_isAllDayDate(o.start, o.end);
          
          if ((a.category_id!=null) && (masterData.category[a.category_id].color!=null))
            o.color=masterData.category[a.category_id].color;
          cs_events.push(o);              
        });
      }
    }
  });
  return cs_events;
}

function getEventFromEventSource(event) {
  if ((event.source.container.data==null) || (event.source.category_id==0) 
             || (event.source.container.data[event.source.category_id]==null)) 
    return null;
  else 
    return event.source.container.data[event.source.category_id].events[event.id];
}

function _eventDrop(event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view ) {
  var myevent=getEventFromEventSource(event);
  if (myevent==null) {
    alert("Fehler oder keine Rechte!");
    revertFunc();
    return null;
  }    
  
  myevent.startdate.addDays(dayDelta);
  myevent.enddate.addDays(dayDelta);
  myevent.startdate.setMinutes(myevent.startdate.getMinutes()+minuteDelta);
  myevent.enddate.setMinutes(myevent.enddate.getMinutes()+minuteDelta);
  var o = new Object();
  o.func="updateEvent";
  o.startdate=myevent.startdate;
  o.enddate=myevent.enddate;
  o.id=event.id;
  o.bookings=myevent.bookings;
  o.bezeichnung=myevent.bezeichnung
  o.category_id=myevent.category_id;
  if (allDay) {
    o.startdate=o.startdate.toStringDe(false).toDateDe(false);
    // Wenn er nur einen Tag geht, dann ist wohl manchmal enddate==null
    if (o.enddate==null) {
      o.enddate=new Date(o.startdate);
    }
    else
      o.enddate=o.enddate.toStringDe(false).toDateDe(false);
  }
  else if (o.enddate==null) {
    o.enddate=new Date(o.startdate);
    o.enddate.setMinutes(o.startdate.getMinutes()+90);
    event.end=o.enddate;
  }
  // Wenn es Wiederholungstermine gibt, kann es bei Verschiebung notwendig sein neu zu rendern wegen Ausnahmetagen!
  if ((myevent.repeat_id>0) && (dayDelta!=0)) {
    calCCType.refreshView(myevent.category_id); 
  }

  churchInterface.jsendWrite(o, function(ok, data) {
    if (!ok) {
      alert(data);
      revertFunc();
    }
  }, true, false);
}

function _eventResize(event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view) {
  var myevent=getEventFromEventSource(event);
  if ((myevent==null) || (masterData.auth["edit category"]==null) || (masterData.auth["edit category"][event.source.category_id]==null)) {
    alert("Fehler oder keine Rechte!");
    revertFunc();
    return null;
  }    

  if (myevent!=null) {
    myevent.enddate.addDays(dayDelta);
    myevent.enddate.setMinutes(myevent.enddate.getMinutes()+minuteDelta);
    churchInterface.jsendWrite({func:"updateEvent", id:event.id, startdate:myevent.startdate,
        enddate:myevent.enddate, bezeichnung:event.title, category_id:myevent.category_id,
         bookings:myevent.bookings}, function(ok, data) {
        if (!ok) {
          alert(data);
          revertFunc();
        }
    }, true, false);
  }
}
  
function _select(start, end, allDay, a, view) {
  var event = new Object();
  event.startdate=start;
  event.enddate=end;
  editEvent(event, view.name=="month");
  calendar.fullCalendar('unselect');
}

function _renderViewChurchResource(elem) {  
  if (masterData.resources==null) {
    churchInterface.jsendRead({func:"getMasterData"}, function(ok, data) {
      if (!ok) alert("Fehler beim Holen der Ressourcen: "+data);
      else {
        masterData.resources=data.res;
        masterData.resourcesTypes=data.resTypes;
        masterData.status=data.status;
        if (masterData.resources==null) masterData.resources=new Object();
        _renderEditEventContent(elem, currentEvent);
      }
    }, null, null, "churchresource");   
    elem.find("#cal_content").html("Lade Daten...");
  }
  else {
    // Baue erst ein schönes Select zusammen
    var arr=new Array();
    var sortkey=0;
    $.each(churchcore_sortMasterData(masterData.resourcesTypes, "sortkey"), function(k,a) {
      arr.push({id:"", bezeichnung:'-- '+a.bezeichnung+' --'});
      $.each(churchcore_sortMasterData(masterData.resources, "sortkey"), function(i,b) {
        if (b.resourcetype_id==a.id) {
          arr.push({id:b.id, bezeichnung:b.bezeichnung});
        }
      });
    });
    var minutes=new Array();
    minutes.push({id:0, bezeichnung:'-'});
    minutes.push({id:15, bezeichnung:'15 Minuten'});
    minutes.push({id:30, bezeichnung:'30 Minuten'});
    minutes.push({id:45, bezeichnung:'45 Minuten'});
    minutes.push({id:60, bezeichnung:'1 Stunde'});
    minutes.push({id:90, bezeichnung:'1,5 Stunden'});
    minutes.push({id:120, bezeichnung:'2 Stunden'});
    minutes.push({id:150, bezeichnung:'2,5 Stunden'});
    minutes.push({id:180, bezeichnung:'3 Stunden'});
    minutes.push({id:240, bezeichnung:'4 Stunden'});
    minutes.push({id:300, bezeichnung:'5 Stunden'});
    minutes.push({id:360, bezeichnung:'6 Stunden'});
    minutes.push({id:60*24, bezeichnung:'1 Tag'});
    
    var form = new CC_Form();
    if (currentEvent.minpre==null) {
      currentEvent.minpre=0; currentEvent.minpost=0;
    }
    form.addSelect({cssid:"ressource_new",  htmlclass:"resource", freeoption:true, label:"Ressource ausw&auml;hlen", data:arr, sort:false});
    form.addSelect({label:"Im Vorfeld buchen", cssid:"min_pre_new", sort:false, selected:currentEvent.minpre, data:minutes});
    form.addSelect({label:"Nachher buchen", cssid:"min_post_new", sort:false, selected:currentEvent.minpost, data:minutes});
    form.addButton({cssid:"ressource-add",  controlgroup:true, htmlclass:"add", label:"Ressource hinzuf&uuml;gen"});

    if (currentEvent.bookings!=null) {
      form.addHtml('<legend>Vorhandene Buchungen</legend>');
      form.addHtml('<div class="w_ell"><table class="table table-condensed"><tr><th>Ressource<th>Vorher<th>Nachher<th>Status<th>');
      $.each(currentEvent.bookings, function(k,a) {
        form.addHtml('<tr><td>');
        form.addHtml(masterData.resources[a.resource_id].bezeichnung);
        form.addHtml('<td>');
        form.addSelect({type:"small", cssid:"min-pre-"+a.resource_id, controlgroup:false, sort:false, selected:a.minpre, data:minutes});
        form.addHtml('<td>');
        form.addSelect({type:"small", cssid:"min-post-"+a.resource_id, controlgroup:false, sort:false, selected:a.minpost, data:minutes});
        form.addHtml('<td>');
        if (a.status_id!=null) 
          form.addHtml('<i>'+masterData.status[a.status_id].bezeichnung+'</i>');
        form.addHtml('<td>');
        if (a.status_id!=99)
          form.addImage({src:"trashbox.png", cssid:"trash", width:20, data:[{name:"id", value:a.resource_id}]});          
      });
      form.addHtml('</table></div>');
    }
    
    elem.find("#cal_content").html(form.render(null, "horizontal"));
    
    elem.find("#ressource-add").click(function() {
      if (elem.find("select.resource").val()>0) {
        if (currentEvent.bookings==null) currentEvent.bookings=new Object();
        currentEvent.minpre=elem.find("#min_pre_new").val();
        currentEvent.minpost=elem.find("#min_post_new").val();        
        currentEvent.bookings[elem.find("select.resource").val()]={resource_id:elem.find("select.resource").val(), 
                  minpre:currentEvent.minpre, minpost:currentEvent.minpost, 
                  status_id:1};
        _renderEditEventContent(elem, currentEvent);
      } 
    });
    elem.find("select").change(function() {
      if ($(this).attr("id").indexOf("min-pre-")==0) {
        currentEvent.bookings[$(this).attr("id").substr(8,99)].minpre=$(this).val();
        _renderEditEventContent(elem, currentEvent);
      }
      else if ($(this).attr("id").indexOf("min-post-")==0) {
        currentEvent.bookings[$(this).attr("id").substr(9,99)].minpost=$(this).val();
        _renderEditEventContent(elem, currentEvent);
      }
    });
    elem.find("#trash").click(function() {
      currentEvent.bookings[$(this).attr("data-id")].status_id=99;
      _renderEditEventContent(elem, currentEvent);
      return false;
    });    
  }  
}
 
function currentEvent_addException(date) {
  if (currentEvent.exceptions==null) currentEvent.exceptions=new Object();
  if (currentEvent.exceptionids==null) currentEvent.exceptionids=0;
  currentEvent.exceptionids=currentEvent.exceptionids-1;
  currentEvent.exceptions[currentEvent.exceptionids]
        ={id:currentEvent.exceptionids, except_date_start:date.toStringEn(), except_date_end:date.toStringEn()};
}

function _renderEditEventContent(elem, currentEvent) {
  var rows = new Array();
  if (currentEvent.view=="view-main") {
  
    rows.push('<form class="form-horizontal">');
  
    rows.push(form_renderInput({
      value:currentEvent.bezeichnung, 
      cssid:"inputBezeichnung", 
      label:"Bezeichnung"
    }));
  
    rows.push(form_renderInput({
      value:currentEvent.ort, 
      cssid:"inputOrt", 
      label:"Ort",
      placeholder:""
    }));
    if ((masterData.category[currentEvent.category_id]!=null) && 
           (masterData.category[currentEvent.category_id].oeffentlich_yn==1)) {
      rows.push(form_renderCheckbox({
        label:" Termin nur intern sichtbar",
        controlgroup:true,
        controlgroup_class:"",
        cssid:"inputIntern",
        checked:(currentEvent.intern_yn!=null && currentEvent.intern_yn==1?true:false)
      }));
    }
    
    rows.push('<div id="dates"></div>');  
    rows.push('<div id="wiederholungen"></div>');
    
    var e_summe=new Array();
    var e=new Array();
    e.push({id:-1, bezeichnung:"-- Pers&ouml;nliche Kalender --"});
    $.each(churchcore_sortMasterData(masterData.category), function(k,a) {
      if ((a.privat_yn==1) && (categoryEditable(a.id))) e.push(a);
    });
    if (e.length>1) e_summe=e_summe.concat(e);

    var e=new Array();
    e.push({id:-1, bezeichnung:"-- Gruppenkalender --"});
    $.each(churchcore_sortMasterData(masterData.category), function(k,a) {
      if ((a.oeffentlich_yn==0) && (a.privat_yn==0) && (categoryEditable(a.id))) e.push(a);
    });
    if (e.length>1) e_summe=e_summe.concat(e);

    var e=new Array();
    e.push({id:-1, bezeichnung:"-- Gemeindekalender --"});
    $.each(churchcore_sortMasterData(masterData.category), function(k,a) {
      if ((a.oeffentlich_yn==1) && (categoryEditable(a.id))) e.push(a);
    });
    if (e.length>1) e_summe=e_summe.concat(e);
    
    
    rows.push(form_renderSelect({
      //data:masterData.category, 
      data:e_summe,
      sort:false,
      cssid:"inputCategory", 
      selected:currentEvent.category_id,
      label:"Kalender"
    }));  
    rows.push(form_renderTextarea({
      data:currentEvent.notizen,
      label:"Weitere Infos",
      cssid:"inputNote", 
      rows:2,
      cols:100
    }));
    rows.push(form_renderInput({
      value:currentEvent.link,
      label:"Link",
      cssid:"inputLink" 
    }));
  
    rows.push('</form>');
        
    if (currentEvent.id!=null) rows.push('<p align="right"><small>#'+currentEvent.id+'</small>');

    elem.find("#cal_content").html(rows.join(""));

    form_renderDates({elem:$("#dates"), data:currentEvent,
      deleteException:function(exc) {
        delete currentEvent.exceptions[exc.id];
      },
      addException:function(options, date) {
        currentEvent_addException(date.toDateDe());
        return currentEvent;
      },
      deleteAddition:function(add) {
        delete currentEvent.additions[add.id];
      },
      addAddition:function(options, date, with_repeat_yn) {
        if (currentEvent.additions==null) currentEvent.additions=new Object();
        if (currentEvent.exceptionids==null) currentEvent.exceptionids=0;
        currentEvent.exceptionids=currentEvent.exceptionids-1;
        currentEvent.additions[currentEvent.exceptionids]
              ={id:currentEvent.exceptionids, add_date:date.toDateDe().toStringEn(), with_repeat_yn:with_repeat_yn};
        return currentEvent;
      }
    });      
    
    $("#inputBezeichnung").focus();    
    
    elem.find("#inputCategory").change(function() {
      churchInterface.jsendWrite({func:"saveSetting", sub:"category_id", val:$(this).val()});
      masterData.settings.category_id=$(this).val(); 
      currentEvent.category_id=$(this).val();
      _renderEditEventNavi(elem, currentEvent);
    });
  }
  else if (currentEvent.view=="view-invite") {
    rows.push('<legend>Besprechungsanfrage an alle Kalenderteilnehmer</legend>');
    elem.find("#cal_content").html(rows.join(""));
  }     
  else if (currentEvent.view=="view-churchresource") {
    _renderViewChurchResource(elem);    
  }     
  else if (currentEvent.view=="view-churchservice") {
    if (masterData.eventTemplate==null) {
      churchInterface.jsendRead({func:"getEventTemplates"}, function(ok, data) {
        if (!ok) alert("Fehler beim Holen der Templates: "+data);
        else {
          masterData.eventTemplate=data;
          if (masterData.eventTemplate==null) masterData.eventTemplate=new Object();
          _renderEditEventContent(elem, currentEvent);
        }
      }, null, null, "churchservice"); 
    }
    else {
      if (currentEvent.event_id==null) {
        var form = new CC_Form();
        form.addCaption({text:'<p>Hier kann direkt f&uuml;r <i>'+masterData.churchservice_name+'</i>'
                   +' ein Event angelegt werden. Bitte dazu eine Vorlage ausw&auml;hlen.'});
        form.addSelect({cssid:"eventTemplate", freeoption:true, selected:currentEvent.eventTemplate, label:"Event-Vorlage ausw&auml;hlen", data:masterData.eventTemplate});
        rows.push(form.render(null, "horizontal"));
      } 
      else if (currentEvent.copyevent) {
        var form = new CC_Form();
        form.addCheckbox({label:"Alle zugeh&ouml;rigen Event-Daten aus "+masterData.churchservice_name+" mit kopieren",
          checked:currentEvent.copychurchservice, cssid:"copychurchservice"}  );        
        form.addSelect({cssid:"eventTemplate", freeoption:true, selected:currentEvent.eventTemplate, label:"Eine Event-Vorlage w&auml;hlen", data:masterData.eventTemplate});
        rows.push(form.render(null, "horizontal"));
      }
      else {
        rows.push("Der Eintrag ist mit <i>"+masterData.churchservice_name+'</i> verbunden. <br><br><a class="btn" href="?q=churchservice&id='+currentEvent.event_id+'">Event aufrufen</a>');
      }
      elem.find("#cal_content").html(rows.join(""));
      elem.find("#copychurchservice").change(function() {
        currentEvent.copychurchservice=$(this).attr("checked")=="checked";
        if (currentEvent.copychurchservice) {
          elem.find("#eventTemplate").val("");
          currentEvent.eventTemplate=null;
        }
      });
      elem.find("#eventTemplate").change(function() {
        elem.find("#copychurchservice").removeAttr("checked");
        currentEvent.copychurchservice=null;
        if ((currentEvent.repeat_id!=0) && ($(this).val()!="")) {
          alert("Leider geht das nicht bei Wiederholungsterminen! Hierzu bitte die Kopier-Funktion verwenden.");
          elem.find("#eventTemplate").val("")
          return;
        }
        currentEvent.eventTemplate=$(this).val();
      });
    }
  }     
}

function _renderEditEventNavi(elem, currentEvent) {
  var navi = new CC_Navi();
  navi.addEntry(currentEvent.view=="view-main","view-main","Kalender");
  /*if ((masterData.category[currentEvent.category_id].oeffentlich_yn==0)
        && (masterData.category[currentEvent.category_id].privat_yn==0))
    navi.addEntry(currentEvent.view=="view-invite","view-invite","Besprechungsanfrage");*/
  if ((masterData.category[currentEvent.category_id].privat_yn==0) && (masterData.auth["view churchservice"]))
    navi.addEntry(currentEvent.view=="view-churchservice","view-churchservice",masterData.churchservice_name);
  if (masterData.auth["view churchresource"])
    navi.addEntry(currentEvent.view=="view-churchresource","view-churchresource",masterData.churchresource_name);
  navi.renderDiv("cal_menu", churchcore_handyformat());
  
  elem.find("ul.nav a").click(function() {
    if (currentEvent.view=="view-main") getCalEditFields(currentEvent);
    currentEvent.view=$(this).attr("id");
    _renderEditEventNavi(elem, currentEvent);
    _renderEditEventContent(elem, currentEvent);  
  });
}

function getCalEditFields(o) {
  form_getDatesInToObject(o);      
  o.bezeichnung=$("#inputBezeichnung").val();
  o.ort=$("#inputOrt").val();
  o.intern_yn=($("#inputIntern").attr("checked")=='checked'?1:0);
  o.notizen=$("#inputNote").val();
  o.link=$("#inputLink").val();
  o.category_id=$("#inputCategory").val();
}

function saveEvent() {
  var o=currentEvent;
  var oldCat=o.category_id;
  if (currentEvent.view=="view-main")
    getCalEditFields(o);
  if ((currentEvent.repeat_id>0) && (currentEvent.eventTemplate!=null) && (currentEvent.eventTemplate!="")) {
    alert("So lange ein Termin von "+masterData.churchservice_name+" verbunden ist, kann daraus kein Wiederholungstermin erstellt werden.");
    return false;
  }
  else if ((currentEvent.repeat_id>0) && (currentEvent.event_id!=null)) {
    alert("Der Termin ist bereits mit "+masterData.churchservice_name+" verbunden. Es kann leider kein Wiederholungstermin erstellt werden.");
    return false;
  }
  if (currentEvent.id!=null) {
    o.func="updateEvent";
    o.currentEvent_id=currentEvent.id;
  }
  else
    o.func="createEvent";
  
  churchInterface.jsendWrite(o, function(ok, data) {
    if (!ok) alert("Fehler beim Anpassen des Events: "+data);
    else {
      if ((oldCat!=o.category_id) && (oldCat!=null)) 
        calCCType.needData(oldCat, true);
      calCCType.needData(o.category_id, true);
      if (o.bookings!=null) calResourceType.refreshView();
    }
  }, false, false);
  return true;
}

/**
 * event - Event wie in der DB
 * month - Monatsansicht=true
 * currentDate - Das Datum auf das geklickt wurde, nur bei Wiederholungsterminen kann es anders sein
 */
function editEvent(event, month, currentDate) {  

  // Clone object
  currentEvent = jQuery.extend({}, event);
  currentEvent.view="view-main";
  
  
  if ((month) && (currentEvent.allDay==null) && (currentEvent.startdate.getHours()==0) && (currentEvent.startdate.getDate()==currentEvent.enddate.getDate()))
    currentEvent.startdate.setHours(10);
  if (currentEvent.enddate==null) {
    currentEvent.enddate=new Date(currentEvent.startdate);
    // Wenn es kein Ganztagstermin ist, dann setze Ende 1h rauf
    if (currentEvent.startdate.getHours()>0)
      currentEvent.enddate.setHours(currentEvent.startdate.getHours()+1);
  }
  if (currentEvent.bezeichnung==null) currentEvent.bezeichnung="";
  if (currentEvent.category_id==null) currentEvent.category_id=masterData.settings.category_id;
  if (!categoryEditable(currentEvent.category_id)) {    
    $.each(masterData.category, function(k,a) {
      if (categoryEditable(k)) {
        currentEvent.category_id=k;
        return false;
      }
    });
    if (currentEvent.category_id==null) {
      if (masterData["edit category"]!=null) {
        alert("Um einen Termin anzulegen, muss erst ein Kalender erstellt werden!");
        editCategory(null, 0);
      }
      return null;
    }
  }
  var rows = new Array();
  
  
  
  rows.push('<div id="cal_menu"><br/></div>');
  rows.push('<div id="cal_content"></div>');
    
  
  var elem=form_showDialog((currentEvent.id==null?"Neuen Termin erstellen":"Termin editieren"), rows.join(""), 560, 600, {
    "Termin speichern": function() {
      //var o = new Object();
      if (saveEvent())
        $(this).dialog("close");
    }
  });
  

  
  _renderEditEventContent(elem, currentEvent);  
  
  
  if (currentEvent.id!=null) {
    // Erst mal checken, ob eine Wiederholung angeklickt wurde
    if ((currentDate!=null) && (currentEvent.startdate.toStringDe()!=currentDate.toStringDe())) {
      elem.dialog('addbutton', 'Nur aktuellen Termin entfernen', function() {
        if (confirm("Termin wirklich entfernen?")) {
          // Erstmal schauen, ob es vielleicht ein AdditionDate ist? (also manuell hinzugef�gt?)
          var additionDate=false;
          if (currentEvent.additions!=null) {
            $.each(currentEvent.additions, function(k,a) {
              if (a.add_date.toDateEn().toStringDe(false)==currentDate.toStringDe(false)) {
                additionDate=true;
                delete currentEvent.additions[k];
                churchInterface.jsendWrite({func:"delAddition", id:k});                                
                return false;
              }
            });           
            calCCType.refreshView(currentEvent.category_id);
          }
          if (!additionDate) {
            currentEvent_addException(currentDate);
            saveEvent();
          }

          elem.dialog("close");
        }
      });
      
    } 
    else {        
      //$('<i/>').html('Termin l&ouml;schen').text()/*
      elem.dialog('addbutton', 'Löschen', function() {
        delEvent(currentEvent, function() {
          elem.dialog("close");          
        });
      });
    }
  }
  elem.dialog('addbutton', 'Abbrechen', function() {
    $(this).dialog("close");
  });

  _renderEditEventNavi(elem, currentEvent);
 
}

function copyEvent(current_event) {
  var event=$.extend({},current_event);
  event.orig_id=event.id;
  event.id=null;
  event.copyevent=true;
  event.copychurchservice=false;
  editEvent(event, "week");  
}

function delEvent(event, func) {
  if (event.event_id!=null) {
    alert("Es sind in "+masterData.churchservice_name+" noch ein Event vorhanden bitte erst das löschen!");
    return;
  }
  if (confirm("Termin wirklich entfernen?")) {
    calCCType.hideData(event.category_id);
    churchInterface.jsendWrite({func:"deleteEvent", id:event.id}, function() {
      calCCType.needData(event.category_id, true);
      if (func!=null) func();
    });
  }
  
}

function _viewChanged(view) {
  if ((masterData.settings["viewName"]==null) || (masterData.settings["viewName"]!=view.name))
    churchInterface.jsendWrite({func:"saveSetting", sub:"viewName", val:view.name});
}

function categoryEditable(category_id) {
  if (category_id==null) return false;
  
  if ((masterData.auth["edit category"]!=null) && (masterData.auth["edit category"][category_id]!=null))
    return true;
/*  if ((masterData.category[category_id]!=null) && (masterData.category[category_id].modified_pid==masterData.user_pid))  
    return true;*/
  return false;
}

function _eventClick(event, jsEvent, view ) {
  clearTooltip(true);
  var rows = new Array();
  rows.push('<legend>'+event.title+'</legend>');
  rows.push('<p>Startdatum: '+event.start.toStringDe(!event.allDay));
  if (event.end!=null)
    rows.push('<p>Enddatum: '+event.end.toStringDe(!event.allDay));
  
  var myEvent=getEventFromEventSource(event);
  if ((myEvent!=null) && (categoryEditable(myEvent.category_id))) {
    editEvent(myEvent, view.name=="month", event.start);
  }
  else 
    form_showOkDialog("Termin: "+event.title, rows.join(""), 400, 400);
  
}

var tooltip_elem=null;
var tooltip_hold=false;
var tooltip_inhide=false;

function initCalendarView() {
  calendar=$('#calendar');
  
  if (viewName=="calView") {
    calendar.fullCalendar({
      header: {
        left: 'prev,next today',
        center: 'title',
        right: 'agendaDay,agendaWeek,month'
      },
      //aspectRatio: 1.7,
      firstDay:1,
      contentHeight: 600,
      defaultEventMinutes:90,
      editable: true,
      monthNames: monthNames,
      weekNumbers: true,
      weekNumberTitle : "KW",
      monthNamesShort: ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'Mai.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'],
      dayNames: dayNames,
      dayNamesShort: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
      buttonText: {
        prev:     '&nbsp;&#9668;&nbsp;',  // left triangle
        next:     '&nbsp;&#9658;&nbsp;',  // right triangle
        prevYear: '&nbsp;&lt;&lt;&nbsp;', // <<
        nextYear: '&nbsp;&gt;&gt;&nbsp;', // >>
        today:    'Heute',
        month:    'Monat',
        week:     'Woche',
        day:      'Tag'
      },      
      timeFormat: {
    // for agendaWeek and agendaDay
    agenda: "H:mm{-H:mm}", // 5:00 - 6:30
        // for all other views
        '': "H(:mm)'h'"
    },
    allDayText:'Ganzt&auml;gig',
    firstHour:10,
    defaultView:((masterData.settings!=null)&&(masterData.settings["viewName"]!=null)?masterData.settings["viewName"]:"month"),
    axisFormat:"H:mm",
    columnFormat : {
      month: 'ddd',    // Mon
      week: 'ddd d.M.', // Mon 9/7
      day: 'dddd d.M.'  // Monday 9/7
      },
      eventDragStart:  function() {clearTooltip(true);},
      eventResizeStart:  function() {clearTooltip(true);},
      eventDrop: _eventDrop,
      eventResize: _eventResize,
      viewDisplay: _viewChanged,
      eventClick: _eventClick,
      eventMouseover: _eventMouseover,
      eventMouseout: function(calEvent, jsEvent) {
        clearTooltip();
      },
      eventRender: function (event, element) {
        element.find('div.fc-event-title').html(element.find('div.fc-event-title').text());           
        element.find('span.fc-event-title').html(element.find('span.fc-event-title').text());           
      },
      selectable:true,
      selectHelper:true,
      select: _select
    });
    if (!embedded) {
      $("td.fc-header-right").append('<span id="yearView" class="fc-button fc-state-default fc-corner-right"><span class="fc-button-inner"><span class="fc-button-content">Jahr</span><span class="fc-button-effect"><span></span></span></span></span>');
      $("td.fc-header-right").append('<span id="eventView" class="fc-button fc-state-default fc-corner-right"><span class="fc-button-inner"><span class="fc-button-content"><i class="icon-list"></i></span><span class="fc-button-effect"><span></span></span></span></span>');
    }
    $("#header").html("");
    if ($("#viewdate").val()!=null) {
      var viewdate=$("#viewdate").val().toDateEn();
      calendar.fullCalendar( 'gotoDate', viewdate.getFullYear(), viewdate.getMonth(), viewdate.getDate());
    }
  }
  else if (viewName=="yearView") {
    calendar.yearCalendar({});
    $("#header").append('<span id="calView" class="fc-button fc-state-default fc-corner-right"><span class="fc-button-inner"><span class="fc-button-content">Kalender</span><span class="fc-button-effect"><span></span></span></span></span>');
    $("#header").append('<span id="yearView" class="fc-button fc-state-default fc-corner-right"><span class="fc-button-inner"><span class="fc-button-content">Jahr</span><span class="fc-button-effect"><span></span></span></span></span>');
    $("#header").append('<span id="eventView" class="fc-button fc-state-default fc-corner-right"><span class="fc-button-inner"><span class="fc-button-content"><i class="icon-list"></i></span><span class="fc-button-effect"><span></span></span></span></span>');
  }
  else if (viewName=="eventView") {
    calendar.eventCalendar({});
    $("#header").append(form_renderInput({controlgroup:false, cssid:"searchEntry", placeholder:"Suche",htmlclass:"input-medium search-query"}));
    $("#header").append('<span id="calView" class="fc-button fc-state-default fc-corner-right"><span class="fc-button-inner"><span class="fc-button-content">Kalender</span><span class="fc-button-effect"><span></span></span></span></span>');
    $("#header").append('<span id="yearView" class="fc-button fc-state-default fc-corner-right"><span class="fc-button-inner"><span class="fc-button-content">Jahr</span><span class="fc-button-effect"><span></span></span></span></span>');
    $("#header").append('<span id="eventView" class="fc-button fc-state-default fc-corner-right"><span class="fc-button-inner"><span class="fc-button-content"><i class="icon-list"></i></span><span class="fc-button-effect"><span></span></span></span></span>');
  }
  else 
    alert("Unbekannter viewname!");
  $("#calView").click(function(k) {
    window.location.href="?q=churchcal&viewname=calView";
  });
  $("#yearView").click(function(k) {
    window.location.href="?q=churchcal&viewname=yearView";
  });
  $("#eventView").click(function(k) {
    window.location.href="?q=churchcal&viewname=eventView";
  });
  $("#searchEntry").keyup(function() {
    filterName=$(this).val();
    send2Calendar("render");
  });
  $("#searchEntry").focus();
}

function send2Calendar(a,b) {
  if (viewName=="calView")
    calendar.fullCalendar(a,b);
  else if (viewName=="yearView")
    calendar.yearCalendar(a,b);
  else if (viewName=="eventView")
    calendar.eventCalendar(a,b);
  else alert("Unbeaknnter send Calendar");
  
}

function _eventMouseover(event, jsEvent, view) {
  if (!tooltip_hold) {
    if (tooltip_elem!=null) {
      clearTooltip(true);
    }
    tooltip_elem=$(this);    
    
    var rows = new Array();
    var title = event.title;
    rows.push('<div id="tooltip_inner" class="CalView">');
    rows.push('<ul><li>');
    
    if (event.end==null)
      rows.push(event.start.toStringDe(!event.allDay));
    else {
      if (event.end.toStringDe(false)!=event.start.toStringDe(false))        
        rows.push(event.start.toStringDe(!event.allDay)+' - '+event.end.toStringDe(!event.allDay));
      else {
        var min=((""+event.end.getMinutes()).length==1?"0"+event.end.getMinutes():event.end.getMinutes()); 
        rows.push(event.start.toStringDe(!event.allDay)+' - '+event.end.getHours()+":"+min);
      }
    }
    
    var myEvent=getEventFromEventSource(event);
    if (myEvent!=null) {
      myEvent.allDay=event.allDay;
      title=myEvent.bezeichnung;
      if (myEvent.intern_yn==1) title=title+" (intern)";

      if (categoryEditable(myEvent.category_id)) {
        title=title+'<span class="pull-right">&nbsp;<nobr>'+form_renderImage({cssid:"copyevent", label:"Kopieren", src:"copy.png", width:20});
        title=title+"&nbsp;"+form_renderImage({cssid:"delevent", label:'Löschen', src:"trashbox.png", width:20})+"</nobr></span>";
      }
      if ((myEvent.ort!=null) && (myEvent.ort!=""))
        rows.push('<li>Ort: '+myEvent.ort);
      if (myEvent.category_id!=null)
        rows.push("<li>Kategorie: <i>"+masterData.category[myEvent.category_id].bezeichnung+'</i>');
      if ((myEvent.notizen!=null) && (myEvent.notizen!=""))
        rows.push('<li>Notizen: <small> '+myEvent.notizen.trim(60)+'</small>');
      if ((myEvent.link!=null) && (myEvent.link!=""))
        rows.push('<li>Link: <small> <a href="'+myEvent.link+'" target="_clean">'+myEvent.link+'</a></small>');
    }
    if (event.status!=null)
      rows.push('<li>Status: '+event.status);
    rows.push('</ul>');
    if (myEvent!=null) {
      if ((!embedded) && (myEvent.booking_id!=null)) 
        rows.push('<a href="?q=churchresource&id='+myEvent.booking_id+'"><span class="label label-info">'+masterData.churchresource_name+'</label></a>&nbsp;');
      if ((!embedded) && (myEvent.event_id!=null)) 
        rows.push('<a href="?q=churchservice&id='+myEvent.event_id+'"><span class="label label-info">'+masterData.churchservice_name+'</label></a>&nbsp;');
    } 
    else {
      if (event.bezeichnung!=null)
        title=title+" ("+event.bezeichnung+")";

      rows.push("<small><i>Termin von ");
      rows.push(event.source.container.data[event.source.category_id].name);
      rows.push("</i></small>");
    }

    rows.push('</div>');
    
    var placement="bottom";
    
    if (jsEvent.pageX>$("#calendar").width()+$("#calendar").position().left-100)
      placement="left";
    else if (jsEvent.pageY>$("#calendar").height()+$("#calendar").position().top-200)
      placement="top";
    else if (jsEvent.pageX<$("#calendar").position().left+130)
      placement="right";
    
    tooltip_elem.popover({title:title,trigger:"manual",html:true,content:rows.join(""),placement:placement}).popover("show");
    
    $("#copyevent").click(function() {
      clearTooltip(true);
      copyEvent(myEvent);
      return false;
    });
    $("#editevent").click(function() {
      clearTooltip(true);
      editEvent(myEvent);
      return false;
    });
    $("#delevent").click(function() {
      clearTooltip(true);
      delEvent(myEvent);
      return false;
    });
    
    
    tooltip_indraw=true;
    $(".popover").hover(
      function() {
        tooltip_hold=true;
      }, 
      function() {
        tooltip_hold=false;
        clearTooltip();
      }
    );            
  }         
}

function clearTooltip(force) {
  if (tooltip_elem==null) return;  

  tooltip_indraw=false;
  if (force==null) force=false;
  if (force) tooltip_hold=false;
  
  if (force) {
    tooltip_elem.popover("hide");
    tooltip_elem.data("popover",null);
    tooltip_elem=null;
  }
  else {  
    window.setTimeout(function() {
      if ((!tooltip_hold) && (!tooltip_indraw) && (tooltip_elem!=null)) {
        tooltip_elem.popover("hide");
        tooltip_elem.data("popover",null);
        tooltip_elem=null;
      }
    }
    ,200);
  }
}
  
function createMultiselect(name, data) {
  var t=this;
  filter[name]=new CC_MultiSelect(data, function(id, selected) {
    masterData.settings[name]=this.getSelectedAsArrayString();
    churchInterface.jsendWrite({func:"saveSetting", sub:name, val:masterData.settings[name]});
    if (id=="allSelected") {
      if (filter[name]!=null) {
        $.each(filter[name].data, function(k,a) {
          if (churchcore_inArray(k,filter[name].selected))
            needData(name, k);
          else
            hideData(name, k);
        });
      }
    }
    else {
      if (selected)
        needData(name, id);
      else
        hideData(name, id);
    }
  });
  if (name=="filterGemeindekalendar") {
    if ($('#filtercategory_select').val()!=null) {
      var arr= new Array();
      $.each($('#filtercategory_select').val().split(","), function(k,a) {
        arr.push(a*1+100);
      });
      masterData.settings[name]="["+arr.join(",")+"]";
    }
    if (masterData.settings[name]==null)
      filter[name].selectAll();
    else filter[name].setSelectedAsArrayString(masterData.settings[name]);     
  }
  else
    filter[name].setSelectedAsArrayString(masterData.settings[name]);
}
function filterMultiselect(name, label) {
  if (filter[name]!=null) {
    filter[name].render2Div(name, {label:label, controlgroup:!embedded});
  }
}

function _loadAllowedGroups(func) {
  var elem=form_showCancelDialog("Lade Daten...","");
  churchInterface.jsendRead({func:"getAllowedGroups"}, function(ok, data) {
    elem.dialog("close");
    if (!ok) {
      alert("Fehler beim Holen der Gruppen: "+data);
      masterData.groups=new Array();
    }
    else {
      masterData.groups=data;
      if (masterData.groups==null) masterData.groups=new Array();
      func();
    }
  });   
}

function _loadAllowedPersons(func) {
  var elem=form_showCancelDialog("Lade Daten...","");
  churchInterface.jsendRead({func:"getAllowedPersons"}, function(ok, data) {
    elem.dialog("close");
    if (!ok) {
      alert("Fehler beim Holen der Personen: "+data);
      allPersons=new Array();
    }
    else {
      allPersons=new Object();
      if (data!=null) {
        $.each(data, function(k,a) {
          allPersons[a.p_id]=new Object();
          allPersons[a.p_id].bezeichnung=a.name+", "+a.vorname;
          if (a.spitzname!="") allPersons[a.p_id].bezeichnung=allPersons[a.p_id].bezeichnung+" ("+a.spitzname+")";
          allPersons[a.p_id].id=a.p_id;
        });
      }
      func();
    }
  });   
}

function editCategory(cat_id, privat_yn, oeffentlich_yn) {  
  var current=$.extend({}, masterData.category[cat_id]);
  if (current.sortkey==null) current.sortkey=0; 
  
  var form = new CC_Form("Kalender editieren", current);
  
  if ((cat_id==null) && (privat_yn==0) && (oeffentlich_yn==0)) {
    if (masterData.groups==null) {
      _loadAllowedGroups(function() {editCategory(cat_id, privat_yn, oeffentlich_yn)});
      return false; 
    }
    form.addSelect({label:"f&uuml;r Gruppe", freeoption:true, cssid:"accessgroup", data:masterData.groups});
    form.addCaption({text:"<p><small>Hier kann eine Gruppen angegeben werden, die automatisch die Berechtigung erh&auml;lt den Kalender zu sehen</small></p>"});
    form.addCheckbox({label:"Gruppenteilnehmer erhalten Schreibrechte", cssid:"writeaccess", checked:false});
  }
  form.addInput({label:"Bezeichnung", cssid:"bezeichnung", required:true});
  //form.addInput({label:"Farbe", cssid:"color"});
  form.addHtml('<span class="color"></span>');
  form.addInput({label:"Sortierungsnummer", cssid:"sortkey"});

  form.addHtml('<p><p><p class="pull-right"><small>#'+cat_id);

  var elem = form_showDialog((cat_id==null?"Kalender erstellen":"Kalender bearbeiten"), form.render(false, "horizontal"), 500, 500, {
    "Speichern": function() {
      var obj = form.getAllValsAsObject();
      obj.color=current.color;
      if (obj.color==null) obj.color="black";
      obj.privat_yn=privat_yn;
      obj.oeffentlich_yn=oeffentlich_yn;
      if (oeffentlich_yn==1)
        obj.privat_yn=0;
      obj.func="saveCategory";
      obj.id=cat_id;
      elem.html("<legend>Speichere Daten...</legend>");
      churchInterface.jsendWrite(obj, function(ok, data) {
        if (!ok) alert("Es ist ein Fehler aufgetreten:"+data)
        else {
          obj.id=data;
          if (cat_id==null) obj.modified_pid=masterData.user_pid;
          else obj.modified_pid=masterData.category[data].modified_pid;
          masterData.category[data]=obj;
          elem.dialog("close");
          editCategories(privat_yn, oeffentlich_yn, true);
        }
      });
    },      
    "Abbruch": function() {
      elem.dialog("close");
      editCategories(privat_yn, oeffentlich_yn);
    }      
  });  
  form_renderColorPicker({label:"Farbe", value:current.color, elem:elem.find("span.color"), func:function() {
    current.color=$(this).val();
  }});
  elem.find("#accessgroup").change(function() {
    if ($(this).val()!="") elem.find("#bezeichnung").val(masterData.groups[$(this).val()].bezeichnung);
  });
  
  
}

function shareCategory(cat_id, privat_yn, oeffentlich_yn) {
  if (masterData.groups==null) {
    _loadAllowedGroups(function() {shareCategory(cat_id, privat_yn, oeffentlich_yn);});
    return false; 
  }
  if (allPersons==null) {
    _loadAllowedPersons(function() {shareCategory(cat_id, privat_yn, oeffentlich_yn);});
    return false; 
  }
  var dlg=form_showCancelDialog("Lade Daten...","");
  churchInterface.jsendRead({func:"getShares", cat_id:cat_id}, function(ok, data) {
    dlg.dialog("close");
    if (!ok) alert("Fehler: "+data);
    else {
      current=$.extend({}, masterData.category[cat_id]);
      if (current.sortkey==null) current.sortkey=0; 
      if (data.person!=null) {
        if (data.person[403]!=null)
          current.personRead=data.person[403].splice(",");
        if (data.person[404]!=null)
          current.personWrite=data.person[404].splice(",");
      }
      if (data.gruppe!=null) {
        if (data.gruppe[403]!=null)
          current.gruppeRead=data.gruppe[403].splice(",");
        if (data.gruppe[404]!=null)
          current.gruppeWrite=data.gruppe[404].splice(",");
      }
      
      var form = new CC_Form(null, current);
      form.addHtml('<legend>Kalender f&uuml;r Personen freigeben</legend>');

      form.addHtml('<div class="control-group"><label class="control-label">Lesezugriff</label>');
      form.addHtml('<div class="controls" id="personRead">');
      form.addHtml('</div></div>');
      form.addHtml('<div class="control-group"><label class="control-label">Schreibzugriff</label>');
      form.addHtml('<div class="controls" id="personWrite">');
      form.addHtml('</div></div>');

      
      form.addHtml('<legend>Kalender f&uuml;r Gruppen freigeben</legend>');
      form.addHtml('<div class="control-group"><label class="control-label">Lesezugriff</label>');
      form.addHtml('<div class="controls" id="gruppeRead">');
      form.addHtml('</div></div>');
      form.addHtml('<div class="control-group"><label class="control-label">Schreibzugriff</label>');
      form.addHtml('<div class="controls" id="gruppeWrite">');
      

      var elem = form_showDialog("Kalender <i>"+masterData.category[cat_id].bezeichnung+" </i>freigeben", form.render(false, "horizontal"), 500, 500, {
        "Speichern": function() {
          var obj = form.getAllValsAsObject();
          obj["person"]=new Object();
          obj["person"][403]=current.personRead;
          obj["person"][404]=current.personWrite;
          obj["gruppe"]=new Object();
          obj["gruppe"][403]=current.gruppeRead;
          obj["gruppe"][404]=current.gruppeWrite;
          obj.cat_id=cat_id;
          elem.html("<legend>Speichere Daten...</legend>");
          obj.func="saveShares";
          churchInterface.jsendWrite(obj, function(ok, data) {
            if (!ok) alert("Es ist ein Fehler aufgetreten:"+data)
            else {
              elem.dialog("close");
              editCategories(privat_yn, oeffentlich_yn);
            }
          });          
        },      
        "Abbruch": function() {
          elem.dialog("close");
          editCategories(privat_yn, oeffentlich_yn);
        }      
      });  
      form_renderLabelList(current, "personRead", allPersons);
      form_renderLabelList(current, "personWrite", allPersons);
      form_renderLabelList(current, "gruppeRead", masterData.groups);
      form_renderLabelList(current, "gruppeWrite", masterData.groups);
    }
  });
  
}

function editCategories(privat_yn, oeffentlich_yn, reload) {
  var rows = new Array();
  if (reload==null) reload=false;
  if (privat_yn==1)
    rows.push('<legend>Pers&ouml;nliche Kalender verwalten</legend>');
  else if (oeffentlich_yn==0)
    rows.push('<legend>Gruppenkalender</legend>');
  else
    rows.push('<legend>Gemeindekalender verwalten</legend>');
  rows.push('<table class="table table-condensed">');
  rows.push('<tr><th width="20px"><th>Bezeichnung<th width="40px">');
  rows.push('<th width="25px">');
  
  rows.push('<th width="25px"><th width="25px">');
  
  $.each(churchcore_sortData(masterData.category,"privat_yn", true, null, "sortkey"), function(k,cat) {
    if ((cat.oeffentlich_yn==oeffentlich_yn) && (cat.privat_yn==privat_yn)) {
      rows.push('<tr><td>'+form_renderColor(cat.color));
      rows.push('<td>'+cat.bezeichnung);
      rows.push('<td><a href="#" id="ical" data-id="'+cat.id+'"><span class="label">iCal</span></a>');
      if (categoryEditable(cat.id)) { 
        rows.push('<td>'+form_renderImage({src:"persons.png", width:20, cssid:"share", data:[{name:"id", value:cat.id}]}));
        rows.push('<td>'+form_renderImage({src:"options.png", width:20, cssid:"options", data:[{name:"id", value:cat.id}]}));
        rows.push('<td>'+form_renderImage({src:"trashbox.png", width:20, cssid:"delete", data:[{name:"id", value:cat.id}]}));
      }
      else rows.push('<td><td><td>');
    }
  });

  if (
       ((privat_yn==1) && (masterData.auth["personal category"]))
       || ((privat_yn==0) && (oeffentlich_yn==0) && (masterData.auth["group category"]))
       || ((privat_yn==0) && (oeffentlich_yn==1) && (masterData.auth["church category"]))
      )
    rows.push('<tr><td><td><a href="#" id="options"><i>Neuen Kalender erstellen</i></a><td><td><td>'+form_renderImage({cssid:"options", src:"plus.png", width:20})+"<td>");
  
  rows.push('</table>');  
  var elem = form_showDialog("Kalender verwalten", rows.join(""), 500, 500, {
    "Schliessen": function() {
      elem.dialog("close");
      if (reload) window.location.reload();
    }      
  });
  
  elem.find("#options").click(function() {
    elem.dialog("close");
    editCategory($(this).attr("data-id"), privat_yn, oeffentlich_yn);
    return false;
  });   
  elem.find("#share").click(function() {
    elem.dialog("close");
    shareCategory($(this).attr("data-id"), privat_yn, oeffentlich_yn);
    return false;
  });   
  elem.find("#ical").click(function() {
    var rows=new Array(); 
    rows.push('<legend>Kalender abonnieren</legend>Der Kalender kann abonniert werden. Hierzu kann die Adresse anbei in einen beliebigen Kalender importiert werden,'+
               ' der iCal unterst&uuml;tzt.<br><br>');
    var id=$(this).attr("data-id");
    rows.push(form_renderInput({label:"iCal-URL", value:masterData.base_url+"?q=churchcal/ical&security="+masterData.category[id].randomurl+"&id="+id, disable:true}));
    form_showOkDialog("Kalender abonnieren", rows.join(""));
    return false;
  });   
  elem.find("#delete").click(function() {
    var id=$(this).attr("data-id");
    var c=0;
    $.each(masterData.category, function(i,cat) {
      if ((cat.id!=id) && (cat.privat_yn==1)) c=c+1;
    });
    if (c==0) {
      alert("Ein privater Kalender muss bestehen bleiben!");
      return false;
    }
    if (confirm("Wirklich den Kalender "+masterData.category[id].bezeichnung+" und alle seine Daten entfernen?")) {
      churchInterface.jsendWrite({func:"deleteCategory", id:id}, function(ok, info) {
        if (!ok) alert("Es ist ein Fehler aufgetreten: "+info);
        else {
          calCCType.hideData(id);
          delete masterData.category[id];
          elem.dialog("close");
          editCategories(privat_yn, oeffentlich_yn, true);
        }
      });      
    }
  });   
}


function needData(filtername, id) {
  if ((filtername=="filterRessourcen")) {
    calResourceType.needData(id);
  }
  else {
    // Wenn es sich um wirklich Kalendardaten handelt dann ist id>100, habe ich ja vorher addiert
    if (id>=100) {
      calCCType.needData(id-100);
      if ((filter["filterGruppenKalender"]!=null) && (churchcore_inArray(5, filter["filterGruppenKalender"].selected)))
        calAbsentsType.needData(0);        
    }
    else if (id==1) calMyServicesType.needData(0);
    else if (id==4) calBirthdayType.needData(0);
    else if (id==5) calAbsentsType.needData(0);
    else if (id==6) calAllBirthdayType.needData(0);
  }
}
function hideData(filtername, id) {
  if ((filtername=="filterRessourcen")) {
    calResourceType.hideData(id);
  }
  else {
    // Wenn es sich um wirklich Kalendardaten handelt dann ist id>100, habe ich ja vorher addiert
    if (id>=100) {
      calCCType.hideData(id-100);
      if ((filter["filterGruppenKalender"]!=null) && (churchcore_inArray(5, filter["filterGruppenKalender"].selected))) {
        calAbsentsType.hideData(0);        
        calAbsentsType.needData(0);
      }
    }
    else if (id==1) calMyServicesType.hideData(0);
    else if (id==4) calBirthdayType.hideData(0);
    else if (id==5) calAbsentsType.hideData(0);
    else if (id==6) calAllBirthdayType.hideData(0);
  }    
}


function renderPersonalCategories() {
  var rows = new Array();
  var form = new CC_Form("Pers&ouml;nliche Kalender"+form_renderImage({cssid:"edit_personal", src:"options.png", top: 8, width:24, htmlclass:"pull-right"}));
  form.setHelp("ChurchCal-Filter");
  var sortkey=-1;
  var mycals=new Object();
  // Meine Kalendar
  if (churchcore_countObjectElements(masterData.category)>0) {
    $.each(churchcore_sortMasterData(masterData.category), function(k,a) {
      if ((a.modified_pid==masterData.user_pid) && (a.privat_yn==1) && (a.oeffentlich_yn==0)) {
        form_addEntryToSelectArray(mycals,a.id*1+100,a.bezeichnung,sortkey);
        sortkey++;
      }
    });
  }
  if (masterData.auth["view churchservice"]) {
    form_addEntryToSelectArray(mycals,1,'Meine Dienste',sortkey); sortkey++;
  }

  if (sortkey>-1) {
    form_addEntryToSelectArray(mycals,2,'-',sortkey); sortkey++;
  }
  
  // Freigegebene
  if (viewName!="yearView") {  
    if (churchcore_countObjectElements(masterData.category)>0) {
      $.each(churchcore_sortMasterData(masterData.category), function(k,a) {
        if ((a.modified_pid!=masterData.user_pid) && (a.oeffentlich_yn==0) && (a.privat_yn==1)) {
          form_addEntryToSelectArray(mycals,(a.id*1+100),a.bezeichnung,sortkey);
          sortkey++;
        }
      });      
    }
  }
  if (sortkey>=0) {
    createMultiselect("filterMeineKalender", mycals);
    form.addHtml('<div id="filterMeineKalender"></div>');
    rows.push(form.render(true));
  }  
  return rows.join("");
}

function renderGroupCategories() {
  form = new CC_Form("Gruppenkalender"+form_renderImage({cssid:"edit_group", src:"options.png", top:8, width:24, htmlclass:"pull-right"}));
  var sortkey=-1;
  var mycals=new Object();
  var rows = new Array();
  
  if ((masterData.auth["group category"]) || (churchcore_countObjectElements(masterData.category)>0)) {
    $.each(churchcore_sortMasterData(masterData.category), function(k,a) {
      if ((a.oeffentlich_yn==0) && (a.privat_yn==0)) {
        var title=a.bezeichnung;
        if (!categoryEditable(a.id)) title=title+" (nur lesbar)";
        form_addEntryToSelectArray(mycals,(a.id*1+100),title,sortkey);
        sortkey++;
      }
    });
    if (sortkey>=0) {
      form_addEntryToSelectArray(mycals,3,'-',sortkey);  sortkey++;
      form_addEntryToSelectArray(mycals,5,'Abwesenheiten pro Kalender',sortkey);  sortkey++;
      createMultiselect("filterGruppenKalender", mycals);
      form.addHtml('<div id="filterGruppenKalender"></div>');
      rows.push(form.render(true));
    }
    else if (masterData.auth["group category"])  {
      form.addHtml('<i>Kein Kalender vorhanden</i>');
      rows.push(form.render(true));
    }

  }
  return rows.join("");
}

function renderChurchCategories() {
  var rows= new Array();
  if (viewName!="yearView") {
    var sortkey=0;
    var form=new CC_Form();

    if (!embedded) {
      form = new CC_Form("Gemeindekalender"+form_renderImage({cssid:"edit_church", src:"options.png", top:8, width:24, htmlclass:"pull-right"}));
      form.setHelp("ChurchCal-Filter");
    }

    oeff_cals=new Object();
    if (churchcore_countObjectElements(masterData.category)>0) {
      $.each(churchcore_sortMasterData(masterData.category), function(k,a) {
        if ((a.oeffentlich_yn==1) && ((filterCategoryIds==null) || (churchcore_inArray(a.id, filterCategoryIds)))) {
          var title=a.bezeichnung;
          form_addEntryToSelectArray(oeff_cals,(a.id*1+100),title,sortkey);
          sortkey++;
        }
      });
    }
    
    if (masterData.auth["view churchdb"]) {
      if (sortkey>=0) {
        form_addEntryToSelectArray(oeff_cals,3,'-',sortkey);  sortkey++;
      }
      if (masterData.auth["view alldata"]) {
        form_addEntryToSelectArray(oeff_cals,4,'Geburtstage (Gruppen)',sortkey);  sortkey++;
        form_addEntryToSelectArray(oeff_cals,6,'Geburtstage (Alle)',sortkey);  sortkey++;
      }
      else {
        form_addEntryToSelectArray(oeff_cals,6,'Geburtstage',sortkey);  sortkey++;
      }
    }
  
    
    createMultiselect("filterGemeindekalendar", oeff_cals);

    if ((embedded) && (!minical)) {
      form.addHtml('<table width="100%"><tr>');
      if ((filterCategoryIds==null) || (filterCategoryIds.length>1))
        form.addHtml('<td width="190px"><div style="width:180px" id="filterGemeindekalendar"></div>');
     
      if  (viewName=="eventView") {
        form.addHtml('<td width="110px">');
        form.addInput({controlgroup:false, cssid:"searchEntry", placeholder:"Suche",htmlclass:"input-small search-query"});
      
        form.addHtml('<td width="50px"> &nbsp;');
        form.addImage({src:'cal.png', cssid:'showminical', size:24}); 
        form.addHtml('<div id="minicalendar" style="display:none; position:absolute;background:#e7eef4;z-index:8001; height:240px; max-width:350px"></div>');
      }

      var title="Kalender";
      if ($("#embeddedtitle").val()!=null) title=$("#embeddedtitle").val();
      form.addHtml('<td><font class="pull-right" style="font-size:180%">'+title+'</font> &nbsp; ');        
      form.addHtml("</table>");
    }
    else if (!embedded){
      form.addHtml('<div id="filterGemeindekalendar"></div>');        
    }
    if (masterData.auth["view churchresource"]) {
      createMultiselect("filterRessourcen", masterData.resourcen);
      form.addHtml('<div id="filterRessourcen"></div>');
      $.each(masterData.resourceTypes, function(k,a) {
        filter["filterRessourcen"].addFunction(a.bezeichnung+" w&auml;hlen", function(b) {
          return b.resourcetype_id==a.id;
        });
      });

    }
    
    rows.push(form.render(true));
  }
  return rows.join("");
}  

$(document).ready(function() {
  churchInterface.setModulename("churchcal");
  if ($("#filtercategory_id").val()!=null) {
    filterCategoryIds=$("#filtercategory_id").val().split(",");
  }
  
  if ($("#isembedded").length!=0) embedded=true;
  if ($("#isminical").length!=0) minical=true;
  if ($("#entries").length!=0) max_entries=$("#entries").val();

	churchInterface.setStatus("Lade Kennzeichen...");
  churchInterface.jsendRead({func:"getMasterData"}, function(ok, json) {
    churchInterface.clearStatus();
    masterData=json;
    
    if ($("#viewname").val()!=null) viewName=$("#viewname").val();
    initCalendarView();
    var rows= new Array();
      
    if ((viewName=="eventView") && (!embedded)) {
      rows.push('<div id="minicalendar" style="height:240px; max-width:350px"></div><br/><br/><br/>');
    }    
    rows.push(renderPersonalCategories());    
    rows.push(renderGroupCategories());    
    rows.push(renderChurchCategories());

    $("#cdb_filter").html(rows.join(""));
    
    filterMultiselect("filterMeineKalender", "Meine Kalender");
    filterMultiselect("filterGruppenKalender", "Gruppenkalender");
    filterMultiselect("filterGemeindekalendar", (!embedded?"Gemeindekalender":"Kalender"));
    filterMultiselect("filterRessourcen", "Ressourcen");
    
    if (embedded) { 
      $("#searchEntry").keyup(function() {
        filterName=$(this).val();
        send2Calendar("render");
      });
      $("#searchEntry").keydown(function(a) {
        if (a.keyCode==13) return false;
      });
      $("#searchEntry").focus();
    }
    
    $("#edit_personal").click(function() {
      editCategories(1, 0);
    });
    $("#edit_group").click(function() {
      editCategories(0, 0);
    });
    $("#edit_church").click(function() {
      editCategories(0, 1);
    });
    $("#showminical").click(function() {
      $("#minicalendar").toggle();
    });
    $("#abo").click(function() {
      var rows=new Array(); 
      rows.push('<legend>Kalender abonnieren</legend>Die &ouml;ffentlichen Termine dieses Kalenders k&ouml;nnen abonniert werden. Hierzu kann die Adresse anbei in einen beliebigen Kalender importiert werden,'+
                 ' der iCal unterst&uuml;tzt.<br><br>');
      var id=$(this).attr("data-id");
      rows.push(form_renderInput({label:"iCal-URL", value:masterData.base_url+"?q=churchcal/ical", disable:true}));
      form_showOkDialog("Kalender abonnieren", rows.join(""));
      return false;
    });
    
    //Sucht sich die Kalender zusammen die neu geholt werden m�ssen
    $.each(filter, function(k,a) {
      if (a.data!=null) 
      $.each(a.data, function(i,s) {
        // Wenn es ausgew�hlt ist
        if (churchcore_inArray(i, a.selected)) {
          needData(k, s.id);
        }
        else {
          hideData(k, s.id);
        }        
      });
    });  
  });
});

