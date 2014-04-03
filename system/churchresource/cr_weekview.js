(function($) {
	  
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
}

Temp.prototype = StandardTableView.prototype;
WeekView.prototype = new Temp();
weekView = new WeekView();

WeekView.prototype.getData = function(sorted) {
  if (sorted) {
    var arr=new Array();
    $.each(masterData.resources,function(k,a){
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

  menu = new CC_Menu("Men&uuml;");
  if (!this.printview) {
    if (masterData.auth.write) 
      menu.addEntry("Neue Anfrage erstellen", "anewentry", "star");
      
    menu.addEntry("Druckansicht", "adruckansicht", "print");   
  }

  if (masterData.auth.admin) 
    menu.addEntry("Stammdatenpflege", "amaintainview", "cog");
  
  menu.addEntry("Hilfe", "ahelp", "question-sign");


  if (!menu.renderDiv("cdb_menu", churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {    
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        t.showBookingDetails("new");
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
        fenster = window.open('?q=churchresource/printview&curdate='+t.currentDate.toStringEn(), "Druckansicht", "width=900,height=600,resizable=yes,scrollbars=1");
        fenster.focus();
        return false;
      }
      else if ($(this).attr("id")=="amain") {
        menuDepth="amain";
        t.renderMenu();
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchResource");
      }
      return false;
    });
  }
};

WeekView.prototype.renderCreatePerson = function(value) {
  var form = new CC_Form();
  form.addHidden({cssid:"func", value:"createAddress"});
  form.addInput({label:"Vorname", cssid:"vorname", required:true, value:(value.indexOf(" ")>=0?value.substr(0,value.indexOf(" ")):"")});
  form.addInput({label:"Nachname", cssid:"name", required:true, value:(value.indexOf(" ")>=0?value.substr(value.indexOf(" ")+1,99):"")});
  form.addInput({label:"E-Mail", cssid:"email", value:(value.indexOf("@")>=0?value:"")});
  form.addSelect({label:"Bereich", cssid:"Inputf_dep", htmlclass:"setting", data:churchcore_sortMasterData(masterData.cdb_bereich), selected:masterData.settings.bereich_id});
  form.addSelect({label:"Status", cssid:"Inputf_status", htmlclass:"setting", data:churchcore_sortMasterData(masterData.cdb_status), selected:masterData.settings.status_id});
  form.addSelect({label:"Station", cssid:"Inputf_station", htmlclass:"setting", data:churchcore_sortMasterData(masterData.cdb_station), selected:masterData.settings.station_id});
  
  var elem=form_showDialog("Neue Person hinzufügen", form.render(null, "horizontal"), 500, 400, {
    "Hinzufügen": function() {
      var obj=form.getAllValsAsObject();
      if (obj!=null) {
        if ((obj.vorname=="") || (obj.name=="")) {
          alert("Bitte Vorname und Name angeben!");
          return;
        }
        churchInterface.jsendWrite(obj, function(ok,data) {
          if (ok) {
            t.currentBooking.person_id=data.id;
            t.currentBooking.person_name=obj.vorname+" "+obj.name;
            $("#assistance_user").val(t.currentBooking.person_name);
            $("#assistance_user").attr("disabled", true);
            
            elem.dialog("close");
          }
          else {
            alert(data);
          }
        }, null, false, "churchdb");
      }
    },
    "Abbruch": function() {
      $(this).dialog("close");
    }
  });    
  elem.find("select.setting").change(function(k) {
    masterData.settings[$(this).attr("id")]=$(this).val();
    churchInterface.jsendWrite({func:"saveSetting", sub:$(this).attr("id"), val:$(this).val()}, null, null, false);    
  });  
};

WeekView.prototype.renderListMenu = function() {
  var t=this;
  
  searchEntry=this.getFilter("searchEntry");
  var navi = new CC_Navi();
  $.each(masterData.resourceTypes, function(k,a) {
    navi.addEntry(t.filter["filterRessourcen-Typ"]==a.id,"ressourcentyp_"+a.id,a.bezeichnung);
  });
  navi.addEntry(t.filter["filterRessourcen-Typ"]=="","","<i>Alle</i>");
  //navi.addEntry(true,"id1","Listenansicht");
  navi.addSearch(searchEntry);
  navi.renderDiv("cdb_search", churchcore_handyformat());
  
  this.implantStandardFilterCallbacks(this, "cdb_search");  
  
  $("#cdb_search a").click(function () {
    t.filter["filterRessourcen-Typ"]=$(this).attr("id").substr(14,99);
    masterData.settings.filterRessourcentyp=t.getFilter("filterRessourcen-Typ");
    churchInterface.jsendWrite({func:"saveSetting", sub:"filterRessourcentyp", 
               val:t.getFilter("filterRessourcen-Typ")});
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
                  label:"Status",
                  selected:this.filter["filterStatus"],
                  cssid:"filterStatus",
                  freeoption:true,
                  type:"medium"
  });

  rows.push(form.render(true));

         
  rows.push("<div id=\"cdb_filtercover\"></div>");
 
  $("#cdb_filter").html(rows.join("")); 
  
  $.each(this.filter, function(k,a) {
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
    monthNames: monthNames, 
    currentText: "Heute",
    defaultDate: t.currentDate,
    firstDay: 1,
    onSelect : function(dateText, inst) { 
      t.currentDate=dateText.toDateDe();
      //t.currentDate.addDays(-1);
      t.renderList();
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
        },150);
      }
    }
  });    
  $("#dp_currentdate").datepicker($.datepicker.regional['de']);
 // $("#dp_currentdate").datepicker('setDate', t.currentDate.toStringDe());
};
  
WeekView.prototype.checkFilter = function (a) {
  var filter=this.filter;
  var t=this;
  // eintrag wurde geloescht o.ae.
  if (a==null) return false;
  
  // Suchfeld wird nicht gesucht, denn das passiert dann bei den einzelnen Eintr�gen
//  var searchString=this.getFilter("searchEntry").toUpperCase();
  
//  if ((searchString!="") && (a.bezeichnung.toUpperCase().indexOf(searchString)!=0) &&
//             (a.id!=searchString)) return false;

  if ((filter["filterRessourcen-Typ"]!=null) && (filter["filterRessourcen-Typ"]!="") && (a.resourcetype_id!=filter["filterRessourcen-Typ"])) 
    return false;

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
        $.each(args, function(k,a) {
          if (a.id!=null) {
            refresh=true;
          }
        });
      if (refresh) {
        var elem = form_showCancelDialog("Achtung...", "Lade aktuelle Daten nach!");
        
        cr_loadBookings(function() {
          elem.dialog("close");
          t.renderList();
        });
      }
    }
    else
      alert("Message "+message+" unbekannt!");
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


WeekView.prototype.buildDates = function (allBookings) {
  var t=this;
  t.datesIndex=new Object();
  if (allBookings!=null) {
    $.each(allBookings, function(k,a) {
      if (a!=null) {
        $.each(churchcore_getAllDatesWithRepeats(a), function(k,ds) {
          
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
      if (t.datesIndex[go.getFullYear()]!=null && 
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
  
  $.each(t.getIndexedBookings(date), function(k,a) {
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
  $.each(bookings, function(k,a) {
    if (a.category_id!=null) {
      txt=txt+'<span title="Kalender: Hauptgottesdienst" style="display:inline-block; background-color:'+masterData.category[a.category_id].color+'; margin-bottom:-2px; margin-right:4px; width:3px; height:11px"></span>';
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
      if (((masterData.auth.write) && (a.person_id==masterData.user_pid)) || ((masterData.auth.editall)))
        color="color:lightgray;text-decoration:line-through;";
      else color="";
    }  
    else color="color:black";
    //  Wenn keine Farbe, dann soll es nicht angezeigt werden
//    if (a.location!="") 
//      ort=" ("+a.location+")";
 //   else
      ort="";
      text=a.text.substr(0,15);
      if (text!=a.text) text=text+"..";
    if (color!="") {
      if ((!this.printview) && 
           ((masterData.auth.write) && (a.person_id==masterData.user_pid)) || ((masterData.auth.editall)))
        txt=txt+"<a href=\"#"+a.viewing_date.toStringEn()+"\" class=\"tooltips\" id=\"edit"+a.id+"\" data-tooltip-id=\""+a.id+"\" style=\"font-weight:normal;"+color+"\">"+starttxt+"-"+endtxt+"h "+text+ort+"</a>";
      else 
        txt=txt+"<span style=\"cursor:default;font-weight:normal;"+color+"\" class=\"tooltips\" data-tooltip-id=\""+a.id+"\">"+starttxt+"-"+endtxt+"h "+text+ort+"</span>";
//      if (a.repeat_id>0) txt=txt+" {R}";
      if (a.repeat_id>0) txt=txt+" "+weekView.renderImage("recurring",12);
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
  churchInterface.jsendWrite(allBookings[id], function(ok) {
    if (!ok) allBookings[id].status_id=oldStatus;
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
  
  
  //d = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), this.currentDate.getDate());
  for (i=0;i<7;i++) {
    d.addDays(1);
    var _class="";
    if (d.toStringDe()==t.currentDate.toStringDe()) _class="active"

    rows.push("<td valign=\"top\" class=\"hoveractor "+_class+"\"><p><small>");
    bookings=this.getBookings(a.id, d);
    bookings=orderBookings(bookings);
    rows.push(renderBookings(bookings));
    
    rows.push("</small>");
    if ((masterData.auth.write) && (!this.printview))
      rows.push("<a href=\"#"+d.toStringEn()+"\" id=\"new_"+a.id+"\">"+form_renderImage({src:"plus.png", width:16, hover:true})+"</a>");
  }  
  return rows.join("");
};

function createNewBooking(res_id, date) {
  var a = new Object();
  a.resource_id=res_id;
  
  if (date==null) 
    d=new Date();
  else
    d=date.toDateEn();
  d.setHours(12);
  a.startdate=new Date(d);
  a.enddate=new Date(d);
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
    label:"Dienstbereich",
    value:a.text,
    disabled:a.cc_cal_id!=null
  }));
  
  rows.push(form_renderInput({
    cssid:"location",
    label:"Ort",
    value:a.location
  }));
  
  rows.push(form_renderSelect({
    data:masterData.resources, 
    cssid:"InputRessource", 
    label:"Ressource",
    htmlclass:"input-medium", 
    selected:a.resource_id,
    disabled:a.cc_cal_id!=null    
  }));
  
  rows.push('<div id="dates"></div>');  
  rows.push('<div id="wiederholungen"></div>');
  rows.push('<div id="conflicts"></div>');  

  rows.push(form_renderSelect({
    data:masterData.status, 
    cssid:"InputStatus", 
    label:"Status",
    selected:a.status_id,
    disabled:!masterData.auth.editall
  }));
  
//  rows.push("<tr><td>Status<td>"+
//      this.renderSelect(a.status_id, "Status", masterData.status, !masterData.auth.editall)+"<tr><td>");

  rows.push(form_renderTextarea({
    data:a.note,
    label:"Weitere Infos",
    cssid:"inputNote", 
    rows:2,
    cols:150
  }));

  if (user_access("assistance mode") && (a.neu || a.person_id==masterData.user_pid)) {
    rows.push(form_renderInput({
      cssid:"assistance_user",
      label:"Im Auftrag von"
    }));
  }
  

  rows.push("</form>");
  
  if (a.id!=null)
    rows.push("<i>Buchungsanfrage "+a.id+" wurde erstellt von "+a.person_name+"</a></i><br/>");
  
  rows.push("</div>");
  return rows.join("");
};

WeekView.prototype.implantEditBookingCallbacks = function(divid, a) {
  var t=this;
  
  function _setStatus() {
    var id=$("#InputRessource").val();
    if ((id!=null) && (masterData.resources[id]!=null)) {
      if ((masterData.resources[id].autoaccept_yn==0) && (!masterData.auth.editall)) 
        $("#"+divid+" select[id=InputStatus]").val(1);
      else if (masterData.resources[id].autoaccept_yn==1) 
        $("#"+divid+" select[id=InputStatus]").val(2);
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

WeekView.prototype.checkConflicts = function() {
  var t=this;
  var id=$("#cr_fields").attr("data-id");
  var new_b=new Object();
  if ((id!=null) && (allBookings[id]!=null))
    var new_b=$.extend({}, allBookings[id]);
  form_getDatesInToObject(new_b);
  var resource_id=$("#InputRessource").val();
  
  // Erst mal baue ich alle Dat�mer auf mit der neuen Anfrage
  var new_booking_dates=new Array();
  var diff=new_b.enddate.getTime()-new_b.startdate.getTime();

  var txt=t.calcConflicts(new_b, resource_id);
  
  if (txt!="") {
    $("#conflicts").html('<p class="text-error">Achtung, Termin-Konflikte: <p class="text-error"><div id="show_conflicts"><ul>'+txt+'</div>');
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
    txt=txt+'<span title="Kalender: Hauptgottesdienst" style="display:inline-block; background-color:'+masterData.category[a.category_id].color+'; margin-bottom:-2px; margin-right:4px; width:3px; height:11px"></span>';
    txt=txt+"<b>"+churchcore_getBezeichnung("category", a.category_id)+"</b>";    
  }
  txt=txt+"<tr><td>Start<td>"+a.startdate.toStringDe(true);
  txt=txt+"<tr><td>Ende<td>"+a.enddate.toStringDe(true);
  if (a.location!="")
    txt=txt+"<tr><td>Ort<td>"+a.location;
  if (a.repeat_id!=0) {
    txt=txt+"<tr><td>Wiederholung<td>";
    if (a.repeat_frequence>1)
      txt=txt+a.repeat_frequence+" "; 
    txt=txt+(masterData.repeat[a.repeat_id]!=null?masterData.repeat[a.repeat_id].bezeichnung:"id:"+a.repeat_id);
    if (a.repeat_id!=999)
      txt=txt+"<br/>bis "+a.repeat_until.toStringDe();
  }  
  else 
    txt=txt+"<tr><td>Wiederholung<td>-";
  txt=txt+"<tr><td>Status<td><b>"+masterData.status[a.status_id].bezeichnung+"</b>";
  txt=txt+"<tr><td>Ersteller<td>"+a.person_name;
  if (a.note!="") {
    txt=txt+"<tr><td>Notiz<td>"+a.note;
  }  
  txt=txt+"</table>";
  title=a.text;
  if ((masterData.auth.editall) || ((masterData.auth.write) && (a.person_id==masterData.user_pid))) {
    if ((a.status_id==1) || (a.status_id==2) || (a.status_id==3)) {
      title=title+'<span class="pull-right">';
      if ((a.status_id==1) && (masterData.auth.editall)) {
        title=title+form_renderImage({label:"Bestätigen", cssid:"confirm", src:"check-64.png", width:20})+"&nbsp; ";
        title=title+form_renderImage({label:"Ablehnen", cssid:"deny", src:"delete_2.png", width:20})+"&nbsp; ";
      }
      if (a.status_id!=3 && a.cc_cal_id==null) 
        title=title+form_renderImage({label:"Kopieren", cssid:"copy", src:"copy.png", width:20})+"&nbsp; ";
      if (a.status_id!=1)
        title=title+form_renderImage({label:"Löschen", cssid:"delete", src:"trashbox.png", width:20})+"&nbsp; ";
      title=title+'</span>';
    }
  }
  return [txt, title];       
};

WeekView.prototype.calcConflicts = function(new_b, resource_id) {
  var t=this;
  var rows=Array();
  $.each(churchcore_getAllDatesWithRepeats(new_b), function(k,ds) {
    $.each(t.getIndexedBookings(ds.startdate, ds.enddate), function(i,conflict) {
      var booking=allBookings[conflict.id];
      if ((booking!=null) && (booking.resource_id==resource_id) && (new_b.id!=booking.id)) {
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

WeekView.prototype.closeAndSaveBookingDetail = function (elem) {
  var t=this;
  var a=t.currentBooking;
  a.repeat_frequence=1;
  form_getDatesInToObject(a);
  if (a.enddate<a.startdate) {
    alert("Das Enddatum liegt vor dem Startdatum, bitte korrigieren!");
    return null;
  }
  if ($("#assistance_user").val()!=null && $("#assistance_user").val()!="") { 
    if ($("#assistance_user").attr("disabled")==null) {
      if (user_access("create person")) {
        if (confirm("Person "+$("#assistance_user").val()+" nicht gefunden, soll ich sie anlegen?")) {
          t.renderCreatePerson($("#assistance_user").val());
        }
      }
      else
        alert("Die Person "+$("#assistance_user").val()+" wurde nicht gefunden!");
      return null;
    }
  }

  a.resource_id=$("select[id=InputRessource]").val();
  a.status_id=$("select[id=InputStatus]").val();
  a.text=$("input[id=text]").val().trim();
  a.location=$("input[id=location]").val().trim();
  a.show_in_churchcal_yn=($("input[id=showinchurchcal]").attr("checked")=="checked"?1:0);
  a.note=$("#inputNote").val().trim();  
  if ($("#show_conflicts").html()!=null)
    a.conflicts=$("#show_conflicts").html();

  a.startdate=a.startdate.toStringEn(true);
  a.enddate=a.enddate.toStringEn(true);
  if (a.repeat_until!=null) a.repeat_until=a.repeat_until.toStringEn(false);
  a.neu=false;
  
  if (a.text=="") {
    alert("Bitte eine Bezeichnung angeben!");
    return null;
  }
  
  $("#cr_fields").html("<br/>Daten werden gespeichert..<br/><br/>");  
  
  churchInterface.jsendWrite(a, function(ok, json) {
    a.startdate=a.startdate.toDateEn();
    a.enddate=a.enddate.toDateEn();
    if (a.repeat_until!=null)
      a.repeat_until=a.repeat_until.toDateEn();
    elem.empty().remove();
    
    if (ok) {
      if (json.id!=null) { 
        a.id=json.id;
        allBookings[json.id]=a;
      }
      else if (a.id!=null)
        allBookings[a.id]=a;

      // Get IDs for currently created Exceptions
      if (json.exceptions!=null) {
        $.each(json.exceptions, function(i,e) {
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

  $("#cdb_content .tooltips").each(function() {
    var tooltip=$(this);
    tooltip.tooltips({
      data:{id:$(this).attr("data-tooltip-id")},
      minwidth:250,
      render:function(data) {
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
    id=$(this).attr("id").substr(4,99);
    date=$(this).attr("href").substr(1,99);
    clearTooltip();
    
    if ($(this).attr("id").indexOf("edit")==0)
      t.showBookingDetails("edit", id, date);
    else 
      if ($(this).attr("id").indexOf("new_")==0) 
        t.showBookingDetails("new", id, date);
  });
};

WeekView.prototype.cloneBooking = function(booking) {
  var currentBooking=$.extend({}, booking);
  currentBooking.startdate=new Date(booking.startdate);
  currentBooking.enddate=new Date(booking.enddate);
  currentBooking.repeat_until=new Date(booking.repeat_until);
  currentBooking.id=null;
  return currentBooking;
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
 * date=bei new=>date, sonst egal
 */
WeekView.prototype.showBookingDetails = function(func, id, date) {
  var t=this;
  var title="";
  var txt="";
  if (func=="edit") {
    t.currentBooking=$.extend({}, allBookings[id]);
    if (t.currentBooking.cc_cal_id!=null) {
      txt=txt+'<div class="alert alert-error">Achtung: Die Buchung wurde in <a href="?q=churchcal&date='+t.currentBooking.startdate.toStringEn(false)+'">'+masterData.churchcal_name+'</a> erstellt. Datum und Bezeichnung bitte im Kalendereintrag bearbeiten. Die Raumbuchung passt sich dementsprechend an.</div>';
    }
    // Pruefen, ob der Eintrag schon von einem Admin bestaetigt wurde, dann muss er auf 1 (unbestaetigt) zurueckgesetzt werden
    if (((!masterData.auth.editall) && (masterData.auth.write) && (t.currentBooking.person_id==masterData.user_pid) &&
        (t.currentBooking.status_id!=1) && (masterData.resources[t.currentBooking.resource_id].autoaccept_yn==0))) {
      txt=txt+"<i><div style=\"background:white\"><b>Achtung: Die Anfrage wurde schon vom Administrator bearbeitet!</b><br/>Wenn nun 'Speichern' gew&auml;hlt, muss erneut der Administrator best&auml;tigen.</i></div><br/><br/>";
      t.currentBooking.status_id=1;
    }
    txt=txt+t.renderEditBookingFields(t.currentBooking);
    txt=txt+'<div id="cr_logs" style=""></div>';
    if (((masterData.auth.write) && (t.currentBooking.person_id==masterData.user_pid)) || ((masterData.auth.editall)))
      title="Editiere Buchungsanfrage";
    else 
      title="Anzeige der Buchungsanfrage";    
    t.currentBooking["func"]="updateBooking";
    if (t.currentBooking.exceptionids==null) t.currentBooking.exceptionids=0;
  }  
  else if (func=="new") {
    t.currentBooking=createNewBooking(id, date);
    txt=t.renderEditBookingFields(t.currentBooking);
    title="Buchungsanfrage erstellen";
    t.currentBooking["func"]="createBooking";
    t.currentBooking.exceptionids=0;
  }
  else if (func=="copy") {
    t.currentBooking=t.cloneBooking(allBookings[id]);
    txt=t.renderEditBookingFields(allBookings[id]);
    title="Buchungsanfrage kopieren";
    t.currentBooking["func"]="createBooking";
    t.currentBooking.exceptionids=0;
  }
  else alert("unkown function in showBookingDetails");

  // D.h. entweder Erstelle oder Editiere    
  if (title!="") {
    var elem = this.showDialog(title, txt, 600, 600, {});
   if (masterData.auth.editall) {
     form_renderDates({elem:$("#dates"), data:t.currentBooking, disabled:t.currentBooking.cc_cal_id!=null,
       authexceptions:masterData.auth.editall,
       authadditions:masterData.auth.editall,       
       deleteException:function(exc) {
         delete t.currentBooking.exceptions[exc.id];
       },
       addException:function(options, date) {
         t.addException(t.currentBooking, date.toDateDe());
         return t.currentBooking;
       },
       deleteAddition:function(add) {
         delete t.currentBooking.additions[add.id];
       },
       addAddition:function(options, date, with_repeat_yn) {
         if (t.currentBooking.additions==null) t.currentBooking.additions=new Object();
         t.currentBooking.exceptionids=t.currentBooking.exceptionids-1;
         t.currentBooking.additions[t.currentBooking.exceptionids]
               ={id:t.currentBooking.exceptionids, add_date:date.toDateDe().toStringEn(), with_repeat_yn:with_repeat_yn};
         return t.currentBooking;
       },
       callback:function(){ 
         t.implantEditBookingCallbacks("cr_fields", allBookings[id]); 
       }
     });
   }
   else  
     form_renderDates({
       elem:$("#dates"), 
       data:t.currentBooking, 
       callback:function() { 
         t.implantEditBookingCallbacks("cr_fields", allBookings[id]); 
       }
     }); 
   
   form_autocompletePersonSelect("#assistance_user", false, function(divid, ui) {
     $("#assistance_user").val(ui.item.label);
     $("#assistance_user").attr("disabled",true);
     t.currentBooking.person_id=ui.item.value;
     t.currentBooking.person_name=ui.item.label;
     return false;
   });
    
   this.checkConflicts();
    
    var log=$("#cr_logs");
    if (log!=null && id!=null) {
      // Hole die Log-Daten
      churchInterface.jsendRead({ func: "getLogs", id:id }, function(ok, json) {
        if (json!=null) {
          logs='<small><font style="line-height:100%;"><a href="#" id="toogleLogs">Historie >></a><br/></small>';
          logs=logs+'<div id="cr_logs_detail" style="display: none; border: 1px solid white; height: 140px; overflow: auto; margin: 2px; padding: 2px;">';
          logs=logs+"<small><table><tr><td>Historie<td>Beschreibung<td>Erfolgt durch";
          $.each(json, function(k,a){
            logs=logs+'<tr><td width="100px">'+a.datum.toDateEn().toStringDe(true)+"<td>"+a.txt+'<td width="80px">'+a.person_name+" ["+a.person_id+"]<br/>";
          });
          logs=logs+"</table>";
          logs=logs+"</small>";
          if (masterData.auth.editall)
            logs=logs+"<small><p align=\"right\"><a href=\"#\" id=\"del_complete\"><i>(Admin: Gesamten Termin l&ouml;schen)</a></small>";
          logs=logs+"</div>";
          log.html(logs);
          $("#cr_logs a").click(function(c) {
            if (($(this).attr("id")=="del_complete") && (masterData.auth.editall)){
              if (confirm("Soll der Termin wirklich entfernt werden? Achtung, man kann es nicht mehr wiederherstellen!")) {          
                churchInterface.jsendWrite({func: "delBooking", id:id}, function(ok, json) {
                  allBookings[id]=null;
                  $("#cr_cover").html("");
                  t.renderList();
                  elem.dialog("close");
                });
              }  
            } else
              $("#cr_logs_detail").animate({ height: 'toggle'}, "fast");  
            return false; 
          });
        }
      });
    }
    
    if (func=="delete") {
      t.closeAndSaveBookingDetail(elem);
      return;
    }
      
    // Keine Edit-Funktion, wenn sie vom churchCal kommt
    //if (t.currentBooking.cc_cal_id==null) 
    {
      if (((masterData.auth.write) && (t.currentBooking.person_id==masterData.user_pid)) || (masterData.auth.editall) || (t.currentBooking.neu)) {
        elem.dialog('addbutton', 'Speichern', function() {
          t.closeAndSaveBookingDetail(elem);
        });
        
        if ((t.currentBooking.status_id!=99) && (!t.currentBooking.neu)) {
          // Bei Wiederholungsterminen UND einem Eintrag, bei dem es sich um einer Wiederholung handelt: date>startdate
          if ((t.currentBooking.repeat_id>0) && (date!=null) && (date.toDateEn()>t.currentBooking.startdate)) {
            if (t.currentBooking.repeat_id!=999)
              elem.dialog('addbutton', 'Nur aktuellen Termin löschen', function() {
                if (t.currentBooking.exceptions==null) t.currentBooking.exceptions=new Object();
                t.currentBooking.exceptionids=t.currentBooking.exceptionids-1;
                t.currentBooking.exceptions[t.currentBooking.exceptionids]
                      ={id:t.currentBooking.exceptionids, except_date_start:date, except_date_end:date};
                t.closeAndSaveBookingDetail(elem);          
              });
            if (t.currentBooking.repeat_id!=999 && t.currentBooking.cc_cal_id==null)
              elem.dialog('addbutton', 'Diesen und nachfolgende löschen', function() {
                d=date.toDateEn();
                d.addDays(-1);
                $("#cr_fields input[id=inputRepeatUntil]").val(d.toStringDe());
                t.closeAndSaveBookingDetail(elem);
              });
          }
          else {
            title="Löschen";
            if (t.currentBooking.repeat_id>0) title="Gesamte Serie löschen";
            elem.dialog('addbutton', title, function() {
              $("select[id=InputStatus]").val(99);
              t.closeAndSaveBookingDetail(elem);
            });
          }
        }
      }
    }     
    elem.dialog('addbutton', 'Abbrechen', function() {elem.dialog("close");});      
  }
};

WeekView.prototype.renderEntryDetail = function() {
};


})(jQuery);
