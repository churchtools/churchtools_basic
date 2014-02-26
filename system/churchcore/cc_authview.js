 
// Constructor
function AuthView() {
  StandardTableView.call(this);
  this.name="AuthView";
  this.allDataLoaded=false;
  this.renderTimer=null;
  this.currentDomain="person";
}

Temp.prototype = StandardTableView.prototype;
AuthView.prototype = new Temp();
authView = new AuthView();

var masterData=null;


AuthView.prototype.getData = function(sorted) {
  if (sorted)
    return churchcore_sortData(masterData[this.currentDomain], "bezeichnung");
  else
    return masterData[this.currentDomain];
};

AuthView.prototype.checkFilter = function(a) {
  if (this.filter==null) return true;
  
  if ((this.filter["filterAuth"]!=null)) 
    return (a.auth!=null) && (a.auth[this.filter["filterAuth"]]!=null);

  if ((this.filter["searchAuth"]!=null) && (a.auth==null)) return false;
  
  if (this.filter["searchEntry"]!=null) {
    if (a.bezeichnung.toUpperCase().indexOf(this.filter["searchEntry"].toUpperCase())>=0) return true;
    return false;
  }

  
  return true;  
};

AuthView.prototype.renderEntryDetail= function(pos_id) {
  var t=this;
  var rows=new Array();
  rows.push('<div class="entrydetail" id="entrydetail_'+pos_id+'">');  
  
  rows.push('<div class="well">');
    rows.push('<legend>Berechtigung</legend>');

    rows.push('<div id="tree"></div>');  
    rows.push('<br> <p>'+form_renderButton({label:"&Auml;nderungen speichern", disabled:true, htmlclass:"save"})+"&nbsp;");
    rows.push(form_renderButton({label:"Urspr&uuml;nglichen Zustand wiederherstellen", disabled:true, htmlclass:"undo"}));    
  rows.push('</div>');  
  
 
  var elem=$("tr[id=" + pos_id + "]").after("<tr id=\"detail" + pos_id + "\"><td colspan=\"7\" id=\"detailTD" + pos_id + "\">"+rows.join("")+"</td></tr>").next();
  
  children=new Array();
  
  var children=new Array();
  $.each(masterData.modules, function(k,modulename) {
    var child=new Array();
    var expand=false;
    $.each(churchcore_sortMasterData(masterData.auth_table), function(i,auth) {
      if (auth.modulename==modulename) {
        if (auth.datenfeld!=null) {
          var expand_datenfeld=new Object();
          var child_daten=new Array();
          if (masterData[masterData.auth_table[auth.id].datenfeld]!=null) {
            // Bietet noch die Möglichkeiten von Untergruppen, wie z.B. bei Kalender mit privaten, Gruppen und Öffentlich
            var sub_child_daten=new Object();        
  
            $.each(churchcore_sortMasterData(masterData[masterData.auth_table[auth.id].datenfeld]), function(h, datenfeld) {
  
              var select=false;
              if ((masterData[t.currentDomain][pos_id]!=null) && (masterData[t.currentDomain][pos_id].auth!=null) 
                   && (masterData[t.currentDomain][pos_id].auth[auth.id]!=null) && 
                     // -1 means ALL! It comes from the authoriziation by ChurchDB
                      ((masterData[t.currentDomain][pos_id].auth[auth.id][-1]!=null)
                       || (masterData[t.currentDomain][pos_id].auth[auth.id][datenfeld.id]!=null))) {
                select=true;
                expand=true;
                expand_datenfeld["general"]=true;
              }
              if (masterData.auth_table[auth.id].datenfeld=="cc_calcategory") {
                var type="Gemeindekalender";
                if (masterData.cc_calcategory[datenfeld.id].privat_yn==1) 
                  type="Persönlich";
                else if (masterData.cc_calcategory[datenfeld.id].oeffentlich_yn==0)
                  type="Gruppenkalender";
                if (sub_child_daten[type]==null)
                  sub_child_daten[type]=new Array();
                sub_child_daten[type].push({title:datenfeld.bezeichnung, select:select, key:auth.id+"_"+datenfeld.id});
              }
              else {
                child_daten.push({title:datenfeld.bezeichnung, select:select, key:auth.id+"_"+datenfeld.id});
              }
            });
            // Wenn es Sub-Kategorien gibt, muss ich die hier noch einfügen.
            $.each(sub_child_daten, function(k,a) {
              child_daten.push({title:k, isFolder:true, children:a});
            });
          }
          child.push({title:auth.bezeichnung+" ("+auth.auth+")", isFolder:true, children:child_daten, expand:!churchcore_isObjectEmpty(expand_datenfeld)});             
        }
        else {
          select=false;
          if ((masterData[t.currentDomain]!=null) && (masterData[t.currentDomain][pos_id]!=null) && (masterData[t.currentDomain][pos_id].auth!=null) 
              && (masterData[t.currentDomain][pos_id].auth[auth.id]!=null)) {
            select=true;
            expand=true;
          }
          child.push({title:auth.bezeichnung+" ("+auth.auth+")", select:select, key:auth.id});              
        }
      }
    });
    children.push({title:(masterData.names[modulename]!=null?masterData.names[modulename]:modulename), isFolder:true, expand:expand, children:child});
  });
  
  $("#tree").dynatree({
    onActivate: function(node) {
        // A DynaTreeNode object is passed to the activation handler
        // Note: we also get this event, if persistence is on, and the page is reloaded.
        //alert("You activated " + node.data.title);
    },
    checkbox: true,
    selectMode: 3,
    onSelect: function(select, node) {
      elem.find("input").removeAttr("disabled");
    },
    onDblClick: function(node, event) {
      node.toggleSelect();
    },
    fx: { height: "toggle", duration: 200 },
    children:children
  });
  elem.find("input.save").click(function() {
    elem.find("input").attr("disabled",true);
    var selNodes=$("#tree").dynatree("getTree").getSelectedNodes(false);
    var data = $.map(selNodes, function(node){
      if (node.data.key.indexOf('_')==0) return null;
      else if (node.data.key.indexOf('_')>0) return {auth_id:node.data.key.substr(0,node.data.key.indexOf("_")), daten_id:node.data.key.substr(node.data.key.indexOf("_")+1,99)};
      else return {auth_id:node.data.key}; 
    });
    churchInterface.jsendWrite({func:"saveAuth", domain_type:t.currentDomain, domain_id:pos_id, data:data}, function(ok, data) {
      if (!ok) {
        alert("Fehler: "+data);
        elem.remove();
      }
      else {
        loadMasterData(function() {
          masterData.person[pos_id].open=true;
          t.renderList();
        });
      }
    },true, false);
  });
  elem.find("input.undo").click(function() {
    elem.remove();
    t.renderEntryDetail(pos_id);
  });
};

AuthView.prototype.getListHeader = function() {
  return '<th>Nr.<th width=200px>Bezeichnung<th>Rechte';
  
};

AuthView.prototype.addFurtherListCallbacks  = function() {
  var t=this;
  $("#cdb_content #simulate").click(function() {
    window.location.href="?q=simulate&id="+$(this).parents("tr").attr("id")+"&location=home&back=churchauth";
  });
  $("#searchEntry").keyup(function() {
    if ($(this).val()!="") {
      $("#searchAuth").removeAttr("checked");
      delete t.filter["searchAuth"];
    }
  });
};


AuthView.prototype.renderListEntry = function(a) {
  var rows_module=new Array();
  if (a.auth!=null) {
    var modules=new Object();
    $.each(a.auth, function(auth_id,daten) {
      if (masterData.auth_table[auth_id]==null) {
        log('No Auth in masterData.auth_table for AuthId:'+auth_id);
      }
      else {
        var txt=masterData.auth_table[auth_id].auth;    
        if (typeof daten=="object") {
          var rows=new Array();
          $.each(daten, function(i, d) {
            if (d==-1) rows.push("<i>alle</i>");
            else if ((masterData[masterData.auth_table[auth_id].datenfeld]==null))
              rows.push('<font color="red">'+masterData.auth_table[auth_id].datenfeld+" nicht vorhanden!</font>");
            else if (masterData[masterData.auth_table[auth_id].datenfeld][d]==null)
              rows.push('<font color="red">'+masterData.auth_table[auth_id].datenfeld+" mit Id:"+d+" nicht vorhanden!</font>");
            else
              rows.push(masterData[masterData.auth_table[auth_id].datenfeld][d].bezeichnung);
          });
          txt=txt+" ("+rows.join(", ")+")";
        }
        if (modules[masterData.auth_table[auth_id].modulename]==null)
          modules[masterData.auth_table[auth_id].modulename]=new Array();
        modules[masterData.auth_table[auth_id].modulename].push(txt);
      }
    });
    $.each(modules, function(k,module) {
      var rows_zeile=new Array();
      $.each(module, function(i,b) {
        rows_zeile.push(b);
      });
      rows_module.push("<b>"+k+": </b>"+rows_zeile.join(", ").trim(500));
    });
  }
  
  var rows=new Array();
  rows.push('<td class="hoveractor"><a href="#" id="detail'+a.id+'">'+a.bezeichnung+'</a>');
  if ((t.currentDomain=="person") && (churchcore_inArray(a.id,masterData.admins))) 
    rows.push('&nbsp; <span class="label label-important">Administrator</span>');
  else {  
    if ((t.currentDomain=="person") && (a.id>0)) {
      rows.push('&nbsp; <span class="hoverreactor" data-id="'+a.id+'" style="display:none">'+form_renderImage({src:"person_simulate.png", width:18, cssid:"simulate"})+"</span>");
    }
  }
  rows.push('<td>'+rows_module.join("<br>"));
  
  return rows.join("");
};



function loadMasterData(func) {
  churchInterface.jsendRead({func:"getMasterData"}, function(ok,data) {
    if (!ok) alert("Fehler: "+data);
    else {
      masterData=data;
      if (func!=null) func();
    }
  });  
}

AuthView.prototype.renderMenu = function() {
  var t=this;

  menu = new CC_Menu("Men&uuml;");
    menu.addEntry("Hilfe", "ahelp", "question-sign");

  if (!menu.renderDiv("cdb_menu", churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {      
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        t.renderAddEntry();
      }
      else if ($(this).attr("id")=="aexporter") {
        t.exportData(); 
      }

    });   
  }
};


AuthView.prototype.renderFilter = function() {
  var t = this;
  
  var form = new CC_Form();
  form.setHelp("ChurchAuth-Filter");
  //form.setLabel();
  
  var data=new Array();
  var modulename="";
  $.each(churchcore_sortData(masterData.auth_table, "modulename"), function(k,a) {
    if (modulename!=a.modulename) {
      modulename=a.modulename;
      data.push({id:-1, bezeichnung:"-- "+a.modulename+' --'});
    }
    data.push({id:a.id, bezeichnung:a.auth+" - "+a.bezeichnung.trim(50)});
  });
  
  form.addSelect({cssid:"filterAuth",label:"Autorisierung", sort:false, freeoption:true, selected:t.filter["filterAuth"], data:data});
  form.addCheckbox({cssid:"searchAuth",label:"nur autorisierte zeigen", checked:true});
  this.filter["searchAuth"]=true;
  
  $("#cdb_filter").html(form.render(true, "inline"));
  
  if (this.filter["filterStatus"]!=null) {
    if (typeof(this.filter["filterStatus"])=="string")
      t.makeFilterStatus(masterData.settings.filterStatus);
      
    this.filter["filterStatus"].render2Div("filterStatus", {label:"Status"});
  }

  
  
  // Setze die Werte auf die aktuellen Filter
  $.each(this.filter, function(k,a) {
    $("#"+k).val(a);
  });

   
  // Callbacks 
  this.implantStandardFilterCallbacks(this, "cdb_filter");

  var t=this;

  
  
  $("#cdb_filter a").click(function(c) {

  });
};


AuthView.prototype.renderListMenu = function() {
  var t=this;
  
  // Men�leiste oberhalb
  if ($("searchEntry").val()!=null) 
    searchEntry=$("searchEntry").val();
  else
    searchEntry=this.getFilter("searchEntry");

  var navi = new CC_Navi();
  navi.addEntry(t.currentDomain=="person","apersonview","Personen");
  navi.addEntry(t.currentDomain=="gruppe","agroupview","Gruppen");
  navi.addEntry(t.currentDomain=="status","astatusview","Status");
  navi.addSearch(searchEntry);
  navi.renderDiv("cdb_search", churchcore_handyformat());
  if (!churchcore_handyformat()) $("#searchEntry").focus();
  
  this.implantStandardFilterCallbacks(this, "cdb_search");    
  
  $("#cdb_search a").click(function () {
    if ($(this).attr("id")=="apersonview") {
      t.currentDomain="person";
      t.renderView();
    }
    else if ($(this).attr("id")=="agroupview") {
      t.currentDomain="gruppe";
      t.renderView();
    }
    else if ($(this).attr("id")=="astatusview") {
      t.currentDomain="status";
      t.renderView();
    }
    return false;
  });  
};



$(document).ready(function() {
  churchInterface.registerView("AuthView", authView);
  churchInterface.setModulename("churchauth");
  
  // Lade alle Kennzeichentabellen
  loadMasterData(function() {
    churchInterface.activateHistory("AuthView");
    churchInterface.sendMessageToAllViews("allDataLoaded");
  });
  
});