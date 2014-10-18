 
// Constructor
function AuthView() {
  StandardTableView.call(this);
  this.name="AuthView";
  this.allDataLoaded=false;
  this.renderTimer=null;
  this.currentDomain="person";
  this.clipboard=null;
}

Temp.prototype = StandardTableView.prototype;
AuthView.prototype = new Temp();
authView = new AuthView();


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
  
  if (this.filter["searchEntry"]!=null && a.bezeichnung) {
    if (a.bezeichnung.toUpperCase().indexOf(this.filter["searchEntry"].toUpperCase())>=0) return true;
    return false;
  }
  
  return true;  
};


// Rendering the permision table
$.widget("ct.permissioner", {
  options: {
    domain_id:null,
    domain_type:null,
    change:function() {},
    saveSuccess:function() {}
  },
  
  refresh:function() {this._create()},
  
  _create:function() {
    var t=this;
    var children=new Array();
    each(masterData.modules, function(k,modulename) {
      var child=new Array();
      var expand=false;
      each(churchcore_sortMasterData(masterData.auth_table_plain), function(i,auth) {
        if (auth.modulename==modulename) {
          if (auth.datenfeld!=null) {
            var expand_datenfeld=new Object();
            var child_daten=new Array();
            if (masterData[masterData.auth_table_plain[auth.id].datenfeld]!=null) {
              // Offers possiblity of subgroups, e.g. calendar for the church and for groups
              var sub_child_daten=new Object();  
              
              child_daten.push({title:"-- "+_("all")+" --", select:(masterData[t.options.domain_type][t.options.domain_id].auth!=null && masterData[t.options.domain_type][t.options.domain_id].auth[auth.id]!=null && masterData[t.options.domain_type][t.options.domain_id].auth[auth.id][-1]!=null), key:auth.id+"_-1"});
    
              each(churchcore_sortMasterData(masterData[masterData.auth_table_plain[auth.id].datenfeld]), function(h, datenfeld) {
                var select=false;
                if ((masterData[t.options.domain_type][t.options.domain_id]!=null) && (masterData[t.options.domain_type][t.options.domain_id].auth!=null) 
                     && (masterData[t.options.domain_type][t.options.domain_id].auth[auth.id]!=null) && 
                       // -1 means ALL! It comes from the authoriziation by ChurchDB
                        ((masterData[t.options.domain_type][t.options.domain_id].auth[auth.id][-1]!=null)
                         || (masterData[t.options.domain_type][t.options.domain_id].auth[auth.id][datenfeld.id]!=null))) {
                  select=true;
                  expand=true;
                  expand_datenfeld["general"]=true;
                }
                if (masterData.auth_table_plain[auth.id].datenfeld=="cc_calcategory") {
                  var type=masterData.publiccalendar_name;
                  if (masterData.cc_calcategory[datenfeld.id].oeffentlich_yn==0 && 
                      masterData.cc_calcategory[datenfeld.id].privat_yn==0)
                    type=_("group.calendar");
                  else if (masterData.cc_calcategory[datenfeld.id].oeffentlich_yn==0 && 
                      masterData.cc_calcategory[datenfeld.id].privat_yn==1)
                    type=_("personal.calendar");
                  if (sub_child_daten[type]==null)
                    sub_child_daten[type]=new Array();
                  sub_child_daten[type].push({title:datenfeld.bezeichnung, select:select, key:auth.id+"_"+datenfeld.id});
                }
                else {
                  child_daten.push({title:datenfeld.bezeichnung, select:select, key:auth.id+"_"+datenfeld.id});
                }
              });
              // Wenn es Sub-Kategorien gibt, muss ich die hier noch einfügen.
              each(sub_child_daten, function(k,a) {
                child_daten.push({title:k, isFolder:true, children:a});
              });
            }
            child.push({title:auth.bezeichnung+" ("+auth.auth+")", isFolder:true, children:child_daten, expand:!churchcore_isObjectEmpty(expand_datenfeld)});             
          }
          else {
            select=false;
            if ((masterData[t.options.domain_type]!=null) && (masterData[t.options.domain_type][t.options.domain_id]!=null) && (masterData[t.options.domain_type][t.options.domain_id].auth!=null) 
                && (masterData[t.options.domain_type][t.options.domain_id].auth[auth.id]!=null)) {
              select=true;
              expand=true;
            }
            child.push({title:auth.bezeichnung+" ("+auth.auth+")", select:select, key:auth.id});              
          }
        }
      });
      children.push({title:(masterData.names[modulename]!=null?masterData.names[modulename]:modulename), isFolder:true, expand:expand, children:child});
    });
    
    var ImadeTheChange=false;
    t.element.dynatree({
      onActivate: function(node) {
          // A DynaTreeNode object is passed to the activation handler
          // Note: we also get this event, if persistence is on, and the page is reloaded.
          //alert("You activated " + node.data.title);
      },
      checkbox: true,
      selectMode: 3,
      onSelect: function(select, node) {
        // If "-- Alle --" was selected then I have to enable all!"
        if (node.data.key.indexOf("_-1")>0) {
          each(node.parent.childList, function(k,c) {
            if (c.data.key!=node.data.key && !ImadeTheChange) {
              c.select(node.bSelected);
            }
          });
        }
        // If not "-- Alle --" was selected look, if there is a ALLE to deselect
        else if (!node.bSelected && node.parent.childList[0].data.key.indexOf("_-1")>0) {
          ImadeTheChange=true;
          node.parent.childList[0].select(false);
          ImadeTheChange=false;        
        }
        t.options.change();
      },
      onDblClick: function(node, event) {
        node.toggleSelect();
      },
      fx: { height: "toggle", duration: 200 },
      children:children
    });
  },
  
  save:function() {
    var t=this;
    var selNodes=$("#tree").dynatree("getTree").getSelectedNodes(false);
    var data = $.map(selNodes, function(node){
      // Only a parent
      if (node.data.key.indexOf('_')==0) 
        return null;
        // with auth ids
      else if (node.data.key.indexOf('_')>0) {
        if (node.data.key.indexOf('_-1')>0) {
          return {auth_id:node.data.key.substr(0,node.data.key.indexOf("_")), 
                 daten_id:-1};
        }
        // if - Alle - is available and not selected, otherwise we can ignore it.
        else if (node.parent.childList[0].data.key.indexOf('-1')==-1 
                     || node.parent.childList[0].bSelected==false)  
          return {auth_id:node.data.key.substr(0,node.data.key.indexOf("_")), 
                daten_id:node.data.key.substr(node.data.key.indexOf("_")+1,99)};
        else {
          return null;          
        }
      }
      else 
        // only child without ids
        return {auth_id:node.data.key}; 
    });
    churchInterface.jsendWrite({func:"saveAuth", domain_type:t.options.domain_type, domain_id:t.options.domain_id, data:data}, function(ok, data) {
      if (!ok) {
        alert(_("error.occured")+": "+data);
        elem.remove();
      }
      else {
        loadAuthViewMasterData(function() {
          t.options.saveSuccess();
        });
      }
    },true, false, "churchauth");    
  }
});


AuthView.prototype.renderEntryDetail= function(domain_id) {
  var t=this;
  var rows=new Array();
  rows.push('<div class="entrydetail" id="entrydetail_'+domain_id+'">');  
  
  rows.push('<div class="well">');
    rows.push('<legend>'+_("permissions")+'</legend>');

    rows.push('<div id="tree"></div>');  
    rows.push('<br> <p>'+form_renderButton({label:_("save.changes"), disabled:true, htmlclass:"save"})+"&nbsp;");
    rows.push(form_renderButton({label:_("undo.changes"), disabled:true, htmlclass:"undo"})+"&nbsp; &nbsp;");
    rows.push(form_renderButton({label:_("copy.permissions"), disabled:(t.clipboard!=null && t.clipboard.id==domain_id), htmlclass:"copy"})+"&nbsp;");
    if (t.clipboard!=null && t.clipboard.id!=domain_id)
      rows.push(form_renderButton({label:_("paste.permissions"), disabled:false, htmlclass:"paste"}));    
  rows.push('</div>');  
  
 
  var elem=$("tr[id=" + domain_id + "]").after("<tr id=\"detail" + domain_id + "\"><td colspan=\"7\" id=\"detailTD" + domain_id + "\">"+rows.join("")+"</td></tr>").next();
  
  var perm=$("#tree");
  
  perm.permissioner({
    domain_type:t.currentDomain,
    domain_id:domain_id,
    change:function() {
      elem.find("input").removeAttr("disabled");
    },
    saveSuccess:function() {
      masterData.person[domain_id].open=true;
      t.renderList();
    }
  });
  
  elem.find("input.save").click(function() {
    elem.find("input").attr("disabled",true);
    perm.permissioner("save");  
  });      
  
  elem.find("input.undo").click(function() {
    elem.remove();
    t.renderEntryDetail(domain_id);
  });
  elem.find("input.copy").click(function() {
    if (elem.find("input.save").attr("disabled")!="disabled") alert(_("please.first.save.or.undo.current.changes"));
    else {
      t.clipboard=$.extend(true, {}, masterData[t.currentDomain][domain_id]);
      t.renderList();      
    }
  });
  elem.find("input.paste").click(function() {
    if (confirm(_("really.past.permissions"))) {
      each(t.clipboard.auth, function(k,a) {
        masterData[t.currentDomain][domain_id].auth[k]=a;
      });
      masterData[t.currentDomain][domain_id].saveable=true;
      t.renderList(masterData[t.currentDomain][domain_id]);
      perm.permissioner("save");
    }
  });
  elem.find("input.undo").click(function() {
    t.renderList(domain_id);
  });
};

AuthView.prototype.getListHeader = function() {
  return '<th>Nr.<th width=200px>'+_("caption")+'<th>'+_("permissions");
  
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
    each(a.auth, function(auth_id,daten) {
      if (masterData.auth_table_plain[auth_id]==null) {
        log('No Auth in masterData.auth_table_plain for AuthId:'+auth_id);
      }
      else {
        var txt=masterData.auth_table_plain[auth_id].auth;    
        if (typeof daten=="object") {
          var rows=new Array();
          each(daten, function(i, d) {
            if (d==-1) rows.push("<i>alle</i>");
            else if ((masterData[masterData.auth_table_plain[auth_id].datenfeld]==null))
              rows.push('<font color="red">'+masterData.auth_table_plain[auth_id].datenfeld+" not available!</font>");
            else if (masterData[masterData.auth_table_plain[auth_id].datenfeld][d]==null)
              rows.push('<font color="red">'+masterData.auth_table_plain[auth_id].datenfeld+" with Id:"+d+" not available!</font>");
            else
              rows.push(masterData[masterData.auth_table_plain[auth_id].datenfeld][d].bezeichnung);
          });
          txt=txt+" ("+rows.join(", ")+")";
        }
        if (modules[masterData.auth_table_plain[auth_id].modulename]==null)
          modules[masterData.auth_table_plain[auth_id].modulename]=new Array();
        modules[masterData.auth_table_plain[auth_id].modulename].push(txt);
      }
    });
    each(modules, function(k,module) {
      var rows_zeile=new Array();
      each(module, function(i,b) {
        rows_zeile.push(b);
      });
      rows_module.push("<b>"+k+": </b>"+rows_zeile.join(", ").trim(500));
    });
  }
  
  var rows=new Array();
  rows.push('<td class="hoveractor"><a href="#" id="detail'+a.id+'">'+a.bezeichnung+'</a>');
  if ((t.currentDomain=="person") && (churchcore_inArray(a.id,masterData.admins))) 
    rows.push('&nbsp; <span class="label label-important">'+_("administrator")+'</span>');
  else {  
    if ((t.currentDomain=="person") && (a.id>0)) {
      rows.push('&nbsp; <span class="hoverreactor" data-id="'+a.id+'" style="display:none">'+form_renderImage({src:"person_simulate.png", width:18, cssid:"simulate"})+"</span>");
    }
  }
  rows.push('<td>'+rows_module.join("<br>"));
  
  return rows.join("");
};



function loadAuthViewMasterData(func) {
  churchInterface.jsendRead({func:"getMasterData"}, function(ok,data) {
    if (!ok) alert("Fehler: "+data);
    else {
      each(data, function(k,a) {
        masterData[k]=a;
      });
      if (func!=null) func();
    }
  }, null, null, "churchauth");  
}

AuthView.prototype.renderMenu = function() {
  var t=this;

  menu = new CC_Menu(_("menu"));
    menu.addEntry(_("help"), "ahelp", "question-sign");

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
  each(churchcore_sortData(masterData.auth_table_plain, "modulename"), function(k,a) {
    if (modulename!=a.modulename) {
      modulename=a.modulename;
      data.push({id:-1, bezeichnung:"-- "+a.modulename+' --'});
    }
    data.push({id:a.id, bezeichnung:a.auth+" - "+a.bezeichnung.trim(50)});
  });
  
  form.addSelect({cssid:"filterAuth",label:_("permissions"), sort:false, freeoption:true, selected:t.filter["filterAuth"], data:data});
  form.addCheckbox({cssid:"searchAuth",label:_("only.show.user.with.permissions"), checked:true});
  this.filter["searchAuth"]=true;
  
  $("#cdb_filter").html(form.render(true, "inline"));
    
  // Set values of current filters
  each(this.filter, function(k,a) {
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
  
  if ($("searchEntry").val()!=null) 
    searchEntry=$("searchEntry").val();
  else
    searchEntry=this.getFilter("searchEntry");

  var navi = new CC_Navi();
  navi.addEntry(t.currentDomain=="person","apersonview",_("users"));
  navi.addEntry(t.currentDomain=="gruppe","agroupview",_("groups"));
  navi.addEntry(t.currentDomain=="status","astatusview",_("status"));
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
