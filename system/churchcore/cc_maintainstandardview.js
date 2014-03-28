	 
// Constructor
function MaintainStandardView() {
  StandardTableView.call(this);
  this.name="MaintainStandardView";
  this.sortKey="id";
  this.sortAsc=false;
}

Temp.prototype = StandardTableView.prototype;
MaintainStandardView.prototype = new Temp();
maintainStandardView = new MaintainStandardView();

MaintainStandardView.prototype.getData = function() {
  return masterData.masterDataTables;
};

MaintainStandardView.prototype.renderMenu = function() {
  alert("Please Overwrite and add Div divcover!");
};

(function($) {
  
  MaintainStandardView.prototype.renderListMenu = function() {
  searchEntry=this.getFilter("searchEntry");
  var navi = new CC_Navi();
  navi.addEntry(true,null,"Stammdatenpflege");
  navi.addSearch(searchEntry);
  navi.renderDiv("cdb_search");  
  // Callbacks 
  this.implantStandardFilterCallbacks(this, "cdb_search");
};  

MaintainStandardView.prototype.renderFilter = function() {
  var rows = new Array();

  //rows.push("<p class=\"filter-bar\">");      
  //rows.push("&nbsp;&nbsp;");
  
  var form = new CC_Form();
  form.setLabel("Filterfunktionen");
  form.addCheckbox({cssid:"searchChecked", label:"markierte"});  
  rows.push(form.render(true));
  
  $("#cdb_filter").html(rows.join("")); 
  this.implantStandardFilterCallbacks(this, "cdb_filter");

};

MaintainStandardView.prototype.checkFilter = function(a) {
  // Eintrag wurde geloescht o.ae.
  if (a==null) return false;
  
  // Suchfeld
  searchEntry=this.getFilter("searchEntry").toUpperCase();
  if ((searchEntry!="") && (a.bezeichnung.toUpperCase().indexOf(searchEntry)<0) &&
             (a.id!=searchEntry)) return false;

  if ((this.filter["searchChecked"]!=null) && (a.checked!=true)) return false;

  return true;
};

MaintainStandardView.prototype.getListHeader = function() {
  str="<th>Nr.<th>Stammtabelle<th>Anzahl Zeilen";
  return str;
};

MaintainStandardView.prototype.renderListEntry = function(a) {
  rows=new Array();

  
  var name=a.bezeichnung;
  if (masterData.fields!=null)
  $.each(masterData.fields, function(k,m) {
    if (m.fields!=null) {
      $.each(m.fields, function(i,n) {
        if (n.selector==a.shortname) {
          name=n.text;
          return false;
        }
      });
    }
  });

  rows.push("<td>" + name);
  i=0;
  if (masterData[a.shortname]!=null)
    $.each(masterData[a.shortname], function(k,a) {
      i++;
    });
  rows.push("<td>" + i);
  
  return rows.join("");
};

MaintainStandardView.prototype.renderEntryDetail = function(pos_id, data_id) {
  var this_object=this;
  if (data_id==null) 
    data_id=pos_id;
  table=this.getData()[pos_id];
  
  var rows = new Array();  
  $("tr[id=detail" + pos_id + "]").remove();

  $("tr[id=" + pos_id + "]").after("<tr id=\"detail" + pos_id + "\"><td colspan=\"10\" id=\"groupinfosTD" + pos_id + "\">Lade Daten..</td></tr>");
  rows[rows.length]="<div id=\"detail\" class=\"detail-view-admin\">";
  
  // Schaue nach sinnvollen Filtern
  var row=new Array();
  $.each(table.desc, function(i,b) {
    if ((i.indexOf("_id")>0) && (i.indexOf("_ids")==-1) && (masterData[table.shortname]!=null)) {
      var t=i.substr(0,i.indexOf("_id"));
      row.push(form_renderSelect({fields: {"data-table-name":t}, 
        data:masterData[t], 
        type:"medium", 
        selected: (table.filter!=null && table.filter[t]!=null?table.filter[t]:""),
        freeoption:true,
        htmlclass:"filter",
        controlgroup:false}));      
    }
  });
  if (row.length>0)
  rows.push('<p><form class="form-inline">&nbsp;Filter: '+row.join("&nbsp;")+'</form>');
  
  
  
  rows[rows.length]="<p style='line-height:100%'><small><table><tr>";  
  
  // Tabellenheader
  $.each(table.desc, function(k,a) {
    rows.push('<td><b><i><a href="#" id="'+a.field+'">'+a.field);
    if (this_object.sortKey==a.field)
      if (this_object.sortAsc) rows.push(" &and;")
      else rows.push(" &or;");
    
    rows.push('</a></b></i>');
  });  
  if (table.special_func!=null) rows.push("<td><b><i>Funktionen</i></b>");
  rows.push("<td><b><i>del.</b></i><tr>");
  
  
  if (masterData[table.shortname]!=null) {
    var data=masterData[table.shortname];
    // Tabellen-Daten  
    $.each(churchcore_sortData(masterData[table.shortname],this.sortKey,this.sortAsc, (this.sortKey!="id") && (this.sortKey!="sortkey")), function(k,a) {
      var filter=false;
      var row=new Array();
      $.each(table.desc, function(i,b) {
        row.push("<td>");
        if ((b.type.indexOf("int")==0) && (i.indexOf("_yn")>0)) {
          row.push('<a href="#" id="change_yn_'+a.id+'" val="'+a[i]+'" col="'+i+'">');
          row.push(this_object.renderYesNo(a[i]));        
          row.push('</a>');
        }
        else if ((i.indexOf("_id")>0) && (i.indexOf("_ids")==-1) && (a[i]!=null) && (a[i]!="")) {
          var t=i.substr(0,i.indexOf("_id"));
          if ((table.filter!=null) && (table.filter[t]!=null) && (table.filter[t]!=a[i])) filter=true;
          var arr=new Array();
          if ((masterData[t]!=null) && (masterData[t][a[i]]!=null) && (masterData[t][a[i]].bezeichnung!=null)) {
            var fields = {table:table.tablename,shorttablename:table.shortname,row:a.id,col:i};
            arr.push(form_renderSelect({fields:fields, 
              htmlclass:"datafield",
              data:masterData[t], 
              selected:a[i], 
              type:"small", 
              freeoption:b.Null=="YES", 
              controlgroup:false}));
          }
          else
            arr.push(a[i]);
          row.push(arr.join(""));
        }
        else {
          row.push('<a href="#" id="edit_'+a.id+'">');
          row.push(a[i]);
          row.push('</a>');
        }
        
      });
      
      
      if (table.special_func!=null) {
        row.push('<td><a href="#" id="special_func_'+a.id+'" func="'+table.special_func.func+'">');
        if (table.special_func.image!=null)
          row.push(this_object.renderImage(table.special_func.image,16,table.special_func.name));
        else
          row.push(table.special_func.name);
        row.push("</a>");
      }
      
      row.push('<td>'+form_renderImage({
        label: "Datensatz l&ouml;schen",
        cssid:'delete_'+a.id, 
        src: masterData.modulespath+'/images/trashbox.png',
        htmlclass: "small"
      })+'</a>');
      row.push("<tr>");
      
      
      if (!filter) rows.push(row.join(""));
      
    });
  }

  rows[rows.length]="</table></small>";  
  rows[rows.length]="<p>"+form_renderImage({
    label: "Neuen Eintrag erstellen",
    cssid:'create', 
    src: masterData.modulespath+'/images/plus.png',
    htmlclass: "small"
  });
    rows[rows.length]="</div>";  
  
  $("#groupinfosTD"+pos_id).html(rows.join(""));


  $("#groupinfosTD"+pos_id+" a").click(function() {
    table=this_object.getData()[pos_id];
    if ($(this).attr("id").indexOf("grp_close")==0) {
      $("#groupinfosTD"+pos_id).remove();
      return false;
    }
    else if ($(this).attr("id").indexOf("edit_")==0) {   
      this_object.renderEditEntry($(this).attr("id").substr(5,99), table.id);
    }
    else if ($(this).attr("id").indexOf("special_func_")==0) {
      this_object[$(this).attr("func")].apply(this_object,[$(this).attr("id").substr(13,99), table.id])
    }
    else if ($(this).attr("id").indexOf("change_yn_")==0) {   
      obj=new Object();
      obj.func="saveMasterData";
      obj.table=table.tablename;
      obj.id=$(this).attr("id").substr(10,99);
      obj.col0=$(this).attr("col");
      obj.value0=($(this).attr("val")==0?1:0);
      churchInterface.jsendWrite(obj, function(ok, data) {
        if (!ok) 
          alert("Fehler beim Speichern: "+data);
        else {  
          masterData[table.shortname][obj.id][obj.col0]=obj.value0;
          masterData.masterDataTables[table.id].open=true;
          this_object.renderList(masterData.masterDataTables[table.id]);
        }
      });      
      return false;
    }
    else if ($(this).attr("id").indexOf("delete_")==0) {
      if (confirm("Wirklich den Datensatz "+$(this).attr("id").substr(7,99)+" entfernen? Bitte sicherstellen, dass keine Daten mehr auf diesen Datensatz referenziert, sonst kann es zu Problemen kommen!")) {
        churchInterface.jsendWrite({ func: "deleteMasterData", table:table.tablename, id:$(this).attr("id").substr(7,99) }, function(ok, data) {
          if (!ok) alert("Fehler beim Speichern: "+data);
          else {
            var id=table.id;
            var filter=table.filter;
            cdb_loadMasterData(function() {
              masterData.masterDataTables[id].open=true;
              masterData.masterDataTables[id].filter=filter;
              this_object.renderList(masterData.masterDataTables[id]);
            }); 
          }
        });      
      }
    }
    else if ($(this).attr("id")=="create") {         
      this_object.renderEditEntry(null, table.id);
    }
    else if (table.desc[$(this).attr("id")]!=null) {
      if (this_object.sortKey==$(this).attr("id"))
        this_object.sortAsc=!this_object.sortAsc;
      this_object.sortKey=$(this).attr("id");
      this_object.renderList(masterData.masterDataTables[table.id]);
    }
    return false;
  });
  $("#cdb_content select.filter").change(function(c) {
    if (table.filter==null) table.filter= new Object();
    if ($(this).val()!="")
      table.filter[$(this).attr("data-table-name")]=$(this).val();
    else 
      delete table.filter[$(this).attr("data-table-name")];
    this_object.renderList(masterData.masterDataTables[table.id]);        
  });
  $("#cdb_content select.datafield").change(function(c) {
    obj.func="saveMasterData";
    obj.table=$(this).attr("table");
    obj.id=$(this).attr("row");
    obj.col0=$(this).attr("col");
    obj.value0=$(this).val();
    
    obj.shorttablename=$(this).attr("shorttablename");
    churchInterface.jsendWrite(obj, function(ok, data) {
      if (!ok) alert("Fehler beim Speichern: "+data);
      else {
        masterData[obj.shorttablename][obj.id][obj.col0]=obj.value0;
      }
    });      
    return false;
  });

};

MaintainStandardView.prototype.renderEditEntry = function (id, table_id) {
  var this_object=this;
  // Hole die Beschreibung dieser Stammdatentabelle in table
  var table=masterData.masterDataTables[table_id];
  // Hole das konkrete Array, wenn id=null, dann handelt es sich um einen neuen Datensatz
  var entry=new Object();
  if (id!=null)
    entry=masterData[table.shortname][id];
  
  var form = new CC_Form(null, null, "in_edit");
  $.each(table.desc, function(k,a) {
    if (a.field=="id") {
      if (entry[a.field]!=null)
        form.addCaption({text:entry[a.field], label:"id"});
    }
    // checkbox
    else if ((a.type.indexOf("int")==0) && (a.field.indexOf("_yn")>0)) {
      form.addCheckbox({label:a.field, cssid:"Input"+a.field, checked:entry[a.field]==1});
    }
    // select
    else if ((a.field.indexOf("_id")>0) && (a.field.indexOf("_ids")==-1) 
           && (masterData[a.field.substr(0,a.field.length-3)]!=null)) {
      // Wenn es einen gesetzen Filter gibt, setze ich den Standard wert darauf!
      if ((entry[a.field]==null) && (table.filter!=null) && (table.filter[a.field.substr(0,a.field.length-3)]!=null))
        entry[a.field]=table.filter[a.field.substr(0,a.field.length-3)];    
      form.addSelect({
        label: a.field, cssid:"Input"+a.field, selected:entry[a.field], data:masterData[a.field.substr(0,a.field.length-3)]   
      });
    }
    else {
      var value="-";
      if ((entry[a.field]==null) && (a.type=="int(11)") && (a["Null"]=="YES"))
        value="null"
      else
        value=entry[a.field];
      var size_arr=a.type.match(/\((.*)\)/);
      var size=null;
      if (size_arr!=null)
        size=size_arr[1];
      if ((a.type=="blob") || ((size!=null) && (size>100))) {
        form.addTextarea({
          label: a.field, cssid:"Input"+a.field, data:value, rows:5, htmlclass:(a["Null"]=="YES"?"nullable":"")   
        });
      }
      else {      
        if ((a.field=="sortkey") && (value==null)) value="0";
        form.addInput({
          label: a.field, cssid:"Input"+a.field, value:value, htmlclass:(a["Null"]=="YES"?"nullable":"")   
        });
      }
    }    
  });
 
  var elem = this.showDialog("Veränderung des Datensatzes "+table.bezeichnung, form.render(null, "horizontal"), 500, 450, {
      "Speichern": function() {
        var s = $(this).attr("id");
        
        obj=new Object();
        obj.func="saveMasterData";
        obj.table=table.tablename;
        obj.id=id;
        k=0;
        $("#in_edit input, #in_edit select,  #in_edit textarea").each(function (i) {
          obj["col"+k]=$(this).attr("id").substr(5,99);
          if (($(this).val()=="") && ($(this).hasClass("nullable")))
            obj["value"+k]=null;
          else if ($(this).attr("type")=="checkbox") {
            if ($(this).attr("checked")=="checked")
              obj["value"+k]=1;
               else  
              obj["value"+k]=0;            
          }
          else
            obj["value"+k]=$(this).val();
    
          k++;
        });
          
        $("#cbn_editor").html("<p><br/><b>Daten werden gespeichert...</b><br/><br/>");
        churchInterface.jsendWrite(obj, function(ok, data) {
          elem.dialog("close");
          if (!ok) alert("Fehler beim Speichern: "+data);
          else {
            var filter=masterData.masterDataTables[table_id].filter;
            cdb_loadMasterData(function() {
              if (masterData.masterDataTables[table_id]!=null) {
                masterData.masterDataTables[table_id].filter=filter;
                masterData.masterDataTables[table_id].open=true;
              }
              this_object.renderList();
            }); 
          }
        });      
      },
      "Abbruch": function() {
        $(this).dialog("close");
      }
  });
};

})(jQuery);
