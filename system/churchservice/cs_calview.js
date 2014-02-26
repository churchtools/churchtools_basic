(function($) {
	  
// Constructor
function CalView() {
  ListView.call(this);
  this.name="CalView";
  this.currentDate=new Date();
  this.printview=false;
  this.availableRowCounts=[10,25,50,200];
}

Temp.prototype = ListView.prototype;
CalView.prototype = new Temp();
calView = new CalView();

CalView.prototype.shiftCurrentDate = function() {
  this.currentDate.addDays(-this.currentDate.getDay()+1);
  this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), this.currentDate.getDate()-1);
};
var wochentag = new Array("So", "Mo", "Di", "Mi", "Do", "Fr", "Sa");


CalView.prototype.getData = function(sorted) {
  var data=new Array;
  $.each(churchcore_sortData(masterData.servicegroup,"bezeichnung"), function(k1,sg) {
    // Sammle Personen zusammen pro ServiceGroup (manche Personen können ja verschiedene Services machen!)
    var servicegroup = new Object();
    $.each(masterData.service, function(k2,s) {
      if ((s.servicegroup_id==sg.id) && (s.cdb_gruppen_ids!=null)){
        $.each(s.cdb_gruppen_ids.split(","), function(k3,p) {
          if ((groups!=null) && (groups[p]!=null)) {
            $.each(groups[p], function(k,g) {
              if ((servicegroup[g.p_id]==null) && (allPersons[g.p_id]!=null)) {
                var o = new Object();
                o.servicegroup_id=sg.id;
                o.group=g;
                o.name=g.name+" "+g.vorname;
                servicegroup[g.p_id]=o;
              }
            });
          }
        });
      }
    });    
    // Nun schiebe die komplette Servicegroup in die Daten rein.
    $.each(churchcore_sortData(servicegroup,"name"), function(k,a) {
      data.push(a);
    });
  });
  
  
  return data;
};

CalView.prototype.checkFilter = function (a) {    
  if ((allPersons[a.group.p_id]!=null) && (allPersons[a.group.p_id].absent!=null)) {
    if (this.filter["filterDienstgruppen"]!=null)
      if (a.servicegroup_id!=this.filter["filterDienstgruppen"]) return false;
    return true;
  }
  return false;
};

//Mit der Function kann man die Einträge gruppieren. Einfach den Gruppenwert ausgeben
CalView.prototype.groupingFunction = function(a) {
  return masterData.servicegroup[a.servicegroup_id].bezeichnung;
};

CalView.prototype.getCountCols = function() {
  return 9;
};

CalView.prototype.getListHeader = function () {
  var rows = new Array();
  $("#cdb_group").html("");
  
  this.shiftCurrentDate();
  
  var currentDate=this.currentDate;
  rows.push('<th style="min-width:100px">');
  currentDate.addDays(1);
  rows.push("KW"+currentDate.getKW());
  currentDate.addDays(-1);
  
  d = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate());
  for (i=0;i<7;i++) {
    d.addDays(1);
    rows.push("<th>"+wochentag[d.getDay()]+", "+d.toStringDe()+"");      
  }
  rows.push("");

  return rows.join("");
};

CalView.prototype.renderListEntry = function (a) {
  var rows = new Array();
  rows.push("<td><b>"+a.group.vorname+" "+a.group.name+"</b>");

  d = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), this.currentDate.getDate());
  for (i=0;i<7;i++) {
    rows.push('<td style="min-width:40px">');
    d.addDays(1);
  
    var p = allPersons[a.group.p_id];
    if (p.absent!=null) {
      $.each(p.absent,function(k,absent) {
        if ((absent!=null) && (absent.startdate<=d) && (absent.enddate>=d)) {
          rows.push(masterData.absent_reason[absent.absent_reason_id].bezeichnung+" ");
        }
      });
    }
  }  
  
  return rows.join("");
};

CalView.prototype.messageReceiver = function(message, args) {
  var this_object = this;
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      this_object.renderList();
    }
  }
};

})(jQuery);
