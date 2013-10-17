/*
 * 
 * allPersons
 * allEvents
 * masterData.groups
 * 
 * STAMMDATEN
 * masterData.cs_category
 * masterData.cdb_distrikt
 * 
 */


// Aktuell ausgewählte Gruppe
var gruppe_id=null;
// Aktuell ausgewählte Person
var person_id=null;
// Aktuelle ausgewählte Event
var event_id=null;
// Letzte erfolgte Checkin
var last_person_id=null;
// eingechechte zeigen
var view_checkin=false;
// Einfache Ansicht
var view_easy=false;

var allPersons=null;
var allEvents=null;

var search_person="";



function renderDatumsname() {
  if ((event_id==null) || (allEvents[event_id]==null))
    $('#datumsname').html("");
  else {
    var b=" &nbsp; "+allEvents[event_id].datumvon.toDateEn(true).toStringDe(true);
    if (allEvents[event_id].bezeichnung!=null) b=b+" - "+allEvents[event_id].bezeichnung;
    $('#datumsname').html(b);
  }
}

function renderEvent(full) {
  if (full==null) full=true;
  
  if (full) {
    var rows= new Array();  
    rows.push('<h3>Veranstaltungen &nbsp;'+(view_easy?"":form_renderImage({cssid:"event_config", width:24, src:"options.png"}))+'</h3>');    
    rows.push('<span class="veranstaltung">Kein JavaScript?</span>');  
    rows.push('<p></p>');
    //rows.push(form_renderButton({label:"Neue hinzuf&uuml;gen", htmlclass:""})+"&nbsp;");
    if (!view_easy)
      rows.push(form_renderButton({label:"Hinzuf&uuml;gen", cssid:"event_add"}));
    $("#events").html(rows.join(""));
  }
    
  var data=new Array();   
  
  
  $.each(churchcore_sortData(allEvents, "datumvon"), function(k,a) {
    var d=a.datumvon.toDateEn(true);    
    var min=d.getMinutes();
    var bez=(a.bezeichnung!=null?" ("+a.bezeichnung+")":"");
    var checkin="";
    if (a.person!=null)
      checkin=churchcore_countObjectElements(a.person)+" Personen eingecheckt";
    data.push({id:a.id, bezeichnung:dayNames[d.getDay()]+", "+d.getDate()+"."+(d.getMonth()+1)+". "+d.getHours()+":"+(min<10?"0"+min:min)+" Uhr "+bez+"<br><small>"+checkin+"</small>"});
    if ((event_id==null) && ((masterData.settings.event_datum==null) || (masterData.settings.event_datum.toDateEn(true).getTime()==d.getTime())))
      event_id=a.id;
  });
  renderDatumsname();
  
  form_renderSelectable($("#events span.veranstaltung"),{
    height:290,
    data:data,
    selected:event_id,
    select:function(t) {
      event_id=t;
      churchInterface.jsendWrite({func:"saveSetting", sub:"event_datum", val:allEvents[t].datumvon}, null, null, false);
      masterData.settings.event_datum=allEvents[t].datumvon;
      window.setTimeout(function(){
        gruppe_id=null;
        renderGroups();
        renderPersons();
        renderDatumsname();
      },20);
    }
  });  
  
  if (full) {
    $("#event_config").click(function() {
      var form=new CC_Form("Auswahl einer Kategorie aus "+masterData.churchservice_name);
      form.addHtml("<p><small>Die Anzeige kann automatisch durch Events aus "+masterData.churchservice_name+" gef&uuml;llt werden, bitte hierzu eine Event-Kategorie w&auml;hlen.</small>");
      form.addSelect({freeoption:true, data:masterData.cs_category, selected:masterData.settings.category_id, cssid:"category_id"});
      var elem=form_showDialog("Veranstaltungsanzeige", form.render(), 400, 400, {
        "Speichern": function() {
          var obj=form.getAllValsAsObject();
          if (obj.category_id=="") obj.category_id=null;
          masterData.settings.category_id=obj.category_id;
          churchInterface.jsendWrite({func:"saveSetting", sub:"category_id", val:obj.category_id}, null, null, false);
          loadData();
          $(this).dialog("close");
        },
        "Abbruch": function() {
          $(this).dialog("close");
        }
      });  
      return false;
    });
    $("#event_add").click(function() {
      if (churchcore_isObjectEmpty(masterData.groups))
        alert("Bitte erst einen Distrikt mit Gruppen nehmen!");
      else {
        var form=new CC_Form("Event hinzuf&uuml;gen");
        var dt=new Date();
        form.addInput({label:"Datum",value:dt.toStringDe(false), required:true});
        form.addInput({label:"Uhrzeit",value:"10:00", required:true});
        var elem=form_showDialog("Veranstaltungsanzeige", form.render(), 400, 400, {
          "Speichern": function() {
            var obj=form.getAllValsAsObject();
            var d=obj.Datum.toDateDe(false);
            if (d.getFullYear()<2000) d.setFullYear(d.getFullYear()+100);
            if (d.dayDiff(new Date())>6) {
              alert("Event darf nicht vor 6 Tagen liegen!");
              return false;
            }
            obj.Uhrzeit=obj.Uhrzeit.replace(/\./g, ':');
            
            var s=d.toStringDe(false)+" "+obj.Uhrzeit;
            obj.datumvon=s.toDateDe(true);
            obj.datumbis=obj.datumvon;
            if (gruppe_id!=null)
              obj.gruppe_id=gruppe_id;
            else obj.gruppe_id=churchcore_getFirstElement(masterData.groups).id;
            obj.func="addEvent";
            churchInterface.jsendWrite(obj, function(ok, data) {
              loadData();
              elem.dialog("close");
            }, null, false);
          },
          "Abbruch": function() {
            $(this).dialog("close");
          }
        });
      }
      return false;
    });
  }
}

function renderGroups(full) {
  if (full==null) full=true;
  
  if (full) {
    var rows= new Array();
    
    rows.push('<h3>Gruppen &nbsp;'+(view_easy?"":form_renderImage({cssid:"gruppen_config", width:24, src:"options.png"}))+'</h3>');    
    rows.push('<span class="gruppen"></span>');  
    rows.push('<p></p>');
    if (!view_easy)
      rows.push(form_renderButton({label:"Checkin abschliessen", cssid:"closecheckin"}));
    else rows.push("<p>&nbsp;");
    
    $("#groups").html(rows.join(""));
  }
  
  var data=new Array();
  $.each(churchcore_sortMasterData(masterData.groups), function(k,g) {
    if ((view_checkin) || 
           ((!view_checkin) && (allEvents[event_id]!=null) 
           && ((allEvents[event_id].gruppen==null) || (allEvents[event_id].gruppen[g.id]==null) || (allEvents[event_id].gruppen[g.id].kommentar==null)))) {

      var checkin=0;
      var counter=0;
      $.each(allPersons, function(i,p) {
        if (churchcore_inArray(g.id, p.gruppe)) {
//        if (g.id==p.gruppe_id) {
          counter=counter+1;
          if ((allEvents[event_id]!=null) && (allEvents[event_id].person!=null) && (allEvents[event_id].person[p.id]==g.id))
            checkin=checkin+1;
          
        }
      });
      var bezeichnung=g.bezeichnung;
      var htmlclass="";
      if ((allEvents[event_id]!=null) && (allEvents[event_id].gruppen!=null) && (allEvents[event_id].gruppen[g.id]!=null))
        if (allEvents[event_id].gruppen[g.id].kommentar!=null) {
          htmlclass="ui-state-checkin";
          if (allEvents[event_id].gruppen[g.id].kommentar=="")
            bezeichnung=bezeichnung+'<span class="pull-right">'+form_renderImage({src:"check-64.png", width:24, label:"Checkin abgeschlossen"})+'</span>';
          else
            bezeichnung=bezeichnung+'<span class="pull-right">'+form_renderImage({src:"comment.png", width:24, label:allEvents[event_id].gruppen[g.id].kommentar})+'</span>';
        }
  
      bezeichnung=bezeichnung+'<br><small>'+checkin+" von "+counter+" Personen eingecheckt.";
  
      if ((allEvents[event_id]!=null) && (allEvents[event_id].gruppen!=null) && (allEvents[event_id].gruppen[g.id]!=null))
        if (allEvents[event_id].gruppen[g.id].anzahl_gaeste>0)
          bezeichnung=bezeichnung+" "+allEvents[event_id].gruppen[g.id].anzahl_gaeste+" G&auml;ste";
      
       bezeichnung=bezeichnung+"</small>";  
        
      data.push({id:g.id, bezeichnung:bezeichnung, htmlclass:htmlclass});
    }
  });
  
  form_renderSelectable($("#groups span.gruppen"),{
    height:(view_easy?330:290),
    data:data,
    deselectable:true,
    selected:gruppe_id,
    select:function(t) {
      gruppe_id=t;
      window.setTimeout(function(){
        renderPersons();
      },20);
    },
    deselect:function() {
        gruppe_id=null;
        window.setTimeout(function(){
          renderPersons();
        },20);
      }
  });  
  
  if (full) {
    $("#gruppen_config").click(function() {
      renderChoseDistrikt();      
      return false;
    });
    $("#closecheckin").click(function() {
      if ((gruppe_id==null) || (event_id==null))
        alert("Bitte vorher eine Veranstaltung und eine Gruppe markieren!");
      else {
        var form=new CC_Form();
        form.addHtml("<p><small>Es besteht nun noch die M&ouml;glichkeit eine Anzahl der G&auml;ste anzugeben, einen Kommentar sowie eine Gruppenliste zu drucken (wenn ein Drucker ausgew&auml;hlt wurde).</small>");
        var defaultGaeste=0;
        var defaultKommentar="";
        if ((allEvents[event_id].gruppen!=null) && (allEvents[event_id].gruppen[gruppe_id]!=null)) { 
          if (allEvents[event_id].gruppen[gruppe_id].anzahl_gaeste>0) 
            defaultGaeste=allEvents[event_id].gruppen[gruppe_id].anzahl_gaeste;
          if (allEvents[event_id].gruppen[gruppe_id].kommentar!=null)
            defaultKommentar=allEvents[event_id].gruppen[gruppe_id].kommentar;
        }
           
        form.addInput({label:"Anzahl G&auml;ste", value:defaultGaeste, cssid:"anzahl_gaeste"});
        form.addTextarea({label:"Kommentar", rows:4, data:defaultKommentar, placeholder:"Kommentar", cssid:"kommentar"});
        if (masterData.settings.printer_id!=null)
          form.addCheckbox({label:"Gruppenliste drucken", cssid:"printgrouplist", checked:masterData.settings.printgrouplist});
        
        var elem=form_showDialog("Checkin <i>"+masterData.groups[gruppe_id].bezeichnung+"</i> abschliessen", form.render(null, "vertical"), 400, 450, {
          "Checkin abschliessen": function() {
            var obj=form.getAllValsAsObject();
            obj.func="finishCheckin";
            obj.gruppe_id=gruppe_id;
            obj.datumvon=allEvents[event_id].datumvon;
            obj.printer_id=masterData.settings.printer_id;
            obj.gruppentreffen_id=allEvents[event_id].gruppen[gruppe_id].gruppentreffen_id;
            churchInterface.jsendWrite(obj, function(ok, data) {
              if (!ok) alert("data");
              else {
                loadData();
              }
            }, null, false);
            $(this).dialog("close");
          },
          "Abbruch": function() {
            $(this).dialog("close");
          }
        });
        elem.find("#printgrouplist").change(function() {
          masterData.settings.printgrouplist=($(this).attr("checked")=="checked"?1:0);
          churchInterface.jsendWrite({func:"saveSetting", sub:"printgrouplist", val:masterData.settings.printgrouplist}, null, null, false);          
        });
        
        
        //churchInterface.jsendWrite({func:"printGrouplist", printer_id:masterData.settings.printer_id, gruppe_id:gruppe_id, event_id:event_id}, null, null, false);
      }
      return false;
    });
  }
  if (masterData.settings.distrikt_id==null) 
    renderChoseDistrikt();
}

function renderChoseDistrikt() {
  var form=new CC_Form("Bitte einen Distrikt ausw&auml;hlen");
  form.addHtml("<p><small>Um die passenden Gruppen anzeigen zu k&ouml;nnen, muss hier ein Distrikt ausgew&auml;hlt werden.</small>");
  form.addSelect({data:masterData.cdb_distrikt, selected:masterData.settings.distrikt_id, cssid:"distrikt_id"});
  var elem=form_showDialog("Anzeige der Gruppen", form.render(), 400, 400, {
    "Speichern": function() {
      var obj=form.getAllValsAsObject();
      masterData.settings.distrikt_id=obj.distrikt_id;
      churchInterface.jsendWrite({func:"saveSetting", sub:"distrikt_id", val:obj.distrikt_id}, null, null, false);
      loadData();
      $(this).dialog("close");
    },
    "Abbruch": function() {
      $(this).dialog("close");
    }
  });      
}



function _renderAddPerson(elem, form) {
  form.addHidden({cssid:"func", value:"addGroupRelation"});
  form.addHtml("<br>");
  form.addInput({label:"Person", cssid:"person_id"});
  //form.addHtml("<br>");
  form.addSelect({label:"Gruppe", cssid:"gruppe_id", data:churchcore_sortMasterData(masterData.groups), selected:gruppe_id});
  elem.find("#form_content").html(form.render(null, "horizontal"));
  elem.find("#person_id").focus();
  form_autocompletePersonSelect("#person_id");

}
function _renderNewPerson(elem, form) {
  form.addHidden({cssid:"func", value:"addPerson"});
  form.addInput({label:"Vorname", cssid:"vorname", required:true});
  form.addInput({label:"Nachname", cssid:"name", required:true});
  form.addInput({label:"Geburtsdatum", type:"small", cssid:"geburtsdatum"});
  form.addSelect({label:"Gruppe", cssid:"gruppe_id", data:churchcore_sortMasterData(masterData.groups), selected:gruppe_id});
  form.addSelect({label:"Bereich", cssid:"bereich_id", htmlclass:"setting", data:churchcore_sortMasterData(masterData.cdb_bereich), selected:masterData.settings.bereich_id});
  form.addSelect({label:"Status", cssid:"status_id", htmlclass:"setting", data:churchcore_sortMasterData(masterData.cdb_status), selected:masterData.settings.status_id});
  form.addSelect({label:"Station", cssid:"station_id", htmlclass:"setting", data:churchcore_sortMasterData(masterData.cdb_station), selected:masterData.settings.station_id});
  elem.find("#form_content").html(form.render(null, "horizontal"));
  elem.find("#vorame").focus();
  elem.find("select.setting").change(function(k) {
    masterData.settings[$(this).attr("id")]=$(this).val();
    churchInterface.jsendWrite({func:"saveSetting", sub:$(this).attr("id"), val:$(this).val()}, null, null, false);    
  });    
}

function renderNewPerson() {
  var rows = new Array();  
  
  var navi = new CC_Navi();
  navi.addEntry(true,"view-add","Aus "+masterData.churchdb_name+" suchen");
  navi.addEntry(false,"view-new","Neue Person erstellen");
  rows.push(navi.render());
  rows.push('<div id="form_content"></div>');
  
  var form = new CC_Form();
  
  var elem=form_showDialog("Gruppenanzeige", rows.join(""), 500, 480, {
    "Hinzufuegen": function() {
      var obj=form.getAllValsAsObject();
      if (obj.func=="addPerson") {
        if ((obj.vorname=="") || (obj.name=="")) alert("Bitte Vorname und Name angeben!");
        else {
          if (obj.geburtsdatum!="")
            obj.geburtsdatum=obj.geburtsdatum.toDateDe().toStringEn();
          else delete obj.geburtsdatum;
        }
      }
      churchInterface.jsendWrite(obj, function(ok,data) {
        if (ok) {
          person_id=data;
          loadData();            
          elem.dialog("close");
        }
        else {
          alert(data);
        }
      }, null, false);
    },
    "Abbruch": function() {
      $(this).dialog("close");
    }
  });    
  
  elem.dialog({height:290});
  _renderAddPerson(elem, form);    
  
  elem.find("ul.nav a").click(function() {
    form = new CC_Form();
    if ($(this).attr("id")=="view-add") {
      _renderAddPerson(elem, form);
      elem.dialog({height:290});
    }
    else {
      _renderNewPerson(elem, form);
      elem.dialog({height:480});
    }
    navi.activate($(this).attr("id"));
    return false;
  });

}

function renderPersons(full) {
  if (full==null) full=true;
  
  if (full) {
    var rows= new Array();
    rows.push(form_renderInput({htmlclass:"pull-right search-query input-small", cssid:"searchPerson", value:search_person}));
    rows.push('<h3>Personen &nbsp;'+(view_easy?"":form_renderImage({cssid:"personen_config", width:24, src:"options.png"}))+'</h3>');    
  rows.push('<span class="personen"></span>');  
    rows.push('<p></p>');
    rows.push(form_renderButton({label:"Checkin", htmlclass:"btn-primary", cssid:"btn_checkin",disabled:person_id==null})+"&nbsp;");
    rows.push(form_renderButton({label:"Letzten r&uuml;ckg&auml;ngig", htmlclass:"pull-right", cssid:"btn_undocheckin", disabled:last_person_id==null}));
    
    $("#persons").html(rows.join(""));
  }
  
  var data=new Array();
  if (allEvents[event_id]!=null) {
    var s=null;
    if (masterData.settings.sortnames==1)
      s=churchcore_sortData(allPersons,"vorname",null,null,"name");
    else
      s=churchcore_sortData(allPersons,"name",null,null,"vorname");
    $.each(s, function(k,a) {
      // Prüfe, ob das Event noch keine eingecheckte Person hat oder die Person noch nicht eingecheckt wurde
      if ((view_checkin) || (allEvents[event_id].person==null) || (allEvents[event_id].person[a.id]==null)) {
        if ((gruppe_id==null) || (churchcore_inArray(gruppe_id, a.gruppe))) {
          if ((search_person=="") || (a.vorname.toUpperCase().indexOf(search_person.toUpperCase())==0)
              || (a.name.toUpperCase().indexOf(search_person.toUpperCase())==0)) {
            a.bezeichnung="";
            if ((allEvents[event_id].person!=null) && (allEvents[event_id].person[a.id]!=null)) {
              a.bezeichnung='<span class="pull-right">'+form_renderImage({src:"check-64.png", width:24, label:"Checkin abgeschlossen"})+'</span>';
              a.htmlclass="ui-state-checkin";
            }
            if (a.imageurl==null) a.imageurl="nobody.gif";
            a.bezeichnung=a.bezeichnung+'<img height="40px" align="left" style="max-height:44px;margin-right:10px;" src="'+masterData.files_url+"/fotos/"+a.imageurl+'"/>'+a.vorname+" "+a.name+'<br/><small>';
            if (gruppe_id==null) {
              $.each(a.gruppe, function(i,g) {
                if (masterData.groups[g]!=null)
                  a.bezeichnung=a.bezeichnung+masterData.groups[g].bezeichnung+" &nbsp; ";
              });
            }
            a.bezeichnung=a.bezeichnung+'</small>';
            data.push(a);
          }
        }
      }
    });
  }

  if (event_id!=null)
    data.push({id:-1, bezeichnung:'<img height="40px" align="left" style="max-height:44px;margin-right:10px;" src="'+masterData.files_url+'/fotos/nobody.gif"/><i>Person der Gruppe hinzuf&uuml;gen</i><br/><small></small>'});
  
  if (data.length==2) {
    person_id=data[0].id;
    $("#btn_checkin").removeAttr("disabled");
  }
  var scrollTop=0;
  if (person_id==null) scrollTop=$("#persons ul").scrollTop();
  form_renderSelectable($("#persons span.personen"),{
    height:(view_easy?330:290),
    min_element_height:45,
    data:data,
    selected:person_id,
    select:function(id) {
      if (id==-1) renderNewPerson();
      person_id=id;
      if (masterData.settings.autocheckin==1)
        doCheckin();
      else
        $("#btn_checkin").removeAttr("disabled");
    }
  });  
  if (scrollTop!=0) $("#persons ul").scrollTop(scrollTop);

  if (full) {
    $("#btn_checkin").click(function() {
      doCheckin();      
    });
    $("#btn_undocheckin").click(function() {
      undoCheckin();
    });
    $("#searchPerson").keyup(function() {
      search_person=$(this).val();
      renderPersons(false);
    });
    $("#personen_config").click(function() {
      renderPersonenConfig();      
      return false;
    });
  }
  //$("#searchPerson").focus();
}

function renderPersonenConfig() {
  var form=new CC_Form("Bitte Personenliste konfigurieren");
  form.addCheckbox({label:"<p>Nach Vorname sortieren<br><small>Legt die Sortierung der Personen fest</small>", checked:masterData.settings.sortnames==1, cssid:"sortnames"});
  form.addCheckbox({label:"<p>Auto-Checkin<br><small>Beim Klick auf eine Person wird diese sofort eingecheckt!</small>", checked:masterData.settings.autocheckin==1, cssid:"autocheckin"});  
  
  var elem=form_showDialog("Anzeige der Personen", form.render(null, "vertical"), 400, 400, {
    "Speichern": function() {
      var obj=form.getAllValsAsObject();
      masterData.settings.sortnames=obj.sortnames;
      masterData.settings.autocheckin=obj.autocheckin;
      churchInterface.jsendWrite({func:"saveSetting", sub:"sortnames", val:obj.sortnames}, null, null, false);
      churchInterface.jsendWrite({func:"saveSetting", sub:"autocheckin", val:obj.autocheckin}, null, null, false);
      loadData();
      $(this).dialog("close");
    },
    "Abbruch": function() {
      $(this).dialog("close");
    }
  });      
}

function doCheckin() {
  if (allEvents[event_id]==null)
    alert("Kein Event geladen!?");
  else if ((allEvents[event_id].person!=null) && (allEvents[event_id].person[person_id]!=null)) 
    alert("Person ist schon in Gruppe "+masterData.groups[allEvents[event_id].person[person_id]].bezeichnung+" eingecheckt!");
  else {      
    if (allEvents[event_id].person==null)
      allEvents[event_id].person=new Object();
    var p_gp_id=gruppe_id;
    if (p_gp_id==null) {
      if (churchcore_countObjectElements(allPersons[person_id].gruppe)>1)
        alert("Person ist in mehreren Gruppen. Bitte vorher Gruppe anklicken.");
      else p_gp_id=allPersons[person_id].gruppe[0];
    }
    if (p_gp_id!=null) {
      var o = new Object();
      o.func="checkin";        
      o.event_id=event_id;
      o.cs_event=allEvents[event_id].cs_event;
      o.datumvon=allEvents[event_id].datumvon;
      o.person_id=person_id;
      o.gruppe_id=p_gp_id;
      o.printer_id=$('#printer_id').val();

      allEvents[event_id].person[person_id]=p_gp_id;
      last_person_id=person_id;
      if ($("#searchPerson").val()!="") {
        search_person="";
        renderPersons();
      }
      else {
        if (!view_checkin)
          $("#persons li[data-id="+person_id+"]").remove();
        else {
          $("#persons a[data-id="+person_id+"]").removeClass("ui-state-hover");
          $("#persons a[data-id="+person_id+"]").addClass("ui-state-checkin");
          $("#persons a[data-id="+person_id+"]").prepend(
          '<span class="pull-right">'+form_renderImage({src:"check-64.png", width:24, label:"Checkin abgeschlossen"})+'</span>');
        }
        $("#btn_undocheckin").removeAttr("disabled");
        $("#btn_checkin").attr("disabled","true");
      }
      person_id=null;
      search_person="";
      //renderPersons();
      churchInterface.jsendWrite(o, function(ok, data) {
        if (!ok) {
          alert("Fehler: "+data+" aufgetreten. Nehme letzten nicht mehr.");
          undoCheckin();
        }
      }, null, false);
      window.setTimeout(function() { renderGroups(); renderEvent(false); },20);
    }
  }  
}

function undoCheckin() {
  if ((last_person_id!=null) && (allEvents[event_id].person!=null) && (allEvents[event_id].person[last_person_id]!=null)) {

    var o = new Object();
    o.func="undocheckin";        
    o.event_id=event_id;
    o.cs_event=allEvents[event_id].cs_event;
    o.datumvon=allEvents[event_id].datumvon;
    o.person_id=last_person_id;
    o.gruppe_id=allEvents[event_id].person[last_person_id];

    delete allEvents[event_id].person[last_person_id];
    person_id=last_person_id;
    last_person_id=null;
    renderPersons();

    churchInterface.jsendWrite(o, function(ok, data) {
      if (!ok) {
        alert("Fehler: "+data+" aufgetreten. Nehme letzten nicht mehr.");
        undoCheckin();
      }
    }, null, false);
    
    window.setTimeout(function() { renderGroups(false); renderEvent(false); },20);
  }
  else alert("Letztes Checkin ist leider nicht mehr vorhanden!");
  
}


function renderView(refreshAll) {
  if (refreshAll) {
    var rows=new Array();
    if (!view_easy) {
      rows.push('<div class="well">');
        rows.push('<span class="pull-right" id="printer"></span>');
        rows.push('<h1>'+masterData.churchcheckin_name+'<small><span id="datumsname"></span></small></h1>');
        rows.push('<p>Bitte Veranstaltung, Gruppe und Person ausw&auml;hlen und dann auf Checkin klicken. <a href="http://intern.churchtools.de/?q=churchwiki#WikiView/filterWikicategory_id:0/doc:ChurchCheckin/" target="_clean"><i class="icon-question-sign"></i></a></p>');
      rows.push('</div>');
      rows.push('<div class="row-fluid">');
        rows.push('<div id="events" class="span4 well"></div>');  
        rows.push('<div id="groups" class="span4 well"></div>');  
        rows.push('<div id="persons" class="span4 well"></div>');
      rows.push('</div>');
    }
    else {
      rows.push('<div class="well">');
        rows.push('<span class="pull-right" id="printer"></span>');
        rows.push('<h1>'+masterData.churchcheckin_name+'<small><span id="datumsname"></span></small></h1>');
      rows.push('</div>');
      rows.push('<div class="row-fluid">');
        //rows.push('<div id="events" class="span4 well"></div>');  
        rows.push('<div id="groups" class="span5 well"></div>');  
        rows.push('<div id="persons" class="span7 well"></div>');
      rows.push('</div>');    
    }
    $("#cdb_content").html(rows.join(""));
  }
  renderEvent(refreshAll);
  renderGroups(refreshAll);
  renderPersons(refreshAll);
  if (refreshAll) renderPrinter();
}

function loadData(refreshAll) {
  if (refreshAll==null) refreshAll=true;
  churchInterface.jsendRead({func:"getData", category_id: masterData.settings.category_id,
    distrikt_id:masterData.settings.distrikt_id}, function(json, data) {
    event_id=null;
    masterData.groups=data.groups;
    allPersons=data.allPersons;
    allEvents=data.events;
    renderView(refreshAll);
//    window.setTimeout(function() {loadData(false);}, 10000);
  }, null, false);      
}

function renderPrinter(){
  var rows = new Array();
  if (!view_easy) {
    if (masterData.cc_printer!=null) {
      var d=new Array();
      d.push({id:"", bezeichnung:"<i>Keinen Drucker ausgew&auml;hlt</i>"});
      $.each(churchcore_sortMasterData(masterData.cc_printer), function(k,a) {
        d.push({id:a.id, bezeichnung:a.bezeichnung+" ("+a.ort+")"});
      });
      rows.push(form_renderSelect({data:d, cssid:"printer_id", selected:masterData.settings.printer_id, controlgroup:false})+"</i>");
    }
    else rows.push('<p><small><i>Keinen Drucker gefunden</i> <a target="_clean" href="http://intern.churchtools.de/?q=churchwiki#WikiView/filterWikicategory_id:0/doc:Keinen%20Drucker%20gefunden/"><i class="icon-question-sign icon-white"></i></a></small>');
  }
  rows.push(form_renderCheckbox({cssid:"togglecheckin", label:"Eingecheckte auch anzeigen", controlgroup:false}));
  rows.push(form_renderCheckbox({cssid:"easyview", checked:view_easy, label:"Vereinfachte Ansicht", controlgroup:false}));
  $("#printer").html(rows.join(""));
  $("#printer_id").change(function() {
    masterData.settings.printer_id=$(this).val();
    churchInterface.jsendWrite({func:"saveSetting", sub:"printer_id", val:$(this).val()}, null, null, false);    
  });
  //$("#togglecheckin").iOSToggle({on:'An',off:'Aus'});
  $("#togglecheckin").change(function() {
    view_checkin=$(this).attr("checked")=="checked";
    renderGroups();
    renderPersons();
  });
  $("#easyview").change(function() {
    view_easy=$(this).attr("checked")=="checked";
    renderView(true);    
  });
  
}

$(document).ready(function() {
  churchInterface.setModulename("churchcheckin");
  
  churchInterface.setStatus("Lade Kennzeichen...");
  churchInterface.jsendRead({func:"getMasterData"}, function(ok, data) {
    masterData=data;
    if (churchcore_countObjectElements(masterData.settings)==0)
      masterData.settings=new Object();
    loadData();
  },null,false);
  
});

