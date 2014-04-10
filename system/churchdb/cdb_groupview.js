(function($) { 
	 
 
// Constructor
function GroupView() {
  CDBStandardTableView.call(this);
  this.name="GroupView";
  this.sortVariable="bezeichnung";
  this.sortdetail="bezeichnung";
  this.range_startday=null;
  this.range_endday=null;
}

Temp.prototype = CDBStandardTableView.prototype;
GroupView.prototype = new Temp();
groupView = new GroupView();

GroupView.prototype.getData = function(sorted) {
  if (sorted)
    return churchcore_sortData(masterData.groups,this.sortVariable);
  else
    return masterData.groups;
};

GroupView.prototype.renderMenu = function() {
  this_object=this;
  
  
  menu = new CC_Menu("Men&uuml;");
  menu.addEntry("Zur&uuml;ck zur Personenliste", "apersonview", "arrow-left");

  if (masterData.auth.admingroups || this_object.isPersonSuperLeader(masterData.user_pid))
    menu.addEntry("Neue Gruppe anlegen", "anewentry", "cog");  

  menu.addEntry("Weitere Filter", "aaddfilter", "filter");
  menu.addEntry("Exporter", "aexport", "share");
  $("#cdb_group").html("");
  $("#cdb_todos").html("");
  this.renderDistrict();
  this.renderGrouptype();

  if (!menu.renderDiv("cdb_menu"))
    $("#cdb_menu").hide();
  else {    
  
    rows = new Array();
    rows.push("<div id=\"divnewentry\" style=\"display:none\" class=\"new-entry\"></div>");
    rows.push("<div id=\"divaddfilter\" cstyle=\"display:none\" lass=\"new-entry\" style=\"width:100%;\"></div>");
    $("#cdb_precontent").html(rows.join(""));
  
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        this_object.renderAddEntry();
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
      else if ($(this).attr("id")=="apersonview") {
        menuDepth="amain";
        $("#cdb_group").html("");
        if (masterData.settings.churchdbInitView!='PersonView') {
          masterData.settings.churchdbInitView='PersonView';
          churchInterface.jsendWrite({func:"saveSetting", sub:"churchdbInitView", val:"PersonView"});
        }
        churchInterface.setCurrentView(personView);
      }
      else if ($(this).attr("id")=="aexport") {
        this_object.exporter();
      }
      else if ($(this).attr("id")=="amain") {
        menuDepth="amain";
        $("#cdb_group").html("");
        this_object.renderMenu();
      }
      return false;
    });
  }

  
};

GroupView.prototype.exporter = function() {
  var t=this;
  var rows=new Array();
  var txt=t.getListHeader().substr(4,9999);
  rows.push(txt.html2csv()+"\r\n");
  
  $.each(t.getData(true), function(i,a) {
    if (t.checkFilter(a)) {
      var txt=t.renderListEntry(a).substr(4,9999);
      rows.push(a.id+";"+txt.html2csv()+"\r\n");
    }
  });
  
  var uri = 'data:text/csv;charset=utf-8,' + escape(rows.join(""));

  var downloadLink = document.createElement("a");
  downloadLink.href = uri;
  downloadLink.download = "export_gruppenliste.csv";

  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
};


GroupView.prototype.messageReceiver = function(message, args) {
  var t = this;
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      t.renderMenu();
      t.renderDistrict();
      t.renderGrouptype();
    }
    else if (message=="filterChanged") {
      this.msg_filterChanged(args[0], args[1]);
    }
  }
};

GroupView.prototype.renderDistrict = function() {
  var t = this;
  var rows = new Array();
  var district_id=t.filter["filterDistrikt"];
  if (district_id==null) {
    if ($("#cdb_group").html()!="") {
      t.range_startday=null;
      $("#cdb_group").html(rows.join(""));
      t.renderGrouptype();
    }
  }  
  else if (masterData.districts[district_id]!=null) {
    rows.push('<div class="well">');
    rows.push('<legend>'+f("distrikt_id")+': '+masterData.districts[district_id].bezeichnung+'</legend>');
    
    rows.push('<div class="container-fluid">');
    rows.push('<div class="span4">');
    
    var ps=new Array();
    $.each(allPersons, function(k,a) {
      if ((a.districts!=null) && (a.districts[district_id]))
        ps.push(a);
    });
    
    if (!churchInterface.isAllDataLoaded()) {
      rows.push('<p>'+form_renderImage({src:"loading.gif"}));
    }
    else {
      if (ps.length==0) {     
        if (masterData.auth.admingroups) 
          rows.push('<p><a href="#" class="add-person btn btn-small">Eine Person zuordnen</a>');
      }
      else {
        rows.push('<table class="table table-condensed"><tr><th>Zugeordnete Personen<th>');
        $.each(churchcore_sortData(ps, "name"), function(k,a) {
          rows.push('<tr><td><a href="#" class="visit-person tooltip-person" data-tooltip-id="'+a.id+'" >'+a.vorname+" "+a.name+'</a>');
          if (masterData.auth.admingroups) {
            rows.push('<td><a href="#" class="del-person" data-id="'+a.id+'">'+this_object.renderImage("trashbox")+'</a>'); 
          }
        });
        rows.push('</table>');
        if (masterData.auth.admingroups) 
          rows.push('<p><a href="#" class="add-person btn btn-small">Weitere Person zuordnen</a>');
      }
    }
    
    rows.push('</div>');
    rows.push('<div class="span8">');
    if (masterData.auth.viewhistory) {
      rows.push('<p>&Auml;nderungen anzeigen innerhalb von: <div id="slider"></div>');
      rows.push('<span class="pull-right" id="slider_value"></div>');
    }
    rows.push('</div>');
    rows.push('</div>');
    
    $("#cdb_group").html(rows.join(""));
    personView.addPersonsTooltip($("#cdb_group"));

    
    if (masterData.auth.viewhistory) {
      var renderTimer=null;
      if (t.range_startday==null) t.range_startday=-31;
      if (t.range_endday==null) t.range_endday=0;
      $( "#slider" ).slider({
        range: true,
        min: -365,
        max: 0,
        values: [ t.range_startday, t.range_endday],
        slide: function( event, ui ) {
          t.range_startday=ui.values[ 0 ];
          t.range_endday=ui.values[ 1 ];
          
          _renderDistriktDate(t.range_startday, t.range_endday);
        }
      });
      function _renderDistriktDate(_start, _end) {
        var start= new Date();
        start.addDays(_start);
        var end= new Date();
        end.addDays(_end);
        $("#slider_value").html(start.toStringDe()+" - "+end.toStringDe());
        if (renderTimer!=null) 
          window.clearTimeout(renderTimer);
        renderTimer=window.setTimeout(function() {
          t.renderList();
        },100);
      }
      _renderDistriktDate(t.range_startday, t.range_endday);
    }
    
    $("#cdb_group a").click(function() {
      if ($(this).hasClass("del-person")) {
        var id=$(this).attr("data-id");
        churchInterface.jsendWrite({func:"delPersonDistrictRelation", id:id, distrikt_id:district_id}, function(ok, data) {
          if (!ok)
            alert("Fehler beim Entfernen der Person-Gruppenbeziehung: "+data);
          else {
            delete allPersons[id].districts[district_id];
            t.renderDistrict();
          }
        });
        
      }
      else if ($(this).hasClass("add-person")) {
        personView.renderPersonSelect("Nach einer Person suchen", true, function(id) {
          churchInterface.jsendWrite({func:"addPersonDistrictRelation", id:id, distrikt_id:district_id}, function(ok, data) {
            if (!ok)
              alert("Fehler beim Erstellen der Person-Gruppenbeziehung: "+data);
            else {
              if (allPersons[id].districts==null)
                allPersons[id].districts=new Object();
              var o = new Object();
              o.distrikt_id=district_id;
              o.person_id=id;
              allPersons[id].districts[district_id]=o;
              t.renderDistrict();
            }
          });
        });            
        return false; 
      }
      else if ($(this).hasClass("visit-person")) {
        clearTooltip(true);
        $("#cdb_group").html("");
        churchInterface.setCurrentView(personView);
        personView.clearFilter();
        personView.setFilter("searchEntry","#"+$(this).attr("data-tooltip-id"));
        personView.renderView();
      }
      return false;    
    });
  }
};



GroupView.prototype.renderGrouptype = function() {
  var t = this;
  var rows = new Array();
  var gruppentyp_id=t.filter["filterGruppentyp"];
  if (gruppentyp_id==null) {
    if ($("#cdb_group").html()!="") {
      $("#cdb_group").html(rows.join(""));
      t.renderDistrict();    
    }
  }
  else {
    rows.push('<div class="well">');
    rows.push('<legend>'+f('gruppentyp_id')+': '+masterData.groupTypes[gruppentyp_id].bezeichnung+'</legend>');
    
    rows.push('<div class="container-fluid">');
    rows.push('<div class="span5">');
    
    var ps=new Array();
    $.each(allPersons, function(k,a) {
      if ((a.gruppentypen!=null) && (a.gruppentypen[gruppentyp_id]))
        ps.push(a);
    });
    
    if (ps.length==0) {     
      if (masterData.auth.admingroups) 
        rows.push('<p><a href="#" class="add-person btn btn-small">Eine Person zuordnen</a>');
    }
    else {
      rows.push('<table class="table table-condensed"><tr><th>Zugeordnete Personen<th>');
      $.each(churchcore_sortData(ps, "name"), function(k,a) {
        rows.push('<tr><td><a href="#" class="visit-person tooltip-person" data-tooltip-id="'+a.id+'" >'+a.vorname+" "+a.name+'</a>');
        if (masterData.auth.admingroups) {
          rows.push('<td><a href="#" class="del-person"data-id="'+a.id+'">'+this_object.renderImage("trashbox")+'</a>'); 
        }
      });
      rows.push('</table>');
      if (masterData.auth.admingroups) 
        rows.push('<p><a href="#" class="add-person btn btn-small">Weitere Person zuordnen</a>');
    }
    
    rows.push('</div>');
    rows.push('<div class="span5">');
    rows.push('</div>');
    rows.push('</div>');
    
    $("#cdb_group").html(rows.join(""));
    personView.addPersonsTooltip($("#cdb_group"));

    $("#cdb_group a").click(function() {
      if ($(this).hasClass("del-person")) {
        var id=$(this).attr("data-id");
        churchInterface.jsendWrite({func:"delPersonGruppentypRelation", id:id, gruppentyp_id:gruppentyp_id}, function(ok, data) {
          if (!ok)
            alert("Fehler beim Entfernen der Person-Gruppentypbeziehung: "+data);
          else {
            delete allPersons[id].gruppentypen[gruppentyp_id];
            t.renderGrouptype();
          }
        });
        
      }
      else if ($(this).hasClass("add-person")) {
        personView.renderPersonSelect("Nach einer Person suchen", true, function(id) {
          churchInterface.jsendWrite({func:"addPersonGruppentypRelation", id:id, gruppentyp_id:gruppentyp_id}, function(ok, data) {
            if (!ok)
              alert("Fehler beim Erstellen der Person-Gruppentypbeziehung: "+data);
            else {
              if (allPersons[id].gruppentypen==null)
                allPersons[id].gruppentypen=new Object();
              var o = new Object();
              o.gruppentyp_id=gruppentyp_id;
              o.person_id=id;
              allPersons[id].gruppentypen[gruppentyp_id]=o;
              t.renderGrouptype();
            }
          });
        });            
        return false; 
      }
      else if ($(this).hasClass("visit-person")) {
        clearTooltip(true);
        $("#cdb_group").html("");
        churchInterface.setCurrentView(personView);
        personView.clearFilter();
        personView.setFilter("searchEntry","#"+$(this).attr("data-tooltip-id"));
        personView.renderView();
      }
      return false;    
    });
  }
};


GroupView.prototype.msg_filterChanged = function (id, oldVal) {
  var t=this;
  if (id=="filterDistrikt") {
    t.renderDistrict();
    if (masterData.settings.filterDistrikt!=t.filter['filterDistrikt']) {
      masterData.settings.filterDistrikt=t.filter['filterDistrikt'];
      churchInterface.jsendWrite({func:"saveSetting", sub:"filterDistrikt", val:(masterData.settings.filterDistrikt==null?"null":masterData.settings.filterDistrikt)});
    }
  }
  else if (id=="filterGruppentyp") {
    t.renderGrouptype();
  }
};

GroupView.prototype.renderListMenu = function() {
  var t=this;
  
  
  searchEntry=this.getFilter("searchEntry");

  var navi = new CC_Navi();
  navi.addEntry(true,"id1","Gruppenliste");
  navi.addSearch(searchEntry);
  navi.renderDiv("cdb_search");
};



GroupView.prototype.renderAddEntry = function() {
  var _text="";
  _text=_text+'<div class="well"><div class="row-fluid">';
  
  var form = new CC_Form("Name");
  form.surroundWithDiv("span4");
  form.addInput({label: "Name der Gruppe", cssid:"inputName"});    
  _text=_text+form.render();
  
  var form = new CC_Form("Gruppe");
  form.surroundWithDiv("span4");
  form.addSelect({label:f('gruppentyp_id'), cssid:"Inputf_grouptype", data:masterData.groupTypes,
       func:function(d){
          return masterData.auth.admingroups 
          || (allPersons[masterData.user_pid].districts!=null 
                 && masterData.groupTypes[d.id].anzeigen_in_meinegruppen_teilnehmer_yn==1)
          || ( allPersons[masterData.user_pid].gruppentypen!=null 
                 && allPersons[masterData.user_pid].gruppentypen[d.id]!=null);                
       } 
  });
  _text=_text+form.render();

  var form = new CC_Form(f("distrikt_id"));
  form.surroundWithDiv("span4");
  form.addSelect({label:f("distrikt_id"), cssid:"Inputf_district", data:masterData.districts,
    func:function(d) {
      return masterData.auth.admingroups 
      || (allPersons[masterData.user_pid].gruppentypen!=null)
      || (allPersons[masterData.user_pid].districts!=null 
             && allPersons[masterData.user_pid].districts[d.id]!=null);                
   } 
  });
  _text=_text+form.render();

  _text=_text+'</div><div class="row-fluid">';
  var form = new CC_Form();
  form.surroundWithDiv("span12");
  form.addButton({label:"Gruppe anlegen", controlgroup:true, cssid:"btProoveNewAddress"});
  form.addHtml("&nbsp; ");
  form.addCheckbox({cssid:"forceCreate",controlgroup_start:true,label:'auch anlegen, wenn es den Namen schon gibt'});
  form.addCheckbox({cssid:"forceDontHide",controlgroup_end:true,label:'weitere Gruppe anlegen'});
  _text=_text+form.render(false,"vertical");
  _text=_text+"</div>";
    
  $("#divnewentry").html(_text);
  $("#btProoveNewAddress").click(function () {
    var obj = new Object();
    obj["func"]="createGroup";
    obj["name"]=$("#inputName").val();  
    $("#divnewentry select").each(function (i, s){
      obj[$(this).attr("id")]=$(this).val();
    });
    if ($("#forceCreate").attr("checked")=="checked") {
      obj["force"]="checked";
    }
    churchInterface.jsendWrite(obj, function(ok, json) {        
      if (json.result=="exist") {
        $("#searchEntry").val(json.id).keyup();          
        alert("Mindestens eine Gruppe mit dem Namen existiert schon!");
      } 
      else {
        $("#searchEntry").val(json.id).keyup();
        cdb_loadMasterData(function() {
          t.filter=new Object();
          t.filter.searchEntry=json.id;
          if ($("#forceDontHide").attr("checked")=="checked") {
            t.renderList();
            t.renderListMenu();
          }
          else t.renderView();
        });
      }        
    });
  });
  $("#divnewentry").animate({ height: 'toggle'}, "slow");  
};



GroupView.prototype.renderFilter = function() {
  var form = new CC_Form();
  
  form.setLabel("Filterfunktionen");
  var ret=personView.getMyGroupsSelector();
  if (ret!=false) {
    form.addSelect({
//      freeoption:true, 
      label:"Meine Gruppen",
      selected:groupView.filter['filterMeine Gruppen'], 
      cssid:"filterMeine Gruppen",
      data:ret,
      type:"medium",
      sort:false
    });
  }
  form.addSelect({
    freeoption:true, 
    label:f("distrikt_id"),
    selected:personView.filter['filterDistrikt'], 
    cssid:"filterDistrikt",
    data:churchcore_sortData(masterData.districts,"bezeichnung"),
    func:function(district) {
      if (masterData.auth.admingroups) return true;
      if ((allPersons[masterData.user_pid]!=null) && (allPersons[masterData.user_pid].districts!=null)
            && (allPersons[masterData.user_pid].districts[district.id]!=null))
        return true;
      var drin=false;
      $.each(ret, function(k,a) {
        if ((masterData.groups[a.id]!=null) && (masterData.groups[a.id].distrikt_id==district.id))
          drin=true;
      });
      return drin;
    },
    type:"medium"
  });
  form.addSelect({
    freeoption:true, 
    label:f('gruppentyp_id'),
    selected:personView.filter['filterGruppentyp'], 
    cssid:"filterGruppentyp",
    data:churchcore_sortData(masterData.groupTypes,"bezeichnung"),
    type:"medium",
    func:function(gt) {
      if (masterData.auth.viewalldetails) return true;
      return gt.anzeigen_in_meinegruppen_teilnehmer_yn==1;
    }
  });
  form.addCheckbox({cssid:"searchChecked",label:"markierte"});  
  //rows.push(form.render(true));
  

  this.implantStandardFilterCallbacks(this, "cdb_search");    
  
  $("#cdb_filter").html(form.render(true));

  
  // Setze die Werte auf die aktuellen Filter
  $.each(this.filter, function(k,a) {
    $("#"+k).val(a);
  });

  // Callbacks 
  this.implantStandardFilterCallbacks(this, "cdb_filter");
};

GroupView.prototype.checkFilter = function(a) {
  // Ausblenden von versteckten Gruppen
  if (!this.isAllowedToSeeDetails(a.id))
    return false;
  
  // Suchfeld
  searchEntry=this.getFilter("searchEntry").toUpperCase();

  if (searchEntry!="") {
    // Split by " ", but not masked with a "
    searches=searchEntry.match(/(?:[^\s"]+|"[^"]*")+/g);
    var res=true;
    $.each(searches, function(k,search) {
      search=search.replace(/"/g, "");
      // Erst mal die Tags checken
      if (search.indexOf("TAG:")==0) {
        if (!this_object.checkFilterTag(search, a.tags)) {
          res=false;
          return false;
        }
      }
      // searchEntry>0 zeigt, dass es sich um eine ID handelt, soll also nicht per Text gesucht werden!
      else if ((search!="") && ((a.bezeichnung.toUpperCase().indexOf(search)<0) || (search>0)) &&
            (a.id!=search)) {
        res=false;
        return false;
      }    
    });
    if (!res) return false;
  }

  
  

  if ((this.filter["searchChecked"]!=null) && (a.checked!=true)) return false;

  // Filter der eigenen Gruppen, Bereiche, Status und Station
  if ((this.filter["filterMeine Gruppen"]!=null) && (a.id!=this.filter["filterMeine Gruppen"])) return false;
    
  if ((this.filter["filterDistrikt"]!=null)
      && ((a.distrikt_id==null)||(a.distrikt_id!=this.filter["filterDistrikt"]))) return false;
  if ((this.filter["filterGruppentyp"]!=null)
      && ((a.gruppentyp_id==null)||(a.gruppentyp_id!=this.filter["filterGruppentyp"]))) return false;

  return true;
};

/**
 * is current login user is allowed to see the name of the group g_id of person p_id
 */
GroupView.prototype.isAllowedToSeeName = function(g_id, p_id) {
  return ((masterData.auth.editgroups) && (masterData.groups[g_id].versteckt_yn==0)) 
            || (groupView.isAllowedToSeeDetails(g_id))
            || (groupView.isPersonLeaderOfGroup(masterData.user_pid, g_id))
            || ((groupView.isGroupViewableForMembers(g_id)) && (personView.isPersonLeaderOfPerson(masterData.user_pid, p_id)));             
};

/**
 * Is current login user is allowed to see the group details for g_id
 */
GroupView.prototype.isAllowedToSeeDetails = function(g_id) {
  if ((masterData.groups!=null) && (masterData.groups[g_id]!=null)) {
    if (user_access("viewgroups", g_id))
      return true;
    
    if ((masterData.auth.viewalldetails) || (masterData.auth.admingroups) || (groupView.isPersonLeaderOfGroup(masterData.user_pid, g_id)) 
      || (groupView.isPersonInGroup(masterData.user_pid, g_id) && (groupView.isGroupViewableForMembers(g_id))))

      if ((masterData.groups[g_id].versteckt_yn==0) || (masterData.auth.admingroups) 
          || (groupView.isPersonLeaderOfGroup(masterData.user_pid, g_id)))
        return true; 
    if (allPersons[masterData.user_pid]!=null) {
      if ((allPersons[masterData.user_pid].districts) && (allPersons[masterData.user_pid].districts[masterData.groups[g_id].distrikt_id]!=null))
        return true;
      if ((allPersons[masterData.user_pid].gruppentypen) && (allPersons[masterData.user_pid].gruppentypen[masterData.groups[g_id].gruppentyp_id]!=null))
        return true;
    }
  }
  return false;
};


GroupView.prototype.initView = function() {
  if ((this.filter==null) || (churchcore_isObjectEmpty(this.filter))) {
    if (masterData.settings.filterDistrikt!=null) {
      this.filter["filterDistrikt"]=masterData.settings.filterDistrikt;    
    }
  }
};

GroupView.prototype.getListHeader = function() {
  str='<th><a href="#" id="sortid">Nr.</a><th><a href="#" id="sortbezeichnung">Gruppe</a>';
  str=str+'<th><a href="#" id="sortdistrikt_id">'+f("distrikt_id")+'</a><th><a href="#" id="sortgruppentyp_id">'+f('gruppentyp_id')+'</a>';

  if (this.filter["filterDistrikt"]=="null") delete this.filter["filterDistrikt"];
  
  if ((this.filter["filterGruppentyp"]!=null) || (this.filter["filterDistrikt"]!=null)) {
    str=str+"<th>Leiter<th title=\"Gesamte Teilnehmer\">TN-Gesamt";
    if (this.range_startday!=null)
      str=str+'<th title="Hinzugef&uuml;gte Teilnehmer innerhalb des Schiebereglers">TN-Hinzugef&uuml;gt<th title="Herausgenommene Teilnehmer innerhalb des Schiebereglers">TN-Herausgenommen';
    else {
      str=str+'<th title="Teilnehmer die auch Mitglied sind">TN-Mitglied-Status<th>TN-Nicht Mitglied';      
    }
  }
  str=str+"<th>Tags";    
  return str;
};

GroupView.prototype.renderListEntry = function(group) {
  var this_object=this;
  var rows=new Array();
  var filter=false;
  var todo_1=0;
  var todo_2=0;

  if ((this.filter["filterGruppentyp"]!=null) || (this.filter["filterDistrikt"]!=null)) {
    counter=0;
    leader=0;
    $.each(allPersons,function(k,a) {
      if (a.gruppe!=null) {
        if (((this_object.filter["filterStatus"]!=null) && (a.status_id!=this_object.filter["filterStatus"])) 
          || ((this_object.filter["filterStation"]!=null) && (a.status_id!=this_object.filter["filterStation"])) 
          || ((this_object.filter["filterBereich"]!=null) && (a.status_id!=this_object.filter["filterBereich"]))) 
          filter=true;
        else {
          $.each(a.gruppe, function(i,b) {           
            if (b.id==group.id) {
              if (b.leiter==0 || b.leiter==4) counter++;
              else if (b.leiter==-1) todo_1=todo_1+1;
              else if (b.leiter==-2) todo_2=todo_2+1;
              else leader++;
            }
          });
        } 
      }    
    });
  }
  else counter="";  
  
  if ((!filter) || (counter>0)) {
    rows.push('<td><a href="#" id="detail'+group.id+'">' + group.bezeichnung+'</a>&nbsp; ');
    
    if ((masterData.auth.adminpersons) && (group.auth!=null)) {
      rows.push(this.renderImage("schluessel", 18, "Berechtigungen: "+this.getAuthAsArray(group.auth).join(", "))+"&nbsp;");
    }
    if (group.offen_yn==1 && group.oeffentlich_yn==1)
      rows.push(form_renderImage({src:"unlock.png", width:18, label:"Offene und öffentliche Gruppe. Anmeldung über einen Link möglich"})+" ");
    else if (group.offen_yn==1 && group.oeffentlich_yn==0)
      rows.push(form_renderImage({src:"unlock.png", width:18, label:"Offene Gruppe, Teilnahme kann über Startseite beantragt werden."})+" ");      
    
    if ((todo_1>0) && ((masterData.auth.admingroups) || (this_object.isPersonLeaderOfGroup(masterData.user_pid, group.id))))  
      rows.push('<span title="Person soll geloescht werden" class="badge badge-important">'+todo_1+'</span>&nbsp;');
    if ((todo_2>0) && ((masterData.auth.admingroups) || (this_object.isPersonLeaderOfGroup(masterData.user_pid, group.id))))  
      rows.push('<span title="Person hat bzw. wurde auf Antrag auf Teilnahme gestellt" class="badge badge-info">'+todo_2+'</span>&nbsp;');
    
    if (group.distrikt_id!=null)
      rows.push('<td><a href="#" id="filterDistrikt'+group.distrikt_id+'">' 
                     + (masterData.districts[group.distrikt_id]!=null?
                            masterData.districts[group.distrikt_id].bezeichnung
                            :"<font color=\"red\">Distrikt-Id:"+group.distrikt_id+"</font>")+'</a>'); 
    else rows.push("<td>-");
    if (group.gruppentyp_id!=null)
      rows.push('<td><a href="#" id="filterGruppentyp'+group.gruppentyp_id+'">' 
                     + (masterData.groupTypes[group.gruppentyp_id]!=null?
                       masterData.groupTypes[group.gruppentyp_id].bezeichnung
                          :'<font color="red">Gruppentyp_Id:'+group.gruppentyp_id+'</font>')+'</a>'); 
    else rows.push("<td>-");
    if ((this.filter["filterGruppentyp"]!=null) || (this.filter["filterDistrikt"]!=null)) {
      rows.push("<td>"+leader+"<td>"+counter);
      if (group.max_teilnehmer!=null)
        rows.push(" <small>(max. "+group.max_teilnehmer+")</small>");
    
      // Zeige nun neue Leute innerhalb des Schiebereglers
      if (this_object.range_startday!=null) {
        count=0;
        var start=new Date();
        start.addDays(this_object.range_startday);
        var end=new Date();
        end.addDays(this_object.range_endday);
        $.each(allPersons,function(k,a) {
          if (a.gruppe!=null) {
            $.each(a.gruppe, function(i,b) {
              if ((b.id==group.id) && (b.d!=null)) {
                var d = b.d.toDateEn();
                if ((d>=start) && (d<=end)) {
                  if (b.leiter==0 || b.leiter==4) 
                    count=count+1;
                }
              }
            });
          }
        });
        rows.push('<td>'+count);
      }
      // Zeige nun neue Leute innerhalb des Schiebereglers
      if (this_object.range_startday!=null) {
        count=0;
        var start=new Date();
        start.addDays(this_object.range_startday);
        var end=new Date();
        end.addDays(this_object.range_endday);
        $.each(allPersons,function(k,a) {
          if (a.oldGroups!=null) {
            $.each(a.oldGroups, function(i,b) {
              if ((b.gp_id==group.id) && (b.leiter==-99) && (b.d!=null)) {
                var d = b.d.toDateEn();
                if ((d>=start) && (d<=end)) {
                  count=count+1;
                  return false;
                }
              }
            });
          }
        });
        rows.push('<td>'+count);
      }
      // Member or not
      if (this_object.range_startday==null) {
        var member=0;
        var not_member=0;
        $.each(allPersons,function(k,a) {
          if (a.gruppe!=null) {
            $.each(a.gruppe, function(i,b) {
              if (b.id==group.id) {
                // Member or stuff
                if (b.leiter==0 || b.leiter==4) {
                  if (masterData.status[a.status_id].mitglied_yn==1)
                    member=member+1;
                  else not_member=not_member+1;    
                }
              }
            });
          }
        });
        rows.push("<td>"+member+"<td>"+not_member);
      }
    }
    
    var t="";
    if (group.tags!=null)
      $.each(group.tags, function(k,a) {
        t=t+this_object.renderTag(a,false)+"&nbsp;";
      });
    rows.push("<td>"+t);
    return rows.join("");
  }
  else return null; 
};

GroupView.prototype.addFurtherListCallbacks = function() {
  $("#cdb_content a").click(function (a) {
    if ($(this).attr("id")==null) {
      return false;
    }
    else {
      if ($(this).attr("id").indexOf("filterDistrikt")==0) {
        groupView.clearFilter();
        groupView.setFilter("filterDistrikt",$(this).attr("id").substr(14,99));
        groupView.renderView();
        return false;
      }
      else if ($(this).attr("id").indexOf("filterGruppentyp")==0) {
        groupView.clearFilter();
        groupView.setFilter("filterGruppentyp",$(this).attr("id").substr(16,99));
        groupView.renderView();
        return false;
      }
      else if ($(this).attr("id").indexOf("search_tag")==0) {
        groupView.setFilter("searchEntry",'tag:"'+masterData.tags[$(this).attr("id").substr(10,99)].bezeichnung+'"');
        groupView.renderView();        
      }
    }
 });
};

GroupView.prototype.isPersonInGroup = function (p_id, g_id) {
  var res = false;
  if ((p_id!=null) && (allPersons[p_id]!=null) && (allPersons[p_id].gruppe!=null)) {
    $.each(allPersons[p_id].gruppe, function (k,a) {
      if ((a.id==g_id) && (a.leiter!=99))
        res=true;
    });
  }        
  return res;
};

GroupView.prototype.isGroupViewableForMembers = function (gruppe_id) {
  if ((masterData.groups[gruppe_id]==null) || (masterData.groups[gruppe_id].versteckt_yn==1)) return false;
  return masterData.groupTypes[masterData.groups[gruppe_id].gruppentyp_id].anzeigen_in_meinegruppen_teilnehmer_yn==1;  
};


GroupView.prototype.isGroupOfGroupType = function (gruppe_id, gruppentyp_id) {
  if (masterData.groups[gruppe_id]==null) return false;
  return masterData.groups[gruppe_id].gruppentyp_id==gruppentyp_id; 
};

/**
 * Gibt zur�ck, ob Person Leiter, coleiter oder Supervisor ist (nicht MA) => True ansonsten false
 * @param p_id
 * @param g_id
 * @return true or false
 */
GroupView.prototype.isPersonLeaderOfGroup = function (p_id, g_id) {
  var res = false;
  if ((p_id!=null) && (allPersons[p_id]!=null)) {
    if (allPersons[p_id].gruppe!=null) {
      $.each(allPersons[p_id].gruppe, function (k,a) {
        if ((a.id==g_id) && (a.leiter>0) && (a.leiter!=4)) {
          res=true;
          return false;
        }
      });      
    }
    if (res) return true;
    return this.isPersonSuperLeaderOfGroup(p_id, g_id);
  }        
  return res;
};

GroupView.prototype.isPersonSuperLeader = function (p_id) {
  if (allPersons[p_id]==null) return false;
  if (allPersons[p_id].districts!=null || allPersons[p_id].gruppentypen!=null)
    return true;
  return false;
};  



GroupView.prototype.isPersonSuperLeaderOfGroup = function (p_id, g_id) {
  if ((allPersons[p_id]==null) || (masterData.groups==null) || (masterData.groups[g_id]==null)) return false;
  if ((allPersons[p_id].districts!=null) && (allPersons[p_id].districts[masterData.groups[g_id].distrikt_id]!=null))
    return true;
  if ((allPersons[p_id].gruppentypen!=null) && (allPersons[p_id].gruppentypen[masterData.groups[g_id].gruppentyp_id]!=null))
    return true;  
};

GroupView.prototype.getMemberOfGroup = function (g_id, status_no) {
  var res = new Object();
  $.each(allPersons, function(k,p) {
    if (p.gruppe!=null) {
      $.each(p.gruppe, function (i,a) {
        if ((a.id==g_id) && ((status_no==null) || (a.leiter==status_no))) {
          var o= new Object();
          o.id=p.id;
          o.bezeichnung=p.vorname+" "+p.name;
          res[a.id]=o;
        }
      });
    }
  });
  return res;
};
// Co-leioter, leiter, supervisor
GroupView.prototype.getLeaderOfGroup = function (g_id) {
  var res = new Object();
  $.each(allPersons, function(k,p) {
    if (p.gruppe!=null) {
      $.each(p.gruppe, function (i,a) {
        if ((a.id==g_id) && (a.leiter>0) && (a.leiter!=4)) {
          var o= new Object();
          o.id=p.id;
          o.bezeichnung=p.vorname+" "+p.name;
          res[p.id]=o;
        }
      });
    }
  });
  return res;
};

GroupView.prototype.editAutomaticEMails = function(g_id) {
  var rows = new Array();
  
  rows.push('<legend>Automatische E-Mails f&uuml;r '+masterData.groups[g_id].bezeichnung+'</legend>');
  rows.push('<p><small>Sobald eine automatische Mail gespeichert und aktiviert ist, werden ab dem Zeitpunkt alle'+
           ' neuen Teilnehmer mit dieser E-Mail begruesst. Absender ist automatisch der erste Leiter der Gruppe</small>');
  rows.push('<form class="form-horizontal">');
  rows.push(form_renderSelect({label:"Teilnehmerstatus",freeoption:true,data:masterData.groupMemberTypes, cssid:"groupMemberType"}));
  rows.push(form_renderCheckbox({label:"Aktivieren",checked:false, cssid:"aktiv_yn"}));
  var d=this.getLeaderOfGroup(g_id);
  rows.push(form_renderSelect({label:"Absender",data:d, cssid:"sender_pid", freeoption:true}));
  rows.push(form_renderInput({label:"Betreff", type:"xlarge", cssid:"email_betreff"}));
  rows.push('<div id="editor"</div>');
  rows.push('</form>');
  
  var elem = this.showDialog("Automatische E-Mails verwalten", rows.join(""), 566, 600, {
    "Speichern": function() {
      if ($("#groupMemberType").val()=="") {
        alert("Bitte erst einen Teilnehmerstatus auswählen!");
        return;
      }
      if (($("#aktiv_yn").attr("checked")=="checked") && ($("#sender_pid").val()=="")) { 
        alert("Es muss eine Person als Absender genommen werden!");
      }
     else {
       churchInterface.jsendWrite({func:"saveGroupAutomaticEMail", id:g_id, 
          status_no:$("#groupMemberType").val(), 
          aktiv_yn:($("#aktiv_yn").attr("checked")=="checked"?1:0), 
          sender_pid:$("#sender_pid").val(), 
          email_betreff:$("#email_betreff").val(),
          email_inhalt:CKEDITOR.instances.editor.getData()}, 
        function(ok, data) {
          if (ok) {
            alert("Automatische E-Mail für "+masterData.groupMemberTypes[$("#groupMemberType").val()].bezeichnung+" wurde gespeichert"+($("#aktiv_yn").attr("checked")=="checked"?" und ist aktiviert!":"."));
          }
          else alert("Fehler: "+status);
        });
     }
    },
    "Schliessen": function() {
      $(this).dialog("close");
    }
  }
  );
  form_implantWysiwygEditor("editor", false);
  function _changeGroupMemberType() {
    if ($("#groupMemberType").val()!="") {
      churchInterface.jsendWrite({func:"getGroupAutomaticEMail", id:g_id, status_no:$("#groupMemberType").val()}, 
        function(ok, data) {
        if (ok) {
          if (data==null) {
            $("#aktiv_yn").removeAttr("checked");
            $("#sender_pid").val("");
            $("#email_betreff").val("Infomail zur Gruppe "+masterData.groups[g_id].bezeichnung)
            if (masterData.settings.signature!=null)
              CKEDITOR.instances.editor.setData(masterData.settings.signature);
          }
          else {
            if (data.aktiv_yn==1)
              $("#aktiv_yn").attr("checked","checked");
            else $("#aktiv_yn").removeAttr("checked");
            $("#sender_pid").val(data.sender_pid);
            $("#email_betreff").val(data.email_betreff);
            CKEDITOR.instances.editor.setData(data.email_inhalt);            
          }
        }
      });
    }
  }
  _changeGroupMemberType();
  
  elem.find("#groupMemberType").change(function(k) {
    _changeGroupMemberType();      
  });
  
};


GroupView.prototype.getStatsOfGroup = function(g_id) {
  var t = this;
  var stats=new Object();
  stats.count_all_member=0;
  stats.count_all_people=0;
  stats.sum_age=0;
  stats.count_age=0;
  stats.sum_age_all=0;
  stats.count_age_all=0;
  stats.entries=new Array();
  
  $.each(allPersons, function(k, a) {
    if (a.gruppe!=null) {
      $.each(a.gruppe, function (i, b) {
        if ((b.id==g_id) && 
              ((masterData.settings.hideStatus==null) || (a.status_id!=masterData.settings.hideStatus))) {
          if ((b.leiter>=0) && (b.leiter!=3)) {
            stats.count_all_people=stats.count_all_people+1;
            if (a.geburtsdatum!=null) {
              var geb=a.geburtsdatum.toDateEn(false);      
              stats.sum_age_all=stats.sum_age_all+geb.getAgeInYears().num;
              stats.count_age_all=stats.count_age_all+1;
            }
          }  
          if ((b.leiter==0)) {
            stats.count_all_member=stats.count_all_member+1;
            if (a.geburtsdatum!=null) {
              var geb=a.geburtsdatum.toDateEn(false);      
              stats.sum_age=stats.sum_age+geb.getAgeInYears().num;
              stats.count_age=stats.count_age+1;
            }
          }  
          // Eintr�ge nun in ein Array packen und sp�ter dann sortiert ausgeben.
          var entry=new Array();
          entry.id=a.id;
          entry.vorname=a.vorname;
          entry.name=a.name;
          entry.status_id=a.status_id;
          if (b.d!=null)
            entry.date=b.d.toDateEn();
          entry.leiter=b.leiter;
          entry.comment=b.comment;
          stats.entries.push(entry);
        }
      });   
    }
  });   
  return stats;
};

GroupView.prototype.renderTodos = function() {
};

GroupView.prototype.editMailchimp = function (g_id) {
  var t=this;
  if (masterData.groups_mailchimp==null) {
    var elem = form_showCancelDialog("Integration MailChimp", "Lade Daten aus MailChimp...", 300, 300);
    churchInterface.jsendRead({func:"mailchimp", sub:"load"}, function(ok,data) {
      elem.dialog("close");
      if (ok) {
        masterData.groups_mailchimp=data;
        t.editMailchimp(g_id);
      }
      else {
        alert("Fehler aufgetreten: "+data);
      }
    });
  }
  else {    
    var rows = new Array();
    if (masterData.groups_mailchimp.lists.total==0) 
      rows.push("Keine Liste eingerichtet. Bitte erst in MailChimp eine Liste einrichten.");
    else {
      rows.push('<legend>Vorhandene Newsletter f&uuml;r Gruppe '+masterData.groups[g_id].bezeichnung+"</legend>");
      var newList=new Array();
      readableList=new Array();
      $.each(masterData.groups_mailchimp.lists.data, function(k,a) {
        newList.push({id:a.id, bezeichnung:a.name});
        readableList[a.id]={id:a.id, bezeichnung:a.name};
      });
      rows.push('<table class="table table-condensed">');
      rows.push('<tr><th>Listenname<th>Optin notwendig<th>Verabschiedung<th>Admin informieren<th>');
      $.each(masterData.groups_mailchimp.zuordnung, function(k,a) {
        if ((a!=null) && (a.gruppe_id==g_id) && (readableList[a.mailchimp_list_id]!=null)) {
          readableList[a.mailchimp_list_id].chosen=true;
          rows.push("<tr><td>"+readableList[a.mailchimp_list_id].bezeichnung);
          rows.push("<td>"+t.renderYesNo(a.optin_yn));
          rows.push("<td>"+t.renderYesNo(a.goodbye_yn));
          rows.push("<td>"+t.renderYesNo(a.notifyunsubscribe_yn));
          rows.push('<td><a href="#" class="delMailchimp" data-id="'+a.mailchimp_list_id+'">'+form_renderImage({src:"trashbox.png", width:20})+'</a>');          
        }
      });
      rows.push("</table>");
      rows.push('<p><legend>Neue Zuordnung hinzuf&uuml;gen</legend>');
      var form = new CC_Form();
      form.setHelp("MailChimp-Integration");
      form.addSelect({data:newList, cssid:"mailchimp_list_id", func:function(a) { return readableList[a.id]==null || !readableList[a.id].chosen;}});
      form.addCheckbox({label:"Neue Teilnehmer der Liste m&uuml;ssen best&auml;tigen (Optin)?", controlgroup_start:true, cssid:"optin_yn"});
      form.addCheckbox({label:"Verabschiedungs-EMail senden beim Verlassen der Liste?", controlgroup:false, cssid:"goodbye_yn"});
      form.addCheckbox({label:"Listenadmin beim Verlassen informieren?", controlgroup_end:true, cssid:"notifyunsubscribe_yn"});
      form.addButton({label:"Hinzuf&uuml;gen", cssid:"addMailchimp"});
      rows.push(form.render(true, "vertical"));
    }
    
    var elem = this.showDialog("Integration MailChimp", rows.join(""), 600, 550, {
      "Schliessen": function() {
        elem.dialog("close");
      }
    });
    elem.find("#addMailchimp").click(function() {
      if ($("#mailchimp_list_id").val()!=null) {
        var o=form.getAllValsAsObject();
        o.gruppe_id=g_id;
        //masterData.groups_mailchimp.zuordnung.push({gruppe_id:g_id, mailchimp_list_id:$("#choseMailchimpList").val()});
        masterData.groups_mailchimp.zuordnung.push(o);
        o.func="mailchimp";
        o.sub="add";
        //churchInterface.jsendWrite({func:"mailchimp", sub:"add", gruppe_id:g_id, mailchimp_list_id:$("#choseMailchimpList").val()});
        churchInterface.jsendWrite(o);
        elem.dialog("close");
        t.editMailchimp(g_id);
      }
      return false;
    });
    elem.find("a.delMailchimp").click(function() {
      var o=$(this);
      $.each(masterData.groups_mailchimp.zuordnung, function(k,a) {
        if ((a!=null) && (a.gruppe_id==g_id) && (a.mailchimp_list_id==o.attr("data-id"))) {
          delete masterData.groups_mailchimp.zuordnung[k];
          return false;
        }
      });
      churchInterface.jsendWrite({func:"mailchimp", sub:"del", gruppe_id:g_id, mailchimp_list_id:o.attr("data-id")});
      elem.dialog("close");
      t.editMailchimp(g_id);
      return false;
    });
  }
};

GroupView.prototype.renderEntryDetail = function(pos_id, data_id) {
  this_object=this;
  function _getGroupStats(p_id, g_id) {
    info="";
      if ((masterData.auth.viewgroupstats) || 
          (this_object.isPersonLeaderOfGroup(masterData.user_pid, pos_id))) {
        if ((masterData.groups[g_id].meetingList!=null) && (masterData.groups[g_id].meetingList!="get data")) {
          info=info+"<br/>";
          var count_dabei=0;
          var count_stattgefunden=0;
          $.each(churchcore_sortData(masterData.groups[g_id].meetingList,"datumvon"), function(k,a) {
            if (a.eintragerfolgt_yn=="0") 
              info=info+'<img title="Eintrag noch nicht erfolgt f�r '+a.datumvon.toDateEn().toStringDe(false)+'" src="'+masterData.modulespath+'/images/box_white.png'+'"/>';
            if (a.ausgefallen_yn=="1") 
              info=info+"x";
            else {  
              var dabei=false;
              $.each(a.entries, function(i,b) {
                if (b.p_id==p_id) {
                  dabei=true;
                  count_stattgefunden=count_stattgefunden+1;
                  if (b.treffen_yn=="1") {
                    count_dabei=count_dabei+1;
                    info=info+'<img title="Dabei am '+a.datumvon.toDateEn().toStringDe(false)+'" src="'+masterData.modulespath+'/images/box_green.png'+'"/>';
                  }
                  else 
                    info=info+'<img title="Abwesend am '+a.datumvon.toDateEn().toStringDe(false)+'" src="'+masterData.modulespath+'/images/box_red.png'+'"/>';
                }
              });
              if ((!dabei) && (a.eintragerfolgt_yn=="1"))
                info=info+'<img title="Am '+a.datumvon.toDateEn().toStringDe(false)+' Offen." src="'+masterData.modulespath+'/images/box_white.png'+'"/>';
            }            
          });
          if (count_stattgefunden>0)
            info=info+" <small>"+Math.round(100*count_dabei/count_stattgefunden)+"%</small>";
        }
      }
    return info;
  }  
  function _filterChecker(a) {
    return (((this_object.filter["filterStatus"]!=null) && (a.status_id!=this_object.filter["filterStatus"])) 
        || ((this_object.filter["filterStation"]!=null) && (a.status_id!=this_object.filter["filterStation"])) 
        || ((this_object.filter["filterBereich"]!=null) && (a.status_id!=this_object.filter["filterBereich"])));
  }
  
  // Start function renderEntryDetail()
  if (data_id==null) 
    data_id=pos_id;
  var g_id=data_id;
  var p_id=pos_id;
  var g=this.getData()[g_id];
  var editGroup = (masterData.auth.admingroups) || (this_object.isPersonLeaderOfGroup(masterData.user_pid,g_id));
  
  // Pr�fe ob es Treffen-Pflege gibt, wenn ja: Pr�fe ob Statistik-List schon vorhanden ist, ansonsten holen
  if ((masterData.auth.viewgroupstats) || (this_object.isPersonLeaderOfGroup(masterData.user_pid, g_id))) { 
    if ((masterData.groups[g_id].meetingList==null)) {
      masterData.groups[g_id].meetingList="get data";
      churchInterface.jsendRead({ func: "GroupMeeting", sub:"getList", g_id: g_id }, function(ok, json) {
        if (json!=null) {
          masterData.groups[g_id].meetingList=json;
          $("#groupinfosTD"+p_id).html("");
          this_object.renderEntryDetail(pos_id, data_id);
        }
        // Dann lege ich ein leeres Array drauf, damit es nicht nochmal geladen wird
        else masterData.groups[g_id].meetingList=new Array();
      });    
    }
  }
  
  
  var rows = new Array();  

  $("tr[id=" + p_id + "]").after("<tr id=\"detail" + p_id + "\"><td colspan=\"10\" id=\"groupinfosTD" + p_id + "\">Lade Daten..</td></tr>");
  rows[rows.length]="<div id=\"detail\" class=\"detail-view\">";
  
  // Linke Spalte
  rows[rows.length]="<div class=\"left-column\">";
//  if (g.treffpunkt!="")
  rows[rows.length]='<div id="map_canvasg'+g_id+'" class="map-canvas"></div>';
  
  
  rows[rows.length]="</div>";
  
  // Rechte Spalte
  rows[rows.length]="<div class=\"right-column\">";

  if (masterData.auth.viewtags || this_object.isPersonLeaderOfGroup(masterData.user_pid, g_id)) 
    rows.push(this_object.renderTags(g.tags, (masterData.auth.admingroups || this_object.isPersonLeaderOfGroup(masterData.user_pid, g_id)), g_id));
  
  rows.push('<div class="detail-view-infobox">');
  rows.push('<p><table><tr style="background:#F4F4F4;">');
  rows.push('<td><i><a href="#" id="sortdetailname">In der Gruppe</a></i>');
  rows.push('<td><i><a href="#" id="sortdetaildate">Dabei seit</a></i><td><td>');  
  stats_dabei=0;
  stats_stattgefunden=0;
  
  var stats=this.getStatsOfGroup(g_id);
  
  var count=100;
  // Sortiere nun Eintr�ge
  stats.entries.sort(function(a,b){
      var arr=new Array(); arr[-1]=-1; arr[-2]=-2; arr[0]=0; arr[1]=5; arr[2]=4; arr[3]=10; arr[4]=1;
      if (arr[a.leiter]==null) return 0;
      if (arr[b.leiter]==null) return 0;
      
      if (arr[a.leiter]<arr[b.leiter]) return 1;
      else if (arr[a.leiter]>arr[b.leiter]) return -1;
      else if (a.leiter==b.leiter) {
        if (this_object.sortdetail=="bezeichnung") {
          var r=((a.name+a.vorname).toUpperCase()>(b.name+a.vorname).toUpperCase()?1:-1);
          return r;
        }
        else  
          return (a.date.getTime()>b.date.getTime()?-1:1);          
      }
      return 0;
    });
  // Und zeige sie an
  var amILeader=this_object.isPersonLeaderOfGroup(masterData.user_pid, g_id);
  var amISuperLeader=this.isPersonSuperLeaderOfGroup(masterData.user_pid, g_id);
  $.each(stats.entries, function(i,a) {
    if (count>0) {          
      count--; 
      rows.push("<tr><td><p><small>");
      info=_getGroupStats(a.id, g_id);      
      if ((a.comment!=null) && (amILeader || masterData.auth.editgroups))
        info="&nbsp;"+this_object.renderImage("comment",16,"Kommentar: "+a.comment)+info;
      style="color:black;";
      if ((_filterChecker(a))||(a.leiter==3)) style="color:gray";
      if (a.leiter==-1) style="color:red;text-decoration:line-through;";
      else if (a.leiter==-2) style="color:#3a87ad;";
      rows[rows.length]='<a href="#" style="'+style+'" id="person_'+a.id+'" class="tooltip-person" '+(masterData.auth.viewalldata?"data-tooltip-id=\""+a.id+"\"":"")+'>'+a.vorname+" "+a.name;
      if (a.leiter>0)
        rows.push(" ("+masterData.groupMemberTypes[a.leiter].bezeichnung+")");
      if (a.leiter==-2) rows.push("?");
      rows.push("</a> "+info);
      rows.push("</small><td>");
      if (a.date!=null)
        rows.push('<small>'+a.date.toStringDe()+'</small>');
      if ((masterData.auth.editgroups) || ((amILeader) && (a.leiter!=-1)) || (amISuperLeader)) {
        rows.push('<td style="width:16px;padding:0 1px"><a href="#" id="editPerson_'+a.id+'" data-pid="'+a.id+'" data-gid="'+g_id+'">'+t.renderImage("options",16)+'</a>');
        rows.push('<td style="width:16px;padding:0 3px 0 0"><a href="#" id="deletePerson_'+a.id+'_'+g_id+'" p_id="'+a.id+'">'+this_object.renderImage("trashbox",16)+'</a>'); 
      }
      
    }
  });
  if (count==0) rows[rows.length]="<tr><td>..";
  rows.push("</table></div>");
  
  
  // Show 4 last comments
  var comments=new Array();
  if (g.meetingList!=null && g.meetingList!="get data") {
    
    rows.push('<p><small>');
    rows.push(form_renderImage({src:"box_green.png"})+" Anwesend &nbsp;");
    rows.push(form_renderImage({src:"box_red.png"})+" Abwesend &nbsp;");
    rows.push(form_renderImage({src:"box_white.png"})+" Offen &nbsp;");
    rows.push("x  ausgefallen</small>");
    
    $.each(churchcore_sortData(g.meetingList,"datumvon", true), function(k,m) {
      if (comments.length<4 && m.kommentar!=null) {
        comments.push(m);
      }
    });
  }
  if (comments.length>0) {
    rows.push('<div class="detail-view-infobox">');
    rows.push("<table><tr><td><i>Die letzten Gruppentreffen-Kommentare");
    $.each(comments, function(k,m) {
      rows.push("<tr><td><p><small>"+m.datumvon.toDateEn().toStringDe()+"<br>"+m.kommentar+'</small>');    
    });
    rows.push('</table></div>');
  }
  

  if ((masterData.auth.viewhistory) && (this_object.range_startday!=null)){
    var history=new Array();
    var start=new Date(); start.addDays(this_object.range_startday);
    var end=new Date(); end.addDays(this_object.range_endday);
    $.each(allPersons, function(k,a) {
      if (a.oldGroups!=null) {
        $.each(a.oldGroups, function(i,b) {
          if ((b.gp_id==g_id) && (b.leiter==-99) && (b.d!=null) && (b.d.toDateEn()>=start) && (b.d.toDateEn()<=end))
            history.push('<tr><td>'+a.vorname+" "+a.name+"<td>"+b.d.toDateEn().toStringDe());
        });
      }
    });
    if (history.length>0) { 
      rows.push('<div class="detail-view-infobox">');
      rows.push("<p><br/><table><tr><td><i>Nicht mehr in der Gruppe</i><td><i>seit</i><td>");
      rows.push(history.join(""));  
      rows.push("</table></div>");
    }
  }
  

  rows.push("<p></p>");
  rows.push('<div class="ui-widget"><legend>Weitere Optionen</legend>');
  rows.push('<p>'+form_renderImage({src:'filter.png', width:18}));
  rows.push('&nbsp;<a href="#" id="grp_to_filter">Gruppe in der Personenliste filtern</a>');    
  if (editGroup) { 
    if ((masterData.groups[g_id].max_teilnehmer==null) || ((masterData.groups[g_id].max_teilnehmer>stats.count_all_member))) {
      rows.push('<p>'+form_renderImage({src:"person.png", width:18}));
      rows.push('&nbsp;<a href="#" id="addPerson">Weitere Person zur Gruppe hinzuf&uuml;gen</a>');
    }
    rows.push('<p>'+form_renderImage({src:'persons.png', width:18}));
    rows.push('&nbsp;<a href="#" style="width=100%" id="edit_meetinglist">Gruppentreffen pflegen</a>');
    if ((masterData.groups[g_id].meetingList!=null) && (masterData.groups[g_id].meetingList!="get data")) {
      rows.push('<p>'+form_renderImage({src:'trashbox.png', width:18}));
      rows.push('&nbsp;<a href="#" id="del_last_statistic">Letztes Gruppentreffen zur&uuml;cksetzen</a>');
    }
  }
  rows.push("</div>");

  rows[rows.length]="</div>";
  
  // Mittlere Spalte
  rows[rows.length]="<div class=\"middle-column\">";
  rows[rows.length]="<legend>"+g.bezeichnung;
  
  if (editGroup) { 
    rows[rows.length]="&nbsp;&nbsp;<a href=\"\" id=\"edit_"+g_id+"\">"+this_object.renderImage("options")+"</a>";   
    rows[rows.length]="&nbsp;<a href=\"\" title=\"Gruppenteilnehmer eine EMail senden\" id=\"sendMail\">"+this_object.renderImage("email")+"</a>";   
  }  
  
  rows[rows.length]="</legend><div class=well><p>";
  if (g.distrikt_id>0)
    rows[rows.length]=f("distrikt_id")+': <a href="#" id="filterDistrikt'+g.distrikt_id+'">'
            + (masterData.districts[g.distrikt_id]!=null?
                masterData.districts[g.distrikt_id].bezeichnung
                :"<font color=\"red\">Distrikt-Id:"+g.distrikt_id+"</font>")+'</a><br/>';
  if ((g.followup_typ_id!=null) && (g.followup_typ_id>0)) {
    rows.push('FollowUp: '+masterData.followupTypes[g.followup_typ_id].bezeichnung+"<br/>");
  }

  rows[rows.length]="<small>Teilnehmeranzahl: "+stats.count_all_member;
  if (stats.count_age>0) {
    rows[rows.length]=" &nbsp; &#216; "+Math.round(10*stats.sum_age/stats.count_age)/10+"J.";
    // Wenn nicht alle ein Alter angegeben haben, dann zeigt er an bei wievielen Alter vorhanden ist.
    if (stats.count_all_member>stats.count_age)
      rows[rows.length]=" ("+stats.count_age+"P.)";
  }
  rows[rows.length]="<br/>";
  rows[rows.length]="Gesamt: "+stats.count_all_people+"";
  if (stats.count_age>0) {
    rows[rows.length]=" &nbsp; &#216; "+Math.round(10*stats.sum_age_all/stats.count_age_all)/10+"J.";
    // Wenn nicht alle ein Alter angegeben haben, dann zeigt er an bei wievielen Alter vorhanden ist.
    if (stats.count_all_people>stats.count_age_all)
      rows[rows.length]=" ("+stats.count_age_all+"P.)";
  }
  rows[rows.length]="</small>";
  
  
  if (stats_stattgefunden>0)
    rows[rows.length]="<p>Teilnehmerquote: "+Math.round(100*stats_dabei/stats_stattgefunden)+"%";
  
  rows[rows.length]="<p>";
  if (g.gruendungsdatum!=null)
    rows[rows.length]="Gr&uuml;ndungsdatum: "+g.gruendungsdatum.toDateEn().toStringDe()+" ";
  if (g.abschlussdatum!=null)
    rows[rows.length]="Abschlussdatum: "+g.abschlussdatum.toDateEn().toStringDe();
  
  rows[rows.length]="<p>";
  if (g.treffzeit!="")
    rows[rows.length]="Treffzeit: "+g.treffzeit+"<br/>";
  rows[rows.length]="Ort des Treffens: "+g.treffpunkt+"<br/>";
  if (g.treffname!="") 
  rows[rows.length]="Treffen bei: "+g.treffname;
    
  rows[rows.length]="<p>";
  rows[rows.length]="Zielgruppe: "+g.zielgruppe+"<br/>";
  rows[rows.length]=f('gruppentyp_id')+': <a href="#" id="filterGruppentyp'+g.gruppentyp_id+'">'
            +(masterData.groupTypes[g.gruppentyp_id]!=null?
                masterData.groupTypes[g.gruppentyp_id].bezeichnung:'<font color="red">Gruppentyp-Id:'+g.gruppentyp_id+'?</font>')+'</a>';

  if ((g.max_teilnehmer!=null)) 
    rows[rows.length]="<p>Max. Teilnehmer: "+g.max_teilnehmer+"";

  if (g.notiz!="") 
    rows[rows.length]="<br/><br/>Notiz: <i>"+g.notiz.replace(/\n/g, '<br/>')+"</i><br/>";

  rows.push('</div><div class="ui-widget">');  
  
  if (masterData.auth.admingroups) {
    rows[rows.length]='<legend>Gruppenadmin-Funktionen</legend>';
    if (g.valid_yn==1)
      rows[rows.length]="<p>"+this_object.renderYesNo(g.valid_yn,16)+"&nbsp; Gruppe ausw&auml;hlbar";        
    if (g.versteckt_yn==1)
      rows[rows.length]="<br/>"+this_object.renderYesNo(g.versteckt_yn,16)+"&nbsp; Gruppe versteckt";
    if (g.instatistik_yn==1)
    rows[rows.length]="<br/>"+this_object.renderYesNo(g.instatistik_yn,16)+"&nbsp; In Statistik";
    if (g.treffen_yn==1)
    rows[rows.length]="<br/>"+this_object.renderYesNo(g.treffen_yn,16)+"&nbsp; Teilnahmenpflege";
    if (g.mail_an_leiter_yn==1)
    rows[rows.length]="<br/>"+this_object.renderYesNo(g.mail_an_leiter_yn,16)+"&nbsp; Leiter per E-Mail informieren";
    if (g.oeffentlich_yn==1) {
      rows[rows.length]="<br/>"+this_object.renderYesNo(g.oeffentlich_yn,16)+"&nbsp; &Ouml;ffentliche Gruppe, Link: ";
      rows.push(' &nbsp; <a class="extern" title="Externer Link zum Anmelden" href="?q=externmapview&g_id='+g.id+'" target="_clean">'+form_renderImage({src:"right.png", width:20})+'</a>');
    }
    if (g.offen_yn==1)
    rows[rows.length]="<br/>"+this_object.renderYesNo(g.offen_yn,16)+"&nbsp; Offene Gruppe &nbsp; "+form_renderImage({src:"unlock.png", label:"Teilnahme kann auf der Startseite und bei öffentlichen Gruppen auch über einen Link beantragt werden", width:18});
    rows.push('<br/>&nbsp;<p>'+form_renderImage({src:"email.png", width:18})+'&nbsp; <a href="#" id="editAutomaticEmails">Automatische E-Mails verwalten</a>');
    if (masterData.mailchimp)
      rows.push('<p>'+form_renderImage({src:"mailchimp.png", width:18})+'&nbsp; <a href="#" id="editMailchimp">MailChimp-Listen zuordnen</a>');
  }

  if (masterData.auth.adminpersons) {
    //rows[rows.length]="<br/><legend>Teilnehmerberechtigungen";        
    rows[rows.length]='<p style="margin-bottom:0px">'+form_renderImage({src:"schluessel.png", width:18})+"&nbsp; <a href=\"\" id=\"auth_"+g_id+"\">Gruppenteilnehmer berechtigen</a>";
//    rows[rows.length]="&nbsp;&nbsp;<a href=\"\" id=\"auth_"+g_id+"\">"+this_object.renderImage("options")+"</a></legend>";
    if (g.auth!=null) {
      rows.push('<p style="margin-left:22px"><small><i>Aktuelle Berechtigungen:</i><br/>');
      var ar = new Array();
      $.each(g.auth, function(k,a) {
        ar.push(this_object.renderAuth(k));
      }); 
      rows.push(ar.join(", "));
    }
    rows.push('</small></p>');
  }
  rows.push('</div>');
  
  rows.push("</div>");
  
  rows[rows.length]="<div style=\"clear:both\">";   

  rows[rows.length]="<div class=\"detail-footer\">";
  rows[rows.length]="<div display:inline;\">&nbsp;";
  rows[rows.length]="<div style=\"float:right\"><small>";
  
  if (masterData.auth.admingroups)
    rows[rows.length]="Admin-Funktion: &nbsp;<a href=\"#\" title=\"Gruppe entfernen\" id=\"deleteGroup\">"+form_renderImage({src:"trashbox.png", width:18})+"</a>&nbsp;&nbsp;";

  
  if (g.letzteaenderung!=null)
    rows[rows.length]="&nbsp;<i>Letzte &Auml;nderung: "+g.letzteaenderung.toDateEn().toStringDe()+" durch "+g.aenderunguser+" &nbsp;";
  rows[rows.length]="&nbsp;id:"+g.id+"</i></small>&nbsp;&nbsp;";
  rows[rows.length]="</small></div></div>";
  
  
  rows[rows.length]="</div>";  
  rows[rows.length]="</div>";  
  
  $("#groupinfosTD"+p_id).html(rows.join(""));
  
  this.addFurtherListCallbacks();
  
  this.addTagCallbacks(g_id, function(tag_id) {
    if (masterData.groups[g_id].tags==null)
      masterData.groups[g_id].tags= new Array();
    masterData.groups[g_id].tags.push(tag_id);
    churchInterface.jsendWrite({func:"addGroupTag", id:g_id, tag_id:tag_id});
    this_object.renderList();
    this_object.renderEntryDetail(g_id);      
  });
  
  $("#cdb_content a").click(function (a) {
    if ($(this).hasClass("extern")) {
      fenster = window.open($(this).attr("href"), "Anmeldung", "width=700,height=600,resizable=yes");
      fenster.focus();
      return false;
    }
    else if ($(this).attr("id")!=null) {
      if ($(this).attr("id").indexOf("auth_")==0) {
        var g_id=$(this).attr("id").substr(5,99);
        this_object.editDomainAuth(g_id, masterData.groups[g_id].auth, "gruppe", function(id) {
          cdb_loadMasterData(function() {
            churchInterface.getCurrentView().renderView();
          });        
        });      
      } 
      else if ($(this).attr("id")=="deleteGroup") {
        var del = false;
        $.each(allPersons, function(k,a) {
          if (a.gruppe!=null) {
            $.each(a.gruppe, function(i,b) {
              if (b.id==p_id) del=true;
            }); 
          }
        });
        
        var form=new CC_Form();
        
        if (del) {
          form.addHtml("<br/><p>Achtung, zu der Gruppe sind noch Personen zugeordnet. Sollen diese aus der Gruppe genommen werden? Das kann nicht r&uuml;ckg&auml;ngig gemacht werden!");
          form.addCheckbox({label:"Personen aus der Gruppe herausnehmen", cssid:"deletePersonGroup"});
        }
        
        var elem=form_showDialog("Wirklich Gruppe löschen?", form.render(null, "vertical"), 370, 300, {
          "Ja": function() {
            var obj=form.getAllValsAsObject();
            if ((del) && (obj.deletePersonGroup==0))
              alert("Ohne das Setzen des Hakens kann die Gruppe nicht entfernt werden!");
            else {
              obj.func="deleteGroup";
              obj.id=p_id;
              churchInterface.jsendWrite(obj, function(ok) {
                if (ok) {        
                  delete masterData.groups[p_id];
                  churchInterface.getCurrentView().renderList();
                  elem.dialog("close");
                }
                else alert("Fehler aufgetreten!");
              });
            }
          },
          "Abbruch":function() {
            $(this).dialog("close");
          }
        });      
      }  
      return false;
    }
 });  
  $("#groupinfosTD"+p_id+" a").click(function() {
    if ($(this).attr("id")!=null) {
      // L�sche den Tooltip, falls es ihn gibt
      clearTooltip();
      if ($(this).attr("id")=="grp_to_filter") {
        $("#cdb_group").html("");
        churchInterface.setCurrentView(personView);
        personView.clearFilter();
        delete masterData.settings.selectedMyGroup;
        personView.furtherFilterVisible=true;
        personView.filter["filterTyp 1"]=g.gruppentyp_id;
        personView.filter["filterGruppe 1"]=g_id;
        personView.currentFurtherFilter="gruppe";
        personView.renderView();
        return false;                
      }
      else if ($(this).attr("id").indexOf("addPerson")==0) {
        personView.renderPersonSelect("Nach einer Person suchen", true, function(id) {
          if (!personView.addPersonGroupRelation(id, g_id)) 
            alert("Fehler beim Erstellen der Person-Gruppenbeziehung. Ist die Person schon in der Gruppe?");
          else {
            groupView.renderList();
          }
        });            
        return false;
      }
      else if ($(this).attr("id").indexOf("editPerson_")==0) {
        var id=$(this).attr("data-pid");
        var res=personView.renderPersonGroupRelation($(this).attr("data-pid"), $(this).attr("data-gid"));      
        width=380; height=330;
        if (res==null) return false;      
        var elem = this_object.showDialog("Veränderung des Datensatzes", res, 380, 330, {
          "Speichern": function() {
             personView._saveEditEntryData(id, "editPersonGroupRelation", true, $(this));
           },
           "Abbruch": function() {
             $(this).dialog("close");
           }
      });
        
        groupView.renderList();
        return false;
      }
      else if ($(this).attr("id").indexOf("deletePerson_")==0) {
        var id=$(this).attr("p_id");
        //if (confirm("Wirklich "+allPersons[id].vorname+" "+allPersons[id].name+" aus der Gruppe entfernen?")) {
          personView.delPersonFromGroup(id, g_id);
       // }     
        groupView.renderList();
        return false;
      }
      else if ($(this).attr("id").indexOf("sortdetailname")==0) {
        groupView.sortdetail="bezeichnung";
        groupView.renderList();
        return false;
      }
      else if ($(this).attr("id").indexOf("sortdetaildate")==0) {
        groupView.sortdetail="date";
        groupView.renderList();
        return false;
      }
      else if ($(this).attr("id").indexOf("editAutomaticEmails")==0) {
        groupView.editAutomaticEMails(p_id);
        return false;
      }
      else if ($(this).attr("id").indexOf("editMailchimp")==0) {
        groupView.editMailchimp(p_id);
        return false;
      }
      else if ($(this).attr("id").indexOf("person_")==0) {   
        $("#cdb_group").html("");
        churchInterface.setCurrentView(personView);
        personView.clearFilter();
        personView.setFilter("searchEntry","#"+$(this).attr("id").substr(7,99));
        personView.renderView();
      }
      else if ($(this).attr("id").indexOf("edit_meetinglist")==0) {   
        $("#cdb_group").html("");
        churchInterface.setCurrentView(personView);
        personView.clearFilter();
        personView.setFilter("filterMeine Gruppen",g_id);
        delete masterData.settings.selectedMyGroup;
        masterData.settings.selectedGroupType=-4;
        personView.renderView();
      }
      else if ($(this).attr("id").indexOf("del_last_statistic")==0) {
        if (confirm("Wirklich die letzte Eintragung entfernen?")) {
          churchInterface.jsendWrite({func:"deleteLastGroupStatistic", id:g_id}, function(ok, data) {
            if (!ok) {
              alert("Fehler: "+data);
            }
            else {
              cdb_loadGroupMeetingStats(churchInterface.getCurrentView().filter, g_id, function() {
                masterData.groups[g_id].meetingList=null;
                groupView.renderList();
              });
            }
          });
        }        
        return false;
      }
      else if ($(this).attr("id").indexOf("grp_close")==0) {
        $("#groupinfosTD"+p_id).remove();
        return false;
      } 
      else if ($(this).attr("id").indexOf("edit_")==0) {
        this_object.renderEditEntry(g_id);
        return false;
      } 
      else if ($(this).attr("id").indexOf("sendMail")==0) {
        var ids=""; var namen=""; var erster=true; var noemail=false;
        var mailTo="";
        var separator=(masterData.settings.mailerSeparator==0?";":",");
        if (masterData.settings.mailerType!=0)
          separator=separator+" ";
        $.each(allPersons, function(k, a) {
          if (a.gruppe!=null) {
            $.each(a.gruppe, function (i, b) {
              if ((b.id==g_id) && (b.leiter!=-1) &&
                    ((masterData.settings.hideStatus==null) || (a.status_id!=masterData.settings.hideStatus))) {
                if (a.email=="") noemail=true;
                else {
                  if (erster)
                    erster=false; 
                  else {
                    ids=ids+",";
                    namen=namen+", ";
                  }
                  ids=ids+a.id;
                  namen=namen+a.vorname+" "+a.name;
                  mailTo=mailTo+$.trim(a.email)+separator;
  
                }
              }
            });
          }
        });
        if (ids=="") 
          alert("Keine Personen mit EMail-Adressen gefunden.");
        else {
          if (noemail) alert("Hinweis: Einige Einträge haben keine E-Mailadresse, diese wurden nicht berücksichtigt!");
          
          
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
            this_object.mailPerson(ids, namen.trim(80), "Gruppeninfos "+masterData.groups[g_id].bezeichnung);
          }
  
          
        }
        return false;
      }
      else if ($(this).attr("id").indexOf("add_tag")==0) {
        $("#add_tag_field"+g_id).toggle();
        $("#input_tag"+g_id).focus();
        return false;
      }
      else if ($(this).attr("id").indexOf("del_tag")==0) {    
        masterData.groups[g_id].tags.splice($.inArray($(this).attr("id").substring(7,99),masterData.groups[g_id].tags),1);
        churchInterface.jsendWrite({func:"delGroupTag", id:g_id, tag_id:$(this).attr("id").substring(7,99)}, null, false);
        this_object.renderView();
        this_object.renderEntryDetail(g_id);
        return false;
      }
      else if ($(this).attr("id").indexOf("search_tag")==0) {
        if (masterData.tags[$(this).attr("id").substring(10,99)]!=null) {
          this_object.setFilter("searchEntry","tag:"+masterData.tags[$(this).attr("id").substring(10,99)].bezeichnung);
          this_object.renderView();
        }
        return false;      
      }
    }
  });

  personView.addPersonsTooltip($("#groupinfosTD"+p_id));
  cdb_showGeoGruppe(g.treffpunkt, g.id); 
};

GroupView.prototype.renderEditEntry = function (id, fieldname) {
  var this_object=this;
  
  var elem = this.showDialog("Veränderung der Gruppe", "", 500, 600, {
    "Speichern": function() {
      var s = $(this).attr("id");
      
      obj=this_object.getSaveObjectFromInputFields(id, "f_group", masterData.groups[id]);
      
      //if (obj.max_teilnehmer=="") delete obj.max_teilnehmer;
      if (masterData.groups[id].max_teilnehmer=="") masterData.groups[id].max_teilnehmer=null; 
  
      // L�sche die Geoinfos, falls es da ein Update bei der Adresse gab.
      masterData.groups[id].geolat="";
        
      $("#cbn_editor").html("<p><br/><b>Daten werden gespeichert...</b><br/><br/>");
      churchInterface.jsendWrite(obj, function(ok) {
        // Hier wird absichtlich die CurrentView neu gerendet, es kann sein, dass eine Gruppe ja aus der
        // Personensicht geaendert wurde!
        churchInterface.getCurrentView().renderList();
      });      
      $(this).dialog("close");
    },
    "Abbruch": function() {
      $(this).dialog("close");
    }
  });
  
  var rows = new Array();  
  
  var auth=new Array();
  if (this_object.isPersonSuperLeaderOfGroup(masterData.user_pid, id)) {
    auth.push("superleader");
  }
  if (this_object.isPersonLeaderOfGroup(masterData.user_pid, id)) {
    auth.push("leader");
  }
  
  this.renderStandardFieldsAsSelect(elem, "f_group", $.extend({},masterData.groups[id]), auth);
  elem.find("#Inputfu_nachfolge_typ_id").change(function() {
    elem.dialog("close");
    masterData.groups[id]["fu_nachfolge_typ_id"]=$(this).val();
    this_object.renderEditEntry(id, fieldname);
  });

};


GroupView.prototype.standardFieldCoder = function (typ, arr) {
  if (typ=="selectNachfolgeObjektId") {
    switch (arr["fu_nachfolge_typ_id"]) {
      case "1": return masterData.groupTypes; break;
      case "2": return masterData.districts; break;
      case "3": return masterData.groups; break;
      default: return null;
    }
  }
  alert("Keine Coder gefunden fuer: "+typ);
  return null;
};


//Weitere Functions ohne Implementierung der AbstractView

GroupView.prototype.renderFurtherFilter = function () {
  $("#divaddfilter").html('<div id="addfilter" style="width:100%;"><div style="height:200px"/></div>');
  if (this.furtherFilterVisible) {
    $("#divaddfilter").animate({ height: 'show'}, "fast");  
    this_object=this;
    var rows = new Array();
    rows.push("<p align=\"center\" class=\"addfilter-head\">Gruppenfilter");
    rows.push("&nbsp;&nbsp;&nbsp;<small><a href=\"#\" id=\"reset_personfilter\" style=\"color:lightgrey;\">(Filter zur&uuml;cksetzen)</a></small>");
    
    rows.push("<p class=\"addfilter-body\">");      
    rows.push("&nbsp;&nbsp;");
    
    rows.push(this.getSelectFilter(masterData.status, f("status_id"), this_object.filter["filterStatus"]));
    rows.push(this.getSelectFilter(masterData.station, f("station_id"), this_object.filter["filterStation"]));
    rows.push(this.getSelectFilter(masterData.dep,f("bereich_id"), this_object.filter["filterBereich"]));
  
    rows.push("<br/>&nbsp;&nbsp;");
  
    rows.push("<i>Diesen Gruppenfilter filtern die Teilnehmer pro Gruppe, bitte vorher "+f("distrikt_id")+" oder "+f('gruppentyp_id')+" w&auml;hlen!</i>");
    
    $("#addfilter").html(rows.join(""));  
  
    // Callbacks
    this.implantStandardFilterCallbacks(this, "addfilter");
    $("#addfilter a").click(function(c) {
      this_object.filter[$(this).attr("id")]=$(this).attr("checked");
      if ($(this).attr("id")=="reset_personfilter") {
        delete this_object.filter["filterStatus"];    
        delete this_object.filter["filterStation"];    
        delete this_object.filter["filterBereich"];
        this_object.renderFurtherFilter();
        listOffset=0;
        this_object.renderList();     
        return false;
      } 
    });        
  }
  else {
    $("#divaddfilter").animate({ height: 'hide'}, "fast");  
  }
};

})(jQuery);
