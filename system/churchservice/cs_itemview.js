(function($) {

// Constructor
function ItemView() {
  ListView.call(this);
  this.name="ItemView";
  this.currentAgenda=null;
  this.allDataLoaded=false;
}

Temp.prototype = ListView.prototype;
ItemView.prototype = new Temp();
itemView = new ItemView();

allAgendas=null;


ItemView.prototype.getData = function(sorted) {
  if (allAgendas==null || this.currentAgenda==null) return null;
  if (sorted) {
    var header="Vorbereitung";
    var arr=new Array();
    if (allAgendas[this.currentAgenda].items!=null)
    $.each(churchcore_sortData(allAgendas[this.currentAgenda].items, "sortkey"), function(k,a) {
      if (a.header_yn==1) {
        header=a.desc;
        if (a.duration!=0) {
          header=header+"&nbsp; ";
          if (a.duration % 60==0) header=header+a.duration/60+"min";
          else header=header+a.duration.formatMS();
        }
      }
      else {
        if ((a.preservice_yn==0) && (header=="Vorbereitung")) header="Veranstaltung"
        a.header=header;
        arr.push(a);        
      }
    });
    return arr;
  }
  else
    return allAgendas[this.currentAgenda].items;
};

ItemView.prototype.groupingFunction = function(event) {
  return event.header;
};

ItemView.prototype.renderMenu = function() {
  this_object=this;

  menu = new CC_Menu("Men&uuml;");

//  if (masterData.auth.write)
//    menu.addEntry("Neues Event anlegen", "anewentry", "star");
//  menu.addEntry("Fakten exportieren", "aexport", "share");
  menu.addEntry("Hilfe", "ahelp", "question-sign");

  if (!menu.renderDiv("cdb_menu",churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {
      $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        this_object.renderAddEntry();
      }
      else if ($(this).attr("id")=="aexport") {
        churchcore_openNewWindow("?q=churchservice/exportfacts");
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchService");
      }
      return false;
    });
  }
};


ItemView.prototype.getCountCols = function() {
  return 10;
};


ItemView.prototype.addFurtherListCallbacks = function(cssid) {
  var t=this;
  $("#cdb_content .hoveractor").hover(
      function () {
        $(this).children("span.hoverreactor").fadeIn('fast',function() {});
      }, 
      function () {
        $(this).children("span.hoverreactor").fadeOut('fast');
      }
    );
  
  var fixHelperModified = function(e, tr) {
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function(index) {
        $(this).width($originals.eq(index).width());
    });
    return $helper;
  };
  var updateIndex = function(e, ui) {
    $('td.index', ui.item.parent()).each(function (i) {
      $(this).html(i + 1);
    });
  };

  $("tbody").sortable({
      helper: fixHelperModified,
      stop: updateIndex
  }).disableSelection();
  
  $(cssid+" a").click(function (a) {
    // Person zu einer Kleingruppe dazu nehmen
    if ($(this).attr("id")==null) 
      return true;
    else if ($(this).attr("id").indexOf("addMoreCols")==0) {
      t.addMoreCols();
    }
    else if ($(this).attr("id").indexOf("delCol")==0) {
      var id=$(this).attr("id").substr(6,99);
      masterData.settings["viewgroup"+id]=0;
      churchInterface.jsendWrite({func:"saveSetting", sub:"viewgroup"+id, val:0});
      t.renderList();
    }
  });
};

ItemView.prototype.renderFilter = function() {
  var t=this;
  
  var form = new CC_Form("Event");
  
  var arr=new Array();
  if (allAgendas!=null) {
    $.each(churchcore_sortData(allAgendas, "desc"), function(k,a) {
      arr.push({id:a.id, bezeichnung:a.desc.trim(50)});
    });
  }
  
  form.addSelect({data:arr, sort:false, cssid:"event", selected:t.currentAgenda, freeoption:true});
  
  $("#cdb_filter").html(form.render(true));
  $("#cdb_filter").find("#event").change(function() {
    t.currentAgenda=$(this).val();
    t.renderList();
  });
};


ItemView.prototype.getListHeader = function () {
  var t=this;
  
  if (allAgendas==null) {
    songView.loadSongData();
    var elem=form_showCancelDialog("Lade...", "Lade Daten..", 300, 300);
    churchInterface.jsendRead({func:"loadAgendas"}, function(ok, data) {
      elem.dialog("close");
      allAgendas=new Array();
      if (!ok) alert("Fehler beim Laden der Daten: "+data);
      else {
        if (data!=null) allAgendas=data;
        t.renderView();
        return;
      }
    });
  }
  if ((t.currentAgenda!=null) && (allAgendas[t.currentAgenda].items==null)) {
    var elem=form_showCancelDialog("Lade...", "Lade Daten..", 300, 300);
    churchInterface.jsendRead({func:"loadAgendaItems", agenda_id:t.currentAgenda}, function(ok, data) {
      elem.dialog("close");
      allAgendas[t.currentAgenda].items=new Object();
      if (!ok) alert("Fehler beim Laden der Daten: "+data);
      else {
        if (data!=null)
          allAgendas[t.currentAgenda].items=data;
        t.renderList();
        return;
      }
    });        
  }
  
  if ((t.currentAgenda==null) && ($("#externevent_id").val()!=null))
    t.currentAgenda=$("#externevent_id").val();
  
  if ((t.currentAgenda==null) || (allAgendas[t.currentAgenda]==null)) {
      
    $("#cdb_group").html("Kein Event ausgew&auml;hlt");
    return;
  }
  
  a=allAgendas[t.currentAgenda];
  var form = new CC_Form();
  form.addHtml('<legend>'+a.desc+'</legend>');
  $("#cdb_group").html(form.render(true));
  
  
  var rows = new Array();
  rows.push('<th>L&auml;nge<th>Text<th>Verantwortlich');
  
  $.each(this.sortMasterData(masterData.servicegroup), function(k,a) {
    if ((masterData.settings["viewgroup"+a.id]==null) || (masterData.settings["viewgroup"+a.id]==1))
      if ((masterData.auth.viewgroup[a.id]) || (this_object.filter["filterMeine Filter"]==2)) {
        rows.push('<th class="hoveractor" id="header'+a.id+'">'+a.bezeichnung);
        rows.push('<span class="hoverreactor" style="display:none;float:right">');
        rows.push('<a href="#" id="delCol'+a.id+'">'+form_renderImage({src:"minus.png",width:16})+'</a> ');
        rows.push('</span>');
      }
  });
  rows.push('<th width="16px"><a href="#" id="addMoreCols">'+this.renderImage("plus",16)+'</a>');
  
  rows.push('<th>'+form_renderImage({src:"paperclip.png", width:18}));

  return rows.join("");
};

ItemView.prototype.renderListEntry = function (event) {
  var rows = new Array();  
  rows.push('<td>'+event.duration.formatMS());
  var song=null;
  var desc=event.desc;
  if (event.arrangement_id!=null) {
    var song=songView.getSongFromArrangement(event.arrangement_id);
    if (song!=null) {
      if (desc=="") desc=song.bezeichnung;
      else desc=desc+ " ("+song.bezeichnung+')';
    }
  }
  rows.push('<td class="hoveractor"><b>'+desc+'</b>');
  rows.push('&nbsp; <span class="hoverreactor" style="display:none">');
  rows.push('<a href="#" class="edit-item" data-id="'+event.id+'">'+form_renderImage({src:"options.png", width:16})+'</a> ');
  rows.push('<a href="#" class="add-item" data-id="'+event.id+'">'+form_renderImage({src:"plus.png",width:16})+'</a> ');  
  rows.push('</span>');
  if (event.note!="")
    rows.push('<div class="event_info">'+event.note.trim(40)+"</div>");
  rows.push('<td>'+event.responsible);
  
  $.each(this.sortMasterData(masterData.servicegroup), function(k,a) {
    if ((masterData.settings["viewgroup"+a.id]==null) || (masterData.settings["viewgroup"+a.id]==1))
      if ((masterData.auth.viewgroup[a.id]) || (this_object.filter["filterMeine Filter"]==2)) {
        rows.push('<td>');        
        if ((event.servicegroup!=null) && (event.servicegroup[a.id]!=null))
          rows.push('<small>'+event.servicegroup[a.id]+'</small>');
      }
  });
  rows.push('<td><td>');
  if (song!=null) {
    rows.push(form_renderImage({src:"paperclip.png", width:20}));
  }
  return rows.join("");
};

ItemView.prototype.messageReceiver = function(message, args) {
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

ItemView.prototype.addSecondMenu = function() {
  return '';
};


})(jQuery);
