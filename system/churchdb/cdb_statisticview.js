// Constructor
function StatisticView() {
//  StandardTableView.call(this);
  PersonView.call(this);
  this.name="StatisticView";
}

//Temp.prototype = StandardTableView.prototype;
Temp.prototype = PersonView.prototype;
StatisticView.prototype = new Temp();
statisticView = new StatisticView();


StatisticView.prototype.renderMasterDataStatistic = function(divid, masterDatafield, id_name) {
  var t=this; 
  rows = new Array();
  desc=masterData.masterDataTables[masterDatafield];
  res=new Object();
  var summe=0;
  $.each(allPersons, function(k,a) {
    if (t.checkFilter(a)) {
      if (res[a[id_name]]==null)
        res[a[id_name]]=0;
      res[a[id_name]]=res[a[id_name]]+1;
      summe++;
    }  
  });
  var data = [];  
  
  rows.push("<small><table cellpadding=\"2\"");
  var count=churchcore_countObjectElements(masterData[desc.shortname]);
  $.each(masterData[desc.shortname], function(k,a) {
    if (res[k]!=null) {
      rows.push("<tr><td width=50%>"+a.bezeichnung+"<td>");
      rows.push("<a href=\"#\" id=\""+divid+"\" val=\""+a.id+"\">"+res[k]+"</a><td>"+Math.round(100*res[k]/summe)+"%");
      data.push({ label:a.bezeichnung.trim(13), data: res[k] });
    }
    else if (count<20) {
      rows.push("<tr><td width=50%>"+a.bezeichnung+"<td>");
      rows.push("0<td>0%");
    }
  });
  rows.push("<tr bgcolor=\"#e7eef4\"><td><i>Summe</i><td><i>"+summe+"</i><td>");
  rows.push("</table></small>");

  if (this.filter["showTables"]==null) rows= new Array();
  
  var name=desc.bezeichnung;
  if (masterData.fields!=null)
  $.each(masterData.fields, function(k,m) {
    if (m.fields!=null) {
      $.each(m.fields, function(i,n) {
        if (n.selector==desc.shortname) {
          name=n.text;
          return false;
        }
      });
    }
  });
  
  rows.unshift("<b>"+name+"</b>");  
    
  if (this.filter["showCharts"]==1) {  
    rows.push('<div style="width:250px;height:250px;" id="'+divid+'_graph"/><div align="center" id="'+divid+'_hover"/>');
  }  
  $("#"+divid).html(rows.join(""));
  
  $("#"+divid+" a").click(function (a) {
    if ($(this).attr("id")==divid) {
      churchInterface.setCurrentView(personView);
      personView.clearFilter();
      personView.filter[divid.substr(5,99)]=$(this).attr("val");
      $.each(t.filter,function(k,a) {
        personView.filter[k]=a;
      });
    }
    return false;
  });

  if (this.filter["showCharts"]==1) {  
    $.plot($("#"+divid+"_graph"), data,
        {
      series: {
          pie: { 
              show: true,
              label:{
                show:true,
                threshold:0.03
              }
          }
      },
      legend: {
          show: false
      },
      grid: {
        hoverable: true,
        clickable: false
      }
    });
    $("#"+divid+"_graph").bind("plothover", function(event, pos, obj) {
        if (!obj) return;
        percent = parseFloat(obj.series.percent).toFixed(1);
        $("#"+divid+"_hover").html('<small><span style="align:center;font-weight: bold; color: '+obj.series.color+'">'+obj.series.label+' ('+percent+'%)</span></small>');
    });
  }
  
};

function sortObj(theObj, idx){
  var sortable = [];
  for(var i in theObj){
    sortable.push([i, theObj[i]]);
  }
  sortable.sort(function(a,b) {
    return a[idx] - b[idx];
  });
  return dojo.map(sortable,function(elm){ return elm[0]; });
}

StatisticView.prototype.renderYearStatistic = function(datefield, name) {
  var t=this;
  var obj=new Array();
  var rows = new Array();
  var currentDate= new Date().getFullYear();
  var dataAvailable=false;
  $.each(allPersons, function(k,a) {
    if ((a[datefield]!=null) && (t.checkFilter(a))) {
      dataAvailable=true;
      d=a[datefield].toDateEn().getFullYear();
      if (d<currentDate-6) d=currentDate-6;
      if (obj[d]==null)
        obj[d]=0;
      obj[d]=obj[d]+1;
    }      
  });
  if (dataAvailable) {
    rows.push("<b>"+name+"</b>");
    rows.push("<small><table cellpadding=\"2\"");
    summe=0;
    $.each(obj,function(k,a) {
      if (a!=null) {
        if (k==currentDate-6)
          k="<="+k;
        rows.push("<tr><td width=60%>"+k+"<td><a href=\"#\" id=\"datefilter"+datefield+"\" val=\""+k+"\">"+a+"</a>"); 
        
        summe=summe+a;
      }  
    });
    rows.push("<tr bgcolor=\"#e7eef4\"><td><i>Summe</i><td><i>"+summe+"</i>");
    rows.push("</table></small>");
  }  
  return rows.join("");
};

StatisticView.prototype.renderAgeGroups = function() {
  var t=this;
  // Altersgruppen
  var rows=new Array();
  res=new Array();
  summe=0; summealter=0;
  $.each(allPersons, function(k,a) {
    if (t.checkFilter(a)) {
      if (a.geburtsdatum!=null) {
        y=a.geburtsdatum.toDateEn().getAgeInYears().num;
        summealter=summealter+y;
        y=Math.floor(y/10);
        if (res[y]==null) res[y]=0;
        res[y]=res[y]+1;
        summe=summe+1;
      }  
    }
  });
  rows.push("<b>Altersgruppen</b>");
  rows.push("<small><table cellpadding=\"2\"");
  $.each(res,function(k,a) {
    if (a!=null)
      rows.push("<tr><td width=50%>"+k+"0-"+k+"9"+"<td>"+a+"<td>"+Math.round(a/summe*1000)/10+"%"); 
  });
  rows.push("<tr><td><i>Summe/Mittel</i><td><i>"+summe+"</i><td><i>"+Math.round(10*summealter/summe)/10+"J.</i>");
  rows.push("</table></small>");
  return rows.join("");
};

StatisticView.prototype.renderList = function() {
  var t=this;

  t.createMultiselect("Status", f("status_id"), masterData.status);
  t.createMultiselect("Station", f("station_id"), masterData.station);
  t.createMultiselect("Bereich", f("bereich_id"), masterData.auth.dep);

  this.filter["showTables"]=1;
  this.filter["showCharts"]=1;
  
  var rows = new Array();
 
  // Es soll mindestes Tabelle oder Charts gezeigt werden!
  if ((this.filter["showTables"]==null) && (this.filter["showCharts"]==null)) {
    this.filter["showTables"]=true;
    this.renderFilter();
  }
  
  
  // Kategorienstatistiken, hier werden nur DIVs erzeugt, die dann unten gefuellt werden.
  rows.push("<table cellpadding=\"5\"><tr><td style=\"vertical-align:top;\" valign=\"top\" width=\"30%\">");
  rows.push('<div id="statsfilterStatus" name="status_id" arr="'+1+'"/>');
  rows.push('<div id="statsfilterStation" name="station_id" arr="'+2+'"/>');
  rows.push('<div id="statsfilterGeschlecht" name="geschlecht_no" arr="'+4+'"/>');
  rows.push('<div id="statsfilterFamilienstatus" name="familienstand_no" arr="'+6+'"/>');
  rows.push('<div id="statsfilterNationalitaet" name="nationalitaet_id" arr="'+13+'"/>');

  if (this.getFilter("showTables")==1) {
    rows.push(this.renderAgeGroups());

    // Jahresstatistiken
    rows.push(this.renderYearStatistic("erstkontakt", "Erstkontakt"));
    rows.push(this.renderYearStatistic("zugehoerig", "Zugeh&ouml;rig"));
    rows.push(this.renderYearStatistic("eintrittsdatum", "Mitglied seit"));
    rows.push(this.renderYearStatistic("austrittsdatum", "Ausgetreten in"));
    rows.push(this.renderYearStatistic("taufdatum", "Getauft"));
    rows.push(this.renderYearStatistic("hochzeitsdatum", "Hochzeit"));
  }
  
 
  
  // Gruppenstatistiken
  rows.push("<td valign=\"top\" style=\"vertical-align:top;\" width=\"70%\">");

  // Gruppen mit Statistikwunsch
  grps=new Object();
  dataAvailable=false;
  current_year=new Date().getFullYear();
  how_many_years=2;

  $.each(allPersons, function(k,a) {
    if ((a.gruppe!=null) && (t.checkFilter(a)))  {
      $.each(a.gruppe, function(i,b) {
        if ((masterData.groups[b.id].instatistik_yn==1) && ((b.leiter>=0) && (b.leiter<=2) || (b.leiter==4))) {
          dataAvailable=true;
          if (grps[b.id]==null) grps[b.id]=new Array();
          if (b.d==null) 
            y=current_year-how_many_years;
          else {
            y=b.d.toDateEn().getFullYear();
            if (y<current_year-how_many_years) y=current_year-how_many_years;
          }  
          if (grps[b.id][y]==null) grps[b.id][y]=0; 
          grps[b.id][y]=grps[b.id][y]+1;
        }  
      }); 
    }
  });
  if (dataAvailable) {
    rows.push("<b>Gruppen mit Statistik</b>");
    rows.push("<small><table cellpadding=\"2\">");
    rows.push("<tr bgcolor=\"#e7eef4\"><td><i>Gruppe</i><td width=10%><=");
    for (i=current_year-how_many_years;i<=current_year;i++) {
      rows.push("<i>"+i+"</i><td width=10%>");
    }
    // Summenspalte
    rows.push("<i>Summe</i>");
    $.each(masterData.groups, function(k,a) {
      if (grps[a.id]!=null) {
        rows.push("<tr><td>"+a.bezeichnung+"<td>");
        summe=0;
        for (i=current_year-how_many_years;i<=current_year;i++) {
          if (grps[a.id][i]!=null) {
            rows.push(grps[a.id][i]);
            summe=summe+grps[a.id][i];
          }
          rows.push("<td>");
        }  
        rows.push(summe);
      }
    });
    rows.push("</table></small>");
  }
  
  // Alle Distrikte kummuliert
  grps=new Object();
  sumGruppentyp=new Object();
  
  $.each(allPersons, function(k,a) {
    if ((a.gruppe!=null) && (t.checkFilter(a)))  {
      var gruppentyp = new Object();
      $.each(a.gruppe, function(i,b) {
        // Nur wenn Teilnehmer, Mitarbeiter, Leiter und Co-Leiter
        if (((b.leiter>=0) && (b.leiter<=2)) || (b.leiter==4)) {

          // Hole Daten in grps-Array fŸr Details
          if (grps[b.id]==null) grps[b.id]=new Array();
          if (b.d==null) 
            y=current_year-how_many_years;
          else {
            y=b.d.toDateEn().getFullYear();
            if (y<current_year-how_many_years) y=current_year-how_many_years;
          }  
          if (grps[b.id][y]==null) grps[b.id][y]=0;
          grps[b.id][y]=grps[b.id][y]+1;
          
          // Merke mir nun, in welchen Gruppentypen die Person ist.
          var gruppentyp_id = masterData.groups[b.id].gruppentyp_id;
          if (gruppentyp[y]==null) gruppentyp[y]=new Object();
          if (gruppentyp[y][gruppentyp_id]==null)
            gruppentyp[y][gruppentyp_id]=true;  
        }  
      });
      $.each(gruppentyp, function(k,a) {
        if (sumGruppentyp[k]==null)
          sumGruppentyp[k]=new Object();
        $.each(a, function(i,b) {
          if (sumGruppentyp[k][i]==null)
            sumGruppentyp[k][i]=0;
          sumGruppentyp[k][i]=sumGruppentyp[k][i]+1;
        });
      });
    }
  });

  $.each(masterData.groupTypes, function(i,b) {
    res=null;
    $.each(masterData.groups, function(k,a) {
      if ((a.gruppentyp_id==b.id) && (a.distrikt_id!=null)) {
        for (i=current_year-how_many_years;i<=current_year;i++) {        
          if ((grps[a.id]!=null) && (grps[a.id][i]!=null)) {
            if (res==null) res=new Object();
            if (res[a.distrikt_id]==null) res[a.distrikt_id]=new Array();
            if (res[a.distrikt_id][i]==null)
              res[a.distrikt_id][i]=0;
            
            res[a.distrikt_id][i]=res[a.distrikt_id][i]+grps[a.id][i];
          }
        }
      }
    });
    if (res!=null) {
      rows.push("<b>"+f("distrikt_id")+" von "+f("gruppentyp_id")+" "+b.bezeichnung+"</b>");
      rows.push("<small><table cellpadding=\"2\"><tr bgcolor=\"#e7eef4\"><td><i>"+f("distrikt_id")+"</i><td width=10%><=");
      for (i=current_year-how_many_years;i<=current_year;i++) {
        rows.push("<i>"+i+"</i><td width=10%>");
      }
      rows.push("<i>Summe</i>");
      $.each(res, function(k,a) {
        summe=0;
        rows.push("<tr><td>"+(masterData.districts[k]!=null
                                  ?masterData.districts[k].bezeichnung
                                  :'<font color="red">DistriktId:'+k+'</font>')+"<td>");
        for (i=current_year-how_many_years;i<=current_year;i++) {   
          if (a[i]!=null) {
            rows.push(a[i]);
            summe=summe+a[i];
          }  
          rows.push("<td>");
        }
        rows.push(summe);
      });
      
      rows.push("<tr bgcolor=\"#e7eef4\"><td><i>Summe</i><td>");
      summe=0;
      for (k=current_year-how_many_years;k<=current_year;k++) {
        if ((sumGruppentyp[k]!=null) && (sumGruppentyp[k][b.id]!=null)) {
          rows.push(sumGruppentyp[k][b.id]);
          summe=summe+sumGruppentyp[k][b.id];
        }
        rows.push("<td>"); 
      }
      rows.push(summe);
      rows.push("</table></small>");
    }  
  });
  
  
  rows.push("</table>");
  rows.push('<div id="yearStats" style="width:800px;height:500px;"/><div id="yearStats_hover"/><div id="yearStats_choices"/>');
  
  
  $("#cdb_content").html(rows.join(""));
  
  $("#cdb_content div").each(function(k,a) {
    if ($(this).attr("id").indexOf("statsfilter")==0) {
      t.renderMasterDataStatistic($(this).attr("id"), $(this).attr("arr"), $(this).attr("name"));
    }
  });
  
  // Callback fŸr renderYearStatistics
  $("#cdb_content a").click(function (a) {
    if ($(this).attr("id").indexOf("datefilter")==0) {
      churchInterface.setCurrentView(personView);
      personView.clearFilter();
      personView.filter["filterDates"]=$(this).attr("id").substr(10,99);
      personView.filter["dateAfter"]="01.01."+$(this).attr("val");
      personView.filter["dateBefore"]="31.12."+$(this).attr("val");
      // †bernahme sŠmtlicher anderer Filter
      $.each(t.filter,function(k,a) {
        personView.filter[k]=a;
      });
    }
    return false;
  });
  
  
  if (this.getFilter("showCharts")==1) {
    var res=new Object();
    $.each(allPersons, function(k,a) {
      if (t.checkFilter(a)) {
        $.each(masterData.fields.f_church.fields, function (b,i) {
          if (i["type"]=="date") {
            n=i["sql"];
            if (a[n]!=null && a[n].toDateEn().getFullYear()>1004) {
              if (res[n]==null) res[n]=new Array();
              var y=a[n].toDateEn().getFullYear();
              if (y>=7000) y=y-7000;
              if (res[n][y]==null)
                res[n][y]=0;
              res[n][y]++;
            }
          }
        });
      }
    });
        
    datasets=new Array();
    $.each(res, function(i,b) {
      data_=new Array();
      $.each(b, function(k,a) {
        if ((a!=null)&& (a>0)) {          
          data_.push([k,a]);
        }
      });
      datasets.push({label:i, data:data_});
    });    
    
    var choiceContainer = $("#yearStats_choices");
    $.each(datasets, function(key, val) {
        choiceContainer.append('<br/><input type="checkbox" name="' + key +
                               '" checked="checked" id="id' + key + '">' +
                               '<label for="id' + key + '">'
                                + val.label + '</label>');
    });
    function plotAccordingToChoices() {
      var data = [];

      choiceContainer.find("input:checked").each(function () {
          var key = $(this).attr("name");
          if (key && datasets[key]) {
              data.push(datasets[key]);
          }
      });

      if (data.length > 0)
          $.plot($("#yearStats"), data, {
              yaxis: { min: 0 },
              xaxis: { tickDecimals: 0 },
              legend: {
                show: false
            },
            grid: {
              hoverable: true,
              clickable: false
            }
          });
    }

    
    choiceContainer.find("input").click(plotAccordingToChoices);
    
    $("#yearStats").bind("plothover", function(event, pos, obj) {
      if (!obj) return;
      $("#yearStats_hover").html('<span style="font-weight: bold; color: '+obj.series.color+'">'+obj.series.label+'</span>');
    });
   
    plotAccordingToChoices();
  }
};
