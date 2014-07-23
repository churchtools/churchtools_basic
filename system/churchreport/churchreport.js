 
// Constructor
function ReportView() {
  StandardTableView.call(this);
  this.name="ReportView";
  this.allDataLoaded=false;
  this.listViewTableHeight=646;
  this.currentQueryId=null;
  this.currentReportId=null;
  this.reportData=null;
}

Temp.prototype = StandardTableView.prototype;
ReportView.prototype = new Temp();
reportView = new ReportView();


var masterData=null;


ReportView.prototype.renderNavi = function () {
  var t=this;

  var navi = new CC_Navi();
  navi.addEntry(true,"alistview0",_("standard"));    
  navi.renderDiv("cdb_navi", churchcore_handyformat());
};

function churchreport_loadMasterData() {
  churchInterface.jsendWrite({func:"getMasterData"}, function(ok, data) {
    if (ok) {
      masterData=data;
    }
  },false);    
}

function cdb_loadMasterData(nextFunction) {
  churchreport_loadMasterData(); 
  if (nextFunction!=null) nextFunction();
}

ReportView.prototype.renderList = function() {
};

ReportView.prototype.loadQuery = function(id) {
  var t=this;
  churchInterface.jsendRead({func:"loadQuery", id:id}, function(ok, data) {
    if (!ok) alert(_("error.occured")+": "+data);
    else {
      t.currentQueryId=id;
      masterData.report=data.reports;
      t.reportData=data.data;
      t.renderView();
    }
  })
};

ReportView.prototype.renderView = function() {
  var t=this;
  
  var rows=new Array();
  var form = new CC_Form();
  form.addSelect({data:masterData.query, selected:t.currentQueryId, freeoption:true, label:_("query")+"&nbsp;", htmlclass:"query", controlgroup:false});
  form.addHtml('&nbsp; &nbsp; &nbsp;');
  if (t.currentQueryId!=null && masterData.report!=null)
    form.addSelect({data:masterData.report, selected:t.currentReportId, label:_("report")+"&nbsp;", htmlclass:"report", controlgroup:false});
  rows.push(form.render(true, "inline"));
  rows.push('<div id="pivottable"></div>');

  $("#cdb_content").html(rows.join(""));
  
  $("#cdb_content select.query").change(function() {
    t.loadQuery($(this).val());
  });
  $("#cdb_content select.report").change(function() {
    t.currentReportId=$(this).val();
    t.renderView();
  });
  
  if (t.reportData!=null) {
  
    var derived=new Object();
//    derived["Monat"] = $.pivotUtilities.derivers.dateFormat("Datum", "%y-%m")
//    derived["Jahr"] = $.pivotUtilities.derivers.dateFormat("Datum", "%y")
  
    var countPersons = function() {
      return function() {
        var sumSuccesses= 0;
        var dates=new Array();  
        return {
          push: function(record) {
            if (!isNaN(parseFloat(record["count"]))) {
              sumSuccesses += parseFloat(record["count"]);
            }
            if ($.inArray(record["Datum"], dates)==-1) {
              dates.push(record["Datum"]);
            }
          },
          value: function() {return sumSuccesses / dates.length;  },
          format: function(x) { return Math.round(x); },
          label: "Success Rate"
        };
      };
    };  
    var countNewPersons = function() {
      return function() {
        var sumSuccesses= 0;
        return {
          push: function(record) {
            if (!isNaN(parseFloat(record["newperson_count"]))) {
              sumSuccesses += parseFloat(record["newperson_count"]);
            }
          },
          value: function() { return sumSuccesses},
          format: function(x) { return Math.round(x); },
          label: "Success Rate"
        };
      };
    };    
    // See more: https://gist.github.com/stephanvd/7246890
    
    var custom_aggregators = {
      "Anzahl Personen" : countPersons,
      "Anzahl neuer Personen": countNewPersons
    };
  
    if (t.currentReportId==null || masterData.report[t.currentReportId]==null) {
      t.currentReportId=churchcore_getFirstElement(masterData.report).id;
    }
    
    // Anzeige der neuen Personen
    $("#pivottable").pivotUI(
      t.reportData,
      {
         rows: masterData.report[t.currentReportId].rows.split(","),
         cols: masterData.report[t.currentReportId].cols.split(","),
         aggregatorName: masterData.report[t.currentReportId].aggregatorName,
         hiddenAttributes: ["newperson_count", "count"],
         derivedAttributes: derived,
         aggregators: $.extend(custom_aggregators, $.pivotUtilities.aggregators),
      }
    );
  }
};


$(document).ready(function() {
  churchInterface.setModulename("churchreport");
  churchInterface.registerView("ReportView", reportView);
  churchInterface.registerView("MaintainView", maintainView);
  churchreport_loadMasterData();
  churchInterface.activateHistory("ReportView");
});
