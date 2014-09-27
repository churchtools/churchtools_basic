 
// Constructor
function ReportView() {
  StandardTableView.call(this);
  this.name="ReportView";
  this.allDataLoaded=false;
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
  $("#cdb_form a").remove();
  $("#cdb_form").append(form_renderImage({src:"loading.gif", width:24}));
  churchInterface.jsendRead({func:"loadQuery", id:id}, function(ok, data) {
    if (!ok) alert(_("error.occured")+": "+data);
    else {
      t.currentQueryId=id;
      t.reportData=data.data;
      t.renderView();
    }
  })
};

ReportView.prototype.renderView = function() {
  var t=this;
  
  var rows=new Array();
  var form = new CC_Form(null, null, "cdb_form");
  form.addSelect({data:masterData.query, selected:t.currentQueryId, freeoption:true, label:_("query")+"&nbsp;", htmlclass:"query", controlgroup:false});
  form.addHtml('&nbsp; &nbsp; &nbsp;');
  if (t.currentQueryId && masterData.report!=null) {
    form.addSelect({data:masterData.report, selected:t.currentReportId, label:_("report")+"&nbsp;", htmlclass:"report", controlgroup:false,
        func:function(k){return k.query_id==t.currentQueryId}});
    form.addHtml('&nbsp; &nbsp; &nbsp;');
  }
  if (user_access("edit masterdata"))
    form.addImage({src:"options.png", link:true, htmlclass:"option", width:24});
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
  $("#cdb_content a.option").click(function() {
    churchInterface.setCurrentView(maintainView);
    return false;
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
  
    if (t.currentReportId==null || masterData.report[t.currentReportId]==null
           || masterData.report[t.currentReportId].query_id!=t.currentQueryId) {
      t.currentReportId=null;
      each(masterData.report, function(k,a) {
        if (a.query_id==t.currentQueryId) {
          t.currentReportId=a.id;
          return false;
        }
      });
    }
    
    if (t.currentReportId==null) {
      alert(_("no.report.available"));
      return;
    }
    
    var options = {
      rows: masterData.report[t.currentReportId].rows.split(","),
      cols: masterData.report[t.currentReportId].cols.split(","),
      hiddenAttributes: ["newperson_count", "count"],
      derivedAttributes: derived,
      aggregators: $.extend(custom_aggregators, $.pivotUtilities.aggregators)        
    };
    if (masterData.report[t.currentReportId].aggregatorName) {
      options.aggregatorName=masterData.report[t.currentReportId].aggregatorName;
    }
    
    $("#pivottable").pivotUI(t.reportData, options);
  }
};


$(document).ready(function() {
  churchInterface.setModulename("churchreport");
  churchInterface.registerView("ReportView", reportView);
  churchInterface.registerView("MaintainView", maintainView);
  churchreport_loadMasterData();
  churchInterface.activateHistory("ReportView");
});
