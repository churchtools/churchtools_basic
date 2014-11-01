
// Constructor
function WeekView() {
  StandardTableView.call(this);
  this.name="WeekView";
  this.currentDate=new Date();
  this.printview=false;
  this.allDataLoaded=false;
  //this.listViewTableHeight=646;
  this.renderTimer=null;
  this.datesIndex=null;
  this.currentBooking=null;
  this.mousePosition={};
}

Temp.prototype = StandardTableView.prototype;
WeekView.prototype = new Temp();
weekView = new WeekView();

function getCRBooking(a) {
  var event = new CCEvent(a);
  event.saveSuccess = saveCRSuccess;
  event.saveSplitSuccess = saveSplitCRSuccess;
  event.name = "Booking";
  return event;
}

function saveCRSuccess(a, b, json) {
  if (a.name=="Event") a = CAL2CRType(a); // When it saved over cal resource
  var t=weekView;
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
  allBookings[newEvent.booking_id] = CAL2CRType(newEvent);
  t.buildDates(allBookings);
  t.renderList();
}

WeekView.prototype.getData = function(sorted) {
  if (sorted) {
    var arr=new Array();
    each(masterData.resources,function(k,a){
      arr[k]=a;
    });
    arr.sort(function(a,b){
      if (masterData.resourceTypes[a.resourcetype_id].sortkey*1>masterData.resourceTypes[b.resourcetype_id].sortkey*1)
        return 1;
      else if (masterData.resourceTypes[a.resourcetype_id].sortkey*1<masterData.resourceTypes[b.resourcetype_id].sortkey*1)
        return -1;
      // Dann sortiere nach Sortkey von der Res
      else if (a.sortkey*1>b.sortkey*1) return 1;
      else if (a.sortkey*1<b.sortkey*1) return -1;
      else return 0;
    });
    return arr;
  }
  else
    return masterData.resources;
};

WeekView.prototype.renderMenu = function() {
  var t=this;
  if ($("#printview").val() != null) {
    this.printview=true;
  }

  menu = new CC_Menu(_("menu"));
  if (!this.printview) {
    if (masterData.auth.write || masterData.auth.virtual)
      menu.addEntry(_("add.new.request"), "anewentry", "star");

    menu.addEntry(_("printview"), "adruckansicht", "print");
  }

  if (masterData.auth.admin) {
    menu.addEntry(_("workload"), "workload", "fire");
    menu.addEntry(_("maintain.masterdata"), "amaintainview", "cog");
  }

  menu.addEntry(_("help"), "ahelp", "question-sign");


  if (!menu.renderDiv("cdb_menu", churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        t.showBookingDetails("new");
      }
      else if ($(this).attr("id")=="workload") {
        t.showAuslastung();
      }
      else if ($(this).attr("id")=="aaddfilter") {
        if (!t.furtherFilterVisible) {
          t.furtherFilterVisible=true;
        } else {
          t.furtherFilterVisible=false;
        }
        t.renderFurtherFilter();
      }
      else if ($(this).attr("id")=="amaintainview") {
        menuDepth="amain";
        churchInterface.setCurrentView(maintainView);
      }
      else if ($(this).attr("id")=="adruckansicht") {
        fenster = window.open('?q=churchresource/printview&curdate='+t.currentDate.toStringEn(), _("printview"), "width=900,height=600,resizable=yes,scrollbars=1");
        fenster.focus();
        return false;
      }
      else if ($(this).attr("id")=="amain") {
        menuDepth="amain";
        t.renderMenu();
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("https://intern.churchtools.de/?q=churchwiki&doc=ChurchResource");
      }
      return false;
    });
  }
  $("#cdb_menu").append('<div id="popupmenu"></div>');
};


WeekView.prototype.showAuslastung = function() {
  var rows = new Array();

  var today = new Date();
  var quartal = new Date(); quartal.addDays(90);
  var year = new Date(); year.addDays(365);

  each(masterData.resources, function(k,a) {
    a.workload = {   Heute : {until : today, count_days : 0, booked_days : 0},
                   Quartal : {until : quartal, count_days: 0, booked_days : 0},
                      Jahr : {until : year, count_days : 0, booked_days : 0} };
  });

  var go=new Date();
  go.addDays(-masterData.entriesLastDays);
  var arr=new Array();
  var in_zones=false;
  do {
    in_zones=false;
    each(masterData.resources, function(k,a) {
      each(a.workload, function(i,zones) {
        if (go.getTime()<=zones.until.getTime()) {
          zones.count_days++;
          in_zones=true;
        }
      });
    });

    if (t.datesIndex!=null && t.datesIndex[go.getFullYear()]!=null &&
        t.datesIndex[go.getFullYear()][go.getMonth()+1]!=null &&
        t.datesIndex[go.getFullYear()][go.getMonth()+1][go.getDate()]!=null)
      each(t.datesIndex[go.getFullYear()][go.getMonth()+1][go.getDate()], function(k,a) {
        each(masterData.resources[allBookings[a.id].resource_id].workload, function(i,zones) {
          if (masterData.resources[allBookings[a.id].resource_id].date!=go.getTime()
                 && go.getTime()<=zones.until) {
            zones.booked_days++;
          }
          masterData.resources[allBookings[a.id].resource_id].date=go.getTime();
        });
      });
    go.addDays(1);
  } while (in_zones);
  rows.push('<legend>Auslastung der letzten '+masterData.entriesLastDays+' Tage bis ...</legend>');
  rows.push('<table class="table table-condensed"><tr><th>Bezeichnung');
  each(churchcore_getFirstElement(masterData.resources).workload, function(i,zones) {
    rows.push('<th>'+i);
  });
  each(churchcore_sortMasterData(masterData.resourceTypes), function(k,rt) {
    rows.push('<tr><td colspan="5"><b>'+rt.bezeichnung+'</b>');
    each(churchcore_sortMasterData(masterData.resources), function(k,r) {
      if (r.resourcetype_id==rt.id) {
        rows.push('<tr><td>'+r.bezeichnung);
        each(r.workload, function(i,zones) {
          rows.push('<td>'+Math.round(zones.booked_days*1000/zones.count_days)/10+"%");
        });
      }
    });
  });
  rows.push('</table>');
  rows.push('<p><p><small>Gezählt werden die Tage mit Buchungen im Vergleich zu allen Tagen innerhalb des angegebenen Zeitbereiches. Es wird dabei nicht berücksichtigt, ob an einem Tag dann mehr Buchungen für eine Ressource vorliegen</small>')
  form_showOkDialog(_("workload"), rows.join(""), 450, 500);
};

WeekView.prototype.renderListMenu = function() {
  var t=this;

  searchEntry=this.getFilter("searchEntry");
  var navi = new CC_Navi();
  each(masterData.resourceTypes, function(k,a) {
    navi.addEntry(t.filter["filterRessourcen-Typ"]==a.id,"ressourcentyp_"+a.id,a.bezeichnung);
  });
  navi.addEntry(t.filter["filterRessourcen-Typ"]=="-1","ressourcentyp_-1","<i>"+_("all")+"</i>");
  navi.addSearch(searchEntry);
  navi.renderDiv("cdb_search", churchcore_handyformat());

  this.implantStandardFilterCallbacks(this, "cdb_search");

  $("#cdb_search a").click(function () {
    t.filter["filterRessourcen-Typ"]=$(this).attr("id").substr(14,99);
    masterData.settings.filterRessourcentyp=t.getFilter("filterRessourcen-Typ");
    churchInterface.jsendWrite({func:"saveSetting", sub:"filterRessourcentyp",
               val:(t.getFilter("filterRessourcen-Typ")!=""?t.getFilter("filterRessourcen-Typ"):-1)});
    t.renderView();
  });
};

WeekView.prototype.renderFilter = function () {
  var rows = new Array();
  var t=this;

  var form = new CC_Form();
  form.setHelp("ChurchResource-Filter");

  form.addHtml("<div id=\"dp_currentdate\"></div>");
  rows.push("<div id=\"dp_currentdate\" style=\"\"></div><br/>");
  form.addSelect({data:masterData.status,
                  label:_("booking.status"),
                  selected:this.filter["filterStatus"],
                  cssid:"filterStatus",
                  freeoption:true,
                  type:"medium"
  });
  form.addCheckbox({cssid:"searchChecked",label:_("selected")});

  rows.push(form.render(true));


  rows.push("<div id=\"cdb_filtercover\"></div>");

  $("#cdb_filter").html(rows.join(""));

  each(this.filter, function(k,a) {
    $("#"+k).val(a);
  });

  this.renderCalender();

  // Callbacks
  filter=this.filter;
  this.implantStandardFilterCallbacks(this, "cdb_filter");
};

WeekView.prototype.renderCalender = function() {
  var t=this;
  $("#dp_currentdate").datepicker({
    dateFormat: 'dd.mm.yy',
    showButtonPanel: true,
    dayNamesMin: dayNamesMin,
    monthNames: getMonthNames(),
    currentText: _("today"),
    defaultDate: t.currentDate,
    firstDay: 1,
    onSelect : function(dateText, inst) {
      t.currentDate=dateText.toDateDe();
      t.renderList();
      t.addWeekButtons();
    },
    onChangeMonthYear:function(year, month, inst) {
      var dt = new Date();
      if (t.allDataLoaded) {
        // Wenn es der aktuelle Monat ist, dann gehe auf den heutigen Tag
        if ((dt.getFullYear()==year) && (dt.getMonth()+1==month))
          t.currentDate=dt;
        else
          t.currentDate=new Date(year, month-1);
        $("#dp_currentdate").datepicker("setDate", t.currentDate);

        if (t.renderTimer!=null) window.clearTimeout(t.renderTimer);
        t.renderTimer=window.setTimeout(function() {
          t.renderTimer=null;
          t.renderList();
          t.addWeekButtons();
        },150);
      }
    }
  });
  $("#dp_currentdate").datepicker($.datepicker.regional['de']);
  t.addWeekButtons();
};

WeekView.prototype.checkFilter = function (a) {
  var filter=this.filter;
  var t=this;
  // eintrag wurde geloescht o.ae.
  if (a==null) return false;

  if ((filter["filterRessourcen-Typ"]!=null) && (filter["filterRessourcen-Typ"]!="-1") && (a.resourcetype_id!=filter["filterRessourcen-Typ"]))
    return false;

  if ((filter["searchChecked"]!=null) && (a.checked!=true)) return false;

  return true;
};

WeekView.prototype.messageReceiver = function(message, args) {
  var t=this;
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      t.buildDates(allBookings);
      // Wenn ein Filter_Id vom Hauptprogramm �bergeben wuede
      if (($("#filter_id").val() != null) && (allBookings[$("#filter_id").val()]!=null)) {
        this.currentDate=new Date(allBookings[$("#filter_id").val()].startdate);
        this.showBookingDetails("edit", $("#filter_id").val());
        $("#filter_id").remove();
      }
      if (($("#curdate").val() != null)) {
        this.currentDate=$("#curdate").val().toDateEn();
        $("#curdate").remove();
      }
      this.renderView();
      this.allDataLoaded=true;
    }
    else if (message=="filterChanged") {
      if (args[0]=="filterRessourcen-Typ") {
        masterData.settings.filterRessourcentyp=this.getFilter("filterRessourcen-Typ");
        churchInterface.jsendWrite({func:"saveSetting", sub:"filterRessourcentyp",
                   val:this.getFilter("filterRessourcen-Typ")});
      }
    }
    else if (message=="pollForNews") {
      var refresh=false;
      if (args!=null)
        each(args, function(k,a) {
          if (a.id!=null) {
            refresh=true;
          }
        });
      if (refresh) {
        var elem = form_showCancelDialog(_("load.data"), '<p>'+form_renderImage({src:"loading.gif"}));

        cr_loadBookings(function() {
          elem.dialog("close");
          t.renderList();
        });
      }
    }
    else
      alert("Message "+message+" unkown!");
  }
};


WeekView.prototype.initView = function () {
  if (masterData.settings.filterRessourcentyp==null)
    masterData.settings.filterRessourcentyp=1;
  this.filter["filterRessourcen-Typ"]=masterData.settings.filterRessourcentyp;

};

WeekView.prototype.getListHeader = function () {
  var rows = new Array();
  var t=this;

  if (t.printview) {
    masterData.settings["listMaxRows"+t.name]=100;
    t.showCheckboxes=false;
    t.showPaging=false;
  }

  var currentDate=new Date(this.currentDate);
  var d=-currentDate.getDay()+1;
  if (d==1) d=-6;
  currentDate.addDays(d);
  currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate()-1);

  rows.push("<th>");
  currentDate.addDays(1);
  rows.push("KW"+currentDate.getKW());
  currentDate.addDays(-1);

  d = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate());
  for (i=0;i<7;i++) {
    d.addDays(1);
    var _class="";
    if (d.toStringDe()==t.currentDate.toStringDe()) _class="active"
    rows.push("<th class=\""+_class+"\">"+dayNamesMin[d.getDay()]+", "+d.toStringDe()+"");
  }
  rows.push("");

  return rows.join("");
};

/**
 * Builds an index <i>this.datesIndex</i> for higher perfomance on accessing bookings
 * @param allBookings
 */
WeekView.prototype.buildDates = function (allBookings) {
  var t=this;
  t.datesIndex=new Object();
  if (allBookings!=null) {
    each(allBookings, function(k,a) {
      if (a!=null) {
        each(churchcore_getAllDatesWithRepeats(a), function(k,ds) {

          // while-Schleife, da es ein Termin �ber mehrere Tage sein kann
          var go_through_days=new Date(ds.startdate);
          while (go_through_days.toStringEn(false)<=ds.enddate.toStringEn(false)) {
            var year=t.datesIndex[go_through_days.getFullYear()];
            if (year==null) year=new Array();

            var month=year[go_through_days.getMonth()+1];
            if (month==null) month=new Array();

            var day=month[go_through_days.getDate()];
            if (day==null) day=new Array();

            ds.id=a.id;
            day.push(ds);
            month[go_through_days.getDate()]=day;
            year[go_through_days.getMonth()+1]=month;
            t.datesIndex[go_through_days.getFullYear()]=year;
            go_through_days.addDays(1);
          }

        });
      }
    });
  }
};

/**
 * Get Dates for startdate d. e can be null. Otherwise it will return all bookings between d and e
 * @param d startdate
 * @param e enddate
 */
WeekView.prototype.getIndexedBookings = function(d, e) {
  var t=this;
  if (e==null) {
    if ((t.datesIndex==null) || (t.datesIndex[d.getFullYear()]==null) ||
        (t.datesIndex[d.getFullYear()][d.getMonth()+1]==null)
        || (t.datesIndex[d.getFullYear()][d.getMonth()+1][d.getDate()]==null)
        )
      return new Array();
    return t.datesIndex[d.getFullYear()][d.getMonth()+1][d.getDate()];
  }
  else {
    var go=new Date(d.getTime());
    var arr=new Array();
    while (go.getTime()<e.getTime()) {
      if (t.datesIndex!=null && t.datesIndex[go.getFullYear()]!=null &&
          t.datesIndex[go.getFullYear()][go.getMonth()+1]!=null &&
          t.datesIndex[go.getFullYear()][go.getMonth()+1][go.getDate()]!=null)
        arr=arr.concat(t.datesIndex[go.getFullYear()][go.getMonth()+1][go.getDate()]);
      go.addDays(1);
    }
    return arr;
  }
};

/**
 *
 * @param res_id Ressource_Id
 * @param date Datum des ausgewaehlten Tages
 * @return Array mit passenden Bookings
 */
WeekView.prototype.getBookings = function(res_id, date) {
  var t = this;
  var bookings = new Array();
  var d = new Date(date.getFullYear(), date.getMonth(), date.getDate()+1);
  var tomorrow = new Date(d.getTime()-1);

  var searchString=this.getFilter("searchEntry").toUpperCase();

  each(t.getIndexedBookings(date), function(k,a) {
    var arr=$.extend({},allBookings[a.id]);
    if ((arr!=null) && ((!this.printview) || (arr.status_id==2))) {
      if (churchcore_datesInConflict(a.startdate, a.enddate, date, tomorrow)) {
        filterOk=true;

        if ((searchString!="") && (arr.text.toUpperCase().indexOf(searchString)!=0) &&
            (arr.person_name.toUpperCase().indexOf(searchString)!=0) &&
            (arr.id!=searchString)) filterOk=false;
        if ((t.filter["filterStatus"]!=null) && (arr.status_id!=t.filter["filterStatus"])) filterOk=false;

        if ((arr.resource_id==res_id) && (filterOk)) {
          arr.startdate=new Date(a.startdate);
          arr.enddate=new Date(arr.enddate);
          arr.viewing_date=date;
          bookings.push(arr);
        }
      }
    }
  });

  return bookings;
};

function orderBookings(bookings) {
  bookings.sort(function (a,b) {
    time_a=a.startdate.getHours()*60+a.startdate.getMinutes();
    time_b=b.startdate.getHours()*60+b.startdate.getMinutes();
    if (time_a>time_b) return 1;
    else if (time_a<time_b) return -1;
    else return 0;
  });
  return bookings;
}

/**
 * Rendert die Bookings als String
 * @param bookings[]
 * @return String
 */
function renderBookings(bookings) {
  txt="";
  each(bookings, function(k,a) {
    if (a.category_id!=null) {
      txt=txt+'<span title="'+_("calendar")+'" style="display:inline-block; background-color:'+masterData.category[a.category_id].color+'; margin-bottom:-2px; margin-right:4px; width:3px; height:11px"></span>';
    }

    starttxt="";
    if (a.startdate<a.viewing_date){
      starttxt="0";
    } else {
      starttxt=a.startdate.getHours();
      if (a.startdate.getMinutes()>0)
        starttxt=starttxt+":"+a.startdate.getMinutes();
    }
    endtxt="";
    tomorrow = new Date(a.viewing_date.getFullYear(), a.viewing_date.getMonth(), a.viewing_date.getDate()+1);
    if ((a.enddate>=tomorrow) || ((a.enddate.getHours()==0) && (a.enddate.getMinutes()==0))) {
      endtxt=24;
    }
    else {
      endtxt=a.enddate.getHours();
      if (a.enddate.getMinutes()>0) endtxt=endtxt+":"+a.enddate.getMinutes();
    }

    if (a.status_id==1) color="color:red";
    else if (a.status_id==3) color="color:gray;text-decoration:line-through;";
    else if (a.status_id==99) {
      if (((masterData.auth.write) && (a.person_id==masterData.user_pid)) || ((user_access("edit",a.resource_id))))
        color="color:lightgray;text-decoration:line-through;";
      else color="";
    }
    else color="color:black";
      text=a.text;
      if (a.location)
        text=text+" ("+a.location+")";
    if (color!="") {
      if ((!this.printview) &&
           ((masterData.auth.write) && (a.person_id==masterData.user_pid)) || ((user_access("edit", a.resource_id))))
        txt=txt+"<a href=\"#"+a.viewing_date.toStringEn()+"\" class=\"tooltips\" id=\"edit"+a.id+"\" data-tooltip-id=\""+a.id+"\" style=\"font-weight:normal;"+color+"\">"+starttxt+"-"+endtxt+"h "+text.trim(17)+"</a>";
      else
        txt=txt+"<span style=\"cursor:default;font-weight:normal;"+color+"\" class=\"tooltips\" data-tooltip-id=\""+a.id+"\">"+starttxt+"-"+endtxt+"h "+text.trim(17)+"</span>";
      if (a.repeat_id>0) txt=txt+"&nbsp;"+weekView.renderImage("recurring",12);
      txt=txt+"<br/>";
    }
  });
  return txt;
}

WeekView.prototype.updateBookingStatus = function(id, new_status) {
  var t=this;
  var oldStatus=allBookings[id].status_id;
  allBookings[id].status_id=new_status;
  allBookings[id].func="updateBooking";
  churchInterface.jsendWrite(allBookings[id], function(ok, data) {
    if (!ok) allBookings[id].status_id=oldStatus;
    else {
      // Get IDs for currently created Exceptions
      if (data.exceptions!=null) {
        each(data.exceptions, function(i,e) {
          if (i<0) {
            allBookings[id].exceptions[e]=allBookings[id].exceptions[i];
            allBookings[id].exceptions[e].id=e;
            delete allBookings[id].exceptions[i];
          }
        });
      }
    }
    t.renderList();
  }, false, false);
  t.renderList();
};


WeekView.prototype.groupingFunction = function (event) {
  return masterData.resourceTypes[event.resourcetype_id].bezeichnung;
};

WeekView.prototype.getCountCols = function() {
  return 9;
};

WeekView.prototype.renderListEntry = function (a) {
  var rows = new Array();
  rows.push("<td><p><b>"+a.bezeichnung+"</b>");
  if (a.location!=null)
    rows.push("<br/><small><font color=\"grey\">"+a.location+"</color></small>");

  var d=new Date(this.currentDate);
  var diff=-d.getDay()+1;
  if (diff==1) diff=-6;

  d.addDays(diff);
  d = new Date(d.getFullYear(), d.getMonth(), d.getDate()-1);

  for (var i=0;i<7;i++) {
    d.addDays(1);
    var _class="";
    if (d.toStringDe()==t.currentDate.toStringDe()) _class="active"

    rows.push("<td valign=\"top\" class=\"hoveractor "+_class+"\"><p><small>");
    bookings=this.getBookings(a.id, d);
    bookings=orderBookings(bookings);
    rows.push(renderBookings(bookings));

    rows.push("</small>");
    if ((masterData.auth.write || (masterData.auth.virtual && a.virtual_yn==1)) 
        && (!this.printview))
      rows.push("<a href=\"#"+d.toStringEn()+"\" id=\"new_"+a.id+"\">"+form_renderImage({src:"plus.png", width:16, hover:true})+"</a>");
  }
  return rows.join("");
};

function createNewBooking(res_id, date) {
  var a = getCRBooking();
  a.resource_id=res_id;
  if (date==null) a.startdate = new Date();
  else a.startdate = new Date(date.getTime());
  a.startdate.setHours(12);
  a.enddate=new Date(a.startdate.getTime());
  a.enddate.setHours(13);
  a.enddate.setMinutes(0);
  a.person_id=masterData.user_pid;
  a.person_name=masterData.user_name;
  a.repeat_id=0;
  a.repeat_frequence=1;
  var d_until=new Date();
  d_until.addDays(7);
  a.repeat_until=d_until;
  if ((res_id==null) || (masterData.resources[res_id].autoaccept_yn==0))
    a.status_id=1;
  else
    a.status_id=2;
  a.text="";
  a.neu=true;
  return a;
}


/**
 *
 * @param a Booking entry
 * @return String of html-code
 */
WeekView.prototype.renderEditBookingFields = function (a) {
  var rows = new Array();

  rows.push('<div id="cr_fields" data-id="'+a.id+'">');
  rows.push('<br/><form class="form-horizontal" >');

  rows.push(form_renderInput({
    cssid:"text",
    label:_("caption"),
    value:a.text,
    maxlength:30,
    disabled:a.cc_cal_id!=null
  }));

  rows.push(form_renderInput({
    cssid:"location",
    label:_("note"),
    maxlength:20,
    value:a.location
  }));

  rows.push(form_renderSelect({
    data:masterData.resources,
    cssid:"InputRessource",
    label:_("resources"),
    htmlclass:"input-medium",
    selected:a.resource_id,
    func: function(a) { 
            return (masterData.auth.create || (masterData.auth.virtual && a.virtual_yn==1)); 
          }
  }));

  rows.push('<div id="dates"></div>');
  rows.push('<div id="wiederholungen"></div>');
  rows.push('<div id="conflicts"></div>');

  rows.push(form_renderSelect({
    data:masterData.status,
    cssid:"InputStatus",
    label:_("booking.status"),
    selected:a.status_id,
    disabled:!user_access("edit", a.resource_id)
  }));

  rows.push(form_renderTextarea({
    data:a.note,
    label:_("more.information"),
    cssid:"inputNote",
    rows:2,
    cols:150
  }));

  if (user_access("assistance mode") && (a.neu || a.person_id==masterData.user_pid)) {
    rows.push(form_renderInput({
      cssid:"assistance_user",
      label:_("by.order.of")
    }));
  }


  rows.push("</form>");

  if (a.id!=null)
    rows.push("<i>"+_("booking.request.x.was.created.by.y", a.id, a.person_name)+"</a></i><br/>");

  rows.push("</div>");
  return rows.join("");
};

WeekView.prototype.implantEditBookingCallbacks = function(divid, a) {
  var t=this;

  function _setStatus() {
    var id=$("#InputRessource").val();
    if ((id!=null) && (masterData.resources[id]!=null)) {
      if ((masterData.resources[id].autoaccept_yn==0) && (!user_access("edit", id)))
        $("#"+divid+" select[id=InputStatus]").val(1);
      else if (masterData.resources[id].autoaccept_yn==1)
        $("#"+divid+" select[id=InputStatus]").val(2);
      $("#"+divid+" select[id=InputStatus]").prop('disabled', !user_access("edit", id));
    }
  }

  _setStatus();
  $("#"+divid+" select").change(function (c) {
    if ($(this).attr("id")=="InputRessource") {
      _setStatus();
    }
    else {
      t.checkConflicts();
    }
  });
  $("#"+divid+" input").keyup(function(c) {
    t.checkConflicts();
  });
  $("#"+divid+" input").click(function(c) {
    t.checkConflicts();
  });
};

WeekView.prototype.checkConflicts = function(withoutEvent) {
  var t=this;
  var id=$("#cr_fields").attr("data-id");
  var new_b=getCRBooking(allBookings[id]);
  form_getDatesInToObject(new_b);
  var resource_id=$("#InputRessource").val();

  // Erst mal baue ich alle Dat�mer auf mit der neuen Anfrage
  var new_booking_dates=new Array();
  var diff=new_b.enddate.getTime()-new_b.startdate.getTime();

  var txt=t.calcConflicts(new_b, resource_id, withoutEvent);

  if (txt!="") {
    $("#conflicts").html('<p class="text-error">'+_("caution.conflicting.dates")+': <p class="text-error"><div id="show_conflicts"><ul>'+txt+'</div>');
    $("#conflicts").addClass("well");
  }
  else {
    $("#conflicts").html("");
    $("#conflicts").removeClass("well");
  }
};

WeekView.prototype.renderTooltip = function(id) {
  var a=allBookings[id];
  txt="";
  txt=txt+"<table style=\"min-width:220px;max-width:300px\" class=\"table table-condensed\">";
  if (a.category_id!=null) {
    txt=txt+"<tr><td>Kalender<td>";
    txt=txt+'<span style="display:inline-block; background-color:'+masterData.category[a.category_id].color+'; margin-bottom:-2px; margin-right:4px; width:3px; height:11px"></span>';
    txt=txt+"<b>"+churchcore_getCaption("category", a.category_id)+"</b>";
  }
  txt=txt+"<tr><td>"+_("start.date")+"<td>"+a.startdate.toStringDe(true);
  txt=txt+"<tr><td>"+_("end.date")+"<td>"+a.enddate.toStringDe(true);
  if (a.location!="")
    txt=txt+"<tr><td>"+_("note")+"<td>"+a.location;
  if (a.repeat_id!=0) {
    txt=txt+"<tr><td>"+_("repeats")+"<td>";
    if (a.repeat_frequence>1)
      txt=txt+a.repeat_frequence+" ";
    txt=txt+(masterData.repeat[a.repeat_id]!=null?masterData.repeat[a.repeat_id].bezeichnung:"id:"+a.repeat_id);
    if (a.repeat_id!=999)
      txt=txt+"<br/>bis "+a.repeat_until.toStringDe();
  }
  else
    txt=txt+"<tr><td>"+_("repeats")+"<td>-";
  txt=txt+"<tr><td>"+_("booking.status")+"<td><b>"+masterData.status[a.status_id].bezeichnung+"</b>";
  txt=txt+"<tr><td>"+_("creator")+"<td>"+a.person_name;
  if (a.note!="") {
    txt=txt+"<tr><td>"+_("note")+"<td>"+a.note;
  }
  txt=txt+"</table>";
  title=a.text;
  if ((user_access("edit", a.resource_id)) || ((masterData.auth.write) && (a.person_id==masterData.user_pid))) {
    if ((a.status_id==1) || (a.status_id==2) || (a.status_id==3)) {
      title=title+'<span class="pull-right">';
      if ((a.status_id==1) && (user_access("edit", a.resource_id))) {
        title=title+form_renderImage({label:_("confirm"), cssid:"confirm", src:"check-64.png", width:20})+"&nbsp; ";
        title=title+form_renderImage({label:_("deny"), cssid:"deny", src:"delete_2.png", width:20})+"&nbsp; ";
      }
      if (a.status_id!=3 && a.cc_cal_id==null)
        title=title+form_renderImage({label:_("copy"), cssid:"copy", src:"copy.png", width:20})+"&nbsp; ";
      if (a.status_id!=1)
        title=title+form_renderImage({label:_("delete"), cssid:"delete", src:"trashbox.png", width:20})+"&nbsp; ";
      title=title+'</span>';
    }
  }
  return [txt, title];
};

WeekView.prototype.calcConflicts = function(new_b, resource_id, withoutEvent) {
  var t=this;
  var rows=Array();
  each(churchcore_getAllDatesWithRepeats(new_b), function(k,ds) {
    var e = new Date(ds.enddate.getTime()); e.addDays(1); // Add 1 day of full day dates
    each(t.getIndexedBookings(ds.startdate, e), function(i,conflict) {
      var booking=allBookings[conflict.id];
      if ((booking!=null) && (booking.resource_id==resource_id) 
           && (new_b.id!=booking.id) 
           && (!booking.isEqual(withoutEvent))
           && (masterData.resources[resource_id].virtual_yn==0)) {
        if ((booking.status_id==1) || (booking.status_id==2)) {
          if (churchcore_datesInConflict(ds.startdate, ds.enddate, conflict.startdate, conflict.enddate)) {
            if (conflict.startdate.sameDay(conflict.enddate))
              rows.push("<li>"+conflict.startdate.toStringDe(true)+' - '+conflict.enddate.toStringDeTime()+': '+booking.text);
            else
              rows.push("<li>"+conflict.startdate.toStringDe(true)+' - '+conflict.enddate.toStringDe(true)+': '+booking.text);
          }
        }
      }
    });
  });
  return rows.join("");
};

WeekView.prototype.addWeekButtons = function() {
  if ($("#btn_prevweek").length==0) {
    $("#dp_currentdate div.ui-datepicker-buttonpane").
    prepend('<button type="button" id="btn_prevweek" '+
   'class="ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all"><<</button>').
    append('<button type="button" id="btn_nextweek" '+
    'class="ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all">>></button>');
    $("#btn_prevweek").click(function() {
      t.currentDate.addDays(-7);
      t.renderList();
      $("#dp_currentdate").datepicker("setDate", t.currentDate);
    });
    $("#btn_nextweek").click(function() {
      t.currentDate.addDays(7);
      t.renderList();
      $("#dp_currentdate").datepicker("setDate", t.currentDate);
    });
  }
};

WeekView.prototype.prepareSaveBookingDetail = function (elem) {
  var t=this;
  var a=t.currentBooking;
  a.repeat_frequence=1;
  form_getDatesInToObject(a);
  if (a.enddate<a.startdate) {
    alert("Das Enddatum liegt vor dem Startdatum, bitte korrigieren!");
    return false;
  }
  if ($("#assistance_user").val()!=null && $("#assistance_user").val()!="") {
    if ($("#assistance_user").attr("disabled")==null) {
      if (user_access("create person")) {
        if (confirm("Person "+$("#assistance_user").val()+" nicht gefunden, soll ich sie anlegen?")) {
          form_renderCreatePerson($("#assistance_user").val(), function(personId, personName) {
            t.currentBooking.person_id=personId;
            t.currentBooking.person_name=personName;
            $("#assistance_user").val(t.currentBooking.person_name);
            $("#assistance_user").attr("disabled", true);
          });
        }
      }
      else
        alert("Die Person "+$("#assistance_user").val()+" wurde nicht gefunden!");
      return false;
    }
  }

  a.resource_id=$("select[id=InputRessource]").val();
  a.status_id=$("select[id=InputStatus]").val();
  a.text=$("input[id=text]").val().trim();
  a.location=$("input[id=location]").val().trim();
  a.show_in_churchcal_yn=($("input[id=showinchurchcal]").attr("checked")=="checked"?1:0);
  a.note=$("#inputNote").val().trim();
  if ($("#assistance_user").attr("data-id") != null) {
    a.person_id=$("#assistance_user").attr("data-id");
    a.person_name=$("#assistance_user").attr("data-name");
  }
  console.log(a);

  if ($("#show_conflicts").html()!=null)
    a.conflicts=$("#show_conflicts").html();

  a.neu=false;
  if (a.text=="") {
    alert("Bitte eine Bezeichnung angeben!");
    return false;
  }

  $("#cr_fields").html("<br/>Daten werden gespeichert..<br/><br/>");

  return true;
  //t.saveBooking(a);
  //elem.empty().remove();
};

WeekView.prototype.saveBooking = function(a) {
  if (a.cc_cal_id==null) {
    delete(a.cc_cal_id);
    a.type="CR";
  }
  else {
    a.type="CAL";
  }
  churchInterface.jsendWrite(a, function(ok, json) {
    if (ok) {
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
    else alert("Fehler beim Speichern: "+json);
  }, false, false);
};

WeekView.prototype.addFurtherListCallbacks = function() {
  var t=this;

  t.addWeekButtons();

  $("#cdb_content .tooltips").each(function() {
    var tooltip=$(this);
    tooltip.tooltips({
      data:{id:$(this).attr("data-tooltip-id")},
      minwidth:250,
      render:function(data) {
        $("#mypop").remove();
        return t.renderTooltip(data.id);
      },

      afterRender: function(element, data) {
        currentTooltip=$(tooltip);
        element.find("#copy").click(function() {
          clearTooltip();
          t.showBookingDetails("copy", data.id);
          return false;
        });
        element.find("#delete").click(function() {
          if (allBookings[data.id].repeat_id==0) {
            if (confirm("Wirklich bei der Buchung den Status auf 'zu löschen' setzen?"))
              t.updateBookingStatus(data.id, 99);
          }
          else {
            clearTooltip(true);
            var txt="Es handelt sich um eine Buchung mit Wiederholungen, welche Buchungen sollen entfernt werden?"
            var elem=form_showDialog("Was soll gelöscht werden?", txt, 300, 300, {
              "Alle": function() {
                        t.updateBookingStatus(data.id, 99);
                        elem.dialog("close");
                      },
              "Nur aktuelle": function() {
                                var date=tooltip.attr("href").substr(1,99);
                                t.addException(allBookings[data.id], date.toDateEn(false));
                                t.buildDates(allBookings);
                                t.updateBookingStatus(data.id, allBookings[data.id].status_id);
                                elem.dialog("close");
                              },
              "Abbrechen": function() { elem.dialog("close"); }
            });
          }
          return false;
        });
        element.find("#confirm").click(function() {
          t.updateBookingStatus(data.id, 2);
          return false;
        });
        element.find("#deny").click(function() {
          t.updateBookingStatus(data.id, 3);
          return false;
        });

      }
    });
  });

  $("#cdb_content a").click(function(c) {
    // id ist bei Create=Resource_id, bei Edit ist es booking_id
    if ($(this).attr("id")!=null) {
      id=$(this).attr("id").substr(4,99);
      date=$(this).attr("href").substr(1,99);
      clearTooltip();

      if ($(this).attr("id").indexOf("edit")==0)
        t.showBookingDetails("edit", id, date.toDateEn(false), $(this));
      else
        if ($(this).attr("id").indexOf("new_")==0)
          t.showBookingDetails("new", id, date.toDateEn(false));
    }
  });
  $( "#cdb_content" ).mousemove(function( event ) {
    t.mousePosition={ clientX : event.pageX, clientY : event.pageY };
  });
};

WeekView.prototype.addException = function(booking, date) {
  if (booking.exceptions==null) booking.exceptions=new Object();
  if (booking.exceptionids==null) booking.exceptionids=0;
  booking.exceptionids=booking.exceptionids-1;
  booking.exceptions[booking.exceptionids]
        ={id:booking.exceptionids, except_date_start:date.toStringEn(), except_date_end:date.toStringEn()};
  return booking;
};

/**
 * func=new oder edit or copy
 * id=bei new=> res_id, bei edit: booking_id
 * @param Date date bei new=>date, sonst egal
 */
WeekView.prototype.showBookingDetails = function(func, id, date, element) {
  var t = this;
  var myEvent = null;

  if (func == "new") myEvent = createNewBooking(id, date);
  else myEvent = allBookings[id].clone();
  if (myEvent.cc_cal_id!=null) myEvent = CR2CALType(myEvent);

  // D.h. entweder Erstelle oder Editiere
  myEvent.askForSplit(t.mousePosition, function(untilEnd) {
    if (untilEnd!=null) {
      myEvent.doSplit(date, untilEnd, function(newEvent, pastEvent) {
        t.renderEditEvent(func, newEvent, myEvent, myEvent.isSeries(), untilEnd, function(newEvent, func) {
          if (myEvent.cc_cal_id==null) {
            newEvent.save();
            if (pastEvent!=null) pastEvent.save(); // Only for real splits
            func(true);
          }
          else {
            if (newEvent.id==null) newEvent.booking_id = -1;
            else newEvent.id = newEvent.cc_cal_id;
            var b = churchcore_getFirstElement(newEvent.bookings);
            var minpre = b.minpre;
            var minpost = b.minpost;
            newEvent.bookings[newEvent.booking_id] =  {
              id: newEvent.booking_id,
              status_id : newEvent.status_id,
              resource_id : newEvent.resource_id,
              location: newEvent.location,
              note: newEvent.note,
              minpre : minpre,
              minpost : minpost
            };
            // Now it is time to undo the pre and post
            newEvent.startdate = new Date(newEvent.startdate.getTime() + minpre * 60000);
            newEvent.enddate = new Date(newEvent.enddate.getTime() - minpost * 60000);
            if (pastEvent != null && newEvent.id == null) {
              pastEvent.startdate = new Date(pastEvent.startdate.getTime() + minpre * 60000);
              pastEvent.enddate = new Date(pastEvent.enddate.getTime() - minpost * 60000);
              pastEvent.id = pastEvent.cc_cal_id;
              delete newEvent.bookings[pastEvent.booking_id];
            }
            myEvent.saveSplitted(newEvent, pastEvent, date, untilEnd);
            func(true);
          }
        });
      });
    }
  });
};

function CAL2CRType(event) {
  if (event == null) return null;
  var cr = getCRBooking(event);
  var b = churchcore_getFirstElement(event.bookings);
  each(b, function(k,a) { cr[k] = a; });
  cr.startdate = new Date(event.startdate.getTime() - cr.minpre * 60000);
  cr.enddate = new Date(event.enddate.getTime() + cr.minpost * 60000);
  cr.text = event.bezeichnung;
  cr.cc_cal_id = event.id;
  delete cr.bookings;

  return cr;
}

function CR2CALType(event) {
  if (event == null) return null;
  var cal = getCRBooking(event);
  cal.name = "Event";
  cal.booking_id = event.id;
  cal.bookings = new Object();
  var minpre =  (event.cal_startdate.getTime() - event.startdate.getTime()) / 60000;
  var minpost = (event.enddate.getTime() - event.cal_enddate.getTime()) / 60000;
  // Add pre and post cause of the display in edit view
  cal.startdate = event.startdate;
  cal.enddate = event.enddate;
  cal.bezeichnung = event.text;
  cal.category_id = event.category_id;
  cal.bookings[event.id] = {
    status_id : event.status_id,
    location: event.location,
    note: event.note,
    resource_id : event.resource_id,
    minpre : minpre,
    minpost : minpost
  }
  return cal;
}

WeekView.prototype.renderEditEvent = function(func, newEvent, myEvent, _isSeries, untilEnd, resFunc) {
  t.currentBooking = newEvent;
  var title="";
  var txt="";
  if (func=="edit") {
    if (myEvent.cc_cal_id!=null) {
      txt=txt+'<div class="alert alert-error">Achtung: Die Buchung wurde in <a href="?q=churchcal&date='+myEvent.startdate.toStringEn(false)+'">'+masterData.churchcal_name+'</a> erstellt. Datum und Bezeichnung bitte im Kalendereintrag bearbeiten. Die Raumbuchung passt sich dementsprechend an.<br>Die Buchung kann aber hier vom Status angepasst werden und eine Resource getauscht werden.</div>';
    }
    // Pruefen, ob der Eintrag schon von einem Admin bestaetigt wurde, dann muss er auf 1 (unbestaetigt) zurueckgesetzt werden
    if (((!user_access("edit", myEvent.resource_id)) && (masterData.auth.write) && (myEvent.person_id==masterData.user_pid) &&
        (myEvent.status_id!=1) && (masterData.resources[myEvent.resource_id].autoaccept_yn==0))) {
      txt=txt+"<i><div class=\"alert alert-error\"><b>Achtung: Die Anfrage wurde schon vom Administrator bearbeitet!</b><br/>Wenn nun 'Speichern' gew&auml;hlt, muss erneut der Administrator best&auml;tigen.</i></div>";
      myEvent.status_id=1;
    }
    if (((masterData.auth.write) && (myEvent.person_id==masterData.user_pid)) || ((user_access("edit", myEvent.resource_id))))
      title="Editiere Buchungsanfrage";
    else
      title="Anzeige der Buchungsanfrage";
    myEvent["func"]="updateBooking";
    if (myEvent.exceptionids==null) myEvent.exceptionids=0;
  }
  else if (func=="new") {
    title="Buchungsanfrage erstellen";
    myEvent["func"]="createBooking";
    myEvent.exceptionids=0;
  }
  else if (func=="copy") {
    t.id=null;
    title="Buchungsanfrage kopieren";
    myEvent["func"]="createBooking";
    myEvent.exceptionids=0;
  }
  else alert("unkown function in showBookingDetails");
  txt=txt+t.renderEditBookingFields(newEvent);
  if (func=="edit") txt=txt+'<div id="cr_logs" style=""></div>';
  var elem = t.showDialog(title, txt, 600, 600, {});

  if (user_access("edit", newEvent.resource_id)) {
    $("#dates").renderCCEvent({event: newEvent, disabled: myEvent.cc_cal_id!=null,
       authexceptions: user_access("edit", myEvent.resource_id),
       authadditions: user_access("edit", myEvent.resource_id),
       callback: function(){
         t.implantEditBookingCallbacks("cr_fields", myEvent);
       }
    });
  }
  else
   $("#dates").renderCCEvent({
     event: myEvent,
     callback: function() {
       t.implantEditBookingCallbacks("cr_fields", myEvent);
     }
   });

  form_autocompletePersonSelect("#assistance_user", false, function(divid, ui) {
    $("#assistance_user").val(ui.item.label);
    $("#assistance_user").attr("disabled",true);
    $("#assistance_user").attr("data-id", ui.item.value);
    $("#assistance_user").attr("data-name", ui.item.label);
    myEvent.person_id=ui.item.value;
    myEvent.person_name=ui.item.label;
    return false;
  });

  t.checkConflicts(myEvent);

  var log=$("#cr_logs");
  if (log!=null && myEvent.id!=null) {
    // Hole die Log-Daten
    churchInterface.jsendRead({ func: "getLogs", id:myEvent.id }, function(ok, json) {
      if (json!=null) {
        logs='<small><font style="line-height:100%;"><a href="#" id="toogleLogs">Historie >></a><br/></small>';
        logs=logs+'<div id="cr_logs_detail" style="display: none; border: 1px solid white; height: 140px; overflow: auto; margin: 2px; padding: 2px;">';
        logs=logs+"<small><table><tr><td>Historie<td>Beschreibung<td>Erfolgt durch";
        each(json, function(k,a){
          logs=logs+'<tr><td width="100px">'+a.datum.toDateEn().toStringDe(true)+"<td>"+a.txt+'<td width="80px">'+a.person_name+" ["+a.person_id+"]<br/>";
        });
        logs=logs+"</table>";
        logs=logs+"</small>";
        if (user_access("edit", myEvent.resource_id))
          logs=logs+"<small><p align=\"right\"><a href=\"#\" id=\"del_complete\"><i>(Admin: Gesamten Termin l&ouml;schen)</a></small>";
        logs=logs+"</div>";
        log.html(logs);
        $("#cr_logs a").click(function(c) {
          if (($(this).attr("id")=="del_complete") && (user_access("edit", myEvent.resource_id))){
            if (confirm("Soll der Termin wirklich entfernt werden? Achtung, man kann es nicht mehr wiederherstellen!")) {
              churchInterface.jsendWrite({func: "delBooking", id:id}, function(ok, json) {
                allBookings[id]=null;
                $("#cr_cover").html("");
                t.renderList();
                elem.dialog("close");
              });
            }
          }
          else {
            $("#cr_logs_detail").animate({ height: 'toggle'}, "fast", function() {
            });
            elem.animate({scrollTop:elem.scrollTop()+160}, 500, 'swing');
          }
          return false;
        });
      }
    });
  }

  if (func=="delete") {
    t.prepareSaveBookingDetail(elem);
    return;
  }

  // Keine Edit-Funktion, wenn sie vom churchCal kommt
  if (((masterData.auth.write) && (myEvent.person_id==masterData.user_pid)) || (user_access("edit", myEvent.resource_id)) || (myEvent.neu)) {
    elem.dialog('addbutton', _("save"), function() {
      t.currentBooking = $("#dates").renderCCEvent("getCCEvent");
      if (t.prepareSaveBookingDetail(elem))
        resFunc(t.currentBooking, function(ok) {if (ok) elem.dialog("close");});

    });

    if ((myEvent.status_id!=99) && (!myEvent.neu) && (date!=null)) {
      if (_isSeries && !untilEnd) {
        elem.dialog('addbutton', 'Einzeltermin entfernen', function() {
          if (myEvent.exceptions==null) myEvent.exceptions=new Object();
          myEvent.exceptionids=myEvent.exceptionids-1;
          myEvent.exceptions[myEvent.exceptionids]
                ={id:myEvent.exceptionids, except_date_start:date, except_date_end:date};
          t.prepareSaveBookingDetail(elem);
        });
      }
      else {
        if (_isSeries) {
          if (myEvent.repeat_id!=999 && myEvent.cc_cal_id==null)
            elem.dialog('addbutton', 'Diesen und nachfolgende löschen', function() {
              d=date.toDateEn();
              d.addDays(-1);
              $("#cr_fields input[id=inputRepeatUntil]").val(d.toStringDe());
              t.prepareSaveBookingDetail(elem);
            });
        }
        title="Löschen";
        if (_isSeries) title="Gesamte Serie löschen";
        elem.dialog('addbutton', title, function() {
          $("select[id=InputStatus]").val(99);
          t.prepareSaveBookingDetail(elem);
        });
      }
    }
  }
  elem.dialog('addbutton', _("cancel"), function() {elem.dialog("close");});
};

WeekView.prototype.renderEntryDetail = function() {
};
