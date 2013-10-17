(function($) {
    
// Constructor
function TestView() {
  ListView.call(this);
  this.name="TestView";
  this.allDataLoaded=false;
  this.sortVariable="bezeichnung";
  this.allTestData = new Object();
  for (var i=0;i<10000;i++) {
    d=new Object();
    d.bezeichnung="teststring"+i;
    d.feld1="feld1_"+i;
    d.feld2="feld2_"+i;
    d.feld3="feld3_"+i;
    d.id=i;
    this.allTestData[i]=d;
  } 
  debug=true;
}

Temp.prototype = ListView.prototype;
TestView.prototype = new Temp();
testView = new TestView();


TestView.prototype.getData = function(sorted) {
  return this.allTestData;
};

TestView.prototype.renderFilter = function() {
  return null;
};

TestView.prototype.groupingFunction = function (list) {
  return null;
};

TestView.prototype.checkFilter = function(a) {
  return true;
};



TestView.prototype.renderMenu = function() {
  t=this;
  
  menu = new CC_Menu("Men&uuml;");
  
  menu.addEntry("renderTable", "arender", "star");
  menu.addEntry("SetzeAlleSpans", "atest1", "star");
  menu.addEntry("GeheDurchSpansTrId", "atest2", "star");
  menu.addEntry("GeheDurchSpansDirktId", "atest3", "star");

  if (!menu.renderDiv("cdb_menu",false))
    $("#cdb_menu").hide();
  else {    
    $("#cdb_menu a").click(function () {
      if ($(this).attr("id")=="arender") {
        t.renderList();
      }
      else if ($(this).attr("id")=="atest1") {
        var d=t.getData();
        t.startTimer();
        $.each(d, function(k,a) {
          var elem=$('tr[id='+a.id+']');
          if (elem.count>0)
            elem.children("td.feld1").html(a.feld1+"a");
        });
        t.endTimer("FŸlle Spans");
      }
      else if ($(this).attr("id")=="atest2") {
        var d=t.getData();
        t.startTimer();
        $('.feld1').each(function(k,a) {
          $(this).html(d[$(this).parents("tr").attr("id")].feld1+"b");
        });
        t.endTimer("FŸlle Spans");
      }
      else if ($(this).attr("id")=="atest3") {
        var d=t.getData();
        t.startTimer();
        $('.feld1').each(function(k,a) {
          $(this).html(d[$(this).attr("id").substr(6,99)].feld1+"b");
        });
        t.endTimer("FŸlle Spans");
      }
      return false;
    });
  }
};


TestView.prototype.addFurtherListCallbacks = function(cssid) {
  var t=this;
};

TestView.prototype.getCountCols = function() {
  return 2;
};


TestView.prototype.getListHeader = function () {
  var t=this;
  var rows=new Array();
  rows.push('<th>Id<th>Bezeichnung<th>Feld1');
  rows.push('<th>Feld2<th>Feld3');
  return rows.join("");
};

TestView.prototype.renderListEntry = function (list) {
  var rows = new Array();
  rows.push('<td>'+list.bezeichnung);
  rows.push('<td class="feld1" id="'+list.feld1+'">'+list.feld1);
  rows.push('<td class="feld2" id="'+list.feld2+'">'+list.feld1);
  rows.push('<td class="feld3" id="'+list.feld3+'">'+list.feld1);
  return rows.join("");
};

TestView.prototype.messageReceiver = function(message, args) {
  var this_object = this;
  if (message=="allDataLoaded") { 
    this.allDataLoaded=true;
    if (this==churchInterface.getCurrentView()) {
    }
  }
  if (this==churchInterface.getCurrentView()) {
    if (message=="allDataLoaded") {
      this_object.renderList();
    }
  }
};

TestView.prototype.addSecondMenu = function() {
  return '';
};


})(jQuery);
