 
// Constructor
function ListView(options) {
  StandardTableView.call(this, options);
  this.name="ListView";
  this.sortVariable="startdate";
//  this.filter["searchFuture"]=true;
  var d=new Date();
  this.currentDate=d.withoutTime();
  this.allDataLoaded=false;
  this.renderTimer=null;
  this.serviceGroupPersonWeight=null;
  this.availableRowCounts=[3,10,25];
}

Temp.prototype = StandardTableView.prototype;
ListView.prototype = new Temp();
listView = new ListView();


ListView.prototype.getData = function(sorted) {
  if (allEvents==null) return null;
  
  if (sorted)
    return churchcore_sortData(allEvents,this.sortVariable);
  else
    return allEvents;
};


ListView.prototype.renderMenu = function() {
  this_object=this;
  
  menu = new CC_Menu(_("menu"));
  
  if (masterData.auth.write)
    menu.addEntry(_("create.new.event"), "create-new-event", "star");

  if (masterData.auth.admin) {  
    menu.addEntry(_("workload"), "workload", "fire");
  }

  menu.addEntry(_("settings"), "settings", "wrench");    

  if (masterData.auth.admin)  
    menu.addEntry(_("maintain.masterdata"), "maintain-masterdata", "cog");

  menu.addEntry(_("printview"), "aprintview", "print");
  menu.addEntry(_("help"), "ahelp", "question-sign");

  if (!menu.renderDiv("cdb_menu",churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {    
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="create-new-event") {
        this_object.renderAddEntry();
      }
      else if ($(this).attr("id")=="avorlage") {
        this_object.editTemplates(); 
      }
      else if ($(this).attr("id")=="workload") {
        this_object.showAuslastung(); 
      }
      else if ($(this).attr("id")=="aprintview") {
        var filter='&date='+t.currentDate.toStringEn(false);
        if (this_object.filter["filterMeine Filter"]!=null)
          filter=filter+"&meineFilter="+this_object.filter["filterMeine Filter"];
        fenster = window.open('?q=churchservice/printview'+filter+'#ListView', "Druckansicht", "width=900,height=600,resizable=yes");
        fenster.focus();
        return false;
      }
      else if ($(this).attr("id")=="aaddfilter") {
        if (!this_object.furtherFilterVisible) {
          this_object.furtherFilterVisible=true;  
        } else {
          this_object.furtherFilterVisible=false;                
        } 
        this_object.renderFurtherFilter();
      }
      else if ($(this).attr("id")=="aadmin") {
        menuDepth=$(this).attr("id");
        this_object.renderMenu();
      }
      else if ($(this).attr("id")=="settings") {
        menuDepth="amain";
        churchInterface.setCurrentView(settingsView);
      }
      else if ($(this).attr("id")=="maintain-masterdata") {
        menuDepth="amain";
        churchInterface.setCurrentView(maintainView);
      }
      else if ($(this).attr("id")=="amain") {
        menuDepth="amain";
        this_object.renderMenu();
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchService");
      }
      return false;
    });
  }    
};

ListView.prototype.renderListMenu = function() {
  var this_object=this;
  
  var navi = new CC_Navi();
  navi.addEntry(churchInterface.isCurrentView("ListView"),"alistview","Dienstplan");
  if (masterData.auth.manageabsent)
    navi.addEntry(churchInterface.isCurrentView("CalView"),"acalview","Abwesenheiten");
  if (masterData.auth.viewfacts)
    navi.addEntry(churchInterface.isCurrentView("FactView"),"afactview","Fakten");
  if (allAgendas!=null || user_access("view agenda"))
    navi.addEntry(churchInterface.isCurrentView("AgendaView"),"aagendaview","Abläufe");
  if (masterData.auth.viewsong)
    navi.addEntry(churchInterface.isCurrentView("SongView"),"asongview","Songs");
  
  navi.addSearch(this.getFilter("searchEntry"));
  navi.renderDiv("cdb_search", churchcore_handyformat());
  this.implantStandardFilterCallbacks(this, "cdb_search");         
  
  $("#cdb_search a").click(function () {
    if ($(this).attr("id")=="alistview") {
      listView.furtherFilterVisible=this_object.furtherFilterVisible;
      churchInterface.setCurrentView(listView);
    }
    else if ($(this).attr("id")=="acalview") {
      calView.furtherFilterVisible=this_object.furtherFilterVisible;
      churchInterface.setCurrentView(calView);
    }
    else if ($(this).attr("id")=="afactview") {
      factView.furtherFilterVisible=this_object.furtherFilterVisible;
      factView.currentDate=this_object.currentDate;
      churchInterface.setCurrentView(factView);
    }
    else if ($(this).attr("id")=="aagendaview") {
      churchInterface.setCurrentView(agendaView);
    }
    else if ($(this).attr("id")=="asongview") {
      churchInterface.setCurrentView(songView);
    }
    return false;
  });  
};

ListView.prototype.addSecondMenu = function() {
  return '<p class="pull-right"><small><a id="ical_abo" href="#">Dienstplan abonnieren per iCal</a></small>';
};

/**
 * In den Diensten die man leitet wird nun jeweils am Start der Anwendung angezeigt, was es an �nderungen gegeben hat zum letzten Besuch der Dienstliste
 */
ListView.prototype.showLastChanges = function() {
  var this_object=this;
  
  // Erstmal die aktuelle Zeit zum Speichern r�bersenden
  var _d= new Date();
  churchInterface.jsendWrite({func:"saveSetting", sub:"lastVisited", val:_d.toStringEn(true)});

  // Variablen vorbereiten
  if (masterData.settings.lastVisited!=null) { 
    var _lastVisited=masterData.settings.lastVisited.toDateEn();
//    var test="2011-04-28 01:01"; _lastVisited=test.toDateEn();
  }
  else 
    _lastVisited=_d.toStringEn(true);
  var _text="";
  var _counter=0;

  // Nun alle Gruppen durchgehen und �nderungen nach dem letzten lastVisited anzeigen  
  $.each(this.getData(true), function(k,event) {
    _first=true;
    if ((event.services!=null) && (event.startdate.toDateEn()>_d)) {
      $.each(event.services, function(i,service) {
        if ((service.valid_yn==1) && (service.user_id!=masterData.user_pid) 
               && (masterData.auth.leaderservice[service.service_id])) {
          var _history=this_object.renderEntryHistory(event.id, service.service_id, service.counter, _lastVisited, true);
          if (_history!="") {
            if (_first) {
              _first=false;
              _counter=_counter+1;
              _text=_text+"<tr><td>"+event.startdate.toDateEn(true).toStringDe(true)+"<br><b>"+event.bezeichnung+"</b><td>";
            }
            _text=_text+masterData.service[service.service_id].bezeichnung;
            _text=_text+_history;
          }
        }        
      });
    }
  });
  
  // Anzeige der Box, wenn es �ndeerungen gab
  if (_text!="") {
    this.showDialog("Neuigkeiten innerhalb Deiner Gruppe", "<table class=\"table table-condensed\">"+_text+"</table>", 600, 600, {
      "Sp\u00e4ter nochmal zeigen": function() {
        churchInterface.jsendWrite({func:"saveSetting", sub:"lastVisited", val:_lastVisited.toStringEn(true)});
        $(this).dialog("close");
      },
      "Schliessen": function() {
        $(this).dialog("close");
      }
    });
  }
};

ListView.prototype.messageReceiver = function(message, args) {
  var this_object = this;
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      //this_object.renderList();
      // Wenn ein Event per Url �bergeben wurde, dann soll er es gleich �ffnen.
      if ($("#id").val()!=null) {
        if ($("#eventservice_id").val()!=null) 
          this_object.renderEditEventService($("#event_id").val(), $("#eventservice_id").val());
      }
      if ($(window).width()>600)
        this_object.showLastChanges();
      this_object.allDataLoaded=true;
    }
    else if (message=="pollForNews") {
      cs_loadNewEventData(churchInterface.getLastLogId(), function(events) {
        $.each(events, function(k,a) {
          this_object.renderList(allEvents[a]);
        });
      });
    }  
//    else if (message=="filterChanged") {
/*      Habe es wieder herausgenommen, da es doch zu Verwirrungen f�hren kann.
 *      if (args[0]=="filterMeine Filter") {
        masterData.settings.filterMeineFilter=this.getFilter("filterMeine Filter");
        churchInterface.jsendWrite({func:"saveSetting", sub:"filterMeineFilter",
                   val:this.getFilter("filterMeine Filter")});
      }*/
//    }
  }  
};

function _getEditEventFromForm() {
  obj=new Object();
  if ($("#EventId").val()!=null)
    obj.id=$("#EventId").val();
  obj.category_id=$("#Inputcategory").val();
  obj.bezeichnung=$("#InputBezeichnung").val();
  
  
  obj.stunde=$("#InputDatumStunde").val();
  obj.minute=$("#InputDatumMinute").val();
  var _datum=new Date();
  _datum=$("#InputDatumTag").val().toDateDe();
  _datum.setHours(obj.stunde);
  _datum.setMinutes(obj.minute);
  
  obj.startdate=_datum.toStringEn(true);

  if ($("#InputDatumTagEnde").val()!=null) {
    var _datum=new Date();
    _datum=$("#InputDatumTagEnde").val().toDateDe();
    _datum.setHours($("#InputDatumStundeEnde").val());
    _datum.setMinutes($("#InputDatumMinuteEnde").val());
    obj.enddate=_datum.toStringEn(true);
  }
  
  obj.special=$("#InputSpecial").val();
  obj.admin=$("#InputAdmin").val();
  
  var services = new Object();
  $("#in_edit input").each(function (i) {
    if ($(this).attr("id").indexOf("cb_")==0) {
      if ($(this).attr("checked")) 
        services[$(this).attr("id").substr(3,99)]=1;
      else if (($(this).val()>0)) 
        services[$(this).attr("id").substr(3,99)]=$(this).val();
    }
  });
  obj.services=services;    
  
  return obj;
}

function _getTemplateIdFromName(name) {
  var res=null;
  $.each(masterData.eventtemplate, function(k,a) {
    if (a.bezeichnung.toUpperCase()==name.toUpperCase()) {
      res=a.id;
      // exit
      return false;
    }
  });
  return res;
}

/**
 * 
 * @param event
 * @param template_name
 * @param func function(new_template_id)
 * @return null
 */
ListView.prototype.saveEventAsTemplate = function (event, template_name, func) {
  event.func="saveTemplate";
  event.event_bezeichnung=event.bezeichnung;
  event.bezeichnung=template_name;
  event.template_id=_getTemplateIdFromName(template_name);
  event.dauer_sec=(event.enddate.toDateEn(true).getTime()-event.startdate.toDateEn(true).getTime())/1000;
  churchInterface.jsendWrite(obj, null, false);
  cdb_loadMasterData(function() {
    func(_getTemplateIdFromName(template_name));    
  });
};

/**
 * template: wenn �bergeben, dann speichert er die �nderungen in das Template
 */
ListView.prototype.saveEditEvent = function (elem) {
  var this_object=this;
  var obj=_getEditEventFromForm();
  obj.func="saveEvent";
  
  if (($("#EventId").val()!=null) && (obj.startdate.toDateEn(true).getTime()!=allEvents[$("#EventId").val()].startdate.toDateEn(true).getTime()) 
     && (!confirm("Durch die Anpassung des Datum verschiebt sich auch der Kalendereintrag und ggfl. weitere Ressourcenanfragen. Soll wirklich gespeichert werden?")))
    return null;
  
  var repeats=$("#InputRepeats").val();
  if (repeats==null) repeats=1;
  
  var wiederholungbis=$("#InputDatumTagWiederholungEnde").val();
  if (wiederholungbis==null) wiederholungbis=new Date(obj.startdate.toDateEn());
  else wiederholungbis=wiederholungbis.toDateDe(true);
  wiederholungbis.addDays(1);
  
  elem.html("<p>");
  

  // Save Data for sending it asynchron 
  _array=new Array();
  var d=obj.startdate.toDateEn(true);
  var e=obj.enddate.toDateEn(true);
  while (d<wiederholungbis) {
    _array.push(jQuery.extend({}, obj));
    // Add one week
    d.addDays(7); e.addDays(7);
    obj.startdate=d.toStringEn(true);
    obj.enddate=e.toStringEn(true);
  }
  var i=0;
  
  function _recurs(ok, json) {
    if (!ok) {
      alert("Fehler: "+json);
    } 
    else {
      if (_array[i]!=null) {
        churchInterface.jsendWrite(_array[i],_recurs);
        elem.html("<b>Daten werden gespeichert f&uuml;r Woche "+i+"...</b><br/><br/>");
      }              
      else {
        // Array ist rausgeschrieben, nun kann ich neuladen
        elem.html("<b>Lade nun Eventliste neu...</b>");  
        cs_loadEventData(null, function(){
          elem.dialog("close");
          this_object.renderList();
        });            
      }
    }
    i=i+1;
  }
  
  _recurs(true);
};

ListView.prototype.renderEditEvent = function(event) {
  var t=this;
  var rows=new Array();
  var template=null;
  
  if (event.bookings) {
    rows.push('<div class="alert alert-error">Achtung: Das Event enth&auml;lt auch Ressourcen-Buchungen, deshalb Datum & Uhrzeit in <a href="?q=churchcal&date='+event.startdate.toDateEn(false).toStringEn(false)+'">'+masterData.churchcal_name+'</a> bearbeiten. </div>');    
  }
  
  rows.push('<div id="in_edit">');
  rows.push('<div style="float:left;wid_th:50%">');
  
  rows.push('<table>');
  
  // Wenn Event neu erstellt wird
  if (event.id==null) { 
    if (masterData.settings.aktuelleEventvorlage==null)
      masterData.settings.aktuelleEventvorlage=0;
    template=masterData.eventtemplate[masterData.settings.aktuelleEventvorlage];
    if (template!=null) {
      if (template.category_id!=null) 
        event.category_id=template.category_id;
      event.bezeichnung=template.event_bezeichnung;
      event.special=template.special;
      var d=event.startdate.toDateEn();
      if (template.stunde!=null)
        d.setHours(template.stunde);
      if (template.minute!=null)
        d.setMinutes(template.minute);
      event.startdate=d.toStringEn(true);
      var e=event.startdate.toDateEn(true);
      if (template.dauer_sec!=null)
        e.setSeconds(e.getSeconds()+template.dauer_sec);
      else
        e.setHours(e.getHours()+1);
      event.enddate=e.toStringEn(true);
      event.special=template.special;
      event.admin=template.admin;
    }
    rows.push('<tr><td>Vorlage<td>');
    //+this.renderSelect(masterData.settings.aktuelleEventvorlage, "eventtemplate", masterData.eventtemplate));
    
    rows.push(form_renderSelect({
      data:masterData.eventtemplate, 
      cssid:"Inputeventtemplate", 
      selected:masterData.settings.aktuelleEventvorlage, 
      htmlclass:"input-medium", 
      controlgroup:false
    }));
    
    if (masterData.auth["edit template"]) {
      rows.push("&nbsp; <a href=\"#\" id=\"saveTemplate\" title=\"Vorlage speichern\">" +this.renderImage("save", 20)+"</a>");
      if (template.id!=0)
        rows.push("&nbsp;<a href=\"#\" id=\"deleteTemplate\" title=\"Vorlage entfernen\">" +this.renderImage("delete_2", 20)+"</a>");      
    }
    
  }

  var minutes = new Array();
  form_addEntryToSelectArray(minutes, 0, "0");
  form_addEntryToSelectArray(minutes, 15, "15");
  form_addEntryToSelectArray(minutes, 30, "30");
  form_addEntryToSelectArray(minutes, 45, "45");
  var hours = new Array();
  for (var i=0;i<24;i++) {
    form_addEntryToSelectArray(hours, i, i, i);
  }
  if (event.id!=null)
    rows.push('<input type=hidden id="EventId" value="'+event.id+'"/>');
  
  rows.push("<tr><td>"+form_renderInput({
    cssid:"InputDatumTag",
    label:"Datum",
    controlgroup:false,
    separator:"<td><nobr>",
    value:event.startdate.toDateEn().toStringDe(),
    type:"small",
    disabled:event.bookings
  })+"&nbsp;");  
  
  rows.push(form_renderSelect({
    data:hours, 
    cssid:"InputDatumStunde", 
    selected:event.startdate.toDateEn().getHours(), 
    htmlclass:"input-mini", 
    disabled:event.bookings,
    controlgroup:false
  })+" : ");
  
  rows.push(form_renderSelect({
    data:minutes, 
    cssid:"InputDatumMinute", 
    selected:event.startdate.toDateEn().getMinutes(), 
    type:"mini", 
    disabled:event.bookings,
    controlgroup:false
  }));  
  rows.push("</nobr>");
  rows.push("<div id=\"dp_inputdatumtag\" style=\"position:absolute;background:#e7eef4;z-index:8001;\"/>");
  
  rows.push("<tr><td>"+form_renderInput({
    cssid:"InputDatumTagEnde",
    label:"Bis",
    controlgroup:false,
    separator:"<td><nobr>",
    value:event.enddate.toDateEn().toStringDe(),
    disabled:event.bookings,
    type:"small"
  })+"&nbsp;");  
  
  rows.push(form_renderSelect({
    data:hours, 
    cssid:"InputDatumStundeEnde", 
    selected:event.enddate.toDateEn().getHours(), 
    htmlclass:"input-mini", 
    disabled:event.bookings,
    controlgroup:false
  })+" : ");
  
  
  rows.push(form_renderSelect({
    data:minutes, 
    cssid:"InputDatumMinuteEnde", 
    selected:event.enddate.toDateEn().getMinutes(), 
    type:"mini", 
    disabled:event.bookings,
    controlgroup:false
  }));  
  rows.push("</nobr>");
  rows.push("<div id=\"dp_inputdatumtagende\" style=\"position:absolute;background:#e7eef4;z-index:8001;\"/>");

  
  
  rows.push('<tr><td>Kalender<td>'+this.renderSelect(event.category_id, "category", t.prepareCategoriesForSelect()));
  rows.push('<tr><td>'+this.renderInput("InputBezeichnung", "Bezeichnung", event.bezeichnung, 20));
  rows.push('<tr><td>'+this.renderTextarea("InputSpecial", "Weitere Infos", event.special, 20,3));
  rows.push('<tr><td>'+this.renderInput("InputAdmin", "Event-Admin", event.admin, 20, !masterData.auth.admin));
  rows.push('<p><small><span id="adminName">Kommaseparierte Person-Ids, dazu Name eintippen.</span></small></p>');
  if ((event.admin!=null) && (event.admin!="") && (masterData.auth.viewchurchdb)) {
    churchInterface.jsendRead({func:"getPersonById", id:event.admin}, function(ok, json) {
      var s = "";
      if (json.data!=null) {
        $.each(json.data, function(k,a) {
          if (s!="") s=s+"<br/>";
          s=s+a.vorname+" "+a.name;
        });
      }
      $("#adminName").html(s);
    }, null, null, "churchdb"); 
  }
    
  
  if (event.id==null) { 
    var a = new Array();
    for (var i=1;i<10;i++) {
      var b = new Array();
      b.id=i;
      b.bezeichnung=i+"";
      a[i]=b;
    }
//    rows.push('<tr><td>Wiederholungen<td>'+this.renderSelect("InputRepeats", "Repeats", a)+"<p><small>Anzahl der zu erstellenden Wochen");    
    rows.push('<tr><td>Wiederholung bis<td>'+form_renderInput({
      cssid:"InputDatumTagWiederholungEnde",
      controlgroup:false,
      value:event.startdate.toDateEn().toStringDe(),
      type:"small"}));
    rows.push("<div id=\"dp_inputdatumtagwiederholung\" style=\"position:absolute;background:#e7eef4;z-index:8001;\"/>");    
    rows.push("<p><small>W&ouml;chentlich Wiederholung des Events</small>");    
  }
  if (event.id!=null)
    rows.push('<p align="right"><small>Id:'+event.id+"</small>");

  rows.push("</table><br/>");
  rows.push('</div>');

  
  // Nun alle Services hinzuf�gbar machen
  
  rows.push('<div style="wid_th:40%;float:right;padding-right:20px;">');
  rows.push('<table>');

  if (event.id==null) {
    $.each(churchcore_sortData(masterData.servicegroup,"sortkey"),function(k,sg) {
      rows.push('<tr><th colspan=2>'+sg.bezeichnung+'<td>');
      
      $.each(masterData.service_sorted, function(i,s) {
        if (s.servicegroup_id==sg.id) {
          rows.push('<tr><td>'+s.bezeichnung+'&nbsp;');
          if ((s.notiz!=null) && (s.notiz!=""))
            rows.push('<small>('+s.notiz.trim(10)+')</small>');
          var count=0;
          if ((masterData.eventtemplate_services!=null) && (masterData.eventtemplate_services[template.id]!=null)) {
            if (masterData.eventtemplate_services[template.id][s.id]!=null)
              count=masterData.eventtemplate_services[template.id][s.id];
          } 
          else {
            // Anonsten schaue, ob es Eintr�ge gibt
            if (event.services!=null) {
              var entries=0;
              $.each(event.services, function(j,e) {
                if (e.service_id==s.id) entries=entries+1; 
              });
              if (entries>0) count=entries;
            }
          }
          rows.push('<td width=10px><input type="checkbox" id="cb_'+s.id+'" class="cdb-checkbox"');
          if (count>0) rows.push("checked");
          rows.push('/>');
        }      
      });    
    });
  }
  rows.push('</table>');
  rows.push('</div>');
  
  
  rows.push("<br/>");
  rows.push('</div>');
  rows.push('<div style="clear:both">');

  var this_object=this;

  var elem = this.showDialog("Veränderung des Events", rows.join(""), (event.id==null?680:450), (event.id==null?600:500), {
    "Speichern": function() {
      if ($("#Inputcategory").val()<0) alert("Bitte einen Kalender auswählen!");
      else if ($("#InputBezeichnung").val()=="") alert("Bitte eine Bezeichnung angeben!");
      else {
        this_object.saveEditEvent(elem);
        // Wenn es neu ist, dann soll das Datum gesetzt werden, damit der neue Eintrag sichtbar wird.
        if (event.id==null) {
          this_object.currentDate=event.startdate.toDateEn(false);
        }
      }
    }
  });
  
  this.autocompletePersonSelect("#InputAdmin", false, function(divid, ui) {
    $("#adminName").html(ui.item.label);
  });

  $("#saveTemplate").click(function() {
    var res=prompt("Bitte den Namen der Vorlage angeben:",template.bezeichnung);
    if (res!=null && res!="")
      this_object.saveEventAsTemplate(_getEditEventFromForm(), res, function(template_id) {
        masterData.settings.aktuelleEventvorlage=template_id;
        elem.dialog("close");
        this_object.renderEditEvent(event);        
      });
    return false;
  });
  $("#deleteTemplate").click(function() {
    if (confirm("Soll die Vorlage "+template.bezeichnung+" wirklich entfernt werden?")) {
      churchInterface.jsendWrite({func:"deleteTemplate", id:template.id}, null, false);
      delete masterData.eventtemplate[template.id];
      elem.dialog("close");
      masterData.settings.aktuelleEventvorlage=0;      
      this_object.renderEditEvent(event);        
    }    
  });
  
  
  
  $("#InputDatumTag").click(function() {
    this_object.implantDatePicker("inputdatumtag", event.startdate.toDateEn().toStringDe(), function(dateText) {
      event.startdate=dateText.toDateDe().toStringEn();
      $("#InputDatumTag").val(dateText);
      $("#InputDatumTagEnde").val(dateText);
      $("#InputDatumTagWiederholungEnde").val(dateText);
    });
  });
  $("#InputDatumTagEnde").click(function() {
    this_object.implantDatePicker("inputdatumtagende", $("#InputDatumTagEnde").val(), function(dateText) {
      if (dateText.toDateDe(false)<event.startdate.toDateEn(false)) alert("Enddatum kann nicht vor dem Anfangsdatum liegen!");
      else {
        event.enddate=dateText.toDateDe().toStringEn();
        $("#InputDatumTagEnde").val(dateText);
        $("#InputDatumTagWiederholungEnde").val(dateText);
      }
    });
  });
  $("#InputDatumTagWiederholungEnde").click(function() {
    this_object.implantDatePicker("inputdatumtagwiederholung", $("#InputDatumTagWiederholungEnde").val(), function(dateText) {
      if (dateText.toDateDe(false)<event.startdate.toDateEn(false)) alert("Wiederholungsdatum kann nicht vor dem Startdatum liegen!");
      else {
        event.widerholungende=dateText.toDateDe().toStringEn();
        $("#InputDatumTagWiederholungEnde").val(dateText);
      }
    });
  });
  
  $("#InputDatumStunde").change(function() {
    $("#InputDatumStundeEnde").val($(this).val()*1+1);
  });
  
  
  $("#in_edit select").change(function (a) {
    if ($(this).attr("id")=="Inputeventtemplate") {
      masterData.settings.aktuelleEventvorlage=$(this).val();
      event.category_id=$("#Inputcategory").val();
      elem.dialog("close");
      this_object.renderEditEvent(event);
    }
  });
  
  $("#in_edit input").click(function (a) {    
    if (($(this).attr("id").indexOf("cb_")==0) && (!$(this).attr("checked"))) {
      var service_id=$(this).attr("id").substr(3,99);
      var no=false;
      if (event.services!=null)
        $.each(event.services, function(i,b) {
          if ((b.service_id==service_id) && (b.valid_yn==1) && (b.name!=null)) {
            no=true;
            //exit
            return false;
          }
        });
      if (no) {
        alert("Solange hier ein Dienst vorgeschlagen oder zugesagt ist, ist das Entfernen nicht erlaubt!");
        $(this).attr("checked",true);
      }
    }
  });
    
  
  if (event.id!=null) {
    elem.dialog('addbutton', 'Event absagen', function() {
      var form = new CC_Form();
      form.addCheckbox({cssid:"informDeleteEvent", label:"Alle angefragten Personen über die Absage informieren?", checked:true});
      form.addCheckbox({cssid:"deleteCalEntry", label:"Auch Kalendereintrag entfernen (wird bei Wiederholungsterminen ignoriert!)"});
      var elem2 = form_showDialog("Absagen des Events", form.render(null, "vertical"), 300, 300, {
        "Absagen": function() {    
          obj=form.getAllValsAsObject();
          obj.func="deleteEvent";
          obj.id=event.id;       
          churchInterface.jsendWrite(obj, function(ok, json) {
            if (!ok) alert("Fehler beim Speichern: "+json);
            else {
              delete allEvents[event.id];
              elem2.dialog("close");
              elem.dialog("close");
              this_object.renderList();
            }
          });  
        },
      "Abbrechen": function() {
        elem2.dialog("close");      
      }
      });
    });
  }
  elem.dialog('addbutton',"Abbrechen", function() {
    $(this).dialog("close");
  });

};  

ListView.prototype.renderAddEntry = function() {
  
  var event = new Object();
  var d = new Date(this.currentDate);
  d.setHours(12);
  event.startdate=d.toStringEn(true);

  this.renderEditEvent(event);
};

ListView.prototype.isLeaderOfServiceGroup = function (sg_id) {
  if (sg_id==null) return false;
  
  isleader=false;
  $.each(masterData.service, function(k,a) {
    if ((a.servicegroup_id==sg_id) && (masterData.auth.leaderservice[a.id]==true)) {
      isleader=true;
      //exit 
      return false;
    }      
  });
  return isleader;
};

/**
 * Findet heraus, ob die User_Pid ein Teilnehmer o.�. ist bei einer komma-separierten Liste von g_ids
 * g_ids: Komma separierte Liste
 * user_pid: user_pid
 */

ListView.prototype.getMemberOfOneGroup = function(g_ids, user_pid) {
  if ((g_ids==null) || (user_pid==null) || (groups==null))
    return false;

  var g_id=g_ids.split(",");
  var ret=false;
  $.each(g_id, function(k,a) {
    if (groups[a]!=null) {
      $.each(groups[a], function(i,b) {
        if ((b.p_id==user_pid) && (b.leiter!=-1)) {
          ret=b;
        }
      }); 
    }
  });
  return ret;
};

/**
 * Checks if the service has no tag or if, then the person has the same tag
 * @param user_pid
 * @param service
 * @returns {Boolean}
 */
ListView.prototype.checkPersonHasOneTagFromService = function (user_pid, service) {
  // No tag, so it is ok for me
  if (service.cdb_tag_ids==null) return true;
  var tag_ids=service.cdb_tag_ids.split(",");
  var ok=false;
  $.each(service.cdb_gruppen_ids.split(","), function(k,g) {
    if (groups!=null && groups[g]!=null) {
      $.each(groups[g], function(i,b) {
        if ((b.p_id==user_pid) && (_checkPersonTag(tag_ids, b.tags))) {
          ok=true;
          return false;
        }
      });
    }
  });  
  return ok;
};

/**
 * 
 * @param event - Das Event
 * @param services - Der konkrete Dienst aus dem Objekt Event
 * @return Html-Code
 */
ListView.prototype.renderEventServiceEntry = function(event_id, services, bin_ich_admin) {
  var t=this;
  
  if ((services.valid_yn==1) && (masterData.service[services.service_id]!=null)) {
    var rows = new Array();
    rows.push("<p style=\"line-height:1.0;margin-bottom:4px;\">");
    var edit=false;
    var service=masterData.service[services.service_id];
    var isMemberOfGroup=masterData.auth.memberservice[services.service_id]==true;
    var isLeaderOfOneGroup=masterData.auth.leaderservice[services.service_id]==true;
    
    // Check the permission to edit:
    // 1. Drupal-Rechte durch EditGroup
    // 2. Wenn die Gruppe eine PersonenId hat und ich selber die Person bin
    // 3. Noch keiner eingetragen hat und ich in einer der Gruppen bin
    // 4. Wenn ich Leiter oder Mitarbeiter der Gruppe bin.
    // 5. Wenn ich Admin des Events bin
    if ((masterData.auth.editservice[services.service_id]) ||
          ((services.cdb_person_id!=null) && (services.cdb_person_id==masterData.user_pid)) ||
          ((services.name==null) && (isMemberOfGroup && t.checkPersonHasOneTagFromService(masterData.user_pid, service))) ||
          ((isLeaderOfOneGroup)) ||
          ((bin_ich_admin)))
      edit=true;
  
    var seeHistory=isLeaderOfOneGroup || (masterData.auth.editservice[services.service_id]) || bin_ich_admin;
    var tooltip='data-tooltip-id="'+event_id+"_"+services.id+'" '+(seeHistory?"history=true":"")+' '+(isMemberOfGroup?"member=true":""+' ');
    
    rows.push('<small>');
    if (edit) 
      rows.push('<a href="#" class="tooltips" '+tooltip+' id="edit_es_'+event_id+'" eventservice_id="'+services.id+'" style="text-decoration:none">');
    else 
      rows.push('<span class="tooltips" '+tooltip+'>');
    _class='';
    if ((services.cdb_person_id!=null) && (services.cdb_person_id==masterData.user_pid))
      if (services.zugesagt_yn==1)
        _class="zugesagt";
      else
        _class="angefragt";
 
    if ((this.filter["filterDienstgruppen"]==null) || 
         ((masterData.settings.listViewTableHeight!=null) && (masterData.settings.listViewTableHeight==0))) {
      rows.push('<font class="'+_class+'"><b>'+service.bezeichnung);
      if (services.counter!=null) rows.push(" "+services.counter);      
        rows.push(': </b></font>');
    }
    _class='';
    style='';
    if (services.zugesagt_yn==0) style=style+"color:red;";
    if ((services.cdb_person_id!=null) && (services.cdb_person_id==masterData.user_pid))                    
      if (services.zugesagt_yn==1)
        _class="zugesagt";
      else
        _class="angefragt";
    
    rows.push('<font class="'+_class+'" style="'+style+'">');
    
    var name=services.name;
    if ((service.cdb_gruppen_ids!=null) && (services.name==null))
      name=services.cdb_person_id;
  
    if (name!=null) rows.push(name.trim(18));
    if ((name==null) || (services.zugesagt_yn==0)) rows.push("?");
  
    rows.push('</font>');
    if (edit) rows.push('</a>');
    else rows.push('</span>'); 
    rows.push('</small>');
    return rows.join("");
  }  
  return "";
};

ListView.prototype.groupingFunction = function (event) {
  return event.startdate.toDateEn(false).getDayInText()+", "+event.startdate.toDateEn(false).toStringDe();
};
  
ListView.prototype.getCountCols = function() {
  var this_object=this;
  var r=0;
  var this_object=this;
  if (this.filter["filterDienstgruppen"]==null) {
    $.each(masterData.servicegroup, function(k,a) {
      if ((masterData.auth.viewgroup[a.id]) || (this_object.filter["filterMeine Filter"]==2)) {
        if ((masterData.settings["viewgroup"+a.id]==null) || (masterData.settings["viewgroup"+a.id]==1))
          r++;
      }  
    });
    r++;
  }
  else {
    $.each(masterData.service, function(k,a) {
      if (a.servicegroup_id==this_object.filter["filterDienstgruppen"]) {
        r++;
      }
    });
  }
  return r+2;
};

ListView.prototype.getAdditionalServicesToServicegroup = function (event, sg_id, bin_ich_admin) {
  this_object=this;
  var choseable = new Array();
  $.each(masterData.service_sorted, function(k,a) {
    if ((a.servicegroup_id==sg_id) && (
          (masterData.auth.write)
          || ((masterData.auth.editgroup!=null && masterData.auth.editgroup[sg_id]))
          || (masterData.auth.leaderservice[a.id]) 
          || (bin_ich_admin))) {      
      if ((masterData.auth.editgroup[sg_id]) || (this_object.isLeaderOfServiceGroup(sg_id)) || (bin_ich_admin)) {
        var isdrin=0;
        var isfrei=true;
        if (event.services!=null) {
          $.each(event.services, function(l,b) {
            if ((b.service_id==a.id) && (b.valid_yn==1)) {
              isdrin++;
              if (b.name!=null) isfrei=false;
            }
          });
        }
//        if ((isdrin==0) || (isfrei)) {
          var arr = new Array();
          arr.id=a.id;
          arr.count=isdrin;
          if (isdrin>0)
            arr.checked="checked";
          choseable.push(arr);
//        }
      }
    }    
  });
  return choseable;
};

function bin_ich_admin(admin) {
  if (admin==null) return false;
  var res=false;  
  $.each(admin.split(","), function(k,a) {
    if ($.trim(a)==masterData.user_pid) {
      res=true;
      //exit;
      return false;
    }    
  });
  return res;
}
  
ListView.prototype.renderListEntry = function(event) {
  this_object=this;
  var _bin_ich_admin=bin_ich_admin(event.admin);
  var _soll_zeigen=_bin_ich_admin; // Wenn ich kein Admin und es keinerlei Inhalt gibt, dann brauche ich es nicht zu sehen
  rows=new Array();

  var width=100/(this.getCountCols()-1);

  if (masterData.category[event.category_id].color!=null)
    rows.push('<div title="Kalender: '+masterData.category[event.category_id].bezeichnung+'" style="background-color:'+masterData.category[event.category_id].color+'; margin-top:5px; margin-left:3px; width:4px; height:15px"></div>');
  rows.push('<td class="hoveractor">');

  var agendaview=event.agenda && (user_access("view agenda", event.category_id) || t.amIInvolved(event));
  if (agendaview)
    rows.push('<b><a href="#" id="detail'+event.id+'">' + event.startdate.toDateEn(true).toStringDeTime() + " "+event.bezeichnung+"</a></b>&nbsp; ");
  else
    rows.push("<b>" + event.startdate.toDateEn(true).toStringDeTime(true)+" "+event.bezeichnung+"</b>&nbsp; ");
  
  if (masterData.auth.write)
    rows.push(form_renderImage({src:"options.png", hover:true, width:18, htmlclass:"edit-event", label:"Editieren", link:true}));

  rows.push("<br/>");

  if ((event.special!=null) && (event.special!=""))
    rows.push("<div class=\"event_info\">"+event.special.htmlize()+"</div>");
  
  var _authMerker=masterData.auth.write || _bin_ich_admin;
  // Check if I am a leader of the group
  if (!_authMerker)
    $.each(masterData.service, function(k,a) {
      if (masterData.auth.leaderservice[a.id]) {
        _authMerker=true;
        //exit
        return false;
      }
    });
  if (_authMerker) rows.push("<a href=\"#\" id=\"editNote" + event.id + "\" title=\"Editiere 'Weitere Infos'\">" +this.renderImage("info")+"</a>&nbsp;");
  // Check if I am in one of the services, so I am allowed to uplaod files
  if ((!_authMerker) && (event.services!=null)) {
    $.each(event.services, function(k,a) {
      if ((a.valid_yn==1) && (masterData.user_pid==a.cdb_person_id)) {
        _authMerker=true;
        return false;
      }
    });
  }
  
  if (_authMerker) rows.push("<a href=\"#\" id=\"attachFile" + event.id + "\" title=\"Datei zum Event anh&auml;ngen\">" +this.renderImage("paperclip")+"</a>&nbsp;");

  if (event.services!=null)
    rows.push("<a href=\"#\" id=\"mailEvent" + event.id + "\" title=\"Erstelle E-Mail an Personen des Events\">" +this.renderImage("email")+"</a>&nbsp;");
  if (event.admin!=null)
    if (_bin_ich_admin) 
      rows.push("<a href=\"#\" id=\"filterMyAdmin" + event.id + "\" title=\"Du bist Admin!\">" +this.renderImage("person")+"</a>&nbsp;");
    else
      rows.push(this.renderImage("person_sw",null,"Das Event hat einen Admin.")+"&nbsp;");
  
  if (!event.agenda && user_access("edit agenda", event.category_id))
    rows.push(form_renderImage({src:"agenda_plus.png", htmlclass:"show-agenda", link:true, label:"Ablaufplan zum Event hinzufügen", width:20}));
  else if (agendaview)
    rows.push(form_renderImage({src:"agenda.png", htmlclass:"show-agenda", link:true, label:"Ablaufplan anzeigen", width:20}));
  
  rows.push('<div class="filelist" data-id="'+event.id+'"></div>');
    
  // When no filterDienstgruppe is selected, it show all Services, sorted by ServiceGroup
  if (this.filter["filterDienstgruppen"]==null) {
    $.each(this.sortMasterData(masterData.servicegroup, "sortkey"), function(k,sg) {
      var is_leader=false;
      if ((masterData.settings["viewgroup"+sg.id]==null) || (masterData.settings["viewgroup"+sg.id]==1)) {
        if ((masterData.auth.viewgroup[sg.id]) || (this_object.filter["filterMeine Filter"]==2)) {
          rows.push('<td valign="top" class="service hoveractor" data-servicegroup-id="'+sg.id+'" style="position:relative" width="'+width+'%">');
          $.each(masterData.service_sorted, function(i,s) {
            if (sg.id==s.servicegroup_id) {
              if (masterData.auth.leaderservice[s.id]==true) is_leader=true;
              if (event.services!=null) {
                var history="";
                $.each(churchcore_sortData(event.services,"counter", false, false), function(j,services) {
                  if ((services.service_id==s.id)) {
                    rows.push(
                      this_object.renderEventServiceEntry(event.id, services, _bin_ich_admin)
                    );
                    _soll_zeigen=true;
                  }
                });
              }  
            }      
          });
          // Show "+" to add furhter Services
          if (masterData.auth.write 
                  || (masterData.auth.editgroup!=null && masterData.auth.editgroup[sg.id]) 
                  || _bin_ich_admin 
                  || is_leader) {
//            rows.push('<p><a href="#" id="addService" event_id="'+event.id+'" servicegroup_id="'+sg.id+'">'+form_renderImage({hover:true, src:"options.png", width:16})+'</a>');
            rows.push('<p>'+form_renderImage({hover:true, htmlclass:"edit-service", src:"options.png", width:16, data:[{name:"servicegroup-id", value:sg.id}, {name:"event-id", value:event.id}], link:true}));
            _soll_zeigen=true;
          }
        }
    }
    });
    rows.push('<td width="16px">');
  }
  // Wenn eine Dienstgruppe ausgew�hlt ist
  else {   
    $.each(masterData.service_sorted, function(k,s) {
      if ((event.services!=null) && (s.servicegroup_id==this_object.filter["filterDienstgruppen"])) {
        rows.push("<td>");
        $.each(event.services, function(i,services) {
          if ((services.service_id==s.id) && (services.valid_yn==1)) {
            rows.push(
              this_object.renderEventServiceEntry(event.id, services, _bin_ich_admin)
            );
            _soll_zeigen=true;
          }
        });
      }  
    });
  }
  if (_soll_zeigen)
    return rows.join("");
  else return null;
};


ListView.prototype.prepareCategoriesForSelect = function(multiselect) {
  var data=new Object();
  if (multiselect==null) multiselect=false;
  var sortkey=1;
  $.each(churchcore_sortMasterData(masterData.category), function(k,c) {
    if (c.privat_yn==0 && c.oeffentlich_yn==0) {
      form_addEntryToSelectArray(data, c.id, c.bezeichnung, sortkey);
      sortkey++;
    }
  });
  if (churchcore_countObjectElements(data)>0) {
    if (multiselect) 
      form_addEntryToSelectArray(data, -1 , '-', sortkey);
    else {
      form_addEntryToSelectArray(data, -2 , '== Gruppenkalender ==', 0);
      form_addEntryToSelectArray(data, -1 , '== Gemeindekalender == ', sortkey);
    }
    sortkey++;
  }
  $.each(churchcore_sortMasterData(masterData.category), function(k,c) {
    if (c.privat_yn==0 && c.oeffentlich_yn==1) {
      form_addEntryToSelectArray(data, c.id, c.bezeichnung, sortkey);
      sortkey++;
    }
  });
  return data;
};

ListView.prototype.makeFilterCategories = function(start_string) {
  var t=this;
  
  t.filter["filterKategorien"]=new CC_MultiSelect(t.prepareCategoriesForSelect(true), function(id, selected) {
    masterData.settings.filterCategory=this.getSelectedAsArrayString();
    churchInterface.jsendWrite({func:"saveSetting", sub:"filterCategory", val:masterData.settings.filterCategory});
    t.renderList();
  });
  t.filter["filterKategorien"].setSelectedAsArrayString(start_string);
  
};

ListView.prototype.getListHeader = function() {
  var this_object=this;
  $("#cdb_group").html("");
  currentTooltip=null;
  
  if (masterData.settings.listMaxRowsListView>25)
    masterData.settings.listMaxRowsListView=25;

  if (masterData.settings.listViewTableHeight==null) masterData.settings.listViewTableHeight=1;
  if ($("#printview").val()!=null)
    masterData.settings.listViewTableHeight=null;

  if ($("#externmeineFilter").val()!=null) {
    this.filter["filterMeine Filter"]=$("#externmeineFilter").val();
    $("#externmeineFilter").remove();
  }
  
  if ((masterData.settings.filterCategory=="") || (masterData.settings.filterCategory==null)
      || ($("#externevent_id").val()!=null))
    delete masterData.settings.filterCategory;
  if (this.filter["filterKategorien"]==null) {
    this_object.makeFilterCategories(masterData.settings.filterCategory);
    this.filter["filterKategorien"].setSelectedAsArrayString(masterData.settings.filterCategory);
    this.filter["filterKategorien"].render2Div("filterKategorien", {label:"Kalender"});
  }

/*  if ((masterData.settings.filterMeineFilter=="") || (masterData.settings.filterMeineFilter==null))
    masterData.settings.filterMeineFilter=null;
  else 
    this.filter["filterMeine Filter"]=masterData.settings.filterMeineFilter;
  */
  this.currentRenderDate=null;
  
  tableHeader='<th><a href="#" id="sortdatum">Events</a>';
  
  if (this.filter["filterDienstgruppen"]==null) {
    $.each(this.sortMasterData(masterData.servicegroup), function(k,a) {
      if ((masterData.settings["viewgroup"+a.id]==null) || (masterData.settings["viewgroup"+a.id]==1))
        if ((masterData.auth.viewgroup[a.id]) || (this_object.filter["filterMeine Filter"]==2)) {
          tableHeader=tableHeader+'<th class="hoveractor" id="header'+a.id+'">'+a.bezeichnung;
          tableHeader=tableHeader+'<span id="headerspan'+a.id+'" class="hoverreactor pull-right" >'+
                  '<a href="#" id="delCol'+a.id+'">'+this_object.renderImage("minus",16)+'</a></span>';
        }
    });
    tableHeader=tableHeader+'<th width="16px"><a href="#" id="addMoreCols">'+this.renderImage("plus",16)+'</a>';
  }
  else {
    $.each(masterData.service_sorted, function(k,a) {
      if (a.servicegroup_id==this_object.filter["filterDienstgruppen"]) {
        tableHeader=tableHeader+'<th>'+a.bezeichnung;
        if (a.notiz!="") tableHeader=tableHeader+" ("+a.notiz+")";
      }
    });
  }
  return tableHeader;
};




function _checkVorschlagen(eventservice, manuelInput, editRights) {
  var show=false;
  if (manuelInput) {
    show=true;
  }
  else {
    var chosen=$("#InputNameSelect").val();
    if (chosen>0) {
      if ((editRights) && (chosen!=eventservice.cdb_person_id))
        show=true;
      if ((!editRights) && (chosen!=masterData.user_pid))
        show=true;
    }  
  }
  if (show) $("#divvorschlagen").show();
  else $("#divvorschlagen").hide();
}
function _checkZusagen(eventservice, manuelInput, editRights) {
  var show=false;
  
  if (manuelInput) {
    if ((editRights))
      show=true;
  }
  else {
    var chosen=$("#InputNameSelect").val();
    
    if (chosen>0) {
      // Entweder habe ich Schreibrechte oder ich bin ausgew�hlt und hatte noch nicht zugesagt
      if (((editRights) && ((eventservice.zugesagt_yn==0) || (chosen!=eventservice.cdb_person_id))) 
          || 
       ((chosen==masterData.user_pid) && (eventservice.zugesagt_yn==0)))
        show=true;
    } 
  }
  if (show)
    $("#divzusagen").show();
  else
    $("#divzusagen").hide();    
}
function _checkAbsagen(eventservice, manuelInput, editRights) {
  var show=false;
  if (manuelInput) {
    if ((editRights) && (eventservice.name!=null))
      show=true;
  }
  else {
    var chosen=$("#InputNameSelect").val();
    if (chosen>0) {
      // Entweder habe ich Schreibrechte und die Person ist die, die schon ausgew�hlt wurde (sonst macht Absagen ja kein Sinn)
      if ((editRights) && (eventservice.cdb_person_id==chosen)) 
        show=true;
      // Oder ich bin selber die Person und die Person ist die, die schon ausgew�hlt wurde
      if ((eventservice.cdb_person_id==masterData.user_pid) //&& (eventservice.zugesagt_yn==1)
          && (chosen==eventservice.cdb_person_id))
        show=true;
    }
  }         
  if (show) $("#divabsagen").show();
  else $("#divabsagen").hide();
}    
ListView.prototype._renderAuslastung = function (event_id, service_id) {
  if ($("#InputNameSelect").val()>0) {
    if ((masterData.auth.admin) || (masterData.auth.leaderservice[service_id])) {
      $("#divauslastung").html('Auslastung: '+this_object.renderPersonAuslastung($("#InputNameSelect").val(), event_id, service_id, true));
      $("#divauslastung a").click(function() {
        if ($(this).attr("id")=="personHistory") {
          var rows = new Array();
          var p_id=$("#InputNameSelect").val();

          rows.push('<table class="table table-condensed"><tr><th>Datum<th>Event<th>Service<th>Zugesagt<th>Notiz');
          $.each(churchcore_sortData(allEvents,"startdate",true), function(k,a) {
            if (a.services!=null) {
              $.each(a.services, function(i,service) {
                if ((service.user_id==p_id) && 
                      (((service.valid_yn==0) && (service.cdb_person_id==null)) 
                     || (service.valid_yn==1) && (service.cdb_person_id!=null))) {
                  rows.push('<tr><td>'+a.startdate.toDateEn(true).toStringDe(true)); 
                  rows.push('<td>'+a.bezeichnung); 
                  rows.push('<td>'+masterData.service[service.service_id].bezeichnung); 
                  rows.push('<td>'+(service.zugesagt_yn==1?"<font>ja":"<font style=color:red>nein")+"</font>"); 
                  rows.push('<td>'+(service.reason!=null?service.reason:"")); 
                }
              });
            }
          });
          rows.push("</table>");
          form_showOkDialog("Anzeige der Auslastung", rows.join(""), 600,600);
        }
      });
    }
  }
  else 
    $("#divauslastung").html("");
};

/**
 * Return null wenn nicht abwesend, anonsten gibt er das erste abwesende zur�ck
 */
function personIsAbsent(p_id, datum) {
  var res=null;

  if ((allPersons[p_id]!=null) && (allPersons[p_id].absent!=null)) {
    $.each(allPersons[p_id].absent, function(k,a) {
      var _enddate=new Date(a.enddate);
      // Wenn es ein ganztagestermin ist, dann mu� ich ein Tag hinzunehmen
      if ((a.startdate.getHours()==0) && (a.enddate.getHours()==0))
          _enddate.addDays(1);
      if ((a!=null) && (a.startdate<=datum) && (_enddate>=datum)) {
        res=a;
        return false;
      }
    });
  }
  return res;
}

function _checkPersonTag(tag_ids, tags) {
  var tag_dabei=false;
  if ((tag_ids==null) || (tag_ids==""))
    tag_dabei=true;
  else {
    $.each(tag_ids, function(i,c) {
      if ((tags!=null)) {
        if (churchcore_inObject(c,tags)) {
          tag_dabei=true;
          return false;
        }
      }
    });
  }
  return tag_dabei;
}

function _checkWarSchonMal(p_id, services, service_id, counter) {
  var warschonmal=false;
  $.each(services, function(j,c) {              
    if (c.cdb_person_id==p_id) {
      // Wenn er nicht mehr aktuell ist oder wenn er in einem anderen Dienst aktuell angefragt ist
      if ((c.valid_yn==0) || (c.service_id!=service_id) || (c.counter!=counter)) {
        warschonmal=true;
      }
      // Setze es auf false, denn sonst ist der ausgew�hlte auch markiert.
      else warschonmal=false;
    }
  });
  return warschonmal;
}

ListView.prototype.getAllPersonsForService = function(service_id) {
  var gruppen_ids=masterData.service[service_id].cdb_gruppen_ids;
  var tag_ids=(masterData.service[service_id].cdb_tag_ids==null?null:masterData.service[service_id].cdb_tag_ids.split(","));
  var persons=new Object();
  $.each(gruppen_ids.split(","), function(k,g) {
    if (groups[g]!=null) {
      $.each(groups[g], function(i,b) {
        if (_checkPersonTag(tag_ids, b.tags)) {
          var o = new Object();
          o.id=b.p_id;
          o.bezeichnung=b.vorname+" "+b.name;
          persons[o.id]=o;
        }
      });
    }
  });  
  return persons;
};

/**
 * 
 * @param persons, notAllowed wird abgefragt!
 * @param event_id
 * @param service_id
 * @return Id mit dem niedrigsten Wert!
 */
ListView.prototype.selectPossiblePersonForService = function(persons, event_id, service_id, counter, selectedPersons) {
  var t=this;
  if (persons!=null) {
    var lowestWert=-100000; lowestId=null;
    $.each(persons, function(k,person) {
      if ((selectedPersons==null) || (selectedPersons[person.id]==null)) {
        var event_date=allEvents[event_id].startdate.toDateEn();
        var sg_id=masterData.service[service_id].servicegroup_id;
        if (person.bewertet==null) {
          person.bewertet=true;
          wert=person.wert;
          if (wert==null) wert=0;
          if (personIsAbsent(person.id,event_date)!=null) {
            wert=wert-1000;
            person.reason="Abwesend";
          }
          else if (_checkWarSchonMal(person.id, allEvents[event_id].services, service_id, counter)) {
            wert=wert-500;
            person.reason="War schon mal";
          }
          else {
            var einsatz=t.getPersonAuslastung(person.id, service_id, event_date);

            if ((t.serviceGroupPersonWeight[person.id]!=null) 
                && (t.serviceGroupPersonWeight[person.id][sg_id]!=null)) {
              var weight=t.serviceGroupPersonWeight[person.id][sg_id];
              // Checke die Max per month definition 
              if ((einsatz.monate[0]!=null) && (weight.max_per_month!=null)
                    && (weight.max_per_month*1<=einsatz.monate[0].person*1)) {
                wert=wert-100;
                person.reason="Maximaler gew&uuml;nschter Einsatz erreicht";
              }
              // Pr�fe auf morgens oder abends
              if (event_date.getHours()>=13) {
                if (weight.morning_weight==1) wert=wert+10; 
                else if (weight.morning_weight>=2) {
                  wert=wert-100;
                  person.reason="Einsatz nur morgens";
                }
              } 
              else {
                if (weight.morning_weight==-1) wert=wert+10; 
                else if (weight.morning_weight<=-2) {
                  wert=wert-100;
                  person.reason="Einsatz nur abends";
                }
              }             
              // Pr�fe auf Beziehung
              if (weight.relation_weight!=0) {
                 $.each(persons, function(k, rel) {
                   if (rel.id==weight.relation_id) {
                     if ((weight.relation_weight==-1) && (rel.bewertet!=null)) {
                       if (rel.wert>-100) {
                         wert=wert-100;
                         person.reason="Nicht mit Partner";
                       }
                     }
                     else if ((weight.relation_weight==1) && (rel.bewertet!=null)) {
                       rel.wert=rel.wert+10;
                     }
                   }
                 });
              }
            }
                        
            if (einsatz.monate[0]!=null) wert=wert-einsatz.monate[0].person*3;
            if (einsatz.monate[-1]!=null) wert=wert-einsatz.monate[-1].person*2;
            if (einsatz.monate[-2]!=null) wert=wert-einsatz.monate[-2].person*1;
            if (einsatz.monate[1]!=null) wert=wert-einsatz.monate[1].person*2;
            if (einsatz.letzter_einsatz_davor!=null) { 
              if (einsatz.letzter_einsatz_davor.dayDiff(event_date)<7) {                 
                wert=wert-50;
                person.reason="letzter Einsatz <7 Tage";
              }
              else if (einsatz.letzter_einsatz_davor.dayDiff(event_date)<14)
                wert=wert-10;
            }
            if (einsatz.naechster_einsatz_danach!=null) { 
              if (einsatz.naechster_einsatz_danach.dayDiff(event_date)>-7) { 
                person.reason="n&auml;chster Einsatz <7 Tage";
                wert=wert-50;
              }
              else if (einsatz.naechster_einsatz_danach.dayDiff(event_date)>-14) 
                wert=wert-10;
            }
          }
          person.wert=wert;
          person.bezeichnung=person.bezeichnung+" "+wert;
          if (person.reason!=null)
            person.bezeichnung="("+person.bezeichnung+") - "+person.reason;
        }
        if ((person.wert!=null) && (person.wert>lowestWert)) {
          lowestWert=person.wert;
          lowestId=person.id;
        }
      }
    });
    if (lowestWert>-100)
      return lowestId;
    else return null;
  }
};


ListView.prototype._renderInputName = function (manuelInput, eventservice, event_id, editRights) {
  var rows = Array();
  var this_object=this;
  var service_id=eventservice.service_id;
  
  if (manuelInput) {
    if (eventservice.cdb_person_id==null) {
      rows.push("<input type=\"text\" id=\"InputName\" class=\"cdb-textfield\" size=\"30\" value=\"");
      rows.push(eventservice.name);
      rows.push("\"/>");
      rows.push("<p style=\"color:gray\"><small><i>Person kann nicht automatisch benachrichtigt werden!</i></small><br/>");
    }
    // Die Person wurde vorher per Suche hinzugef�gt!
    else {
      rows.push('<select id="InputNameSelect" class="cdb-input"><option value="'+eventservice.cdb_person_id+'">'+eventservice.name+'</option></select>');
      manuelInput=false;
    }
  }
  // SelectBox, da kein ManuelInput
  else {
    var klammer_person=false;
    rows.push('<p>Person ausw&auml;hlen');
    rows.push('<p><select id="InputNameSelect" class="cdb-input">');
//rows.push('<div class="" style="height:170px; width:250px; overflow-y:auto; overflow-x:auto"><ui class="ui-menu ui-widget ui-widget-content ui-corner-all" id="selectable">');
    
    var _gruppen_ids=masterData.service[service_id].cdb_gruppen_ids;
    var tag_ids=(masterData.service[service_id].cdb_tag_ids==null?null:masterData.service[service_id].cdb_tag_ids.split(","));
    // Schaue wenn das Ding null ist, dann gibt es eine Person manuel ausgew�hlt
    if (_gruppen_ids==null) _gruppen_ids="-1";
    var _person_vorhanden=false;
    var _leere_liste=true;
    $.each(_gruppen_ids.split(","), function(k,a) {
      var title=true;
      if (_gruppen_ids.indexOf(",")>0) title=false;
      if (groups[a]!=null) {
        $.each(churchcore_sortData(groups[a],"vorname"), function(i,b) {
          
          // Pr�fe, ob auch Tags abgefragt werden sollen und ob sie passen       
          if (_checkPersonTag(tag_ids, b.tags)) {
            _leere_liste=false;

            // Bei mehreren Gruppen wird der Name der Gruppe als Titel angezeigt
            if (!title) {
              title=true;
              rows.push('<option value="-2"> == '+b.bezeichnung.trim(26)+" == ");
            }
            
            // warschonmal macht Klammern, also wenn die Person abwesend ist wird sie geklammert.
            var warschonmal=(personIsAbsent(b.p_id,allEvents[event_id].startdate.toDateEn())!=null);

            // Pr�fe nun, ob die Person schon mal eingetragen wurde und �ndere dann den Style
            if (_checkWarSchonMal(b.p_id, allEvents[event_id].services, service_id, eventservice.counter)) {
              warschonmal=true;
              klammer_person=true;
            }
            
            rows.push('<option value="'+b.p_id+'"');

            if (eventservice.cdb_person_id==b.p_id) {
              rows.push(" selected");
              _person_vorhanden=true;
            }
            var name = b.vorname+" "+b.name;
            rows.push('>'+(warschonmal?"(":"")+name.trim(26)+(warschonmal?")":""));            
          }  
        });
      }  
    });
    // Person wurde fr�her manuel hinzugef�gt zur Liste, also mu� sie nun dazugef�gt werden
    if ((!_person_vorhanden) && (eventservice.cdb_person_id!=null)) {
      // Kann wieder rein...
      rows.push('<option selected value="'+eventservice.cdb_person_id+'">'+eventservice.name+'</option>');
      _leere_liste=false;
    }
    if (_leere_liste) 
      rows.push('<option value="-2">-- keine Person in der Liste --</option>');
    if ((editRights) && (eventservice.name==null) && (masterData.auth.viewchurchdb)) 
      rows.push('<option value="-1">... andere Person hinzuf&uuml;gen</option>');          

    rows.push("</Select>");
//rows.push('</ul></div>');
    rows.push("<p><small>");
    if (klammer_person) rows.push("() = Personen in Klammern wurden bereits f&uuml;r dieses Event angefragt. Eine weitere Anfrage ist hier u.U. nicht sinnvoll.<br/>");
    rows.push('</small></p>');
  }
  rows.push('<input type="hidden" id="nameofadditionperson" name="UserBrowser" value="'+eventservice.name+'"/>');

  return rows.join("");
};

function _checkEMail() {
  if ($("#InputNameSelect").val()>0)
    $("#divemail").show();
  else
    $("#divemail").hide();
}

function _completePersonInfo(id) {
  //Versuche Kontaktdaten anzureichern
  $("#divkontakt").html("");
  if ((id!=null) && (masterData.auth.viewchurchdb)) {
    churchInterface.jsendRead({func:"getPersonById",id:id}, function(ok, json) {
      if ((json.data!=null) && (json.data[id]!=null)) {
        var d=json.data[id];
        txt="";
//        if ((d.imageurl!=null)) txt=txt+'<img src="'+masterData.files_url+"/fotos/"+d.imageurl+'" width="42px" align="left"/>';
        if ((d.email!=null) && (d.email!="")) txt=txt+'E-Mail: <a href="mailto:'+d.email+'">'+d.email+'</a><br/>';
        if ((d.telefonhandy!=null) && (d.telefonhandy!="")) txt=txt+'Handy: <a href="tel:'+d.telefonhandy+'">'+d.telefonhandy+'</a><br/>';
        else if ((d.telefonprivat!=null) && (d.telefonprivat!="")) txt=txt+'Tel.: <a href="tel:'+d.telefonprivat+'">'+d.telefonprivat+'</a><br/>';
        if (txt!="") $("#divkontakt").html("<p><small>"+txt+"</small>");
      }
      
    }, null, null, "churchdb");
  } 
}

/**
 * Rendere die Auswahlbox, wer nun konkret den Dienst �bernimmt. Entweder per Selectbox oder per Freitext
 * @param event_id
 * @param eventservice_id
 * @param zwinge_manuelinput
 */
ListView.prototype.renderEditEventService = function(event_id, eventservice_id, zwinge_manuelinput) {  
  var this_object=this;
  var rows=new Array();
  if (zwinge_manuelinput==null) zwinge_manuelinput=false;
  var eventservice=this.getEventService(event_id, eventservice_id);
  var service_id=eventservice.service_id;
  
  // Feld ist keine Auswahl als ChurchDB-Gruppen sondern Freitext
  var manuelInput=((masterData.service[service_id].cdb_gruppen_ids==null) && (eventservice.cdb_person_id==null))  
                     || (zwinge_manuelinput)
                     || ((eventservice.cdb_person_id==null) && (eventservice.name!=null));
  // Entweder hat er explizit auf diese Gruppe Schreibrechte oder er ist Leiter der Gruppe
  var editRights=((masterData.auth.editservice[service_id]) || (masterData.auth.leaderservice[service_id]==true)
           || (bin_ich_admin(allEvents[event_id].admin)));
  var amIChosen=((eventservice.cdb_person_id!=null) && (eventservice.cdb_person_id==masterData.user_pid));

  rows.push("<legend>Dienst <i>");
  if (masterData.auth.editservice[service_id]) rows.push('<a href="#" id="editService"><font style="text-decoration:underline">');
  rows.push(masterData.service[service_id].bezeichnung);
  if (masterData.service[service_id].notiz!="")
    rows.push(" ("+masterData.service[service_id].notiz+")");
  if (masterData.auth.editservice[service_id]) rows.push('</font></a>');
  rows.push("</i> besetzen:</legend>");

  rows.push('<div id="in_edit"><div class="row-fluid">');  
    rows.push('<div class="span5">');
    
    rows.push("<div id=\"divinputname\">");
      // Erstellt entweder die Selectbox mit allen Namen oder das ManuelInput-Eingabefeld
      rows.push(this._renderInputName(manuelInput, eventservice, event_id, editRights));
    rows.push("</div>");
    
    rows.push('');
    rows.push('<span style="display:none" id="divvorschlagen"><input type="button" value="Vorschlagen" class="btn btn-warning" />&nbsp;</span>');
    rows.push('<span style="display:none" id="divzusagen"><input type="button" value="Zusagen" class=\"btn btn-success\"/>&nbsp;</span>');
    rows.push('<span style="display:none" id="divabsagen"><input type="button" value="Absagen" class=\"btn btn-danger\"/>&nbsp; </span>&nbsp;');    

    rows.push('</div><div class="span1"></div><div class="span6">');
      rows.push("<div class=\"well\"><h4>Infos zur Person</h4><p><span id=\"divauslastung\"></span>");
        rows.push("<p><span id=\"divemail\"><a href=\"#\" title=\"Person eine E-Mail senden\" id=\"mailPerson\">" +this_object.renderImage("email")+"</a> <small>Web-EMail an ausgew&auml;hlte Person</small></span>&nbsp;");
        rows.push("<div id=\"divkontakt\"></div>");
    rows.push('</div></div></div>');
    
    // Pr�fe, ob er die Histore sehen darf
    if (editRights) {     
      //rows.push('<br/><p><a href="#" id="showHistory" class="btn btn-mini">Historie anzeigen >></a>');
      rows.push('<p><h4>Historie</h4>');
      rows.push(this_object.renderEntryHistory(event_id, eventservice.service_id, eventservice.counter, null, ((editRights) || (masterData.auth.admin) )));
    }
    
    rows.push('<div id="divshowhistory"></div>');  
    
  rows.push('</div>');

  //var elem=$("<div>"+rows.join("")+"</div>").appendTo("#cdb_content");
  
  var elem = form_showCancelDialog("Anfrage "+
       masterData.servicegroup[masterData.service[service_id].servicegroup_id].bezeichnung+
       " für den "+allEvents[event_id].startdate.toDateEn(true).toStringDe(true), 
      rows.join(""), 550,450);

  
  var this_object=this;

  _checkVorschlagen(eventservice, manuelInput, editRights);
  _checkZusagen(eventservice, manuelInput, editRights);
  _checkAbsagen(eventservice, manuelInput, editRights);
  this._renderAuslastung(event_id, service_id);
  _checkEMail();
  _completePersonInfo(eventservice.cdb_person_id);
  
  if (manuelInput) 
    $("#InputName").focus();
  else
  // Nimmt Focus von der Auswahlliste weg, damit beim iPad nicht automatisch die Auswahl zu sehen ist, das nervt sonst!
    $("#InputNameSelect").blur();
  
  // Callbacks fuer den Editor
  
  // Autocomplete f�r Auswahl von Freitextnahmen
  if (manuelInput)
    this.autocompletePersonSelect("#InputName", false, function(event, ui) {
      var txt='<select id="InputNameSelect" class="cdb-input"><option value="'+ui.item.value+'">'+ui.item.label+'</option></select>';
      txt=txt+'<input type="hidden" id="nameofadditionperson" name="UserBrowser" value="'+ui.item.label+'"/>';
      $("#divinputname").html(txt);
      manuelInput=false;
    });
 
    
  $("#InputNameSelect").change(function(a) {
    // Bei Auswahl "...andere Person"
    if ($("#InputNameSelect").val()==-1) {
      elem.empty().remove();
      this_object.renderEditEventService(event_id, eventservice_id, true);
    } 
    // Buttons neu pr�fen
    else {
      _checkVorschlagen(eventservice, manuelInput, editRights);
      _checkZusagen(eventservice, manuelInput, editRights);
      _checkAbsagen(eventservice, manuelInput, editRights);
      this_object._renderAuslastung(event_id, service_id);
      _checkEMail();
      _completePersonInfo($("#InputNameSelect").val());
    }
  });
  
  $("#cdb_dialog a").click(function(a) {
    if ($(this).attr("id")=="showHistory") 
      $("#divshowhistory").html(this_object.renderEntryHistory(event_id, eventservice.service_id, eventservice.counter, null, ((masterData.auth.leaderservice[service_id]) || (masterData.auth.admin))));
    else if ($(this).attr("id")=="mailPerson") {
      if ($("#InputNameSelect").val()<=0)
        alert("Bitte eine Person nehmen!");
      else
        this_object.mailPerson($("#InputNameSelect").val());
    }
    else if ($(this).attr("id")=="editService") {
      t.editService(service_id);
    }
    return false;
  });
  
  $("#in_edit input").click(function (a) {
    if ($(this).attr("type")=="button") {
      if (((manuelInput) && ($("#InputName").val()=="")) ||
         ((!manuelInput) && ($("#InputNameSelect").val()=="")))  
        alert("Bitte erst einen Namen aussuchen");
      else {
        obj=new Object();
        obj.func="updateEventService";
        obj.id=eventservice.id;
        eventservice.valid_yn=0;
  
        if (($(this).val() == "Vorschlagen") || ($(this).val() == "Zusagen")) {
          if (manuelInput) {
            obj.name=$("#InputName").val();
          }
          else {
            obj.cdb_person_id=$("#InputNameSelect").val();
            if (masterData.service[service_id].cdb_gruppen_ids!=null) {
              // Mu� nun erst mal den Namen suchen, daf�r mu� ich die m�glichen Gruppen durchgehen.
              $.each(masterData.service[service_id].cdb_gruppen_ids.split(","), function(k,a) {
                if (groups[a]!=null) {
                  $.each(groups[a], function(i,b) {
                    if (b.p_id==obj.cdb_person_id) {
                      obj.name=b.vorname+" "+b.name;
                      return false;
                    }
                  });
                }  
              });
            } 
            // Eine Person wurde manuel gesucht, dann konnte ich sie bis jetzt nicht finden und �bernehme den Namen aus der Selectbox
            if (obj.name==null)
              obj.name=$("#nameofadditionperson").val();
          }
          if ($(this).val() == "Zusagen") {
            obj.zugesagt_yn=1;
            if (($("#InputNameSelect").val()==masterData.user_pid) & (masterData.service[service_id].allowtonotebyconfirmation_yn==1)) {
              var res=prompt("Hiermit verbindlich zusagen? Hier kannst Du noch eine Info angeben.","");
              if (res==null) return null;
              obj.reason=res;              
            }
          }
          else
            obj.zugesagt_yn=0;
        }
        else if ($(this).val() == "Absagen"){              
          if (($("#InputNameSelect").val()==masterData.user_pid) || (eventservice.mailsenddate!=null)) {
            var res=prompt("Wirklich absagen? Hier kannst Du noch einen Grund angeben.","");
            if (res==null) return null;
            obj.reason=res;
          }
          delete obj.name;
          delete obj.cdb_person_id;
          obj.zugesagt_yn=0;
        }
        elem.html("<p><br/><b>Daten werden gespeichert...</b><br/><br/>");
        churchInterface.jsendWrite(obj, function(ok, json) {
          elem.dialog("close");
          if (!json.result) {
            alert("Fehler beim Speichern: "+json);
            window.location.reload();
          } 
          else {
            // Wenn es nur ein Update war (gleicher Modifiedduser)
            if (json.eventservice.id==obj.id) {
              $.each(allEvents[event_id].services, function(k,a) {
                if (a.id==obj.id)
                  allEvents[event_id].services[k]=json.eventservice;
              });
            }
            else
              allEvents[event_id].services.push(json.eventservice);
            // Wenn nur einer hochgez�hlt wurde, dann war ich das selber, ansonsten hat jemand 
            // anderes auch was ge�ndert und ich sollte neu laden!
            if ((json.eventservice.id*1)==(churchInterface.lastLogId*1+1))
              churchInterface.setLastLogId(json.eventservice.id);
            elem.empty().remove();
            this_object.renderList(allEvents[event_id]);
          } 
        });
      }  
    }
  }); 
};

ListView.prototype.showAuslastung = function() {
  var rows = new Array();

  var user = new Object();
  var counter = 0;
  $.each(allEvents, function(k,a) {
    if (a.services!=null) {
      counter=counter+1;
      $.each(a.services, function(i,b) {      
        if ((b.cdb_person_id!=null) && (b.valid_yn==1)) {
          if (user[b.cdb_person_id]==null) {
            var a = new Array();
            a.counter=1;
            a.name=b.name;
            a.cdb_person_id=b.cdb_person_id;
            a.service_id=b.service_id;
            user[b.cdb_person_id]=a;
          }  
          else 
            user[b.cdb_person_id].counter=user[b.cdb_person_id].counter+1;        
        }
      });
    }
  });
  rows.push("<h2>Dienste pro Event<h2/>");
  rows.push("<table>");
  $.each(churchcore_sortData(user, "counter", true, false), function(k,a) {
    rows.push("<tr><td>"+a.name+" ("+a.cdb_person_id+")<td>"+Math.round(a.counter/counter*100)+"%");    
  });
  rows.push("</table>");
  
  form_showOkDialog("Anzeige der Auslastung der Mitarbeiter", rows.join(""));  
};

/**
 * 
 * @param event_id
 * @param service_id
 * @param counter entweder null wenn es der Dienst nur einmal an dem Event angefragt ist oder die Nummer 
 * @param timeBack - wie weit zur�ck? null = soweit die Daten reichen
 * @param withReason
 * @param shortVersion - default false, true=Reason wird nur auszugsweise unter dem Namen abgedruckt, Name werden abgeschnitten, wenn zu lang
 * @return text in html
 */
ListView.prototype.renderEntryHistory = function(event_id, service_id, counter, timeBack, withReason, shortVersion) {
  var txt="";
  if (withReason==null) withReason=false;
  if (shortVersion==null) shortVersion=false;
  var _reasonAvailable=false;
  var _lastName=null;
  $.each(churchcore_sortData(allEvents[event_id].services,"datum",false), function(k,a) {
    if ((a.service_id==service_id) && (a.counter==counter)) {
      if ((timeBack==null) || (a.datum.toDateEn()>=timeBack)) {
        var row='<tr><td>';
        if (a.zugesagt_yn==0) row=row+"<font style=\"color:red\">";
        
        if ((a.name!=null) && (a.name!=""))
          row=row+a.name;
        // Wenn ich den vorigen Namen habe, dann kann ich den nehmen und durchstreichen
        else if (_lastName!=null) row=row+"<font style=\"text-decoration: line-through\">"+_lastName+"</font>";
        // Dann habe ich wohl nix.
        else row=row+"?";
        if (a.zugesagt_yn==0) row=row+"</font>";        
        _lastName=a.name;    

        if ((withReason) && (a.reason!=null) && (shortVersion)) {
          row=row+"<br/><div><i>\""+a.reason.trim(15)+'"</i></small>';
        }
        
        row=row+"<td>"+a.datum.toDateEn().toStringDe(true)+"<td>";
        if (shortVersion) row=row+a.user.trim(15);
        else row=row+a.user;
        
        if ((withReason) && (a.reason!=null) && (!shortVersion)) {
          row=row+"<td>"+a.reason;
          _reasonAvailable=true;
        }
        txt=row+txt;
      }
    }
  });
  if (txt!="") {
//    var txt2='<div style="clear:both"><small><br/><table class=\"table table-condensed\"><tr><th>Name<th>Wann<th>Von wem';
    var txt2='<div><small><table class=\"table table-condensed\"><tr><th>Name<th>Wann<th>Von wem';
    if (_reasonAvailable) txt2=txt2+"<th>Notiz";
    txt=txt2+txt+"</table></small></div>";
  }
  return txt;
};

/** 
 * event, service_id, id des serviceeintrages
 */
function tryToGetReason(event, service_id, id) {
  var reason=null;
  $.each(churchcore_sortArray(event.services, "datum", true), function(b, service) {
    if (service.service_id==service_id) {
      if (service.id==id) {
        // exit
        return false;
      }
      if (service.reason!=null)
        reason=service.reason;
    }
  });
  return reason;
}

/**
 * 
 * @param cdb_user_id
 * @param service_id - entweder null, wenn alle, oder service_id und dann wird innerhalb der servicegroup geschaut.
 * @param now_date datum ab dem er pr�ft
 * @return als object. letzter_einsatz_davor, naechster_einsatz_danach, monate(diffdate{events(anzahl gesamt), person(wie oft die person)})
 */
ListView.prototype.getPersonAuslastung = function(cdb_user_id, service_id, now_date) {
  var result=new Object();
  result.letzter_einsatz_davor=null;
  result.naechster_einsatz_danach=null;
  result.monate=new Object();
  var _event_date=null;

  // Bau das Objekt _monate nun auf
  $.each(this.getData(true), function(a,event){
    if (event.services!=null) {
      _service_besetzt=false;
      _count_person=false;
      $.each(event.services, function(b,service) {
        if ((service.valid_yn==1) && (service.cdb_person_id!=null)) {
          _service_besetzt=true;
          // Entweder wurde kein Service mit �bergeben oder nur der ServiceGroup_id soll betrachtet werden z.b. nur Technik
          if (((service_id==null) || (masterData.service[service_id].servicegroup_id==masterData.service[service.service_id].servicegroup_id)) 
            && (service.cdb_person_id==cdb_user_id)) {
            _count_person=true;
          }
        }        
      });      
      var _monat=now_date.monthDiff(event.startdate.toDateEn(false));
      if (result.monate[_monat]==null) {
        var _a = new Array();
        _a.person=0;
        _a.events=0;
        result.monate[_monat]=_a;
      }
      if (_count_person) {
        result.monate[_monat].person++;
        if ((event.startdate.toDateEn()<now_date) && ((result.letzter_einsatz_davor==null) || (result.letzter_einsatz_davor<event.startdate.toDateEn())))
          result.letzter_einsatz_davor=event.startdate.toDateEn();
        if ((event.startdate.toDateEn()>now_date) && ((result.naechster_einsatz_danach==null) || (result.naechster_einsatz_danach>event.startdate.toDateEn())))
          result.naechster_einsatz_danach=event.startdate.toDateEn();
      }
      if (_service_besetzt) {
        // Schaue nun, das mehrer Events an einem Tag auch nur einmal gez�hlt werden
        if (_event_date!=event.startdate.substr(0,10))  
          result.monate[_monat].events++;
        _event_date=event.startdate.substr(0,10);        
      }
    }    
  });  
  return result;
};

/**
 * service_id entweder null, dann wird es mit allen verglichen oder die entsprechende service_id
 */
ListView.prototype.renderPersonAuslastung = function (cdb_user_id, event_id, service_id, withDayView) {
  if (withDayView==null) withDayView=false;
  if (cdb_user_id==null) return "";
  var now_date=allEvents[event_id].startdate.toDateEn();  
  
  var result=this.getPersonAuslastung(cdb_user_id, service_id, now_date);

  var txt="";
  var txt2="";
  
  var _diff_date=-2;
  var _percent_v=0.0; 
  var _percent_c=0;
  while (_diff_date<=1) {
    if (_diff_date==0) txt=txt+"|";
    if (_diff_date==0) txt2=txt2+"<";
    var _c = "white"; 
    if ((result.monate[_diff_date]!=null) && (result.monate[_diff_date].events>0)) {
      var _p = result.monate[_diff_date].person/result.monate[_diff_date].events;
      if (_p>0.5) _c="red";
      else if (_p>0.3) _c="yellow";
      else _c="green";
      _percent_v=_percent_v+_p;
      txt2=txt2+result.monate[_diff_date].person+"";
    }
    else
      txt2=txt2+"0";
    txt=txt+'<img src="'+masterData.modulespath+'/images/box_'+_c+'.png'+'"/>';
    if (_diff_date==0) txt=txt+"|";
    if (_diff_date==0) txt2=txt2+">";
    txt2=txt2+" ";
    _diff_date++;
    _percent_c++;
  }
  
  var txt3="";
  if (withDayView) {
    // Pr�fe ob die Person schon an dem Tag in einem Event eingetragen ist oder war
    $.each(this.getData(true), function(a, event){
      if ((event.services!=null) && (event.startdate.toDateEn(false).toStringEn(false)==now_date.toStringEn(false))) {
        $.each(churchcore_sortArray(event.services, "datum", true), function(b, service) {
          // Ist es die Person?
          if ((service.cdb_person_id!=null) && (service.cdb_person_id==cdb_user_id)) {
            // Nur andere Events untersuchen oder andere Dienste am gleichen Event
            if ((event.id!=event_id) || (service.service_id!=service_id)) {
              if (txt3!="") txt3=txt3+" und ";
              txt3=txt3+" "+masterData.service[service.service_id].bezeichnung+" ";
              if ((service.zugesagt_yn==1) && (service.valid_yn==1))
                txt3=txt3+"zugesagt";
              else if ((service.zugesagt_yn==0) && (service.valid_yn==1))
                txt3=txt3+"angefragt";
              else {
                txt3=txt3+"abgesagt";
                var reason=tryToGetReason(event, service.service_id, service.id);
                if (reason!=null) txt3=txt3+" (<i>"+reason+"</i>)";
              }
              txt3=txt3+" f&uuml;r "+event.startdate.toDateEn(true).toStringDeTime()+" "+event.bezeichnung;
              //exit
              return false;
            }
          }
        });
      }
    });
  }
  
  if (txt2!="") {
    txt2='<a href="#" id="personHistory">'+txt2+'</a>';
    var _percent=Math.round(100*_percent_v/_percent_c);
    if (_percent>100) _percent=100;
    txt2=txt2+"&nbsp; ("+_percent+"%)";
    if (result.letzter_einsatz_davor!=null)
      txt2=txt2+"<br/>Letzter Einsatz: "+result.letzter_einsatz_davor.toStringDe(true);
    if (result.naechster_einsatz_danach!=null)
      txt2=txt2+"<br/>N&auml;chster Einsatz: "+result.naechster_einsatz_danach.toStringDe(true);
    if (txt3!="")
      txt2=txt2+"<p><small>Andere Anfragen an diesem Tag:<br>"+txt3+"!</small>";
    
    var absent=personIsAbsent(cdb_user_id,now_date);
    if (absent!=null) {
      txt2=txt2+"<p><small style=\"color:red\">Achtung: Person abwesend bis "+(absent.enddate.getHours()==0?absent.enddate.toStringDe(false):absent.enddate.toStringDe(true))+" ("+masterData.absent_reason[absent.absent_reason_id].bezeichnung;
      if (absent.bezeichnung!="")
        txt2=txt2+" - "+absent.bezeichnung;
      txt2=txt2+")</small>";
    }

    
    return '<font title="Auslastung je Monat innerhalb der Dienstgruppe, aktueller Eventmonat ist mit <> markiert">'+txt2+'</font>';
  }
  return "";  
};

/**
 * 
 * @param event_id
 * @param eventservice_id
 * @return eventservice
 */
ListView.prototype.getEventService = function(event_id, eventservice_id) {
  var _eventservice=null;
  $.each(allEvents[event_id].services, function(k,a) {
    if ((a.id==eventservice_id) && (a.valid_yn!=0)) {
      _eventservice=a;
      // break
      return false;
    }
  });
  return _eventservice;
};

ListView.prototype.renderTooltip = function(id, event_id, withLastDates, withHistory) {
  var eventservice_id=id.substr(id.indexOf("_")+1,99);
  var txt="";
  var a = this.getEventService(event_id, eventservice_id);
  var _bin_ich_admin=bin_ich_admin(allEvents[event_id].admin);
  var _editor=(masterData.auth.admin) || (masterData.auth.editservice[a.service_id]) || (masterData.auth.leaderservice[a.service_id]) || (_bin_ich_admin);
  if (a.name!=null) {
    txt="<h4>"+a.name;
    txt=txt+"</h4>";
  }
  
  var info=false;
  if (masterData.service[a.service_id].cdb_gruppen_ids!=null) {
    var info=this_object.getMemberOfOneGroup(masterData.service[a.service_id].cdb_gruppen_ids, [a.cdb_person_id]);
  }

  if (withLastDates) {
    if (a.zugesagt_yn==1)
      txt=txt+"<font style=\"color:green\">Zusage am "+a.datum.toDateEn().toStringDe()+"</font>";
    else if (a.name!=null) 
      txt=txt+"<font style=\"color:red\">Anfrage vom "+a.datum.toDateEn().toStringDe()+"</font>";
    else
      txt=txt+"<font style=\"color:red\">Offen seit "+a.datum.toDateEn().toStringDe()+"</font>";
    if (a.mailsenddate!=null)
      txt=txt+'&nbsp;<span title="Letzte Erinnerung gesendet am '+a.mailsenddate.toDateEn(true).toStringDe(true)+'">'+form_renderImage({src:"email.png",width:12})+'</span>';
    txt=txt+'<br/>';
  }
  //txt=txt+"</div>";
  if (info!=false) {
    if (info.imageurl!=null)
      txt='<div style="float:right">&nbsp;<img src="'+masterData.files_url+"/fotos/"+info.imageurl+'" style="max-width:70px" width="70"></div>'+txt;
  }
  if (_editor) {
    var t2=this.renderPersonAuslastung(a.cdb_person_id, event_id, a.service_id);
    if (t2!="")
      txt=txt+"Auslastung: "+t2;
  }
  if (a.cdb_person_id!=null) {
    txt=txt+"<br/>";
    var txt2="";
    if (user_access("administer persons")) {
      txt2=txt2+'<br/><a href="#" class="simulate-person" data-id="'+a.cdb_person_id+'">'
            +form_renderImage({src:"person_simulate.png",label:"Person simulieren", width:18})+'&nbsp;Simulieren</a>';
    }
    if (info!=false && info.email!="") {
      txt2=txt2+'<br/><a href="#" class="email-person" data-id="'+a.cdb_person_id+'">'
            +form_renderImage({src:"email.png",label:"Person eine E-Mail senden", width:18})+"&nbsp;E-Mail senden</a>";
    }
    if (txt2!="") {
      txt=txt+txt2;
    }
  }
  
  if (txt!="" || _editor) {
    txt='<div>'+txt+"</div>";
    txt=txt+'<div style="clear:both"></div>';  
  }

  
  if ((withHistory) || (masterData.auth.viewhistory) || (_bin_ich_admin))
    txt=txt+"<p>"+this.renderEntryHistory(event_id, a.service_id, a.counter, null, _editor, true);

  if (txt!="") {
    var title=masterData.service[a.service_id].bezeichnung;
    if (_bin_ich_admin || masterData.auth.admin || (masterData.auth.editservice[a.service_id])) {
      title='<a href="#" class="edit-service" data-id="'+a.service_id+'">'+title+'</a>';
    }
    
    if (masterData.service[a.service_id].notiz!="")
      title=title+' <small> ('+masterData.service[a.service_id].notiz+")</small>";

    if (_editor) {
      var abonniert=false;
      if (getNotification("service", a.service_id)!==false) {
        abonniert=true;      
      }
      title=title+'&nbsp; <span class="label '+(abonniert?"label-info":"")+'">';
      title=title+'<a href="#" class="edit-notification" data-domain-type="service" data-domain-id="'+a.service_id+'" '+'>'
        +(abonniert?"abonniert":"abonnieren")+'</a></span>';
    }
    
    txt='<div style="min-width:250px; max-width:300px;">'+txt+'</div>';
    return [txt,title];
  }
  return null;
};

function getNotification(domain_type, domain_id) {
  if (masterData.notification[domain_type]==null) return false;
  if (masterData.notification[domain_type][domain_id]==null) return false;
  return masterData.notification[domain_type][domain_id];  
}


ListView.prototype.countActiveServices = function(event, service_id) {
  var count=0;
  if (event.services!=null) {
    $.each(event.services, function(k,s) {
      if ((s.service_id==service_id) && (s.valid_yn==1) && (s.name!=null)) {
        count=count+1;
      }
    }); 
  }       
  return count;
};


ListView.prototype.editService = function(service_id, sg_id) {
  var arr=$.extend({}, masterData.service[service_id]);
  if (service_id==null) {
    arr=new Array();
    arr.sortkey=0;
  }
  arr.gruppen=new Array();
  arr.tags=new Array();
  if (arr.cdb_gruppen_ids!=null)
    arr.gruppen=arr.cdb_gruppen_ids.split(","); 
  if (arr.cdb_tag_ids!=null)
    arr.tags=arr.cdb_tag_ids.split(","); 
  if (arr.servicegroup_id==null) arr.servicegroup_id=sg_id;
  
  if (masterData.groups==null) {
    var elem = form_showCancelDialog("Gruppendaten werden geladen...","Bitte warten..");
    churchInterface.jsendRead({func:"getGroupAndTagInfos"}, function(ok, data) {
      if (ok) {
        masterData.groups=data.groups;
        if (masterData.groups==null) masterData.groups= new Array();
        masterData.tags=data.tags;
        t.editService(service_id, sg_id);
      }
      else {
        alert("Fehler: "+data); 
        masterData.groups="null";
      }
      elem.dialog("close");
    }); 
  }
  else {
    var form = new CC_Form(null, arr);
    form.addInput({label:"Bezeichnung",cssid:"bezeichnung",required:true});
    form.addInput({label:"Notiz",cssid:"notiz",required:false});
    form.addInput({label:"Ergänzung für Kalendertext", placeholder:"mit [Vorname]", cssid:"cal_text_template",required:false});
    form.addSelect({label:"Servicegruppe", cssid:"servicegroup_id", data:masterData.servicegroup, 
      func: function(o) {return (masterData.auth.editgroup!=null) && (masterData.auth.editgroup[o.id]!=null);}
    });
  
    
    form.addHtml('<div class="control-group"><label class="control-label">Gruppenzuordnungen</label>');
    form.addHtml('<div class="controls" id="gruppen">');
    form.addHtml('</div></div>');
    
    if (masterData.tags!=null) {
      form.addHtml('<div class="control-group"><label class="control-label">Tag-Zuordnungen</label>');
      form.addHtml('<div class="controls" id="tags">');
      form.addHtml('</div></div>');
    }
    
    
    form.addCheckbox({controlgroup_start:true, label:"Sende Dienstanfragen per E-Mail", cssid:"sendremindermails_yn"});
    form.addCheckbox({controlgroup_end:true, label:"Die Dienstanfrage kann auch bei Zusage kommentiert werden", cssid:"allowtonotebyconfirmation_yn"});
    form.addInput({label:"Sortierungsnummer (sortkey)",cssid:"sortkey",required:true});
    form.addHtml('<p class="pull-right"><small>Id: '+service_id);
  
    var elem = form_showDialog((service_id!=null?"Service editieren":"Service erstellen"), form.render(false, "horizontal"), 600,550);
    elem.dialog('addbutton', 'Speichern', function() {
      obj=form.getAllValsAsObject();
      obj.cdb_gruppen_ids=arr.gruppen.join(",");
      obj.cdb_tag_ids=arr.tags.join(",");
      
      if (obj.cdb_gruppen_ids=="") delete obj.cdb_gruppen_ids;
      if (obj.cdb_tag_ids=="") delete obj.cdb_tag_ids;
      
      if ((obj.cdb_gruppen_ids==null) && (obj.cdb_tag_ids!=null)) {
        alert("Es wurde mindestens ein Tag angegeben ohne eine Gruppe. Tags wirken nur innerhalb von Gruppen. Bitte erst Gruppe auswaehlen!");
        return false;
      }
      
      obj.id=service_id;
      if (obj!=null) {
        obj.func="editService";
        churchInterface.jsendWrite(obj, function(ok, data) {
          if (!ok) alert("Fehler beim Speichern: "+data);
          else window.location.reload();
        });
      }
    });
    if ((service_id!=null) && (masterData.auth.editgroup!=null) && (masterData.auth.editgroup[sg_id])) {
      elem.dialog('addbutton', 'Löschen', function() {
        if (confirm("Wirklich '"+masterData.service[service_id].bezeichnung+"' entfernen? Alle dazu vorhandenen Zuordnungen werden auch unwiderruflich entfernt!")) {
          elem.dialog("close");
          churchInterface.jsendWrite({func:"deleteService", id:service_id}, function(ok, data) {
            if (ok) {
              window.location.reload();
            }
            else alert("Fehler: "+data);
          });
        }
        return false;
      });
    }
    elem.dialog('addbutton', 'Abbrechen', function() {
      $(this).dialog("close");
    });
    
    form_renderLabelList(arr, "gruppen", masterData.groups);
    if (masterData.tags!=null)
      form_renderLabelList(arr, "tags", masterData.tags);    
  }  
  
};

ListView.prototype.renderAddServiceToServicegroup = function(event, sg_id, user_pid) {
  var rows=new Array();
  rows.push('<div id="in_edit"><table class="table table-condensed">');
  rows.push('<tr><th><input type="checkbox" id="cb_enableAll"/><th>Service');
  if ((masterData.auth.editgroup!=null) && (masterData.auth.editgroup[sg_id])) {
    rows.push('<th width="25px">');
  }
  var _bin_ich_admin=bin_ich_admin(event.admin);

  $.each(this.getAdditionalServicesToServicegroup(event, sg_id, _bin_ich_admin), function(i,s) {
    rows.push('<tr><td><input type="checkbox" '+s.checked+' id="on_'+s.id+'"/><td><p>'+masterData.service[s.id].bezeichnung);
    if (masterData.service[s.id].notiz!="")
      rows.push('&nbsp; <small>('+masterData.service[s.id].notiz+")</small>");
      if ((masterData.auth.editgroup!=null) && (masterData.auth.editgroup[sg_id])) {
        rows.push('<td>'+form_renderImage({htmlclass:"edit-service", link:true, data:[{name:"service-id", value:s.id}], src:"options.png", width:20}));
      }
    
  });
  if ((masterData.auth.editgroup!=null) && (masterData.auth.editgroup[sg_id])) {
    rows.push('<tr><td><td><i><a href="#" class="newService">Neuen Service erstellen</a></i><td>'
        +form_renderImage({cssid:"addService", src:"plus.png", width:20}));
  }
  rows.push("</table></div>");
  
  var elem = this.showDialog("Service zum Event hinzufügen oder entfernen", rows.join(""), 450, 500, {
      "Speichern": function() {
        obj=new Object();
        auto=new Array();
        obj.func="addOrRemoveServiceToEvent";
        obj.id=event.id;
    
        var k=0;
        $("#in_edit input:checkbox").each(function (i) {
          if ($(this).attr("id").indexOf("on_")==0) {
            var service_id=$(this).attr("id").substr(3,99);
            obj["col"+k]=service_id;
            obj["val"+k]=$(this).attr("checked");
            obj["count"+k]=$("#service_"+service_id).val();            
            if ($("#auto_"+service_id).attr("checked"))
              auto.push(service_id);
            k++;
          }
        });
        
        elem.html("<p><br/><b>Daten werden gespeichert...</b><br/><br/>");
                
        churchInterface.jsendWrite(obj, function(ok, json) {
          if (!ok) alert("Fehler beim Speichern: "+json);
          else {
            cs_loadEventData(event.id, function(){
              elem.dialog("close");
              this_object.renderList(allEvents[event.id]);
            });
          }
        });  
      },
      "Abbrechen": function() {
        $(this).dialog("close");
      }
    });
  elem.find("input:checkbox").change(function(k,a) {
    if ($(this).attr("id")=="cb_enableAll") {
      var checked=this.checked;
      elem.find("input:checkbox").each(function(i) {
        if ($(this).attr("id").indexOf("on_")==0) {
          var id = $(this).attr("id").substr(3,99);
          if ((checked==true) || (t.countActiveServices(event, id)==0)) {
            this.checked=checked;
            if (checked) {
              $("#service_"+id).removeAttr("disabled");
              if ($("#service_"+id).val()==0) $("#service_"+id).val(1);
              $("#auto_"+id).removeAttr("disabled");
            }
            else {
              $("#service_"+id).attr("disabled","disabled");
              if ($("#service_"+id).val()>0) $("#service_"+id).val(0);
              $("#auto_"+id).attr("disabled","disabled");
            }
          }
        }
      });
    }
    else if ($(this).attr("id").indexOf("on_")==0) {
      var checked=this.checked;
      var id = $(this).attr("id").substr(3,99);
      if (checked) {
        $("#service_"+id).removeAttr("disabled");
        if ($("#service_"+id).val()==0) $("#service_"+id).val(1);
        $("#auto_"+id).removeAttr("disabled");
      }
      else {
        var count=t.countActiveServices(event, id);
        if (count>0) { 
          $(this).attr("checked",true);
          alert("Service kann nicht entfernt werden, da noch Personen angefragt sind bzw. zugesagt haben.");
        }
        else 
        {
          $("#service_"+id).attr("disabled","disabled");
          if ($("#service_"+id).val()>0) $("#service_"+id).val(0);
          $("#auto_"+id).attr("disabled","disabled");
        }
      }
    }
  });
  elem.find("input:text").change(function(k,a) {
    if ((this.value==0)) {
      alert("Es muss mindestens ein Services angegeben werden. Wenn der Dienst nicht notwendig ist, bitte mit der Checkbox ausschalten.");
      this.value=1;      
    } else { 
      var counter=t.countActiveServices(event, $(this).attr("id").substr(8,99));
      if (counter>this.value) {
        this.value=counter;
        alert("Es sind schon "+counter+" Personen angefragt! Bitte Anfragen absagen um den Wert hier zu reduzieren.");
      }
    }
  });
  elem.find("#addService").click(function() {
    t.editService(null, sg_id);
    return false;
  });
  elem.find("a.newService").click(function() {
    t.editService(null, sg_id);
    return false;
  });
  elem.find("a.edit-service").click(function() {
    t.editService($(this).attr("data-service-id"), sg_id);
    return false;
  });
};

ListView.prototype.sendEMailToEvent = function(event) {
  var this_object=this;
  var rows = new Array();
  
  var _dienstgruppen = new Array();
  $.each(event.services, function(k,service) {
    if ((service.valid_yn==1) && (service.cdb_person_id!=null)) {
      _dienstgruppen[masterData.service[service.service_id].servicegroup_id]=true;
    }
  });
  if (_dienstgruppen.length==0)
    alert("Um eine E-Mail zu senden, muss mindestens eine bekannte Person angefragt sein.");
  else {
    rows.push('<form class="form-inline">');    
    rows.push('<div class="well">E-Mail an folgende Mitarbeiter senden:<br/><p><p>');
    var c=0;
    $.each(this_object.sortMasterData(masterData.servicegroup), function(k,a) {
      if (_dienstgruppen[a.id]) {
        var checked="";
        if (this_object.isLeaderOfServiceGroup(a.id)) checked="checked";
        rows.push(form_renderCheckbox({label:a.bezeichnung, cssid:"checkSG"+a.id,  controlgroup:false,
                              checked:this_object.isLeaderOfServiceGroup(a.id)})+"&nbsp; &nbsp; ");
      }
    });
    rows.push('</div>');
    
    rows.push(form_renderInput({label:"Betreff", value:"Infos zum "+event.bezeichnung+" am "+event.startdate.toDateEn(true).toStringDe(true),
                cssid:"betreff", type:"xlarge"}));
    
    var txt='<div id="inhalt" class="well" contenteditable="true">';
    if (event.agenda && (user_access("view agenda", event.category_id) || t.amIInvolved(event))) {
      var a=agendaView.getAgendaForEventIdIfOnline(event.id);
      if (a!=null) 
        txt=txt+'<br/><br/><a href="'+masterData.base_url+'?q=churchservice&id='+a.id+'#AgendaView" class="button">Ablauf aufrufen</a>';
    }

    if (masterData.settings.signature!=null) txt=txt+masterData.settings.signature;
    txt=txt+'</div>';
    rows.push(txt);
    
    if (masterData.settings.sendBCCMail==null)
      masterData.settings.sendBCCMail=1;
    rows.push(form_renderCheckbox({label:"Eine Kopie an mich senden", cssid:"sendBCCMail", 
      checked:masterData.settings.sendBCCMail==1}));
    
    rows.push("</form");

    var elem=this_object.showDialog("E-Mail an ausgewählte Mitarbeiter",rows.join(""), 600,650, {
        "Absenden": function() {
          var obj = new Object();
          var ids="";
          $.each(event.services, function(k,service) {
            if ((service.valid_yn==1) && (service.cdb_person_id!=null) && ($("#checkSG"+masterData.service[service.service_id].servicegroup_id).attr("checked"))) {
              ids=ids+service.cdb_person_id+",";
            }
          });
          if (ids=="") 
            alert("Bitte eine Dienstgruppe markieren!");
          else {
            masterData.settings.sendBCCMail=($("#sendBCCMail").attr("checked")?1:0);
            if (masterData.settings.sendBCCMail==1)            
              ids=ids+masterData.user_pid+",";            
            churchInterface.jsendWrite({func:"saveSetting", sub:"sendBCCMail", val: masterData.settings.sendBCCMail});
            ids=ids+"-1";
            obj.ids=ids;
            obj.betreff=$("#betreff").val();
            obj.inhalt=CKEDITOR.instances.inhalt.getData();
            obj.domain_id=event.id;
            obj.func="sendEMailToPersonIds";
            churchInterface.jsendWrite(obj, function(res, data) {
              if (res) alert("EMail wurde gesendet. "+(data!=null?data:""));
              else alert("Problem: "+data)
            }, null, false);          
            $(this).dialog("close");
          }
        },
        "Abbrechen": function() {
          $(this).dialog("close");
        }        
    });
    form_implantWysiwygEditor("inhalt", false, false);
    elem.find("#inhalt").focus();
    elem.find("a").click(function(c) {
      if (($(this).attr("id")=="Vorname") || ($(this).attr("id")=="Nachname")) {
        $("#inhalt").insertAtCaret("["+$(this).attr("id")+"]");    
        return false;
      }
    });
    
  }
};

ListView.prototype.editNote = function(event) {
  var this_object=this;
  var rows = new Array();
  rows.push("<br/>"+this_object.renderTextarea("infos", "Weitere Infos zum Event:<br/>", event.special, 35, 8));
  this_object.showDialog("Weitere Infos editieren",rows.join(""), 400,400, {
    "Speichern": function() {
      var obj = new Object();
      obj.func="saveNote";
      var txt=$("#infos").val();
      obj.text=txt;
      obj.event_id=event.id;      
      churchInterface.jsendWrite(obj, function(res) {
        if (res) {
          event.special=txt;
          this_object.renderList(event);
        }
      });
      $(this).dialog("close");
    },
    "Abbrechen": function() {
      $(this).dialog("close");
    }        
  }); 
};

ListView.prototype.attachFile = function(event) {
  var this_object=this;
  var rows = new Array();
  var checked=false;
  
  var eventIds= new Array();
  var day=event.startdate.toDateEn(false).toStringEn(false);
  $.each(allEvents, function(k,a) {
    if ((event.id!=a.id) && (a.startdate.toDateEn(false).toStringEn(false)==day) && (event.category_id==a.category_id)) {
      eventIds.push(a.id);
    }    
  });

  rows.push('<form class="form-inline"><legend>1. Option zum Hochladen der Datei</legend>');
  if (eventIds.length>0) {
    checked=masterData.settings.file_attachToAllEvents==1;
    rows.push(form_renderCheckbox({
         cssid: "file_attachToAllEvents",
         checked:checked,
         label:"Datei automatisch an alle Events des Tages mit gleicher Kategorie anh&auml;ngen"
      }));
  }
  
  var _dienstgruppen = new Array();
  if (event.services!=null) {
    $.each(event.services, function(k,service) {
      if ((service.valid_yn==1) && (service.cdb_person_id!=null)) {
        _dienstgruppen[masterData.service[service.service_id].servicegroup_id]=true;
      }
    });
  }
  if (_dienstgruppen.length>0) {
    rows.push("<p>Folgende Dienstgruppen per E-Mail &uuml;ber die neue Datei informieren:<p>");
    $.each(this_object.sortMasterData(masterData.servicegroup), function(k,a) {
      if (_dienstgruppen[a.id]) {
        checked=masterData.settings["file_informServiceGroup"+a.id]==1;
        rows.push(form_renderCheckbox({label:a.bezeichnung, controlgroup:false, checked:checked, 
            cssid:"file_informServiceGroup"+a.id})+"&nbsp; &nbsp;");
      }
    });    
   rows.push('<p>Hier kann ein Kommentar angeben werden:<div class="well" contenteditable="true" id="editor">&nbsp;</div>');    
    
  }
  if (rows.length==1) { 
    rows = new Array();
    rows.push('<legend>Datei ausw&auml;hlen</legend>');
  }
  else 
    rows.push('<legend>2. Datei ausw&auml;hlen</legend>');
  
  rows.push("<p><div id=\"upload_button\">Nochmal bitte...</div><p>");
  
  rows.push("<p><small>Sobald eine Datei hochgeladen wurde, werden alle angewählten Mitarbeiter per E-Mail informiert.</form>");
  
  var elem = form_showDialog("Datei zum Event "+event.bezeichnung+" hochladen",rows.join(""), 520, 500, {
    "Abbrechen": function() {
      $(this).dialog("close");
    }
  });
  form_implantWysiwygEditor("editor", null, true);
  elem.find("input:checkbox").change(function() {
    masterData.settings[$(this).attr("id")]=($(this).attr("checked")=="checked"?1:0);
    churchInterface.jsendWrite({func:"saveSetting", sub:$(this).attr("id"), val:($(this).attr("checked")=="checked"?1:0)});    
  });

  
  var uploader = new qq.FileUploader({
    element: document.getElementById('upload_button'),
//    action: masterData.modulespath+'/uploadFile.php',
    action: "?q=churchservice/uploadfile",
    params: {
//      file_dir:masterData.files_dir+"/files/"+"1",   NICHT MEHR NOTWEDNIG
      domain_type:"service",
      domain_id:event.id
    },
    multiple:false,
    debug:true,
    onComplete: function(file, filename, res) {
      if (res.success) {
        var elem2 = this_object.showDialog("Bitte warten", "Datei wird gespeichert...", 300,300);
        var kommentar = "";
        if (CKEDITOR.instances.editor!=null)
          kommentar=CKEDITOR.instances.editor.getData();
        window.setTimeout(function() {
          // Datei kopieren, wenn es sein soll
          if ((masterData.settings.file_attachToAllEvents==1) && (eventIds.length>0)) {
            churchInterface.jsendWrite({func:"copyFile", id:res.id, domain_id:eventIds.join(",")},function(ok, data) {
              if (!ok) alert("Probleme beim Kopieren der Daten auf die anderen Events: "+data);  
            }, false);        
          }
          // Mails schicken, wenn es sein soll
          var mailGroups=new Array();
          $.each(this_object.sortMasterData(masterData.servicegroup), function(k,a) {
            if (_dienstgruppen[a.id]) {
              if (masterData.settings["file_informServiceGroup"+a.id]==1)
                mailGroups.push(a.id);
            }
          });
          if (mailGroups.length>0) {
            // Gehe nun alle Events durch und nat�rlich das angeklickte auch, deshalb PUSH ich das hier mit rien
            eventIds.push(event.id);
            $.each(eventIds, function(k,i) {
              // Entweder an alle, oder nur wo die Id gleich ist
              if ((masterData.settings.file_attachToAllEvents==1) || (i==event.id)) {
                var ev=allEvents[i];
                var obj = new Object();
                var mailPersons=new Array();
                $.each(ev.services, function(k,service) {
                  if ((service.valid_yn==1) && (service.cdb_person_id!=null) && (masterData.service[service.service_id]!=null) 
                      && (_dienstgruppen[masterData.service[service.service_id].servicegroup_id]) 
                      &&  (masterData.settings["file_informServiceGroup"+masterData.service[service.service_id].servicegroup_id]==1)) {
                    mailPersons.push(service.cdb_person_id);
                  }
                });
                if (mailPersons.length>0) {
                  obj.ids=mailPersons.join(",");
                  obj.betreff="Neue Datei zum Event "+ev.bezeichnung+" "+ev.startdate.toDateEn(true).toStringDe(true);
                  obj.inhalt="<h3>Hallo [Vorname]!</h3>"+
                      "<p>f&uuml;r <i>"+ev.bezeichnung+"</i> wurde eine neue Datei hochgeladen. Du wirst informiert, da Du zum Dienst angefragt bist.";
                  if ((kommentar!=null) && (kommentar!="")) 
                    obj.inhalt=obj.inhalt+'<p><i>'+kommentar+'</i></p>';
                  obj.inhalt=obj.inhalt+'<ul><li><a href="'+masterData.files_url+"/files/service/"+ev.id+"/"+res.filename+'">'+res.bezeichnung+'</a></ul>';
                  obj.domain_id=ev.id;
                  obj.usetemplate="true";
                  obj.func="sendEMailToPersonIds";
                  churchInterface.jsendWrite(obj, function(ok, data) {
                    if (ok) alert("E-Mail wurde gesendet. "+(data!=null?data:""));
                    else alert("Problem beim Senden: "+data);   
                  }, null, false);          
                }
              }
            });
          }
          
          elem.dialog("close");      
          cs_loadFiles(function() {
            elem2.dialog("close");
          });
        },100);
      }
      else alert("Sorry, es ist ein Fehler beim Hochladen aufgetreten!");
    }
  });    
};

ListView.prototype.renderFiles = function () {
  var t=this;
  if (allEvents!=null) {
    if ((masterData.auth.write)) { // TODO || (bin_ich_admin(a.admin))) {
      t.renderFilelist("Dateien zum Event:", allEvents, null, function(id, domain_id) {
        delete allEvents[domain_id].files[id];
        t.renderList(allEvents[domain_id]);
      });
    }
    else t.renderFilelist("Dateien zum Event:", allEvents);
  }

  $("#cdb_content span.tooltip-file").each(function() {
    var tooltip=$(this);
    tooltip.tooltips({
      data:{id:tooltip.attr("data-id"), event_id:tooltip.parents("tr").attr("id")},
      render:function(data) {        
        return t.renderTooltipForFiles(tooltip, allEvents[data.event_id].files[data.id], 
            (masterData.auth.admin || allEvents[data.event_id].files[data.id].modified_pid==masterData.user_pid 
              || bin_ich_admin(allEvents[data.event_id].admin)));
      },      
      afterRender: function(element, data) {
        return t.tooltipCallbackForFiles(data.id, element, allEvents, data.event_id);
      }
    });    
  });  
};


ListView.prototype.addFurtherListCallbacks = function(cssid) {
  var t=this;
  if (cssid==null) cssid="#cdb_content";
  t.renderFiles();

  $("#cdb_content .tooltips").each(function() {
    var tooltip=$(this);
    tooltip.tooltips({
      data:{id:tooltip.attr("data-tooltip-id"), event_id:tooltip.parents("tr").attr("id")},
      showontouchscreen:false,
      render:function(data) {
        return t.renderTooltip(data.id, data.event_id, tooltip.attr("member")!=null, tooltip.attr("member")!=null);
      },
      
      afterRender: function(element, data) {
        element.find("a.edit-notification").click(function() {
          clearTooltip();
          t.editNotification($(this).attr("data-domain-type"), $(this).attr("data-domain-id"));
          return false;
        });
        element.find("a.simulate-person").click(function() {
          window.location.href="?q=simulate&id="+$(this).attr("data-id")+"&location=churchservice";
          return false;
        });
        element.find("a.email-person").click(function() {
          clearTooltip();
          t.mailPerson($(this).attr("data-id"));
          return false;
        });     
        element.find("a.edit-service").click(function() {
          clearTooltip();
          t.editService($(this).attr("data-id"));          
        })
      }
    });    
  });
  
  $(cssid+" a.edit-event").click(function() {
    clearTooltip();
    t.renderEditEvent(allEvents[$(this).parents("tr").attr("id")]);
    return false;
  });

  $(cssid+" a.show-agenda").click(function() {
    var event=allEvents[$(this).parents("tr").attr("id")];
    if (!event.agenda) {
      t.currentEvent=event;
      agendaView.currentAgenda=null;
      churchInterface.setCurrentView(agendaView, true);      
    }
    else {
      t.entryDetailClick($(this).parents("tr").attr("id")); 
    }    
  });

  
  $(cssid+" a").click(function (a) {
    clearTooltip();
    var cssid=$(this).attr("id");
    if (cssid==null) 
      return true;
    else if (cssid.indexOf("editEvent")==0) {
      t.renderEditEvent(allEvents[cssid.substr(9,99)]);
    }
    else if (cssid.indexOf("mailEvent")==0) {
      t.sendEMailToEvent(allEvents[cssid.substr(9,99)]);
    }
    else if (cssid.indexOf("filterMyAdmin")==0) {
      if (t.filter["filterMeine Filter"]==2)
        delete t.filter["filterMeine Filter"];
      else t.setFilter("filterMeine Filter",2);
      t.renderView();
    }
    else if (cssid.indexOf("editNote")==0) {
      t.editNote(allEvents[cssid.substr(8,99)]);
    }
    else if (cssid.indexOf("attachFile")==0) {
      t.attachFile(allEvents[cssid.substr(10,99)]);
    }
    else if (cssid.indexOf("edit_es_")==0) {
      t.renderEditEventService(cssid.substr(8,99),$(this).attr("eventservice_id"));      
    }
    else if (cssid.indexOf("addMoreCols")==0) {
      t.addMoreCols();
    }
    else if (cssid.indexOf("delCol")==0) {
      var id=cssid.substr(6,99);
      masterData.settings["viewgroup"+id]=0;
      churchInterface.jsendWrite({func:"saveSetting", sub:"viewgroup"+id, val:0});
      t.renderList();
    }
  });
  $(cssid+" a.edit-service").click(function() {
    t.renderAddServiceToServicegroup(allEvents[$(this).attr("data-event-id")], $(this).attr("data-servicegroup-id"), masterData.user_pid);
  });
  
  $('#ical_abo').click(function() {
    ical_abo();
    return false;
  });
};

ListView.prototype.editNotification = function(domain_type, domain_id) {
  var t=this;
  
  var form = new CC_Form();
  var value=null;
  if (domain_id!=null && getNotification(domain_type, domain_id)!==false) 
    value=getNotification(domain_type, domain_id);
  
  if (domain_id!=null && value==null) {
    form.addHtml('<legend>Neues Abo f&uuml;r '+masterData[domain_type][domain_id].bezeichnung+'</legend>');
    
    $.each(masterData.notificationtype, function(k,a) {
      a.sortkey=a.delay_hours;
    });
    
    form.addSelect({label:"Wann soll bei Neuigkeiten f&uuml;r <b>"+masterData[domain_type][domain_id].bezeichnung+"</b> benachrichtigt werden?",
           data:masterData.notificationtype, type:"medium", controlgroup:false, htmlclass:"new-notificationtype", selected:value, freeoption:true});
    form.addHtml('<p><p>');
  }
  
  if (masterData.notification[domain_type]!=null) {
    form.addHtml('<legend>Vorhandene Abonnements</legend>');
    form.addHtml('<table class="table table-condensed"><tr><th style="min-width:60px">Abo<th style="min-width:60px">Notiz<th>Wie oft?<th width="22px">');
    $.each(masterData.notification[domain_type], function(k,a) {
      form.addHtml('<tr data-id="'+k+'"><td>'+masterData[domain_type][k].bezeichnung+'<td>');
      if (masterData[domain_type][k].notiz!=null)
        form.addHtml('<small>'+masterData[domain_type][k].notiz+'</small>');        
      form.addHtml('<td>');
      form.addSelect({data:masterData.notificationtype, type:"medium", htmlclass:"edit-notificationtype", 
        selected:a.notificationtype_id, controlgroup:false});
      form.addHtml('<td>');
      form.addImage({src:"trashbox.png", width:20, htmlclass:"delete-notification", link:true});
    });
  }
  
  var elem=form_showDialog("Abonnement bearbeiten",form.render(null, "vertical"), 460,500, {
    "Schliessen": function() {
      $(this).dialog("close");
    }
  });
  
  elem.find('select.new-notificationtype').change(function() {
    if ($(this).val()!="") {
      var notificationtype_id=$(this).val();
      if (masterData.notification[domain_type]==null)
        masterData.notification[domain_type]=new Object();
      masterData.notification[domain_type][domain_id]={notificationtype_id:notificationtype_id, lastsenddate:null};
      elem.dialog("close");
      t.editNotification(domain_type, domain_id);        
      churchInterface.jsendWrite({func:"editNotification", domain_type:domain_type, domain_id:domain_id, 
           notificationtype_id:notificationtype_id}, function(ok, data) {
        if (!ok) alert("Fehler aufgetreten: "+data);
      });
    }
  });  
  elem.find('select.edit-notificationtype').change(function() {
    if ($(this).val()!="") {
      var notificationtype_id=$(this).val();
      var domain_id=$(this).parents("tr").attr("data-id");
      masterData.notification[domain_type][domain_id]={notificationtype_id:notificationtype_id, lastsenddate:null};
      elem.dialog("close");
      t.editNotification(domain_type);
      churchInterface.jsendWrite({func:"editNotification", domain_type:domain_type, domain_id:domain_id, 
        notificationtype_id:notificationtype_id}, function(ok, data) {
        if (!ok) alert("Fehler aufgetreten: "+data);
      });
    }
  });  
  elem.find('a.delete-notification').click(function() {
    var domain_id=$(this).parents("tr").attr("data-id");
    delete masterData.notification[domain_type][domain_id];
    elem.dialog("close");
    t.editNotification(domain_type);
    churchInterface.jsendWrite({func:"editNotification", domain_type:domain_type, domain_id:domain_id}, function(ok, data) {
      if (!ok) alert("Fehler aufgetreten: "+data);
    });
    return false;
  });
};

function ical_abo() {
  var rows=new Array(); 
  rows.push('<legend>Dienstplan abonnieren</legend>Deine Termine dieses Kalenders k&ouml;nnen abonniert werden. Hierzu kann die Adresse anbei in einen beliebigen Kalender importiert werden,'+
             ' der iCal unterst&uuml;tzt.<br><br>');
  var id=$(this).attr("data-id"); 
  rows.push(form_renderInput({label:"iCal-URL", value:settings.base_url+"?q=ical&id="+masterData.user_pid, disable:true}));
  form_showOkDialog("Kalender abonnieren", rows.join(""));  
}

ListView.prototype.addMoreCols = function() {
  var rows = new Array();
  var t=this;
  rows.push("<legend>Auswahl der Servicegruppen</legend>");
  $.each(churchcore_sortData(masterData.servicegroup,"sortkey"), function(k,a) {
    if (masterData.auth.viewgroup[a.id]!=null) {
      rows.push(form_renderCheckbox({
        cssid:"viewgroup"+a.id, label:a.bezeichnung, controlgroup:false,
        checked: (masterData.settings["viewgroup"+a.id]==null) || (masterData.settings["viewgroup"+a.id]==1)
      }));
    }
  });  
  var elem = this.showDialog("Anpassen der Tabelle", rows.join(""), 400, 400, {
    "Schliessen": function() {
      $(this).dialog("close");
    }
  });
  elem.find("input:checkbox").click(function(c) {
    masterData.settings[$(this).attr("id")]=($(this).attr("checked")=="checked"?1:0);
    churchInterface.jsendWrite({func:"saveSetting", sub:$(this).attr("id"), val:masterData.settings[$(this).attr("id")]});
    t.renderList();
  });  
};

ListView.prototype.renderFilter = function() {  
  var this_object=this;

  var rows = new Array();
  rows.push("<div id=\"divviewmap\" class=\"new-entry\"></div>");
  rows.push("<div id=\"divaddfilter\" style=\"width:100%;\" class=\"new-entry\"></div>");
  
  var form = new CC_Form();
  form.setHelp("ChurchService-Filter");
  //form.setLabel("Filterfunktionen");

  form.addHtml("<div id=\"dp_currentdate\" style=\"\"></div>");
  rows.push("<div id=\"dp_currentdate\" style=\"\"></div>");

  rows.push('<p> &nbsp; <small><img src="system/assets/img/red_dot.png"/> Abwesenheit  &nbsp; <img src="system/assets/img/yellow_dot.png"/> Angefragt  &nbsp; <img src="system/assets/img/green_dot.png"/> Zugesagt</small>');
//  form.addSeparator();
  
  var _meineDienste = new Array();
  form_addEntryToSelectArray(_meineDienste, 1, "Meine Dienste filtern");
  _drin=false;
  if (allEvents!=null)
    $.each(allEvents, function(k,event) {
      if (bin_ich_admin(event.admin)) {
        _drin=true;
        // exit
        return false;
      }
    });
  if (_drin) {
    form_addEntryToSelectArray(_meineDienste, 2, "Meine Events filtern");
  }
  
  //form.addSelectFilter(_meineDienste,"Meine Filter",this.filter["filterMeine Filter"]);
  form.addSelect({data:_meineDienste,
                  label:"Meine Filter",
                  selected:this.filter["filterMeine Filter"],
                  freeoption:true,
                  cssid:"filterMeine Filter",
                  type:"medium"});
  
  
  if (this.name!="FactView") {
    form.addSelect({data:this.sortMasterData(masterData.servicegroup),
                    label:"Dienstgruppen",
                    selected:this.filter["filterDienstgruppen"],
                    freeoption:true,
                    cssid:"filterDienstgruppen",
                    type:"medium",
                    func:function(s) {return (masterData.auth.viewgroup!=null) && (masterData.auth.viewgroup[s.id])}
    });
  }
  
  form.addHtml('<div id="filterKategorien"></div>');
  
//  form.addCheckbox("searchFuture", this.filter["searchFuture"]!=null, "nur zuk&uuml;nfte Events");
  form.addCheckbox({cssid:"searchChecked",label:"markierte"});  
  rows.push(form.render(true));
     
  rows.push("<div id=\"cdb_filtercover\"></div>");
 
  $("#cdb_filter").html(rows.join("")); 
  
  if (this.filter["filterKategorien"]!=null) {
    if (typeof(this.filter["filterKategorien"])=="string")
      this_object.makeFilterCategories(masterData.settings.filterCategory);
      
    this.filter["filterKategorien"].render2Div("filterKategorien", {label:"Kalender"});
  }

  $.each(this.filter, function(k,a) {
    $("#"+k).val(a);
  });
   
  // Callbacks 
  filter=this.filter;
  this.implantStandardFilterCallbacks(this, "cdb_filter");
  this.renderCalendar();

};

ListView.prototype.renderCalendar = function() {
  var t=this;
  $("#dp_currentdate").datepicker({
    dateFormat: 'dd.mm.yy',
    showButtonPanel: true,
    dayNamesMin: dayNamesMin,
    monthNames: monthNames, 
    currentText: "Heute",
    firstDay: 1,
    beforeShowDay: function(date) {
                      var today=new Date();
                      var checkable=today.toStringEn()==date.toStringEn();
                      var angefragt=false;
                      var zugesagt=false;
                      
                      $.each(allEvents, function(k,a) {
                        if (date.sameDay(a.startdate.toDateEn(false))) {
                          checkable=true;            
                          if (a.services!=null) {
                            $.each(a.services, function(i,service) {
                              if ((service.valid_yn==1) && (service.cdb_person_id==masterData.user_pid)) {
                                if (service.zugesagt_yn==1)
                                  zugesagt=true;
                                else angefragt=true;
                                return false;
                              }
                            });
                          }
                        }
                      });
                      var myday="";
                      if (angefragt)
                        myday="angefragt";
                      else if (zugesagt)
                        myday="zugesagt highlight";  

                      // Nun die Abwesenheit
                      var absent=false;
                      if ((allPersons[masterData.user_pid]!=null) && (allPersons[masterData.user_pid].absent!=null)) {
                        $.each(allPersons[masterData.user_pid].absent, function(k,a) {
                          if ((a!=null) && (a.startdate.withoutTime()<=date) && (a.enddate>=date)) {
                            absent=true;
                            return false;
                          }
                        });
                      }
                      if (absent) myday=myday+" absent";
                      
                      return [checkable,myday];
    },
    onSelect : function(dateText, inst) {
      if (debug) console.log("onSelect "+dateText);
      t.currentDate=dateText.toDateDe();
      //    t.currentDate.addDays(-1);
      t.listOffset=0;
      if (t.filter["searchEntry"]!=null) {
        delete t.filter["searchEntry"];
        t.renderFilter();
        t.renderListMenu();
      }
      t.renderList();
      t.addAbsentButton();
    },
    onChangeMonthYear:function(year, month, inst) {
      if (debug) console.log("onChangeMonthYear "+year+" "+month);
      var dt = new Date();
      if (t.allDataLoaded) {
        // Wenn es der aktuelle Monat ist, dann gehe auf den heutigen Tag
        if ((dt.getFullYear()==year) && (dt.getMonth()+1==month))
          t.currentDate=dt.withoutTime();
        else
          t.currentDate=new Date(year, month-1);
      }
      t.listOffset=0;
      t.addAbsentButton();
      if (t.renderTimer!=null) clearTimeout(t.renderTimer);
      t.renderTimer=window.setTimeout(function() {
        t.renderTimer=null;
        t.renderList();
      },150);
    }
  });    
  $("#dp_currentdate").datepicker($.datepicker.regional['de']);
  $("#dp_currentdate").datepicker('setDate', t.currentDate.toStringDe());
  t.addAbsentButton();
};

ListView.prototype.addAbsentButton = function () {
  var t=this;
  window.setTimeout(function() {
    if ($("#btn_abwesenheit").length==0) {
      $("#dp_currentdate div.ui-datepicker-buttonpane").append('<button type="button" id="btn_abwesenheit" class="ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all">Abwesenheit bearbeiten</button>');
      $("#btn_abwesenheit").hover(function(k,a) {
        $(this).addClass("ui-state-hover");
        }, function() { $(this).removeClass("ui-state-hover");} 
      ); 
      $("#btn_abwesenheit").click(function(k,a) {
        t.editAbsent();
      });
      }
  },1);
};

ListView.prototype.editAbsent = function(pid, name, fullday) {
  var this_object=this;
  var rows = new Array();
  var new_absent= new Object();
  if (fullday==null) fullday=true;
  
  new_absent.startdate=this_object.currentDate.toStringDe(false).toDateDe(false);
  new_absent.enddate=new Date(new_absent.startdate);
  if (fullday)
    new_absent.enddate.addDays(7);
  else {
    new_absent.startdate.setHours(10);
    new_absent.enddate.setHours(20);
  }
  
  if (pid==null) {
    pid=masterData.user_pid;
    name=masterData.user_name;
  }
  
  if (allPersons[pid]==null)
    allPersons[pid]=new Object();

  if (masterData.auth.manageabsent)
    var form = new CC_Form('Neue Abwesenheit f&uuml;r <a href="#" id="changePerson"><font style="text-decoration:underline">'+name+'</font> <small>(&auml;ndern)</small></a>');
  else
    var form = new CC_Form('Neue Abwesenheit f&uuml;r '+name);
  form.addHtml("<table><tr><td>");
  form.addInput({
    cssid:"inputStartdate",
    label:"Von",
    c_ontrolgroup:false,
    separator:"&nbsp;",
    value:new_absent.startdate.toStringDe(),
    type:"small"
  });  
  form.addHtml("<div id=\"dp_startdate\" style=\"position:absolute;background:#e7eef4;z-index:12001;\"/>");

  if (fullday!=true) {
    form.addHtml("<td>");
    var hours=_getHoursArray();
    form.addSelect({
      data:hours, 
      cssid:"inputStarthour", 
      label:"Stunde",
      selected:new_absent.startdate.getHours(), 
      htmlclass:"input-mini" 
    });
    form.addHtml("<td>");
    
    var minutes=_getMinutesArray();
    form.addSelect({
      data:minutes, 
      label:"Minute",
      cssid:"inputStartminutes", 
      selected:new_absent.startdate.getMinutes(), 
      type:"mini" 
    });  
    form.addHtml("<td>");
  }
    
  form.addHtml("<td>");
  form.addInput({
    cssid:"inputEnddate",
    label:"Bis",
    c_ontrolgroup:false,
    separator:"&nbsp;",
    value:new_absent.enddate.toStringDe(),
    type:"small"
  });  
  form.addHtml("<div id=\"dp_enddate\" style=\"position:absolute;background:#e7eef4;z-index:12001;\"/>");
  form.addHtml("<td>"); 
  
  if (fullday!=true) {
    form.addSelect({
      data:hours, 
      cssid:"inputEndhour", 
      label:"Stunde",
      selected:new_absent.enddate.getHours(), 
      htmlclass:"input-mini" 
    });
    form.addHtml("<td>");
    
    form.addSelect({
      data:minutes, 
      label:"Minute",
      cssid:"inputEndminutes", 
      selected:new_absent.enddate.getMinutes(), 
      type:"mini" 
    });  
    form.addHtml("<tr><td colspan=2>");
   
  }
  
  form.addSelect({
    data:churchcore_sortData(masterData.absent_reason,"sortkey"),
    label:"Grund",
    cssid:"inputAbsentReason",
    type:"medium"
  });
  form.addHtml("<td colspan=2>"); 
  form.addInput({
    data:masterData.absent_reason,
    label:"Kommentar",
    cssid:"inputBezeichnung",
    type:"medium"
  });
  form.addHtml("<td>");
  form.addHtml("<tr><td>");
  form.addCheckbox({label:"Ganzt&auml;gig", checked:fullday, cssid:"wholeday"});
  form.addHtml("<tr><td colspan=4>");
  form.addLink("", "addabsent", "Abwesenheit eintragen");  
  form.addHtml("</table>");

  rows.push(form.render(false, "inline"));
  
  if (allPersons[pid].absent!=null) {
    rows.push('<legend>Bereits eingetragene Abwesenheiten</legend>');
    rows.push('<div style="max-height:180px; overflow-y:auto; overflow-x:auto">');
    rows.push('<table class="table table-condensed"><tr><th>Datum<th>Grund<th>Kommentar<th>');
    var sum=new Object();
    $.each(churchcore_sortData(allPersons[pid].absent, "startdate", true), function(k,a) {
      if ((a.startdate.getHours()==0) && (a.enddate.getHours()==0)) 
        rows.push('<tr><td>'+a.startdate.toStringDe(false)+" - "+a.enddate.toStringDe(false));
      else
        rows.push('<tr><td>'+a.startdate.toStringDe(true)+" - "+a.enddate.toStringDe(true));
      rows.push("<td>"+masterData.absent_reason[a.absent_reason_id].bezeichnung);
      rows.push("<td><small>"+(a.bezeichnung!=null?a.bezeichnung:"")+"</small>");
      rows.push('<td><a href="#" id="delabsent_'+a.id+'">'+this_object.renderImage("trashbox")+'</a>');
      if (sum[a.startdate.getFullYear()]==null)
      sum[a.startdate.getFullYear()]=new Object();
      if (sum[a.startdate.getFullYear()][a.absent_reason_id]==null)
        sum[a.startdate.getFullYear()][a.absent_reason_id]=0;
      sum[a.startdate.getFullYear()][a.absent_reason_id]=sum[a.startdate.getFullYear()][a.absent_reason_id]
             +(a.enddate-a.startdate)/1000/24/60/60+1;
    });
    rows.push('</table>');
    if (masterData.auth.manageabsent) {
      rows.push("<p><small><i>Summe:</i><br/>");
      $.each(sum, function(k,years) {
        rows.push(k+": ");
        $.each(years, function(i,sum) {
          rows.push(masterData.absent_reason[i].bezeichnung+": "+sum+"  ");
        });
        rows.push("<br/>");
      });
      rows.push("</small></p>");
    }
    rows.push('</div>');
  }
  

  var elem=this.showDialog("Abwesenheiten bearbeiten", rows.join(""), 600, 580, {
    "Schliessen": function() {
      $(this).dialog("close");
    }
  });
  $("#inputStartdate").click(function() {
    this_object.implantDatePicker("startdate", new_absent.startdate.toStringDe(), function(dateText) {
      new_absent.startdate=dateText.toDateDe();
      $("#inputStartdate").val(dateText);
      new_absent.enddate=new Date(new_absent.startdate);
      new_absent.enddate.addDays(7);
      $("#inputEnddate").val(new_absent.enddate.toStringDe());
    });
  });
  $("#inputEnddate").click(function() {
    this_object.implantDatePicker("enddate", new_absent.enddate.toStringDe(), function(dateText) {
      new_absent.enddate=dateText.toDateDe();
      $("#inputEnddate").val(dateText);
    });
  });
  elem.find("#inputStarthour").change(function() {
    elem.find("#inputEndhour").val($(this).val()+1);
  });
  
  elem.find("#wholeday").change(function() {
    elem.dialog("close");
    this_object.editAbsent(pid, name, $(this).attr("checked")=="checked");
  });
  elem.find("a").click(function() {
    if ($(this).attr("id").indexOf("addabsent")==0) {
      var d=$("#inputStartdate").val();
      new_absent.startdate=d.toDateDe(true);
      var d=$("#inputEnddate").val();
      new_absent.enddate=d.toDateDe(true);

      new_absent.func="addAbsent";
      new_absent.person_id=pid;
      new_absent.absent_reason_id=$("#inputAbsentReason").val();
      new_absent.bezeichnung=$("#inputBezeichnung").val();
      if (!fullday) {
        new_absent.startdate.setHours($("#inputStarthour").val());
        new_absent.startdate.setMinutes($("#inputStartminutes").val());
        new_absent.enddate.setHours($("#inputEndhour").val());
        new_absent.enddate.setMinutes($("#inputEndminutes").val());
      }
      elem.html("Speichere Daten...");
      churchInterface.jsendWrite(new_absent, function(ok, data) {
        if (ok) { 
          if (allPersons[pid].absent==null) 
            allPersons[pid].absent=new Array();
          new_absent.id=data;
          allPersons[pid].absent.push(new_absent);
          elem.dialog("close");
          this_object.editAbsent(pid, name);
          this_object.renderCalendar();
        }
        else alert("Fehler beim Speichern: "+data);
      });
    }
    else if ($(this).attr("id").indexOf("delabsent")==0) {
      if (confirm("Abwesenheit wirklich entfernen?")) {
        var absent_id=$(this).attr("id").substr(10,99);
        elem.html("Speichere Daten...");
        churchInterface.jsendWrite({func:"delAbsent", id:absent_id}, function(ok, data) {
          if (ok) { 
            $.each(allPersons[pid].absent, function(k,a) {
              if ((a!=null) && (a.id==absent_id))
                delete allPersons[pid].absent[k];
            });
            elem.dialog("close");
            this_object.editAbsent(pid, name);
            this_object.renderCalendar();
          }
          else alert("Fehler beim Entfernen: "+data);
        });
      }
    }
    else if ($(this).attr("id").indexOf("changePerson")==0) {
      var rows=new Array();
      rows.push(form_renderInput({
        label:"Person angeben",
        cssid:"inputPerson"
      }));
      var elemPerson=this_object.showDialog("Andere Person ausw&auml;hlen", rows.join(""), 400, 400, {
        "Abbrechen": function() {
          $(this).dialog("close");
        }
      });
      this_object.autocompletePersonSelect("#inputPerson", false, function(divid, ui) {
        elemPerson.dialog("close");
        elem.dialog("close");
        this_object.editAbsent(ui.item.value, ui.item.label);
      });
      
    }
    return false;
  });

  
};

ListView.prototype.amIInvolved = function(a) {
  if (a.services==null)
    return false;
  var _dabei=false;
  $.each(a.services, function(k,service) {
    if ((service.valid_yn==1) && (service.cdb_person_id==masterData.user_pid)) {
      _dabei=true;
      // exit
      return false;
    }
  });
  if (!_dabei) return false;  
  return true;
};

ListView.prototype.checkFilter = function(a) {
  var filter=this.filter;
  var t=this;
  // Person wurde geloescht o.ae.
  if (a==null) return false;
  // Es gibt noch keine Daten, soll er aber laden ueber Details
  if (a.bezeichnung==null) return true;

  if (this.currentDate>a.startdate.toDateEn())
    return false;
  
  if ((this.filter["filterKategorien"]!=null) && (this.filter["filterKategorien"].filter(a.category_id)))
    return false;

  if (this.filter["filterMeine Filter"]!=null) {
    // Meine Filter filtern, also angefragt oder zugesagt
    if (this.filter["filterMeine Filter"]==1) {
      return t.amIInvolved(a);
    } 
    // Meine Events, wo ich Admin bin
    else if (this.filter["filterMeine Filter"]==2) {
      if (!bin_ich_admin(a.admin))
        return false;
    }
  }

  if (this.filter["searchEntry"]!=null) {
    searchEntry=this.getFilter("searchEntry").toUpperCase();
    if (searchEntry.indexOf('#')==0) {
      if (searchEntry=="#"+a.id) return true; else return false;
    }
    
    var searches=searchEntry.split(" ");
    var res=true;
    $.each(searches, function(k,search) {
      dabei=false;
      if (search!="") {
        
        if ((a.bezeichnung.toUpperCase().indexOf(searchEntry)>=0) || 
            (a.id==search)) dabei=true;
        
        if (a.services!=null)
          $.each(a.services, function(k,b) {
            if ((b.name!=null) && (b.valid_yn==1) && (b.name.toUpperCase().indexOf(search)>=0))
              dabei=true;                
          });
      }
      if (!dabei) {
        res=false;
        return false;
      }
    });
    if (!res) return false;
  }
  

  if ((filter["searchChecked"]!=null) && (a.checked!=true)) return false;
  

  return true;    
};

function _renderDetails(id) {
  $("#detailTD"+id).html("Rendern...");

}

ListView.prototype.renderEntryDetail = function (event_id) {
  var t=this;
  if (allEvents[event_id]==null) return;
  var event=allEvents[event_id];
  t.currentEvent=event;
  $("tr.detail[data-id="+event_id+"]").html("Lade Daten..");
  if (event.agenda) {
    songView.loadSongData();
    agendaView.loadAgendaForEvent(event_id, function(data) {
      var rows=new Array();
      rows.push('<tr class="detail" id="detail'+event_id+'" data-id="'+event_id+'"><td colspan=20><div class="well">');  
      rows.push('<legend>Ablauf ');
      if (agendaView.currentAgenda!=null && agendaView.currentAgenda.final_yn==0)
        rows.push(' ENTWURF');
      rows.push('&nbsp;');
      if (user_access("view agenda", event.category_id))
        rows.push(form_renderImage({src:"agenda_call.png", htmlclass:"show-agenda", data:[{name:"id", value:data.id}], link:true, label:"Ablaufplan aufrufen", width:20})+"&nbsp;");
      rows.push(form_renderImage({src:"printer.png", htmlclass:"print-agenda", data:[{name:"id", value:data.id}], link:true, label:"Druckansicht", width:20}));

      rows.push('</legend>');
      rows.push('<table class="table table-mini AgendaView">');
      rows.push('<tr>'+agendaView.renderListHeader(true));
      $.each(agendaView.getData(true), function(k,a) {
        rows.push('<tr id="'+a.id+'">'+agendaView.renderListEntry(a, true));
      });
      
      rows.push("</table>");
      rows.push('</div>');
      var elem=$("tr[id=" + event_id + "]").after(rows.join("")).next();
      agendaView.addFurtherListCallbacks("tr.detail[data-id="+event_id+"]", true);
      elem.find("a.show-agenda").click(function() {
        agendaView.currentAgenda=allAgendas[$(this).attr("data-id")];
        churchInterface.setCurrentView(agendaView); 
        return false;
      });
      elem.find("a.print-agenda").click(function() {
        fenster = window.open('?q=churchservice/printview&id='+$(this).attr("data-id")+'#AgendaView', "Druckansicht", "width=900,height=600,resizable=yes");
        fenster.focus();
        return false;
      });
    });
  }
};

ListView.prototype.renderEditEntry = function(id, fieldname) {  
  
};
