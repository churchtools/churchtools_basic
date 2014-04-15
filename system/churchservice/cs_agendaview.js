(function($) {

// Constructor
function AgendaView(options) {
  ListView.call(this, options);
  this.name="AgendaView";
  this.currentAgenda=null;
  this.allDataLoaded=false;
  this.templatesLoaded=false;
}

Temp.prototype = ListView.prototype;
AgendaView.prototype = new Temp();
agendaView = new AgendaView({showPaging:false, rowNumbering:false});

allAgendas=null;

AgendaView.prototype.checkFilter = function(item) {
  return true;
};


AgendaView.prototype.getData = function(sorted, withHeader) {
  if (allAgendas==null || this.currentAgenda==null) return null;
  if (withHeader==null) withHeader=false;
  if (sorted) {
    var arr=new Array();
    if (this.currentAgenda.items!=null)
    $.each(churchcore_sortData(this.currentAgenda.items, "sortkey", null, false), function(k,a) {
      arr.push(a);        
    });
    return arr;
  }
  else
    return this.currentAgenda.items;
};

AgendaView.prototype.addMoreCols = function() {
  var rows = new Array();
  var t=this;
  rows.push("<legend>Auswahl der Servicegruppen</legend>");
  $.each(churchcore_sortData(masterData.servicegroup,"sortkey"), function(k,a) {
    if (masterData.auth.viewgroup[a.id]!=null) {
      rows.push(form_renderCheckbox({
        cssid:"viewgroup_agenda"+a.id, label:a.bezeichnung, controlgroup:false,
        checked: (masterData.settings["viewgroup_agenda"+a.id]==null) || (masterData.settings["viewgroup_agenda"+a.id]==1)
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



AgendaView.prototype.groupingFunction = function(event) {
  return null;
  //return event.header;
};

AgendaView.prototype.renderMenu = function() {
  var t=this;

  menu = new CC_Menu("Men&uuml;");

//  if (masterData.auth.write)
//    menu.addEntry("Neues Event anlegen", "anewentry", "star");
//  menu.addEntry("Fakten exportieren", "aexport", "share");
  if (user_access("edit agenda templates"))
    menu.addEntry("Neue Vorlage erstellen", "anewtemplate", "star");
  menu.addEntry("Druckansicht", "aprintview", "print");
  menu.addEntry("Exportieren zu Songbeamer", "aexportsb", "share");
  menu.addEntry("Hilfe", "ahelp", "question-sign");

  if (!menu.renderDiv("cdb_menu",churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {
      $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        t.renderAddEntry();
      }
      else if ($(this).attr("id")=="anewtemplate") {
        t.editAgenda(null, true);
      }
      else if ($(this).attr("id")=="aexportsb") {
        t.exportCurrentAgendaToSongBeamer();
      }
      else if ($(this).attr("id")=="aprintview") {
        fenster = window.open('?q=churchservice/printview&id='+t.currentAgenda.id+'#AgendaView', "Druckansicht", "width=900,height=600,resizable=yes");
        fenster.focus();
        return false;
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchService");
      }
      return false;
    });
  }
};

AgendaView.prototype.exportCurrentAgendaToSongBeamer = function () {
  var rows=new Array();
  rows.push("object AblaufPlanItems: TAblaufPlanItems");
  rows.push("\r\n  items = <");
  $.each(t.getData(true), function(i,a) {
    var song=null;
    if (a.arrangement_id>0) 
      song=songView.getSongFromArrangement(a.arrangement_id);
    
    if (a.header_yn==1 || a.arrangement_id>0) {
      rows.push("\r\n    item");
      var bez=a.bezeichnung;
      if (song!=null) {
        if (bez!="") bez=bez+" - ";
        bez=bez+song.bezeichnung;              
      }
      rows.push("\r\n      Caption = '"+bez+"'");
      if (a.header_yn==0)
        rows.push("\r\n      Color = clBlue");
      else
        rows.push("\r\n      Color = 4227327");
      if (song!=null) {
        if (masterData.songwithcategoryasdir==1)
          rows.push("\r\n      FileName = '"+masterData.songcategory[song.songcategory_id].bezeichnung+"\\"+song.bezeichnung+".sng'");
        else
          rows.push("\r\n      FileName = '"+song.bezeichnung+".sng'");        
      }
      rows.push("\r\n    end");
    }
  });
  rows.push(">\r\nend\r\n");
  
  var uri = 'data:text/col;charset=utf-8,' + escape(rows.join(""));

  var downloadLink = document.createElement("a");
  downloadLink.href = uri;
  downloadLink.download = "ablaufplan.col";

  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
 
};

AgendaView.prototype.editAgenda = function(agenda, template) {
  var t=this;
  var form = new CC_Form((template?"Vorlage für Ablaufpläne editieren":"Ablaufplan editieren"), agenda);
  form.addInput({label:"Bezeichnung", cssid:"bezeichnung"});
  if (!template)
    form.addCheckbox({label:"Ablauf ist endg&uuml;ltig", cssid:"final_yn"});
  form.addInput({label:"Predigtserie", cssid:"series"});
  form.addSelect({label:"Für Kalender", data:masterData.category, cssid:"calcategory_id"})
  if (agenda!=null && template) {
    form.addCheckbox({label:"Als Kopie speichern", cssid:"copy"});        
  }
  else if (agenda!=null && !template) {
    form.addCheckbox({label:"Kopie als Template speichern", cssid:"copy_as_template"});
  }
    
  var elem = form_showDialog((agenda==null?"Neuen Ablaufplan erstellen":"Ablaufplan editieren"), form.render(false, "horizontal"), 500, 400, {
    "Speichern": function() {
      var obj=form.getAllValsAsObject(true);
      // If copy or copy as template I have to delete ids, so it will be copied!
      if (obj.copy==1 || obj.copy_as_template==1) {
        obj.items=$.extend(true, {}, agenda.items);
        $.each(obj.items, function(k,i) {
          delete i.id;
          delete i.event_ids;
        });
      }
      else if (agenda!=null && agenda.id!=null) obj.id=agenda.id;
      if (template || obj.copy_as_template==1) 
        obj.template_yn=1; 
      else obj.template_yn=0;
      if ((agenda==null) && (obj.items==null)) {
        obj.items=new Object();
        obj.items[-1]=t.getNewItem();
      }
      t.saveAgenda(obj, function(data) {
        elem.dialog("close");
        if (allAgendas==null) allAgendas=new Object();
        allAgendas[data.id]=data;
        t.currentAgenda=data;
        t.renderView();        
      });
    },
    "Abbruch":function() {
      elem.dialog("close");
    }
  });  
};

/**
 * Creates new item and overwrite the standard options with options
 * @param options
 * @returns new item
 */
AgendaView.prototype.getNewItem = function(options) {
  var o={bezeichnung:"Neue Position", note:"", sortkey:0, duration:"0", preservice_yn:0, 
      header_yn:0, responsible:""};
  if (options!=null)
    $.each(options, function(k,a) {
      o[k]=a;
    });
  return o;
};

AgendaView.prototype.deleteAgenda = function(agenda) {
  var t=this;
  if (confirm("Wirklich den aktuellen Ablaufplan mit allen seinen Positionen löschen?")) {
    if (agenda.event_ids!=null) {
      $.each(agenda.event_ids, function(k,a) {
        if (allEvents[a]!=null)
          allEvents[a].agenda=false;
      });
    }
    churchInterface.jsendWrite({func:"deleteAgenda", id:agenda.id}, function(ok, data) {
      if (!ok) alert("Fehler beim Löschen der Agenda: "+data);
      else {
        if (t.currentAgenda.id==agenda.id){
          t.currentAgenda=null;
        }
        delete allAgendas[agenda.id];       
        t.renderView();
      }
    });
  }
};

AgendaView.prototype.getCountCols = function() {
  return 10;
};

/**
 * Sort data array. Change the sortkey from origindex to the sortkey to new index
 */
function sortData(data, origindex, newindex) {
  var origitem=data[origindex];
  var newitem=data[newindex];    
  // Sortkey will be replaced.
  origitem.sortkey=newitem.sortkey;
  var sortkey=0;
  // Now renumber all other sortkeys
  $.each(data, function(k,a) {
    if (a.id!=origitem.id) {
      // If the sortkey is the same as the original, the original will keep his sortkey
      if (sortkey==origitem.sortkey) sortkey=sortkey+1;
      a.sortkey=sortkey;
      sortkey=sortkey+1;
    }
  });  
}

/**
 * Saves the agenda agenda. 
 */
AgendaView.prototype.saveAgenda = function(agenda, func) {
  var obj = $.extend({}, agenda);
  obj.func="saveAgenda";

  churchInterface.jsendWrite(obj, function(ok, data) {
    if (!ok) {
      alert("Fehler beim Speichern: "+data);
      agenda.items=null;
      t.renderList();
    }
    else {
      if (allAgendas==null) allAgendas=new Object();
      allAgendas[data.id]=data;
      if (func!=null) func(data);
    }
  }, null, false);
};

/**
 * Render the field dataField of object o
 * @param o
 * @param dataField
 * @returns string
 */
AgendaView.prototype.renderField = function(o, dataField, smallVersion) {
  var t=this;
  
  if (o==null) return "";
  
  var rows=new Array();
  
  if (dataField=="duration") {
    rows.push(o.duration.formatMS());
  }
  else if (dataField=="bezeichnung") {
    if (o.header_yn==1) {
      rows.push('<b>'+o.bezeichnung+'</b>');
      if (o.duration!=0) {
        rows.push('&nbsp; ca. ');
        if (o.duration % 60==0) rows.push(o.duration/60+"min");
        else rows.push(o.duration.formatMS());
      }
    }
    else {
      var song=null;
      var bezeichnung="<b>"+o.bezeichnung+"</b>";
      if (o.arrangement_id!=null) {
        var song=songView.getSongFromArrangement(o.arrangement_id);
        if (song!=null) {
          var s=song.bezeichnung;
          // Song als URL!!
          if (user_access("viewsong")) 
            s='<a href="#" class="view-song" data-song-id="'+song.id+'" data-arrangement-id="'+o.arrangement_id+'">'+s+'</a>';
            
          if (o.bezeichnung=="") bezeichnung="Song: <i>"+s+"</i>";
          else bezeichnung=bezeichnung+ "<i> - "+s+'</i>';
          arr=song.arrangement[o.arrangement_id];
          if (arr!=null && arr.tonality!="" || arr.bpm!="") {
            bezeichnung=bezeichnung+' <span class="pull-right" style="color:grey">&nbsp; <small>';
            if (arr.tonality!="") bezeichnung=bezeichnung+""+arr.tonality;
            if (arr.tonality!="" && arr.bpm!="") bezeichnung=bezeichnung+", ";
            if (arr.bpm!="") bezeichnung=bezeichnung+" "+arr.bpm+"BPM";
            bezeichnung=bezeichnung+"&nbsp; </small></span>";
          }
        }
      }   
      rows.push(bezeichnung);
    } 
    if (user_access("edit agenda", t.currentAgenda.calcategory_id) 
         && (t.currentAgenda.template_yn==0 || user_access("edit agenda templates", t.currentAgenda.calcategory_id))) {
      rows.push('&nbsp; <span class="hoverreactor">');
      rows.push('<a href="#" class="edit-item" data-id="'+o.id+'">'+form_renderImage({src:"options.png", width:16})+'</a> ');
      rows.push('<span class="dropdown" data-id="'+o.id+'"><a href="#" class="dropdown-toggle" data-toggle="dropdown" data-id="'+o.id+'">'+form_renderImage({src:"plus.png",width:16})+'</a>');
      rows.push('<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">');
      rows.push('<li><a href="#" class="add-item post">Position dahinter einfügen</a></li>');
      rows.push('<li><a href="#" class="add-item song post">Song dahinter einfügen</a></li>');
      rows.push('<li><a href="#" class="add-item header post">Überschrift dahinter einfügen</a></li>');
      rows.push('<li class="divider"></li>');
      rows.push('<li><a href="#" class="add-item">Position davor einfügen</a></li>');
      rows.push('<li><a href="#" class="add-item song">Song davor einfügen</a></li>');
      rows.push('<li><a href="#" class="add-item header">Überschrift davor einfügen</a></li>');
      rows.push('<li class="divider"></li>');
      rows.push('<li><a href="#" class="delete-item">Löschen</a></li>');
      rows.push('</ul></span>');
      rows.push('</span>');
    }
    if (o.note!="") {
      rows.push('<div class="event_info">');
      if ($("#printview").val()!=null)
        rows.push(o.note);
      else        
        rows.push(o.note.trim((!smallVersion?200:40)));
      rows.push("</div>");
    }
    
  }
  else if (dataField.indexOf("servicegroup")==0) {
    if (o.servicegroup!=null && o.servicegroup[dataField.substr(12,99)]!=null)
      rows.push('<small>'+o.servicegroup[dataField.substr(12,99)].htmlize()+'</small>');
  }
  else rows.push(o[dataField]);
  
  return rows.join(""); 
};

AgendaView.prototype.rerenderField = function(input, dataField) {
  if (dataField=="duration") {
    var a=input.split(":");
    if (a.length==1)
      return a[0]*60+"";
    else
      return (a[0]*60+a[1]*1)+"";    
  }
  
  return input; 
};

AgendaView.prototype.saveServiceGroupNote = function (data, servicegroup_id) {
  churchInterface.jsendWrite({func:"saveServiceGroupNote", item_id:data.id, servicegroup_id:servicegroup_id,
      note:data.servicegroup[servicegroup_id]}, function(ok, data) {
        if (!ok) alert("Fehler beim Speichern: "+data);
      });
};


AgendaView.prototype.addFurtherListCallbacks = function(cssid, smallVersion) {
  var t=this;
  if (smallVersion==null) smallVersion=false;
  
  
  t.renderTimes();
  
  $(cssid+" a").click(function (a) {
    if ($(this).attr("id")==null) {
      if ($(this).hasClass("view-song")) {
        var song_id=$(this).attr("data-song-id");
        var arrangement_id=$(this).attr("data-arrangement-id");
        allSongs[song_id].active_arrangement_id=arrangement_id;
        songView.filter["searchEntry"]="#"+song_id;
        churchInterface.setCurrentView(songView, false);
        return false;
      }
      else
        return true;    
    }
    else if ($(this).attr("id").indexOf("addMoreCols")==0) {
      t.addMoreCols();
      return false;
    }
    else if ($(this).attr("id").indexOf("delCol")==0) {
      var id=$(this).attr("id").substr(6,99);
      masterData.settings["viewgroup_agenda"+id]=0;
      churchInterface.jsendWrite({func:"saveSetting", sub:"viewgroup_agenda"+id, val:0});
      t.renderList();
      return false;
    }
  });

  if (!smallVersion) {
    
    $(cssid+" a.attachement").click(function() {
      var id=$(this).parents("tr").attr("id");
      var arr_id=t.currentAgenda.items[id].arrangement_id;
      var song=songView.getSongFromArrangement(arr_id);
      if (song!=null) {
        song.active_arrangement_id=arr_id;
        churchInterface.setCurrentView(songView, true);
        songView.setFilter("searchEntry", "#"+songView.getSongFromArrangement(arr_id).id);
      }
    });
    
    if (t.currentAgenda!=null && user_access("edit agenda", t.currentAgenda.calcategory_id)
      && (t.currentAgenda.template_yn==0 || user_access("edit agenda templates", t.currentAgenda.calcategory_id))) {

      $(cssid+" td.clickable").hover(function() {
          $(this).addClass("active");
        },
        function() {
          $(this).removeClass("active");
        }
      );
      
      // Implements editable
      $(cssid+" td.editable").each(function(k,a) {
        var id=$(this).parents("tr").attr("id");
        var field=$(this).attr("data-field");
        $(this).editable({
          
          type: ($(this).hasClass("textarea")?"textarea":"input"),
          
          data: {id:id, field:field},
          
          success:
            function(newval, data) {
              item=t.currentAgenda.items[data.id];           
              if (data.field=="bezeichnung") { 
                item[data.field]=newval;
                t.saveItem(item);
              } 
              else if (data.field.indexOf("servicegroup")==0) {
                if (item.servicegroup==null) item.servicegroup=new Object();
                item.servicegroup[data.field.substr(12,99)]=newval;
                t.saveServiceGroupNote(item, data.field.substr(12,99));
              }
              else {
                item[data.field]=newval;
                t.saveItem(item);
                if (data.field=="duration") t.renderTimes();
              }            
            },
          
          value: (field.indexOf("servicegroup")==0
                   ?(t.currentAgenda.items[id].servicegroup!=null?t.currentAgenda.items[id].servicegroup[field.substr(12,99)]:"")
                   :t.currentAgenda.items[id][field]),
                   
          render: 
            function(txt, data) {
              return t.renderField(t.currentAgenda.items[data.id], data.field, true);
            },
            
          rerenderEditor:
            function(txt, data) {
              if (data.field=="duration") {
                var a=txt.split(":");
                if (a.length==1)
                  return a[0]*60+"";
                else
                  return (a[0]*60+a[1]*1)+"";
              }
              else return txt;            
            },
            
          renderEditor:
            function(txt, data) {
              if (data.field=="duration") return txt.formatMS();
              else return txt;
            },
            
          afterRender:
            function(element, data) {
              // Now implement callbacks
              if (data.field=="bezeichnung") {
                
                element.find("a.dropdown-toggle").dropdown();
                
                element.find("a.add-item").click(function() {
                  var elem=$(this);
                  var orig_item_id=elem.parents("span.dropdown").attr("data-id");
                  // When addings songs it will lead to the songView, where I can select a song
                  if (elem.hasClass("song")) {
                    songView.songselect={post:elem.hasClass("post"), orig_item_id:orig_item_id};
                    churchInterface.setCurrentView(songView);
                  }
                  else {
                    t.addItem(orig_item_id, elem.hasClass("post"), elem.hasClass("header"));
                  }
                  return false;
                });
                
                element.find("a.edit-item").click(function() {
                  t.editItem(t.currentAgenda.items[$(this).attr("data-id")]);
                  return false;
                });
                element.find("a.view-song").click(function() {
                  var song_id=$(this).attr("data-song-id");
                  var arrangement_id=$(this).attr("data-arrangement-id");
                  allSongs[song_id].active_arrangement_id=arrangement_id;
                  songView.filter["searchEntry"]="#"+song_id;
                  delete songView.filter["filterSongcategory"];
                  churchInterface.setCurrentView(songView, false);
                  return false;
                });
                
                element.find("a.delete-item").click(function() {
                  if (churchcore_countObjectElements(t.currentAgenda.items)==1) {
                    alert("Position kann nicht gelöscht werden. Es muß mindestens eine Position bestehen bleiben!");
                  }
                  else {
                    if (confirm("Position "+t.currentAgenda.items[id].bezeichnung+" wirklich löschen?")) {
                      t.deleteItem(t.currentAgenda.items[id], function() {
                        t.renderList();
                      });
                    }
                  }
                  return false;
                });
                
                
                
              }              
            }
        });
      });

      $(cssid+" td.clickable").click(function() {
        var data=t.getData();
        var id=$(this).parents("tr").attr("id");
        var col=$(this).attr("data-field");
        if (col.indexOf("time")==0) {
          var event_id=col.substr(4,99);
          if (churchcore_inArray(event_id, data[id].event_ids)) {
            churchcore_removeFromArray(event_id, data[id].event_ids);
            churchInterface.jsendWrite({func:"deleteItemEventRelation", item_id:id, event_id:event_id});
          } 
          else {
            if (data[id].event_ids==null) data[id].event_ids=new Array();
            churchInterface.jsendWrite({func:"addItemEventRelation", item_id:id, event_id:event_id});
            data[id].event_ids.push(event_id);
          }
          t.renderTimes();
        }    
      });
      
      $("tbody").sortable({
          helper: function(e, tr) {
            var originals = tr.children();
            var helper = tr.clone();
            helper.children().each(function(index) {
                $(this).width(originals.eq(index).width());
            });
            return helper;
          },
          stop: function(e, ui) {
            sortData(t.getData(true), $(this).attr("data-previd"), ui.item.index());
            t.saveAgenda(t.currentAgenda, function(data) {
              t.currentAgenda=data;
            });
            t.renderTimes();
          },
          start: function(e, ui) {
            $(this).attr('data-previd',ui.item.index());
          }
      }).disableSelection();
    }
  }
  

};


AgendaView.prototype.addItem = function(orig_item_id, post, header, arrangement) {
  var t=this;
  
  var sortkey=t.currentAgenda.items[orig_item_id].sortkey*1;
  // Add after this position?
  if (post) sortkey=sortkey+1;
  
  // Move all positions one position behind
  $.each(t.currentAgenda.items, function(k,a) {
    if (a.sortkey*1>=sortkey) a.sortkey=a.sortkey*1+1;
  });
  
  
  var item = t.getNewItem({sortkey:sortkey, event_ids:t.currentAgenda.event_ids, agenda_id:t.currentAgenda.id});
  
  if (header) { 
    item.header_yn=1;
    item.bezeichnung="Neue Überschrift";
  }
  else if (arrangement!=null) {
      item.arrangement_id=arrangement.id;
      item.bezeichnung="";
      item.duration=arrangement.length_min*60+arrangement.length_sec*1+"";
  }
  t.saveItem(item, function() {
    t.saveAgenda(t.currentAgenda, function(data) {
      t.currentAgenda=data;
    });
    var pos=$(document).scrollTop();
    t.renderList();
    window.setTimeout(function() { $(document).scrollTop(pos);}, 10);
    t.renderTimes();
  });
  
};

AgendaView.prototype.getPrevItem = function(item) {
  var t=this;
  
  var res=null;
  $.each(t.getData(true, true), function(k,a) {
    if (a.id==item.id) return false;
    else res=a; 
  });
  return res;
};

AgendaView.prototype.getNextItem = function(item) {
  var t=this;
  
  var next=false;
  var res=null;
  $.each(t.getData(true, true), function(k,a) {
    if (a.id==item.id) next=true;
    else if (next) {
      res=a;
      return false;
    }
  });
  return res;
};

AgendaView.prototype.editItem = function(item) {
  var item = $.extend({}, item);
  item.duration_m=Math.floor(item.duration/60);
  item.duration_s=item.duration%60;
  if (item.duration_s<10) item.duration_s="0"+item.duration_s;
  var form = new CC_Form(null, item);
  form.addInput({cssid:"bezeichnung", label:"Titel"});
  if (item.header_yn==0) {
    if (item.song_id==null && item.arrangement_id!=null) {
      var s=songView.getSongFromArrangement(item.arrangement_id);
      if (s!=null)
        item.song_id=s.id;
    }
    form.addSelect({data:allSongs, cssid:"song_id", htmlclass:"song", freeoption:true, label:"Song"});
    if (item.song_id!=null) {
      form.addSelect({data:allSongs[item.song_id].arrangement, cssid:"arrangement_id", label:"Arrangement",
          htmlclass:"arrangement"});      
    }
    form.addInput({cssid:"duration_m", controlgroup_start:true, type:"mini", label:"Dauer (m:s)"});
    form.addHtml(' : ');
    form.addInput({cssid:"duration_s", controlgroup_end:true, type:"mini"});
    form.addInput({cssid:"responsible", label:"Verantwortlich"});
    form.addTextarea({cssid:"note", label:"Notiz", rows:4});
    form.addCheckbox({cssid:"preservice_yn", label:"Position liegt zeitlich vor dem Event"});
  }
  else {
    form.addInput({cssid:"duration_m", controlgroup_start:true, type:"mini", label:"ca. Dauer (min)"});
  }

  form.addHtml('<p class="pull-right"><small>#'+item.id+'</p>');
  var elem = form_showDialog((item.header_yn==0?"Position bearbeiten":"Überschrift bearbeiten"), form.render(null, "horizontal"), 530, 500, {
    "<<": function() {
      var res=t.getPrevItem(item);
      if (res!=null) {
        $(this).dialog("close");
        t.editItem(res);
      }
      else alert("Keine weitere Position vorhanden!");
    },
    ">>": function() {
      var res=t.getNextItem(item);
      if (res!=null) {
        $(this).dialog("close");
        t.editItem(res);
      }
      else alert("Keine weitere Position vorhanden!");
    },
    "Speichern": function() {
      $.each(form.getAllValsAsObject(), function(k,a) {
        item[k]=a;
      });
      item.duration=((item.duration_m*60)+(item.duration_s*1))+"";
      item.song_id=null;
      item.agenda_id=t.currentAgenda.id;
      t.saveItem(item, function() {
        elem.dialog("close");
        t.renderList(item);
        t.renderTimes();
      });
    },
    "Abbruch": function() {
      item.song_id=null;
      $(this).dialog("close");
    }
  });  
  elem.find('select.song').change(function() {
    item.song_id=$(this).val();
    if (allSongs[item.song_id].active_arrangement_id!=null)
      item.arrangement_id=allSongs[item.song_id].active_arrangement_id;
    else
      item.arrangement_id=songView.getDefaultArrangement(allSongs[item.song_id]);

    elem.dialog("close");
    t.editItem(item);
  });
  elem.find('select.arrangement').change(function() {
    var a=allSongs[item.song_id].arrangement[$(this).val()];
    item.arrangement_id=$(this).val();
    item.duration=a.length_min*60+a.length_sec*1;
    elem.dialog("close");
    t.editItem(item);
  });
};



AgendaView.prototype.saveItem = function(item, func) {
  var t=this;
  
  item.func="saveItem";
  churchInterface.jsendWrite(item, function(ok, data) {
    if (!ok) alert("Fehler: "+data);
    else {
      item.id=data;
      t.currentAgenda.items[item.id]=item;
      if (func!=null) func(item.id);
    }
  });  
};

AgendaView.prototype.deleteItem = function(item, func) {
  var t=this;
  
  item.func="deleteItem";
  churchInterface.jsendWrite(item, function(ok, data) {
    if (!ok) alert("Fehler: "+data);
    else {
      delete t.currentAgenda.items[item.id];
      if (func!=null) func();
    }
  });    
};
 
AgendaView.prototype.renderFilter = function() {
  var t=this;
  var form = new CC_Form("Auswahl des Ablaufes");
  
  var arr=new Array();
  if (allAgendas!=null) {
    $.each(allAgendas, function(k,a) {
      if (a.template_yn==0) arr.push(a);
    });
    if (arr.length>0) {
      var arr2=new Array();
      arr2.push({bezeichnung:"-- Aktuelle Abläufe --"});
      arr=arr2.concat(arr);
    }
    arr.push({bezeichnung:"-- Vorlagen --"});
    $.each(churchcore_sortData(allAgendas, "bezeichnung"), function(k,a) {
      if (a.template_yn==1)
        arr.push({id:a.id, bezeichnung:a.bezeichnung.trim(50)});
    });
    
  }
  
  form.addSelect({data:arr, sort:false, cssid:"event", type:"medium", 
	             selected:(t.currentAgenda!=null?t.currentAgenda.id:null), freeoption:true});
  
  $("#cdb_filter").html(form.render(true));
  $("#cdb_filter").find("#event").change(function() {
    t.currentAgenda=allAgendas[$(this).val()];
    t.renderList();
  });
};



AgendaView.prototype.loadTemplates = function () {
  var t=this;
  if (!t.templatesLoaded) {
    t.templatesLoaded=true; 
    if (user_access("view agenda")) {
      churchInterface.jsendRead({func:"loadAllAgendaTemplates"}, function(ok, data) {
        if (!ok) alert("Fehler beim Laden der Daten: "+data);
        else {
          if (data!=null) {
            $.each(data, function(k,a) {
              if (allAgendas==null) allAgendas=new Object();
              allAgendas[a.id]=a;
            });
          }
          t.renderView();
        }
      });
    }
  }  
};

AgendaView.prototype.loadAgendaForEvent = function(event_id, func) {
  var t=this;
  
  var agenda_id=null;
  if (allAgendas!=null) {
    $.each(allAgendas, function(k,a) {
      if (a.event_ids!=null) {
        $.each(a.event_ids, function(i,e) {
          if (e==event_id) agenda_id=a.id;
          return false;
        });
      }
    });
    if (agenda_id!=null) {
      t.currentAgenda=allAgendas[agenda_id];
      if (t.currentAgenda.items==null) {
        t.loadItems(t.currentAgenda.id, function() {
          if (func!=null) func(t.currentAgenda);
        });
      }
      else 
        if (func!=null) window.setTimeout(function() {func(t.currentAgenda);}, 10);
    }
  }

  if (agenda_id==null) {    
    churchInterface.jsendRead({func:"loadAgendaForEvent", event_id:event_id}, function(ok, data) {
      if (!ok) $("#cdb_content").html("Fehler beim Laden: "+data);
      else {
        if (allAgendas==null) {
          allAgendas=new Object();
        }
        allAgendas[data.id]=data;
        t.currentAgenda=data;
        t.loadItems(t.currentAgenda.id, function() {
          if (func!=null) func(data);
        });
      }
    });
  }
};

AgendaView.prototype.getAgendaForEventIdIfOnline = function (event_id) {
  if (allAgendas==null) return null;
  var res=null;
  $.each(allAgendas, function(k,a) {
    if (churchcore_inArray(event_id, a.event_ids)) {
      res=a;
      return false;
    }
  });
  return res;
};

AgendaView.prototype.getListHeader = function () {
  var t=this;
  t.listViewTableHeight=null;
  masterData.settings["listMaxRows"+t.name]=9999;
  t.loadTemplates();
  
  // When allAgenda is null, start loading Songs and Templates
  if (allAgendas==null) {
    songView.loadSongData();

    var ids=new Array();
    if (listView.currentEvent==null) {
      if (masterData.settings.currentAgenda!=null)
        ids.push(masterData.settings.currentAgenda);
      if ($("#externevent_id").val()!=null) {
        ids.push($("#externevent_id").val());
        masterData.settings.currentAgenda=$("#externevent_id").val();
      }
    }
    
    if (ids.length>0) {
      churchInterface.jsendRead({func:"loadAgendas", ids:ids}, function(ok, data) {
        if (!ok) alert("Fehler beim Laden der Daten: "+data);
        else {
          if (data!=null) {
            if (allAgendas==null) allAgendas=new Object();
            $.each(data, function(k,a) {
              allAgendas[a.id]=a;
            });
            if (listView.currentEvent==null && masterData.settings.currentAgenda!=null)
              t.currentAgenda=allAgendas[masterData.settings.currentAgenda];
            t.renderView();            
          }
        }
      });
      return;
    }
  }

  // When there is a currentAgenda and no items, then first loading items.
  if ((t.currentAgenda!=null) && (t.currentAgenda.items==null)) {
    t.loadItems(t.currentAgenda.id, function() {
      t.renderList();
    });
    return;
  }
  
  
  if (t.currentAgenda==null || allAgendas==null) {
    var form=new CC_Form();
    if (listView.currentEvent!=null) {
      // If already an agenda mapped to the event
      if (listView.currentEvent.agenda) {
        t.loadAgendaForEvent(listView.currentEvent.id, function(data) {
          t.renderView();          
        });
        return;
      } 
      // There is no agenda, please select one
      else {
        form.addHtml("<legend>"+listView.currentEvent.bezeichnung+'&nbsp;');
        form.addHtml(listView.currentEvent.startdate.toDateEn(true).toStringDe(true));
        form.addHtml("</legend>");
        
        form.addHtml("Zum Erstellen des Ablaufplanes kann nun eine Vorlage ausgewählt werden:");
        
        var arr2=new Array();
        if (allAgendas!=null) {
          $.each(allAgendas, function(k,a) {
            if (a.template_yn==0) {
              var add=false;
              $.each(a.event_ids, function(i,e) {
                if (allEvents[e]!=null && (allEvents[e].startdate.substr(0,10)==listView.currentEvent.startdate.substr(0,10))
                   && (allEvents[e].category_id==a.calcategory_id))
                  add=true;
              });
              if (add) {
                arr2.push(a);
              }
            }
          });
        }
        var arr = new Array();
        if (arr2.length>0) {
          arr.push({id:"", bezeichnung:"-- Vorhandenen Ablaufplan nutzen --"});
          arr=arr.concat(arr2);
          arr.push({id:"", bezeichnung:"-- Vorlage auswählen --"});
        }
        if (allAgendas!=null) {
          $.each(allAgendas, function(k,a) {
            if (a.template_yn==1 && listView.currentEvent.category_id==a.calcategory_id) arr.push(a);
          });
        }
        if (allAgendas!=null) {
          arr.push({id:"", bezeichnung:"-- Vorlage anderer Kalender auswählen --"});
          $.each(allAgendas, function(k,a) {
            if (a.template_yn==1 && listView.currentEvent.category_id!=a.calcategory_id) arr.push(a);
          });
        }
        
        form.addSelect({data:arr, sort:false, freeoption:true, htmlclass:"chose-template", label:"Vorlage auswählen"});
        form.addButton({controlgroup:true, label:"Ohne Vorlage fortfahren", htmlclass:"go-without-template"});        
      }
    }
    else {
      form.addHtml("Um Abläufe zu editieren bitte entweder ein Event im Dienstplan oder hier eine Vorlage auswählen.");
    }

    
    $("#cdb_group").html(form.render(true, "horizontal"));
    
    $("input.go-without-template").click(function() {
      t.startNewAgenda(null);      
    });
    $("select.chose-template").change(function() {
      if ($(this).val()!="") {
        if (listView.currentEvent!=null) {
          var id=$(this).val();
          if (allAgendas[$(this).val()]!=null && allAgendas[$(this).val()].template_yn==1) {
            t.startNewAgenda(allAgendas[id]);            
          }
          else {
            var form = new CC_Form();
            form.addHtml("<legend>Wie soll der Ablaufplan genutzt werden?</legend>");
            form.addHtml("<ul><li><b>Kopieren</b> - Beim Kopieren wird eine Kopie angelegt, d.h. der neue Ablaufplan ist völlig unabhängig von dem alten.");
            form.addHtml("<li><b>Integrieren</b> - Beim Integrieren wird der neue Plan in den vorhandenen eingearbeitet. Der Ablauf erhält also eine zusätzliche Startzeit. Durch einen Klick auf die jeweilge Uhrzeit kann gewählt werden, ob der Eintrag nur in einem der Events stattfinden soll.");
            form.addHtml('</ul');
  
            var elem=form_showDialog("Ablaufplan nutzen",form.render(), 500,350, {
              "Kopieren": function() {
                t.startNewAgenda(allAgendas[id], true);
                elem.dialog("close");
              },
              "Integrieren": function() {
                t.startNewAgenda(allAgendas[id]);              
                elem.dialog("close");
              },
              "Abbrechen": function() {
                elem.dialog("close");
                $("select.chose-template").val("");
              }                         
            });
          }
        }
      }
    });
    
    return;
  }

  if (t.currentAgenda.id!=null && masterData.settings.currentAgenda!=t.currentAgenda.id) {
    masterData.settings.currentAgenda=t.currentAgenda.id;
    churchInterface.saveSetting("currentAgenda", t.currentAgenda.id);
  }
  
  var form = new CC_Form();
  form.addHtml('<legend class="hoveractor">');
  if (masterData.category[t.currentAgenda.calcategory_id]!=null && masterData.category[t.currentAgenda.calcategory_id].color!=null)
    form.addHtml('<span title="Kalender: '+masterData.category[t.currentAgenda.calcategory_id].bezeichnung+'" style="background-color:'+masterData.category[t.currentAgenda.calcategory_id].color+'; margin-top:5px; margin-left:3px; width:4px; height:20px">&nbsp;</span>&nbsp;');

  if (t.currentAgenda.template_yn==1) form.addHtml("Vorlage: ");
  form.addHtml(t.currentAgenda.bezeichnung+"&nbsp;");
  if (t.currentAgenda.template_yn==0 && t.currentAgenda.final_yn==0) form.addHtml(" - ENTWURF &nbsp;");
  if (user_access("edit agenda", t.currentAgenda.calcategory_id) 
    && (t.currentAgenda.template_yn==0 || user_access("edit agenda templates", t.currentAgenda.calcategory_id))) {
    form.addImage({src:"options.png", hover:true, width:20, htmlclass:"edit-agenda", label:"Editieren", link:true});
    form.addImage({src:"trashbox.png", hover:true, width:20, htmlclass:"delete-agenda", label:"Löschen", link:true});
  }
  form.addHtml('</legend>');
  if (!$("#printview").val()) {
    if (churchcore_countObjectElements(t.currentAgenda.event_ids)>0) {
      form.addButton({label:"Zum Event gehen", htmlclass:"go-to-event"});
      form.addHtml("&nbsp;");
    }
    if (t.currentAgenda.template_yn==0 && t.currentAgenda.final_yn==0) 
      form.addButton({label:"Als abgeschlossen markieren", htmlclass:"send-agenda"});
  }
  $("#cdb_group").html(form.render(true));
  $("#cdb_group a.edit-agenda").click(function() {
    t.editAgenda(t.currentAgenda, t.currentAgenda.template_yn==1);
    return false;
  });  
  $("#cdb_group a.delete-agenda").click(function() {
    t.deleteAgenda(t.currentAgenda);
    return false;
  });
  $("#cdb_group .go-to-event").click(function() {
    var startdate=new Date(2000);
    $.each(t.currentAgenda.event_ids, function(k,a) {
      if (allEvents[a]!=null && allEvents[a].startdate.toDateEn(false)>startdate)
        startdate=allEvents[a].startdate.toDateEn(false);
    });
    churchInterface.setCurrentView(listView, true);
    window.setTimeout(function() {
      listView.currentDate=startdate;
      listView.renderList();      
    },10);
    return false;
  });
  $("#cdb_group .send-agenda").click(function() {
    var form = new CC_Form("Ablaufplan abschliessen");
    form.addCheckbox({label: "Ablaufplan als abgeschlossen markieren", checked:true, cssid:"final_yn"});
    form.addCheckbox({label: "Alle beteiligten per E-Mail &uuml;ber den Ablauf informieren", checked:false, cssid:"email"});
    var elem = form_showDialog("Ablaufplan", form.render(), 500, 400, {
      "Speichern": function() {
        var o=form.getAllValsAsObject();
        if (o.final_yn==1) {
          t.currentAgenda.final_yn=1;
          t.saveAgenda(t.currentAgenda);
        }
        if (o.email==1) {
          listView.sendEMailToEvent(allEvents[t.currentAgenda.event_ids[0]]);
        }
        elem.dialog("close");
        t.renderList();
      },
      "Abbrechen": function() {
        elem.dialog("close");
      }
    });    
  });
  
  return t.renderListHeader();
};


AgendaView.prototype.getAllowedServiceGroupsWithComments = function() {
  var t=this;
  var groups=new Object();
  if (t.currentAgenda.items!=null) {
    $.each(t.currentAgenda.items, function(k,a) {
      if (a.servicegroup!=null) {
        $.each(a.servicegroup, function(i,s) {
          if (s!="" && s!="\n" && masterData.auth.viewgroup[i])  
            groups[i]=masterData.servicegroup[i];
        });
      } 
    });
  }
  return t.sortMasterData(groups);
};

AgendaView.prototype.renderListHeader = function(smallVersion) {
  var t=this;
  if (smallVersion==null) smallVersion=false;
  var rows = new Array();
  if (t.currentAgenda.template_yn==1) {
    rows.push('<th width="40px">Gesamt');     
  } 
  else if (t.currentAgenda.event_ids!=null) {
    if (churchcore_countObjectElements(t.currentAgenda.event_ids)==1)
      rows.push('<th width="40px">Uhrzeit'); 
    else {      
      $.each(t.currentAgenda.event_ids, function(k,a) {
        if (allEvents[a]!=null)
          rows.push('<th width="40px">'+allEvents[a].startdate.toDateEn(true).toStringDeTime());      
      });
    }
  }
  rows.push('<th width="45px">L&auml;nge<th style="min-width:200px">Text<th>Verantwortlich');

  var groups=new Object();
  if (smallVersion)
    groups=t.getAllowedServiceGroupsWithComments();
  else {
    $.each(t.sortMasterData(masterData.servicegroup), function(k,a) {
      if ((masterData.settings["viewgroup_agenda"+a.id]==null) || (masterData.settings["viewgroup_agenda"+a.id]==1))
        if (masterData.auth.viewgroup[a.id]) {
          groups[k]=a;
        }
    });    
  } 

  $.each(groups, function(k,a) {
    rows.push('<th class="hoveractor" style="min-width:60px" id="header'+a.id+'">'+a.bezeichnung);
    rows.push('<span class="hoverreactor pull-right">');
    rows.push('<a href="#" id="delCol'+a.id+'">'+form_renderImage({src:"minus.png",width:16})+'</a> ');
    rows.push('</span>');
  });
  
  if (!smallVersion && $("#printview").val()==null) {
    rows.push('<th width="16px"><a href="#" id="addMoreCols">'+this.renderImage("plus",16)+'</a>'); 
    rows.push('<th>'+form_renderImage({src:"paperclip.png", width:18}));
  }  
  return rows.join("");
};

AgendaView.prototype.startNewAgenda = function(template_agenda, copying) {
  var t=this;
  
  t.currentAgenda=$.extend({}, template_agenda);
  if (copying==null) copying=false;

  // If copying from a template delete Id
  if (t.currentAgenda.template_yn==1 || copying) {
    copying=true;             
    delete t.currentAgenda.id;
  }
  
  if (copying || template_agenda==null) {
    t.currentAgenda.template_yn=0;
    t.currentAgenda.calcategory_id=listView.currentEvent.category_id;
      t.currentAgenda.bezeichnung=listView.currentEvent.startdate.toDateEn(true).toStringDe(true)+
    " - Ablauf "+listView.currentEvent.bezeichnung;
    if (t.currentAgenda.series==null) t.currentAgenda.series="";
    if (t.currentAgenda.final_yn==null) t.currentAgenda.final_yn=0;
  }

  if (t.currentAgenda.event_ids==null)
    t.currentAgenda.event_ids=new Array();
  t.currentAgenda.event_ids.push(listView.currentEvent.id);
  if (copying) {
    // Load Items from template
    t.loadItems(template_agenda.id, function() {
      $.each(t.currentAgenda.items, function(k,a) {
        delete a.id;
        a.event_ids=new Array();
        a.event_ids.push(listView.currentEvent.id);
      });
      t.saveAgenda(t.currentAgenda, function(data) {
        t.currentAgenda=data;
        t.renderList();
      });
      listView.currentEvent.agenda=true;
    });
  }
  else {
    if (t.currentAgenda.items!=null) {
      $.each(t.currentAgenda.items, function(k,a) {
        a.event_ids.push(listView.currentEvent.id);
      });
    } 
    else {
      t.currentAgenda.items=new Object();
      t.currentAgenda.items[-1]= t.getNewItem({event_ids:[listView.currentEvent.id]});      
    }
    t.saveAgenda(t.currentAgenda, function(data) {
      t.renderList();              
      t.currentAgenda=data;
    });
    listView.currentEvent.agenda=true;
  }  
};

/**
 * Load items in currentAgenda-Object and call func if ready
 * @param agenda_id
 */
AgendaView.prototype.loadItems = function (agenda_id, func) {
  var t=this;
  churchInterface.jsendRead({func:"loadAgendaItems", agenda_id:agenda_id}, function(ok, data) {
    t.currentAgenda.items=new Object();
    if (!ok) alert("Fehler beim Laden der Daten: "+data);
    else {
      if (data!=null) t.currentAgenda.items=data;
      if (func!=null) func();
    }
  });          
};

AgendaView.prototype.renderListEntry = function (event, smallVersion) {
  var t=this;
  if (smallVersion==null) smallVersion=false;
  var rows = new Array();  

  if (event.header_yn==1) {
    rows.push('<td id="'+event.id+'" class="hoveractor editable grouping" data-field="bezeichnung" colspan="12">');
    rows.push(t.renderField(event, "bezeichnung", smallVersion));
  }
  else {    
    if (t.currentAgenda.template_yn==1) {
      rows.push('<td class="clickable" data-field="time-1"></td>');              
    }
    else if (t.currentAgenda.event_ids!=null) {
      $.each(t.currentAgenda.event_ids, function(i,a) {
        if (allEvents[a]!=null)
          rows.push('<td class="clickable" data-field="time'+a+'"></td>');        
      });
    }
    
    rows.push('<td class="editable" data-field="duration">');
    rows.push(t.renderField(event, "duration"));
    rows.push('<td class="hoveractor editable" data-field="bezeichnung">');

    rows.push(t.renderField(event, "bezeichnung", smallVersion));
  }

  if (debug) rows.push("&nbsp;" +event.sortkey);
 

  if (event.header_yn==0) {
    rows.push('<td class="editable" data-field="responsible">'+event.responsible);
  
    var groups=new Object();
    if (smallVersion)
      groups=t.getAllowedServiceGroupsWithComments();
    else {
      $.each(t.sortMasterData(masterData.servicegroup), function(k,a) {
        if ((masterData.settings["viewgroup_agenda"+a.id]==null) || (masterData.settings["viewgroup_agenda"+a.id]==1))
          if (masterData.auth.viewgroup[a.id]) {
            groups[k]=a;
          }
      });    
    }   
    
    $.each(groups, function(k,a) {
      rows.push('<td class="editable textarea" data-field="servicegroup'+a.id+'">');    
      rows.push(t.renderField(event, "servicegroup"+a.id, true));
    });
    if (!smallVersion && $("#printview").val()==null) {
      rows.push('<td><td>');
      if (event.arrangement_id!=null && event.arrangement_id>0) {
        var song=songView.getSongFromArrangement(event.arrangement_id);
        if (song!=null && song.files!=null)
          rows.push(form_renderImage({src:"paperclip.png", link:true, htmlclass:"attachement", width:20}));
      }
    }
  }
  return rows.join("");
};


AgendaView.prototype.renderTimes = function() {
  var t=this;
  if (t.currentAgenda==null || t.currentAgenda.items==null) return;
  
  var preservice_seconds=0;
  $.each(t.getData(), function(k,item) {
    if (item.preservice_yn==1)
      preservice_seconds=preservice_seconds+item.duration*1;
  });
  
  if (t.currentAgenda.template_yn==1) t.currentAgenda.event_ids=[-1];

  // Get Starttimes from all Events
  var time = new Array();
  $.each(t.currentAgenda.event_ids, function(k,a) {
    if (allEvents[a]!=null) 
      time[a]=allEvents[a].startdate.toDateEn(true);
    else {
      time[a]=new Date();
      time[a]=time[a].withoutTime();
    }
    time[a].setSeconds(time[a].getSeconds() - preservice_seconds);
  });
  var elem=$("table.AgendaView");
  // Now go through the Items and render the times
  $.each(t.getData(true), function(k,item) {
    $.each(t.currentAgenda.event_ids, function(i,a) {
      if ((t.currentAgenda.template_yn==1 || churchcore_inArray(a, item.event_ids)) && item.header_yn==0) {
        var rows=new Array();
        rows.push(time[a].toStringDeTime()+"h");
        time[a].setSeconds(time[a].getSeconds() + item.duration*1);
        elem.find('tr[id="'+item.id+'"] td[data-field=time'+a+']').html(rows.join(""));
      }
      else elem.find('tr[id="'+item.id+'"] td[data-field=time'+a+']').html("");
    });
  });
  
  if (t.currentAgenda.template_yn==1) delete t.currentAgenda.event_ids;
};

AgendaView.prototype.messageReceiver = function(message, args) {
  var t= this;
  if (message=="allDataLoaded")
    this.allDataLoaded=true;
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      t.renderFilter();
      t.renderList();
    }
  }
};

AgendaView.prototype.addSecondMenu = function() {
  return '';
};

AgendaView.prototype.renderEntryDetail = function (event_id) {
};


})(jQuery);
