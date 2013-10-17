(function($) {

// Constructor
function ItemView() {
  ListView.call(this);
  this.name="ItemView";
  this.currentEvent=null;
  this.allDataLoaded=false;
}

Temp.prototype = ListView.prototype;
ItemView.prototype = new Temp();
itemView = new ItemView();


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
  return 2;
};

ItemView.prototype.groupingFunction = function (event) {
  var tagDatum=event.startdate.toDateEn(false).toStringDe();
  var merker = new Object;
  $.each(allEvents, function(k,a) {
    if (a.startdate.toDateEn(false).toStringDe()==tagDatum) {
      if (a.facts!=null)
        $.each(a.facts, function(i,b) {
          if (merker[i]==null) merker[i]=0;
          merker[i]=merker[i]+b.value*1;
        });
    }
  });
  var txt=event.startdate.toDateEn(false).getDayInText()+", "+event.startdate.toDateEn(false).toStringDe();

  $.each(churchcore_sortMasterData(masterData.fact), function(k,a) {
    txt=txt+'<td class="grouping">';
    if (merker[a.id]!=null)
      txt=txt+merker[a.id];
  });
  return txt;
};


ItemView.prototype.addFurtherListCallbacks = function(cssid) {
  var t=this;
  $(".hover").hover(
      function () {
        $('#headerspan'+$(this).attr("id").substr(6,99)).fadeIn('fast',function() {});
      }, 
      function () {
        $('#headerspan'+$(this).attr("id").substr(6,99)).fadeOut('fast');
      }
    );
  
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
      churchInterface.jsonWrite({func:"saveSetting", sub:"viewgroup"+id, val:0});
      t.renderList();
    }
  });
};

ItemView.prototype.renderFilter = function() {
  var t=this;
  
  var form = new CC_Form("Event");
  
  var arr=new Array();
  $.each(churchcore_sortData(allEvents, "startdate"), function(k,a) {
    arr.push({id:a.id, bezeichnung:a.startdate.toDateEn(true).toStringDe(true)+" - "+a.bezeichnung.trim(50)});
  });
  
  form.addSelect({data:arr, sort:false, cssid:"event", selected:t.currentEvent});
  
  $("#cdb_filter").html(form.render(true));
  $("#cdb_filter").find("#event").change(function() {
    t.currentEvent=$(this).val();
    t.renderList();
  });
 
};


ItemView.prototype.getListHeader = function () {
  var t=this;
  
  if ((t.currentEvent==null) && ($("#externevent_id").val()!=null))
    t.currentEvent=$("#externevent_id").val();
  
  if ((t.currentEvent==null) || (allEvents[t.currentEvent]==null)) {
      
    $("#cdb_group").html("Kein Event ausgew&auml;hlt");
    return;
  }
  a=allEvents[t.currentEvent];
  var form = new CC_Form();
  form.addHtml('<legend>'+a.bezeichnung+' - '+a.startdate.toDateEn(true).toStringDe(true)+'</legend>');
  $("#cdb_group").html(form.render(true));
  
  
  var rows = new Array();
  rows.push('<th>L&auml;nge<th>Text<th>Verantwortlich');
  
  $.each(this.sortMasterData(masterData.servicegroup), function(k,a) {
    if ((masterData.settings["viewgroup"+a.id]==null) || (masterData.settings["viewgroup"+a.id]==1))
      if ((masterData.auth.viewgroup[a.id]) || (this_object.filter["filterMeine Filter"]==2)) {
        rows.push('<th class="hover" id="header'+a.id+'">'+a.bezeichnung);
        rows.push('<span id="headerspan'+a.id+'" style="display:none;float:right">'+
                '<a href="#" id="delCol'+a.id+'">'+this_object.renderImage("minus",16)+'</a></span>');
      }
  });
  rows.push('<th width="16px"><a href="#" id="addMoreCols">'+this.renderImage("plus",16)+'</a>');
  
  rows.push('<th>'+form_renderImage({src:"paperclip.png", width:18}));

  return rows.join("");
};

ItemView.prototype.renderListEntry = function (event) {
  var rows = new Array();
 

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
