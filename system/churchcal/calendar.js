calendar=null;
currentEvent=null;
allEvents=null;
allPersons=null;
allData=new Object();
// For loading ChurchResource-Bookings
allBookings=null;
viewName="calView";
filterName="";
previousBookings=null;
filterCategoryIds=null;
filterId=null;
var saveSettingTimer=null;
var filter=new Object();
var embedded=false;
var minical=false;
var max_entries=50;
var printview=false;
var newbookingid=0;


function getCALEvent(a) {
  var event = new CCEvent(a);
  event.saveSuccess = saveCalSuccess;
  event.saveSplitSuccess = saveSplitCalSuccess;
  event.name = "Event";
  return event;
}

/*
 * Collect database conform event vom source
 */
function getEventFromEventSource(event) {
  if ((event.source==null) || (event.source.container.data==null)
       || (event.source.category_id==0)
             || (event.source.container.data[event.source.category_id]==null))
    return null;
  else
    return event.source.container.data[event.source.category_id].events[event.id];
}

function saveSplitCalSuccess(newEvent, pastEvent, originEvent) {
  delete calCCType.data[originEvent.category_id].events[originEvent.id];
  calCCType.data[newEvent.category_id].events[newEvent.id] = newEvent;
  calCCType.data[pastEvent.category_id].events[pastEvent.id] = pastEvent;
  calCCType.refreshView(newEvent.category_id, false);
}

function saveCalSuccess(newEvent, originEvent, data) {
  if (newEvent.copychurchservice) {
    // When copy then I need to refresh completly
    calCCType.refreshView(newEvent.category_id, true);
    return;
  }

  if (data!=null) {
    // New CSEvent Id Mapping from -1, -2 ... to 1, 2 ...
    each(data.cseventIds, function(k,a) {
      newEvent.csevents[a] = newEvent.csevents[k]
      newEvent.csevents[a].id=a;
      delete newEvent.csevents[k];
    });
    // New Booking-Ids Mapping from -1, -2 ... to 1, 2 ...
    each(data.bookingIds, function(k,a) {
      newEvent.bookings[a] = newEvent.bookings[k]
      newEvent.bookings[a].id=a;
      delete newEvent.bookings[k];
    });
  }

  // Clean deleted csevents
  each(newEvent.csevents, function(k,a) {
    if (a.action=="delete") delete newEvent.csevents[k];
  });
  if (newEvent.id == null) newEvent.id = data.id;
  // Perhaps a change in category?
  else if ( originEvent != null && originEvent.category_id != null
             && originEvent.category_id != newEvent.category_id ) {
    delete calCCType.data[originEvent.category_id].events[originEvent.id];
    calCCType.refreshView(originEvent.category_id, false);
  }
  if (calCCType.data[newEvent.category_id]!=null) {  // already visible
    calCCType.data[newEvent.category_id].events[newEvent.id] = newEvent;
    calCCType.refreshView(newEvent.category_id, false);
  }
  else { // not loaded, so activate and load it
    $("#filterCalender_"+(newEvent.category_id*1+100)).colorcheckbox("check", true)
    calCCType.refreshView(newEvent.category_id, true);
  }
}

/**
* event: fullCalendar event
* @func: false if something is wrong, or func(true, newEvent) for changes in new Event
*/
function doEventChanges(event, delta, jsEvent, func) {
  clearTooltip(true);

  var originEvent=getEventFromEventSource(event);
  if ((originEvent==null) || (masterData.auth["edit category"]==null) || (masterData.auth["edit category"][event.source.category_id]==null)) {
    alert("Fehler oder keine Rechte!");
    func(false);
  }
  else {
    var splitDate = new Date( event.start.format(DATETIMEFORMAT_EN).toDateEn(true).getTime() - delta.asMilliseconds());
    var currentDatetime = event.start.format(DATETIMEFORMAT_EN).toDateEn(true);
    originEvent.askForSplit(jsEvent, function(untilEnd) {
      if (untilEnd==null) func(false);  // Cancel
      else {
        originEvent.doSplit(splitDate, untilEnd, function(newEvent, pastEvent) {
          // Change new event
          newEvent = func(true, newEvent);
          originEvent.prooveEventChangeImpact(newEvent, pastEvent, splitDate, untilEnd, function(ok) {

            if (!ok) func(false);
            else {
              originEvent.saveSplitted(newEvent, pastEvent, splitDate, untilEnd, function(ok) { if (!ok) func(false); });
            }
          });
        });
      }
    });
  }
}

/**
 * event - Event wie in der DB
 * month - Monatsansicht=true
 * currentDate - Das Datum auf das geklickt wurde, nur bei Wiederholungsterminen kann es anders sein
 * jsEVent
 * editSeries - Soll die ganze Serie geöffnet werden oder nur der einzelne Termin
 */
function editEvent(myEvent, month, currentDate, jsEvent) {
  if (debug) console.log("editEvent", myEvent, month, currentDate);

  myEvent.askForSplit(jsEvent, function(untilEnd) {
    if (untilEnd!=null) {
      myEvent.doSplit(currentDate, untilEnd, function(newEvent, pastEvent) {
        renderEditEvent(newEvent, myEvent, myEvent.isSeries(), untilEnd, function(newEvent, func) {
          myEvent.prooveEventChangeImpact(newEvent, pastEvent, currentDate, null, function(ok) {
            if (!ok) func(false);
            else {
              myEvent.saveSplitted(newEvent, pastEvent, currentDate, untilEnd);
              func(true);
            }
          })
        });
      });
    }
  });
}

function _eventResize(event, delta, revertFunc, jsEvent, ui, view) {
  if (debug) console.log("_eventResize", event, delta);

  doEventChanges(event, delta, jsEvent, function(ok, newEvent) {
    if (!ok) revertFunc();
    else {
      newEvent.enddate = new Date(newEvent.enddate.getTime() + delta.asMilliseconds());
      // No impact for CS, cause CS has no enddate!
      return newEvent;
    }
  });
}

function _eventDrop(event, delta, revertFunc, jsEvent, ui, view ) {
  if (debug) console.log("_eventDrop", event, delta);
  doEventChanges(event, delta, jsEvent, function(ok, newEvent) {
    if (!ok) revertFunc();
    else {
      if (!event.start.hasTime()) {
        newEvent.startdate = new Date(event.start.format(DATEFORMAT_EN)).withoutTime();
        if (event.end==null) newEvent.enddate = new Date(newEvent.startdate.getTime());
        else newEvent.enddate = new Date(event.end.format(DATEFORMAT_EN)).withoutTime();
      }
      else {
        newEvent.startdate = new Date(newEvent.startdate.getTime() + delta.asMilliseconds());
        newEvent.enddate = new Date(newEvent.enddate.getTime() + delta.asMilliseconds());
        if (newEvent.startdate.getTime()==newEvent.enddate.getTime())
          newEvent.enddate.setMinutes(newEvent.enddate.getMinutes()+90);

        // Add changes to csevents
        each (newEvent.csevents, function(k, a) {
          a.startdate = new Date (a.startdate.getTime() + delta.asMilliseconds());
        });
      }
      return newEvent;
    }
  });
}

function _select(start, end, jsEvent, view) {
  if (debug) console.log("_select", start, end, jsEvent, view);

  var myEvent = getCALEvent();
  myEvent.startdate = start.format(DATETIMEFORMAT_EN).toDateEn(true);
  myEvent.enddate = end.format(DATETIMEFORMAT_EN).toDateEn(true);
  // fullCalendar works with exclusive dates, CT not!
  if (!start.hasTime()) myEvent.enddate.addDays(-1);
  editEvent(myEvent, view.name=="month");
  calendar.fullCalendar('unselect');
}

function exceptionExists(exceptions, except_date_start) {
  if (exceptions==null) return false;
  var res=false;
  each(exceptions, function(k,a) {
    var s, e;
    if (a.except_date_start instanceof(Date))
      s=a.except_date_start;
    else
      s=a.except_date_start.toDateEn(true);

    if (except_date_start instanceof(Date))
      e=except_date_start;
    else
      e=except_date_start.toDateEn(true);

    if (s.getTime()==e.getTime()) {
      res=true;
      return false;
    }
  });
  return res;
}

function _renderViewChurchResource(elem) {
  // Baue erst ein schönes Select zusammen
  var arr=new Array();
  var sortkey=0;
  each(churchcore_sortMasterData(masterData.resourceTypes, "sortkey"), function(k,a) {
    arr.push({id:"", bezeichnung:'-- '+a.bezeichnung+' --'});
    each(churchcore_sortMasterData(masterData.resources, "sortkey"), function(i,b) {
      if (b.resourcetype_id==a.id) {
        arr.push({id:b.id, bezeichnung:b.bezeichnung});
      }
    });
  });

  var minutes = getMinutesDuration();

  var form = new CC_Form();
  if (currentEvent.minpre==null) {
    currentEvent.minpre=0; currentEvent.minpost=0;
  }
  form.addSelect({label:"Im Vorfeld buchen", cssid:"min_pre_new", sort:false, selected:currentEvent.minpre, data:minutes});
  form.addSelect({label:"Nachher buchen", cssid:"min_post_new", sort:false, selected:currentEvent.minpost, data:minutes});
  form.addSelect({cssid:"ressource_new",  htmlclass:"resource", freeoption:true,
          label:"Ressource ausw&auml;hlen", data:arr, sort:false, func:function(a) {
            var drin=false;
            each(currentEvent.bookings, function(k,b) { if (b.resource_id==a.id) drin=true; })
            return !drin;
          }});
  if (currentEvent.bookings==null && previousBookings!=null && previousBookings!=currentEvent.bookings)
    form.addButton({controlgroup:true, label:"Vorherige Buchungen hinzufügen", htmlclass:"use-previous-bookings"});

  if (currentEvent.bookings!=null) {
    if (allBookings==null) {
      if (user_access("view churchresource")) {
        churchInterface.loadLazyView("WeekView", function(view) {
          view.loadDependencies(function() {
            view.buildDates(allBookings);
            _renderViewChurchResource(elem);
          });
        });
      }
    }

    form.addHtml('<legend>Vorhandene Buchungen</legend>');
    form.addHtml('<div class="w_ell"><table class="table table-condensed"><tr><th>'+_("resource")+
                 '<th>'+_("booking.before")+'<th>'+_("booking.after")+'<th>'+_("status")+'<th>');
    each(currentEvent.bookings, function(k,a) {
      form.addHtml('<tr><td>');
      if (masterData.resources[a.resource_id]!=null)
        form.addHtml(masterData.resources[a.resource_id].bezeichnung);
      else
        form.addHtml("-- Resource existiert nicht mehr --");
      form.addHtml('<td>');
      form.addSelect({type:"small", cssid:"min-pre-"+k, controlgroup:false, sort:false, selected:a.minpre, data:minutes});
      form.addHtml('<td>');
      form.addSelect({type:"small", cssid:"min-post-"+k, controlgroup:false, sort:false, selected:a.minpost, data:minutes});
      form.addHtml('<td>');
      form.addSelect({type:"medium", data:masterData.bookingStatus, cssid:"status-"+k, controlgroup:false, selected:a.status_id,
          func:function(s) {
            return s.id==1
                   || (s.id==2 && masterData.resources[a.resource_id]!=null && masterData.resources[a.resource_id].autoaccept_yn==1)
                   || user_access("administer bookings", a.resource_id)
                   || s.id==a.status_id;
          }
      });
      form.addHtml('<td>');
      if (a.status_id!=99)
        form.addImage({src:"trashbox.png", htmlclass:"delete-booking", link:true, width:20, data:[{name:"id", value:k}]});
      if (churchInterface.views.WeekView!=null) {
        if (allBookings[a.id]!=null && allBookings[a.id].exceptions!=null) {
          var arr=new Array();
          each(churchcore_sortData(allBookings[a.id].exceptions, "except_date_start"), function(i,b) {
            if (!exceptionExists(currentEvent.exceptions, b.except_date_start))
              arr.push(b.except_date_start.toDateEn(false).toStringDe(false));
          });
          if (arr.length>0) {
            form.addHtml('<tr><td><td colspan="4">Ausnahmen: &nbsp;');
            each(arr, function(i,b) {
              form.addHtml('<span class="label label-important">'+b+'</span> &nbsp;');
            });
          }
        }
        var c=$.extend({}, currentEvent);
        c.startdate=new Date();
        c.enddate=new Date();
        c.startdate.setTime(currentEvent.startdate.getTime() - (a.minpre * 60 * 1000));
        c.enddate.setTime(currentEvent.enddate.getTime() + (a.minpost * 60 * 1000));
        c.id=a.id;

        if (a.note || a.location) {
          form.addHtml('<tr><td colspan="5"><div class="alert">');
          if (a.location!="") form.addHtml('<i>Bemerkung: '+a.location+'</i>&nbsp;<br/>');
          if (a.note!="") form.addHtml('<small><i>'+a.note+'</i></small><br/>');
          form.addHtml('</div>');
        }

        var conflicts=churchInterface.views.WeekView.calcConflicts(c, a.resource_id, allBookings[a.old_id]);
        if (conflicts!="") form.addHtml('<tr><td colspan="5"><div class="alert alert-error">Konflikte: '+conflicts+"</div>");
      }
      else if (user_access("view churchresource")) {
        form.addHtml('<tr><td colspan="5">');
        form.addImage({src:"loading.gif"});
      }

    });
    form.addHtml('</table></div>');
  }

  elem.find("#cal_content").html(form.render(null, "horizontal"));

  elem.find("select.resource").change(function() {
    if (elem.find("select.resource").val()>0) {
      if (currentEvent.bookings==null) currentEvent.bookings=new Object();
      currentEvent.minpre=elem.find("#min_pre_new").val();
      currentEvent.minpost=elem.find("#min_post_new").val();
      var resource_id=elem.find("select.resource").val();
      var status_id=(masterData.resources[resource_id].autoaccept_yn==1?2:1);
      newbookingid = newbookingid - 1;
      currentEvent.bookings[newbookingid]={id:newbookingid, resource_id:resource_id,
                minpre:currentEvent.minpre, minpost:currentEvent.minpost,
                status_id:status_id};
      _renderEditEventContent(elem);
    }
  });
  elem.find("select").change(function() {
    if ($(this).attr("id").indexOf("min-pre-")==0) {
      currentEvent.bookings[$(this).attr("id").substr(8,99)].minpre=$(this).val();
      _renderEditEventContent(elem);
    }
    else if ($(this).attr("id").indexOf("min-post-")==0) {
      currentEvent.bookings[$(this).attr("id").substr(9,99)].minpost=$(this).val();
      _renderEditEventContent(elem);
    }
    else if ($(this).attr("id").indexOf("status-")==0) {
      currentEvent.bookings[$(this).attr("id").substr(7,99)].status_id=$(this).val();
      _renderEditEventContent(elem);
    }
  });
  elem.find("input.use-previous-bookings").click(function() {
    currentEvent.bookings=previousBookings;
    _renderEditEventContent(elem);
  });
  elem.find("a.delete-booking").click(function() {
    // Is booking created but not saved (fresh), then I can delete it.
    if ($(this).attr("data-id")<0) {
      delete currentEvent.bookings[$(this).attr("data-id")];
    }
    else {
      currentEvent.bookings[$(this).attr("data-id")].status_id=99;
    }
    _renderEditEventContent(elem);
    return false;
  });
}

function _renderInternVisible(elem, currentEvent) {
  var txt="";
  if ((masterData.category[currentEvent.category_id]!=null) &&
     (masterData.category[currentEvent.category_id].oeffentlich_yn==1)) {
    txt=txt+form_renderCheckbox({
      checked:(currentEvent.intern_yn!=null && currentEvent.intern_yn==1?true:false),
      label:" "+_("only.intern.visible"),
      controlgroup:true,
      controlgroup_class:"",
      cssid:"inputIntern"
    });
  }
  $("#internVisible").html(txt);
}

function _renderEditEventContent(elem) {
  var rows = new Array();
  if (currentEvent.view=="view-main") {

    rows.push('<form class="form-horizontal">');

    rows.push(form_renderInput({
      value:currentEvent.bezeichnung,
      cssid:"inputBezeichnung",
      label:_("caption")
    }));

    rows.push(form_renderInput({
      value:currentEvent.ort,
      cssid:"inputOrt",
      label:_("note"),
      placeholder:""
    }));
    rows.push('<div id="internVisible"></div>');
    rows.push('<div id="dates"></div>');
    rows.push('<div id="wiederholungen"></div>');

    var e_summe=new Array();
    var e=new Array();

    e.push({id:-1, bezeichnung:"-- "+masterData.maincal_name+" --"});
    each(churchcore_sortMasterData(masterData.category), function(k,a) {
      if ((a.oeffentlich_yn==1) && (categoryEditable(a.id)) &&
           (!masterData.category[a.id].ical_source_url)) e.push(a);
    });
    if (e.length>1) e_summe=e_summe.concat(e);

    var e=new Array();
    e.push({id:-1, bezeichnung:"-- "+_("group.calendar")+" --"});
    each(churchcore_sortMasterData(masterData.category), function(k,a) {
      if ((a.oeffentlich_yn==0) && (a.privat_yn==0) && (categoryEditable(a.id))) e.push(a);
    });
    if (e.length>1) e_summe=e_summe.concat(e);

    if (currentEvent.csevents==null) {
      var e=new Array();
      e.push({id:-1, bezeichnung:"-- "+_("personal.calendar")+" --"});
      each(churchcore_sortMasterData(masterData.category), function(k,a) {
        if ((a.privat_yn==1) && (categoryEditable(a.id)) &&
            (!masterData.category[a.id].ical_source_url)) e.push(a);
      });
      if (e.length>1) e_summe=e_summe.concat(e);
    }


    rows.push(form_renderSelect({
      //data:masterData.category,
      data:e_summe,
      sort:false,
      cssid:"inputCategory",
      selected:currentEvent.category_id,
      label:_("calendar")
    }));
    rows.push(form_renderTextarea({
      data:currentEvent.notizen,
      label:_("more.information"),
      cssid:"inputNote",
      rows:2,
      cols:100
    }));
    rows.push(form_renderInput({
      value:currentEvent.link,
      label:"Link",
      cssid:"inputLink"
    }));
    if (user_access("assistance mode") && (currentEvent.modified_pid==null || currentEvent.modified_pid == masterData.user_pid)) {
      rows.push(form_renderInput({
        cssid:"assistance_user",
        label:_("by.order.of")
      }));
    }

    rows.push('</form>');

    if (currentEvent.id!=null) {
      rows.push('<p align="right"><small>');
      rows.push(' #'+currentEvent.id);
      if (currentEvent.modified_name!=null) rows.push(' - Erstellt von '+currentEvent.modified_name);
      if (currentEvent.modified_date!=null) rows.push(" am "+currentEvent.modified_date.toDateEn(true).toStringDe(true)+"&nbsp;");
      rows.push("</small>");
    }
    rows.push('<br/><br/>');

    elem.find("#cal_content").html(rows.join(""));

    _renderInternVisible(elem, currentEvent);
    $("#dates").renderCCEvent({event: currentEvent});

    form_autocompletePersonSelect("#assistance_user", false, function(divid, ui) {
      $("#assistance_user").val(ui.item.label);
      $("#assistance_user").attr("disabled", true);
      currentEvent.modified_pid = ui.item.value;
      currentEvent.modified_name = ui.item.label;
      $("#assistance_user").attr("data-id", ui.item.value);
      $("#assistance_user").attr("data-name", ui.item.label);
      return false;
    });


    $("#inputBezeichnung").focus();

    elem.find("#inputCategory").change(function() {
      churchInterface.jsendWrite({func:"saveSetting", sub:"category_id", val:$(this).val()});
      masterData.settings.category_id=$(this).val();
      currentEvent.category_id=$(this).val();
      _renderInternVisible(elem, currentEvent);
      _renderEditEventNavi(elem);
    });
  }
  else if (currentEvent.view=="view-invite") {
    _renderViewInvite(elem);
  }
  else if (currentEvent.view=="view-churchresource") {
    _renderViewChurchResource(elem);
  }
  else if (currentEvent.view=="view-churchservice") {
    _renderViewChurchService(elem);
  }
}

function _renderViewInvite(elem) {
  var rows = new Array();
  rows.push('<div id="meeting-request">'+form_renderImage({src:"loading.gif"})+'</div>');
  elem.find("#cal_content").html(rows.join(""));
  churchInterface.jsendRead({func:"getAllowedPeopleForCalender", category_id:currentEvent.category_id}, function(ok, data) {
    var form = new CC_Form();
    var invitable=false;
    if (data.length==0)
      form.addHtml('<p>Dem Kalender sind keine Personen oder Gruppen zugewiesen.');
    else {
      var dt = new Date();
      if (currentEvent.startdate<dt) {
        form.addHtml('<div class="alert alert-error">Besprechungsanfrage liegt in der Vergangenheit. Es finden so keine Email-Anfragen statt.</div>');
      }

      form.addHtml('<div class="person-selector"></div>');
      form.addHtml('<div style="height:360px;overflow-y:auto">');
      form.addHtml('<table class="table table-condensed">');
      function _addPerson(p) {
        var mr=null;
        if (currentEvent.meetingRequest!=null)
          mr=currentEvent.meetingRequest[p.id];
        form.addHtml('<tr data-id="'+p.id+'"><td>');
        if (p.email!="" && (mr==null || mr.invite))
          form.addCheckbox({htmlclass:"cb-person", checked:(mr!=null&&mr.invite), controlgroup:false,
              data:[{name:"id", value:p.id}]});
        form.addHtml('<td>'+form_renderPersonImage(p.imageurl, 40));
        form.addHtml('<td>'+p.vorname+" "+p.name);
        form.addHtml('<td><span class="status">');
        if (p.email=="")
          form.addHtml('<i>Keine E-Mail-Adresse!');
        else {
          if (mr!=null) {
            if (mr.invite)
              form.addHtml('wird eingeladen');
            else if (mr.response_date==null)
              form.addImage({src:"question.png",width:24, label:"Noch keine Antwort!"});
            else if (mr.zugesagt_yn==null)
              form.addImage({src:"check-64_sw.png",width:24, label:"Vorbehaltlich zugesagt"});
            else if (mr.zugesagt_yn==1)
              form.addImage({src:"check-64.png",width:24, label:"Zugesagt"});
            else if (mr.zugesagt_yn==0)
              form.addImage({src:"delete_2.png",width:24, label:"Abgesagt"});
          }
          else {
            form.addHtml('nicht eingeladen');
            invitable=true;
          }
        }
        form.addHtml('</span>');
      }
      each(data, function(k,a) {
        if (a.type=="gruppe") {
          form.addHtml('<tr><td colspan=4><h4>'+a.bezeichnung+'</h4>');
          each(a.data, function(i,p) {
            _addPerson(p);
          });
        }
      });
      form.addHtml('<tr><td colspan=4><h4>Personen</h4>');
      each(data, function(k,a) {
        if (a.type=="person") {
          _addPerson(a.data);
        }
      });
      form.addHtml('</table></div>');
    }

    elem.find("#meeting-request").html(form.render());
    if (invitable) {
      form = new CC_Form();
      form.addHtml('<p><span class="pull-right">');
      form.addButton({label:"Alle auswählen", htmlclass:"select-all"});
      form.addHtml("&nbsp; ")
      form.addButton({label:"Alle abwählen", htmlclass:"deselect-all"});
      form.addHtml('</span><i>Für eine Anfrage bitte Personen auswählen</i><br>');
      if (currentEvent.repeat_id!=0)
        form.addHtml('<small>Bei Wiederholungsterminen wird nur der erste Termin angefragt!</small>');
      form.addHtml('&nbsp;</p>');
      $('div.person-selector').html(form.render());
    }
    myelem=elem;
    elem.find("input.select-all").click(function() {
      elem.find("input.checkbox").each(function() {
        $(this).attr("checked","checked");
        $(this).trigger("change");
      });
    });
    elem.find("input.deselect-all").click(function() {
      elem.find("input.checkbox").each(function() {
        $(this).removeAttr("checked");
        $(this).trigger("change");
      });
    });
    elem.find("input.cb-person").change(function() {
      var id=$(this).attr("data-id");
      var checked=$(this).attr("checked")=="checked";
      if (checked) {
        elem.find("tr[data-id="+id+"]").find("span.status").html("wird eingeladen");
        if (currentEvent.meetingRequest==null) currentEvent.meetingRequest=new Object();
        if (currentEvent.meetingRequest[id]==null)
          currentEvent.meetingRequest[id]=new Object();
        currentEvent.meetingRequest[id].invite=true;
      }
      else {
        elem.find("tr[data-id="+id+"]").find("span.status").html("nicht eingeladen");
        if (currentEvent.meetingRequest[id]!=null)
          delete currentEvent.meetingRequest[id];
      }
    });
  });
}


/**
 * Renders the ChurchService pane in edit/create event
 * @param {[type]} elem
 */
function _renderViewChurchService(elem) {
  if (masterData.eventTemplate==null) {
    churchInterface.jsendRead({func:"getEventTemplates"}, function(ok, data) {
      if (!ok) alert("Fehler beim Laden der Templates: "+data);
      else {
        masterData.eventTemplate=data;
        if (masterData.eventTemplate==null) masterData.eventTemplate=new Object();
        _renderEditEventContent(elem);
      }
    }, null, null, "churchservice");
  }
  else {
    var rows = new Array();
    rows.push(form_renderCaption({text:'<p>Hier können für <i>'+masterData.churchservice_name+'</i>'
               +' Events angelegt und aufgerufen werden. Um Dienste anzugelegen bitte '
               +' eine entsprechende Vorlage auswählen. '}));
    if (currentEvent.copyevent && !currentEvent.isSeries()) {
      var form = new CC_Form(masterData.churchservice_name+' kopieren oder Template nutzen?');
      form.addCheckbox({label:"Alle Events und Dienstanfragen mit kopieren",
        checked:currentEvent.copychurchservice, cssid:"copychurchservice"}  );
      rows.push(form.render(null, "horizontal"));
    }
    rows.push('<div class="well">');

      rows.push('<table class="table-condensed table">');
      rows.push('<tr><th width="115px">Event-Datum<th>Event-Vorlage<th>');
      var isOneNew = false; moreThenOne = false;
      each(churchcore_getAllDatesWithRepeats(currentEvent), function(a,ds) {
        if (a>0) moreThenOne=true;
        rows.push('<tr data-id="'+a+'" data-date="'+ds.startdate.toStringEn(true)+'"><td>' + ds.startdate.toStringDe(true));
        var csEvent=null;
        var id = getCSEventId(currentEvent, ds.startdate);
        if (id==null) isOneNew=true;
        else csEvent = currentEvent.csevents[id];
        rows.push('<td>');
        rows.push(form_renderSelect({cssid:"eventTemplate"+a, htmlclass:"event-template", freeoption:true,
                                     controlgroup:false, type:"medium", disabled:(id!=null && id>=0),
                                     selected:(csEvent!=null?csEvent.eventTemplate:""), data:masterData.eventTemplate}));
        rows.push('<td>');
        if (id!=null && id>=0) rows.push('<a class="btn btn-small" href="?q=churchservice&id='+csEvent.id+'">Event aufrufen</a>');
        rows.push('<span class="event-hint"></span>');
      });
      rows.push('</table>');

    rows.push('</div>');

    elem.find("#cal_content").html(rows.join(""));
    
    function _refreshCSInfo() {
      elem.find("select.event-template").each(function() {
        var startdate = $(this).parents("tr").attr("data-date").toDateEn();
        var csEventId = getCSEventId(currentEvent, startdate);
        if (csEventId==null || csEventId<0) {
          if ($(this).val()!="") {
            $(this).parents("tr").find(".event-hint").html('<i>Event wird angelegt</i>');
            if (csEventId!=null) currentEvent.csevents[csEventId].eventTemplate = $(this).val();
            else addCSEvent(currentEvent, {startdate: startdate, eventTemplate: $(this).val()});
          }
          else {
            $(this).parents("tr").find(".event-hint").html('');
            if (csEventId!=null) delete currentEvent.csevents[csEventId];
          }
        }
      });
    }
    _refreshCSInfo();
    
    elem.find("#copychurchservice").change(function() {
      currentEvent.copychurchservice=$(this).attr("checked")=="checked";
      if (currentEvent.copychurchservice) {
        elem.find("select.event-template").val("");
        currentEvent.eventTemplate=null;
      }
      _refreshCSInfo();
    });
    var firstOne=true;
    elem.find("select.event-template").change(function() {
      // No copy anymore
      elem.find("#copychurchservice").removeAttr("checked");
      currentEvent.copychurchservice=false;
      var id = $(this).parents("tr").attr("data-id");
      if (isOneNew && moreThenOne && (id==0 || firstOne) && confirm("Soll es für alle Event übernommen werden?")) {
        firstOne=false;
        var newVal = $(this).val();
        elem.find("select.event-template").each(function() {
          if ($(this).attr("disabled")==null) $(this).val(newVal);
        });
      }
      _refreshCSInfo();
    });
  }
}

function _renderEditEventNavi(elem) {
  var navi = new CC_Navi();
  navi.addEntry(currentEvent.view=="view-main","view-main","Kalender");
  if ((masterData.category[currentEvent.category_id].oeffentlich_yn==0)
        && (masterData.category[currentEvent.category_id].privat_yn==0))
    navi.addEntry(currentEvent.view=="view-invite","view-invite","Besprechungsanfrage");
  if ((masterData.category[currentEvent.category_id].privat_yn==0) && (masterData.auth["view churchservice"]))
    navi.addEntry(currentEvent.view=="view-churchservice","view-churchservice",masterData.churchservice_name);
  if (masterData.auth["create bookings"] || masterData.auth["administer bookings"])
    navi.addEntry(currentEvent.view=="view-churchresource","view-churchresource",masterData.churchresource_name);
  navi.renderDiv("cal_menu", churchcore_handyformat());

  elem.find("ul.nav a").click(function() {
    if (currentEvent.view=="view-main") {
      currentEvent = $("#dates").renderCCEvent("getCCEvent");
      getCalEditFields(currentEvent);
    }
    currentEvent.view=$(this).attr("id");
    _renderEditEventNavi(elem);
    _renderEditEventContent(elem);
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
  if ($("#assistance_user").attr("data-id") != null) {
    o.modified_pid=$("#assistance_user").attr("data-id");
    o.modified_name=$("#assistance_user").attr("data-name");
  }
}


function renderEditEvent(myEvent, origEvent, isSeries, editSeries, func) {
    currentEvent = myEvent;
    currentEvent.view="view-main";
    if (currentEvent.bookings!=null) previousBookings=$.extend({}, currentEvent.bookings);
    if (currentEvent.enddate==null) {
      currentEvent.enddate=new Date(currentEvent.startdate);
      // Wenn es kein Ganztagstermin ist, dann setze Ende 1h rauf
      if (currentEvent.startdate.getHours()>0)
        currentEvent.enddate.setHours(currentEvent.startdate.getHours()+1);
    }
    if (currentEvent.bezeichnung==null) currentEvent.bezeichnung="";
    if (currentEvent.category_id==null) currentEvent.category_id=masterData.settings.category_id;
    if (!categoryEditable(currentEvent.category_id)) {
      each(masterData.category, function(k,a) {
        if (categoryEditable(k)) {
          currentEvent.category_id=k;
          return false;
        }
      });
    }

    if (currentEvent.category_id==null) {
      if (masterData["edit category"]!=null) {
        alert("Um einen Termin anzulegen, muss erst ein Kalender erstellt werden!");
        editCategory(null, 0);
      }
      return null;
    }
    var rows = new Array();

    rows.push('<div id="cal_menu"><br/></div>');
    rows.push('<div id="cal_content"></div>');

    var desc = "Termin";
    if (isSeries) {
      if (editSeries) desc = "Serie"; else desc = "Einzeltermin";
    }
    var elem=form_showDialog((currentEvent.id==null?"Neuen "+desc+" erstellen":desc+" editieren"), rows.join(""), 560, 600, {
      "Speichern": function() {
        if (currentEvent.bookings!=null) previousBookings=$.extend({}, currentEvent.bookings);
        if (currentEvent.view=="view-main") {
          currentEvent = $("#dates").renderCCEvent("getCCEvent");
          getCalEditFields(currentEvent);
        }
        if ($("#assistance_user").val()!=null && $("#assistance_user").val()!="") {
          if ($("#assistance_user").attr("disabled")==null) {
            if (user_access("create person")) {
              if (confirm("Person "+$("#assistance_user").val()+" nicht gefunden, soll ich sie anlegen?")) {
                form_renderCreatePerson($("#assistance_user").val(), function(personId, personName) {
                  currentEvent.modified_pid=personId;
                  currentEvent.modified_name=personName;
                  $("#assistance_user").val(currentEvent.modified_name);
                  $("#assistance_user").attr("disabled", true);
                });
              }
            }
            else
              alert("Die Person "+$("#assistance_user").val()+" wurde nicht gefunden!");
            return null;
          }
        }

        // Check if there are CSEvents to delete
        each(currentEvent.csevents, function(k, a) { a.mark=false; });
        each(churchcore_getAllDatesWithRepeats(currentEvent), function(a, ds) {
          var id = getCSEventId(currentEvent, ds.startdate);
          if (id!=null) currentEvent.csevents[id].mark=true;
        });
        each(currentEvent.csevents, function(k, a) {
          if (!a.mark) a.action = "delete";
        });
        currentEvent.informCreator = $("#inform_creator").attr("checked")=="checked";
        var myEvent = currentEvent.clone();
        delete myEvent.view;
        delete myEvent.minpre;
        delete myEvent.minpost;
        func(myEvent, function(ok) {if (ok) elem.dialog("close");});
      }
    });

    _renderEditEventContent(elem);

    if (isSeries && !editSeries) {
      elem.dialog('addbutton', 'Einzeltermin entfernen', function() {
        if (!confirm("Wirklich den Termin löschen?")) return null;
        currentEvent.addException(currentEvent.startdate);
        func(currentEvent, function(ok) {if (ok) elem.dialog("close");});
      });

    }
    else {
      if (isSeries) {
        elem.dialog('addbutton', 'Diesen und nachfolgende löschen', function() {
          var txt="Wirklich zukünftige Termine löschen?";
          if (confirm(txt)) {

            var d = new Date(currentEvent.startdate.withoutTime().getTime()); d.addDays(-1);
            newEvent = origEvent.clone();
            newEvent.repeat_until=d;
            deleteNewerExceptionsAndAdditions(newEvent, d, false);
            origEvent.prooveEventChangeImpact(newEvent, null, null, null, function(ok) {
              if (ok) {
                elem.dialog("close");
                newEvent.saveEvent(newEvent);
              }
            });
          }
        });
      }
      if (origEvent!=null && origEvent.id!=null) {
        elem.dialog('addbutton', desc + ' löschen', function() {
          var txt=desc + " '"+currentEvent.bezeichnung+"' wirklich löschen? Alle Dienste und Buchungen werden mit gelöscht!";
          if (confirm(txt)) {
            currentEvent = origEvent.clone();
            delEvent(currentEvent, function() {
              elem.dialog("close");
            });
          }
        });
      }
    }
    elem.dialog("addcancelbutton");
    if (currentEvent.modified_pid!=null && currentEvent.modified_pid!=settings.user.id)
      $("div.ui-dialog-buttonset").prepend(
          form_renderCheckbox({label:"Ersteller über Änderung informieren", checked:true,
                                controlgroup: false, cssid:"inform_creator"}));



    _renderEditEventNavi(elem, currentEvent);
}

function copyEvent(current_event, splitDate) {
  current_event.orig_id=current_event.id;
  current_event.doSplit(splitDate, true, function(newEvent, pastEvent) {
    newEvent.id = null;
    newEvent.csevents=null;
    newEvent.copyevent=true;
    newEvent.copychurchservice=false;
    renderEditEvent(newEvent, current_event, true, true, function(newEvent, func) {
      newEvent.save();
      func(true);
    });
  });
}


function delEvent(event, func) {
  calCCType.hideData(event.category_id);
  if (event.bookings!=null) {
    each(event.bookings, function(k,a) {
      a.status_id=99;
    });
  }
  churchInterface.jsendWrite({func:"deleteEvent", id:event.id}, function() {
    calCCType.needData(event.category_id, true);
    if (func!=null) func();
  });
}

function delEventFormular(event, func, currentDate) {
  var newEvent = event.clone();
  if (newEvent.repeat_id>0) {
    var txt="Es handelt sich um einen Termin mit Wiederholungen, welche Termine sollen entfernt werden?";
    var elem=form_showDialog("Was soll gelöscht werden?", txt, 380, 300);

    if (currentDate.getTime() == newEvent.startdate.getTime()) {
      elem.dialog('addbutton', 'Gesamte Serie löschen', function() {
        if (confirm("Es werden alle entsprechenden Termine, Anfragen und Buchungen dieser Serie gelöscht. Wirklich ausführen?")) {
          delEvent(newEvent, func);
        }
        $(this).dialog("close");
      });
    }
    else {
      elem.dialog('addbutton', 'Diesen und nachfolgende', function() {
        var d=new Date(currentDate.withoutTime().getTime());
        d.addDays(-1);
        newEvent.repeat_until=d;
        deleteNewerExceptionsAndAdditions(newEvent, d, false);
        elem.dialog("close");
        event.prooveEventChangeImpact(newEvent, null, null, null, function(ok) {
          if (ok) event.saveEvent(newEvent);
        });
      });
    }
    elem.dialog('addbutton', 'Nur aktueller', function() {
       newEvent.addException(currentDate);
       elem.dialog("close");
       event.prooveEventChangeImpact(newEvent, null, null, null, function(ok) {
         if (ok) newEvent.save();
       });
    });
    elem.dialog("addcancelbutton");
  }
  else {
    var txt="Termin '"+newEvent.bezeichnung+"' wirklich entfernen?";
    if (confirm(txt)) {
      delEvent(newEvent, func);
    }
  }
}

function _viewChanged(view) {
  if (debug) console.log("_viewChanged", view);

  if (saveSettingTimer!=null) window.clearTimeout(saveSettingTimer);
  saveSettingTimer=window.setTimeout(function() {
    if ((masterData.settings["viewName"]==null) || (masterData.settings["viewName"]!=view.name)) {
      masterData.settings["viewName"]=view.name;
      churchInterface.jsendWrite({func:"saveSetting", sub:"viewName", val:view.name});
    }
    var d = view.start.format(DATEFORMAT_EN).toDateEn();
    if (view.name=="month") d.addDays(8);
    if ((masterData.settings["startDate"]==null) || (masterData.settings["startDate"]!=d.toStringEn(false))) {
      masterData.settings["startDate"]=d.toStringEn(false);
      churchInterface.jsendWrite({func:"saveSetting", sub:"startDate", val:d.toStringEn(true)});
    }
    saveSettingTimer=null;
  },700);
}

function categoryEditable(category_id) {
  if (category_id==null || masterData.category[category_id]==null) return false;

  if (user_access("edit category", category_id))
    return true;

  return false;
}

/**
 * Checks for admin rights to change name and color of category
 * @param category_id
 * @returns {Boolean}
 */
function categoryAdminable(category_id) {
  if (!categoryEditable(category_id)) return false;

  var cat=masterData.category[category_id];

  if (cat.oeffentlich_yn==0 & cat.privat_yn==1 && user_access("admin personal category"))
    return true;
  else if (cat.oeffentlich_yn==0 & cat.privat_yn==0 && user_access("admin group category"))
    return true;
  else if (cat.oeffentlich_yn==1 & cat.privat_yn==0 && user_access("admin church category"))
    return true;

  if (masterData.category[category_id].modified_pid==masterData.user_pid) return true;

  return false;
}

function _eventClick(event, jsEvent, view ) {
  if (debug) console.log("_eventClick", event, jsEvent, view);
  clearTooltip(true);

  var myEvent=getEventFromEventSource(event);
  if ((myEvent!=null) && (categoryEditable(myEvent.category_id))
        && (!masterData.category[myEvent.category_id].ical_source_url)) {
    editEvent(myEvent, view.name=="month", event.start.format(DATETIMEFORMAT_EN).toDateEn(true), jsEvent);
  }
  else {
    var rows = new Array();
    rows.push('<legend>'+event.title+'</legend>');
    rows.push('<p>'+_("start.date")+': '+event.start.format(event.start.hasTime()?DATETIMEFORMAT_DE:DATEFORMAT_DE));
    if (event.end!=null)
      rows.push('<p>'+_("end.date")+': '+event.end.format(event.end.hasTime()?DATETIMEFORMAT_DE:DATEFORMAT_DE));
    if (myEvent!=null) {
      if (myEvent.notizen!=null) rows.push('<p>'+myEvent.notizen);
    }
    form_showOkDialog("Termin: "+event.title.html2csv(), rows.join(""), 400, 400);
  }

}

function _calcCalendarHeight() {
  if (printview) return 1000;
  else if (embedded) return 600;
  var height=$( window ).height()-150;
  if (height>1000) height=1000;
  else if (height<400) height=400;
  return height;
}

function initCalendarView() {
  calendar=$('#calendar');
  var d = null;
  if ($("#init_startdate").val() != null) var d = $("#init_startdate").val().toDateEn(true);
  else if (masterData.settings.startDate!=null)
    d = masterData.settings.startDate.toDateEn();
  else d = new Date();
  var height=$( window ).height()-150;
  if (height>1000) height=1000;
  if (viewName=="calView") {
    calendar.fullCalendar({
      header: {
        left: 'prev,next today',
        center: 'title',
        right: 'agendaDay,agendaWeek,month'
      },
      //aspectRatio: 1.7,
      firstDay:masterData.firstDayInWeek,
      contentHeight: _calcCalendarHeight(),
      defaultEventMinutes:90,
      editable: true,
      monthNames: getMonthNames(),
      weekNumbers: true,
      weekNumberTitle : "",
      monthNamesShort: monthNamesShort,
      dayNames: dayNames,
      dayNamesShort: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
      buttonText: {
        prev:     '<<',  // left triangle
        next:     '>>',  // right triangle
        prevYear: '<<', // <<
        nextYear: '>>', // >>
        today:    _("today"),
        month:    _("month"),
        week:     _("week"),
        day:      _("day")
      },
      timeFormat: {
        // for agendaWeek and agendaDay
        agenda: 'H:mm', // 5:00

        // for all other views
        month: "H(:mm)[h]"  // 7p
      },
      // No Text for allDay, not necessary
      allDayText:'',
      firstHour:10,
      defaultView:((masterData.settings!=null)&&(masterData.settings["viewName"]!=null)?masterData.settings["viewName"]:"month"),
      axisFormat:"H:mm",
      columnFormat : {
        month: 'ddd',    // Mon
        week: 'ddd D.M.', // Mon 9/7
        day: 'dddd D.M.'  // Monday 9/7
      },
      eventLimit: {
        'month': 6, // adjust to 6 only for agendaWeek/agendaDay
        'default': true // give the default value to other views
      },
      eventLimitText: _("more"),
      eventDragStart:  function() {clearTooltip(true); },
      eventResizeStart:  function() {clearTooltip(true); },
      eventDrop: _eventDrop,
      eventResize: _eventResize,
      viewRender: _viewChanged,
      eventClick: _eventClick,
      eventMouseover: _eventMouseover,
      eventMouseout: function(calEvent, jsEvent) {
        clearTooltip(false);
      },
      eventRender: function (event, element) {
        element.find('.fc-title').html(element.find('.fc-title').text());
      },
      selectable:true,
      selectHelper:true,
      select: _select
    });
    if (!embedded)
      $("div.fc-left").append("&nbsp; "+form_renderImage({src:"cal.png", width:28, htmlclass:"open-cal", link:true})+'<div style="position:absolute;width:220px;z-index:12001" id="dp_month"></div>');
    $("div.fc-left").append(" "+form_renderImage({src:"printer.png", width:28, htmlclass:"printview", link:true})+'<div style="position:absolute;z-index:12001" id="dp_month"></div>');
    if (!embedded) {
//      $("div.fc-right>div.fc-button-group").append('<button type="button" id="yearView" class="fc-button fc-state-default fc-corner-right">'+_("year")+'</button>');
      $("div.fc-right>div.fc-button-group").append('<button type="button" id="eventView" class="fc-button fc-state-default fc-corner-right"><i class="icon-list"></button>');
    }
    $( window ).resize(function() {
      calendar.fullCalendar('option', 'contentHeight', _calcCalendarHeight());
      style="overflow:scroll;height:'+(_calcCalendarHeight()+20)+'px"
    });
    $("#header").html("");
    calendar.fullCalendar( 'gotoDate', moment(d));
  }
  else if (viewName=="yearView") {
    calendar.yearCalendar({});
    $("#header").append('<button type="button" id="calView" style="overflow:inherit" class="fc-button fc-state-default fc-corner-right">'+_("calendar")+'</button>');
    $("#header").append('<button type="button" id="eventView" class="fc-button fc-state-default fc-corner-right"><i class="icon-list"></button>');
  }
  else if (viewName=="eventView") {
    var enddate=null;
    if ($("#init_enddate").val()!=null)
      enddate=$("#init_enddate").val().toDateEn(true);
    calendar.eventCalendar({startdate:d, enddate:enddate});
    $("#header").append(form_renderInput({controlgroup:false, cssid:"searchEntry", placeholder:_("search"),htmlclass:"input-medium search-query"}));
    $("#header").append('<button type="button" id="calView" style="overflow:inherit" class="fc-button fc-state-default fc-corner-right">'+_("calendar")+'</button>');
  }
  else
    alert("Unkwnown viewname!");
  $("a.open-cal").click(function() {
    form_implantDatePicker('dp_month', masterData.settings["startDate"].toDateEn(), function(dateText) {
      var viewdate=dateText.toDateDe();
      calendar.fullCalendar('gotoDate', viewdate);
      calendar.fullCalendar('render');
    });
  });

  $("a.printview").click(function() {
    if (printview) {
      window.print();
    }
    else {
      var fenster=window.open("?q=churchcal&embedded=true&printview=true", '_blank', 'location=yes,height=570,width=700,scrollbars=yes,status=yes');
      fenster.focus();
    }
  });

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
  else alert("Unbekannter send Calendar");
  
  if (b.category_id==filterCategoryIds && filterId!=null)
    editEvent(b.container.data[filterCategoryIds].events[filterId]);
}

function renderTooltip(event) {
  var rows = new Array();
  var title = event.title;
  rows.push('<div id="tooltip_inner" class="CalView">');
  rows.push('<ul><li>');

  var start_txt=event.start.format(event.start.hasTime()?DATETIMEFORMAT_DE:DATEFORMAT_DE);

  if (event.end==null)
    rows.push(start_txt);
  else {
    if (!event.end.isSame(event.start, "day")) {
      rows.push(start_txt);
      if (event.end.hasTime())
        rows.push(' - '+event.end.format(DATETIMEFORMAT_DE));
      else {
        var end=event.end.format(DATEFORMAT_EN).toDateEn(false);
        end.addDays(-1);
        if (end.toStringDe(false)!=start_txt)
        rows.push(' - '+end.toStringDe(false));
      }
    }
    else {
      rows.push(start_txt);
      rows.push(' - '+event.end.format("HH:mm"));
    }
  }

  var myEvent=getEventFromEventSource(event);
  if (myEvent!=null) {
    myEvent.allDay=event.allDay;
    title=myEvent.bezeichnung;
    if (myEvent.intern_yn==1) title=title+" (intern)";

    title=title+'<span class="pull-right">&nbsp;<nobr>';
    if (myEvent.startdate.getTime() > new Date().getTime()
           || form_getReminder("event", myEvent.id)!=null) {
      title=title + form_renderReminder("event", myEvent.id);
    }
    if (categoryEditable(myEvent.category_id)) {
      title=title+'&nbsp;'+form_renderImage({cssid:"copyevent", htmlclass:"copy", label:"Kopieren", src:"copy.png", width:20});
      title=title+"&nbsp;"+form_renderImage({cssid:"delevent", htmlclass:"delete", label:'Löschen', src:"trashbox.png", width:20});
    }
    title = title + "</nobr></span>";

    // Now get the service texts out of the events
    if (myEvent.csevents!=null) {
      each(myEvent.csevents, function(i,e) {
        if ((e.service_texts!=null) &&
             (e.startdate.toStringEn(false)==event.start.format(DATEFORMAT_EN))) {
          title=title+' <span class="event-servicetext">'+e.service_texts.join(", ")+'</span>';
          return false;
        }
      });
    }

    if ((myEvent.ort!=null) && (myEvent.ort!=""))
      rows.push('<li><b>'+myEvent.ort+'</b>');
    if (myEvent.category_id!=null)
      rows.push("<li>Kalender: <i>"+masterData.category[myEvent.category_id].bezeichnung+'</i>');
    if (myEvent.bookings!=null && masterData.resources!=null) {
      rows.push('<li>Resourcen: <small>');
      var r = new Array();
      each(myEvent.bookings, function(i,e) {
        if (masterData.resources[e.resource_id]!=null)
          r.push('<span class="cr-status-'+e.status_id+'">'
                  + masterData.resources[e.resource_id].bezeichnung.trim(30)+'</span>');
      });
      rows.push(r.join(", "));
      rows.push('</small>');
    }
    if ((myEvent.notizen!=null) && (myEvent.notizen!=""))
      rows.push('<li>'+_("comment")+': <small> '+myEvent.notizen.trim(60)+'</small>');
    if ((myEvent.link!=null) && (myEvent.link!=""))
      rows.push('<li>Link: <small> <a href="'+myEvent.link+'" target="_clean">'+myEvent.link+'</a></small>');

  }
  if (event.status!=null)
    rows.push('<li>Status: '+event.status);
  if (myEvent!=null && myEvent.modified_name!=null) {
    rows.push('<li><small>Erstellt von <span '
              + (myEvent.modified_date!=null ? 'title="Erstellt am '+myEvent.modified_date.toDateEn().toStringDe(true)+'"':'')
              + '>' + myEvent.modified_name+'</span></small>');
  }
  rows.push('</ul>');

  // A calender event
  if (myEvent!=null) {
    if ((!embedded) && (myEvent.bookings!=null) && (masterData.auth["view churchresource"]))
      rows.push('<a href="?q=churchresource&id='+myEvent.booking_id+'"><span class="label label-info">'+masterData.churchresource_name+'</label></a>&nbsp;');
    if ((!embedded) && (churchcore_countObjectElements(myEvent.csevents)>0))
      rows.push('<a href="?q=churchservice&id='+myEvent.event_id+'"><span class="label label-info">'+masterData.churchservice_name+'</label></a>&nbsp;');
    if (myEvent.meetingRequest!=null) {
      rows.push('<h5>Info Besprechungsanfrage</h5>');
      var confirm=0, decline=0, offen=0, perhaps=0;
      each(myEvent.meetingRequest, function(k,a) {
        if (a.zugesagt_yn==1) confirm=confirm+1;
        else if (a.zugesagt_yn==0) decline=decline+1;
        else if (a.response_date!=null) perhaps=perhaps+1;
        else offen=offen+1;
      });
      if (confirm>0)
        rows.push('<span class="badge badge-success">'+confirm+'</span> Zusage<br/>');
      if (perhaps>0)
        rows.push('<span class="badge">'+perhaps+'</span> Vielleicht<br/>');
      if (decline>0)
        rows.push('<span class="badge badge-important">'+decline+'</span> Absage<br/>');
      if (offen>0)
        rows.push('<span class="badge badge-info">'+offen+'</span> Offen');
    }
  }
  else {
    if (event.source==null) return null;

    if (event.bezeichnung!=null)
      title=title+" ("+event.bezeichnung+")";

    rows.push("<small><i>Termin von ");
    rows.push(event.source.container.data[event.source.category_id].name);
    rows.push("</i></small>");
  }

  rows.push('</div>');

  return [rows.join(""), title];
}

function _eventMouseover(event, jsEvent, view) {
  if (debug) console.log("_eventMouseover", event, jsEvent, view);

  if (event.source==null) return;

  // No tooltip when popupmenu is visible
  if ($("#popupmenu").data("popupmenu")!=null) return;

  var placement="bottom";
  if (jsEvent.pageX>$("#calendar").width()+$("#calendar").position().left-100)
    placement="left";
  else if (jsEvent.pageY>$("#calendar").height()+$("#calendar").position().top-150)
    placement="top";
  else if (jsEvent.pageX<$("#calendar").position().left+130)
    placement="right";

  $(this).tooltips({
    data:{id:"1", event:event},
    show:true,
    container:"#calendar",
    placement:placement,
    auto:false,
    render:function(data) {
      return renderTooltip(data.event);
    },
    afterRender:function(element, data) {
      var event=getEventFromEventSource(data.event);
      $("a.copy").click(function() {
        clearTooltip(true);
        copyEvent(event, data.event.start.format(DATETIMEFORMAT_EN).toDateEn(true));
        return false;
      });
      $("a.reminder").click(function() {
        clearTooltip(true);
        form_editReminder($(this), event.startdate);
        return false;
      });
      $("a.edit").click(function() {
        clearTooltip(true);
        editEvent(event);
        return false;
      });
      $("a.delete").click(function() {
        clearTooltip(true);
        currentDate=data.event.start;
        currentEvent=event;
        delEventFormular(event, null, data.event.start.format(DATETIMEFORMAT_EN).toDateEn(true));
        return false;
      });
    }
  });
  $(this).tooltips("show");
}

function createMultiselect(name, data) {
  var t=this;
  filter[name]=new CC_MultiSelect(data, function(id, selected) {
    masterData.settings[name]=this.getSelectedAsArrayString();
    churchInterface.jsendWrite({func:"saveSetting", sub:name, val:masterData.settings[name]});
    if (id=="allSelected") {
      if (filter[name]!=null) {
        each(filter[name].data, function(k,a) {
          if (churchcore_inArray(k,filter[name].selected))
            needData(name, k);
          else
            hideData(name, k);
        });
      }
    }
    else {
      if (name=="filterGemeindekalendar" && (id==6 || id==4)) {
        var counterpart=(id==4?6:4);
        if (filter[name].isSelected(counterpart)) {
          hideData(name, counterpart);
          filter[name].toggleSelected(counterpart);
          filterMultiselect("filterGemeindekalendar", (!embedded?masterData.maincal_name:"Kalender"));
        }
      }
      if (selected)
        needData(name, id);
      else
        hideData(name, id);
    }
  });
  if (name=="filterGemeindekalendar") {
    if ($('#filtercategory_select').val()!=null) {
      var arr= new Array();
      each($('#filtercategory_select').val().split(","), function(k,a) {
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
  var elem=form_showCancelDialog(_("load.data"),"");
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
  var elem=form_showCancelDialog(_("load.data"),"");
  churchInterface.jsendRead({func:"getAllowedPersons"}, function(ok, data) {
    elem.dialog("close");
    if (!ok) {
      alert("Fehler beim Holen der Personen: "+data);
      allPersons=new Array();
    }
    else {
      allPersons=new Object();
      if (data!=null) {
        each(data, function(k,a) {
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
  if (privat_yn==null) privat_yn=masterData.category[cat_id].privat_yn;
  if (oeffentlich_yn==null) oeffentlich_yn=masterData.category[cat_id].oeffentlich_yn;
  if (current.sortkey==null) current.sortkey=0;

  var form = new CC_Form((cat_id==null?"Kalender erstellen":"Kalender editieren"), current);

  if ((cat_id==null) && (privat_yn==0) && (oeffentlich_yn==0)) {
    if (masterData.groups==null) {
      _loadAllowedGroups(function() {editCategory(cat_id, privat_yn, oeffentlich_yn)});
      return false;
    }
    form.addSelect({label:"f&uuml;r Gruppe", freeoption:true, cssid:"accessgroup", data:masterData.groups});
    form.addCaption({text:"<p><small>Hier kann eine Gruppen angegeben werden, die automatisch die Berechtigung erh&auml;lt den Kalender zu sehen</small></p>"});
    form.addCheckbox({label:"Gruppenteilnehmer erhalten Schreibrechte", cssid:"writeaccess", checked:false});
  }
  form.addInput({label:_("caption"), cssid:"bezeichnung", required:true});
  //form.addInput({label:"Farbe", cssid:"color"});
  form.addHtml('<span class="color"></span>');
  form.addInput({label:_("sortkey"), cssid:"sortkey"});
  if (oeffentlich_yn == 1 || privat_yn == 1) {
    form.addInput({label:"Externe iCal-Quelle", cssid:"ical_source_url"});
    form.addCaption({text:"<p><small>Die iCal-Quelle wird einmal täglich automatisch aktualisiert."});
  }

  form.addHtml('<p><p><p class="pull-right"><small>#'+cat_id);

  var elem = form_showDialog((cat_id==null?"Kalender erstellen":"Kalender bearbeiten"), form.render(false, "horizontal"), 500, 500, {
    "Speichern": function() {
      var obj = form.getAllValsAsObject();
      if (obj.ical_source_url && masterData.category[cat_id]!=null &&
             !confirm("Achtung, wenn eine iCal-Source angegeben wird, werden alle bereits im Kalender "
             +masterData.category[cat_id].bezeichnung+" vorhandenen Termine unwiderruflich gelöscht!"))
        return;
      obj.color=current.color;
      obj.textColor=current.textColor;
      if (obj.color==null) obj.color="black";
      if (obj.textColor==null) obj.color="white";
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
          window.location.reload();
          elem.dialog("close");
          window.location.reload();
        }
      });
    },
    "Abbruch": function() {
      elem.dialog("close");
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
  var dlg=form_showCancelDialog(_("load.data"),"");
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


      var elem = form_showDialog("Kalender '"+masterData.category[cat_id].bezeichnung+"' freigeben", form.render(false, "horizontal"), 500, 500, {
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
            }
          });
        },
        "Abbruch": function() {
          elem.dialog("close");
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
    rows.push('<legend>Pers&ouml;nliche Kalender</legend>');
  else if (oeffentlich_yn==0)
    rows.push('<legend>'+_("group.calendar")+'</legend>');
  else
    rows.push('<legend>'+masterData.maincal_name+'</legend>');
  rows.push('<table class="table table-condensed">');
  rows.push('<tr><th width="20px"><th>Bezeichnung<th width="40px">');
  rows.push('<th width="25px">');

  rows.push('<th width="25px"><th width="25px">');

  each(churchcore_sortData(masterData.category, "sortkey"), function(k,cat) {
    if ((cat.oeffentlich_yn==oeffentlich_yn) && (cat.privat_yn==privat_yn)) {
      rows.push('<tr><td>'+form_renderColor(cat.color));
      rows.push('<td>'+cat.bezeichnung);
      rows.push('<td><a href="#" class="ical" data-id="'+cat.id+'"><span class="label">iCal</span></a>');
      if (categoryAdminable(cat.id)) {
        rows.push('<td>'+form_renderImage({src:"persons.png", width:20, htmlclass:"share", link:true, data:[{name:"id", value:cat.id}]}));
        rows.push('<td>'+form_renderImage({src:"options.png", width:20, htmlclass:"options", link:true, data:[{name:"id", value:cat.id}]}));
        rows.push('<td>'+form_renderImage({src:"trashbox.png", width:20, htmlclass:"delete", link:true, data:[{name:"id", value:cat.id}]}));
      }
      else rows.push('<td><td><td>');
    }
  });

  if (
       ((privat_yn==1) && (user_access("create personal category || admin personal category")))
       || ((privat_yn==0) && (oeffentlich_yn==0) && (user_access("create group category || admin group category")))
       || ((privat_yn==0) && (oeffentlich_yn==1) && (user_access("admin church category")))
      )
    rows.push('<tr><td><td><a href="#" class="add"><i>Neuen Kalender erstellen</i></a><td><td><td>'+form_renderImage({link:true, htmlclass:"add", src:"plus.png", width:20})+"<td>");

  rows.push('</table>');
  var elem = form_showDialog("Kalender verwalten", rows.join(""), 500, 500, {
    "Schliessen": function() {
      elem.dialog("close");
      if (reload) window.location.reload();
    }
  });

  elem.find("a.options, a.add").click(function() {
    elem.dialog("close");
    editCategory($(this).attr("data-id"), privat_yn, oeffentlich_yn);
    return false;
  });
  elem.find("a.share").click(function() {
    elem.dialog("close");
    shareCategory($(this).attr("data-id"), privat_yn, oeffentlich_yn);
    return false;
  });
  elem.find("a.ical").click(function() {
    showICalDialog($(this).attr("data-id"));
    return false;
  });
  elem.find("a.delete").click(function() {
    var id=$(this).attr("data-id");
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

function showICalDialog(id) {
  var rows=new Array();
  rows.push('<legend>Kalender abonnieren</legend>Der Kalender kann abonniert werden. Hierzu kann die Adresse anbei in einen beliebigen Kalender importiert werden,'+
             ' der iCal unterst&uuml;tzt.<br><br>');
  rows.push(form_renderInput({label:"<a target='_clean' href='"+masterData.base_url+"?q=churchcal/ical&security="+masterData.category[id].randomurl+"&id="+id+"'>iCal-URL</a>", htmlclass:"ical-link", value:masterData.base_url+"?q=churchcal/ical&security="+masterData.category[id].randomurl+"&id="+id, disable:true}));
  var elem=form_showOkDialog("Kalender abonnieren", rows.join(""));
  elem.find("input.ical-link").select();

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
    else if (id==2) calMyAbsentsType.needData(0);
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
    else if (id==2) calMyAbsentsType.hideData(0);
    else if (id==4) calBirthdayType.hideData(0);
    else if (id==5) calAbsentsType.hideData(0);
    else if (id==6) calAllBirthdayType.hideData(0);
  }
}

function renderChurchCategories() {
  var rows= new Array();
  if (viewName!="yearView") {
    var sortkey=0;
    var form=new CC_Form();

    if (!embedded) {
      form = new CC_Form(masterData.maincal_name+" "+form_renderImage({cssid:"edit_church", src:"options.png", top:8, width:24, htmlclass:"pull-right"}));
      form.setHelp("Gemeindekalender");
    }

    oeff_cals=new Object();
    if (churchcore_countObjectElements(masterData.category)>0) {
      if (embedded) {
        var dabei=false;
        each(churchcore_sortMasterData(masterData.category), function(k,a) {
          if ((a.oeffentlich_yn==0) && (a.privat_yn==0) && ((filterCategoryIds==null) || (churchcore_inArray(a.id, filterCategoryIds)))) {
            dabei=true;
            var title=a.bezeichnung;
            form_addEntryToSelectArray(oeff_cals,(a.id*1+100),title,sortkey);
            sortkey++;
          }
        });
        if (dabei) form_addEntryToSelectArray(oeff_cals,-2,'-',sortkey);  sortkey++;
      }

      each(churchcore_sortMasterData(masterData.category), function(k,a) {
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
        form_addEntryToSelectArray(oeff_cals,4,_("birthdays")+' (Gruppen)',sortkey, true);  sortkey++;
        form_addEntryToSelectArray(oeff_cals,6,_("birthdays")+' (Alle)',sortkey, true);  sortkey++;
      }
      else {
        form_addEntryToSelectArray(oeff_cals,6,_("birthdays"),sortkey);  sortkey++;
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
    /*
    if ((filterCategoryIds==null || !embedded) && (masterData.auth["view churchresource"])) {
      createMultiselect("filterRessourcen", masterData.resources);
      form.addHtml('<div id="filterRessourcen"></div>');
      each(masterData.resourceTypes, function(k,a) {
        filter["filterRessourcen"].addFunction(a.bezeichnung+" w&auml;hlen", function(b) {
          return b.resourcetype_id==a.id;
        });
      });

    }*/

    rows.push(form.render(true));
  }
  return rows.join("");
}

function renderFilterCalender() {
  var rows = new Array ();

  //rows.push('<legend>Kalenderauswahl</legend>');

  rows.push('<div class="well filterCalender" style="overflow:scroll;height:'+(_calcCalendarHeight()+20)+'px">');
  rows.push('<div style="white-space: nowrap">');
  rows.push('<ul class="ct-ul-list">');

  function _renderCalenderEntry(name, id, color, textColor, desc) {
    var rows = new Array ();
    if (color==null) color="#000000";
    rows.push('<li class="hoveractor">');
    // Editable category, not personal calendar like service cal
    if (id>6) {
      rows.push('<span class="pull-right dropdown" data-id="'+id+'"><a href="#" class="dropdown-toggle" data-toggle="dropdown" data-id="'+id+'"><i class="icon-cog icon-white"/></a>');
      rows.push('<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">');
      rows.push('<li><a href="#" class="options show-ical">iCal Link anzeigen</a></li>');
      if (categoryAdminable(id-100)) {
        rows.push('<li><a href="#" class="options share">Freigabe-Einstellungen</a></li>');
        rows.push('<li><a href="#" class="options edit">Weitere Optionen</a></li>');
      }
      if (getNotification("category", id-100)===false)
        rows.push('<li><a href="#" class="options notification">Email-Abo einrichten</a></li>');
      else
        rows.push('<li><a href="#" class="options notification">Email-Abo bearbeiten</a></li>');
      rows.push('<li class="divider"></li>');
      rows.push('<li><a href="#" class="options enable-all">Alle aktivieren</a></li>');
      rows.push('<li><a href="#" class="options disable-all">Alle anderen deaktivieren</a></li>');
      rows.push('</ul></span>');
      rows.push('</span></span>');
    }

    var checked=churchcore_inArray(id, churchcore_getArrStrAsArray(masterData.settings[name]));
    if (masterData.settings[name]==null && id>100) checked=true;
    if (filterCategoryIds!=null && churchcore_inArray(id-100, filterCategoryIds)) checked=true;
    if (filterCategoryIds==null && masterData.settings[name]==null && name=="filterGemeindekalendar") checked=true;
    rows.push('<span '+(checked?'checked="checked"':'') + ' '
                +'data-id="'+(id)+'" '
                +'data-name="'+name+'" '
                +'data-color="'+color+'" '
                +'data-textColor="'+textColor+'" '
                +'data-label="'+desc+'" '
                +'class="colorcheckbox" id="filterCalender_'+id+'"></span>');
//                +'data-textColor="'+textColor+'" '
    return rows.join("");
  }

  // Church Calendar
  if (!embedded && user_access("admin church category")) {
    rows.push(form_renderImage({cssid:"edit_church", src:"options.png", top:0, width:24, htmlclass:"pull-right"}));
  }
  rows.push('<h4>'+masterData.maincal_name);
  rows.push('</h4>');

  each(churchcore_sortData(masterData.category,"sortkey"), function(k, cat) {
    if ((cat.oeffentlich_yn==1) && (cat.privat_yn==0)) {
      rows.push(_renderCalenderEntry("filterGemeindekalendar", cat.id*1+100, cat.color, cat.textColor, cat.bezeichnung));
    }
  });
  rows.push('</ul>');

  // Group calendar
  var rows_cal = new Array();
  each(churchcore_sortData(masterData.category,"sortkey"), function(k, cat) {
    if ((cat.oeffentlich_yn==0) && (cat.privat_yn==0)) {
      rows_cal.push(_renderCalenderEntry("filterGruppenKalender", cat.id*1+100, cat.color, cat.textColor, cat.bezeichnung));
    }
  });
  if (!embedded && (rows_cal.length>0 || user_access("create group category"))) {
    rows.push('<ul class="ct-ul-list">');
    if (user_access("admin group category") || user_access("create group category"))
      rows.push(form_renderImage({cssid:"edit_group", src:"options.png", top:0, width:24, htmlclass:"pull-right"}));
    rows.push('<h4>'+_("group.calendar"));
    rows.push('</h4>');
    rows.push(rows_cal.join(""));
    rows.push('</ul><ul class="ct-ul-list">');
  }

  // Personal Calendar
  var rows_cal = new Array();
  each(churchcore_sortData(masterData.category,"sortkey"), function(k, cat) {
    if ((cat.oeffentlich_yn==0) && (cat.privat_yn==1)) {
      rows_cal.push(_renderCalenderEntry("filterPersonalKalender", cat.id*1+100, cat.color, cat.textColor, cat.bezeichnung));
    }
  });
  if (user_access("view churchservice")) {
    rows_cal.push(_renderCalenderEntry("filterPersonalKalender", 1, "blue", "white", 'Meine Dienste'));
    rows_cal.push(_renderCalenderEntry("filterPersonalKalender", 2, "lightgreen", "green", 'Meine Abwesenheiten'));
  }
  if (masterData.auth["view churchdb"]) {
    if (masterData.auth["view alldata"]) {
      rows_cal.push(_renderCalenderEntry("filterPersonalKalender", 4, "lightblue", "blue", _("birthdays")+' (Gruppen)'));
      rows_cal.push(_renderCalenderEntry("filterPersonalKalender", 6, "lightblue", "blue", _("birthdays")+' (Alle)'));
    }
    else {
      rows_cal.push(_renderCalenderEntry("filterPersonalKalender", 6, "lightblue", "blue", _("birthdays")));
    }
  }
  if (user_access("view churchservice")) {
    rows_cal.push(_renderCalenderEntry("filterGruppenKalender", 5, "red", "white", "Abwesenheit in Gruppen"));
  }
  if (!embedded && rows_cal.length>0 || user_access("create personal category") || user_access("admin personal category")) {
    rows.push('<ul class="ct-ul-list">');
    if (user_access("admin group category") || user_access("create group category"))
      rows.push(form_renderImage({cssid:"edit_personal", src:"options.png", top:0, width:24, htmlclass:"pull-right"}));
    rows.push('<h4>'+_("personal.calendar"));
    rows.push('</h4>');
    rows.push(rows_cal.join(""));
    rows.push('</ul><ul class="ct-ul-list">');
  }

  $("#filterCalender").html(rows.join(""));

  $( window ).resize(function() {
     $("div.filterCalender").css("height",(_calcCalendarHeight()+20)+"px");
  });
  $("#filterCalender").find("a.options").click(function() {
    var id=$(this).parents("span").attr("data-id");
    if (id>100) id=id-100;
    if ($(this).hasClass("edit")) {
      editCategory(id);
    }
    else if ($(this).hasClass("show-ical")) {
      showICalDialog(id);
    }
    else if ($(this).hasClass("share")) {
      shareCategory(id);
    }
    else if ($(this).hasClass("notification")) {
      form_editNotification("category", id);
    }
    else if ($(this).hasClass("enable-all")) {
      each(masterData.category, function(k,a) {
        if (a.oeffentlich_yn == masterData.category[id].oeffentlich_yn
             && a.privat_yn == masterData.category[id].privat_yn)
          $("span.colorcheckbox[data-id="+(a.id*1+100)+"]").colorcheckbox("check", true);
      });
    }
    else if ($(this).hasClass("disable-all")) {
      each(masterData.category, function(k,a) {
        if (a.oeffentlich_yn == masterData.category[id].oeffentlich_yn
          && a.privat_yn == masterData.category[id].privat_yn
          && a.id != id)
          $("span.colorcheckbox[data-id="+(a.id*1+100)+"]").colorcheckbox("check", false);
        });
    }

  });

  $(".colorcheckbox").each(function() {
    $(this).colorcheckbox({
      checked : $(this).attr("checked")=="checked",
      color: $(this).attr("data-color"),
      textColor: $(this).attr("data-textColor"),
      name: $(this).attr("data-name"),
      label: $(this).attr("data-label"),
      id: $(this).attr("data-id"),
      change: function(checked, id, name) {
        var arr = churchcore_getArrStrAsArray(masterData.settings[name]);
        if (checked) {
          needData("", id);
          arr.push(id);
        }
        else {
          hideData("", id);
          arr.splice(arr.indexOf(id),1);
        }
        masterData.settings[name]="["+arr.join(",")+"]";
        churchInterface.jsendWrite({func:"saveSetting", sub:name, val:masterData.settings[name]});
      }
    });
    if ($(this).attr("checked")=="checked") {
      needData("", $(this).attr("data-id"));
    }
  });
/*
  $( window ).resize(function() {
    $(".options").each(function() {
      $(this).css("padding-left", $(this).parents("li").width());
    });
  });

  $(".options").each(function() {
    $(this).css("margin-left", $(this).parents("li").width()-20);
  });*/

  $(".hoveractor").off("hover");
  $(".hoveractor").hover(
      function () {
        $(this).find("span.hoverreactor").fadeIn('fast',function() {});
      },
      function () {
        $(this).find("span.hoverreactor").fadeOut('fast');
      }
    );
}

$(document).ready(function() {
  churchInterface.setModulename("churchcal");
  if ($("#filtercategory_id").val()!=null) {
    filterCategoryIds=$("#filtercategory_id").val().split(",");
    filterId=$("#filterevent_id").val();
  }

  if ($("#printview").length!=0) {
    printview=true;
    $("#cdb_filter").hide();
  }
  $("#cdb_content").append('<div id="popupmenu"></div>');
  if ($("#isembedded").length!=0) embedded=true;
  if ($("#isminical").length!=0) minical=true;
  if ($("#entries").length!=0) max_entries=$("#entries").val();

  churchInterface.loadMasterData(function() {

    if ($("#viewname").val()!=null) viewName=$("#viewname").val();
    initCalendarView();
    var rows= new Array();

    if ((viewName=="eventView") && (!embedded)) {
      rows.push('<div id="minicalendar" style="height:240px; max-width:350px"></div><br/><br/><br/>');
    }
    if (!embedded) {
      rows.push('<div id="filterCalender"></div>');
      //rows.push(renderPersonalCategories());
      //rows.push(renderGroupCategories());
      //rows.push(renderChurchCategories());
    }
    else {
      // All together in one well div.
      rows.push(renderChurchCategories());
    }


    $("#cdb_filter").html(rows.join(""));
    renderFilterCalender();

    if (!embedded) {
      filterMultiselect("filterMeineKalender", _("personal.calendar"));
    }
    else {
      filterMultiselect("filterGemeindekalendar", _("calendar"));
    }
    //filterMultiselect("filterGruppenKalender", _("group.calendar"));
    //filterMultiselect("filterRessourcen", _("resources"));

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
      return false;
    });
    $("#edit_group").click(function() {
      editCategories(0, 0);
      return false;
    });
    $("#create_group_cal").click(function() {
      editCategory(null, 0, 0);
      return false;
    });
    $("#edit_church").click(function() {
      editCategories(0, 1);
      return false;
    });
    $("#showminical").click(function() {
      $("#minicalendar").toggle();
      return false;
    });
    $("#abo").click(function() {
      var rows=new Array();
      rows.push('<legend>Kalender abonnieren</legend>Die &ouml;ffentlichen Termine dieses Kalenders k&ouml;nnen abonniert werden. Hierzu kann die Adresse anbei in einen beliebigen Kalender importiert werden,'+
                 ' der iCal unterst&uuml;tzt.<br><br>');
      var id=$(this).attr("data-id");
//      rows.push(form_renderInput({label:"iCal-URL", value:masterData.base_url+"?q=churchcal/ical", disable:true}));
      rows.push(form_renderInput({label:"<a href='"+masterData.base_url+"?q=churchcal/ical'>iCal-URL</a>", value:masterData.base_url+"?q=churchcal/ical", disable:true}));
      form_showOkDialog("Kalender abonnieren", rows.join(""));
      return false;
    });

    //Sucht sich die Kalender zusammen die neu geholt werden m�ssen
    each(filter, function(k,a) {
      if (a.data!=null)
      each(a.data, function(i,s) {
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
