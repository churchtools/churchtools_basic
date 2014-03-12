// Constructor
function SongView() {
  ListView.call(this);
  this.name="SongView";
  this.songsLoaded=false;
  this.allDataLoaded=false;
  this.sortVariable="bezeichnung";
  this.availableRowCounts=[10,25,50,200];

}

Temp.prototype = ListView.prototype;
SongView.prototype = new Temp();
songView = new SongView();


SongView.prototype.getSongFromArrangement = function(arrangement_id) {
  var song=null;
  if (allSongs!=null)
    $.each(allSongs, function(k,a) {
      $.each(a.arrangement, function(i,b) {
        if (b.id==arrangement_id) {
          if (a.active_arrangement_id!=null)
            a.active_arrangement_id=arrangement_id;
          song=a;
          return false;
        }
      });
      if (song!=null) return false;
    });
  return song;
};

SongView.prototype.getData = function(sorted) {
  if (sorted) {
    var list=new Array();
    if (allSongs!=null)
      $.each(churchcore_sortData(allSongs,"bezeichnung"), function(k,song) {
        list.push(song);
      });
    return list;
  }
  else {
    return allSongs;
  }
};


SongView.prototype.renderFilter = function () {
  var t=this;
  
  if ((masterData.settings.filterSongcategory=="") || (masterData.settings.filterSongcategory==null))
    delete masterData.settings.filterSongcategory;
  else if (t.filter["searchEntry"]==null) t.filter["filterSongcategory"]=masterData.settings.filterSongcategory;

  if ((masterData.settings.searchStandard!="true") || (masterData.settings.searchStandard==null))
    delete masterData.settings.searchStandard;
  else t.filter["searchStandard"]=masterData.settings.searchStandard;
  
  var rows = new Array();
  
  var form = new CC_Form();
  form.setHelp("ChurchService-Filter");

  form.addHtml('<div id="filterKategorien"></div>');

  form.addSelect({data:t.sortMasterData(masterData.songcategory),
                    label:"Song-Kategorien",
                    selected:t.filter["filterSongcategory"],
                    freeoption:true,
                    cssid:"filterSongcategory",
                    type:"medium",
                    func:function(s) {return (masterData.auth.viewsongcategory!=null) && (masterData.auth.viewsongcategory[s.id])}
  });
 
  form.addCheckbox({cssid:"searchStandard",label:"Arrangements anzeigen", checked:masterData.settings.searchStandard=="true"});  
  rows.push(form.render(true));

  /*
  if (agendaView.currentAgenda!=null && agendaView.currentAgenda!="") {
    var form = new CC_Form("Zum Ablauf hinzufügen");
    form.addSelect({label:"Arrangement", type:"medium", data:allAgendas});
    form.addSelect({label:"Einordnen nach", type:"medium", data:agendaView.currentAgenda.items});
    form.addButton({label:"Auswahl hinzufügen", type:"medium"});
    rows.push(form.render(true));
  }
    */ 
  rows.push("<div id=\"cdb_filtercover\"></div>");
 
  $("#cdb_filter").html(rows.join(""));
   
  $.each(this.filter, function(k,a) {
    $("#"+k).val(a);
  });
   
  // Callbacks 
  filter=this.filter;
  this.implantStandardFilterCallbacks(this, "cdb_filter");
  this.renderCalendar();
};


SongView.prototype.checkFilter = function(a) {
  if (a==null) return false;
  var song=allSongs[a.id];
  if (songView.filter!=null) {
    var filter=songView.filter;
    if ((filter.searchEntry!=null) && (song.bezeichnung.toLowerCase().indexOf(filter.searchEntry.toLowerCase())==-1)
        && (filter.searchEntry!=a.active_arrangement_id) && (filter.searchEntry!="#"+a.id)) 
      return false;
    
    if ((filter.filterSongcategory!=null) 
          && (song.songcategory_id!=filter.filterSongcategory))
      return false;
    
    if (!churchcore_inArray(song.songcategory_id, masterData.auth.viewsongcategory))
      return false;
    
  }
  return true;
};

SongView.prototype.renderFiles = function(filecontainer, arrangement_id) {
  var t=this;
  if (masterData.auth.editsong) {
    t.renderFilelist("", filecontainer, arrangement_id, function(file_id) {
      delete filecontainer[arrangement_id].files[file_id];
      t.renderFiles(filecontainer, arrangement_id);
    });
  }
  else {
    t.renderFilelist("", filecontainer, arrangement_id);
  }
  if (!churchcore_touchscreen()) {
    $("div.filelist[data-id="+arrangement_id+"] span.tooltip-file").each(function() {
      var tooltip=$(this);
      tooltip.tooltips({
        data:{id:tooltip.attr("data-id"), 
              ar_id:tooltip.parent().attr("data-id"),
              song_id:tooltip.parents("div.entrydetail").attr("data-song-id")
             },
        render:function(data) {
          return t.renderTooltipForFiles(tooltip, allSongs[data.song_id].arrangement[data.ar_id].files[data.id], masterData.auth.editsong);            
        },      
        afterRender: function(element, data) {
          return t.tooltipCallbackForFiles(data.id, element, allSongs[data.song_id].arrangement, data.ar_id);          
        }
      });    
    });  
  }
};

SongView.prototype.renderEntryDetail = function(pos_id) {
  var t=this;
  var song=allSongs[pos_id];
  var arrangement=song.arrangement[song.active_arrangement_id];
  
  if (t.songselect!=null) {
    agendaView.addItem(t.songselect.orig_item_id, t.songselect.post, false, arrangement);
    churchInterface.setCurrentView(agendaView);
    t.songselect=null;
    song.open=false;
    return;
  }

  var rows=new Array();
  rows.push('<div class="entrydetail" id="entrydetail_'+pos_id+'" data-song-id="'+song.id+'" data-arrangement-id="'+arrangement.id+'">');  
  
  rows.push('<div class="well">');  
  rows.push('<b style="font-size:140%">'+song.bezeichnung+' - '+arrangement.bezeichnung+'&nbsp; ');
  if (masterData.auth.editsong) 
    rows.push(form_renderImage({src:"options.png", htmlclass:"edit-song", link:true, width:20}));
  rows.push('</b>');

  
  if (song.autor!="")
  rows.push('<br/><small>Autor: '+song.author+'</small>');
  if (song.copyright!="")
    rows.push('<br/><small>Copyright: '+song.copyright+'</small>');
  if (song.ccli!="")
    rows.push('<br/><small>CCLI: '+song.ccli+'</small>');
  rows.push('</div>');
  
  var navi = new CC_Navi();
  $.each(song.arrangement, function(k,a) {
    navi.addEntry(a.id==song.active_arrangement_id,"view-"+a.id,a.bezeichnung);
  });
  if (masterData.auth.editsong)
    navi.addEntry(false,"new",'<i>Erstelle weiteres Arrangement</i>');

  rows.push(navi.render());
  
  
  rows.push('<div class="well"><div class="row-fluid">');
    
    rows.push('<div class="span6">');
      rows.push('<legend>Informationen &nbsp; ');
      if (masterData.auth.editsong)
        rows.push('<a href="#" class="edit">'+t.renderImage("options",20)+'</a>');
      rows.push('</legend>');
      rows.push('<p>Tonart: '+arrangement.tonality);
      rows.push('&nbsp; BPM: '+arrangement.bpm);
      rows.push('&nbsp; Takt: '+arrangement.beat);
      rows.push('<p>L&auml;nge: '+arrangement.length_min+":"+arrangement.length_sec);
      rows.push('<p>Bemerkung:<br/><p><small> '+arrangement.note.htmlize()+'</small>');
    rows.push('</div>');
    rows.push('<div class="span6">');   
    
    rows.push('<legend>Dateien</legend>');
      rows.push('<div class="we_ll">');
      rows.push('<div class="filelist" data-id="'+arrangement.id+'"></div>');
      if (masterData.auth.editsong)
        rows.push('<p><div id="upload_button_'+arrangement.id+'">Nochmal bitte...</div>');
      rows.push('</div>');
    rows.push('</div>');    
  rows.push('</div>');
  if (masterData.auth.editsong && arrangement.default_yn==0) {
    rows.push('<hr/><p>');
    rows.push(form_renderButton({label:"Arrangement zum Standard machen", htmlclass:"makestandard",type:"small"})+"&nbsp; ");
    rows.push(form_renderButton({label:"Arrangement entfernen", htmlclass:"delete", type:"small"})+"&nbsp; ");
  }
  rows.push('</div>');
    
  rows.push('</div>');
  rows.push('<div class="pull-right">');
  if (masterData.auth.editsong) 
    rows.push('<a href="#" class="delete-song">'+form_renderImage({src:"trashbox.png", width:16})+'</a>&nbsp; ');
  rows.push('<small>#'+song.id+'</small></div>');
  $("tr[id=detail" + pos_id + "]").remove();
  var elem=$("tr[id=" + pos_id + "]").after('<tr id="detail' + pos_id + '" class="detail"><td colspan="8" id="detailTD' + pos_id + '">'+rows.join("")+"</td></tr>");

  t.renderFiles(allSongs[song.id].arrangement, arrangement.id);

  if (masterData.auth.editsong) {
    var uploader = new qq.FileUploader({
      element: document.getElementById('upload_button_'+arrangement.id),
      action: "?q=churchservice/uploadfile",
      params: {
        domain_type:"song_arrangement",
        domain_id:arrangement.id
      },
      multiple:true,
      debug:true,
      onSubmit: function() {
      },
      onComplete: function(ok, filename, res) {
        if (res.success) {
          if (arrangement.files==null) arrangement.files=new Object();
          arrangement.files[res.id]={bezeichnung:res.bezeichnung, filename:res.filename, id:res.id, domain_type:"song_arrangement", domain_id:arrangement.id};
          t.renderFiles(allSongs[song.id].arrangement, arrangement.id);
        }
        else {
          alert("Sorry, Fehler beim Hochladen aufgetreten! Datei zu gross oder Dateiname schon vergeben?")
        }
      }
    });
  }

  
  $("td[id=detailTD"+pos_id+"] a.edit").click(function(e) {
    t.editArrangement(song.id, arrangement.id);    
    return false;
  });
  $("td[id=detailTD"+pos_id+"] input.delete").click(function(e) {
    t.deleteArrangement(song.id, arrangement.id);   
    return false;
  });
  $("td[id=detailTD"+pos_id+"] input.makestandard").click(function(e) {
    t.makeAsStandardArrangement(song.id, arrangement.id);    
    return false;
  });
  $("td[id=detailTD"+pos_id+"] a.delete-song").click(function(e) {
    t.deleteSong(song.id);
    return false;
  });
  $("td[id=detailTD"+pos_id+"] a.edit-song").click(function(e) {
    t.editSong(song.id);
    return false;
  });
  
  $("td[id=detailTD"+pos_id+"] ul.nav a").click(function() {
    var id=$(this).attr("id");
    if (id=="new")
      t.addArrangement(song.id);    
    else 
      song.active_arrangement_id=id.substr(5,99);
    t.renderEntryDetail(pos_id);      
    return false;
  });

  
};


SongView.prototype.deleteSong = function(song_id) {
  var t=this;
  if (confirm("Wirklich den Song "+allSongs[song_id].bezeichnung+" entfernen?")) {
    var o=new Object();
    o.func="deleteSong";
    o.id=song_id;
    churchInterface.jsendWrite(o, function(res) {
      t.songsLoaded=false;
      t.loadSongData();
    });
  }  
};

/**
 * Load song data
 * @param song_id and arrangement_id give an id of a song which should be opened
 */
SongView.prototype.loadSongData = function(song_id, arrangement_id) {
  var t=this;
  if (!t.songsLoaded) {
    cs_loadSongs(function() {
      t.songsLoaded=true;
      if (allSongs[song_id]!=null) {
        allSongs[song_id].open=true;
        if (allSongs[song_id].active_arrangement_id==null)
          allSongs[song_id].active_arrangement_id=arrangement_id;
      }
      if (churchInterface.getCurrentView()==t)
        this_object.renderList();
    });
  }
};

SongView.prototype.renderMenu = function() {
  this_object=this;

  menu = new CC_Menu("Men&uuml;");

  if (masterData.auth.editsong)
    menu.addEntry("Neuen Song anlegen", "anewentry", "star");
//  menu.addEntry("Hilfe", "ahelp", "question-sign");

  if (!menu.renderDiv("cdb_menu",churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        this_object.renderAddEntry();
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchService");
      }
      return false;
    });
  }
};

SongView.prototype.editSong = function(song_id) {
  var t=this;
  var song=allSongs[song_id];
  var form = new CC_Form("Infos des Songs", song);

  form.addInput({label:"Bezeichnung",cssid:"bezeichnung",required:true,disabled:!masterData.auth.editsong});
  form.addSelect({label:"Song-Kategorie",cssid:"songcategory_id",data:masterData.songcategory, disabled:!masterData.auth.editsong});
  form.addInput({label:"Autor",cssid:"author", disabled:!masterData.auth.editsong});
  form.addInput({label:"Copyright",cssid:"copyright", disabled:!masterData.auth.editsong});
  form.addInput({label:"CCLI-Nummer",cssid:"ccli", disabled:!masterData.auth.editsong});

  var elem = form_showDialog("Song editieren", form.render(false, "horizontal"), 500,500);
  if (masterData.auth.editsong) {
    elem.dialog('addbutton', 'Speichern', function() {
      obj=form.getAllValsAsObject();
      if (obj!=null) {
        obj.func="editSong";
        obj.id=song_id;
        churchInterface.jsendWrite(obj, function(res) {
          t.songsLoaded=false;
          t.loadSongData();
        });
        $(this).dialog("close");
      }
    });
  }
  elem.dialog('addbutton', 'Abbrechen', function() {
    $(this).dialog("close");
  });
};

SongView.prototype.renderAddEntry = function() {
  var t=this;
  var form = new CC_Form("Bitte Infos des neuen Songs angeben");

  form.addInput({label:"Bezeichnung",cssid:"bezeichnung",required:true});
  form.addSelect({label:"Song-Kategorie",cssid:"songcategory_id",required:true,data:masterData.songcategory,
                    selected:masterData.settings.filterSongcategory});
  form.addInput({label:"Autor",cssid:"author"});
  form.addInput({label:"Copyright",cssid:"copyright"});
  form.addInput({label:"CCLI-Nummer",cssid:"ccli"});

  form.addInput({label:"Tonart", type:"small", cssid:"tonality"});
  form.addInput({label:"BPM", type:"small", cssid:"bpm"});
  form.addInput({label:"Takt", type:"small", cssid:"beat"});

  var elem = form_showDialog("Neuen Song anlegen", form.render(false, "horizontal"), 500,500, {
    "Erstellen": function() {
      var obj=form.getAllValsAsObject();
      if (obj!=null) {
        obj.func="addNewSong";
        churchInterface.jsendWrite(obj, function(ok, res) {
          t.songsLoaded=false;
          t.filter["searchEntry"]='#'+res;
          if (t.filter["filterSongcategory"]!=obj.songcategory_id) {
            delete t.filter["filterSongcategory"];
            delete masterData.settings.filterSongcategory;
          }
          t.renderFilter();
          t.loadSongData();
        });
        $(this).dialog("close");
      }
    },
    "Abbrechen": function() {
      $(this).dialog("close");
    }
  });
};

function processFieldInput(elem, save) {
  var oldval=elem.parents("td").attr("data-oldval");
  elem.parents("td").removeAttr('data-oldval');
  if (save) {
    newval=elem.val();
    elem.parents("td").html(newval);
  }
  else {
    elem.parents("td").html(oldval);
  }
}

SongView.prototype.addFurtherListCallbacks = function(cssid) {
  var t=this;
  $("#cdb_content a.show-arrangement").click(function() {
    var song_id=$(this).parents("tr").attr("id");
    var arr_id=$(this).attr("data-arrangement-id");
    allSongs[song_id].active_arrangement_id=arr_id;
    t.renderList(allSongs[song_id]);
    return false;
  });
  
  $("#cdb_content a.edit-song").click(function(e) {
    t.editSong($(this).parents("tr").attr("id"));
    return false;
  });
};

SongView.prototype.editArrangement = function(song_id, arrangement_id) {    
  var t=this;
  var arrangement=allSongs[song_id].arrangement[arrangement_id];
  var form = new CC_Form("Bitte Arrangement anpassen", arrangement);

  form.addInput({label:"Bezeichnung",cssid:"bezeichnung",required:true});
  form.addInput({label:"Tonart",cssid:"tonality"});
  form.addInput({label:"BPM",cssid:"bpm"});
  form.addInput({label:"Takt",cssid:"beat"});
  form.addInput({label:"L&auml;nge Minuten:Sekunden",type:"mini",cssid:"length_min",controlgroup_start:true});
  form.addHtml(" : ");
  form.addInput({controlgroup_end:true, type:"mini",cssid:"length_sec"});
  form.addTextarea({label:"Bemerkungen",rows:3, cssid:"note"});

  var elem = form_showDialog("Arrangement editieren", form.render(false, "horizontal"), 500,500, {
    "Absenden": function() {
      obj=form.getAllValsAsObject();
      if (obj!=null) {
        obj.func="editArrangement";
        obj.id=arrangement_id;
        churchInterface.jsendWrite(obj, function(res) {
          t.songsLoaded=false;
          t.loadSongData(song_id, arrangement_id);
        });
        $(this).dialog("close");
      }
    },
    "Abbrechen": function() {
      $(this).dialog("close");
    }
  });  
};

SongView.prototype.deleteArrangement = function(song_id, arrangement_id) {    
  var t=this;
  var arrangement=allSongs[song_id].arrangement[arrangement_id];
  if (arrangement.files!=null) {
    alert("Bitte zuerst die Dateien entfernen!");
    return null;
  }
  if (confirm("Wirklich Arrangement "+arrangement.bezeichnung+" entfernen?")) {
    var o=new Object();
    o.func="delArrangement";
    o.song_id=song_id;
    o.id=arrangement_id;
    churchInterface.jsendWrite(o, function(res) {
      t.songsLoaded=false;
      t.loadSongData(song_id);
    });
  }
};

SongView.prototype.makeAsStandardArrangement = function(song_id, arrangement_id) {    
  var t=this;
  var o=new Object();
  o.func="makeAsStandardArrangement";
  o.id=arrangement_id;
  o.song_id=song_id;
  churchInterface.jsendWrite(o, function(res) {
    t.songsLoaded=false;
    t.loadSongData(song_id, arrangement_id);
  });
};

SongView.prototype.addArrangement = function(song_id) {    
  var t=this;
  var o=new Object();
  o.func="addArrangement";
  o.song_id=song_id;
  o.bezeichnung="Neues Arrangement";
  churchInterface.jsendWrite(o, function(ok, res) {
    t.songsLoaded=false;
    t.open=true;
    t.renderFilter();
    t.loadSongData(song_id, res);
  });
};

SongView.prototype.getCountCols = function() {
  if (this_object.filter["filterSongcategory"]==null)
    return 6;
  else
    return 5;
};


SongView.prototype.getListHeader = function () {
  var t=this;

  if (t.songselect!=null) {
    $.each(allSongs, function(k,a) {
      a.open=false;
    });
    t.filter["searchStandard"]="true";
    form = new CC_Form();
    form.addHtml('<legend>Bitte einen Song auswählen!</legend>');
    form.addHtml('<P>Um einen Song dem Ablauf hinzuzufügen bitte das gewünschte Arrangement aktivieren und dann auf den Songnamen klicken</p>');
    form.addButton({label:"Auswählen abbrechen", htmlclass:"cancel"});
    $("#cdb_group").html(form.render(true));
  }
  else {
    $("#cdb_group").html("");
  }
  
  $("#cdb_group input.cancel").click(function() {
    t.songselect=null;
    t.renderView();
  });

  
  if (masterData.settings.listViewTableHeight==null) masterData.settings.listViewTableHeight=1;
  
  
  this.loadSongData();
  var rows = new Array();
  if (masterData.settings.listViewTableHeight==0)
    songView.listViewTableHeight=null;
  else
    songView.listViewTableHeight=665;

  rows.push('<th>Nr.');
    
  rows.push('<th>Bezeichnung');
  if (t.filter["filterSongcategory"]==null)
    rows.push('<th>Kategorie');
  rows.push('<th class="hidden-phone">Tonart<th class="hidden-phone">BPM<th class="hidden-phone">Takt');

  return rows.join("");
};

SongView.prototype.groupingFunction = function() {
  return null;
};

SongView.prototype.getDefaultArrangement = function(song) {
  if (song==null) return null;
  var ret=null;
  $.each(song.arrangement, function(k,a) {
    ret=a;
    if (a.default_yn==1) return false;
  });
  return ret;  
};

SongView.prototype.renderListEntry = function (list) {
  var t=this;
  var rows = new Array();
  var song=allSongs[list.id];
  if (song.active_arrangement_id==null) {
    var arr=t.getDefaultArrangement(song);
    if (arr!=null) song.active_arrangement_id=arr.id;
  }
  var arr=song.arrangement[song.active_arrangement_id];

  rows.push('<td class="hoveractor"><a href="#" id="detail'+list.id+'">'+song.bezeichnung+"</a>");
  rows.push('&nbsp; <i><small>'+song.author.trim(50)+'</small></i>');
  // Only nice to have, so not displayed when working on touchscreens
  if (masterData.auth.editsong!=null && !churchcore_touchscreen()) 
    rows.push('&nbsp; <span class="hoverreactor"><a href="#" class="edit-song" data-id="'+list.song_id+'">'+form_renderImage({src:"options.png", width:16})+'</a></span>');
  if (this.filter.searchStandard!=null) {
    rows.push("<br/>");
    $.each(song.arrangement, function(k,a) {
      var txt=a.bezeichnung;
      if (a.id==song.active_arrangement_id)
        rows.push('<span class="label label-info">'+txt+'</span> ');
      else
        rows.push('<a href="#" class="show-arrangement" data-arrangement-id="'+a.id+'"><span class="label">'+txt+'</span></a> ');
    });
  }

  if (this_object.filter["filterSongcategory"]==null)
    rows.push('<td>'+masterData.songcategory[song.songcategory_id].bezeichnung);
  rows.push('<td class="hidden-phone">'+arr.tonality);
  rows.push('<td class="hidden-phone">'+arr.bpm);
  rows.push('<td class="hidden-phone">'+arr.beat);

  return rows.join("");
};


SongView.prototype.messageReceiver = function(message, args) {
  var this_object = this;
  if (message=="allDataLoaded") {
    this.allDataLoaded=true;
    if (this==churchInterface.getCurrentView())
      this.loadSongData();
  }
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      this_object.renderList();
    }
  }
  if (message=="filterChanged") {
    if (args[0]=="filterSongcategory" || args[0]=="searchStandard") {
      if (args[0]=="searchStandard")
        masterData.settings[args[0]]=$("#"+args[0]).attr("checked")=="checked";
      else
        masterData.settings[args[0]]=$("#"+args[0]).val();
      churchInterface.jsendWrite({func:"saveSetting", sub:args[0], val:masterData.settings[args[0]]});
    }
  }
};

SongView.prototype.addSecondMenu = function() {
  return '';
};
