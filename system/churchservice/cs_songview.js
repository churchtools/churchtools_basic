// Constructor
function SongView() {
  ListView.call(this);
  this.name="SongView";
  this.songsLoaded=false;
  this.allDataLoaded=false;
  this.sortVariable="bezeichnung";
}

Temp.prototype = ListView.prototype;
SongView.prototype = new Temp();
songView = new SongView();


SongView.prototype.getData = function(sorted) {
  if (sorted) {
    var list=new Array();
    if (allSongs!=null)
      $.each(churchcore_sortData(allSongs,"bezeichnung"), function(k,song) {
        $.each(song.arrangement, function(i,arr) {
          var a=new Array();
          a.song_id=song.id;
          a.arrangement_id=arr.id;
          a.id=song.id+"_"+arr.id;
          list.push(a);
        });
      });
    return list;
  }
  else {
    var list=new Object();
    if (allSongs!=null)
      $.each(allSongs, function(k,song) {
        $.each(song.arrangement, function(i,arr) {
          var a=new Array();
          a.song_id=song.id;
          a.arrangement_id=arr.id;
          a.id=song.id+"_"+arr.id;
          list[a.id]=a;
        });
      });
    return list;
  }
};


SongView.prototype.renderFilter = function () {
  var t=this;
  
  if ((masterData.settings.filterSongcategory=="") || (masterData.settings.filterSongcategory==null))
    delete masterData.settings.filterSongcategory;
  else t.filter["filterSongcategory"]=masterData.settings.filterSongcategory;
  
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
 
  form.addCheckbox({cssid:"searchStandard",label:"Nur Standard-Arrangement"});  
  rows.push(form.render(true));
     
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


SongView.prototype.groupingFunction = function (list) {
  if (this.filter.searchStandard==null) {
    var txt="";
    txt=txt+"<b>"+allSongs[list.song_id].bezeichnung+"</b>";
    if (allSongs[list.song_id].author!="")
      txt=txt+'&nbsp; <small>'+allSongs[list.song_id].author+'</small>';
    txt=txt+'&nbsp; <a href="#" class="edit-song" data-id="'+list.song_id+'">'+form_renderImage({src:"options.png", width:16})+'</a>';
    return txt;
  }
  else return null;
};

SongView.prototype.checkFilter = function(a) {
  if (a==null) return false;
  var song=allSongs[a.song_id];
  var arrangement=song.arrangement[a.arrangement_id];
  if (songView.filter!=null) {
    var filter=songView.filter;
    if ((filter.searchEntry!=null) && (song.bezeichnung.toLowerCase().indexOf(filter.searchEntry.toLowerCase())==-1)
        && (filter.searchEntry!=a.arrangement_id))
      return false;
    
    if ((filter.filterSongcategory!=null) 
          && (song.songcategory_id!=filter.filterSongcategory))
      return false;
    
    if (!churchcore_inArray(song.songcategory_id, masterData.auth.viewsongcategory))
      return false;
    
    if ((filter.searchStandard) && (arrangement.default_yn==0))
      return false;
  }
  return true;
};

SongView.prototype.renderTooltip = function(tooltip, divid) {
  var song_id=tooltip.parents("div.entrydetail").attr("data-song-id");
  var ar_id=tooltip.parent().attr("data-id");
  var id=tooltip.attr("tooltip");
  
  return this.renderTooltipForFiles(tooltip, divid, allSongs[song_id].arrangement[ar_id].files[id], masterData.auth.editsong);  
};

SongView.prototype.tooltipCallback = function(id, tooltip) {
  var song_id=tooltip.parents("div.entrydetail").attr("data-song-id");
  var ar_id=tooltip.parent().attr("data-id");
  return this.tooltipCallbackForFiles(id, tooltip, allSongs[song_id].arrangement, ar_id);
};

SongView.prototype.renderFiles = function(filecontainer, arrangement_id) {
  var t=this;
  t.clearTooltip(true);
  if (masterData.auth.editsong) {
    t.renderFilelist("", filecontainer, arrangement_id, function(file_id) {
      delete filecontainer[arrangement_id].files[file_id];
      t.renderFiles(filecontainer, arrangement_id);
    });
  }
  else {
    t.renderFilelist("", filecontainer, arrangement_id);
  }
  $("div.filelist[data-id="+arrangement_id+"] span[tooltip],a[tooltip]").hover(
      function() {
        drin=true;
        this_object.prepareTooltip($(this), null, $(this).attr("data-tooltiptype"));
      }, 
      function() {
        drin=false;
        window.setTimeout(function() {
          if (!drin)
            this_object.clearTooltip();
        },100);
      }
    );   
};

SongView.prototype.renderEntryDetail = function(pos_id) {
  var t=this;
  var i=pos_id.indexOf('_');
  var song=allSongs[pos_id.substr(0,i)];
  var arrangement=song.arrangement[pos_id.substr(i+1,99)];

  var rows=new Array();
  rows.push('<div class="entrydetail" id="entrydetail_'+pos_id+'" data-song-id="'+song.id+'" data-arrangement-id="'+arrangement.id+'">');  
  
  rows.push('<div class="well">');  
  rows.push('<b style="font-size:140%">'+song.bezeichnung+' - '+arrangement.bezeichnung+'</b>');
  
  if (song.autor!="")
  rows.push('<br/><small>Autor: '+song.author+'</small>');
  if (song.copyright!="")
    rows.push('<br/><small>Copyright: '+song.copyright+'</small>');
  if (song.ccli!="")
    rows.push('<br/><small>CCLI: '+song.ccli+'</small>');
  rows.push('</div>');
  

  
  rows.push('<div class="row-fluid">');
    
    rows.push('<div class="span6">');
      rows.push('<legend>Informationen &nbsp; ');
      if (masterData.auth.editsong)
        rows.push('<a href="#" class="edit">'+t.renderImage("options",20)+'</a>');
      rows.push('</legend>');
      rows.push('<p>Tonart: '+arrangement.tonality);
      rows.push('&nbsp; BPM: '+arrangement.bpm);
      rows.push('&nbsp; Takt: '+arrangement.beat);
      rows.push('<p>L&auml;nge: '+arrangement.length_min+":"+arrangement.length_sec);
      rows.push('<p>Bemerkung:<br/><p><small> '+arrangement.note.replace(/\n/g,'<br/>')+'</small>');
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
  if (masterData.auth.editsong) {
    rows.push('<hr/><p>');
    rows.push(form_renderButton({label:"Weiteres Arrangement hinzuf&uuml;gen", htmlclass:"add", type:"small"})+"&nbsp; ");
    if (arrangement.default_yn==0) {
      rows.push(form_renderButton({label:"Zum Standard machen", htmlclass:"makestandard",type:"small"})+"&nbsp; ");
      rows.push(form_renderButton({label:"Arrangement entfernen", htmlclass:"delete", type:"small"})+"&nbsp; ");
    }
  }
    
  rows.push('</div>');
  var elem=$("tr[id=" + pos_id + "]").after("<tr id=\"detail" + pos_id + "\"><td colspan=\"7\" id=\"detailTD" + pos_id + "\">"+rows.join("")+"</td></tr>");

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
  $("td[id=detailTD"+pos_id+"] input.add").click(function(e) {
    t.addArrangement(song.id);    
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
  //t.addTableContentCallbacks("#cdb_content td[id=detailTD"+pos_id+"]");
  

  
};

SongView.prototype.loadSongData = function() {
  if ((!this.songsLoaded) && (this.allDataLoaded)) {
    var elem = this.showDialog("Lade Songs", "Lade Songs...", 300,300);
    cs_loadSongs(function() {
      this_object.songsLoaded=true;
      elem.dialog("close");
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
        churchInterface.jsendWrite(obj, function(res) {
          t.songsLoaded=false;
          t.filter["searchEntry"]=obj.bezeichnung;
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
  
  /*
  $("td.editable").hover(
    function() {
      $(this).addClass("active");
    },
    function() {
      $(this).removeClass("active");
    }
  );
  $("td.editable").click(function() {
  	if ($(this).attr("data-oldval")==null) {
  	  if ($("#inputFieldData").length==1) {
  	    processFieldInput($("#inputFieldData"), true);
  	  }

	    var pos_id=$(this).parents("tr").attr("id");
	    var i=pos_id.indexOf('_');
	    var song=allSongs[pos_id.substr(0,i)];
	    var arrangement=song.arrangement[pos_id.substr(i+1,99)];

	    var val=$(this).text();
	    $(this).attr("data-oldval",val);
	    var rows = new Array();
	    rows.push('<div id="inputData" class="input-append">');
	      rows.push(form_renderInput({value:val, cssid:"inputFieldData", controlgroup:false, type:"small"}));
        rows.push('<button class="btn ok" type="button"><i class="icon-ok"></i></button>');
        rows.push('<button class="btn remove" type="button"><i class="icon-remove"></button>');
      rows.push('</div>');
	    rows.push();
	    $(this).html(rows.join(""));
      $('#inputData button').click(function() {
        processFieldInput($("#inputFieldData"), $(this).hasClass('ok'));
        return false;
      });
	    $("#inputFieldData").focus();
	    $('#inputFieldData').keyup(function(e) {
	      // Enter
	      if (e.keyCode == 13)
          processFieldInput($("#inputFieldData"), true);
	      // Escape
	      else if (e.keyCode == 27)
          processFieldInput($("#inputFieldData"), false);
	    });
	   }
  });*/

  $("#cdb_content a.edit-song").click(function(e) {
    t.editSong($(this).attr("data-id"));
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
      t.loadSongData();
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
    t.loadSongData();
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
    t.filter["searchEntry"]=res;
    t.renderFilter();
    t.loadSongData();
  });
};

SongView.prototype.getCountCols = function() {
  return 6;
};


SongView.prototype.getListHeader = function () {
  var this_object=this;
  this.loadSongData();
  var rows = new Array();
  if (masterData.settings.listViewTableHeight==0)
    songView.listViewTableHeight=null;
  else
    songView.listViewTableHeight=665;

  if (this.filter.searchStandard!=null) 
    rows.push('<th>Nr.');
    
  rows.push('<th>Bezeichnung<th>Tonart<th>BPM<th>Takt<th>Tags');

  return rows.join("");
};

SongView.prototype.renderListEntry = function (list) {
  var rows = new Array();
  var song=allSongs[list.song_id];
  var arr=song.arrangement[list.arrangement_id];
  if (this.filter.searchStandard==null) {
    rows.push('<td><a href="#" id="detail'+list.id+'">'+arr.bezeichnung+"</a>");
    if (arr.default_yn==1) rows.push(" *");
  }
  else {
    rows.push('<td><a href="#" id="detail'+list.id+'">'+song.bezeichnung+"</a>");
    if (masterData.auth.editsong!=null) 
      rows.push('&nbsp; <a href="#" class="edit-song" data-id="'+list.song_id+'">'+form_renderImage({src:"options.png", width:16})+'</a>');

  }
  rows.push('<td>'+arr.tonality);
  rows.push('<td>'+arr.bpm);
  rows.push('<td>'+arr.beat);
  rows.push("<td>");

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
    if (args[0]=="filterSongcategory") {
      masterData.settings.filterSongcategory=$("#filterSongcategory").val();
      churchInterface.jsendWrite({func:"saveSetting", sub:"filterSongcategory", val:masterData.settings.filterSongcategory});
    }
  }
};

SongView.prototype.addSecondMenu = function() {
  return '';
};
