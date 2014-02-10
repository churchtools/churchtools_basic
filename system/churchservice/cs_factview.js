(function($) {

// Constructor
function FactView() {
  ListView.call(this);
  this.name="FactView";
  this.currentDate=new Date();
  this.currentDate=this.currentDate.toStringDe(false).toDateDe(false);
  this.factLoaded=false;
  this.allDataLoaded=false;
}

Temp.prototype = ListView.prototype;
FactView.prototype = new Temp();
factView = new FactView();


FactView.prototype.renderMenu = function() {
  this_object=this;

  menu = new CC_Menu("Men&uuml;");

  if (masterData.auth.write)
    menu.addEntry("Neues Event anlegen", "anewentry", "star");
  if (masterData.auth.exportfacts)
    menu.addEntry("Fakten exportieren", "aexport", "share");
  menu.addEntry("Hilfe", "ahelp", "question-sign");

  if (!menu.renderDiv("cdb_menu",churchcore_handyformat()))
    $("#cdb_menu").hide();
  else {
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="anewentry") {
        this_object.renderAddEntry();
      }
      else if ($(this).attr("id")=="aexport") {
        var rows=new Array();
        rows.push('<legend>Zeitraum des Exportes</legend>');
        rows.push('<p>Es k&ouml;nnen entweder alle Fakten exportiert werden, '+ 
                     'oder die Fakten ab dem aktuell ausgew&auml;hlten Datum.');
        var elem=form_showDialog('Export von Fakten', rows.join(""), 370, 300, {
            "Alle Fakten": function() {    
                churchcore_openNewWindow("?q=churchservice/exportfacts");
                elem.dialog("close");
            },
            "Ab aktuellem Datum": function() {    
              churchcore_openNewWindow("?q=churchservice/exportfacts&date="+this_object.currentDate.toStringEn(false));
              elem.dialog("close");
            },
            "Abbruch": function() {    
              elem.dialog("close");
            }
        });
      }
      else if ($(this).attr("id")=="ahelp") {
        churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchService");
      }
      return false;
    });
  }
};

FactView.prototype.renderEntryDetail = function (event_id) {
  
};


FactView.prototype.getCountCols = function() {
  return 2;
};

FactView.prototype.groupingFunction = function (event) {
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

FactView.prototype.addFurtherListCallbacks = function(cssid) {
  var t=this;
  
  if (masterData.auth.editfacts) {

    // Implements editable
    $(cssid+" td.editable").each(function(k,a) {
      var event_id=$(this).attr("event_id");
      var fact_id=$(this).attr("fact_id");
      $(this).editable({
        
        type: ($(this).hasClass("textarea")?"textarea":"input"),
        
        data: {event_id:event_id, fact_id:fact_id},
        
        autosaveSeconds: 5,
        
        rerenderEditor: 
          function(txt) {
            return txt.replace(",",".");
          },
        
        validate:
          function(newval, data) {
            if (!isNumber(newval) && newval!="") {
              alert("Bitte Zahl angeben oder Feld leer lassen!");
              return false;
            }
            return true;
          },
        
        success:
          function(newval, data) {
            if (allEvents[data.event_id].facts==null)
              allEvents[data.event_id].facts=new Object();
            o=$.extend({}, data);
            o.value=newval;
            o.func="saveFact";
            churchInterface.jsendWrite(o, function(ok, data) {
              if (!ok) alert("Fehler beim Speichern: "+data);
              else {
                allEvents[event_id].facts[fact_id]=o;
              }  
            });
          },
        
        value: ((event_id!=null) && (allEvents[event_id].facts!=null) && (allEvents[event_id].facts[fact_id]!=null)?
            allEvents[event_id].facts[fact_id].value:null)
                 
      });
    });    
  }
};

FactView.prototype.getListHeader = function () {
  var this_object=this;
  
  $("#cdb_group").html("");
  
  if ((masterData.settings.filterCategory=="") || (masterData.settings.filterCategory==null))
    delete masterData.settings.filterCategory;
  if (this.filter["filterKategorien"]==null) {
    this_object.makeFilterCategories(masterData.settings.filterCategory);
    this.filter["filterKategorien"].setSelectedAsArrayString(masterData.settings.filterCategory);
  }
  this.filter["filterKategorien"].render2Div("filterKategorien", {label:"Kategorien"});

  if ((!this.factLoaded) && (this.allDataLoaded)) {
    var elem = this.showDialog("Lade Faktendaten", "Lade Faktendaten...", 300,300);
    cs_loadFacts(function() {
      this_object.factLoaded=true;
      elem.dialog("close");
      this_object.renderList();
    });
  }
  var rows = new Array();
  if (masterData.settings.listViewTableHeight==0)
    factView.listViewTableHeight=null;
  else
    factView.listViewTableHeight=665;

  rows.push('<th>Events');
  $.each(churchcore_sortData(masterData.fact,"sortkey"), function(k,a){
    rows.push('<th>'+a.bezeichnung);
  });

  return rows.join("");
};

FactView.prototype.renderListEntry = function (event) {
  var rows = new Array();
  var width=100/(1+churchcore_countObjectElements(masterData.fact));
  rows.push('<td width="'+width+'%">' + event.startdate.toDateEn(true).toStringDeTime(true)+" "+event.bezeichnung);
  if (event.special!=null) {
    rows.push("<div class=\"event_info\">"+event.special.htmlize()+"</div>");
  }
  var cl="";
  if (masterData.auth.editfacts) cl="editable";
  $.each(churchcore_sortData(masterData.fact,"sortkey"), function(k,a) {
    rows.push('<td width="'+width+'%" class="'+cl+'" event_id="'+event.id+'" fact_id="'+a.id+'">');
    if ((event.facts!=null) && (event.facts[a.id]!=null))
      rows.push(event.facts[a.id].value);
    else (rows.push(""));
  });


  return rows.join("");
};

FactView.prototype.messageReceiver = function(message, args) {
  var this_object = this;
  if (message=="allDataLoaded")
    this.allDataLoaded=true;
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      this_object.renderList();
    }
  }
};

FactView.prototype.addSecondMenu = function() {
  return '';

};


})(jQuery);
