	 
 
// Constructor
function PersonView() {
  CDBStandardTableView.call(this);
  this.name="PersonView";
  // Alle Detail-Eintraege anzeigen, auch wenn List sehr lang wird.
  this.showAllGroupDetails=null;
  this.showGroupDetailsId=null;
  this.showGroupDetailsWithHistory=false;
  this.showAllCommentDetails=false;
  this.mapVisible=false;
  //Listenansicht
  this.sortVariable="name";
  // "email"=>Prio Email, dann tel. Andere variante:"tel" 
  this.showcontact="email";
  this.currentFurtherFilter="person";
  this.listViewTableHeight=646;
  this.sortedData=null;
  this.currentTodoTimer=null;
  this.gruppenteilnehmerdatum=new Date();
}

Temp.prototype = CDBStandardTableView.prototype;
PersonView.prototype = new Temp();
personView = new PersonView();

function f(selector) {
  if (masterData.fields==null) return null;
  var res="!!"+selector+"!!";
  $.each(masterData.fields, function(i,c) {
    if (c.fields!=null && c.fields[selector]!=null) {
      res=c.fields[selector].text;
      return false;
    }
  });
  return res;
}

PersonView.prototype.getData = function(sorted, newSort /*default:true*/) {
  if (sorted) {
    if ((newSort==null) || (newSort==true) || (this.sortedData==null)) {
      if (this.sortVariable=="name")
        this.sortedData=churchcore_sortData(allPersons,this.sortVariable, null, null, "vorname");
      else
        this.sortedData=churchcore_sortData(allPersons,this.sortVariable, null, null, "name");
    }
    return this.sortedData;
  }
  else
    return allPersons;
};

PersonView.prototype.renderMenu = function() {
  var t=this;
  
  t.renderTodos();
  
  menu = new CC_Menu(_("menu"));
  if (menuDepth=="amain") {
    if (masterData.auth["create person"])
      menu.addEntry("Neue Person anlegen", "anewentry", "star");
    if (user_access("complex filter"))
      menu.addEntry("Weitere Filter", "aaddfilter", "filter");  
    menu.addEntry("Exporter", "aexporter", "share");
    menu.addEntry("E-Mailer", "amailer", "envelope");
    if (masterData.auth.sendsms)
      menu.addEntry("SMS", "asms", "bullhorn");    
    menu.addEntry("Gruppenliste", "agroupview", "th-list");
    menu.addEntry("Einstellungen", "asettingsview", "wrench");    
    if ((masterData.auth.admin) || (masterData.auth["export"])) { 
      menu.addEntry("Administration", "aadmin", "cog");
    }
    menu.addEntry("Hilfe", "ahelp", "question-sign");
  }
  else if (menuDepth=="aadmin") {
    menu.addEntry("Zur&uuml;ck zum Hauptmen&uuml;", "amain", "home");
    if (masterData.auth["admin"])
      menu.addEntry("Stammdatenpflege", "amaintainview", "cog");  
    if (masterData.auth["export"])
      menu.addEntry("Gesamtexport", "aallexporter", "share");  
    if (masterData.auth["admin"])
      menu.addEntry("LogViewer", "alogviewer", "eye-open");
  }  

  if (!menu.renderDiv("cdb_menu", churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {    
  
    var rows = new Array();  
    rows.push("<div id=\"divnewentry\" style=\"display:none\" class=\"new-entry\"></div>");
    rows.push('<div id="divaddfilter"  style="display:none" class="new-entry"><div id="addfilter" style="width:100%;"><div style="height:200px"/></div></div>');
    $("#cdb_precontent").html(rows.join(""));
  
    
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        t.renderAddEntry();
      }
      else if ($(this).attr("id")=="aexporter") {
        t.exportData(); 
      }
      else if ($(this).attr("id")=="amailer") {
        t.mailer(); 
      }
      else if ($(this).attr("id")=="asms") {
        t.smser(); 
      }
      else if ($(this).attr("id")=="aallexporter") {
        var Fenster = window.open("?q=churchdb/export");     
//        return true;
      }
      else if ($(this).attr("id")=="alogviewer") {
        window.location.href="?q=churchcore/logviewer";      
      }
      else if ($(this).attr("id")=="aaddfilter") {
        if (!t.furtherFilterVisible) {
          t.furtherFilterVisible=true;  
        } else {
          t.furtherFilterVisible=false;                
        } 
        t.renderFurtherFilter();
      }
      else if ($(this).attr("id")=="aadmin") {
        menuDepth=$(this).attr("id");
        t.renderMenu();
      }
      else if ($(this).attr("id")=="agroupview") {
        menuDepth="amain";
        if (masterData.settings.churchdbInitView!='GroupView') {
          masterData.settings.churchdbInitView='GroupView';
          churchInterface.jsendWrite({func:"saveSetting", sub:"churchdbInitView", val:"GroupView"});
        }
        churchInterface.setCurrentView(groupView);
      }
      else if ($(this).attr("id")=="amaintainview") {
        menuDepth="amain";
        churchInterface.setCurrentView(maintainView);
      }
      else if ($(this).attr("id")=="asettingsview") {
        menuDepth="amain";
        churchInterface.setCurrentView(settingsView);
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchDB");
      }
      else if ($(this).attr("id")=="amain") {
        menuDepth="amain";
        t.renderMenu();
      }
      return false;
    });   
  }
};


PersonView.prototype.renderListMenu = function() {
  var t=this;
  
  // Men�leiste oberhalb
  if ($("searchEntry").val()!=null) 
    searchEntry=$("searchEntry").val();
  else
    searchEntry=this.getFilter("searchEntry");

  var navi = new CC_Navi();
  navi.addEntry(churchInterface.isCurrentView("PersonView"),"apersonview","Personenliste");
  navi.addEntry(churchInterface.isCurrentView("MapView"),"aviewmap","Kartenansicht");
  if (masterData.auth["viewstats"])
    navi.addEntry(churchInterface.isCurrentView("StatisticView"),"astatisticview","Statistik");
  if (masterData.auth["viewarchive"])
    navi.addEntry(churchInterface.isCurrentView("ArchiveView"),"aarchiveview","Archiv");
  
  navi.addSearch(searchEntry);
  navi.renderDiv("cdb_search", churchcore_handyformat());
  if (!churchcore_handyformat()) $("#searchEntry").focus();
  
  this.implantStandardFilterCallbacks(this, "cdb_search");    
  
  
  $.widget( "custom.catcomplete", $.ui.autocomplete, {
    _renderMenu: function(ul, items ) {
      var that = this,
        currentCategory = "";
      $.each(items, function( index, item ) {
        if (item.category != currentCategory ) {
          ul.append( "<li class='ui-autocompldete-category'><small><b> " + item.category + "<b></small></li>" );
          currentCategory = item.category;
        }
        that._renderItemData( ul, item );
      });
    }
  });  
  
  var searchEntry=$("#searchEntry");
  searchEntry.catcomplete({
    search: function(event, ui) {
      searchEntry.addClass("throbbing");
    },
    source: function( request, response ) {
      var str=request.term.toUpperCase();
      if (str.length>1) {
        var r=new Array();
        $.each(allPersons, function(k,a) {
          var n=a.vorname+" "+a.name;
          if (n.toUpperCase().indexOf(str)>=0)
            r.push({label:a.vorname+" "+a.name, category:"", value:"#"+a.id});              
        });
        if (masterData.groups!=null) {
          $.each(masterData.groups, function(k,a) {
            if (groupView.isAllowedToSeeDetails(a.id))
              if ((str=="GRUPPE:") || (a.bezeichnung.toUpperCase().indexOf(str)>=0) || (("GRUPPE:"+a.bezeichnung.toUpperCase()).indexOf(str)>=0))
                r.push({label:a.bezeichnung, category:"Gruppe", value:'gruppe:"'+a.bezeichnung+'"'});              
          });
        }
        if (masterData.tags!=null) {
          $.each(masterData.tags, function(k,a) {
            if ((str=="TAG:") || (a.bezeichnung.toUpperCase().indexOf(str)>=0) || (("TAG:"+a.bezeichnung.toUpperCase()).indexOf(str)>=0))
              r.push({label:a.bezeichnung, category:"Tag", value:'tag:"'+a.bezeichnung+'"'});              
          });
        }
        if ((r.length==0) && (str.indexOf("GRUPPE")==-1) && (str.indexOf("TAG")==-1) && (user_access("create person")) && (str.indexOf("#")==-1))
          r.push({label:"Erstelle "+request.term, category:"", value:"CREATE:"+request.term});
        response(r);
      }
      searchEntry.removeClass("throbbing");
    },
    select: function(a,item) {
      if (item.item.value.indexOf("CREATE:")==0) {
        var str=item.item.value.substr(7,99);
        var str2=str.split(" ");
        var vorname=str2[0].substr(0,1).toUpperCase() + str2[0].substr(1);
        var name=str2[1]; 
        if (name!=null) name=name.substr(0,1).toUpperCase() + name.substr(1);
        t.renderAddEntry({vorname:vorname, name:name});
        window.setTimeout(function(){
          $("#searchEntry").val("");
          delete personView.filter["searchEntry"];
        },10);
      }
      else {
        // Leider mu� ich hier TimeOut setzen, denn sonst ist der Wert noch nicht in der Oberfl�che angekommen...
        window.setTimeout(function(){        
          $("#searchEntry").trigger( "keyup" );
          // For iOS to close the virtual keyboard
          $("#searchEntry").blur();
        },10);
      }
    }    
  });
  
  $("#cdb_search a").click(function () {
    if ($(this).attr("id")=="apersonview") {
      personView.furtherFilterVisible=t.furtherFilterVisible;
      churchInterface.setCurrentView(personView);
    }
    else if ($(this).attr("id")=="astatisticview") {
      statisticView.furtherFilterVisible=t.furtherFilterVisible;
      churchInterface.setCurrentView(statisticView);
    }
    else if ($(this).attr("id")=="aviewmap") {
      mapView.furtherFilterVisible=t.furtherFilterVisible;
      churchInterface.setCurrentView(mapView);
    }
    else if ($(this).attr("id")=="aarchiveview") {
      mapView.furtherFilterVisible=t.furtherFilterVisible;
      churchInterface.setCurrentView(archiveView);
    }
    churchInterface.getCurrentView().filter=t.filter;
    return false;
  });  
};


// Render AddNewPerson
PersonView.prototype.renderAddEntry = function(prefill) {
  var _text='<div class="well"><div class="row-fluid">';
  var t=this;

  var form_person = new CC_Form("Name", prefill);
  form_person.surroundWithDiv("span4");
  form_person.addStandardField(masterData.fields.f_address.fields["vorname"]);
  form_person.addStandardField(masterData.fields.f_address.fields["name"]);
  form_person.addHtml('<p><a href="#" class="furtherfields">Weitere Felder hinzuf&uuml;gen</a>');
  form_person.addHtml('<div id="furtherfields" style="display:none">');
  $.each(masterData.fields, function(k,fields) {
    $.each(fields.fields, function(i,f) {
      if (f.inneuerstellen_yn==1) {
        form_person.addStandardField(f);
      }
    });
  });
  form_person.addHtml('</div>');
  
  _text=_text+form_person.render();

  var form = new CC_Form("Kategorien");
  form.surroundWithDiv("span4");
  if (masterData.settings.newPersonBereich==null)
    masterData.settings.newPersonBereich=1;
  if (masterData.settings.newPersonStatus==null)
    masterData.settings.newPersonStatus=1;
  if (masterData.settings.newPersonStation==null)
    masterData.settings.newPersonStation=1;
  form.addSelect({data: masterData.auth.dep, selected:masterData.settings.newPersonBereich, cssid:"Inputf_dep", label:f("bereich_id")});
  form.addSelect({data: masterData.status, selected:masterData.settings.newPersonStatus, cssid:"Inputf_status", label:f("status_id")});  
  if (masterData.fields.f_category.fields.station_id!=null) 
    form.addSelect({data: masterData.station, selected:masterData.settings.newPersonStation, cssid:"Inputf_station", label:f("station_id")});
  _text=_text+form.render();

  var form = new CC_Form("Gruppen");
  form.surroundWithDiv("span4");
  if (masterData.groups!=null) {
    var diff_date=new Date();
    diff_date.addDays(-1*masterData.groupnotchoosable);
    if (masterData.auth.editgroups) {
      $.each(masterData.groupTypes, function(k,a) {
        if (a.in_neue_person_erstellen_yn==1) {      
          var data=new Array(); 
          $.each(masterData.groups, function(i,b) {
            if ((b.gruppentyp_id==a.id) && (b.valid_yn==1) && (b.versteckt_yn==0) && ((b.abschlussdatum==null) || (b.abschlussdatum.toDateEn()>diff_date)))
              data.push(form_prepareDataEntry(b.id, b.bezeichnung));
          });
          form.addSelect({data:data, freeoption:true, cssid:"createGroup", label:a.bezeichnung});
        }  
      });
    }
    else {
      // Get groups, only where I am a leader. No free option. 
      var data=new Array(); 
      $.each(masterData.groupTypes, function(k,a) {
        $.each(masterData.groups, function(i,b) {
          if ((b.gruppentyp_id==a.id) && (b.valid_yn==1) && (b.versteckt_yn==0) && ((b.abschlussdatum==null) || (b.abschlussdatum.toDateEn()>diff_date)))
            if (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))
            data.push(form_prepareDataEntry(b.id, b.bezeichnung));
        });
      });
      form.addSelect({data:data, freeoption:false, cssid:"createGroup", label:"Meine Gruppen"});
    }    
  }
  _text=_text+form.render();
  
  _text=_text+'</div><div class="row-fluid">';
  var form = new CC_Form();
  form.surroundWithDiv("span12");
  form.addButton({label:"Person anlegen", controlgroup:true, cssid:"btProoveNewAddress"});
  form.addHtml("&nbsp; ");
  form.addCheckbox({cssid:"forceCreate",controlgroup_start:true,label:'auch anlegen, wenn es den Namen schon gibt'});
  form.addCheckbox({cssid:"forceDontHide",controlgroup_end:true,label:'weitere Person anlegen'});
  _text=_text+form.render(false,"vertical");
  
  _text=_text+"</div></div>";
      
  $("#divnewentry").html(_text);
  $("#divnewentry a.furtherfields").click(function () {
    $(this).hide();
    $("#furtherfields").animate({ height: 'toggle'}, "medium");
    return false;
  });
  $("#divnewentry select").change(function () {
    var txt="";
    if ($(this).attr("id")=="Inputf_dep") txt="newPersonBereich";
    else if ($(this).attr("id")=="Inputf_status") txt="newPersonStatus";
    else if ($(this).attr("id")=="Inputf_station") txt="newPersonStation";
    churchInterface.jsendWrite({func:"saveSetting", sub:txt, val:$(this).val()});
    masterData.settings[txt]=$(this).val();
    
    var g_id=$(this).val();
    if (masterData.groups[g_id]!=null) {
      var stats=groupView.getStatsOfGroup(g_id);
      if ((masterData.groups[g_id].max_teilnehmer!=null) && (masterData.groups[g_id].max_teilnehmer<=stats.count_all_member)) {
        alert("Die Gruppe hat schon die maximale Anzahl von Teilnehmer erreicht, bitte andere Gruppe nehmen!");
        $(this).val("");
      }
    }
    
  });  
  
  $("#btProoveNewAddress").click(function () {
    var obj=form_person.getAllValsAsObject(false);
    obj["func"]="createAddress";
    if (obj["vorname"]==null) obj["vorname"]="";
    if (obj["name"]=="") { alert("Bitte Nachname angeben!"); return false };
    $("#divnewentry select").each(function (i, s){
      obj[$(this).attr("id")]=$(this).val();
    });
    
    if ($("#forceCreate").attr("checked")=="checked") {
      obj["force"]="checked";
    }
    
    churchInterface.jsendWrite(obj, function(ok, json) {        
      if (json.result=="exist") {
        $("#searchEntry").val(json.id).keyup();          
        alert("Mindestens eine Person mit dem Namen existiert schon!");
      } 
      else if (json.result!="ok") {
        alert("Fehler aufgetreten: "+json.result);
      }
      else {
        $("select[id=createGroup]").each(function (i) {
          if ($(this).val()>"") {    
            t.addPersonGroupRelation(json.id, $(this).val());
          }
        });
        cdb_loadPersonData(function() {
          t.filter=new Object();
          t.filter.searchEntry="#"+json.id;
          if ($("#forceDontHide").attr("checked")=="checked") {
            t.renderList();
            t.renderListMenu();
          }
          else t.renderView();
          
          //$("#searchEntry").val(json.id).keyup();
        },1,json.id);
      }        
    });
  });
  $("#divnewentry").animate({ height: 'toggle'}, "medium");
  window.setTimeout(function() {
    $("#divnewentry").find("#vorname").focus();
  }, 200);

};

PersonView.prototype.isPersonLeaderOfPerson = function (leader_id, p_id) {
  if (allPersons[p_id].gruppe==null) return false;
  var res=false;
  $.each(allPersons[p_id].gruppe, function(k,gruppe) {
    if (groupView.isPersonLeaderOfGroup(leader_id, gruppe.id)) {
      res=true;
      // exit
      return false;
    }
  });  
  return res;
};
PersonView.prototype.isPersonSuperLeaderOfPerson = function (leader_id, p_id) {
  if (allPersons[p_id].gruppe==null) return false;
  var res=false;
  $.each(allPersons[p_id].gruppe, function(k,gruppe) {
    if (groupView.isPersonSuperLeaderOfGroup(leader_id, gruppe.id)) {
      res=true;
      // exit
      return false;
    }
  });  
  return res;
};

PersonView.prototype.isPersonLeaderOfOneGroupTypeOfPerson = function (leader_id, grouptype_id, p_id) {
  if (allPersons[p_id].gruppe==null) return false;
  var res=false;
  $.each(allPersons[p_id].gruppe, function(k,gruppe) {
    if (groupView.isGroupOfGroupType(gruppe.id, grouptype_id)) {
      if (groupView.isPersonLeaderOfGroup(leader_id, gruppe.id)) {
        res=true;
        // exit
        return false;
      }
    }
  });
  return res;
};

/**
 * 
 * @param id
 * @param g_id
 * @return true if ok, false if error
 */
PersonView.prototype.addPersonGroupRelation = function(id, g_id, memberstatus_no, followup_erfolglos_zurueck_gruppen_id) {
  var d=new Date();
  var success=false;
  if (memberstatus_no==null) memberstatus_no=0;
  
  var stats=groupView.getStatsOfGroup(g_id);
  if ((masterData.groups[g_id].max_teilnehmer!=null) && (masterData.groups[g_id].max_teilnehmer<=stats.count_all_member)) {
    if (allPersons[id]!=null)
      alert("Gruppe "+masterData.groups[g_id].bezeichnung+" hat schon die maximale Anzahl von "+masterData.groups[g_id].max_teilnehmer+" Teilnehmern erreicht. "
         +allPersons[id].vorname+" "+allPersons[id].name+" konnte nicht zugewiesen werden!");
    else  
      alert("Gruppe "+masterData.groups[g_id].bezeichnung+" hat schon die maximale Anzahl von "+masterData.groups[g_id].max_teilnehmer+" Teilnehmern erreicht. "
          +"Die Gruppe konnte der Person nicht zugewiesen werden!");
    return false;
  }
  
  if (masterData.groups[g_id].followup_typ_id>0) {
    alert('Followup "'+masterData.followupTypes[masterData.groups[g_id].followup_typ_id].bezeichnung+'" wurde gestartet!');
    churchInterface.jsendWrite({func:"addPersonGroupRelation", id:id, g_id:g_id, 
           followup_count_no:1, followup_erfolglos_zurueck_gruppen_id:followup_erfolglos_zurueck_gruppen_id,
           leader:memberstatus_no, date:d.toStringEn()}, function(ok) {success=ok;}, false);
  }  
  else         
    churchInterface.jsendWrite({func:"addPersonGroupRelation", id:id, g_id:g_id, leader:memberstatus_no, date:d.toStringEn()}, function(ok) {success=ok;}, false);
  
  if (!success) {
    return false;
  }
  else {  
    arr=new Object();
    arr.id=g_id;
    arr.leiter=memberstatus_no;
    arr.d=new Date().toStringEn();
    if (allPersons[id]==null) {
      allPersons[id]=new Object();
      churchInterface.jsendRead({func:"getPersonDetails", id:id}, function(ok, json) {
        allPersons[json.id]=cdb_mapJsonDetails(json, allPersons[json.id]);
      }, false);
    }
    if (allPersons[id].gruppe==null)
      allPersons[id].gruppe=new Object();

    allPersons[id].gruppe[arr.id]=arr;
    this.renderTodos();
    return true;
  }
};

PersonView.prototype.addSecondMenu = function() {
  rows = new Array();
  // Gruppenfunktionen sind nur erlaubt, wenn ich Leiter der ausgew�hlten Gruppe bin, oder ich Schreibrechte habe
  rows.push("<p></p>");
  if ((this.filter["filterMeine Gruppen"]>0) && 
       ((groupView.isPersonLeaderOfGroup(masterData.user_pid,this.filter["filterMeine Gruppen"])) || (masterData.auth.write))) { 
    rows.push("<p>Gruppenfunktionen:&nbsp;");
    rows.push("|&nbsp;<a id=\"addtogroup\" href=\"#\">Person zur Gruppe hinzuf&uuml;gen</a>&nbsp; | &nbsp;");
//    if (masterData.auth.write)
      rows.push("<a id=\"delfromgroup\" href=\"#\">Markierte Person aus der Gruppe entfernen</a>&nbsp; | &nbsp;");
  } 
  if ((masterData.auth.write) && (masterData.auth.editgroups)) {
    rows.push("<p>Personenfunktionen: Markierte Personen ... &nbsp;");
    rows.push('<select id="personFunction"><option value="-1">');
    rows.push('<option value="addToGroup">... einer Gruppe hinzuf&uuml;gen');
    if (masterData.auth.viewtags)
      rows.push('<option value="addPersonTag">... einen Tag hinzuf&uuml;gen');
    if (masterData.auth.adminpersons)
      rows.push('<option value="addPersonAuth">... ein Zugriffssrecht hinzuf&uuml;gen');
    if ((masterData.auth["push/pull archive"]) && (t.name=="PersonView"))
      rows.push('<option value="archivePerson">... ins Archiv verschieben');
    
    if (churchInterface.isCurrentView("ArchiveView") && (masterData.auth.admin || masterData.auth.adminpersons))
      rows.push('<option value="deletePerson">... endg&uuml;ltig l&ouml;schen');    
    
    rows.push('</select>');
  }
  
  
  return rows.join("");
};


function renderPersonTooltip(id) {
  function _renderTooltipDetails(id) {
    var a=allPersons[id];
    txt="<b>"+a.vorname+" "+a.name;

    if (a.geburtsdatum!=null && a.geburtsdatum.toDateEn().getAgeInYears().txt!=null) {
      var age=a.geburtsdatum.toDateEn().getAgeInYears().txt;
      if (age!=null) {
        txt=txt+" ("+age+")";        
      }
    }
    txt=txt+"</b><br/>";
    
    if ((masterData.auth.viewaddress) || (masterData.auth.viewalldetails))
      txt=txt+a.strasse+"<br/>";
    txt=txt+a.plz+" "+a.ort;
    txt=txt+"<br/><br/>";
    if (a.email!="") 
      txt=txt+"E-Mail: "+a.email+"<br/>";
    if (a.telefonhandy!="") 
    txt=txt+"Handy: "+a.telefonhandy+"<br/>";
    if (a.telefonprivat!="") 
    txt=txt+"Telefon: "+a.telefonprivat+"<br/>";
    if (a.telefongeschaeftlich!="") 
    txt=txt+"Telefon Arbeit: "+a.telefongeschaeftlich+"<br/>";
    
    return txt;
  }
  var txt="";
  var id;
  
  if (id>0) {
    txt=txt+personView.renderPersonImage(id);
    txt='<br/><div class="tooltip_gesamt" style="min-width:300px"><div class="tooltip_foto">'+txt+'</div><div id="cdb_tooltipdetail" class="tooltip_address">';
    
    if (allPersons[id].details) {
      txt=txt+_renderTooltipDetails(id)+'</div><div style="clear:both"></div></div>';
    }
    else {
      txt=txt+'Lade Daten..</div><div style="clear:both"></div></div>';
      churchInterface.jsendRead({func:"getPersonDetails", id:id}, function(ok, json) {
        if (json!="no access") {
          allPersons[json.id]=cdb_mapJsonDetails(json, allPersons[json.id]);       
          if (currentTooltip!=null)
            currentTooltip.tooltips("refresh");
          txt=null;
        }
        else $("#cdb_tooltipdetail").html("<i>Keine Berechtigung</i>");
      });
    }
    return [txt, ""];
  }
};

PersonView.prototype.renderGroupmeetingTooltip = function(group_id, groupmeeting_id, meetingdate) {
  var t=this;
  var txt="<p>";
  var meeting=t.getMeetingFromMeetingList(group_id, groupmeeting_id);
  if (meeting!=null) {
    if (meeting.kommentar!=null) {
        txt=txt+'<div class="well"><i>Kommentar: </i><br><small><i>'+meeting.kommentar;
        txt=txt+'<p>'+meeting.modified_date.toDateEn(false).toStringDe(false);
        if (allPersons[meeting.modified_pid]!=null)
          txt=txt+" "+allPersons[meeting.modified_pid].vorname+" "+allPersons[meeting.modified_pid].name+"";
        else
          txt=txt+" ["+meeting.modified_pid+"]";
        txt=txt+"</i></small></div>";
    }
    else
      txt=txt+"Kein Kommentar vorhanden";
    if (meeting.anzahl_gaeste!=null)
        txt=txt+"<p>Anzahl G&auml;ste: "+meeting.anzahl_gaeste;
    txt=txt+"<p>"+form_renderButton({label:"Editieren", htmlclass:"edit btn-success", type:"small"})+" ";
    txt=txt+""+form_renderButton({label:"Entfernen", htmlclass:"delete btn-danger", type:"small"});
    txt=txt+form_renderHidden({cssid:"data-group-id", value:group_id});
    txt=txt+form_renderHidden({cssid:"data-datum", value:meetingdate});
    txt=txt+form_renderHidden({cssid:"data-gruppentreffen-id", value:meeting.id});
    return [txt];
  };
};

PersonView.prototype.getMeetingFromMeetingList = function (g_id, gruppentreffen_id) {
  var res=null;
  $.each(masterData.groups[g_id].meetingList, function(k,a) {
    if (a.id==gruppentreffen_id) {
      res=a;
      return false;
    }
  });
  return res;
};

PersonView.prototype.editMeetingProperties = function(g_id, treffen_id) {
  var t=this;
  var meeting=t.getMeetingFromMeetingList(g_id, treffen_id);
  if (meeting!=null) {
    var form=new CC_Form(null);       
    form.addInput({label:"Datum",cssid:"inputmeetingdate", value:meeting.datumvon.toDateEn(false).toStringDe(), datepicker:"dp_meetingdate"});
    form.addInput({label:"Uhrzeit",value:meeting.datumvon.toDateEn(true).toStringDeTime()});
    form.addInput({label:"Anzahl G&auml;ste", value:meeting.anzahl_gaeste, cssid:"anzahl_gaeste"});
    form.addTextarea({label:"Kommentar", rows:4, data:meeting.kommentar, placeholder:"Kommentar", cssid:"kommentar"});
    
    var elem=form_showDialog("Gruppentreffen editieren", form.render(null, "horizontal"), 500, 400, {
      "Speichern": function() {
        var obj=form.getAllValsAsObject();
        obj.func="GroupMeeting";
        obj.sub="saveProperties";
        obj.id=treffen_id;
        obj.g_id=g_id;
        var save=$.extend({}, meeting);
        obj.datumvon=obj.inputmeetingdate.toDateDe().toStringEn(false)+" "+obj.Uhrzeit;
        obj.datumbis=obj.datumvon;
        meeting.datumvon=obj.datumvon
        meeting.datumbis=obj.datumvon;        
        meeting.kommentar=obj.kommentar;
        meeting.anzahl_gaeste=obj.anzahl_gaeste;
        if (meeting.anzahl_gaeste=="") meeting.anzahl_gaeste=0;
        
        churchInterface.jsendWrite(obj, function(ok) {
          if (!ok) {
            alert("Es gab ein Fehler beim Speichern!");
            meeting=save;
            t.renderList();
          }
        });        
        t.renderList();
        $(this).dialog("close");
      },
      "Abbrechen": function() {
        $(this).dialog("close");
      }
    });
    $("#inputmeetingdate").click(function() {
      form_implantDatePicker("dp_meetingdate", dt.toStringDe(), function(dateText) {    
        $("#inputmeetingdate").val(dateText.toDateDe().toStringDe());
      });
    });
    
    
  }
  else alert("Treffen nicht gefunden!?");  
  
};


PersonView.prototype.addPersonsTooltip = function(element) {
  element.find(".tooltip-person").each(function() {
    var tooltip=$(this);
    tooltip.tooltips({
      data:{id:$(this).attr("data-tooltip-id")},
      render:function(data) {
        return renderPersonTooltip(data.id);
      }      
    });    
  });  
};

PersonView.prototype.addGroupMeetingDate = function() {
  var form=new CC_Form("Ein Gruppentreffen hinzuf&uuml;gen");
  var dt=new Date();
  form.addInput({label:"Datum",cssid:"inputmeetingdate", value:dt.toStringDe(false), datepicker:"dp_meetingdate"});
  form.addInput({label:"Uhrzeit",value:"10:00"});
  form.addHtml('<p><small>Hinweis: Dieses Gruppentreffen wird nur für diese Gruppe angelegt. ');
  form.addHtml('<a href="https://intern.churchtools.de/?q=churchwiki#WikiView/filterWikicategory_id:0/doc:Gruppentreffen/" target="_clean"><i class="icon-question-sign"></i></a>');
  var elem=form_showDialog("Gruppentreffen", form.render(), 400, 400, {
    "Speichern": function() {
      var obj=form.getAllValsAsObject();
      obj.datumvon=obj.inputmeetingdate.toDateDe().toStringEn(false)+" "+obj.Uhrzeit;
      obj.datumbis=obj.datumvon;
      obj.gruppe_id=t.filter["filterMeine Gruppen"];
      obj.func="addEvent";
      churchInterface.jsendWrite(obj, function(ok, data) {
        elem.dialog("close");
        t.loadGroupMeetingList(t.filter["filterMeine Gruppen"]);            
      });
    },
    "Abbrechen": function() {
      $(this).dialog("close");
    }
  }); 
  $("#inputmeetingdate").click(function() {
    form_implantDatePicker("dp_meetingdate", dt.toStringDe(), function(dateText) {    
      $("#inputmeetingdate").val(dateText.toDateDe().toStringDe());
    });
  });
  
  
};

PersonView.prototype.addFurtherListCallbacks = function(cssid) {
  var t=this;
  
  t.addPersonsTooltip($("#cdb_content"));
  
  $("#cdb_content span.tooltip-groupmeeting").each(function() {
    var tooltip=$(this);
    tooltip.tooltips({
      data:{group_id:$(this).attr("data-tooltip-id"),
            groupmeeting_id:$(this).attr("data-gruppentreffen-id"),
            meetingdate:$(this).attr("data-datum")
      },
      render:function(data) {
        return t.renderGroupmeetingTooltip(data.group_id, data.groupmeeting_id, data.meetingdate);
      },
      
      afterRender: function(element, data) {
        t.currentTooltip=$(tooltip);
        element.find("input.delete").click(function() {
          if (confirm("Wirklich das Treffen vom "+data.meetingdate.toDateEn(true).toStringDe()+" entfernen?")) {
            churchInterface.jsendWrite({func:"GroupMeeting", sub:"delete", id:data.groupmeeting_id}, function(ok) {
              if (ok) {
                cdb_loadGroupMeetingStats(churchInterface.getCurrentView().filter, data.group_id, function() {
                  masterData.groups[data.group_id].meetingList=null;
                  personView.renderList();
                });
              }
            });
            
          }
        });
        element.find("input.edit").click(function() {
          clearTooltip();
          t.editMeetingProperties(data.group_id, data.groupmeeting_id);
        });        
      }
    });    
  });


  
  $("#cdb_content a").click(function (a) {
    clearTooltip();
    if ($(this).attr("id")==null)
      return true;
    else if ($(this).attr("id")=="addtogroup") {
      t.renderPersonSelect("Nach einer Person suchen", true, function(id) {
        delete t.filter["searchEntry"];
        t.addPersonGroupRelation(id, t.filter["filterMeine Gruppen"]);

        t.renderList();                  
      });
    }
    else if ($(this).attr("id").indexOf("contact")==0) {
      t.showcontact=$(this).attr("id").substr(7,99);
      t.renderList();
    }
    else if ($(this).attr("id")=="delfromgroup") {
      usernames="";
      ids="-1";
      $.each(allPersons, function(k,a){
        if ((t.checkFilter(a)) && (a.checked)) {
          usernames=usernames+a.vorname+" "+a.name+", ";
          ids=ids+","+a.id;
        }  
      });
      if (usernames=="")
        alert("Bitte entsprechenden Eintrag markieren");
      else  
      if (confirm("Wirklich "+usernames+" aus '"+masterData.groups[t.filter["filterMeine Gruppen"]].bezeichnung+"' entfernen?")) {
        $.each(allPersons, function(k,a){
          if ((t.checkFilter(a)) && (a.checked)) {
            t.delPersonFromGroup(a.id, t.filter["filterMeine Gruppen"], true);
            a.checked=false;
          }  
        });
      } 
    }
    else if ($(this).attr("id")=="mailto") return true;
    else if ($(this).attr("id").indexOf("groupinfos")==0) {
      churchInterface.setCurrentView(groupView);
      groupView.clearFilter();
      // Wenn er nicht alle sehen darf, dann hat er Meine Gruppen. 
      if (masterData.auth.viewalldata)
        groupView.setFilter("searchEntry",$(this).attr("href").substring(1,99));
      else
        groupView.setFilter("filterMeine Gruppen", $(this).attr("href").substring(1,99));
      groupView.renderView();
    }   
    else if ($(this).attr("id").indexOf("search_tag")==0) {
      t.setFilter("searchEntry",'tag:"'+masterData.tags[$(this).attr("id").substring(10,99)].bezeichnung+'"');
      a.open=false;
      t.renderView();
      return false;
    }
    else if ($(this).attr("id")=="closeGruppenteilnahme") {
      masterData.settings.selectedGroupType=churchcore_getFirstElement(masterData.groupTypes).id;
      churchInterface.jsendWrite({func:"saveSetting", sub:"selectedGroupType", val:masterData.settings.selectedGroupType});    
      r.renderView();
    }
    else if ($(this).attr("id")=="editGruppentreffenProperties") {
      clearTooltip(true);
      t.editMeetingProperties($(this).attr("data-group-id"), $(this).attr("data-gruppentreffen-id"));
    }
    else if ($(this).attr("id")=="addGruppenteilnehmerdatum") {
      t.addGroupMeetingDate();
    }
  });
  if (cssid=="#cdb_content") { 
    $("#cdb_content span.clickyesno").click(function() {
      var m=t.getMeetingFromMeetingList(t.filter["filterMeine Gruppen"], $(this).attr("data-gruppentreffen-id"));
      var p_id=$(this).attr("data-person-id");
      var entry=null;
      $.each(m.entries, function(k,a) {
        if (a.p_id==p_id) {
          entry=k;
          return false;
        }
      });
      var o=new Object();
      if (entry==null) { 
        m.entries.push({p_id:p_id, treffen_yn:1});        
        o.treffen_yn=1;
      }
      else {
        if (m.entries[entry].treffen_yn==1)
          m.entries[entry].treffen_yn=0;
        else          
          m.entries[entry].treffen_yn=1;
        o.treffen_yn=m.entries[entry].treffen_yn;      
      }
      m.eintragerfolgt_yn=1;
      o.func="GroupMeeting";
      o.sub="editCheckin";
      o.gruppentreffen_id=$(this).attr("data-gruppentreffen-id");
      o.p_id=p_id;
      churchInterface.jsendWrite(o); 

      t.getListHeader();
      
      var pos=$(document).scrollTop();
      t.renderList();
      window.setTimeout(function() { $(document).scrollTop(pos);}, 10);
      return false;
    });
  
    // Auswahl des Gruppentyps fuer die Spalte Gruppe
    $("#cdb_content select").change(function(c) {
      if (this.id=="filterGruppentyp") {
        var oldval=masterData.settings.selectedGroupType;
        masterData.settings.selectedGroupType=$(this).val();
        churchInterface.jsendWrite({func:"saveSetting", sub:"selectedGroupType", val:$(this).val()});
        if ((masterData.settings.selectedGroupType==-4) && (t.filter['filterMeine Gruppen']==null)) {
          alert('Bitte erst eine Gruppe unter "Meine Filter" einstellen');
          masterData.settings.selectedGroupType=oldval;
        }
        else 
          t.renderGroupEntry();
        t.renderList();
      }
      else if (this.id=="personFunction") {
        t.personFunction(this.value);
        $(this).val(-1);
      }
    });
  }
};

/**
 * value: Welche Aktion wurde gew�hlt
 * param: ein belibiegr Parameter zur �bergabe
 */
PersonView.prototype.personFunction = function (value, param) {
  var t=this;
  var ids=new Array();
  $.each(allPersons, function(k,a) {
    if (a.checked) ids.push(a.id);
  });
  if (ids.length==0) 
    alert("Bitte Personen markieren um diese Funktion zu nutzen!");
  else {
    var form = new CC_Form();
    if (value=="addToGroup") {
      form.setLabel("Bitte Gruppe ausw&auml;hlen");
      form.addSelect({
        freeoption:true, 
        label:f("gruppentyp_id"),
        data:masterData.groupTypes, 
        cssid:"inputGruppentyp",
        selected:param
      });
      if (param>0) 
        form.addSelect({
          label:"Gruppe",
          data:masterData.groups, 
          cssid:"inputId",
          func: function(d) {
                  return d.gruppentyp_id==param;
                } 
        });
    }
    else if (value=="addPersonTag") {
      form.setLabel("Bitte Tag ausw&auml;hlen");
      form.addSelect({
        freeoption:false, 
        label:"Tag",
        data:masterData.tags, 
        cssid:"inputId"
      });
    }
    else if (value=="addPersonAuth") {        
      form.setLabel("Bitte Zugriffsrecht ausw&auml;hlen");
      form.addSelect({
        label:"Zugriffsrecht",
        data:getAuthAsDataArray(false),
        cssid:"inputId",
        sort:false,
        selected:t.filter["filterAuth"]
      });      
    }
    else if (value=="deletePerson") {
      form.setLabel("Personen endg&uuml;ltig l&ouml;schen");
      form.addHtml("Hiermit werden die markierten Personen gel&ouml;scht. Diese Aktion kann nicht wieder r&uuml;ckg&auml;ngig gemacht werden!");
      form.addHidden({cssid:"inputId",value:"dummy"});
    }
    else if (value=="archivePerson") {
      form.setLabel("Personen archivieren");
      form.addHtml("Hiermit werden die markierten Personen in das Archiv verschieben. Diese sind dann nur noch mit speziellen Rechten sichtbar!");

      form.addHidden({cssid:"inputId",value:"dummy"});
    }
    form.addCheckbox({cssid:"delChecked",label:'Markierung bei den '+ids.length+' Personen wieder entfernen?'});
    
    var elem = t.showDialog("Batch-Anpassungen", form.render(), 500, 350, {
      "Speichern": function() {
         var id=$("#inputId").val();
         var obj=new Object();
         var delChecked=$("#delChecked").attr("checked")=="checked";
         obj.func=value;
         if (id==null) {
           alert("Bitte Eintrag selektieren!");
           return false;
         }
         elem.html('<legend>Fortschritt</legend><div class="progress progress-striped active"><div class="bar" style="width: 0%;"></div></div>');          
         function _progress(ids, current_id) {
           if (ids[current_id]!=null) {
             window.setTimeout(function() {
               if (value=="addToGroup") {
                 t.addPersonGroupRelation(ids[current_id], id);
               }
               else if (value=="addPersonTag") {
                 if (allPersons[ids[current_id]].tags==null)
                   allPersons[ids[current_id]].tags= new Array();
                 if (!churchcore_inArray(id, allPersons[ids[current_id]].tags)) {
                   allPersons[ids[current_id]].tags.push(id);
                   churchInterface.jsendWrite({func:"addPersonTag", id:ids[current_id], tag_id:id});
                 }
               }
               else if (value=="addPersonAuth") {
                 obj.id=ids[current_id];
                 if (allPersons[ids[current_id]].auth==null)
                   allPersons[ids[current_id]].auth=new Object();
                 allPersons[ids[current_id]].auth[id]=id;
                 obj.auth_id=id;
                 churchInterface.jsendWrite(obj, null, false);                 
               }
               else if (value=="archivePerson") {
                 obj.id=ids[current_id];
                 if (allPersons[ids[current_id]].archiv_yn==0) {
                   allPersons[ids[current_id]].archiv_yn=1;
                   churchInterface.jsendWrite(obj, null, false);
                 }
               }
               else if (value=="deletePerson") {
                 obj.id=ids[current_id];
                 allPersons[ids[current_id]]=null;                                 
                 churchInterface.jsendWrite(obj, null, false);                 
               }
               elem.find("div.bar").width((100*(current_id+1)/ids.length)+'%');
               _progress(ids, current_id+1);
             },10);
           }
           // Fertig
           else {
             elem.dialog("close");
             t.renderList();
           }
         }
         _progress(ids, 0);                
         if (delChecked) {
           $.each(allPersons, function(k,a) {allPersons[a.id].checked=null; });
         }
      },          
      "Abbrechen": function() {
        $(this).dialog("close");
      }
    });
    elem.find("#inputGruppentyp").change(function(c) {
      elem.dialog("close");
      t.personFunction(value,$(this).val());      
    });
  }
};


PersonView.prototype.renderListEntry = function(a) {
  var t=this;
  rows=new Array();
  
  var _class="tooltip-person status_";
  if (masterData.status[a.status_id]!=null) {
    _class=_class+masterData.status[a.status_id].kuerzel.toLowerCase();
    if (masterData.status[a.status_id].mitglied_yn==1) _class=_class+" member";
  }  
  
  rows.push("<td><a href=\"\" class=\""+_class+"\" data-tooltip-id=\""+a.id+"\" id=\"detail" + a.id + "\">" + a.vorname);
  if (a.spitzname!="") rows.push(" ("+a.spitzname+")");
  rows.push("</a><td><a href=\"\" class=\""+_class+"\" data-tooltip-id=\""+a.id+"\" id=\"detail" + a.id + "\">" + a.name+"</a>");

  // Nicht anzeigen bei Gruppenteilnahmenstatus
  if (masterData.settings.selectedGroupType!=-4) {
    if (t.showcontact=="email") {
      if ((a.email != null) && (a.email != "")) 
        rows[rows.length] = "<td class=\"hidden-phone\"><a id=\"mailto\" href=\"mailto:" + a.email + "\">" + a.email + "</a>";
      else 
        if ((a.tel != null) && (a.tel != "")) 
          rows[rows.length] = '<td class=\"hidden-phone\"><a href="tel:'+a.tel+'">' + a.tel + '</a>';
        else 
          rows[rows.length] = "<td class=\"hidden-phone\">-";
    } 
    else {
      if ((a.tel != null) && (a.tel != "")) 
        rows[rows.length] = '<td class=\"hidden-phone\"><a href="tel:'+a.tel+'">' + a.tel + '</a>';
      else 
        rows[rows.length] = "<td class=\"hidden-phone\">-";
    }
    rows[rows.length] = "<td class=\"hidden-phone\">";
  }
  else if ((masterData.groups!=null) && (masterData.groups[t.filter["filterMeine Gruppen"]]!=null)) {  
    a.open=false;
    if ((masterData.groups[t.filter["filterMeine Gruppen"]].meetingList!=null)
         && (masterData.groups[t.filter["filterMeine Gruppen"]].meetingList!="get data")) {
      $.each(masterData.groups[t.filter["filterMeine Gruppen"]].meetingList, function(k,m) {
        if (((m.datumvon.toDateEn(false).getFullYear()==t.gruppenteilnehmerdatum.getFullYear())
               && m.datumvon.toDateEn(false).getMonth()==t.gruppenteilnehmerdatum.getMonth())) {
          rows.push('<td><span class="clickyesno" data-person-id="'+a.id+'" data-gruppentreffen-id="'+m.id+'">');
          var dabei=false;
          $.each(m.entries, function(i,b) {
            if (b.p_id==a.id) {
              dabei=true;
              rows.push(t.renderYesNo(b.treffen_yn, 18));
            }
          });
          if (!dabei) rows.push(form_renderImage({src:"question.png", width:18}));
        }
      });
    }
    rows.push("<td>");
  }
  
  
  if (masterData.settings.selectedGroupType==-2) {
    if (a.tags!=null) {
      var first=true;
      $.each(a.tags, function(k,a) {
        rows.push(t.renderTag(a,false)+"&nbsp;");
      });
    }
  } 
  else if (masterData.settings.selectedGroupType==-3) {      
    if (a.auth!=null) {
      var arr=new Array();
      $.each(a.auth, function(k,b) {
        arr.push(t.renderAuth(k));
      });
      if (arr.length>0)
        rows.push('<b>Direkte Rechte: </b>'+arr.join(",")+'<br/>');
    }
      
    if (a.gruppe!=null) {
      var arr=new Object();
      $.each(a.gruppe, function(k,b) {
        if ((b.leiter>=0) && (masterData.groups[b.id].auth!=null))
          $.each(masterData.groups[b.id].auth, function(i,c) {
            arr[i]=i;
          });
      });        
      if (churchcore_countObjectElements(arr)>0) {
        rows.push('<b>Rechte durch Gruppen: </b>');
        var arr2=new Array();
        $.each(arr, function(k,a) {
          arr2.push(t.renderAuth(a));
        });
        rows.push(arr2.join(", "),'<br/>');
      }
    }
    if (masterData.status[a.status_id].auth!=null) {
      var arr=new Array();
      $.each(masterData.status[a.status_id].auth, function(k,b) {
        arr.push(t.renderAuth(k));
      });
      if (arr.length>0)
        rows.push('<b>Rechte durch '+f("status_id")+': </b>'+arr.join(",")+'<br/>');
    }
  } 
  // Show group membership
  else { 
    if (a.gruppe!=null) {
      var k=0;
      $.each(a.gruppe, function(j,b) {
        if ((masterData.groups[b.id]!=null) && 
               (groupView.isGroupOfGroupType(b.id, masterData.settings.selectedGroupType))
               && groupView.isAllowedToSeeName(b.id, a.id)) {
          if (k>0) rows[rows.length] = ", ";
          var link=groupView.isAllowedToSeeDetails(b.id);          
          var style="";
          //Leiter & Co-Leiter
          if ((b.leiter==1)||(b.leiter==2))
            style="font-weight:bold;";
          // Supervisor
          else if (b.leiter==3)
          style="font-weight:bold;color:gray";
          // Zu l�schen
          else if (b.leiter==-1)
            style="color:gray;text-decoration:line-through;";
          // Aufnahem beantragt
          else if (b.leiter==-2)
            style="color:#3a87ad;";
          
          if (link)
            rows[rows.length] = "<a href=\"#"+b.id+"\" id=\"groupinfos"+a.id+"\" style=\""+style+"\">";
          
          rows[rows.length] = masterData.groups[b.id].bezeichnung.trim(25);
          
          if (b.leiter==-2) rows.push("?");
          
          if (masterData.groupMemberTypes[b.leiter].kuerzel!="")
            rows[rows.length] = " ("+masterData.groupMemberTypes[b.leiter].kuerzel+")";
          
          if ((masterData.groups[b.id].followup_typ_id>0) && (b.followup_count_no!=null)) {
            var i=_getPersonGroupFollowupDiffDays(b);
            var color="";
            if (i<0) color="red";
            else if (i<7) color="green";                    
            rows.push(' <small>(<i><font color="'+color+'">Step '+b.followup_count_no+", "+i+"T.</font></i>)</small>");
          }
  
          if ((groupMeetingStats!=null) && (groupMeetingStats[a.id]!=null) 
                  && (groupMeetingStats[a.id][b.id]!=null)) {
            val=Math.round(groupMeetingStats[a.id][b.id].dabei/groupMeetingStats[a.id][b.id].stattgefunden*100);
            if (val<50) 
              valtxt="<font color=\"red\">"+val+"%</font>";
            else 
              valtxt=val+"%";
            rows.push("&nbsp;<small>"+valtxt+" ");
            if (val>0) {
              date=groupMeetingStats[a.id][b.id].datum.toDateEn();
              now=new Date();
              if (now-date>30*1000*60*60*24)
                  rows.push("/ <font color=\"red\">"+date.toStringDe()+"</font>");
            }  
            rows.push("</small>");
          }
          if (link)
            rows[rows.length] ="</a>";              
          k++;
        }
      });
    }
  }
  if (masterData.settings.selectedGroupType!=-4) {
    if (masterData.auth.viewalldetails) {
      rows[rows.length] = "<td class=\"hidden-phone\">";
      if ((a.status_id!=null) && (masterData.status[a.status_id]!=null)) 
        rows.push('<font title="'+masterData.status[a.status_id].bezeichnung+'">'+masterData.status[a.status_id].kuerzel+'</font>');
      else rows.push("Id:"+a.status_id+'?');
    }
    if (masterData.fields.f_category.fields.station_id!=null) {
      rows[rows.length] = "<td class=\"hidden-phone\">";
      if ((a.status_id!=null) && (masterData.station[a.station_id]!=null)) 
        rows.push('<font title="'+masterData.station[a.station_id].bezeichnung+'">'+masterData.station[a.station_id].kuerzel+'</font>');
    }
    var bereich="";
    if (a.access!=null) {
      bereich=bereich+'<font title="';
      $.each(a.access, function (i, b) {
        if (masterData.dep[i]!=null)
          bereich=bereich+masterData.dep[i].bezeichnung+" ";
      });
      bereich=bereich+'">';
      $.each(a.access, function (i, b) {
        if (masterData.dep[i]!=null)
          bereich=bereich+masterData.dep[i].kuerzel;
      });
      bereich=bereich+'</font>';
    }
    rows[rows.length] = "<td class=\"hidden-phone\">"+bereich;
  }
  return rows.join("");
};

PersonView.prototype.makeMasterDataMultiselectFilter = function(name, start_string, data) {
  var t=this;
  var filterName="filter"+name;
  if (data==null) data=masterData[name.toLowerCase()];
  
  t.filter[filterName]=new CC_MultiSelect(data, function(id, selected) {
    masterData.settings[filterName]=this.getSelectedAsArrayString();
    churchInterface.jsendWrite({func:"saveSetting", sub:filterName, val:masterData.settings[filterName]});
    churchInterface.getCurrentView().renderList();
  });
  t.filter[filterName].setSelectedAsArrayString(start_string);
  if (name=="Status") {
    t.filter[filterName].addFunction("Mitglieder ausw&auml;hlen", function(a) {
      return a.mitglied_yn==1;
    });
  }
};

PersonView.prototype.createMultiselect = function(name, bezeichnung, data, refresh) {
  var t=this;
  var filterName="filter"+name;
  if (refresh==null) refresh=false;
  
  if ((masterData.settings[filterName]=="") || (masterData.settings[filterName]==null))
    delete masterData.settings[filterName];
  if (t.filter[filterName]==null) {
    t.makeMasterDataMultiselectFilter(name, masterData.settings[filterName], data);
    refresh=true;
  }
  if (refresh)
    t.filter[filterName].render2Div(filterName, {label:bezeichnung});    
};

PersonView.prototype.getListHeader = function() {  
  var t = this;
  t.currentTooltip=null;
  var g_id=t.filter["filterMeine Gruppen"];
  // Diese Zeilen dienen beim ersten Aufruf �ber Direkteinstieg mit Id daf�r, dass der Datenssatz
  // angelegt wird, so dass sofort PersonDetails geladen werden.
  if ((this.filter["searchEntry"]!=null) && (this.filter["searchEntry"]>0)
      && (allPersons[this.filter["searchEntry"]]==null)) {
    allPersons[this.filter["searchEntry"]]=new Object();
    allPersons[this.filter["searchEntry"]].id=this.filter["searchEntry"];
  }
  t.createMultiselect("Status", f("status_id"), masterData.status);
  t.createMultiselect("Station", f("station_id"), masterData.station);
  t.createMultiselect("Bereich", f("bereich_id"), masterData.auth.dep);
  
  tableHeader='<th><a href="#" id="sortid">Nr.</a><th><a href="#" id="sortvorname">'
       +masterData.fields.f_address.fields.vorname.text
       +'</a><th><a href="#" id="sortname">'
       +masterData.fields.f_address.fields.name.text
        +'</a>';
    
  if (masterData.settings.selectedGroupType==null)
    masterData.settings.selectedGroupType=3;

  if (masterData.settings.selectedGroupType!=-4)
    tableHeader=tableHeader+'<th class="hidden-phone"><a href="#" id="contactemail">EMail</a> / <a href="#" id="contacttel">Telefon</a>';

  // -2 = TagAnsicht  -3 = Zugrifssrechte  -4 Gruppenstatistik
  if (masterData.settings.selectedGroupType>-2)
    if (((masterData.groupTypes[masterData.settings.selectedGroupType]==null))
       || ((!masterData.auth.viewalldata) && (masterData.groupTypes[masterData.settings.selectedGroupType].anzeigen_in_meinegruppen_teilnehmer_yn==0)))
      masterData.settings.selectedGroupType=-1;

  if (masterData.settings.selectedGroupType!=-4) {
    var b = jQuery.extend({}, masterData.groupTypes);
    if (masterData.auth.viewtags) {
      var c = new Object();
      c.id=-2;
      c.bezeichnung="Tags";
      b[-2]=(_createEntry(-2, "Tags"));
    }
    if (masterData.auth.adminpersons) {
      var c = new Object();
      c.id=-3;
      c.bezeichnung="Zugriffsrechte";
      b[-3]=(_createEntry(-3, "Zugriffsrechte"));
    }
    if ((masterData.auth.viewgroupstats) || 
         (t.filter['filterMeine Gruppen']!=null && groupView.isPersonLeaderOfGroup(masterData.user_pid, t.filter['filterMeine Gruppen']))) {
      var c = new Object();
      c.id=-4;
      c.bezeichnung="Gruppentreffen";
      b[-4]=(_createEntry(-4, "Gruppentreffen"));
    }
    tableHeader=tableHeader+"<th class=\"hidden-phone\">";
    tableHeader=tableHeader+form_renderSelect({data:b, controlgroup:false, cssid:"filterGruppentyp", type:"medium", selected:masterData.settings.selectedGroupType, func:function(a) {
       // -2 = Tag-Ansicht   -4 = Gruppentreffen
        return ((a.id==-2) || (a.id==-4) || (a.anzeigen_in_meinegruppen_teilnehmer_yn==1) || (masterData.auth.viewalldata));
      }
    });
  }
  
  // -4 = pflege
  if (masterData.settings.selectedGroupType==-4) {
    if ((masterData.groups!=null) && (masterData.groups[g_id]!=null) && (masterData.groups[g_id].meetingList!=null)
        && (masterData.groups[g_id].meetingList!="get data")) {
      $.each(masterData.groups[g_id].meetingList, function(k,m) {
        if ((m.datumvon!=null) && 
            (m.datumvon.toDateEn(false).getFullYear()==t.gruppenteilnehmerdatum.getFullYear()) &&
             (m.datumvon.toDateEn(false).getMonth()==t.gruppenteilnehmerdatum.getMonth())) {
          var d=m.datumvon.toDateEn(true);
          tableHeader=tableHeader+'<th><span class="tooltip-groupmeeting" data-gruppentreffen-id="'+m.id+'" data-tooltip-id="'+g_id+'" data-datum="'+m.datumvon+'">'+d.getDate()+"."+(d.getMonth()+1)+".";
          if (d.getHours()!=0)
            tableHeader=tableHeader+" <small>"+d.getHours()+":"+((d.getMinutes()+"").length==1?"0"+d.getMinutes():d.getMinutes())+"h</small><br>";
          tableHeader=tableHeader+form_renderImage(
                {cssid:"editGruppentreffenProperties", 
                  src:(m.kommentar==null?"comment_sw.png":(m.kommentar!=""?"comment.png":"check-64.png")), 
                  width:16, 
                  data:[{name:"gruppentreffen-id",value:m.id},
                        {name:"group-id",value:g_id}
            ]})+"&nbsp;";
          var count=0;
          if (m.entries!=null) {
            $.each(m.entries, function(i,b) {
              if (b.treffen_yn==1) count++;
            });
          }
          tableHeader=tableHeader+' <small>'+count+' </small>'+form_renderImage({src:"person.png", label:"Anzahl Teilnehmer", width:12});
          if (m.anzahl_gaeste!=null)
            tableHeader=tableHeader+' <small>'+m.anzahl_gaeste+' </small>'+form_renderImage({src:"person_sw.png", label:"Anzahl G&auml;ste", width:12});
          tableHeader=tableHeader+'</span>';          
        }
      });
    }
    if (g_id!=null)
      tableHeader=tableHeader+"<th>"+form_renderImage({src:"plus.png", width:16, title:"Weiteres Datum hinzuf&uuml;gen", cssid:"addGruppenteilnehmerdatum"});
    t.renderGroupEntry();
  }
  if (masterData.settings.selectedGroupType!=-4) {    
    if (masterData.auth.viewalldetails)
      tableHeader=tableHeader+'<th class="hidden-phone"><a href="#" id="sortstatus_id" title="'+f("status_id")+'">'+f("status_id").substr(0,1);
    if (masterData.fields.f_category.fields.station_id!=null) 
      tableHeader=tableHeader+'<th class="hidden-phone"><a href="#" id="sortstation_id" title="'+f("station_id")+'">'+f("station_id").substr(0,1);
    tableHeader=tableHeader+'<th class="hidden-phone"><font title="'+f("bereich_id")+'">'+f("bereich_id").substr(0,1)+'</font>';
  }
  
  return tableHeader;
};

PersonView.prototype.msg_allDataLoaded = function (refreshListNecessary) {
  var _overdue=0;
  $.each(allPersons, function(k,a) {
    if (a.gruppe!=null) 
      $.each(a.gruppe, function(i,b) {
        if ((_isFollowUpGroup(b)) && (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))) {
          var _d = _getPersonGroupFollowupDiffDays(b);
          if (_d<0) _overdue++;
        }  
      });
  });
  if (_overdue>0) {
    if ((masterData.settings.automaticActivateFollowupOverdue==null) || (masterData.settings.automaticActivateFollowupOverdue==1))
      this.filter["followupOverdue"]=true;
    refreshListNecessary=true;
  }
  if ((this.filter['filterMeine Gruppen']>0) && (churchInterface.getCurrentView()==personView)) 
    this.renderGroupEntry();  
  
  if (churchInterface.getCurrentView()==personView) {
    if (refreshListNecessary) churchInterface.getCurrentView().renderList();
    churchInterface.getCurrentView().renderTodos();
  }
};


PersonView.prototype.messageReceiver = function(message, args) {
  var t = this;
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      this.msg_allDataLoaded(args[0]);
    }
    else if (message=="filterChanged") {
      if (churchInterface.getCurrentView()==personView)
        this.msg_filterChanged(args[0], args[1]);
    }
    else if (message=="pollForNews") {
      var refresh=false;
      $.each(args,function(k,a) {
        if ((a.domain_type=="person") && (a.schreiben_yn==1) && (a.domain_id!=-1)) {
          if (masterData.auth.viewalldata)
            if (allPersons[a.domain_id]!=null)
              churchInterface.setStatus("<small>"+a.datum.toDateEn(true).getHours()+":"
                +a.datum.toDateEn(true).getMinutes()+" - "
                +a.userid+" hat "+allPersons[a.domain_id].vorname
                +allPersons[a.domain_id].name+" editiert.</small>",true);
            else 
              churchInterface.setStatus("<small>"+a.datum.toDateEn(true).getHours()+":"
                  +a.datum.toDateEn(true).getMinutes()+" - "
                  +a.userid+" hat eine neue Person angelegt.</small>",true);
          
          if ((allPersons[a.domain_id]==null) || (allPersons[a.domain_id].details)) {
            churchInterface.jsendRead({func:"getPersonDetails", id:a.domain_id}, function(ok, json) {
              allPersons[json.id]=cdb_mapJsonDetails(json, allPersons[json.id]); 
              refresh=true;
            }, false);                    
            if ((allPersons[a.domain_id]!=null) && (allPersons[a.domain_id].inEdit!=null)) {
              allPersons[a.domain_id].inEdit.empty().remove();
              allPersons[a.domain_id].inEdit=null;
              alert("Achtung, die Person wurde eben von '"+a.userid+"' editiert, Daten werden nun neu geladen!");
            }
          }
        }
      });
      if (refresh)
        t.renderList();        
    }
    else
      alert("Message "+message+" unbekannt!");
  }  
};

PersonView.prototype.renderTodos = function() {
  var t=this;
  if (t.currentTodoTimer==null) 
  
  t.currentTodoTimer=window.setTimeout(function() {    
  
  // Render FollowUps in renderFilter
  var _today=0;
  var _overdue=0;
  var _gruppenantrag=0;
  var _gruppenloeschung=0;
  var rows = new Array();
  
  $.each(allPersons, function(k,a) {
    if ((a!=null) && (a.gruppe!=null)) 
      $.each(a.gruppe, function(i,b) {
        if ((_isFollowUpGroup(b)) && (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))) {
          var _d = _getPersonGroupFollowupDiffDays(b);
          if (_d<0) _overdue++;
          else if (_d<14) _today++;
        }  
        if ((b.leiter==-2) && (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))) {
          _gruppenantrag++;
        }
        if ((b.leiter==-1) && 
            ((groupView.isPersonSuperLeaderOfGroup(masterData.user_pid, b.id)))) {
          _gruppenloeschung++;
        }
      });
  });
  var renderList=false;
  if (_today==0) renderList=renderList||t.deleteFilter("followupToday");
  if (_overdue==0) renderList=renderList||t.deleteFilter("followupOverdue");
  if (_gruppenantrag==0) renderList=renderList||t.deleteFilter("groupSubscribe");
  if (_gruppenloeschung==0) renderList=renderList||t.deleteFilter("groupDelete");
  if (renderList) t.renderList();
  

  if ((_today>0) || (_overdue>0) || (_gruppenantrag>0) || (_gruppenloeschung>0)) {
    var form = new CC_Form();
    form.surroundWithDiv("well");
    form.setHelp("ChurchDB-Aufgaben");
    //rows.push('<div class="well">'); //style="background:#e7eef4;border:solid 1px lightgray;font-size:8pt;padding-left:5px">');
    form.addHtml("<p>Meine Aufgaben</p>");
    if (_overdue>0)
      form.addCheckbox({controlgroup:false,controlgroup_class:"s", checked:t.getFilter("followupOverdue"), cssid:"followupOverdue", label:'&uuml;berf&auml;llige FollowUps <span class="pull-right badge badge-warning">'+_overdue+'</span>'});
    if (_today>0)
      form.addCheckbox({controlgroup:false,controlgroup_class:"s", checked:t.getFilter("followupToday"), cssid:"followupToday", label:'n&auml;chste FollowUps <span class="pull-right badge badge-success">'+_today+'</span>'});
    if (_gruppenantrag>0)
      form.addCheckbox({controlgroup:false,controlgroup_class:"s", checked:t.getFilter("groupSubscribe"), cssid:"groupSubscribe", label:'Antrag Gruppenteilnahme &nbsp;<span class="pull-right badge badge-info">'+_gruppenantrag+'</span>'});
    if (_gruppenloeschung>0)
      form.addCheckbox({controlgroup:false,controlgroup_class:"s", checked:t.getFilter("groupDelete"), cssid:"groupDelete", label:'L&oumlschung Gruppenteilnahme &nbsp;<span class="pull-right badge badge-important">'+_gruppenloeschung+'</span>'});
    
    rows.push(form.render());
  } 
  else if ((t.filter["followupToday"]!=null) || (t.filter["followupOverdue"]!=null)) {
    alert("Vielen Dank! Followup ist heute abgeschlossen.");    
    delete t.filter["followupToday"];
    delete t.filter["followupOverdue"];
  }
  $("#cdb_todos").html(rows.join(""));  
  t.implantStandardFilterCallbacks(this, "cdb_todos");
  t.currentTodoTimer=null;
  },50);

};


PersonView.prototype.renderFilter = function() {
  var t = this;
  var rows = new Array();
  
  var form = new CC_Form();
  form.setHelp("ChurchDB-Filter");
  //form.setLabel();
  
  var ret=t.getMyGroupsSelector(true);
  var img="&nbsp; ";
  if (user_access("complex filter")) {
    img=img+form_renderImage({
      label: "Aktuelle Filter als intelligente Gruppe speichern",
      cssid:"saveMyFilter", 
      src:'save.png',
      htmlclass: "small"
    });
    img=img+"&nbsp;"
  }
  if (groupView.isPersonLeaderOfGroup(masterData.user_pid, this.filter["filterMeine Gruppen"])) {
    img=img+form_renderImage({label:"Gruppentreffen pflegen", cssid:"maintaingroupmeeting", src:"persons.png", width:20});  
    img=img+"&nbsp;"
  }
  if (user_access("complex filter")) {
    if ((typeof this.filter["filterMeine Gruppen"]=="string") && (this.filter["filterMeine Gruppen"].indexOf("filter")==0)) {
      img=img+form_renderImage({
        label:"Intelligente Gruppe entfernen",
        cssid:"delIntelligentGroup",
        src:masterData.modulespath+'/images/trashbox.png',
        htmlclass:"small"
      });
    }
  }
  form.addSelect({
    data:ret, 
    label:"Meine Gruppen", 
    sort:false,
    cssid:"filterMeine Gruppen",
    selected: personView.filter['filterMeine Gruppen'],
    type:"medium",
    html:img
  });  
    
  if (masterData.auth.viewalldetails) {
    form.addHtml('<div id="filterStatus"></div>');
  }
  if (masterData.fields.f_category.fields.station_id!=null) {
    form.addHtml('<div id="filterStation"></div>');
  }
  form.addHtml('<div id="filterBereich"></div>');
  form.addCheckbox({cssid:"searchChecked",label:"markierte"});  
  rows.push(form.render(true, "inline"));
  
  
  $("#cdb_filter").html(rows.join(""));

  t.createMultiselect("Status", f("status_id"), masterData.status, true);
  t.createMultiselect("Station", f("station_id"), masterData.station, true);
  t.createMultiselect("Bereich", f("bereich_id"), masterData.auth.dep, true);
  
  // Setze die Werte auf die aktuellen Filter
  $.each(this.filter, function(k,a) {
    $("#"+k).val(a);
  });

   
  // Callbacks 
  this.implantStandardFilterCallbacks(this, "cdb_filter");

  var t=this;

  
  
  $("#cdb_filter a").click(function(c) {
    if ($(this).attr("id")=="atoggleViews") {
      $("#cdb_search").toggle();
      t.renderFilter();
      return false;
    }    
    else if ($(this).attr("id")=="delIntelligentGroup") {
      if (confirm("Wirklich die intelligente Gruppen "+t.filter["filterMeine Gruppen"].substr(6,99)+" entfernen?")) {
        churchInterface.jsendWrite({func:"delMyFilter", name:t.filter["filterMeine Gruppen"].substr(6,99)});
        delete masterData.settings.filter[t.filter["filterMeine Gruppen"].substr(6,99)];
        delete t.filter["filterMeine Gruppen"];
        masterData.settings.selectedMyGroup=null;
        churchInterface.jsendWrite({func:"saveSetting", sub:"selectedMyGroup", val:"null"});

        t.resetPersonFilter();
        t.resetGroupFilter();
        t.renderFilter();
        t.renderFurtherFilter();
        t.renderList();
      }
    }
    else if ($(this).attr("id")=="maintaingroupmeeting") {
      masterData.settings.selectedGroupType=-4;
      t.renderView();
      return false;
    }
    else if ($(this).attr("id")=="saveMyFilter") {
      var rows = new Array();
      rows.push('Bitte einen Namen f&uuml;r die intelligente Gruppe eingeben<br/>');
      rows.push("Name: <input type=\"text\" id=\"inputName\" class=\"cdb-textfield\"><br/><br/>");
      rows.push('<small><i>Die aktuelle Filtereinstellung kann als "intelligente Gruppe" abgespeichert werden. Diese Gruppe enth&auml;lt dann keine festen Verbindung zu Personen, sondern beim Aufruf werden alle Personen gefiltert, die zu dieser Gruppe passen. Der Aufruf ist dann &uuml;ber "Meine Gruppen" m&ouml;glich.</i></small><br/><br/>');
      
      
      t.showDialog("Filter speichern", rows.join(""), 400, 400, {
          "Speichern": function() {
            var name=$("#inputName").val();
            t.filter.filterStatus=t.filter.filterStatus.getSelectedAsArrayString();
            t.filter.filterStation=t.filter.filterStation.getSelectedAsArrayString();
            t.filter.filterBereich=t.filter.filterBereich.getSelectedAsArrayString();
            churchInterface.jsendWrite({func:"saveMyFilter", name:name, filter:t.filter}, null, false);
            t.makeMasterDataMultiselectFilter("Status", masterData.settings.filterStatus);
            t.makeMasterDataMultiselectFilter("Station", masterData.settings.filterStation);
            t.makeMasterDataMultiselectFilter("Bereich", masterData.settings.filterBereich, masterData.auth.dep);
            t.filter["filterMeine Gruppen"]="filter"+name;
            t.furtherFilterVisible=false;
            cdb_loadMasterData(function() {
              t.renderFilter();
              t.renderFurtherFilter();
              listOffset=0;
              t.renderList();
            });
            $(this).dialog("close");
          },
          "Abbrechen": function() {
            $(this).dialog("close");
          }
      });
      renderList=false;
      return false;
    }
  });
};


function _checkGroupFilter(a, filter, z) {
  function _checkDate(z,k) {
    if (k.d==null) return false;
    
    if ((filter["filterGruppeInAb "+z]!=null) && (k.d.toDateEn()<filter["filterGruppeInAb "+z].toDateDe()))
      return false;
    if ((filter["filterGruppeInSeit "+z]!=null) && (k.d.toDateEn()>filter["filterGruppeInSeit "+z].toDateDe()))
      return false;
    
    return true;
  }

  // Filter "In" der Gruppe
  if ((filter["filterFilter "+z]==null) || (filter["filterFilter "+z]==0)) {
    var dabei=true;
    if ((filter["filterTyp "+z]!=null) && (filter["filterTyp "+z]!="")) {
      if (a.gruppe==null) return false;
      dabei=false;
      $.each(a.gruppe, function (b,k) {
        if (filter["filterTyp "+z]==masterData.groups[k.id].gruppentyp_id)
          dabei=_checkDate(z,k);              
      });
      if (!dabei) return false;
    }
    if (filter["filterGruppe "+z]!=null) {
      if (a.gruppe==null) return false;
      dabei=false;
      $.each(a.gruppe, function (b,k) {
        if ((filter["filterGruppe "+z]!=null) && (filter["filterGruppe "+z]!="")) {
          if (k.id==filter["filterGruppe "+z]) dabei=_checkDate(z,k);
        } 
      });
      if (!dabei) return false;
    }
    if ((filter["filterDistrikt "+z]!=null) && (filter["filterDistrikt "+z]!="")) {
      if (a.gruppe==null) return false;
      else {
        dabei=false;
        $.each(a.gruppe, function (b,k) {
          if ((masterData.groups[k.id]!=null) && (masterData.groups[k.id].distrikt_id==filter["filterDistrikt "+z])
              && (((filter["filterTyp "+z]=="") || (filter["filterTyp "+z]==null) || (filter["filterTyp "+z]==masterData.groups[k.id].gruppentyp_id))))
              dabei=_checkDate(z,k);
        });
        if (!dabei) return false;
      }
    }
    if ((filter["filterTeilnehmerstatus "+z]!=null)) {
      if (a.gruppe==null) return false;
      else {
        dabei=false;
        // Hier ist kein Zusammenhang zwischen den anderen, d.h. hier mu� ich auch noch suchen, 
        // wer z.B. Leiter bei dem Distrikt ist
        $.each(a.gruppe, function (b,k) {
          if (filter["filterTyp "+z]=="" || filter["filterTyp "+z]==null || filter["filterTyp "+z]==masterData.groups[k.id].gruppentyp_id)
            if ((filter["filterGruppe "+z]==null) || (filter["filterGruppe "+z]==k.id))
              if ((filter["filterDistrikt "+z]==null) || (filter["filterDistrikt "+z]==masterData.groups[k.id].distrikt_id))
            if (k.leiter==filter["filterTeilnehmerstatus "+z]) dabei=_checkDate(z,k);
        });
        if (!dabei) return false;
      }
    }
  }


  // Filter "Nicht in" der Gruppe
  else if ((filter["filterFilter "+z]!=null) && (filter["filterFilter "+z]==1)) {
    if (a.gruppe!=null) {
      dabei=false;
      $.each(a.gruppe, function (b,k) {
        // Erstmal mu� der Typ stimmen
        if (filter["filterTyp "+z]!="" && filter["filterGruppe "+z]==null) {
          if (masterData.groups[k.id].gruppentyp_id==filter["filterTyp "+z]) {
            // Wenn auch noch Leitertyp angegeben ist?
            if (filter["filterTeilnehmerstatus "+z]!=null) {
              if (filter["filterTeilnehmerstatus "+z]==k.leiter) dabei=true;
            } 
            else dabei=true;
          }  
        }

        // Gruppen angegeben 
        if ((filter["filterGruppe "+z]!=null) && (filter["filterGruppe "+z]!="")) {
          if (k.id==filter["filterGruppe "+z]) {
            // Wenn auch noch Leitertyp angegeben ist?
            if (filter["filterTeilnehmerstatus "+z]!=null) {
              if (filter["filterTeilnehmerstatus "+z]==k.leiter) dabei=true;
            } 
            else dabei=true;
          }  
        }
        // Distrikt angegeben?
        else if ((filter["filterDistrikt "+z]!=null) && (filter["filterDistrikt "+z]!="")) {
          if (masterData.groups[k.id].distrikt_id==filter["filterDistrikt "+z]) {
            // Wenn auch noch Leitertyp angegeben ist?
            if (filter["filterTeilnehmerstatus "+z]!=null) {
              if (filter["filterTeilnehmerstatus "+z]==k.leiter) dabei=true;
            } 
            else dabei=true;
          }
        }
        // Nur Leitertyp angegeben?
        else if (filter["filterTeilnehmerstatus "+z]!=null) {
          if (filter["filterTeilnehmerstatus "+z]==k.leiter) dabei=true;
        } 
         
      });
      if (dabei) return false;
    }  
  }
  
  
  // Filter "War in" der Gruppe
  else if ((filter["filterFilter "+z]!=null) && (filter["filterFilter "+z]==2)) {
    var dabei=false;
    // Erst alte Gruppen (Gruppenarchiv) durchsuchen
    if (a.oldGroups!=null) {
      arr2=new Object();
      $.each(a.oldGroups, function(k,og) {
        if (og.id==a.id) {
          if (((filter["filterGruppe "+z]!=null) && (og.gp_id==filter["filterGruppe "+z]))
              || ((filter["filterDistrikt "+z]!=null) && (filter["filterGruppe "+z]==null) 
                   && (masterData.groups[og.gp_id]!=null) && (masterData.groups[og.gp_id].distrikt_id==filter["filterDistrikt "+z])) 
              || ((filter["filterGruppe "+z]==null) && ((filter["filterDistrikt "+z])==null) 
                 && (masterData.groups[og.gp_id]!=null) && (masterData.groups[og.gp_id].gruppentyp_id==filter["filterTyp "+z]))) {
            if ((filter["filterGruppeWarInVon "+z]==null) && (filter["filterGruppeWarInBis "+z]==null)) {
              // Ohne Datum mu� ich nur schauen, dass es einen Status -99 gibt, also dass er raus ist.
              // bzw. ob der Teilnehmerstatus erf�llt wurde, 
              if (((filter["filterTeilnehmerstatus "+z]==null) && (og.leiter==-99))
                || (og.leiter==filter["filterTeilnehmerstatus "+z])) dabei=true;
            } 
            else {
              // Wenn es ein Datum gibt, dann speichere ich mir die Daten f�r jede Gruppen-ID in ein Array, s.u. 
              if (arr2[og.gp_id]==null) arr2[og.gp_id]=new Array();
              arr2[og.gp_id].push(og);                
            }              
          }
          
        }                            
      });
      // Wenn es Datumsfilterung gibt, dann m�ssen wir alle alten Eintr�ge sortieren und interpretieren
      if (filter["filterGruppeWarInBis "+z]!=null) {
        $.each(arr2, function(k,og) {
          // Sortiere nach Datum
          var b=churchcore_sortData(og,"d");
          var startdatum="";
          $.each(b, function(i,c) {            
            // Entweder wird nicht nach TNStatus gefiltert, dann ist >0 wichtig, oder es mu� eben vergleichen werden.
            if (((filter["filterTeilnehmerstatus "+z]==null) && (c.leiter>=0))
                || (filter["filterTeilnehmerstatus "+z]==c.leiter)) 
              startdatum=c.d;
            // Wenn auch ein -99 da ist bzw. der TN-Status sich ge�ndert hat, dann haben wir einen g�ltiges Bundle, also los!
            else if ((startdatum!="") && ((c.leiter==-99) || filter["filterTeilnehmerstatus "+z]!=null)) {
              if (((filter["filterGruppeWarInBis "+z]==null) || (startdatum.toDateEn()<=filter["filterGruppeWarInBis "+z].toDateDe()))
                 &&
                 ((filter["filterGruppeWarInVon "+z]==null) || (c.d.toDateEn()>=filter["filterGruppeWarInVon "+z].toDateDe())))
                dabei=true;
              startdatum=""; 
            }
            
          });
        });
      }
    }
    // Wenn Datumsfilter gesetzt kann es sein, dass die Person ja auch noch in der Gruppe ist
    if ((!dabei) && (filter["filterGruppeWarInBis "+z])) {
      if (a.gruppe!=null) {
        $.each(a.gruppe, function(b,k) {
          // Gruppen angegeben 
          if (filter["filterGruppe "+z]!=null) {
            if (k.id==filter["filterGruppe "+z]) {
              if ((k.d.toDateEn()<=filter["filterGruppeWarInBis "+z].toDateDe()))
                if ((filter["filterTeilnehmerstatus "+z]==null) || (filter["filterTeilnehmerstatus "+z]==k.leiter))
                  dabei=true;
            }  
          }
          if ((filter["filterDistrikt "+z]!=null) && (masterData.groups[k.id]!=null)) {
            if (masterData.groups[k.id].distrikt_id==filter["filterDistrikt "+z]) 
              if ((k.d.toDateEn()<=filter["filterGruppeWarInBis "+z].toDateDe()))
                if ((filter["filterTeilnehmerstatus "+z]==null) || (filter["filterTeilnehmerstatus "+z]==k.leiter))
                dabei=true;
          }  
        });
      }
    }
    if (!dabei) return false;
  }
  return true;
}

function checkKommentar(gruppen, str) {
  var r=false;
  if (gruppen!=null)
  $.each(gruppen, function(k,g) {
    if ((g.comment!=null) && (g.comment.toUpperCase().indexOf(str)!=-1))
      r=true;
  });
  return r;
}

PersonView.prototype.checkFilter = function(a) {
  var filter=this.filter;
  var t=this;
  // Person wurde geloescht o.ae.
  if (a==null) return false;
  if ((t.name=="PersonView") && (a.archiv_yn==1)) return false;
  if ((t.name=="ArchiveView") && (a.archiv_yn==0)) return false;
  // Es gibt noch keine Daten, soll er aber laden ueber Details
  if (a.name==null) return true;
  
  if ((masterData.settings.hideStatus!=null) && (a.status_id!=null) && (a.status_id==masterData.settings.hideStatus))
    return false;

  // Suchfeld
  var searchEntry=this.getFilter("searchEntry").toUpperCase();
  if (searchEntry!="") {
    
    // Split by " ", but not masked with a "
    searches=searchEntry.match(/(?:[^\s"]+|"[^"]*")+/g);
    var res=true;
    $.each(searches, function(k,search) {
      search=search.replace(/"/g, "");
      if (search.indexOf("TAG:")==0) {
        if (!t.checkFilterTag(search, a.tags)) {
          res=false;          
          return false;
        }
      }
      else if (search.indexOf("#")==0) {
        if ("#"+a.id!=search) {
          res=false;
          return false;
        }
      }    
      else if (search.indexOf("GRUPPE:")==0) {
        if (a.gruppe==null) {res=false; return false;}       
        var dabei=false;
        $.each(a.gruppe, function (b,k) {
          // Nicht die anzeigen, wo ich SuperVisor bin!
          if (masterData.groups[k.id].bezeichnung.toUpperCase().indexOf(search.substr(7,99).toUpperCase())>=0) {
            dabei=true; 
            return false;
          }
        });
        if (!dabei) res=false;
        return false;    
      }
      // Wenn kein Tag, dann nun andere M�glichkeiten testen
      else if ((a.name.toUpperCase().indexOf(search)<0) &&
                 (a.vorname.toUpperCase().indexOf(search)<0) &&
                 ((a.email==null) || (a.email.toUpperCase().indexOf(search)!=0)) &&
                 (a.spitzname.toUpperCase().indexOf(search)!=0) &&
                 (a.id!=search) &&
                 (!checkKommentar(a.gruppe, search))) {
        res=false;
        return false;
      }
    });
    if (!res) return false;
  }

  if ((filter["searchChecked"]!=null) && (a.checked!=true)) return false;
  
  
  // Meine Aufgaben (FollowUps)
  if ((this.getFilter("followupOverdue")!="") || (filter["followupToday"]!=null)) {
    if (a.gruppe==null) return false;
    var _res=false;
    $.each(a.gruppe, function(k,b) {
      if ((_isFollowUpGroup(b)) && (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))) {
        var _d = _getPersonGroupFollowupDiffDays(b);
        //if (_d>0) _res=false;
        if ((filter["followupToday"]!=null) && (_d>=0) && (_d<14)) {
          _res=true;
          return false;
        }
        if ((t.getFilter("followupOverdue")!="") && (_d<0)) {
          _res=true;
          return false;
        }
      }  
    });     
    if (!_res) return false;
  }
  
  if ((filter["groupDelete"]!=null) || (filter["groupSubscribe"]!=null)) {
    if (a.gruppe==null) return false;
    var _res=false;
    $.each(a.gruppe, function(k,b) {
      if ((((b.leiter==-1) && (filter["groupDelete"]!=null))
         || ((b.leiter==-2) && (filter["groupSubscribe"]!=null)))
          && (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))) { 
        _res=true;
        return false;
      }
    });     
    if (!_res) return false;
  }
 
  // Filter der eigenen Gruppen, Bereiche, Status und Station
  if ((filter["filterMeine Gruppen"]>0) || (filter["filterMeine Gruppen"]<0)) {
    if (a.gruppe==null) return false;       
    dabei=false;
    $.each(a.gruppe, function (b,k) {
      // Nicht die anzeigen, wo ich SuperVisor bin!
      if ((k.id==filter["filterMeine Gruppen"]) && (k.leiter!=3)) {
        dabei=true; 
        return false;
      }
    });
    if (!dabei) return false;     
  }

  if (this.filter["isMember"]!=null) {
    if (masterData.status[a.status_id]!=null && (masterData.status[a.status_id].mitglied_yn==0))
      return false;    
  } 
  
  if ((this.filter["filterStatus"]!=null) && (this.filter["filterStatus"].filter(a.status_id)))
    return false;
  if ((this.filter["filterStation"]!=null) && (this.filter["filterStation"].filter(a.station_id)))
    return false;
  
  if ((filter["filterBereich"]!=null && filter["filterBereich"].isSomethingSelected())) {
    dabei=false;
    if (a.access!=null)
    $.each(a.access, function (b,k) {
      if (!t.filter["filterBereich"].filter(k)) dabei=true;
    });
    if (!dabei) return false;
  }
  
  if (filter["withoutPicture"]!=null) {
    if ((a.imageurl!=null) && (a.imageurl!="nobody.gif")) return false;
  }
  
  if (filter["withoutEMail"]!=null) {
    if ((a.email!=null) && (a.email!="")) return false;
  }
  
  // Filter der Gruppen, Hauskreis und Distrikte
  var z=1;
  var or=true;
  if (filter["filterOr"]!=null)
    or=false;

  while (filter["filterOn "+z]!=null) {
    var res=_checkGroupFilter(a, filter, z);
    // UND-Verkn�pfung
    if (filter["filterOr"]==null) {
      if (!res) return false;
    }  
    // ODER-Verkn�pfung
    else if (res) or=true;   
    z=z+1;  
  }
  if (!or) return false;

    
  // Searchables, also alle Infos, die Nachgeladen werden.
  if (a.searchable) {
    // Filter Zugriffsrechte
    if (filter["filterAuth"]!=null) {
      if (filter["filterAuthNegativ"]==null) {
        if ((a.auth==null) || (!hasPersonAuth(a, filter["filterAuth"]))) return false;
      }
      else {
        if ((a.auth!=null) && (hasPersonAuth(a, filter["filterAuth"]))) return false;
      }      
    }
    if ((filter["filterFamilienstatus"]!=null)
      && ((a.familienstand_no==null)||(a.familienstand_no!=filter["filterFamilienstatus"]))) return false;
    if ((filter["filterGeschlecht"]!=null)
      && ((a.geschlecht_no==null)||(a.geschlecht_no!=filter["filterGeschlecht"]))) return false;

    if ((filter["plz"]!=null)
      && ((a.plz==null)||(!a.plz.match("^"+filter["plz"])))) return false;
    if ((filter["geburtsort"]!=null)
        && ((a.geburtsort==null)||(a.geburtsort.indexOf(filter["geburtsort"])==-1))) return false;
    if ((filter["filterNationalitaet"]!=null)
        && ((a.nationalitaet_id==null)||(a.nationalitaet_id!=filter["filterNationalitaet"]))) return false;
      
    if ((filter["ageFrom"]!=null) || (filter["ageTo"]!=null)) {
      if (a.geburtsdatum==null) 
        return false;
      else {
        geb=a.geburtsdatum.toDateEn();      
        if (geb.getAgeInYears().num==null) return false;
        if ((filter["ageFrom"]!=null) && (geb.getAgeInYears().num<filter["ageFrom"]))
          return false;
        if ((filter["ageTo"]!=null) && (geb.getAgeInYears().num>filter["ageTo"]))
          return false;
      }   
    }   

    if ((filter["filterDates"]!=null) && (filter["dateBefore"]!=null) && (filter["dateBefore"].toDateDe()!=null)) {
      // Enthaelt der Datensatz ueberhaupt ein Datum?
      if (a[filter["filterDates"]]!=null) {
        flt=a[filter["filterDates"]].toDateEn();
        src=filter["dateBefore"].toDateDe();
        if (filter["dateIgnoreYear"]!=null) {
          flt.setFullYear(2000);
          src.setFullYear(2000);
        }  
        if (flt>src) return false;
      } 
      else return false;
    }
    if ((filter["filterDates"]!=null) && (filter["dateAfter"]!=null) && (filter["dateAfter"].toDateDe()!=null)) {
      // Enthält der Datensatz überhaupt ein Datum?
      if (a[filter["filterDates"]]!=null) {
        flt=a[filter["filterDates"]].toDateEn();
        src=filter["dateAfter"].toDateDe();
        if (filter["dateIgnoreYear"]!=null) {
          flt.setFullYear(2000);
          src.setFullYear(2000);
        }  
        if (flt<src) return false;
      } 
      else return false;
    }   
    if ((filter["filterDates"]!=null) && (filter["dateNotSet"]!=null)) {
      if (a[filter["filterDates"]]!=null) 
        return false;
    }
    
    if (filter["filterRelations"]!=null) {
      // Wer hat die Beziehung?
      if (filter["filterRelationNegativ"]==null) {
        if (a.rels==null) return false;
        var dabei=false;
        var bt_id=filter["filterRelations"].substr(2,99);
        $.each(a.rels, function(b,k) {
          if (k.beziehungstyp_id==bt_id) {
            if (masterData.relationType[bt_id].bez_vater==masterData.relationType[bt_id].bez_kind)
              dabei=true;
            else if (((filter["filterRelations"].substr(0,1)=="k") && (k.vater_id==a.id)) ||
                ((filter["filterRelations"].substr(0,1)=="v") && (k.kind_id==a.id))) 
              dabei=true;
          }
        });
        if (!dabei) return false;
      }
      // Wer hat die Beziehung NICHT?
      else {    
        if (a.rels!=null) {
          var dabei=true;
          var bt_id=filter["filterRelations"].substr(2,99);
          $.each(a.rels, function(b,k) {
            if (k.beziehungstyp_id==bt_id) {
              if (masterData.relationType[bt_id].bez_vater==masterData.relationType[bt_id].bez_kind)
                dabei=false;
              else if (((filter["filterRelations"].substr(0,1)=="k") && (k.vater_id==a.id)) ||
                  ((filter["filterRelations"].substr(0,1)=="v") && (k.kind_id==a.id))) 
                dabei=false;
            }
          });
          if (!dabei) return false;
        }
      }
    }    
    
    if ((filter["filterRelationExt"]!=null) && (filter["filterRelationExtGroupTyp"]!=null)) {
      var dabei=false;
      if (a.rels!=null) {
        var bt_id=filter["filterRelationExt"].substr(2,99);
        $.each(a.rels, function(b,k) {
          if (k.beziehungstyp_id==bt_id) {
            var rel_pid=null;
            if (masterData.relationType[bt_id].bez_vater==masterData.relationType[bt_id].bez_kind) {
              if (k.vater_id==a.id) rel_pid=k.kind_id;
              else rel_pid=k.vater_id;
            }
            else if ((filter["filterRelationExt"].substr(0,1)=="k") && (k.vater_id==a.id))
              rel_pid=k.kind_id;
            else if ((filter["filterRelationExt"].substr(0,1)=="v") && (k.kind_id==a.id))
              rel_pid=k.vater_id;
            if ((rel_pid!=null) && (allPersons[rel_pid]!=null) && (allPersons[rel_pid].gruppe!=null)) {
              $.each(allPersons[rel_pid].gruppe, function(c,i) {
                if (filter["filterRelationExtGroup"]!=null) {
                  if (filter["filterRelationExtGroup"]==i.id) dabei=true;
                }
                else { 
                  if (filter["filterRelationExtGroupTyp"]==masterData.groups[i.id].gruppentyp_id) 
                    dabei=true;
                }
                if (dabei) return false;                              
              });
            }
          }
          if (dabei) return false;                                        
        });
      }
      return dabei;
    }
    
      
    if (filter["filterFollowUp"]!=null) {
      if ((a.gruppe==null) && (filter["filterFollowUp"]>0)) return false;
      else if (a.gruppe!=null) {
        if (filter["filterFollowUp"]>0) _res=false; else _res=true;
        $.each(a.gruppe, function(k,b) {
          if (_isFollowUpGroup(b)) {
            if (filter["filterFollowUp"]>0) {
              var _d = _getPersonGroupFollowupDiffDays(b);
              if ((filter["filterFollowUpStep"]==null) || (b.followup_count_no==filter["filterFollowUpStep"])) {
              if ((filter["filterFollowUp"]==1)) _res=true;
              else if ((filter["filterFollowUp"]==2) && (_d<0)) _res=true;
            } 
            } 
            else _res=false; 
          }  
        });     
        if (!_res) return false;
      }  
    }   
    
    var z=0;
    var res=true;
    while ((filter["filterTags"+z]!=null) && (res)) {
      var res=false;
      if (a.tags==null) return false;
      $.each(a.tags, function(k,a) {
        if (a==filter["filterTags"+z]) {
          res=true;
          return false;
        }
      });  
      z=z+1;
    }
    if (!res) return false;
  }
  return true;    
};

function hasPersonAuth(a, auth_id) {
  if ((a.auth!=null) && (a.auth[auth_id]!=null)) return true; 

  var res=false;
  if (a.gruppe!=null) {
    $.each(a.gruppe, function(k,b) {
      if ((b.leiter>=0) && (!res) && (masterData.groups[b.id].auth!=null)) {
        $.each(masterData.groups[b.id].auth, function(i,c) {
          if (c==auth_id) { 
            res=true;
            return false;
          }
        });
      }
    });
    if (res) return true;
  }
  if (masterData.status[a.status_id].auth!=null) {
    $.each(masterData.status[a.status_id].auth, function(k,b) {
      if (b==auth_id) res=true;
    });
    if (res) return true;
  }  
  return false;
}

function _renderRelationList(id,del_link) {
  var _text="";
  if (del_link==null) del_link=false;
  
  if (allPersons[id].rels!=null) {
    $.each(churchcore_sortMasterData(masterData.relationType), function(i,relType) {
      $.each(allPersons[id].rels, function (k,b) {
        if (b.beziehungstyp_id==relType.id) {
          if (b.kind_id==id) {
            _text=_text+masterData.relationType[b.beziehungstyp_id].bez_vater;
            // Checken ob er �berhaupt die Person sehen darf, kann anderer Bereich sein.
            if (allPersons[b.vater_id]!=null) {
              _text=_text+': <a href="#" class="tooltip-person" id="person_'+b.vater_id+'" '+(masterData.auth.viewalldata?'data-tooltip-id="'+b.vater_id+'">':'>')+allPersons[b.vater_id].vorname+" "+allPersons[b.vater_id].name+"</a>";
            } 
          }
          else { 
            _text=_text+masterData.relationType[b.beziehungstyp_id].bez_kind;
            // Checken ob er �berhaupt die Person sehen darf, kann anderer Bereich sein.
            if (allPersons[b.kind_id]!=null) {
              _text=_text+': <a href="#" class="tooltip-person" id="person_'+b.kind_id+'" '+(masterData.auth.viewalldata?'data-tooltip-id="'+b.kind_id+'">':'>')+allPersons[b.kind_id].vorname+" "+allPersons[b.kind_id].name+"</a>";
            }
          }
          if ((masterData.auth.write) && (del_link)) 
             _text=_text+'&nbsp;&nbsp;(<a href="#" id="del_rel_'+b.id+'">entfernen</a>)';
          _text=_text+"<br/>";
        }
      });
    });
  }
  return _text;  
}

function _renderOldGroupEntry(og) {
  var _text='<tr class="oldgroupentry"><td><p>';
  var _title="Wurde ge&auml;ndert von "+og.user+" am "+og.d.toDateEn(true).toStringDe(true);
  var _style="color:black;";
  if ((og.leiter==1) || (og.leiter==2))
    _style="color:black;font-weight:bold;";
  else if (og.leiter==3)
    _style="color:gray;";
  else if (og.leiter==-1)
    _style="color:gray;text-decoration:line-through;";                     
  else if (og.leiter==-99)
    _style="color:gray;text-decoration:line-through;";                     
  
  _text=_text+'<i><small> > <a href="#" title="'+_title+'" style="'+_style+'" id="grp_'+og.gp_id+'">'+masterData.groups[og.gp_id].bezeichnung+"</a>";
  if (og.leiter>0)
    _text=_text+" ("+masterData.groupMemberTypes[og.leiter].bezeichnung+")";
  else if (og.leiter==-99)
    _text=_text+" <b>gel&ouml;scht</b>";
          
  _text=_text+"</small><i><td><i><small>"+og.d.toDateEn().toStringDe()+"</small></i><td><td>";          
  return _text;
}

function _getOldGroupEntries(p_id, gt_id, func) {
  var _text="";
  count=0;
  $.each(allPersons[p_id].oldGroups, function(k,og) {
    if ((!og.used) && (masterData.groups[og.gp_id]!=null) && (masterData.groups[og.gp_id].gruppentyp_id==gt_id) && (func(og))) {
      _text=_text+_renderOldGroupEntry(og);
    }   
  });
  return _text;
}


PersonView.prototype.getGroupEntries = function (p_id, gt_id, func) {
  var t=this;
  var rows = new Array();
  var entries_hidden=false;
  var len=0;

  // Alle korrekten reinholen
  var res = new Array();  
  $.each(allPersons[p_id].gruppe, function(k,b) {
    if ((masterData.groups[b.id]!=null) && (masterData.groups[b.id].gruppentyp_id==gt_id) 
        && (func(b)) && (groupView.isAllowedToSeeName(b.id, p_id)))
      {
      b.show=true;
      res.push(b);
    }
  });
  
  // Sortiere nach Datum r�ckw�rts
  res.sort(function(a,b){if (a.d>b.d) return -1; else return 1;});

  // Nehem soviele heraus, bis 4 �brig bleiben
  if (t.showGroupDetailsId!=gt_id) {    
    var count=res.length;
    var d=new Date(); d.addDays(-1*masterData.groupnotchoosable);
    var i=res.length;
    
    // Erst die Filtern, bei denen die Gruppe ein Abschlu�datum hat
    while ((i>0) && (count>4)) {
      i=i-1;
      if ((masterData.groups[res[i].id].abschlussdatum!=null) && (masterData.groups[res[i].id].abschlussdatum.toDateEn()<d)) {
        res[i].show=false;
        count=count-1;
        entries_hidden=true;
      }  
    }
    
    // Dann von hinten nach vorne filtern
    var i=res.length;
    while ((i>0) && (count>4)) {
      i=i-1;
      res[i].show=false;
      count=count-1;
      entries_hidden=true;
    }
  }  
  
  $.each(res, function(k,b) {
    if (b.show) {
      _text='<tr style="background:#F4F4F4;"><td><p>';
      var _title="Seit ";
      if (b.d!=null) _title=_title+b.d.toDateEn().toStringDe();
      _title=_title+" von "+b.user;
        var _style="color:black;";
        if ((b.leiter==1) || (b.leiter==2))
          _style="color:black;font-weight:bold;";
        else if (b.leiter==3)
          _style="color:gray;";
        else if (b.leiter==-2)
          _style="color:#3a87ad;";
        else if (b.leiter==-1)
          _style="color:red;text-decoration:line-through;";                     

      var grptxt=masterData.groups[b.id].bezeichnung;
      if (b.leiter==-2) grptxt=grptxt+"?";
      
      if (groupView.isAllowedToSeeDetails(b.id))
        _text=_text+'<small><a href="#" title="'+_title+'" style="'+_style+'" id="grp_'+b.id+'">'+grptxt+"</a>";
      else if (b.leiter==-1) 
        _text=_text+'<small style="text-decoration:line-through;">'+grptxt;
      else
        _text=_text+'<small>'+grptxt;
      
      
      if ((b.comment!=null) && ((masterData.auth.editgroups) || (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))))
        _text=_text+"&nbsp;"+t.renderImage("comment",16,"Kommentar: "+b.comment);
      
      if ((masterData.auth.adminpersons) && (masterData.groups[b.id].auth!=null)) 
        _text=_text+"&nbsp;"+t.renderImage("schluessel",16,"Berechtigungen: "+t.getAuthAsArray(masterData.groups[b.id].auth).join(", "));
        if (b.leiter>0)
          _text=_text+" ("+masterData.groupMemberTypes[b.leiter].bezeichnung+")";
        else if (masterData.groups[b.id].followup_typ_id>0)
          _text=_text+' <i>(FollowUp: "'+masterData.followupTypes[masterData.groups[b.id].followup_typ_id].bezeichnung.trim(20)+')</i>';
        
      _text=_text+"</small><td><p><small>"+(b.d!=null?b.d.toDateEn().toStringDe():"")+"</small>";
      
      
      if ((masterData.auth.editgroups) || (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))) {
        _text=_text+'<td style="padding:0;width:16px;"><a href="#" id="editPersonGroupRelation'+b.id+'" grouptype="'+a.id+'">'+t.renderImage("options",16)+'</a>';
        _text=_text+'<td style="padding:0;width:16px;"><a href="#" id="delPersonGroupRelation'+b.id+'">'+t.renderImage("trashbox",16)+'</a>';
      }  
      
      if ((t.showGroupDetailsId==gt_id) && (t.showGroupDetailsWithHistory) && (allPersons[p_id].oldGroups!=null)) { 
        $.each(allPersons[p_id].oldGroups, function(i,c) {
          if (c.gp_id==b.id) {
            c.used=true;
            _text=_text+_renderOldGroupEntry(c);
    }   
  });
  }
      rows.push(_text);      
    }
  });
  
  if (entries_hidden)   
    rows.push('<tr style="background:#F4F4F4;"><td><a href="#" id="f_gruppe_showmore_'+gt_id+'">...</a><td><td><td>');

  return rows.join("");
  
};

/**
 * Ist es �berhaupt eine Followup-Gruppe?
 * group=Person.group
 */
function _isFollowUpGroup(group) {
  if ((masterData.groups[group.id]!=null) && (masterData.groups[group.id].followup_typ_id>0) && (group.followup_count_no>0))
    return true;
  else
    return false;  
}


function _getFollowupDiffDays(followup_typ_id, followup_count_no) {
  var _res;
  $.each(masterData.followupTypIntervall, function(k,a) {
    if ((a.followup_typ_id==followup_typ_id) && (a.count_no==followup_count_no))
      _res=a.days_diff*1;  //*1, damit er es als Nummer umrechnet und nicht als String interpretiert
  });
  return _res;
}

function _getNextFollowupCountNo(followup_typ_id, followup_count_no) {
  var _res=null;
  followup_count_no++;
  $.each(masterData.followupTypIntervall, function(k,a) {
    if ((a.followup_typ_id==followup_typ_id) && (a.count_no==followup_count_no))
      _res=a.count_no;
  });
  return _res;
}

/**
 * 
 * @param group - Ein Gruppeneintrag von allPerson.groups[]
 * @return null oder Anzahl Tage von heute bis zum FollowUp, negativ wenn schon �berf�llig
 */
function _getPersonGroupFollowupDiffDays(group) {
  if (!_isFollowUpGroup(group))
    return null;
  else {
    var _last_date=group.d.toDateEn();
    var _diff=_getFollowupDiffDays(masterData.groups[group.id].followup_typ_id, group.followup_count_no);
    if (group.followup_add_diff_days!=null)
      _diff=_diff+group.followup_add_diff_days*1;
    _last_date.addDays(_diff);
    var _diff_days=Math.round((_last_date-new Date())/(1000*60*60*24))+1;
    return _diff_days;
  }
}

PersonView.prototype.invitePerson = function(a) {
  if (a.email=="")
    alert("Ohne E-Mail-Adresse kann die Person nicht eingeladen werden!");
  else if (confirm("Wirklich "+a.vorname+" "+a.name+" einladen? Die Person bekommt eine E-Mail mit einem Link, wo sie dann das Passwort erstellen kann.")) {
    churchInterface.jsendWrite({func:"sendInvitationMail", id:a.id}, function(ok) {
      if (ok) {
        alert("Einladung wurde gesendet.");
        a.einladung=1;
        t.renderDetails(a.id);
      }
      else alert("Fehler: "+res);
    });      
  }
};

PersonView.prototype.renderAuthDialog = function (id) {
  var t=this;
  var _text="";
  _text=_text+"<h3>Anpassung der Berechtigungen</h3><br/>";
  if (a.active_yn==1) {
    _text=_text+"<p>"+t.renderImage("schluessel")+'&nbsp;<a href="" title="Berechtigung editieren" id="personAuth">Berechtigungen anpassen</a><small> - Personen k&ouml;nen hier direkte Rechte zugewiesen werden</small>';
    _text=_text+"<p>"+t.renderImage("person_simulate")+"&nbsp;<a href=\"#\" title=\"Person simulieren\" id=\"simulatePerson\">"+"Person simulieren</a><small> - Berechtigung der Person testen</small>";
    _text=_text+"<p>"+t.renderImage("person")+"&nbsp;<a href=\"#\" title=\"Person per E-Mail einladen\" id=\"invitatePerson\">Person einladen</a><small> - Person erh&auml;lt per E-Mail einen Anmeldelink.</small>";
    _text=_text+"<p>"+t.renderImage("trashbox")+"&nbsp;<a href=\"#\" title=\"Zugang sperren\" id=\"deactivatePerson\">Zugang sperren</a><small> - Berechtigungen entfernen und der Person den Zugang sperren.</small>";
    _text=_text+"<p>"+t.renderImage("attention")+"&nbsp;<a href=\"#\" title=\"Passwort zur&uuml;cksetzen\" id=\"setPassword\">Passwort zur&uuml;cksetzen</a><small> - Der Person ein Passwort setzen.</small>";
  }
  else 
    _text=_text+"<p>"+t.renderImage("schluessel")+'&nbsp;<a href="" title="Zugang erlauben" id="activatePerson">Zugang erlauben</a><small> - Personen wieder den Zugang erlauben</small>';
  
  _text=_text+"<br/><br/><p><i><b>Hinweis: </b>";
  if (a.active_yn==0)
    _text=_text+"<font color=red>Zugang f&uuml;r Person gesperrt!</font>";
  else if (a.lastlogin!=null)
    _text=_text+"Person war zuletzt online am "+a.lastlogin.toDateEn(true).toStringDe(true);
  else if (a.einladung==1)
    _text=_text+"Person ist bereits eingeladen";
  else 
    _text=_text+"Person wurde noch nie eingeladen";

  var elem = t.showDialog("Anpassung der Berechtigungen", _text, 500, 380, {
      "Schließen": function() {
        $(this).dialog("close");
      }
  });
  
  elem.find("a").click(function() {
    if ($(this).attr("id")=="simulatePerson") {
      window.location.href="?q=simulate&id="+a.id+"&location=churchdb";
      return false;
    }
    else if ($(this).attr("id")=="invitatePerson") {
      t.invitePerson(a);
      return false;
    }
    else if ($(this).attr("id")=="personAuth") {
      t.editPersonAuth(id);
      return false;
    }         
    else if ($(this).attr("id")=="deactivatePerson") {
      if (confirm("Wirklich "+a.vorname+" "+a.name+" den Zugang sperren? Der Zugang ist erst wieder nach einer neuen Einladung aktiv.")) {
        churchInterface.jsendWrite({func:"deactivatePerson", id:a.id}, function(ok, status) {
          if (ok) {
            alert("Person wurde gesperrt.");
            a.active_yn=0;
            a.auth=null;
            elem.dialog("close");
            t.renderDetails(a.id);
          }
          else alert("Fehler: "+status);
        });      
      }
      return false;
    }
    else if ($(this).attr("id")=="activatePerson") {
      churchInterface.jsendWrite({func:"activatePerson", id:a.id}, function(ok, status) {
        if (ok) {
          alert("Der Zugang ist wieder erlaubt. Sinnvoll kann es nun sein, eine Einladung zu senden.");
          a.active_yn=1;
          elem.dialog("close");
          t.renderDetails(a.id);
        }
        else alert("Fehler: "+status);
      });      
      return false;
    }    
    else if ($(this).attr("id")=="setPassword") {
      var _text=form_renderInput({label:"Neues Passwort", type:"medium", password:true, cssid:"new_password"});
      var elem2 = t.showDialog("Neues Passwort setzen", _text, 250, 200, {
         "Speichern": function() {
          churchInterface.jsendWrite({func:"setPersonPassword", id:a.id, password:$("#new_password").val()}, function(ok, status) {
            if (ok) {
              alert("Das Passwort wurde gesetzt.");
              elem2.dialog("close");
            }
            else alert("Fehler: "+status);
          });                
          $(this).dialog("close");
        },
        "Abbrechen": function() {
          $(this).dialog("close");
        }
      });      
      return false;
    }
  });
};


PersonView.prototype.renderDetails = function (id) {
  var t=this;
  if ($("#detailTD"+id).length==0) return null;
  
  $("#detailTD"+id).html("Rendern...");
  a=allPersons[id];
  a.open=true;

  // Feststellen, ob aktueller User ein Leiter einer Gruppe ist, in der die Person ist. 
  // => Dann hat der User Schreibrechte!
  personLeader=false;
  personSuperLeader=false;
  if ((allPersons[id].gruppe!=null) && (masterData.user_pid!=null) && (allPersons[masterData.user_pid].gruppe!=null)) {
    $.each(allPersons[masterData.user_pid].gruppe, function (b,k) {    
      if ((k.leiter>=1) && (k.leiter<=2)) {
        $.each(allPersons[id].gruppe, function(c,l) {
          if ((l.id==k.id) && (masterData.groups[k.id]!=null)) {
            var d = new Date;
            d.addDays(-masterData.groupnotchoosable);
            // Schauen, dass Gruppe noch nicht abgeschlossen ist!
            if ((masterData.groups[k.id].abschlussdatum==null) || (masterData.groups[k.id].abschlussdatum.toDateEn()>d))
              personLeader=true;
          }
        });
      }
    });
  }
  // Check if I am SuperLeader (Distrikt- or Gruppentypleiter), for acting as a leader
  if (allPersons[id].gruppe!=null) {
    $.each(allPersons[id].gruppe, function (b,k) {    
      if (groupView.isPersonSuperLeaderOfGroup(masterData.user_pid, k.id)) {
        personLeader=true;
        personSuperLeader=true;        
      }
    });    
  }

  var rows = new Array();
  rows.push('<div id="detail" class="detail-view-person">');
    
      // FollowUp in renderDetails  
      var _text="";
         
      if ((masterData.auth.viewalldetails) || (personLeader)) {  
        var _follow="";
        var _current=new Date();
        if (a.gruppe!=null) {
          $.each(a.gruppe, function(k,b) {
            if ((masterData.auth.viewalldata) || (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id))) {
              var _diff_days=_getPersonGroupFollowupDiffDays(b);
              if (_diff_days!=null) {
                var _info="";
                var _color="";
                if (_diff_days>0) { 
                  _color="background:lightgreen;border:solid 2px;border-color:white;";
                  _info="Ist f&auml;llig in "+_diff_days+" Tagen";
                }
                else if (_diff_days==0) {
                  _color="background:orange;border:solid 0px;border-color:gray;";
                  _info="<b>Ist heute f&auml;llig!</b>";
                }
                else {
                  _color="background:#FFAAAA;border:solid 2px;border-color:red;color:black;";
                  _info="<b>Ist f&auml;llig seit "+(-_diff_days)+" Tagen!</b>";
                }
                var _last_date=new Date(); 
                _last_date.addDays(_diff_days);
                          
                _text=_text+'<div style="'+_color+'padding:4px 8px 4px 8px">FollowUp <i>"'
                           +masterData.followupTypes[masterData.groups[b.id].followup_typ_id].bezeichnung
                           +' ('+b.followup_count_no+')"</i> von '+masterData.groups[b.id].bezeichnung+": "
                           + _info;
                if ((b.followup_add_diff_days!=null) && (b.followup_add_diff_days!=0))
                  _text=_text+'&nbsp; <small><i>(inklusive '+b.followup_add_diff_days+' Tage Verschiebung)</i></small>';
                if (groupView.isPersonLeaderOfGroup(masterData.user_pid, b.id)) {
                  _text=_text+'<br/><div id="cdb_followup_'+b.id+'_'+id+'"><a href="#" id="f_followup_'+b.id+'"><b>jetzt durchf&uuml;hren</b></a></div>';          
                }
                _text=_text+'</div>';
              }        
            }
          });      
        }
      }
      if (_text!="")
        rows.push('<div class="row-fluid"><div class="span12">'+_text+'</div></div>');
        
    rows.push('<div class="row-fluid">');
      // Linke Spalte
      var _text ="<div class=\"left-column-person span4\">";
      
      if ((masterData.auth.write) || (personLeader))
        _text=_text+"<p><a id=\"f_image\" href=\"\">"+form_renderPersonImage(allPersons[a.id].imageurl)+"</a>";
      else
        _text=_text+"<p>"+form_renderPersonImage(allPersons[a.id].imageurl);
      
      _text=_text+"<p style='line-height:100%;color:black'>";
      _text=_text+a.vorname+" "+a.name;

      if (a.geburtsdatum!=null && a.geburtsdatum.toDateEn().getAgeInYears().num!=null) {
        var age=a.geburtsdatum.toDateEn().getAgeInYears().txt;
        if (age!=null) {
          _text=_text+" ("+age+")";        
        }
      }
      _text=_text+" &nbsp; ";
      
      if (a.email!="")
        _text=_text+"<a href=\"#\" title=\"E-Mail an Person schreiben\" id=\"mailPerson\">" +t.renderImage("email")+"</a>&nbsp;";
      if ((a.telefonhandy!="") && (masterData.auth.sendsms))
        _text=_text+form_renderImage({src:"mobile.png", width:20, label:"SMS an Person schreiben", cssid:"smsPerson"})+"&nbsp;";        
  
      if ((masterData.auth.adminpersons) && (a.active_yn==1))
        _text=_text+"<a href=\"#\" title=\"Person simulieren\" id=\"simulatePerson\">"+t.renderImage("person_simulate")+"</a>&nbsp;";
  
      if ((masterData.auth.viewalldetails) || (personLeader)) {
        _text=_text+"<small style=\"color:grey;\"><br/>"+_renderRelationList(id)+"</small>";
      }    
      
      _text=_text+"<br/>";    
      if (document.all?document.body.clientWidth:window.innerWidth>800)
        _text=_text+'<div id="map_canvas'+id+'" class="map-canvas-person"></div>';
      else _text=_text+"<small><i>Zu wenig Platz f&uumlr die Karte, bitte Browserfenster vergr&ouml;ssern.</i></small>";
    
      _text=_text+"</div>";    

    
      
      
      // Mittlere Spalte
      _text=_text+"<div id=\"detailAddress\" class=\"span4 middle-column-person\">";
      
      var autharr=new Array();
      if (masterData.auth.admin) 
        autharr.push("admin");
      if (masterData.auth.viewalldetails) 
        autharr.push("viewalldetails");
      if (personLeader)
        autharr.push("leader");
      if (personSuperLeader)
        autharr.push("superleader");
      
      _text=_text + t.renderFields(masterData.fields.f_address, a, masterData.auth.write || personLeader, autharr);
      
      if (masterData.auth.viewalldetails)
        _text=_text + t.renderFields(masterData.fields.f_church, a, masterData.auth.write);
      else if (a.geburtsdatum!=null) {
        _text=_text+"<p><b><i>Information</i></b><br/><small>Geburtstag: ";
        _text=_text + a.geburtsdatum.toDateEn().toStringDe()+"</small>";
      }
      
      if (masterData.auth.viewalldetails)
        _text=_text + t.renderFields(masterData.fields.f_category, a, masterData.auth.write);
      // Die kein Recht auf alle Daten haben m�ssen auch nur wissen, ob Mitglied oder nicht. Nicht die einzelnen Statis.
      else {
        _text=_text+"<p><b><i>Kategorie</i></b><br/><small>Mitglied: ";
        if (masterData.status[a.status_id].mitglied_yn==1)
          _text=_text + "ja";
        else  
          _text=_text + "nein";             
        _text=_text + "</small>";       
      }
      
      if (masterData.auth.adminpersons) {
  //      _text=_text+"<p style=\"line-height:100%\"><b><i>Berechtigungen</i></b>&nbsp;&nbsp;";
        _text=_text+"<h4>Berechtigungen&nbsp;&nbsp;";
        _text=_text+'<a href="#" id="auth">'+t.renderImage("options")+'</a></h4>';
        _text=_text+'<p style="line-height:100%;color:black"><small>';
      }
      else if (personSuperLeader) {
        _text=_text+"<br/><br/><h4>Berechtigungen&nbsp;&nbsp;</h4>";
        _text=_text+'<p style="line-height:100%;color:black"><small>';          
      }
      else { 
        _text=_text+'<p><small>';
      }
      
      if (masterData.auth.adminpersons || personSuperLeader) {  
        var txt="";
        if (a.active_yn==0)
          _text=_text+"<font color=red>Zugang f&uuml;r Person gesperrt!</font>";
        else if (a.lastlogin!=null)
          txt="Zuletzt online am "+a.lastlogin.toDateEn(true).toStringDe(true);
        else if (a.einladung==1)
          txt=a.vorname+' ist bereits eingeladen. <a href="#" style="color:black" id="invitatePerson">Erneut einladen</a>';
        else 
          txt='<a href="#" style="color:black" id="invitatePerson">'+form_renderImage({src:"person.png", width:18})+' Zu '+masterData.site_name+' einladen</a>';
        _text=_text+txt+"</small><br/>";
      }
      if (masterData.auth.adminpersons) {
        if (a.districts!=null) {
          _text=_text+"<p><small><b>Zugeordnet in "+f("distrikt_id")+":</b>";
          $.each(a.districts, function(k,b) {
            _text=_text+"<br/>- "+masterData.districts[b.distrikt_id].bezeichnung+"";
          });
          _text=_text+"</small>";
        }      
        if (a.gruppentypen!=null) {
          _text=_text+"<p><small><b>Zugeordnet in "+f("gruppentyp_id")+":</b>";
          $.each(a.gruppentypen, function(k,b) {
            _text=_text+"<br/>- "+masterData.groupTypes[b.gruppentyp_id].bezeichnung+"";
          });
        }
        if (a.auth!=null) {
          var auth=t.getAuthAsArray(a.auth).join(", ");
          if (auth!="") {
            _text=_text+"<p><small><b>Manuelle Berechtigungen:</b><br/>"+auth.trim(100);
          }
        }
      }
      _text=_text+"</small>";
      _text=_text+"</div>";
      
      
      
  
      // Rechte Spalte
      _text=_text+"<div class=\"right-column-person span4\">";
      
      if (masterData.auth.viewtags || personLeader) {
        _text=_text + t.renderTags(a.tags, masterData.auth.write || personLeader, id);
      }
  
      // Kommentare
      if (masterData.auth.comment_viewer!=null) {
        _text=_text+'<div class="detail-view-infobox"><table><tr><th>Kommentare';
    
        var _comments="";
        var _count=0;
        if (a.comments!=null) {
          $.each(a.comments, function (k,b) {
            _count++;
            if ((_count<=3) || (t.showAllCommentDetails)) {
              var _class=(b.relation_name=='person_followup'?'followup_comment':'');
              // Text als Links erkennen und ersetzen
              var _text=b.text.htmlize();
              
              _comments=_comments+'<tr>'+'<td class="'+_class+'"><p><small>'+_text;
              _comments=_comments+"<br/><font color=\"grey\"><i>(";
              if (allPersons[b.person_id]!=null)
                _comments=_comments+'<a href="#" id="person_'+b.person_id+'" class="tooltip-person" data-tooltip-id="'+b.person_id+'">'+allPersons[b.person_id].vorname+" "+allPersons[b.person_id].name+'</a>';
              else   
                _comments=_comments+"["+b.person_id+"]";
              _comments=_comments+"/"+masterData.comment_viewer[b.comment_viewer_id].bezeichnung+" "+b.datum.toDateEn().toStringDe()+")</i></font></small>";
              _comments=_comments+'<td style="padding:0;width:16px;" class="'+_class+'">';
            if (masterData.auth.write)
                _comments=_comments+'<a href="#" id="del_note'+b.id+'"><img width=16px src="'+masterData.modulespath+'/images/trashbox.png"/></a>';
            }  
          });
        }
        _text=_text+'<th style="width:32px;padding:0 4px 0 0;">';
        if (_count>3)
          if (t.showAllCommentDetails)
            _text=_text+"<i><a href=\"#\" id=\"f_comments_show\">"+'<img width="16px" src="'+masterData.modulespath+'/images/closed.png" align="absmiddle"/></a></i>';
          else {
            _text=_text+"<i><a href=\"#\" id=\"f_comments_show\">"+'<img width="16px" src="'+masterData.modulespath+'/images/opened.png" align="absmiddle"/></a></i>';
            _comments=_comments+'<tr style="background:#F4F4F4;">'+'<td><a href="#" id="f_comments_show">...</a><td>';          
          }
        if ((masterData.auth.write) || ((personLeader) && (masterData.auth.comment_viewer!=null))) 
          _text=_text+'<a href="" id="f_note"><img width=16px src="'+masterData.modulespath+'/images/plus.png" align="absmiddle"/></a>';
        _text=_text+_comments;
    
        _text=_text+"</table></div><small><br/></small>";
      }
      
      rows.push(_text);
      
      // Gruppen
      if (allPersons[id]!=null) {
        $.each(churchcore_sortMasterData(masterData.groupTypes), function(k,a) {
          
          if ((masterData.auth.viewalldetails) 
                 || (a.anzeigen_in_meinegruppen_teilnehmer_yn==1)
                 || (masterData.auth.editgroups)
                 || (t.isPersonLeaderOfOneGroupTypeOfPerson(masterData.user_pid, a.id, id))) {
            _text="";
            _text=_text+'<div class="detail-view-infobox"><table><tbody style="border:none"><tr><th>'+a.bezeichnung;
            _text=_text+'<th class="datum"><th style="width:16px;padding:0">';
            if ((t.showGroupDetailsId!=a.id)) 
              _text=_text+'<a href="#" id="f_gruppe_show_'+a.id+'"><img width="16px" src="'+masterData.modulespath+'/images/opened.png" align="absmiddle"/></a>';
            else
              _text=_text+'<a href="#" id="f_gruppe_show_'+a.id+'"><img width="16px" src="'+masterData.modulespath+'/images/closed.png" align="absmiddle"/></a>';
            
            _text=_text+'<th style="width:16px;padding:0 4px 0 0">';
            var _groups="";
            if ((masterData.auth.editgroups) || (t.getAvailableAddGroupsForGrouptype(a.id)!=null)) {
              _text=_text+'<a href="#" id="addPersonGroupRelation'+a.id+'"><img width=16px src="'+masterData.modulespath+'/images/plus.png" align="absmiddle"/></a>';
              _groups=" ";
            }
  
  
            // Erst mal alle auf Unused setzen, damit ich hinterher wei�, welche Archiv-Gruppe noch nicht gezeigt wurde
            if ((allPersons[id].oldGroups!=null) && (t.showGroupDetailsId==a.id) && (t.showGroupDetailsWithHistory)) {
              $.each(allPersons[id].oldGroups,function(y,d) {
                d.used=false;
              });
            }            
            if (allPersons[id].gruppe!=null) {            
              _groups=_groups+t.getGroupEntries(id, a.id, function(b) {return ((b.leiter>0));});
              _groups=_groups+t.getGroupEntries(id, a.id, function(b) {return ((b.leiter==0));});
              _groups=_groups+t.getGroupEntries(id, a.id, function(b) {return ((b.leiter<0));});
            }
            if ((allPersons[id].oldGroups!=null) && (t.showGroupDetailsId==a.id) && (t.showGroupDetailsWithHistory)) {
                  _groups=_groups+_getOldGroupEntries(id, a.id, function(b) {return ((1==1));});
            }
            // Nur anzeigen wenn es hier was zu zeigen gibt
            if (_groups!="") rows.push(_text+_groups+"</table></div>");
          }  
        });     
  
      }
      rows.push("</div>");
    
    
    // Logs & Letzte Aenderung
    _text="<div style=\"clear:both\">";   

      _text=_text+"<div class=\"detail-footer\">";
      _text=_text+"<div class=\"bottom_links\" style=\"display:inline;\"> &nbsp; ";
      
      if (masterData.auth.viewhistory)        
          _text=_text+"<a href=\"#\" id=\"logs\">Historie >></a>&nbsp; &nbsp; ";
      if (masterData.auth.editrelations) 
        _text=_text+" <a href=\"#\" id=\"rels\">Beziehungen pflegen >></a>&nbsp; &nbsp;";              

      _text=_text+"<a href=\"#\" id=\"vcard\">VCard export>></a></div><!--bottom_links-->";                
      _text=_text+"<div style=\"float:right\">";
    
      _text=_text+"<small><i>"+f("bereich_id")+": </i>";
        $.each(masterData.auth.dep, function(k,a) {
          if ((allPersons[id].access!=null) && (allPersons[id].access[a.id]==a.id)) {
            _text=_text+" "+a.bezeichnung+"&nbsp;";         
          }
        }); 
      if (masterData.auth.write)        
        _text=_text+'<a href="#" id="f_bereich"><img width="16px" align="absmiddle" src="'+masterData.modulespath+'/images/options.png"/></a> &nbsp; &nbsp; &nbsp;';
      if ((masterData.auth.admin) || (masterData.auth.adminpersons) || (masterData.auth["push/pull archive"])) {
        _text=_text+"&nbsp;Admin-Funktion: ";
        if ((masterData.auth["push/pull archive"]) && (a.archiv_yn==0))
          _text=_text+"&nbsp;<a href=\"#\" title=\"Person archivieren\" id=\"archivePerson\">"+form_renderImage({src:"archive.png", width:18})+"</a>";
        if ((masterData.auth["push/pull archive"]) && (a.archiv_yn==1))
          _text=_text+"&nbsp;<a href=\"#\" title=\"Person zur&uuml;ckholen\" id=\"undoArchivePerson\">"+form_renderImage({src:"undoarchive.png", width:18})+"</a>";
        if (masterData.auth.admin || masterData.auth.adminpersons)
          _text=_text+"&nbsp;<a href=\"#\" title=\"Person entfernen\" id=\"deletePerson\">"+form_renderImage({src:"trashbox.png", width:18})+"</a>&nbsp;&nbsp;";
      }
      _text=_text+'&nbsp;<a href="#" id="person_'+a.id+'">#'+a.id+"</a></i></small>&nbsp;&nbsp;";

     _text=_text+"</div><!--float-right-->";
     _text=_text+"</div><!--detail-footer-->"; 
      
      
      _text=_text+"<div id=\"detail_logs"+id+"\" class=\"logs\"></div>";        
      
       // Beziehungen  
        _text=_text+"<div id=\"detail_rels"+id+"\" class=\"relations\">";
       
        _text=_text+"<p style='line-height:100%;' id=\"pRels\"><b><i>Beziehungen</i></b>&nbsp;&nbsp;";

        _text=_text+"<p style='line-height:100%;color:black'><small>";
        
        _text=_text+_renderRelationList(id,true);

        if (masterData.auth.editrelations) {
          _text=_text+"<br/>";
          $.each(masterData.relationType, function (k,b) {
            _text=_text+"<i><a href=\"#\" id=\"f_rels_k_"+b.id+"\">"+b.bez_kind+" hinzuf&uuml;gen</a></i> &nbsp; &nbsp;";
            if (b.bez_kind!=b.bez_vater)
              _text=_text+"<i><a href=\"#\" id=\"f_rels_v_"+b.id+"\">"+b.bez_vater+" hinzuf&uuml;gen</a></i> &nbsp; &nbsp;";
          });
        }
    
        _text=_text+"<br/></small>";
      _text=_text+"</div>";
          
      
      
    _text=_text+"</div>";
    
  _text=_text+"</div>";
  
  rows.push(_text);
        
  $("#detailTD"+id).html(rows.join(""));

  if ((a.plz!='') || (a.ort!=''))
    cdb_showGeoPerson(a.strasse+", "+a.plz+" "+a.ort, id, !((personLeader) || (masterData.auth.viewalldata)));
  
  
  // Callbacks
  
  t.addTagCallbacks(id, function(tag_id) {
    if (allPersons[id].tags==null)
      allPersons[id].tags= new Array();
    allPersons[id].tags.push(tag_id);
    churchInterface.jsendWrite({func:"addPersonTag", id:id, tag_id:tag_id});
    t.renderList(allPersons[id]);
  });
  
  $("td[id=detailTD"+id+"] a").click(function() {
    // L�sche den Tooltip, falls es ihn gibt
    clearTooltip();
    if (($(this).parents("td").attr("id")!=null) && ($(this).parents("td").attr("id")!=""))
      var id=$(this).parents("td").attr("id").substring(8,100);
    else 
      id=$(this).parents("td").parents("td").attr("id").substring(8,100);
    var fieldname=$(this).attr("id");

    // Es sollen nur Logs eingeblendet werden.
    if (fieldname=="logs") {
      if ($("#detail_logs"+id).is(":hidden")) {
        $("#detail_logs"+id).html("Lade Daten...");
        $("#detail_logs"+id).animate({ height: 'toggle'}, "medium");
        churchInterface.jsendRead({ func: "getPersonDetailsLogs",id:id }, function(ok, json) {
          _text="";        
          _text=_text+"<small><table class=\"table\"><tr><td><i>Datum</i><td><i>Text</i><td><i>Erfolgt durch</i><td>";      
          if (json!=null)
            $.each(json, function (k,b) {
              if ((b.level<3) || (masterData.auth.admin)) {
                color="gray";
                if (b.level==1) color="red";
                else if (b.level==2) color="black";
                _text=_text+"<tr style=\"line-height:100%;color:"+color+"\"><td><nobr>"+b.datum.toDateEn().toStringDe(true)+"</nobr><td>"+b.txt+"<td>";
                if (allPersons[b.person_id]!=null)
                  _text=_text+"<nobr>"+allPersons[b.person_id].vorname+" "+allPersons[b.person_id].name+" ["+b.person_id+"]</nobr>";
                else
                  _text=_text+"["+b.person_id+"]";
              }
            });
          _text=_text+"</table></small>";
          $("#detail_logs"+id).html(_text);
        });
      }
      else 
        $("#detail_logs"+id).animate({ height: 'toggle'}, "fast");
    }
    // Es sollen Beziehungen eingeblendet werden.
    else if (fieldname=="rels") 
      $("#detail_rels"+id).animate({ height: 'toggle'}, "fast");  
    else if (fieldname=="vcard") {
      fenster = window.open("?q=churchdb/vcard&id="+id, "fenster1", "width=600,height=400,status=yes,scrollbars=yes,resizable=yes");
      fenster.focus();
    }       
    else if ((fieldname=="extern") || (fieldname=="")) {
      return true;
    }       
    else if (fieldname=="auth") {
      t.renderAuthDialog(id);
    }
    else if (fieldname=="simulatePerson") {
      if ((masterData.settings.hideSimulateQuestion=="1") || (confirm("Wirklich "+a.vorname+" "+a.name+" simulieren? Simulation kann durch das Benutzer-Menu beendet werden."))) {
        if (masterData.settings.hideSimulateQuestion==null) 
          churchInterface.jsendWrite({func:"saveSetting", sub:"hideSimulateQuestion", val:"1"}, null, false);
        window.location.href="?q=simulate&id="+allPersons[id].id+"&location=churchdb";
      }
    }
    else if (fieldname.indexOf("grp_")==0) {          
      if ($("tr[id=groupinfos"+id+"]").text()!="") {
        $("tr[id=groupinfos"+id+"]").remove();
      } 
      churchInterface.setCurrentView(groupView);
      groupView.clearFilter();
      groupView.setFilter("searchEntry",fieldname.substring(4,99));
      groupView.renderView();
    }
    else if (fieldname.indexOf("person_")==0) {
      t.clearFilter();
      t.setFilter("searchEntry", "#"+$(this).attr("id").substr(7,99));
      t.renderFurtherFilter();
      t.renderListMenu();
      t.renderList();
    }
    else if (fieldname.indexOf("del_rel_")==0) {
      if (confirm("Beziehung wirklich entfernen?")) {
        rel_id=$(this).attr("id").substr(8,99);
        churchInterface.jsendWrite({func:"del_rel", id:id, rel_id:rel_id}, function(ok) {
          $("tr[id=detail"+id+"]").html("");   
          cdb_loadRelations(function () {
            t.renderEntryDetail(id);
          });
        });
      }
    }  
    else if ($(this).attr("id").indexOf("detail_close")==0) {
      $("#detailTD"+id).remove();
      return false;
    }
    // Es sollen nun alle Gruppeninfos angezeigt werden
    else if (fieldname.indexOf("f_gruppe_show_")==0) {
      if (t.showGroupDetailsId==fieldname.substr(14,99)) {
        t.showGroupDetailsId=null;
        t.showGroupDetailsWithHistory=false;
      }
      else { 
        t.showGroupDetailsId=fieldname.substr(14,99);
        t.showGroupDetailsWithHistory=true;
      }
      t.renderDetails(id);
    } 
    // Es sollen nun alle Gruppeninfos angezeigt werden
    else if (fieldname.indexOf("f_gruppe_showmore_")==0) {
      t.showGroupDetailsId=fieldname.substr(18,99);
      t.renderDetails(id);
    } 
    else if (fieldname=="f_comments_show") {
      t.showAllCommentDetails=!t.showAllCommentDetails; 
      t.renderDetails(id);
    } 
    // Wenn Beziehungen gepflegt werden, gibt es die Standard PersonSelect-Seite
    else if (fieldname.indexOf("f_rels_")==0) {
      renderDivid=false;
      t.renderPersonSelect("Auswahl f&uuml;r: "+$(this).attr("text"), false, function (p_id) {
        if (p_id!=null) {             
          $("#cbn_editor").html("<p><br/><b>Daten werden gespeichert...</b><br/><br/>");
          obj=new Object();
          obj["func"]='add_rel';
          if (fieldname.substr(7,1)=="k") {
            obj["id"]=id;
            obj["child_id"]=p_id;
          }
          else {
            obj["id"]=p_id;
            obj["child_id"]=id;
          }
          obj["rel_id"]=fieldname.substr(9,99);
          
          churchInterface.jsendWrite(obj, function(ok) {  
            $("tr[id=detail"+id+"]").html(""); 
            cdb_loadRelations(function () {
              t.renderEntryDetail(id);
            });
          });
        } 
      });
    }
    else if (fieldname.indexOf("f_followup_")==0) {
      // FollowUp Erg�nzen, falls die aktuelle Person hier ein FollowUp durchf�hren soll
      g_id=fieldname.substring(11,99);
      var rows=new Array();
      b=allPersons[id].gruppe[g_id];
      var _info="";
//      rows.push('<b>FollowUp durchf&uuml;hren:</b><br/><br/>');
      if (b.comment!=null)
        rows.push('<p>Gruppenkommentar: </i>'+b.comment+'</p>');
      
      rows.push('<div class="row-fluid"><div class="span5">');
        rows.push(form_renderCheckbox({cssid:"followupOk_"+g_id+'_'+id, label:"FollowUp erfolgreich"}));
//        rows.push('<input type="checkbox" id="followupOk_'+g_id+'_'+id+'"></input> FollowUp erfolgreich<br/>');
        rows.push('<textarea class="input-xlarge" rows="4" id="followupNote_'+g_id+'_'+id+'"/><br/>');
      rows.push('</div> <div class="span5 well">');
      
      var _typIntervall = _getFollowupTypIntervall(masterData.groups[b.id].followup_typ_id, b.followup_count_no);
      rows.push('<i>'+masterData.followupTypes[masterData.groups[b.id].followup_typ_id].bezeichnung
            +' Stufe '+b.followup_count_no+'</i><br/>'+_typIntervall.info+'<br/>');              

      rows.push('</div></div>');
      
   //   if (_getPersonGroupFollowupDiffDays(b)*1<=0)
        rows.push('<div id="cdbfollowupDiff_'+g_id+'_'+id+'">Erinnern in <input type="text" class="input-mini" size="2" maxlength="2" id="followupDiff_'+g_id+'_'+id+'" value="2"></input> Tagen<br/></div>');
        
      rows.push('<div id="cdbfollowupNachfolger_'+g_id+'_'+id+'" style="display:none">');
      if (_getFollowupTypIntervall(masterData.groups[b.id].followup_typ_id, b.followup_count_no*1+1)==null) {
        // Gibt es einen definierte Nachfolgergruppe?
        if (masterData.groups[b.id].fu_nachfolge_typ_id==0)
          rows.push("<p><small><i>Hinweis: Dieses FollowUp wird dann beendet.</i></small>");
        else {          
          rows.push("<p>Hinweis: Dieses FollowUp wird dann beendet ");
          if (masterData.groups[b.id].fu_nachfolge_gruppenteilnehmerstatus_id==null)
            masterData.groups[b.id].fu_nachfolge_gruppenteilnehmerstatus_id=0;
          if (masterData.groups[b.id].fu_nachfolge_typ_id==3) { // gruppe
            if (masterData.groups[b.id].fu_nachfolge_objekt_id!=null) {
              rows.push("und die Person wird in die Gruppe <i>"+masterData.groups[masterData.groups[b.id].fu_nachfolge_objekt_id].bezeichnung+"</i>");
              rows.push(' als <i>'+masterData.groupMemberTypes[masterData.groups[b.id].fu_nachfolge_gruppenteilnehmerstatus_id].bezeichnung+'</i>');
              rows.push(" hinzugef&uuml;gt");
              rows.push('<input type="hidden" id="selectNachfolgegruppe_'+g_id+"_"+id+'" value="'+masterData.groups[b.id].fu_nachfolge_objekt_id+'"></input>');
            }
          }
          else if (masterData.groups[b.id].fu_nachfolge_typ_id>0) { // 1=gruppentyp oder 2=distrikt
            rows.push("und die Person kann nun in eine Gruppe ");
            rows.push(' als <i>'+masterData.groupMemberTypes[masterData.groups[b.id].fu_nachfolge_gruppenteilnehmerstatus_id].bezeichnung+'</i>');
            rows.push(" &uuml;bernommen werden.<br/>Gruppe: ");
            rows.push('<select id="selectNachfolgegruppe_'+g_id+"_"+id+'"');
            rows.push('<option value="-1">-</option>');                
            $.each(masterData.groups, function(i,c) {
              if (
                  (((masterData.groups[b.id].fu_nachfolge_typ_id==1) && (c.gruppentyp_id==masterData.groups[b.id].fu_nachfolge_objekt_id))
                ||  ((masterData.groups[b.id].fu_nachfolge_typ_id==2) && (c.distrikt_id==masterData.groups[b.id].fu_nachfolge_objekt_id))) 
                   && (c.valid_yn==1) && (c.versteckt_yn==0))
                rows.push('<option value='+c.id+'>'+c.bezeichnung+'</option>');
            });
            rows.push("</select>");
          }
        }
      }
      else 
        rows.push("<p><small><i>Hinweis: Das FollowUp geht dann in die Stufe "+(b.followup_count_no*1+1)+"</i></small>");
      rows.push("</div>");
      
      rows[rows.length]='<p><input type="button" class="btn btn-royal" id="idFollowupSave_'+g_id+'_'+id+'" value="Speichern"/>&nbsp;';
      rows[rows.length]='<input type="button" class="btn btn-alert" id="idFollowupAbort_'+g_id+'_'+id+'" value="FollowUp l&ouml;schen"/>&nbsp;&nbsp;';
      rows[rows.length]='<a href="#" id="idFollowupCancel_'+g_id+'_'+id+'">Sp&auml;ter durchf&uuml;hren</a>';

      rows.push('</div>');
      $("#cdb_followup_"+g_id+"_"+id).html(rows.join(""));
      
      // Callback Followup
      $('#followupOk_'+g_id+'_'+id).change(function (a) {
        $('#cdbfollowupDiff_'+g_id+'_'+id).toggle(!$(this).attr("checked"));
        $('#cdbfollowupNachfolger_'+g_id+'_'+id).toggle($(this).attr("checked"));
      });
      $('#idFollowupCancel_'+g_id+'_'+id).click(function (a) {
        t.renderDetails(id);
        return false;
      });
      $('#idFollowupAbort_'+g_id+'_'+id).click(function (g) {
        var txt="";
        var back_id=null;
        if ((allPersons[id].gruppe[g_id].followup_erfolglos_zurueck_gruppen_id!=null) 
            && (masterData.groups[allPersons[id].gruppe[g_id].followup_erfolglos_zurueck_gruppen_id])) {
          back_id=allPersons[id].gruppe[g_id].followup_erfolglos_zurueck_gruppen_id;
          txt="Das FollowUp bei "+a.vorname+" "+a.name+" wirklich entfernen? Das FollowUp geht zurueck an Gruppe '"+
             masterData.groups[back_id].bezeichnung+"'!";
        }
        else
          txt="Das FollowUp bei "+a.vorname+" "+a.name+" wirklich entfernen? Achtung, um das FollowUp wieder zu starten, muss die Person wieder der entsprechend Gruppen zugeordnet werden!";          
          
        if (confirm(txt)) {
          churchInterface.jsendWrite({func:"delPersonGroupRelation",id:id,g_id:g_id});
          delete allPersons[id].gruppe[g_id];
          var obj = new Object();
          obj["note"]='Followup "'+masterData.followupTypes[masterData.groups[g_id].followup_typ_id].bezeichnung+'" wurde abgebrochen. ';
          obj["note"]=obj["note"]+"<br/>"+$('#followupNote_'+g_id+'_'+id).val();
          obj["func"]='f_note';
          obj["relation_name"]='person_followup';
          obj["id"]=id;
          obj["comment_viewer"]=masterData.followupTypes[masterData.groups[g_id].followup_typ_id].comment_viewer_id;
          if (back_id!=null) {
            t.addPersonGroupRelation(id, back_id);
            obj["note"]=obj["note"]+" Es geht zur&uuml;ck an "+masterData.groups[back_id].bezeichnung;
          }
          churchInterface.jsendWrite(obj, null, false);
          allPersons[id].details=null;
          t.renderList();
          //t.renderDetails(id);
          t.renderTodos();
          return false;                    
        }
      });
      $('#idFollowupSave_'+g_id+'_'+id).click(function (a) {
        var obj=new Object();
        obj["note"]='Followup "'+masterData.followupTypes[masterData.groups[g_id].followup_typ_id].bezeichnung+'"';
        obj["func"]='f_note';
        obj["relation_name"]='person_followup';
        obj["id"]=id;
        obj["comment_viewer"]=masterData.followupTypes[masterData.groups[g_id].followup_typ_id].comment_viewer_id;

        var _diff_days=_getPersonGroupFollowupDiffDays(b)*(-1);
        if (_diff_days<0) _diff_days=0;
        if (b.followup_add_diff_days==null)
          b.followup_add_diff_days=_diff_days;
        else b.followup_add_diff_days=b.followup_add_diff_days*1+_diff_days;

        if ($('#followupOk_'+g_id+'_'+id).attr("checked")) {
          b.followup_count_no = _getNextFollowupCountNo(masterData.groups[g_id].followup_typ_id, b.followup_count_no);
          // Wenn das null ist, dann ist das Followup durch und die Gruppe wird rausgenommen
          if (b.followup_count_no!=null) {
            // Gebe dem Ajax auch die FollowUp-Infos mit
            obj["followup_gid"]=g_id;
            obj["followup_count_no"]=b.followup_count_no;
            obj["followup_add_diff_days"]=b.followup_add_diff_days;

            obj["note"]=obj["note"]+" erfolgreich, nun Step "+b.followup_count_no;
            if ((_diff_days>0) || (b.followup_add_diff_days>0))
              obj["note"]=obj["note"]+" mit insg. "+b.followup_add_diff_days+" Tagen Differenz (+"+_diff_days+")";
          } else {
            // Entferne Person aus der Gruppe 
            churchInterface.jsendWrite({func:"delPersonGroupRelation",id:id,g_id:g_id});
            delete allPersons[id].gruppe[g_id];
            obj["note"]=obj["note"]+" abgeschlossen mit "+b.followup_add_diff_days+" Tagen Differenz, Gruppe "+masterData.groups[g_id].bezeichnung+" wird entfernt.";
            // Wurde ein Nachfolger markiert?
            var _nachfolger=$("#selectNachfolgegruppe_"+g_id+"_"+id).val();
            if (_nachfolger!=null) {
              alert("Nachfolgegruppe "+masterData.groups[_nachfolger].bezeichnung+" wurde bei der Person eingetragen");
              t.addPersonGroupRelation(id, _nachfolger, masterData.groups[g_id].fu_nachfolge_gruppenteilnehmerstatus_id, g_id);
            }
          }
        } 
        else {       
          obj["note"]=obj["note"]+" erfolglos. ";
          if ($('#followupDiff_'+g_id+'_'+id).attr("value")!=null) {
            obj["followup_gid"]=g_id;
            b.followup_add_diff_days=b.followup_add_diff_days*1+$('#followupDiff_'+g_id+'_'+id).attr("value")*1;
            obj["followup_add_diff_days"]=b.followup_add_diff_days;          
            obj["followup_count_no"]=b.followup_count_no;
            obj["note"]=obj["note"]+"Neuer Aufschub: "+$('#followupDiff_'+g_id+'_'+id).attr("value")+" Tage, insg.: "+b.followup_add_diff_days;
          }  
        }
        
        // Loesche Infos, damit er sich die neuen Kommentare zieht
        allPersons[id].details=null;
        obj["note"]=obj["note"]+"<br/>"+$('#followupNote_'+g_id+'_'+id).val();
        $("#cdb_followup_"+g_id+"_"+id).html("Speichern...");
        churchInterface.jsendWrite(obj, function(ok) {
          t.renderView();  
          t.renderTodos();
        });        
      });
    }
    else if (fieldname.indexOf("delPersonGroupRelation")==0) {
      var g_id=fieldname.substring(22,99);
      t.delPersonFromGroup(id, g_id);
    }
    // Es geht nun in die Edit-Seite
    else   
      t.renderEditEntry(id, fieldname);
    return false;
  });
  t.addPersonsTooltip($("#detailTD"+id));
};


function _getFollowupTypIntervall(followup_typ_id, followup_count_no) {
  var res=null;
  $.each(masterData.followupTypIntervall, function(i,c) {
    if ((c.followup_typ_id==followup_typ_id) && (c.count_no==followup_count_no)) {
      res=c;
      // exit
      return false;
    }                        
  });
  return res;
}

PersonView.prototype.getAvailableAddGroupsForGrouptype = function(gt_id) {
  var arr=null;
  $.each(masterData.groups, function(k,a) {
    if (a.gruppentyp_id==gt_id) {
      if (groupView.isPersonLeaderOfGroup(masterData.user_pid, a.id)) {
        if (arr==null) arr=new Array();
        arr.push(a);
      }
    }
  });
  return arr;
};

PersonView.prototype.editPersonAuth = function (id) {
  var t=this;
  this.editDomainAuth(id, allPersons[id].auth, "person", function(id) {
    // Hole mir neue Details f�r die Person mit der Auth-infos
    churchInterface.jsendRead({func:"getPersonDetails", id:id}, function(ok, json) {
      allPersons[json.id]=cdb_mapJsonDetails(json, allPersons[json.id]);
      // Rendere die View neu, da auch die Tablle Zugriffsrechte anzeigen kann
      t.renderFilter();
      t.renderList();
    });
  });
};

PersonView.prototype.delPersonFromGroup = function (id, g_id, withoutConfirmation) {
  var t=this;
  var a=allPersons[id];
  if (withoutConfirmation==null) withoutConfirmation=false;
  
  // Muss erst pruefen, ob es sich um einen Leiter handelt und ein Gruppentyp, wo ein Leiter bleiben muss. 
  if ((masterData.groupTypes[masterData.groups[g_id].gruppentyp_id].muss_leiter_enthalten_yn==1) 
        && (allPersons[id].gruppe[g_id].leiter==1)) {
    var leiter_check=false;
    $.each(allPersons,function(k,p) {
      if ((p.id!=id) && (p.gruppe!=null))
        $.each(p.gruppe, function(i,b) {
          if ((b.id==g_id) && (b.leiter==1))
            leiter_check=true;
        });
    });
    if (!leiter_check) {
      alert("Bei "+a.vorname+" "+a.name+" nicht machbar, da es sich um den einzigen Leiter dieser Gruppe handelt. " +
          "Gruppen von "+f("gruppentyp_id")+" "+masterData.groupTypes[masterData.groups[g_id].gruppentyp_id].bezeichnung+
          "' muessen mindestens einen Leiter haben.");
      return false;
    } 
  }
  if ((masterData.auth.editgroups) || (groupView.isPersonSuperLeaderOfGroup(masterData.user_pid, g_id))) {
    if ((withoutConfirmation) || (confirm(a.vorname+" "+a.name+" wirklich aus der Gruppe herausnehmen?"))) {
      churchInterface.jsendWrite({func:"delPersonGroupRelation",id:id,g_id:g_id}, function() {
        $("tr[id=detail" + id + "]").remove();
        delete a.gruppe[g_id]; 
        if (churchInterface.getCurrentView().name=="PersonView") {
          t.renderList(a);
          t.renderTodos();
        }
        else churchInterface.getCurrentView().renderList();
      });
    }
  } 
  // Nicht erlaubt, d.h. es handelt sich um einen KG-Leiter, der nur auf "Zu L�schen" setzen darf
  else {
    var form = new CC_Form("Person in der Gruppe als zu l&ouml;schen markieren");
    form.addCaption({label:'Person', text:a.vorname+" "+a.name});
    form.addCaption({label:'Gruppe', text:masterData.groups[g_id].bezeichnung});
    form.addTextarea({label:"Kommentar angeben", rows:4, cssid:"comment",placeholder:"Warum soll die Person nicht mehr in der Gruppe sein?",
           data:(a.gruppe[g_id].comment!=null?a.gruppe[g_id].comment:"")});
    var elem = t.showDialog("Person aus der Gruppe nehmen", form.render(false, "horizontal"), 500, 350, {
        "Speichern": function() {
        var date = new Date();
        var comment=$("#comment").val();
        churchInterface.jsendWrite({func:"editPersonGroupRelation",id:id,g_id:g_id,comment:$("#comment").val(),date:date.toStringEn(),leader:-1}, function() {
          a.gruppe[g_id].leiter=-1;
          a.gruppe[g_id].comment=comment;
          t.renderList(a);
        });                      
        $(this).dialog("close");            
      },          
      "Abbrechen": function() {
        $(this).dialog("close");
      }
    });
  }
};

PersonView.prototype.renderEntryDetail = function (pos_id, data_id) {
  var t=this;
  if (data_id==null)
    data_id=pos_id;
  
  // start function renderDetails()
  $("tr[id=detail" + pos_id + "]").remove();
  $("tr[id=" + pos_id + "]").after("<tr id=\"detail" + data_id + "\"><td colspan=\"10\" id=\"detailTD" + data_id + "\">Lade Daten..</td></tr>");
  if ((allPersons[data_id]!=null)  && (allPersons[data_id].details == null)) {
    churchInterface.jsendRead({func:"getPersonDetails", id:data_id}, function(ok, json) {
//      _aktiv=window.clearTimeout(_aktiv);
      if (json=="no access") {
        delete allPersons[data_id];
        t.clearFilter();
        t.renderView();
        alert("Leider fehlt die Berechtigung, um die Detaildaten dieser Person zu sehen.");
      } 
      else {
        allPersons[json.id]=cdb_mapJsonDetails(json, allPersons[json.id]);       
        t.renderDetails(data_id);
      }  
    }); 
    
  }
  else if (allPersons[data_id]!=null)
    t.renderDetails(data_id);
};

PersonView.prototype.renderEditEntry = function(id, fieldname, preselect) {
  var t=this;
  var a=allPersons[id];
  var rows = new Array();  
  var renderViewNecessary=false;
  var width=550;
  var height=650;
  buttonText="Speichern";
  // Alle bekannten Elemente als Input rendern      
  if (masterData.fields[fieldname]!=null) {
    
    var autharr=new Array();
    if (masterData.auth.viewalldetails) 
      autharr.push("viewalldetails");
    if (t.isPersonLeaderOfPerson(masterData.user_pid, id))
      autharr.push("leader");
    if (t.isPersonSuperLeaderOfPerson(masterData.user_pid, id))
      autharr.push("superleader");
    
    rows.push(this.getStandardFieldsAsSelect(fieldname, a, autharr));
    
    if (fieldname=="f_category") height=300;
  }   
  else if (fieldname=="f_note") {
    width=350; height=350;
    rows[rows.length]="<textarea cols=\"30\" rows=\"4\" id=\"InputNote\"/><br/><br/>";
    rows[rows.length]="Der Kommentar soll zu sehen sein f&uuml;r:<br/> ";
    rows[rows.length]="<select id=\"InputComment\">";

    $.each(masterData.auth.comment_viewer, function(k,a) {
      rows[rows.length]="<option value=\""+a+"\" "+(a==0?"selected":"")+">"+masterData.comment_viewer[a].bezeichnung+"</option>";   
    }); 
    rows[rows.length]="</select><br/>";
  } 
  else if (fieldname.indexOf("addPersonGroupRelation")==0) {
    if (masterData.groups==null) {
      alert('Noch keine Gruppe vorhanden. Bitte erst eine Gruppe in der "Gruppenliste" anlegen!');
      return null;
    }
    else {
      width=380; height=330;
      var gt_id=fieldname.substring(22,99);
      rows.push("<table><tr><td><b>"+masterData.groupTypes[gt_id].bezeichnung+"</b>&nbsp;<td>");
      
      rows.push("<select id=\"InputGroupEntry\">");
      var diff_date=new Date();
      diff_date.addDays(-1*masterData.groupnotchoosable);
    
      $.each(churchcore_sortData(masterData.groups, "bezeichnung"), function(k,a) {
        // Es sollen nur Gruppen ausw�hlbar sein, deren Abschlu�datum zu alt ist
        if ((a.gruppentyp_id==gt_id) 
            && (a.valid_yn==1) 
            && ((a.abschlussdatum==null) || (a.abschlussdatum.toDateEn()>diff_date))) {
            // Wenn ich die Gruppe sehen darf, darf ich sie auch zuordnen. Und nat�rlich auf editgroups
            if ((groupView.isAllowedToSeeDetails(a.id)) || ((masterData.auth.editgroups) && (a.versteckt_yn==0)) ) {
            // Es sollen nur Gruppen angezeigt werden, in denen die Person noch nicht ist.
            var dabei=false;
            if (allPersons[id].gruppe!=null) {
              $.each(allPersons[id].gruppe, function(i,b) {
                if (a.id==i) dabei=true;
              });
            }  
            if (!dabei) {
              rows.push('<option ');
              if (preselect==a.id) rows.push('selected ');
              rows.push('value="'+a.id+'">' + a.bezeichnung + "</option>");
            }
          }
        }  
      });
      if (masterData.auth.admingroups) {
        rows.push('<option value="">--</option>');
        rows.push('<option value="create'+gt_id+'">'+masterData.groupTypes[gt_id].bezeichnung+' erstellen</option>');
      }
    }
    rows.push("</select>");

    rows.push("<tr><td>Teilnehmerstatus<td>");    
    rows.push("<select id=\"InputGroupLeader\">");
    $.each(masterData.groupMemberTypes, function(k,a) {
      rows.push('<option value="'+k+'">' + a.bezeichnung + "</option>");
    });
    rows.push("</select>");
    
    rows.push("<tr><td>Dabei seit<td>");
    var d= new Date();
    rows.push('<input type="text" id="InputDate" size="10" value="'+d.toStringDe()+'"/>');

    rows.push("<tr><td>Notiz<td>");
    var d= new Date();
    rows.push('<textarea id="InputComment" size="10" rows="3"/></textarea>');

    rows.push("</table>");
    fieldname="addPersonGroupRelation";
  }
  else if (fieldname.indexOf("editPersonGroupRelation")==0) {
    width=380; height=330;
    var g_id=fieldname.substring(23,99);    
    var res=t.renderPersonGroupRelation(id, g_id);
    if (res==null) return false;
    rows.push(res);
    fieldname="editPersonGroupRelation";
  }
  else if (fieldname=="f_bereich") {      
    width=350; height=300;
    rows.push("<h3>Anpassung von "+f("bereich_id")+"</h3><p><small>Eine Person muss in mindestens einem zugeordnet sein</small>");
    $.each(masterData.auth.dep, function(k,a) {
      if (allPersons[id].access[a.id]==a.id) {
        rows[rows.length]="<p><input type=\"checkbox\" id=\"InputBereich"+a.id+"\"/ checked=\"true\"/>"; 
      }
      else  
        rows[rows.length]="<p><input type=\"checkbox\" id=\"InputBereich"+a.id+"\"/>"; 
      rows[rows.length]=" "+a.bezeichnung+"";
    }); 
  }
  else if (fieldname=="f_image") {
    width=300; height=300;
    rows[rows.length]="<p>Bitte nun ein Bild im Format JPG ausw&auml;hlen.<div id=\"upload_button\">Nochmal bitte...</div><p><div id=\"image_uploaded\"/>";
    rows.push('<p><small>max. '+Math.round(masterData.max_uploadfile_size_kb/1024)+'MB</small>');
  }
  else if (fieldname.indexOf("del_note")==0) {
    width=300; height=300;
    rows[rows.length]="Soll der Kommentar wirklich entfernt werden?";
    buttonText="Entfernen";
  } 
  else if (fieldname.indexOf("deletePerson")==0) {
    width=300; height=300;
    rows[rows.length]="Soll die Person wirklich gel&ouml;scht werden? Achtung, dieses L&ouml;schen ist endg&uuml;ltig und entfernt auch alle Gruppenbeziehungen etc.!";
  }
  else if (fieldname.indexOf("archivePerson")==0) {
    width=300; height=300;
    rows[rows.length]="Die Person wird in das Archiv genommen und ist nur noch mit Archiv-Rechten sichtbar. Wirklich ausf&uuml;hren";
  }
  else if (fieldname.indexOf("undoArchivePerson")==0) {
    width=300; height=300;
    rows[rows.length]="Soll die Person wird wieder zur&uuml;ck in die normale Liste genommen werden?";
  }
  else if (fieldname.indexOf("search_tag")==0) {
    this.setFilter("searchEntry",'tag:"'+masterData.tags[fieldname.substring(10,99)].bezeichnung+'"');
    allPersons[id].open=false;
    this.renderView();
    return false;
  }
  else if (fieldname.indexOf("del_tag")==0) {    
    allPersons[id].tags.splice($.inArray(fieldname.substring(7,99),allPersons[id].tags),1);
    churchInterface.jsendWrite({func:"delPersonTag", id:id, tag_id:fieldname.substring(7,99)}, null, false);
    this.renderList();      
    return false;
  }
  else if (fieldname.indexOf("add_tag")==0) {
    $("#add_tag_field"+id).toggle();
    $("#input_tag"+id).focus();
    return false;
  }
  else if (fieldname.indexOf("mailPerson")==0) {
    t.mailPerson(id,allPersons[id].vorname+" "+allPersons[id].name);
    return false;
  }
  else if (fieldname.indexOf("smsPerson")==0) {
    t.smsPerson([id]);
    return false;
  }
  else if (fieldname=="invitatePerson") {
    t.invitePerson(allPersons[id]);
    return false;
  }
  else
    alert("Sorry, "+fieldname+" ist noch nicht fertig.");

  rows[rows.length]='<input type="hidden" id="fields" value="'+fieldname+'"/><br/>';
  
  var elem = this.showDialog("Veränderung des Datensatzes", rows.join(""), width, height, {
    "Speichern": function() {
      t._saveEditEntryData(id, fieldname, renderViewNecessary, $(this));
    },
    "Abbrechen": function() {
      $(this).dialog("close");
    }
  });
  allPersons[id].inEdit=elem;

  // Jetzt kommt noch das Bild-Hochladen, kann erst hier stattfinden, da es  
  // auf den neuen Content referenziert.  
  if (fieldname=="f_image") {
    var uploader = new qq.FileUploader({
      element: document.getElementById('upload_button'),
      action: "?q=churchdb/uploadImage",
      params: {
        userid:id
      },
      multiple:false,
      debug:true,
      onComplete: function(file, response, res) {
//        $("#image_uploaded").html("<img src=\""+masterData.files_url+"/fotos/imageaddr"+id+".jpg?dummy="+Math.random()+"\"/>");
        $("#image_uploaded").html('<img src="'+masterData.files_url+"/fotos/"+res.filename+'"/><input type="hidden" id="uploadfilename" name="filename" value="'+res.filename+'"/>');
      }
    });    
  }  
  
  $("#InputGroupEntry,#InputGroupLeader").change(function(k) {
    var g_id=$("#InputGroupEntry").val();
    if (isNumber(g_id)) {
      var stats=groupView.getStatsOfGroup(g_id);
      if ((masterData.groups[g_id].max_teilnehmer!=null) && 
            (masterData.groups[g_id].max_teilnehmer<=stats.count_all_member)) {
        if ($("#InputGroupLeader").val()==0) {
          alert("Gruppe "+masterData.groups[g_id].bezeichnung+" hat schon die maximale Anzahl von "+masterData.groups[g_id].max_teilnehmer+" Teilnehmern erreicht. Bitte andere Gruppe nehmen.");
          $("#InputGroupEntry").val("");
        }
      }
    }
    else if ((g_id!="") && (g_id.indexOf("create")==0)) {
      var gt_id=g_id.substr(6,99);
      var form=new CC_Form(masterData.groupTypes[gt_id].bezeichnung+" erstellen");
      form.addInput({label:"Name der Gruppe", cssid:"name"});
      form.addHidden({cssid:"Inputf_grouptype", value:gt_id});
      form.addSelect({label:f("distrikt_id"), data:masterData.districts, cssid:"Inputf_district"});
      elem.dialog("close");
      var elem2=form_showDialog("Gruppe erstellen", form.render(null, "vertical"), 300, 350, {
        "Speichern": function() {
          var obj=form.getAllValsAsObject();
          obj.func="createGroup";
          churchInterface.jsendWrite(obj, function(ok, json) {        
            if (json.result=="exist") {
              alert("Gruppe mit dem Namen existiert schon!");
            }
            else {
              cdb_loadMasterData(function() {
                t.renderEditEntry(id, fieldname+gt_id, json.id);  
              })
            };            
          });        
          t.renderList();
          $(this).dialog("close");
        },
        "Abbrechen": function() {
          $(this).dialog("close");
        }    
     });
     elem2.find("form").submit(function() {
       return false;
     })
    }  
  });
};

PersonView.prototype.renderPersonGroupRelation = function(id, g_id) {
  var rows = new Array();
  // Muss erst pruefen, ob es sich um einen Leiter handelt und ein Gruppentyp, wo ein Leiter bleiben muss. 
  if ((masterData.groupTypes[masterData.groups[g_id].gruppentyp_id].muss_leiter_enthalten_yn==1) 
        && (allPersons[id].gruppe[g_id].leiter==1)) {
    var leiter_check=false;
    $.each(allPersons,function(k,a) {
      if ((a.id!=id) && (a.gruppe!=null))
        $.each(a.gruppe, function(i,b) {
          if ((b.id==g_id) && (b.leiter==1))
            leiter_check=true;
        });
    });
    if (!leiter_check) {
      alert("Nicht editierbar, da es sich um den einzigen Leiter der Gruppe handelt. " +
          "Gruppen von "+f("gruppentyp_id")+" "+masterData.groupTypes[masterData.groups[g_id].gruppentyp_id].bezeichnung+
          "' muessen mindestens einen Leiter haben.");
      return null;
    } 
  }
  
  rows.push("<table><tr><td><b>"+masterData.groupTypes[masterData.groups[g_id].gruppentyp_id].bezeichnung+"</b><td>");
  rows.push(masterData.groups[g_id].bezeichnung);
  rows.push('<input type="hidden" id="InputGroupEntry" value="'+g_id+'"/>');
  rows.push("<tr><td>Teilnehmerstatus &nbsp; <td>");
  
  rows.push("<select id=\"InputGroupLeader\">");
  $.each(masterData.groupMemberTypes, function(k,a) {
    rows.push("<option value=\""+k+"\""+ ((k==allPersons[id].gruppe[g_id].leiter)?"selected":"") + ">" + a.bezeichnung + "</option>");
  });
  rows.push("</select>");
  
  rows.push("<tr><td>Dabei seit<td>");
  var d=allPersons[id].gruppe[g_id].d;
  if (d==null) d=(new Date()).toStringEn();
  rows.push('<input type="text" id="InputDate" size="10" value="'+d.toDateEn().toStringDe()+'"/>');

  rows.push("<tr><td>Notiz<td>");
  var d= new Date();
  rows.push('<textarea id="InputComment" size="10" rows="3">');
    if (allPersons[id].gruppe[g_id].comment!=null) rows.push(allPersons[id].gruppe[g_id].comment);
  rows.push('</textarea>');

  rows.push("</table>");
  return rows.join("");
};

PersonView.prototype._saveEditEntryData = function (id, fieldname, renderViewNecessary, cover) {
  var t=this;
  var orig_obj=$.extend({}, allPersons[id]);
  obj=t.getSaveObjectFromInputFields(id, fieldname, allPersons[id]);
  
  // Wenn Adresse geaendert wurde, muessen dann neue Geo-Daten geholt werden.
  if (obj["func"]=='f_address')
    allPersons[id].geolat='';

  // Sind es definierte Felder?          
  if (masterData.fields[obj["func"]]!=null) {
  } 
  else if (obj["func"]=='f_note') {
    obj["note"] = $("#InputNote").val();
    obj["comment_viewer"] = $("#InputComment").val();
    allPersons[id].details=null;
  } 
  else if ((obj["func"]=='editPersonGroupRelation') || (obj["func"]=='addPersonGroupRelation')) {
    var arr = new Object();
    obj["date"]=$("#InputDate").val().toDateDe().toStringEn();
    obj["leader"]=$("#InputGroupLeader").val();
    if ($("#InputComment").val()!="") { 
      obj["comment"]=$("#InputComment").val();
      arr.comment=obj["comment"];
    }
    obj["g_id"]=$("#InputGroupEntry").val();
    arr.id=obj["g_id"];
    arr.d=obj["date"];
    arr.leiter=obj["leader"];
    if (obj["func"]=='editPersonGroupRelation') {
      arr.followup_count_no=allPersons[id].gruppe[arr.id].followup_count_no;
      obj["followup_count_no"]= arr.followup_count_no;
      arr.followup_add_diff_days=allPersons[id].gruppe[arr.id].followup_add_diff_days;
    }  

    // Abspeichern in der PersonenGruppe
    if (allPersons[id].gruppe==null) allPersons[id].gruppe = new Object(); 
    allPersons[id].gruppe[arr.id]=arr;
    
    // Wenn Followup und es ein Teilnehmer ist
    if ((masterData.groups[obj["g_id"]].followup_typ_id>0) && (obj["leader"]==0)) {
      if (allPersons[id].gruppe[arr.id].followup_count_no==null) {
        alert('Followup "'+masterData.followupTypes[masterData.groups[obj["g_id"]].followup_typ_id].bezeichnung+'" wurde gestartet!');
        arr.followup_count_no=1;
        obj["followup_count_no"]=1;
      }  
      renderViewNecessary=true;
    }                

  }
  else if (obj["func"]=='f_bereich') {
    var oneSelected=false;
    var newAccess=new Object();
    $.each(masterData.auth.dep, function(k,a) {
      $("input[id=InputBereich"+k+"]").each(function (i) {
        if ($(this).attr("checked")) {
          checked=1;
          oneSelected=true;
          newAccess[k]=k;
        }  
        else checked=0;
        obj["bereich"+k]=checked;
      });   
    }); 
    if (!oneSelected) {
      alert("Die Person muss mindestens einem Bereich zugewiesen werden!");
      return false;
    } else {
      allPersons[id].access=newAccess;            
    }
  }
  else if (obj["func"]=='f_image') {
    if ($("#uploadfilename").val()==null) {
      alert("Bitte erst Datei hochladen!");
      return null;
    }
    else {    
      obj["id"]=id;
      //obj["url"]="imageaddr"+id+".jpg";  
      obj["url"]=$("#uploadfilename").val();  
      allPersons[id].imageurl=obj["url"];
    }
  }
  else if (obj["func"].indexOf('del_note')==0) {
    obj["func"]="del_note";
    obj["comment_id"]=fieldname.substr(8,99);
    allPersons[id].details=null;
  }
  else if (obj["func"].indexOf('archivePerson')==0) {
    obj["func"]="archivePerson";
    allPersons[id].archiv_yn=1;
    renderViewNecessary=true;
  }
  else if (obj["func"].indexOf('undoArchivePerson')==0) {
    obj["func"]="undoArchivePerson";
    allPersons[id].archiv_yn=0;
    renderViewNecessary=true;
  }

  cover.html("Die Daten werden gespeichert...");
  
  churchInterface.jsendWrite(obj, function(ok, data) {
      cover.dialog("close");
      cover.html("");
      if (!ok) {
        alert("Fehler beim Speichern: "+data);
        allPersons[id]=orig_obj;
      }
      if (obj["func"]=="deletePerson") {
        allPersons[id]=null;
        t.renderList();
      } 
      else if (renderViewNecessary) { 
        churchInterface.getCurrentView().renderView();
        churchInterface.getCurrentView().renderTodos();
      }
      else {
        t.renderList(allPersons[id]);
        t.renderTodos();
      }
  });
};   

/**
 * Bereitet die Daten f�r ein renderSelect vor
 * withDataAuth: Auch die Datenautorisierung nehmen, default=ja
 */
function getAuthAsDataArray(withDataAuth) {
  var data = new Array();
  if (withDataAuth==null) withDataAuth=true;
  
  $.each(masterData.auth_table, function(k,a) {
    data.push(form_prepareDataEntry(-1,'== '+k+' =='));
    $.each(a, function(i,b) {
      if ((withDataAuth) || (b.datenfeld==null))
        data.push(form_prepareDataEntry(b.id,b.auth));
    });        
  });
  return data;
}

PersonView.prototype.msg_filterChanged = function (id, oldVal) {
  var t=this;

  // Wenn nicht "Meine Gruppen" ge�ndert wurden, ich aber Meine Gruppen ein intelligente Gruppe gefiltert habe
  if ((id!="filterMeine Gruppen") && (this.filter["filterMeine Gruppen"]!=null) && 
                 (this.filter["filterMeine Gruppen"].indexOf("filter")==0)) {
    delete this.filter["filterMeine Gruppen"];
    this.msg_filterChanged("filterMeine Gruppen",null);
  } 
  // Wenn "Meine Gruppen" ge�ndert wurde
  else if (id=="filterMeine Gruppen") {
    $("#cdb_group").html("");
    if (masterData.settings.selectedMyGroup!=t.filter['filterMeine Gruppen']) {
      masterData.settings.selectedMyGroup=t.filter['filterMeine Gruppen'];
      churchInterface.jsendWrite({func:"saveSetting", sub:"selectedMyGroup", val:(masterData.settings.selectedMyGroup==null?"null":masterData.settings.selectedMyGroup)});
    }
    // Wenn es mit "filter" anf�ngt, dann handelt es sich jetzt um intelligente Gruppen
    if ((typeof t.filter['filterMeine Gruppen']=="string") && (t.filter['filterMeine Gruppen'].indexOf("filter")==0)) {
      // Nun kopiere intelligente Gruppe in die Filter
      var merker=t.filter['filterMeine Gruppen'];
      t.filter=new cc_copyArray(masterData.settings.filter[t.filter['filterMeine Gruppen'].substr(6,99)]);
      t.makeMasterDataMultiselectFilter("Status", t.filter.filterStatus);
      t.makeMasterDataMultiselectFilter("Station", t.filter.filterStation);
      t.makeMasterDataMultiselectFilter("Bereich", t.filter.filterBereich, masterData.auth.dep);

      t.filter["filterMeine Gruppen"]=merker;
      t.renderFurtherFilter();
      t.renderTodos();
    }
    // Wenn es vorher eine intelligente Gruppe war, mu� ich nun Filter wieder l�schen
    else if ((typeof oldVal=="string") && (oldVal.indexOf("filter")==0)) {
      var merker=t.filter['filterMeine Gruppen'];
      t.resetPersonFilter();
      t.resetGroupFilter();  
      t.filter['filterMeine Gruppen']=merker;
      t.renderTodos();
      t.renderFurtherFilter();
    }
    if ((t.filter['filterMeine Gruppen']>0)) {
      if ((groupMeetingStats==null) || (groupMeetingStats[t.filter['filterMeine Gruppen']==null]))    
        cdb_loadGroupMeetingStats(t.filter, t.filter['filterMeine Gruppen']);
      if (churchInterface.getCurrentView()==personView)
        t.renderGroupEntry();
    } 
    t.renderFilter();
  }    
};



// Weitere Functions ohne Implementierung der AbstractView


PersonView.prototype.renderPersonFilter = function() {
  var t=this;
  
  var rows = new Array();
  
//  rows.push('<legend>Personenfilter</legend>');

   rows.push('<div style="float:right;margin-top:10px;margin-right:10px;"><a href="#" title="Filter zur&uuml;cksetzen" id="reset_personfilter"><img style="vertical-align:middle" width=20px src="'+masterData.modulespath+'/images/delete_2.png"/></a></div>'); 
   rows.push('<div class="well"><table cellpadding=5px>');
  
  rows.push("<tr><td>"+form_renderSelect({
    cssid:"filterFamilienstatus",
    data:masterData.familyStatus, 
    label:"Familenstatus", 
    selected: this.getFilter("filterFamilienstatus"),
    freeoption:true, type:"small"
  }));
  rows.push("<td>"+form_renderSelect({
    cssid:"filterGeschlecht",
    data:masterData.sex, 
    label:"Geschlecht", 
    selected: this.getFilter("filterGeschlecht"),
    freeoption:true, type:"small"
  }));
     
  rows.push("<td>"+form_renderInput({
    size:3,
    cssid:"ageFrom",
    label:"Alter von",
    placeholder:"von",
    value:this.getFilter("ageFrom"),      
    type:"xmini"
  }));
  rows.push("<td>"+form_renderInput({
    size:3,
    cssid:"ageTo",
    label:"bis",
    placeholder:"bis",
    value:this.getFilter("ageTo"),
    type:"xmini"
  }));
  rows.push("<td>"+form_renderInput({
    size:3,
    cssid:"plz",
    label:"PLZ",
    placeholder:"PLZ",
    value:this.getFilter("plz"),
    type:"mini"
  }));
  rows.push("<td>"+form_renderInput({
    size:3,
    cssid:"geburtsort",
    label:"Geburtsort",
    placeholder:"Geburtsort",
    value:this.getFilter("geburtsort"),
    type:"mini"
  }));
  rows.push("<td>"+form_renderSelect({
    cssid:"filterNationalitaet",
    data:masterData.nationalitaet, 
    label:"Nationalit&auml;t", 
    selected: this.getFilter("filterNationalitaet"),
    freeoption:true, type:"small"
  }));
  
  var _member="";
  $.each(masterData.status, function(b,k) {
    if (k.mitglied_yn==1)
      _member=_member+k.kuerzel+",";
  });
  
  rows.push('<td width="190px">');    
  rows.push(form_renderCheckbox({
    cssid:"isMember",
    checked:this.filter["isMember"],
    controlgroup:false,
    label:"Ist Mitglied"     
  }));        
   rows.push(form_renderCheckbox({
    cssid:"withoutEMail",
    checked:this.filter["withoutEMail"],
    controlgroup:false,
    label:"Kein E-Mail vorhanden"     
  }));        
   rows.push(form_renderCheckbox({
     cssid:"withoutPicture",
     checked:this.filter["withoutPicture"],
     controlgroup:false,
     label:"Kein Bild vorhanden"     
   }));        
  
 rows.push('</table><table cellpadding="5px" class=""><tr>');
  var data = new Array();
  $.each(masterData.fields.f_church.fields, function (b,k) {
    if (k["type"]=="date") 
      data.push(form_prepareDataEntry(k["sql"], k["text"]));
  });
  rows.push("<td>"+form_renderSelect({
    cssid:"filterDates",
    data:data, 
    label:"Datumsfilter", 
    selected: this.getFilter("filterDates"),
    freeoption:true, type:"medium", controlgroup:true
  }));
 
  
  rows.push("<td>"+form_renderInput({
    size:10,
    hint:"TT.MM.JJJJ oder z.Bsp. 1t, 1w oder 1m",
    cssid:"dateAfter",
    label:"ab",
    placeholder:"ab",
    value:this.getFilter("dateAfter"),
    type:"small", controlgroup:true      
  }));
  rows.push("<td>"+form_renderInput({
    size:10,
    hint:"TT.MM.JJJJ oder z.Bsp. 1t, 1w oder 1m",
    cssid:"dateBefore",
    label:"bis",
    placeholder:"bis",
    value:this.getFilter("dateBefore"),
    type:"small", controlgroup:true      
  }));
  
  rows.push('<td width="180px">'+form_renderCheckbox({
    cssid:"dateIgnoreYear",
    label:"Jahr ignorieren",
    controlgroup:false,
    checked:this.getFilter("dateIgnoreYear")
  }));
  rows.push(""+form_renderCheckbox({
    cssid:"dateNotSet",
    label:"Datum nicht vorhanden",
    controlgroup:false,
    checked:this.getFilter("dateNotSet")
  }));

  rows.push('</table><table cellpadding="5px" class=""><tr>');

  // FollowUp-Filter
  var data = new Array();
  data.push(form_prepareDataEntry(1,"aktiv"));
  data.push(form_prepareDataEntry(2,"&uuml;berf&auml;llig"));
  data.push(form_prepareDataEntry(3,"nicht im FollowUp"));
  rows.push("<td>"+form_renderSelect({
    cssid:"filterFollowUp",
    data:data, 
    label:"Followup ", 
    selected: this.getFilter("filterFollowUp"),
    freeoption:true, type:"medium", controlgroup:true
  }));

  if (this.filter["filterFollowUp"]>0) {
    var data = new Array();
    data.push(form_prepareDataEntry(1,"Step 1"));
    data.push(form_prepareDataEntry(2,"Step 2"));
    data.push(form_prepareDataEntry(3,"Step 3"));
    data.push(form_prepareDataEntry(4,"Step 4"));
    rows.push("<td>"+form_renderSelect({
      cssid:"filterFollowUpStep",
      label:"Step ",
      data:data, 
      selected: this.getFilter("filterFollowUpStep"),
      freeoption:true, type:"medium", controlgroup:true
    }));
  }
  
  if (masterData.auth.viewtags) {
    var z=0;
    do {
      rows.push("<td>&nbsp;<td>"+form_renderSelect({
        cssid:"filterTags"+z,
        data:masterData.tags, 
        label:"Tag "+(z+1), 
        selected: t.getFilter("filterTags"+z),
        freeoption:true, type:"medium", controlgroup:true
      }));
      z=z+1;
    } while (t.filter["filterTags"+(z-1)]!=null);
  }
  
  
  if (masterData.auth.adminpersons) {
    rows.push("<td> &nbsp;  &nbsp;  ");
    rows.push("<td>"+form_renderSelect({
      data:getAuthAsDataArray(true), 
      sort:false,
      label:"Zugriffsrechte",
      cssid:"filterAuth", 
      freeoption:true, type:"medium",
      selected:this.filter["filterAuth"]
    }) );

    rows.push("<td>"+form_renderCheckbox({
      cssid:"filterAuthNegativ",
      label:"!",
      checked:this.getFilter("filterAuthNegativ")
    }));
  }
  rows.push("</table>");

  rows.push('</div>');
  $("#furtherFilterDetail").html(rows.join(""));
};

PersonView.prototype.renderGroupFilter = function() {

  var rows = new Array();
  rows.push('<div style="float:right;margin-top:10px;margin-right:10px;"><a href="#" title="Filter zur&uuml;cksetzen" id="reset_groupfilter"><img style="vertical-align:middle" width=20px src="'+masterData.modulespath+'/images/delete_2.png"/></a></div>'); 
  rows.push('<div class="well"><table cellpadding="5px" class="">');
  
  if (this.filter["filterOn 1"]==null) this.filter["filterOn 1"]=true; 
  
  k=1;
  while (this.filter["filterOn "+k]!=null) {
    rows.push("<tr><td>");
    rows.push(form_renderSelect({
      data:masterData.groupFilterTypes,
      label:"Filter "+k,
      selected:this.filter["filterFilter "+k],
      cssid:"filterFilter "+k,
      controlgroup:true, type:"small"
    }));
    rows.push('<td>'+form_renderSelect({
      data:masterData.groupTypes,
      label:f("gruppentyp_id")+" "+k,
      selected:this.filter["filterTyp "+k],
      cssid:"filterTyp "+k,
      controlgroup:true, freeoption:true, type:"medium"
    }));

    rows.push("<td>");
    rows.push(form_renderSelect({
      data:masterData.groups,
      label:"Gruppe "+k,
      selected:this.filter["filterGruppe "+k],
      cssid:"filterGruppe "+k,
      controlgroup:true, freeoption:true, type:"medium",
      func:     function(a) {
        return (((t.filter["filterTyp "+k]=="") || t.filter["filterTyp "+k]==null || (a.gruppentyp_id==t.filter["filterTyp "+k])) 
            && (groupView.isAllowedToSeeDetails(a.id))
            && (masterData.groups[a.id].valid_yn==1)
            && ((t.filter["filterDistrikt "+k]==null) || (t.filter["filterDistrikt "+k]=="") || (t.filter["filterDistrikt "+k]==a.distrikt_id) ));
         }
    }));

    rows.push("<td>");
    
    rows.push(form_renderSelect({
      data:masterData.districts,
      label:f("distrikt_id")+" "+k,
      selected:this.filter["filterDistrikt "+k],
      cssid:"filterDistrikt "+k,
      controlgroup:true, freeoption:true, type:"medium"
    }));
    
    rows.push("<td>");

    rows.push(form_renderSelect({
      data:masterData.groupMemberTypes,
      label:"Teilnehmerstatus "+k,
      selected:this.filter["filterTeilnehmerstatus "+k],
      cssid:"filterTeilnehmerstatus "+k,
      controlgroup:true, freeoption:true, type:"medium",
      func:function(a) {return a.gruppentyp_id==t.filter["Teilnehmerstatus "+k];}
    }));
    rows.push("<td>"+form_renderImage({src:"trashbox.png", cssid:"delete-groupfilter-"+k, width:24}));
    
    rows.push("<tr><td>");
    if (k==1) {
      rows.push('<br/><p align="center"><input title="An=ODER oder Aus=UND-Verkn&uuml;pfung" type="checkbox" id="filterOr" '+(this.filter["filterOr"]!=null?"checked":"")+' > <small>ODER-Verkn&uuml;pfung</small> ');
    }

    if ((this.filter["filterFilter "+k]==null) || (this.filter["filterFilter "+k]==0)) {            
      rows.push("<td colspan=5><font style=\"font-size:8pt;\">Neu in der Gruppe ");
      rows.push("ab: <input type=\"text\" class=\"input-small\" size=\"10\"  title=\"TT.MM.JJJJ oder z.Bsp. 1t, 1w oder 1m\"  id=\"filterGruppeInAb "+k+"\"/ value=\""+this.getFilter("filterGruppeInAb "+k)+"\">&nbsp;&nbsp; &nbsp;");
      rows.push("<font style=\"font-size:8pt;\">Ist in der Gruppe seit mindestens: <input type=\"text\" class=\"input-small\" size=\"10\"  title=\"TT.MM.JJJJ oder z.Bsp. 1t, 1w oder 1m\" id=\"filterGruppeInSeit "+k+"\"/ value=\""+this.getFilter("filterGruppeInSeit "+k)+"\">&nbsp;");
    }
    else if (this.filter["filterFilter "+k]==2) {            
      rows.push("<td colspan=5><font style=\"font-size:8pt;\">War in der Gruppe ");
      rows.push("vom: <input type=\"text\" size=\"10\" class=\"input-small\"  title=\"TT.MM.JJJJ oder z.Bsp. 1t, 1w oder 1m\" id=\"filterGruppeWarInVon "+k+"\"/ value=\""+this.getFilter("filterGruppeWarInVon "+k)+"\">&nbsp;&nbsp;");
       rows.push("bis: <input type=\"text\" size=\"10\"  class=\"input-small\" title=\"TT.MM.JJJJ oder z.Bsp. 1t, 1w oder 1m\" id=\"filterGruppeWarInBis "+k+"\"/ value=\""+this.getFilter("filterGruppeWarInBis "+k)+"\">&nbsp;");
    }
    k=k+1;
  }
  rows.push("<tr><td>"+form_renderImage({src:"plus.png", cssid:"add-groupfilter", width:24}));
  rows.push("</table></div>");  
  $("#furtherFilterDetail").html(rows.join(""));
  $("#furtherFilterDetail a").click(function() {
    if ($(this).attr("id").indexOf("delete-groupfilter-")==0) {
      var i=$(this).attr("id").substr(19,99);
      var k=i*1+1;
      while (t.filter["filterTyp "+k]!=null) {
        t.filter["filterOn "+i]=t.filter["filterOn "+k];
        t.filter["filterTyp "+i]=t.filter["filterTyp "+k];
        t.filter["filterFilter "+i]=t.filter["filterFilter "+k];
        t.filter["filterGruppe "+i]=t.filter["filterGruppe "+k];
        t.filter["filterDistrikt "+i]=t.filter["filterDistrikt "+k];
        t.filter["filterTeilnehmerstatus "+i]=t.filter["filterTeilnehmerstatus "+k];
        t.filter["filterfilterGruppeInAb "+i]=t.filter["filterfilterGruppeInAb "+k];
        t.filter["filterfilterGruppeInSeit "+i]=t.filter["filterfilterGruppeInSeit "+k];
        t.filter["filterfilterGruppeWarInVon "+i]=t.filter["filterfilterGruppeWarInVon "+k];
        t.filter["filterfilterGruppeWarInBis "+i]=t.filter["filterfilterGruppeWarInBis "+k];
        i=k;
        k=k+1;
      }
      delete t.filter["filterOn "+i];
      delete t.filter["filterTyp "+i];
      delete t.filter["filterFilter "+i];
      delete t.filter["filterGruppe "+i];
      delete t.filter["filterDistrikt "+i];
      delete t.filter["filterTeilnehmerstatus "+i];
      delete t.filter["filterfilterGruppeInAb "+i];
      delete t.filter["filterfilterGruppeInSeit "+i];
      delete t.filter["filterfilterGruppeWarInVon "+i];
      delete t.filter["filterfilterGruppeWarInBis "+i];
    }
    else if ($(this).attr("id").indexOf("add-groupfilter")==0) {
      var k=1;
      while (t.filter["filterOn "+k]!=null) k=k+1;
      t.filter["filterOn "+k]="";
    }
  });
};



PersonView.prototype.renderRelationFilter = function() {
  var t=this;
  var rows = new Array();
  
//  rows.push('<legend>Personenfilter</legend>');

   rows.push('<div style="float:right;margin-top:10px;margin-right:10px;"><a href="#" title="Filter zur&uuml;cksetzen" id="reset_relationfilter"><img style="vertical-align:middle" width=20px src="'+masterData.modulespath+'/images/delete_2.png"/></a></div>'); 
   rows.push('<div class="well"><table cellpadding="5px"><tr>');


  var data= new Array();
  $.each(masterData.relationType, function (k,b) {
    data.push(form_prepareDataEntry("k_"+b.id, b.bez_kind));
    if (b.bez_kind!=b.bez_vater) {
      data.push(form_prepareDataEntry("v_"+b.id, b.bez_vater));
    }  
  });
  rows.push("<td>"+form_renderSelect({
    cssid:"filterRelations",
    data:data, 
    label:"Wer hat ein", 
    selected: this.getFilter("filterRelations"),
    freeoption:true, type:"medium", controlgroup:true
  }));
  rows.push("<td>"+form_renderCheckbox({
    cssid:"filterRelationNegativ",
    label:"!",
    checked:this.getFilter("filterRelationNegativ")
  }));

  rows.push("<tr>");

  
  rows.push("<td>"+form_renderSelect({
    cssid:"filterRelationExt",
    data:data, 
    label:"Wer hat ein ", 
    selected: this.getFilter("filterRelationExt"), controlgroup:true,
    freeoption:true, type:"medium", controlgroup:true
  }));

  rows.push("<td>");

  rows.push(form_renderSelect({
    data:masterData.groupTypes,
    label:"in "+f("gruppentyp_id"),
    selected:this.filter["filterRelationExtGroupTyp"],
    cssid:"filterRelationExtGroupTyp",
    controlgroup:true, freeoption:true, type:"medium"
  }));
  
  rows.push("<td>");

  rows.push(form_renderSelect({
    data:masterData.groups,
    label:"in Gruppe ",
//    separator:": ",
    selected:this.filter["filterRelationExtGroup"],
    cssid:"filterRelationExtGroup",
    controlgroup:true, freeoption:true, type:"medium",
    func:     function(a) {
      return ((a.gruppentyp_id==t.filter["filterRelationExtGroupTyp"]) && (groupView.isAllowedToSeeDetails(a.id))
          && (masterData.groups[a.id].valid_yn==1));
       }
  }));
  
  rows.push("</table>");

  rows.push('</div>');
  $("#furtherFilterDetail").html(rows.join(""));
};


PersonView.prototype.renderFurtherFilter = function () {
//  $("#divaddfilter").html('<div id="addfilter" style="width:100%;"><div style="height:200px"/></div>');
  if (this.furtherFilterVisible) {
    $("#divaddfilter").animate({ height: 'show'}, "medium");  

    var t=this;
    var rows=new Array();
    rows.push('<ul class="nav nav-pills">');
    if (masterData.auth.viewalldata)
      rows.push('<li class="'+(t.currentFurtherFilter=="person"?"active":"")+'"><a href="#" data-filter="person" class="filter">Personenfilter</a>');
    rows.push('<li class="'+(t.currentFurtherFilter=="gruppe"?"active":"")+'"><a href="#" data-filter="gruppe" class="filter">Gruppenfilter</a>');
    if (masterData.auth.viewalldetails)
      rows.push('<li class="'+(t.currentFurtherFilter=="beziehung"?"active":"")+'"><a href="#" data-filter="beziehung" class="filter">Beziehungsfilter</a>');
    rows.push('<li class="pull-right"><a href="#" class="hide">Filter schließen</a>');
    rows.push('</ul>');
    rows.push('<div id="furtherFilterDetail"></div>');
    
    $("#addfilter").html(rows.join(""));  

    if (t.currentFurtherFilter=="person")
      t.renderPersonFilter();
    else if (t.currentFurtherFilter=="beziehung")
      t.renderRelationFilter();
    else
      t.renderGroupFilter();
    
    $("#divaddfilter a.hide").click(function() {
      t.furtherFilterVisible=false;
      $("#divaddfilter").animate({ height: 'hide'}, "fast");
      return false;
    });
    $("#divaddfilter a.filter").click(function() {
      t.currentFurtherFilter=$(this).attr("data-filter");
      t.renderFurtherFilter();
      return false;
    });
    
    this.implantStandardFilterCallbacks(this, "addfilter");
  
    $("#furtherFilterDetail select").change(function(c) {
      // Es muessen nun noch die weiteren Filter neu gerendet werden, wenn Aenderungen in der Gruppe erfolgt sind,
      // damit diese nun angepasst werden. Z.B. Kleingruppen zum Distrikt
      t.renderFurtherFilter();
    }); 
    
    $("#furtherFilterDetail a").click(function(c) {
      var renderList=true;
      if ($(this).attr("id")=="reset_personfilter") {
        t.resetPersonFilter();
      } 
      else if ($(this).attr("id")=="reset_groupfilter") {
        t.resetGroupFilter();   
      }
      else if ($(this).attr("id")=="reset_relationfilter") {
        t.resetRelationFilter();   
      }
      else {
        t.filter[$(this).attr("id")]=$(this).attr("checked");
      }
      if (renderList) {
        t.renderFurtherFilter();
        listOffset=0;
        t.renderList(null, false);
      }  
      return false;
    });           
  }  
  else 
    $("#divaddfilter").animate({ height: 'hide'}, "fast");  

};

PersonView.prototype.resetPersonFilter = function() {
  delete this.filter["plz"];
  delete this.filter["ageFrom"]; 
  delete this.filter["ageTo"];
  delete this.filter["dateBefore"]; 
  delete this.filter["dateAfter"];
  delete this.filter["dateIgnoreYear"];
  delete this.filter["filterNationalitaet"];
  delete this.filter["geburtsort"];
  delete this.filter["isMember"];
  delete this.filter["withoutPicture"];
  delete this.filter["withoutEMail"];
  delete this.filter["dateNotSet"];
  delete this.filter["filterDates"];
  delete this.filter["searchChecked"];
  delete this.filter["filterMeine Gruppen"]; 
  delete this.filter["filterStation"]; 
  delete this.filter["filterBereich"]; 
  delete this.filter["filterStatus"];
  // Todo-filter
  delete this.filter["followupOverdue"];
  delete this.filter["followupToday"];
  delete this.filter["groupSubscribe"];
  delete this.filter["groupDelete"];
  
  
  delete this.filter["filterGeschlecht"]; 
  delete this.filter["filterFamilienstatus"];
  z=0;
  while (this.filter["filterTags"+z]!=null) {
    delete this.filter["filterTags"+z];
    z=z+1;
  }
  delete this.filter["filterFollowUp"];
  delete this.filter["filterFollowUpStep"];
};


PersonView.prototype.resetRelationFilter = function() {
  delete this.filter["filterRelations"];
  delete this.filter["filterRelationNegativ"];
  delete this.filter["filterRelationExt"];
  delete this.filter["filterRelationExtGroupTyp"];
  delete this.filter["filterRelationExtGroup"];  
};

PersonView.prototype.resetGroupFilter = function () {
  var t=this;
  k=1;
  delete t.filter["filterOr"];
  while (t.filter["filterOn "+k]!=null) {
    delete t.filter["filterTyp "+k];
    delete t.filter["filterFilter "+k];
    delete t.filter["filterDistrikt "+k];
    delete t.filter["filterGruppe "+k];        
    delete t.filter["filterGruppeInAb "+k];        
    delete t.filter["filterGruppeInSeit "+k];        
    delete t.filter["filterGruppeWarInVon "+k];        
    delete t.filter["filterGruppeWarInBis "+k];        
    delete t.filter["filterGruppeWarInBis "+k];
    delete t.filter["filterTeilnehmerstatus "+k];
    if (k>0)
      delete t.filter["filterOn "+k];
    k=k+1;  
  }  
};

/**
 * Baut die Funktion fuer die Eintragungen der Teilnahme an Gruppen
 */
PersonView.prototype.renderGroupEntry = function() {
  t=this;
  // Start function renderGroupEntry()  
  $("#cdb_group").html("");
  if ((this.filter['filterMeine Gruppen']>0) 
      && (allPersons[masterData.user_pid]!=null) 
      && (masterData.groups[this.filter['filterMeine Gruppen']]!=null)
      && (masterData.groups[this.filter['filterMeine Gruppen']].meetingList==null)
      &&
         (
            (allPersons[masterData.user_pid].districts!=null 
                   && allPersons[masterData.user_pid].districts[masterData.groups[this.filter['filterMeine Gruppen']].distrikt_id]!=null) 
            || (allPersons[masterData.user_pid].gruppentypen!=null && allPersons[masterData.user_pid].gruppentypen[masterData.groups[this.filter['filterMeine Gruppen']].gruppentyp_id]!=null) 
            || (allPersons[masterData.user_pid].gruppe[this.filter['filterMeine Gruppen']].leiter>=1  
                   && allPersons[masterData.user_pid].gruppe[this.filter['filterMeine Gruppen']].leiter<=2)) 
     ) {
      t.loadGroupMeetingList(this.filter['filterMeine Gruppen']);
   } 
   else {
     t.renderGroupContent(this.filter['filterMeine Gruppen']);    
   }
};
PersonView.prototype.loadGroupMeetingList = function (g_id) {
  var t=this;
  masterData.groups[g_id].meetingList="get data";
  churchInterface.jsendWrite({ func: "GroupMeeting", sub:"getList", g_id: g_id}, function(ok, json) {
    if (json!=null) {
      masterData.groups[g_id].meetingList=json;  
      t.renderGroupContent(g_id);
      t.renderList();
    }
    else         // Dann lege ich ein leeres Array drauf, damit es nicht nochmal geladen wird
      masterData.groups[g_id].meetingList=new Array();
  });
};
    
PersonView.prototype.exportData = function() {
  var t=this;
  var i=masterData.max_exporter;
  if (masterData.auth["export"])
	  i=99999;
  var exportIds="";
  
  var rels=new Object();
  $.each(allPersons, function(k, a) {
    if ((i>0) && (t.checkFilter(a))) {
      i--;
      exportIds=exportIds+a.id+",";
      if (a.rels!=null) {
        $.each(a.rels, function(i,b) {
          if (masterData.relationType[b.beziehungstyp_id].export_aggregation_yn==1)
            if (((b.vater_id==a.id) && (t.checkFilter(allPersons[b.kind_id]))) ||
                ((b.kind_id==a.id) && (t.checkFilter(allPersons[b.vater_id]))))
              rels[b.beziehungstyp_id]=b.beziehungstyp_id;          
        });   
      }
    }
  });  
  // Weil hinten ein Komma steht einfach eine -1 ergaenzen, die ID gibt es nicht.
  exportIds=exportIds+"-1";
  
  if (this.filter["filterMeine Gruppen"]!=null) 
    exportIds=exportIds+"&groupid="+this.filter["filterMeine Gruppen"];

  if (i==0) alert(unescape("Es d%FCrfen nur max. "+masterData.max_exporter+" Eintr%E4ge exportiert werden. Bitte genauer filtern%21"));
  else {
  	if (this.filter["filterRelations"]!=null) {
  	  this.showDialog("Beziehungen exportien", "Es wird momentan nach Beziehungen gefiltert, sollen die durch die Beziehung verbundene Personen mit exportiert werden?",
  	      300,300, {
  	      "Ja": function() {
            agg="&rel_part="+t.filter["filterRelations"].substr(0,1);
            agg=agg+"&rel_id="+t.filter["filterRelations"].substr(2,99);       
            // Und los geht es
  	        var Fenster = window.open("?q=churchdb/export&ids="+exportIds+agg);     
            $(this).dialog("close");
  	      },
  	      "Nein": function() {
  	        // Und los geht es
  	        var Fenster = window.open("?q=churchdb/export&ids="+exportIds);     
  	        $(this).dialog("close");
  	      }
  	  });  	  
  	} 
  	else if (masterData.auth.viewalldata) {
  	  var txt="";
      $.each(rels, function(k,a) {
        txt=txt+"<input type=\"checkbox\" id=\"cb_"+a+"\" class=\"cdb-checkbox\"></input> &nbsp;"+masterData.relationType[a].bez_vater+"/"+masterData.relationType[a].bez_kind+"<br/>";
      });
      if (txt!="") {
        this.showDialog("Beziehungen zusammenfassen", "Es wurden Beziehungen gefunden, welche sollen zusammengefasst werden?<br/><br/>"+txt,
            400, 350, {
            "Ok": function() {
              var agg="";
              $.each(rels, function(k,a) {
                if ($("#cb_"+a).attr("checked")) {
                  agg=agg+"&agg"+a+"=y";
                }
              });
              // Und los geht es
              var Fenster = window.open("?q=churchdb/export&ids="+exportIds+agg);     
              $(this).dialog("close");
            },
            "Abbrechen": function() {
              $(this).dialog("close");
            }
        });     
      }
      else {
        var Fenster = window.open("?q=churchdb/export&ids="+exportIds);     
      }
  	}
    else { 
      if (!groupView.isPersonLeaderOfGroup(masterData.user_pid, this.getFilter("filterMeine Gruppen")))
        alert("Um zu exportieren muss unter 'Meine Gruppen' eine Gruppe gefiltert werden, in der Du Leiter bist.");
      else
        var Fenster = window.open("?q=churchdb/export&ids="+exportIds);
    }
  }
};

PersonView.prototype.renderGroupContent = function(g_id) {
  var t=this;
  var json=null;
  if ((masterData.groups!=null) && (masterData.groups[g_id]!=null))
    json=masterData.groups[g_id].meetingList;  
  var rows = new Array();
  if (masterData.settings.selectedGroupType==-4) {
    if (g_id==null) {
      delete masterData.settings.selectedGroupType;
      t.renderGroupContent();
      return false;
    }
    rows.push('<div class="well">');
      if (json!=null) {
        rows.push('<legend>Gruppentreffen <a href="#" id="a_gruppenliste">'+masterData.groups[g_id].bezeichnung+'</a> im <i>'+monthNames[t.gruppenteilnehmerdatum.getMonth()]+" "+t.gruppenteilnehmerdatum.getFullYear()+'</i></legend>');
        rows.push(form_renderButton({label:"<< Monat zur&uuml;ck", cssid:"btn_monatback"})+" ");
        rows.push(form_renderButton({label:"Monat vor >>", cssid:"btn_monatfurther"}));
      }
      rows.push('<span class="pull-right">');
      rows.push(form_renderButton({label:"Gruppentreffen hinzuf&uuml;gen", cssid:"btn_addGroupMeetingDate"})+"&nbsp;");
      rows.push(form_renderButton({label:"Pflege beenden", cssid:"btn_gruppentreffen"}));
      rows.push('</span>');
    rows.push('</div>');
  }
  else {
    var rows2 = new Array();
    var datumvon=new Date();
    gruppentreffen_id=-1;
    if (json!=null) {
      $.each(churchcore_sortData(json,"datumvon"), function(k,a) {
        if (a.eintragerfolgt_yn==0) {
          rows2.push('<legend>'+form_renderImage({src:"persons.png"})+'&nbsp;Bitte noch ein Gruppentreffen pflegen...</legend>');
          rows2.push('<span class="pull-right">')
          rows2.push('<input type="button" class="btn" value="Auswahl absenden"/>&nbsp;');
          rows2.push('<input type="button" class="btn" value="Treffen ausgefallen"/>');
          rows2.push('</span>');
          if (a.datumvon.toDateEn(true).toStringDe(true)==a.datumbis.toDateEn(true).toStringDe(true))
            rows2.push("<p><b>Wer war am "+a.datumvon.toDateEn(true).toStringDe(true)+" da?</b>");
          else
            rows2.push("<p><b>Wer war in der Zeit vom "+a.datumvon.toDateEn().toStringDe()+" - "+a.datumbis.toDateEn().toStringDe()+" da?</b>");
          rows2.push('<br><small>Bitte die Personen markieren, die bei dem Treffen dabei gewesen sind. Dann auf "Auswahl absenden" klicken.</small>');
          gruppentreffen_id=a.id;
          datumvon=a.datumvon.toDateEn(true);
          return false;
        } 
      });
      // Kein zu pflegende Gruppe gefunden, also rows wieder l�schen.
      if (gruppentreffen_id==-1)                 
        rows2 = new Array();
    }
    
    if (rows2.length>0)
      rows.push('<div class="well">'+rows2.join("")+'</div>');
  }
  $("#cdb_group").html(rows.join(""));

  // Callbacks
  $("#cdb_group input, #cdb_group a").click(function(c) {
    if ($(this).val()=="Auswahl absenden") {
      var arr=new Array();
      $.each(allPersons, function(a,k) {
        if (k.checked) arr.push(k.id);
      });
      
      var form=new CC_Form();       
      form.addHtml('<p>'+arr.length+' Personen einchecken</p>');
      form.addInput({label:"Anzahl G&auml;ste", value:0, cssid:"anzahl_gaeste"});
      form.addTextarea({label:"Kommentar", rows:4, data:"", placeholder:"Kommentar", cssid:"kommentar"});
      form.addCheckbox({label:"Markierung der Personen wieder entfernen?", checked:true, cssid:"demarkPersons"});
      
      var elem=form_showDialog("Treffen "+datumvon.toStringDe(true), form.render(null, "vertical"), 360, 400, {
        "Speichern": function() {
          var obj=form.getAllValsAsObject();
          obj.func="GroupMeeting";
          obj.sub="saveProperties";
          obj.id=gruppentreffen_id;
          obj.g_id=g_id;
          obj.entries=arr;
          churchInterface.jsendWrite(obj, function(ok) {
            if (!ok) {
              alert("Es gab ein Fehler beim Speichern!");
            } else {
              masterData.groups[g_id].meetingList=null;
              if (obj.demarkPersons) {
                $.each(allPersons, function(k,a) {
                  a.checked=false;
                });
              }
              t.renderGroupEntry();
              t.renderList();
            }
          });        
          $(this).dialog("close");
        },
        "Abbrechen": function() {
          $(this).dialog("close");
        }
      });
    }
    else if ($(this).attr("id")=="a_gruppenliste") {
      groupView.clearFilter();
      groupView.setFilter("filterMeine Gruppen", t.filter["filterMeine Gruppen"]);
      churchInterface.setCurrentView(groupView);
      return false;
    }
    else if ($(this).attr("id")=="btn_addGroupMeetingDate") {
      t.addGroupMeetingDate();
    }
    else if ($(this).attr("id")=="btn_gruppentreffen") {
      var gt=churchcore_getFirstElement(masterData.groupTypes);
      var id=null;
      if (gt!=null) id=gt.id;      
      masterData.settings.selectedGroupType=id;
      churchInterface.jsendWrite({func:"saveSetting", sub:"selectedGroupType", val:id});

      t.renderGroupEntry();
      t.renderList();
    }
    else if ($(this).attr("id")=="btn_monatback") {
      t.gruppenteilnehmerdatum.setMonth(t.gruppenteilnehmerdatum.getMonth()-1);
      t.renderGroupEntry();
      t.renderList();
    }
    else if ($(this).attr("id")=="btn_monatfurther") {
      t.gruppenteilnehmerdatum.setMonth(t.gruppenteilnehmerdatum.getMonth()+1);
      t.renderGroupEntry();
      t.renderList();
    }
    else {
      churchInterface.jsendWrite({ func: "GroupMeeting", sub:"canceled", gt_id: gruppentreffen_id }, function(oi, json) {
        // info l�schen, damit er neue Infos holt.
        masterData.groups[t.filter['filterMeine Gruppen']].meetingList=null;
        t.renderGroupEntry();
        t.renderList();
      });
      $("#cdb_group").html("");
    };
  });
};

PersonView.prototype.smser = function() {
  var t=this;
  var arr=new Array();
  $.each(allPersons, function(k, a) {
    if (t.checkFilter(a)) {
      arr.push(a.id);
    }
  });  
  if (arr.length>0) {
    t.smsPerson(arr);
  }
};

/**
 * 
 * @param arr mit Ids
 */
PersonView.prototype.smsPerson = function(arr) {
  var form = new CC_Form("SMS an "+arr.length+" Personen versenden");
  form.addTextarea({label:'SMS-Text', rows:4, cssid:"smstxt",data:""});
  form.addHtml('<div class="control-group controls"><p><small><span id="count_zeichen"></span></small></div>');
  form.addHtml("<p><small>Hinweis: Es kann [Vorname], [Nachname] und [Spitzname] verwendet werden.</small>");
  var elem = t.showDialog("SMS versenden", form.render(false, "horizontal"), 500, 350, {
    "Senden": function() {
        var obj=form.getAllValsAsObject();
        obj.func="sendsms";
        obj.ids=arr;
        churchInterface.jsendWrite(obj, function(ok, status) {
          if (ok) {
            if (status.withoutmobilecount>0) {
              alert(status.withoutmobilecount+" Person haben keine Handynummer gespeichert.");
            }
            $.each(arr, function(k,a) {
              $("tr[id="+a+"]").after('<tr id="detail'+a+'"><td colspan="10">SMS-Versand Ergebnis: '+status[a]);
            });
          }
          else 
            alert(status);
        });
      $(this).dialog("close");            
    },          
    "Abbrechen": function() {
      $(this).dialog("close");
    }
  });
  elem.find("#smstxt").keyup(function() {
    if ($(this).val().length>1)
      elem.find("#count_zeichen").html(($(this).val().length)+" Zeichen");
    else
      elem.find("#count_zeichen").html("");
  });
};

PersonView.prototype.mailer = function() {
  
  if ((!masterData.auth.viewalldata) && (!groupView.isPersonLeaderOfGroup(masterData.user_pid, this.getFilter("filterMeine Gruppen")))) {
    alert("Um den E-Mailer zu nutzen muss unter 'Meine Gruppen' eine Gruppe gefiltert werden, in der Du Leiter bist.");
    return null;
  }
  
  var t=this;
  var counter=0;
  var maxMails=masterData.max_exporter;
  if (masterData.auth["export"]) maxMails=99999;
  var mailTo="";
  var ids="";
  var noEmail=false;
  var separator=(masterData.settings.mailerSeparator==0?";":",");
  if (masterData.settings.mailerType!=0)
    separator=separator+" ";
  $.each(allPersons, function(k, a) {
    if ((counter<=maxMails) && (t.checkFilter(a))) {
      if ((a.email!=null) && (a.email!="")) {
        counter++;
        // Wenn ich das in ein Extra Fenster ausgeben, k�nnen auch die Namen dazu
        mailTo=mailTo+$.trim(a.email)+separator;
        ids=ids+a.id+",";
      }  
      else noEmail=true;    
    }
  });    
  if (counter>=maxMails) alert(unescape("Es d%FCrfen nur max. "+masterData.max_exporter+" Eintr%E4ge exportiert werden. Bitte genauer filtern%21"));
  else {
//    if (noEmail) alert("Hinweis: Einige Einträge haben keine E-Mailadresse, diese wurden nicht berücksichtigt!");
    if ((noEmail) && confirm("Einige Personen haben keine E-Mail-Adresse, sollen diese anschließend angezeigt werden?")) {
      t.filter["withoutEMail"]=1;
      if (!t.furtherFilterVisible) { 
        t.furtherFilterVisible=true;  
        t.renderFurtherFilter();
      }
      t.renderList();
    }
    // Und los geht es
    if (masterData.settings.mailerType==0) {
      if (masterData.settings.mailerBcc==null)
        masterData.settings.mailerBcc=0;
      var string ="";
      if (masterData.settings.mailerBcc==0)
        string="mailto:"+mailTo;
      else 
        string="mailto:"+allPersons[masterData.user_pid].email+"?bcc="+mailTo;
      var Fenster = window.open(string,"Mailer");
      window.setTimeout(function() {
        Fenster.close();
      },500);
    } 
    else if (masterData.settings.mailerType==1) {
      var Fenster = window.open("", "E-Mail-Adresse","width=500,height=300");
      Fenster.document.write(mailTo);
      Fenster.focus();
    }
    else if (masterData.settings.mailerType==2) {
      t.mailPerson(ids+"-1", mailTo.trim(60));
    }
  }
};



function _createEntry(id, bez) {
  var arr = new Array();
  arr.id=id;
  arr.bezeichnung=bez;
  return arr;
}

PersonView.prototype.getMyGroupsSelector = function(withIntelligentGroups) {
  var currentView=churchInterface.getCurrentView();

  if (withIntelligentGroups==null) withIntelligentGroups=false;
  var arr = new Array();
  var first_id = -1;
  
  if ((masterData.settings.selectedMyGroup!=null) && (masterData.settings.selectedMyGroup!="null") && 
      (currentView.filter['filterMeine Gruppen']!=masterData.settings.selectedMyGroup))
    // Wenn es keine Intelligente Gruppe gibt, sollte er sie auch nicht vorselektieren!
    if ((withIntelligentGroups) || (masterData.settings.selectedMyGroup.indexOf("filter")!=0)) {
      if ((currentView.filter.searchEntry==null && currentView==personView)) {
        currentView.filter['filterMeine Gruppen']=masterData.settings.selectedMyGroup;
        this.msg_filterChanged("filterMeine Gruppen",null);
      }
    }

  // If current person already loaded and available
  if ((masterData.user_pid!=null) && (allPersons[masterData.user_pid]!=null)) {
    // Vergleichsdatum fuer die Listenauswahl, alle Gruppen wo ich Leiter bin und
    // die kein Abschlussdatum haben oder das Abschlussdatum 14 Tage zurueck liegt
    d = new Date(); d.addDays(-14);
    arr.push(_createEntry("",""));

    // Leiter und Co-Leiter, sowie Supervisor, nicht aber Mitarbeiter
    var _owngroups_leader = new Array();
    
    // Nun Gruppen, wo ich Leiter bin
    if (allPersons[masterData.user_pid].gruppe!=null)
      $.each(allPersons[masterData.user_pid].gruppe, function (b,k) {    
        if ((masterData.groups[k.id].abschlussdatum==null) || (masterData.groups[k.id].abschlussdatum.toDateEn()>d)) {
          if ((k.leiter>=1) && (k.leiter<=3)) {
            _owngroups_leader.push(masterData.groups[k.id]);
            if (first_id==-1) first_id=k.id;
          }
        }
      });
    
    // Gruppen, wo ich Distriktleiter bin
    if (allPersons[masterData.user_pid].districts!=null)
      $.each(allPersons[masterData.user_pid].districts, function (c,district) {    
        $.each(masterData.groups, function (b,group) {    
          if ((group.distrikt_id==district.distrikt_id) && ((group.abschlussdatum==null) || (group.abschlussdatum.toDateEn()>d))) {
            if (!churchcore_inArray(group, _owngroups_leader)) {
              _owngroups_leader.push(group);
              if (first_id==-1) first_id=group.id;
            }
          }
        });
      });
    // Gruppen, wo ich Gruppentyp bin
    if (allPersons[masterData.user_pid].gruppentypen!=null)
      $.each(allPersons[masterData.user_pid].gruppentypen, function (c,district) {    
        $.each(masterData.groups, function (b,group) {    
          if ((group.gruppentyp_id==district.gruppentyp_id) && ((group.abschlussdatum==null) || (group.abschlussdatum.toDateEn()>d))) {
            if (!churchcore_inArray(group, _owngroups_leader)) {
              _owngroups_leader.push(group);
              if (first_id==-1) first_id=group.id;
            }
          }
        });
      });
    
    
    if (_owngroups_leader.length>0) {
      arr.push(_createEntry(-1, "-- Leiter --"));
      arr=arr.concat(churchcore_sortArray(_owngroups_leader,"bezeichnung"));
    }
    
    // Teilnehmer und Mitarbeiter
    var _owngroups_member = new Array();
    if (allPersons[masterData.user_pid].gruppe!=null)
      $.each(allPersons[masterData.user_pid].gruppe, function (b,k) {    
        if ((masterData.groups[k.id].abschlussdatum==null) || (masterData.groups[k.id].abschlussdatum.toDateEn())>d) {
          if (((k.leiter==0) && (groupView.isGroupViewableForMembers(k.id))) || ((k.leiter==4) && (masterData.groups[k.id].versteckt_yn==0))) {
            _owngroups_member.push(masterData.groups[k.id]);
            if (first_id==-1) first_id=k.id;
          }
        }
      });
    if (_owngroups_member.length>0) {
      arr.push(_createEntry(-1, "-- Teilnehmer --"));
      arr=arr.concat(churchcore_sortArray(_owngroups_member,"bezeichnung"));
    }

    // Intelligente Gruppen (Gespeicherte Filter)
    if ((withIntelligentGroups) && (masterData.settings.filter!=null)) {
      var _owngroups_intelligent= new Array();
      $.each(masterData.settings.filter, function(k,a) {
        var entry = new Array();
        entry.id="filter"+k;
        entry.bezeichnung="&nbsp; "+k;
        _owngroups_intelligent.push(entry);
      });
      if (_owngroups_intelligent.length>0) {
        arr.push(_createEntry(-1, "-- Intellig. Gruppen --"));
        arr=arr.concat(churchcore_sortArray(_owngroups_intelligent,"bezeichnung"));
      }  
    }
    
    if (arr.length==0) 
      arr.push(_createEntry(-1, "Keine eigene Gruppe"));
    else if ((currentView.filter['filterMeine Gruppen']==-1) && (currentView.filter.searchEntry==null))
      currentView.filter['filterMeine Gruppen']=first_id;
    
    return arr;    
  } 
  return false;
};
