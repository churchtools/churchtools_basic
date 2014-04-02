//Implementiert die Standard-Features fuer eine Table View: renderView und renderList



//Constructor
function StandardTableView(options) {
  var t=this;
  AbstractView.call(this);
  
  this.name="StandardTableView";
  this.init=false;
  this.filter=new Object();
  this.furtherFilterVisible=false;
  this.listOffset=0;
  this.sortVariable="id";
  this.availableRowCounts=[10, 25, 50, 200];
  this.showCheckboxes=true;

  // Number of entries currently visible
  this.counter=0;
  // Der letzte geöffnet Eintrag, wird gemerkt, damit er nach einem renderList()
  // auch wieder angezeigt wird.
  
  this.testTimer=null; // Zeitmessung
  this.renderListTimer=null;
  this.listViewAggregate=false;
  this.listViewTableHeight=null; // Wenn ja, hat die Tabelle eien fixen Titel
  
  this.showPaging=true;
  this.rowNumbering=true;

  if (options!=null) {
    $.each(options, function(k,a) {
      t[k]=a;
    });
  }
}
Temp.prototype = AbstractView.prototype;
StandardTableView.prototype = new Temp();

StandardTableView.prototype.initView = function () {
  
};


StandardTableView.prototype.historyCreateStep = function () {
  var str = "";
  jQuery.each(this.filter, function(k,a) {
    str=str+"/"+k+":"+a;
  });
  churchInterface.historyCreateStep(this.name+str+"/");
};

/*
 * Ruft alle Functions auf, um die View komplett zu bauen
 * Parameter: withMenu - default = true
 */
StandardTableView.prototype.renderView = function(withMenu) {
  var t=this;
  if (t.init==false) {
    t.initView();
    t.init=true;
  }    
  
  if (!churchcore_handyformat()) {
    if ((withMenu==null) || (withMenu)) {
      this.renderMenu();
    }
    this.renderListMenu();    
    this.renderFilter();
    if (!churchcore_touchscreen())
      $("#searchEntry").focus();
  }
  else {
    this.renderMenu();    
    this.renderListMenu();    
    $("#cdb_menu").append("&nbsp; "+form_renderButton({label:"Filter &raquo;", htmlclass:"togglefilter"})+"<br/><br/>");
    $("#cdb_menu input.togglefilter").click(function() {
      if ($("#cdb_filter").html()=="") {
        t.renderFilter();
        $(this).val("Filter "+String.fromCharCode(171));
      } 
      else { 
        $("#cdb_filter").html("");
        $(this).val("Filter "+String.fromCharCode(187));
      }
    });
    
  }
  
  if (this.furtherFilterVisible)
    this.renderFurtherFilter();
  this.renderList();
};

// Mit der Function kann man die Einträge gruppieren. Einfach den Gruppenwert ausgeben
StandardTableView.prototype.groupingFunction = function() {
  return null;
};

StandardTableView.prototype.getCountCols = function() {
  return 1;
};

/**
 * @param filterId
 * @return Gibt den aktuellen Wert des Filters filterId zurueck oder "" wenn nicht gefuellt.
 */
StandardTableView.prototype.getFilter = function(filterId) {
  if (this.filter[filterId]!=null)
    return this.filter[filterId];
  else return "";
};

// Löscht den Filter und gibt true zurück, wenn er gesetzt war
StandardTableView.prototype.deleteFilter = function(filterId) {
  if (this.filter[filterId]!=null) {
    delete this.filter[filterId];
    return true;
  }
  else return false;
};

StandardTableView.prototype.renderFurtherFilter = function() {  
};

/**
 * Messages schicken an die Views
 */
StandardTableView.prototype.messageReceiver = function(message, args) {  
};


/**
 * 
 * @param this_object - Gibt die zu verwendene this an
 * @param divid - Divid, fuer die der Callback installiert werden soll
 */

_aktiv=false;
(function($) {
StandardTableView.prototype.implantStandardFilterCallbacks = function(this_object, divid) {
  var t=this;
  var myfilter=t.filter;
  $("#"+divid+" select").change(function(c) {
    var oldVal=myfilter[$(this).attr("id")];
    if ($(this).val()=="") {
      delete myfilter[$(this).attr("id")];
    }  
    else {
      myfilter[$(this).attr("id")]=$(this).val();
    }
    churchInterface.sendMessageToAllViews("filterChanged",new Array($(this).attr("id"), oldVal));
    t.listOffset=0;
    t.renderList(null, false);
  }); 
  $("#"+divid+" input:checkbox").click(function(c) {
    var oldVal=myfilter[$(this).attr("id")];
    if ($(this).attr("checked")) {
      myfilter[$(this).attr("id")]=true;
    }
    else { 
      delete myfilter[$(this).attr("id")];
    }  
    churchInterface.sendMessageToAllViews("filterChanged",new Array($(this).attr("id"), oldVal));
    t.listOffset=0;
    t.renderList(null, false);        
  });        
  $("#"+divid+" input:text").keyup(function(c) {
    if ((myfilter[$(this).attr("id")]!=$(this).val()) && (($(this).val().length>0) || $(this).val()=="")) {
      var oldVal=myfilter[$(this).attr("id")];
      var id=$(this).attr("id");
      if ($(this).val()=="")
        delete myfilter[$(this).attr("id")];
      else 
        myfilter[$(this).attr("id")]=$(this).val();
      t.listOffset=0;

      // Ein Timeout erlaubt das entspannte Eintippen, ohne das immer sofort die Seite neu geladen wird. 
      if (_aktiv!=null)
        _aktiv=window.clearTimeout(_aktiv);
      _aktiv=window.setTimeout(function() {
//        t.historyCreateStep();
        churchInterface.sendMessageToAllViews("filterChanged",new Array(id, oldVal));
        t.renderList(null, false);   
        _aktiv=false;
        },300);
    }
  });
};
 
/*
 * Erlaubt das Setzen des Filters einer View, filtername=value 
 */
StandardTableView.prototype.setFilter = function(filterName, value) {
  this.filter[filterName]=value;
};

/*
 * Loescht alle Filter 
 */
StandardTableView.prototype.clearFilter = function() {
  this.filter=new Object();
  this.listOffset=0;
};


/**
 * 
 * @param {Object} data - Array type (Array mit Feldern id und bezeichnung)
 * @param {Object} name - Anzeigename
 * @param {Object} selected - Welches soll vorselektiert sien?
 * @param {Object} func - m√∂glihce Einschr√§nkungsfunciton
 */
StandardTableView.prototype.getSelectFilter = function(data, name, selected, func, emptyentry) {
  if (data==null) return "";
  if (emptyentry==null) emptyentry=true;
  if (name!="") 
    _text='<font style="font-size:8pt;">'+name+": </font><select id=\"filter"+name+"\" style=\"\">";    
  else  
    _text="<select id=\"filterVoid"+"\" style=\"\">";
  if (emptyentry)
    _text=_text+'<option value=""></option>';
    
  jQuery.each(data, function (i,d) {
    if ((typeof func !="function") || (func(d))) {
      _text=_text+"<option value=\""+d.id+"\"";
      if (selected==d.id) _text=_text+" selected ";
      _text=_text+">"+d.bezeichnung.trim(25)+"</option>";   
    }  
  });
  _text=_text+"</select>&nbsp;&nbsp;";   
  return _text;
};




/*
 * View kann noch ein Menu hinzufuegen fuer Extra-Funktionen abhaegngig von der aktuellen Sicht
 */
StandardTableView.prototype.addSecondMenu = function() {
  return "";
};
StandardTableView.prototype.addFurtherListCallbacks = function() {  
};

StandardTableView.prototype.renderOneListEntry = function(id) {
  
};


/*
 * Generiert die Liste, verwendet dafuer getListHeader und renderListEntry und addSecondMenu
 * entry wenn nur ein Eintrag neu gerendert werden soll. Sehr sinnvoll bei großen Listen (z.B. Dienstplan!)
 * newSort (default true)
 */
StandardTableView.prototype.renderList = function(entry, newSort) {
  t=this;
   
  var rows = new Array();
  
  // Wenn nur ein Eintrag neu gerendert werden soll
  if (entry!=null) {
    if (t.checkFilter(entry)) {      
      if (entry.checked == true) 
        rows[rows.length] = "<td><input type=\"checkbox\" id=\"check" + entry.id +
        "\" checked=\"checked\" class=\"checked\">";
      else 
        rows[rows.length] = "<td><input type=\"checkbox\" id=\"check" + entry.id +
        "\" class=\"checked\">";      
      if (t.rowNumbering && t.groupingFunction(entry)==null) {
        rows.push("<td><a href=\"\" id=\"detail" + entry.id + "\">" + $("tr[id="+entry.id+"]").find("td").next().first().text()+"</a>");
      }

      var txt=t.renderListEntry(entry);
      if (txt!=null) {
        rows.push(txt);
        $("#cdb_content tr[id="+entry.id+"]").html(rows.join(""));
        t.addTableContentCallbacks("#cdb_content tr[id="+entry.id+"]");
        if (entry.open) t.renderEntryDetail(entry.id);
      }
    }  // Wenn der Filter nicht mehr passt, dann lösche es 
    else {
      $("#cdb_content tr[id="+entry.id+"]").html("");
    }
  }
  // Wenn die ganze Tabelle neu gerendert werden soll
  else {
    if (t.renderListTimer==null)
      t.renderListTimer=window.setTimeout(function() {
      
      if (debug) t.startTimer();
      
      var current_id = 0;    
      listObject=t.getData(true, newSort);    
  
      $("#cdb_content").html("Tabellenaufbau...");    
      
      if ((masterData.settings==null) || (masterData.settings.listViewTableHeight==null) || (masterData.settings.listViewTableHeight==0))
        t.listViewTableHeight=null;
      else 
        t.listViewTableHeight=646;
  
      if (masterData.settings==null) masterData.settings=new Object();    
      if (masterData.settings["listMaxRows"+t.name]==null)
        masterData.settings["listMaxRows"+t.name]=25;
      else masterData.settings["listMaxRows"+t.name]=masterData.settings["listMaxRows"+t.name]*1;
      
      // Wenn es Handyformat ist dann zeige immer nur 10 Zeilen, außer bei der Ressourcen-WeekView, 
      // denn hier macht es Sinn, das man alle sieht.
      if ((churchcore_handyformat()) && (churchInterface.getCurrentView().name!="WeekView"))
        masterData.settings["listMaxRows"+t.name]=10;    
  
      var header=t.getListHeader();
      
      if (listObject == null) {
        rows[rows.length] = "Keinen Eintrag gefunden.";
      }
      else {
        var classes='view '+t.name+' table table-bordered table-condensed ';
        rows[rows.length] = '<div style="" id="DivAddressTable"><table class="'+classes+'table-striped" style="tab_le-layout:fixed;margin-bottom:0px;" id="AddressTable">';
        rows[rows.length] = '<thead>';
        if (t.showCheckboxes)
          rows.push('<tr><th width="12px"><input type="checkbox" class="checked" id="markAll">');      
          rows.push(header);
        rows.push('</thead>');
        
        if (t.listViewTableHeight!=null) {
          rows.push('</table></div>');
          
          rows.push('<div style="min-height:300px; max-height:'+t.listViewTableHeight+'px; overflow-y:auto; overflow-x:auto">');
          rows.push('<table class="'+classes+'" style="margin-bottom:0px" id="AddressTableChild">');        
        }
        
        rows.push('<tbody>');
        t.counter = 0; 
        var lastGrouping=null;
        
        $.each(listObject, function(k, entry) {
          if ((entry!=null) && (t.checkFilter(entry))) {
            t.counter++;
            if ((t.counter>=t.listOffset) && (t.counter<=masterData.settings["listMaxRows"+t.name]+t.listOffset)) {
  
              entry_txt=t.renderListEntry(entry);
              if (entry_txt==null) 
                t.counter--;
              else {
                var r = t.groupingFunction(entry);
                if (r!=lastGrouping) {
                  lastGrouping=r;
                  rows.push('<tr class="grouping"><td class="grouping" align="center" colspan="'+(t.getCountCols())+'">'+r);
                }
                
                rows.push("<tr class=\"data\" id=\"" + entry.id + "\">");   
                if (t.showCheckboxes) {
                  rows.push("<td width=\"12px\"><input type=\"checkbox\" class=\"checked\" id=\"check" + entry.id + "\"");
                  if (entry.checked) rows.push(" checked=checked"); 
                  rows.push(">");
                }
                
                if (t.rowNumbering && lastGrouping==null)
                  rows.push("<td><a href=\"\" id=\"detail" + entry.id + "\">" + t.counter + "</a>");         
                rows.push(entry_txt);
                
                current_id = entry.id;
              }  
            }
          } 
        });    
        
        rows.push('<tbody>');
        rows.push("</table>");
        rows.push("</div>");
  
        rows.push('<table class="table table-bordered table-condensed"><tr><td>');
        
        if (t.counter>=masterData.settings["listMaxRows"+t.name]) {
          rows[rows.length] = "Zeige "+masterData.settings["listMaxRows"+t.name]+" von "+t.counter+" gefundenen Eintr&auml;gen";
          rows.push("&nbsp; &nbsp; &nbsp;Bl&auml;ttern: <a id=\"offset0\" href=\"#\"><<</a>&nbsp;|&nbsp;<a id=\"offsetMinus\" href=\"#\"><</a>&nbsp;|&nbsp;<a id=\"offsetPlus\" href=\"#\">></a>");
        }  
        else 
          rows[rows.length] = "Zeige "+t.counter+" gefundene Eintr&auml;ge";
  
        if (t.showPaging) {    
          if (!churchcore_handyformat()) {
            rows.push("&nbsp; &nbsp; &nbsp; &nbsp;(Zeilenanzahl: ");
            $.each(t.availableRowCounts, function(i,k) {
              rows.push('<a href="#" class="changemaxrow" data-id="'+k+'">'+k+'</a>&nbsp;|&nbsp;');              
            });
          }
          rows.push("&nbsp; &nbsp; &nbsp; ");
          if ((masterData.settings["listMaxRows"+t.name]<=20) || (t.counter<=20))
            rows.push("&nbsp;<a href=\"#\" id=\"showAll\">alle &ouml;ffnen</a>");
          rows.push("&nbsp; &nbsp; <a href=\"#\" id=\"hideAll\">alle schlie&szlig;en</a>");
        }
        rows.push('</table>');
        
        if (t.listViewTableHeight!=null) rows.push("<p></p>");
  
        rows.push(t.addSecondMenu());      
      }
      $("#cdb_content").html(rows.join(""));
      
      if (debug) t.endTimer("renderTableView");
      
      calcHeaderWidth(current_id);
  
      //if (t.counter==1) {
        // Entry anzeigen, wenn alles geladen ist, sonst lädt er die Detaildaten auch noch, obwohl später vielleicht noch
        // mehr Leute kommen, die passend könnten. Außer es ist eine Id, da wird es nur einen geben
       // if (t.filter["searchEntry"]>0)
         // t.renderEntryDetail(current_id);
      //}  
    
      // Callbacks auf die Header und Footer der Tabelle
      $("#cdb_content a").click(function (_content_a) {
        if ($(this).attr("id")=="orderby") loadList("","",$(this).attr("href"));
        else if ($(this).attr("id")=="offset0") {
          t.listOffset=0;
          t.renderList();
        }
        else if ($(this).attr("id")=="offsetMinus") {
          t.listOffset=t.listOffset-masterData.settings["listMaxRows"+t.name];
          if (t.listOffset<0) t.listOffset=0;
          t.renderList();
        }
        else if ($(this).attr("id")=="offsetPlus") {
          t.listOffset=t.listOffset+masterData.settings["listMaxRows"+t.name];
          t.renderList();
        }
        else if ($(this).attr("id")=="showAll") {
          n=0; j=0;
          $.each(listObject, function(k, a) {
            n++;
            if (t.checkFilter(a)) {
              j++;
              if ((n>=t.listOffset) && (j<=masterData.settings["listMaxRows"+t.name])) {
                t.renderEntryDetail(a.id);
              } 
            }
          });   
        }
        else if ($(this).attr("id")=="hideAll") {
          $.each(listObject, function(k, entry) {
            entry.open=false;
          });
          
          t.renderList();
        }      
      });
      $("#cdb_content a.changemaxrow").click(function () {    
        masterData.settings["listMaxRows"+t.name]=$(this).attr("data-id");
        t.renderList();
        churchInterface.jsendWrite({func:"saveSetting", sub:"listMaxRows"+t.name, val:$(this).attr("data-id")});
        return false;
      });
      
      t.addTableContentCallbacks("#cdb_content");
      $(window).resize(function() {
        calcHeaderWidth();
      });
      if (listObject!=null)
      $.each(listObject, function(k, entry) {
        if ((entry!=null) && (t.checkFilter(entry))) 
          if ((entry.open) || (t.counter==1)) t.renderEntryDetail(entry.id);
      });
      
      if (debug) t.endTimer("renderTablecallback");
      t.renderListTimer=null;
    },10);

  }
};

StandardTableView.prototype.startTimer = function() {
  this.testTimer=new Date();
  
};
StandardTableView.prototype.endTimer = function(name) {
  console.log(name+": "+churchcore_getTimeDiff(this.testTimer));
  $("#cdb_content").prepend("<small><br>"+name+": "+churchcore_getTimeDiff(this.testTimer)+'</small>');
  this.startTimer();
};


function calcHeaderWidth() {
  // headerFromChild
  var header=$("#cdb_content thead th");
  $("#AddressTableChild tr").next(".data").first().children().each(function(k,a) {
    header.first().width($(this).width());
    header=header.next();
  });
  // childFromHeader
/*  var header=$("#AddressTableChild tr").next(".data").first().children();
  $("#cdb_content thead th").each(function(k,a) {
    header.width($(this).width());
    header=header.next();
  });*/
}

StandardTableView.prototype.entryDetailClick = function(id) {
  var t=this;
  var a=t.getData();
  if ($("tr[id=detail"+id+"]").text()!="") {
    $("tr[id=detail"+id+"]").remove();
    a[id].open=false;
  } 
  else {          
    a[id].open=true;
    t.renderEntryDetail(id);
  }   
};

StandardTableView.prototype.addTableContentCallbacks = function(cssid) {
  var t=this;
  
  $(cssid+" input.checked").click(function (_content_input) {
    var s=$(this).attr("id");
    if (s=="markAll") {
      if ((t.counter>=masterData.settings["listMaxRows"+t.name]) && (confirm("Es sind mehr Eintraege vorhanden, als momentan angezeigt. Sollen alle Eintraege markiert werden?"))) {
        bol=$(this).is(":checked");
        $.each(listObject, function(k,a) {
          if ((a!=null) && (t.checkFilter(a))) {
            a.checked=bol;
          }  
        });
      }  

      if ($(this).is(":checked")) {          
        $("#cdb_content input.checked").not("#markAll").each(function(a) {
          $(this).attr("checked","true");
          t.getData(false)[$(this).attr("id").substr(5,99)]["checked"]=true;
        });  
      }  
      else {   
        $("#cdb_content input.checked").not("#markAll").each(function(a) {
          $(this).removeAttr("checked");
          t.getData(false)[$(this).attr("id").substr(5,99)]["checked"]=false;
        }); 
      }
    }  
    else t.getData(false)[$(this).attr("id").substr(5,99)]["checked"]=$(this).is(":checked");
  });
  
  $(cssid+" a").click(function (_content_a) {
    if ($(this).attr("id")==null)
      return true;
    else if ($(this).attr("id").indexOf("detail")==0) {
      var id = $(this).attr("id").substr(6,99);
      t.entryDetailClick(id);
    }
    else if ($(this).attr("id")=="mailto") return true;
    else if ($(this).attr("id")=="extern") return true;
    else if ($(this).attr("id").indexOf("sort")==0) {
      t.sortVariable=$(this).attr("id").substr(4,99);
      t.renderList();
    }
    return false;
  });
  $(".hoveractor").off("hover");
  $(".hoveractor").hover(
      function () {
        $(this).find("span.hoverreactor").fadeIn('fast',function() {});
      }, 
      function () {
        $(this).find("span.hoverreactor").fadeOut('fast');
      }
    );  
  this.addFurtherListCallbacks(cssid);
};

StandardTableView.prototype.mailPerson = function (personId, name, subject) {
  var t=this;
  var rows = new Array();
  rows.push(form_renderInput({cssid:"betreff", label:"Betreff", type:"xlarge", text:(subject!=null?subject:"")}));
  rows.push('<p>Inhalt<span class="pull-right editor-status"></span><div id="inhalt" class="well">');
  if (masterData.settings.signature!=null)
    rows.push(masterData.settings.signature);
  rows.push('</div>');
  
  if (masterData.settings.sendBCCMail==null)
    masterData.settings.sendBCCMail=1;
  rows.push("<p>"+form_renderCheckbox({cssid:"sendBCCMail", checked: masterData.settings.sendBCCMail==1,
       label:"eine BCC-Kopie an mich senden."}));

  
  var elem=this.showDialog("E-Mail an "+(name!=null?name:"Person"),rows.join(""), 600,550, {
      "Senden": function() {
        var arr=personId.split(",");
        if ((arr.length<5) || (confirm("Soll wirklich eine E-Mail an "+arr.length+" Personen gesendet werden?"))) {
          var obj = new Object();
          obj.ids=personId;
          masterData.settings.sendBCCMail=($("#sendBCCMail").attr("checked")?1:0);
          if (masterData.settings.sendBCCMail==1)   
            if (typeof(masterData.user_pid)=="string")
              obj.ids=obj.ids+","+masterData.user_pid;
            else
              obj.ids=obj.ids+","+masterData.user_pid[0];
          churchInterface.jsendWrite({func:"saveSetting", sub:"sendBCCMail", val: masterData.settings.sendBCCMail});
  
          obj.betreff=$("#betreff").val();
          obj.inhalt=CKEDITOR.instances.inhalt.getData();
          obj.domain_id=null;
          obj.func="sendEMailToPersonIds";
          churchInterface.jsendWrite(obj, function(ok, data) {
            if (ok) {
              alert("Die EMail wurde gesendet!");
              drafter.clear();
            }
            else alert("Es ist ein Fehler aufgetreten: "+data);
          }, null, false);          
          $(this).dialog("close");
        }
      },
      "Abbrechen": function() {
        drafter.clear();
        $(this).dialog("close");
      }        
  });
  
  form_implantWysiwygEditor('inhalt', false);
  //Save draft
  var drafter=new Drafter("email", {
    setStatus : function(status) {
      elem.find('span.editor-status').html('<small><i>'+status+'</small></i>');
    },
    getContent : function() {
      return CKEDITOR.instances.inhalt.getData();
    },
    setContent : function(content) {
      CKEDITOR.instances.inhalt.setData(content);
    }
  });
  
  CKEDITOR.instances.inhalt.on('change', function() {  elem.find('span.editor-status').html('');});

  
  if (subject!=null)
    $("#inhalt").focus();

  $("a").click(function(c) {
    if (($(this).attr("id")=="Vorname") || ($(this).attr("id")=="Nachname")) {
      $("#inhalt").insertAtCaret("["+$(this).attr("id")+"]");    
    }
  });
};

StandardTableView.prototype.renderFile = function(file, filename_length) {
  txt="";
  if (file!=null) {
    txt=txt+'<span class="tooltip-file file" data-id="'+file.id+'">';
    var i = file.bezeichnung.lastIndexOf(".");
    if (i>0) {
      switch (file.bezeichnung.substr(i,99)) {
      case '.mp3': 
        txt=txt+this.renderImage("mp3",18);
        break;
      case '.m4a': 
        txt=txt+this.renderImage("mp3",18);
        break;
      case '.pdf': 
        txt=txt+this.renderImage("pdf",18);
        break;
      case '.doc': 
        txt=txt+this.renderImage("word",18);
        break;
      case '.docx': 
        txt=txt+this.renderImage("word",18);
        break;
      case '.rtf': 
        txt=txt+this.renderImage("word",18);
        break;
      default:
        txt=txt+this.renderImage("paperclip",18);
        break;
      }
    }
    else txt=txt+this.renderImage("paperclip",18);
    if (filename_length==null) filename_length=25;
    var filename=file.bezeichnung;
    if (i>filename_length) {
      var filetype=file.filename.substr(i,99);
      filename=filename.substr(0,filename_length)+"[..]"+filetype;
    }
//    txt=txt+' <a target="_blank" href="'+masterData.files_url+'/files/'+file.domain_type+'/'+file.domain_id+'/'+file.filename+'" title="'+title+'">'+filename+'</a>';
    txt=txt+' <a target="_blank" href="?q='+masterData.modulename+'/filedownload&id='+file.id+'&filename='+file.filename+'">'+filename+'</a>';
    txt=txt+'</span><br/>';
  }
  return txt;
};

StandardTableView.prototype.renderTooltipForFiles = function (tooltip, f, editauth) {
  var t=this;
  var rows = new Array();
  var i = f.bezeichnung.lastIndexOf(".");
  if (i>0)
    
  switch (f.bezeichnung.substr(i,99).toLowerCase()) {
    case '.mp3': 
      rows.push('<audio style="width:300px" src="'+masterData.files_url+"/files/"+f.domain_type+"/"+f.domain_id+"/"+f.filename+'"/>');
      break;
    case '.wav': 
      rows.push('<audio style="width:300px" src="'+masterData.files_url+"/files/"+f.domain_type+"/"+f.domain_id+"/"+f.filename+'"/>');
      break;
    case '.m4a': 
      rows.push('<video src="'+masterData.files_url+"/files/"+f.domain_type+"/"+f.domain_id+"/"+f.filename+'"/>');
      break;
    case '.mp4': 
      rows.push('<video src="'+masterData.files_url+"/files/"+f.domain_type+"/"+f.domain_id+"/"+f.filename+'"/>');
      break;
    case '.avi': 
      rows.push('<video src="'+masterData.files_url+"/files/"+f.domain_type+"/"+f.domain_id+"/"+f.filename+'"/>');
      break;
    case '.mpeg': 
      rows.push('<video src="'+masterData.files_url+"/files/"+f.domain_type+"/"+f.domain_id+"/"+f.filename+'"/>');
      break;
    case '.jpg': 
      rows.push('<img style="max-width:200px;max-height:200px" src="'+masterData.files_url+"/files/"+f.domain_type+"/"+f.domain_id+"/"+f.filename+'"/>');
      break;
    case '.png': 
      rows.push('<img style="max-width:200px;max-height:200px" src="'+masterData.files_url+"/files/"+f.domain_type+"/"+f.domain_id+"/"+f.filename+'"/>');
      break;
    default:
      break;
  }
  
  if (f.modified_date!=null)
    rows.push("<p>vom "+f.modified_date.toDateEn(true).toStringDe(true)+" ("+f.modified_username+")");
  rows.push("<p>");

  rows.push('<a class="btn btn-success btn-small" target="_blank" href="?q='+masterData.modulename+'/filedownload&id='+f.id+'&filename='+f.filename+'&type=download">Herunterladen</a>&nbsp;');

  var title=f.bezeichnung;
  if (editauth) {
    rows.push(form_renderButton({label:"Datei l&ouml;schen", cssid:"file_delete", htmlclass:"btn-danger btn-small"})+"&nbsp;");
    title='<span id="file_name">'+f.bezeichnung+" "+form_renderImage({label:"Datei Umbenennen", src:"options.png", width:20, cssid:"file_rename"})+'</span>';
  }
  
  rows.push(form_renderHidden({cssid:"file",value:"true"}));
  return [rows.join(""), title] 
};


StandardTableView.prototype.tooltipCallbackForFiles = function(id, tooltip, filecontainer, filecontainer_id) {
  var t=this;
  $('video,audio').mediaelementplayer({
   // shows debug errors on screen
      enablePluginDebug: false,
      pluginPath: 'system/assets/mediaelements/',
      // name of flash file
      flashName: 'flashmediaelement.swf',
      // name of silverlight file
      silverlightName: 'silverlightmediaelement.xap',
      autoplay:true,
      //plugins: ['flash', 'silverlight'],
      success: function(mediaElement, domObject) {
        mediaElement.play();
        },
      error: function(a) {
        //alert('Fehler beim Laden der Mediendaten!');
      }
  });    
  
  var f=filecontainer[filecontainer_id].files[id];
  tooltip.find("#file_download").click(function() {
    t.clearTooltip(true);
    return false;
  });
  tooltip.find("#file_rename").click(function() {
    tooltip.find("#file_name").html(form_renderInput({cssid:"newfilename", value:f.bezeichnung, controlgroup:false, type:"medium"}));
    $('#newfilename').keyup(function(e) {
      var newfilename=$(this).val();
      // Enter
      if (e.keyCode == 13) {
        churchInterface.jsendWrite({func:"renameFile", id:f.id, filename:newfilename}, function(ok, data) {
          if (ok) {
            f.bezeichnung=newfilename;
            t.renderFiles(filecontainer, filecontainer_id);        
          }
          else alert("Fehler beim Speichern: "+data);
        });        
      }
      // Escape
      else if (e.keyCode == 27) {
        t.clearTooltip(true);
      }
    });
    return false;
  });
  tooltip.find("#file_delete").click(function() {
    if (confirm("Soll wirklich die Datei entfernt werden?")) {
      churchInterface.jsendWrite({func:"delFile", id:f.id}, function(ok, data) {
        if (ok) {
          delete filecontainer[filecontainer_id].files[id];
          t.renderFiles(filecontainer, filecontainer_id);        
        }
        else alert("Fehler beim Speichern: "+data);
      });        
      t.clearTooltip(true);    
    }
  });
};


/**
 * Rendert alle div.filelist mit Inhalt von filecontainer. 
 * @param header
 * @param filecontainer
 * @param specific_domain_id oder null, wenn alle
 * @param delete_func funtion(file_id)
 */
StandardTableView.prototype.renderFilelist = function(header, filecontainer, specific_domain_id, delete_func, filename_length) {
  var t=this; 
  if (filename_length==null) filename_length=25;
  var specialselector="";
  if (specific_domain_id!=null)
    specialselector="[data-id="+specific_domain_id+"]";
  $("#cdb_content div.filelist"+specialselector).each(function() {
    $(this).html("");
    if ((filecontainer!=null) && (filecontainer[$(this).attr("data-id")]!=null)) {
      var files=filecontainer[$(this).attr("data-id")].files;
      if ((files!=null) && (churchcore_countObjectElements(files)>0)) {
        var txt=header;
        if (txt!="") txt=txt+"<br/>"; 
        $.each(churchcore_sortData(files, "bezeichnung"), function(k,a) {
          txt=txt+t.renderFile(a,filename_length);
        });
        $(this).html(txt);
      }
    }
  });
};


/**
 * 
 * @param title 
 * @param text
 * @param width
 * @param height
 * @param buttons
 * @return jQuery-Element
 */
StandardTableView.prototype.showDialog = function(title, text, width, height, buttons) {
  return form_showDialog (title, text, width, height, buttons);
};


/**
 * 
 * @param divid
 * @param curDate aktuelles Datum
 * @param func function fuer erfolgte Auswahl mit Arg func(dateText,divid)
 */
StandardTableView.prototype.implantDatePicker = function(divid, curDate, func, highlightDay) {
  form_implantDatePicker("dp_"+divid, curDate, func, highlightDay);
};


StandardTableView.prototype.standardFieldCoder = function (typ, arr) {
  alert("Keine Code gefunden fuer: "+typ);
  return null;
};

/**
 * Holt sich die Standardfelder und füllt das Elem damit. Außerdem wird ein Callback für Selects erzeugt, 
 * so dass Änderungen sofort beachtet werden (z.B. Code:XXX)
 */
StandardTableView.prototype.renderStandardFieldsAsSelect = function (elem, fieldname, arr, authArray) {
  var this_object=this;
  elem.html(this.getStandardFieldsAsSelect(fieldname, arr, authArray));
  $("#standardFieldsAsSelect select").change(function(c) {
    var obj=this_object.getSaveObjectFromInputFields(null, fieldname, arr);
    this_object.renderStandardFieldsAsSelect(elem, fieldname, obj, authArray);
  });
};


StandardTableView.prototype.checkFieldPermission = function (field, authArray) {
  var res=true;
  if (field.auth!=null && field.auth!="") {
    res=false;
    $.each(churchcore_getAuthAsArray(field.auth), function(k,auth) {
      if (auth!=null) 
        if ((masterData.auth[auth]!=null) || (authArray!=null && churchcore_inArray(auth, authArray))) 
          res=true;
    });
  }
  return res;
};

StandardTableView.prototype.getStandardFieldAsSelect = function (field, arr, elem, authArray) {
  var t=this;
  var value=arr[elem];
  var o = new Object();
  o.label=field.text;
  o.cssid="Input"+field.sql;
  o.disabled=!t.checkFieldPermission(field, authArray);
  switch (field.type) {
    case "select":
      var data = null;
      if (field.selector.indexOf("code:")==0) {
        data=this.standardFieldCoder(field.selector.substr(5,99), arr);
      }
      else
        data=masterData[field.selector];
      o.selected=value;
      o.data=data;
      return form_renderSelect(o);
    case "date":
      if ((value!=null) && (value!="")) o.value=value.toDateEn(false).toStringDe(false);
      else o.value="";
      return form_renderInput(o);
    case "checkbox":      
      o.checked=((value!=null) && (value==1));
      return form_renderCheckbox(o);
    case "textarea":
      o.maxlength=field.length;
      o.data=value;
      return form_renderTextarea(o);
    default:
      o.email=(field.sql=="email");
      o.value=value;
      o.maxlength=field.length; 
      return form_renderInput(o);
  }   
};

/**
 * 
 * @param fieldname - z.Bsp. f_address
 * @param arr - Array mit den zugehˆrigen Daten, z.Bsp. allPersons[x].details
 * @param authArray - Array mit Strings, bei denen man das bearbeiten darf
 */
StandardTableView.prototype.getStandardFieldsAsSelect = function (fieldname, arr, authArray) {
  var t=this;
  var rows = new Array();
  var form = new CC_Form(null, arr, "standardFieldAsSelect");
  $.each(masterData.fields[fieldname].fields, function(elem, field) {
    //TODO: Wechsel auf Formular. Problem ist noch die ccsid, denn diese ist hier ohne "Input..."
    //form.addStandardField(f, authArray);     
    form.addHtml(t.getStandardFieldAsSelect(field, arr, elem, authArray));
  });
  return form.render(null, "horizontal");
};

/**
 * 
 * @param id
 * @param fieldname - Fields Name, also zum Beispiel f_address
 * @param arr
 * @return obj
 */
StandardTableView.prototype.getSaveObjectFromInputFields = function(id, fieldname, arr) {
  var obj=new Object();
  obj["id"]=id;               
  obj["func"] =fieldname;                    

  // Wenn field ueberhaupt vorhanden ist
  if (masterData.fields[obj["func"]]!=null) {
    // Setzen der Variable im Browser und Aufbereiten des Obj für Ajax
    for (var elem in masterData.fields[obj["func"]].fields) {
      if (!$("#Input" + elem).is(':disabled')) {
        if (masterData.fields[obj["func"]].fields[elem].type=="date") {
            if (($("#Input" + elem).val()!=null)  && ($("#Input" + elem).val().toDateDe()!=null))
              arr[elem] = $("#Input" + elem).val().toDateDe().toStringEn();
            else arr[elem]=null;
        }
        else if (masterData.fields[obj["func"]].fields[elem].type=="checkbox") {
          if ($("#Input" + elem).attr("checked")=="checked")
            arr[elem] = 1;
          else  
            arr[elem] = 0;          
        }
        else {
          if ($("#Input" + elem).val()!=null)
            arr[elem] = $("#Input" + elem).val().trim();
          else 
            arr[elem] =null;
        }
        obj[elem] = arr[elem];
      }
    }
  }   
  return obj;
};

StandardTableView.prototype.renderImage = function(imageName, width, title) {
  if (width==null) width=20;
  if (title==null) title="";
  return '<img width="'+width+'px" style="max-width:'+width+'px" title="'+title+'" src="'+masterData.modulespath+'/images/'+imageName+'.png"/>';  
};

StandardTableView.prototype.renderYesNo = function(nr, width) {
  return form_renderYesNo(nr, width);
};


StandardTableView.prototype.renderField = function(elem, field, a, write_allowed, authArray) {
  var t=this;
  
  if (debug) console.log(field);
  _text="";
  if ((a[elem]!=null) && (a[elem] != "") && 
      (t.checkFieldPermission(field, authArray))) {
    
    var eol=field.eol;
    if (eol.indexOf("%")>-1) {
      _text = _text + eol.substr(0,eol.indexOf("%"));
      eol=eol.substr(eol.indexOf("%")+1,99);
    }
    
    if (field.shorttext != "") {
      _text = _text + field.shorttext + ": ";
    }       
    _text=_text+'<span class="content">';      
    if (field.type=="date") {
      _text = _text + a[elem].toDateEn().toStringDe();
    } 
    else if (field.type!="select") {
      if ((elem=="strasse") || (elem=="ort") || (elem=="plz"))
        _text=_text + '<a style="color:black" id="extern" target="_blank" href="http://maps.google.de/?q='+a.strasse+", "+a.plz+" "+a.ort+'">'+a[elem]+'</a>';
      else if ((elem=="telefonprivat") || (elem=="telefongeschaeftlich") || (elem=="telefonhandy"))
        _text = _text + '<a id="extern" style="color:black" href="tel:'+a[elem]+'">'+a[elem]+'</a>';
      else if ((elem=="email"))
        _text=_text + '<a style="color:black" id="extern" href="'+'mailto:'+a.email+'">'+a[elem]+'</a>';          
      else
        _text = _text + a[elem];                
    }  
    else {
      if (masterData[field.selector][a[elem]]!=null) {
        _text = _text + masterData[field.selector][a[elem]].bezeichnung;
        if (masterData[field.selector][a[elem]].auth!=null)
          _text = _text + "&nbsp;"+t.renderImage("schluessel",16,"Berechtigungen: "+t.getAuthAsArray(masterData[field.selector][a[elem]].auth).join(", "));
      }
      else 
        _text = _text +'<font color="red">Id:'+a[elem]+"?</font>";
    }
    _text = _text + '</span>' + eol;              
  }
  return _text;
};

/*
 * Rendert die Felder in Fields als HTML-Text und gibt diesen als String zurück
 */
StandardTableView.prototype.renderFields = function(fields, a, write_allowed, authArray) {
  var t=this;
  var _text=""; 

//  _text=_text+"<p style='line-height:100%;' id=\"pDetail"+fields.arrayname+"\"><b><i>"+fields.text+"</i></b>&nbsp;&nbsp;";
  _text=_text+"<h4 id=\"pDetail"+fields.arrayname+"\">"+fields.text+"&nbsp;&nbsp;";
  if (write_allowed)
//    _text=_text+'<a href="" id="'+fields.arrayname+'"><img width="20px" align="absmiddle" src="'+masterData.modulespath+'/images/options.png"/></a>';
  _text=_text+'<a href="" id="'+fields.arrayname+'">'+t.renderImage("options", 20)+'</a>';

    
  _text=_text+"</h4><p style='line-height:100%'><small>";

  $.each(fields.fields, function(elem, field) {
    _text=_text+t.renderField(elem, field, a, write_allowed, authArray);
  }); 
  
  _text=_text+"</small><br/>";

  return _text;
};



StandardTableView.prototype.renderInput2 = function (options) {
  return form_renderInput(options);
};


/**
 * 
 * @param id
 * @param title
 * @param text
 * @param size
 * @param disabled
 * @return String of html code
 */
StandardTableView.prototype.renderInput = function (id, title, text, size, disabled) {
  if (text==null) text="";
  if (size==null) size=10;
  if ((disabled==null) || (disabled==false)) disabletxt="";
  else disabletxt="disabled";
  return (title!=null?title+"<td>":"")+'<input type="text" size=\"'+size+'\" id="'+id+'" value="'+text+'" '+disabletxt+'/>';
};

/**
 * 
 * @param id
 * @param title
 * @param text
 * @param cols
 * @param rows
 * @param disabled
 * @return String of HTML Code
 */
StandardTableView.prototype.renderTextarea = function(id, title, text, cols, rows, disabled) {
  if (text==null) text="";
  if ((disabled==null) || (disabled==false)) disabletxt="";
  else disabletxt="disabled";
  return title+"<td> "+'<textarea type="text" cols=\"'+cols+'\" rows=\"'+rows+'\" id="'+id+'" disabletxt'+'>'+text+"</textarea>";
};


StandardTableView.prototype.sortMasterData = function (data) {
  return churchcore_sortMasterData(data);
};


/**
 * 
 * @param {Object} selected - id des vorselektierten Eintrags
 * @param {Object} elem - Name des Elementes
 * @param {Object} masterData - Welches MasterData genommen wird, z.Bsp. MasterData.f_status
 */
StandardTableView.prototype.renderSelect = function(selected, elem, masterData, disabled, func) {
  //alert("Deprectated render select");
  var _text="";
  var this_object=this;
  if (masterData==null)
    return "<select><option>-</select>";
  if ((disabled!=null) && (disabled)) 
    _text=_text+"<select id=\"Input"+elem+"\" disabled=\"true\">";
  else 
    _text=_text+"<select id=\"Input"+elem+"\">";
  $.each(this_object.sortMasterData(masterData), function (k,a) {
    if ((typeof func !="function") || (func(a))) {
      if ((selected!=null) && (a.id==selected)) 
        _text=_text+"<option selected value=\""+a.id+"\">"+a.bezeichnung+"</option>";         
      else            
        _text=_text+"<option value=\""+a.id+"\">"+a.bezeichnung+"</option>";
    }
  });
  _text=_text+"</select>";
  return _text;
};

StandardTableView.prototype.renderPersonImage = function(id, width) {
  return  form_renderPersonImage(allPersons[id].imageurl, width);
};


/**
 * 
 * @param title 
 * @param searchAll false=nur alle Person in der Variabel allPersons suchen / true=online suchen, nach allen in meinen Bereichen
 * @param resultFunction gibt id zurück
 */
StandardTableView.prototype.renderPersonSelect = function(title, searchAll, resultFunction) {
  var _searchString="";
  var rows = new Array();
  var this_object=this;
  rows.push("Suche: <input type=\"text\" size=\"10\" id=\"searchAddress\"/ value=\""+_searchString+"\">&nbsp;&nbsp;<br/>");
  rows.push("<div id=\"cdb_personselector\">"+"<i>Bitte Namen eingeben...</i>"+"</div><br/>");
  if (searchAll) rows.push("<p><small>Gesucht wird nach den f&uuml;r Dich sichtbaren Personen sowie allen Personen aus Deinen Bereichen.</small>");
  
  //elem.html(rows.join(""));
  var elem = form_showCancelDialog("Auswahl einer Person",rows.join(""));
  
  if (!searchAll) {
  
    $("#searchAddress").keyup(function(c) {
      if ($(this).val()=="") $("#cdb_personselector").html("");
      if ((_searchString!=$(this).val().toUpperCase()) && ($(this).val().length>0)) {
        _searchString=$(this).val().toUpperCase();
        
        var rows = new Array();
        i=0;
        rows.push('<table class="table table-condensed">');
        $.each(allPersons, function(k, a) {
          if (i<20) { 
            if ((((masterData.status[a.status_id]==null) || (masterData.status[a.status_id].infreitextauswahl_yn==0))) || 
                ((_searchString!="") && 
                (a.spitzname.toUpperCase().indexOf(_searchString)!=0) &&
                (a.name.toUpperCase().indexOf(_searchString)!=0) &&
                (a.vorname.toUpperCase().indexOf(_searchString)!=0) &&
                (a.email.toUpperCase().indexOf(_searchString)!=0) &&
                ((a.vorname+" "+a.name).toUpperCase().indexOf(_searchString)!=0) &&
                (a.id!=_searchString))) {
            }
            else {
              rows.push("<tr><td><a href=\"#\" id=\""+a.id+"\">"+this_object.renderPersonImage(a.id,42)+"</a>");
              rows.push("<td><a href=\"#\" id=\""+a.id+"\">"+a.name+",  "+a.vorname+" "+
                        (a.spitzname!=""?"("+a.spitzname+")":"")+"</a>");
              i++;
            };
          }        
        });
        rows.push('</table>');
        $("#cdb_personselector").html(rows.join(""));
        // Callbacks
        $("#cdb_personselector a").click(function (e) {
          id = $(this).attr("id");
          resultFunction(id);
          elem.empty().remove();
          return false;
        });      
      }  
    });           
  }
  else {
    this.autocompletePersonSelect("#searchAddress", true, function(divid, ui) {
      resultFunction(ui.item.value);
      elem.empty().remove();
      return false;
    });
  }

};


StandardTableView.prototype.renderPersonImageUrl = function(url, width) {
  if (url==null) url="nobody.gif";
  return '<img style="max-width:'+width+'px;max-height:'+width+'px;" src="'+masterData.files_url+"/fotos/"+url+"\"/>";          
};

StandardTableView.prototype.autocompletePersonSelect = function (divid, withMyDeps, func) {
  form_autocompletePersonSelect(divid, withMyDeps, func);
};



StandardTableView.prototype.getAuthAsArray = function (auth) {
  var t=this;
  var list=new Array();
  $.each(auth, function(k,a) {
    list.push(t.renderAuth(k));
  });
  return list;
};

StandardTableView.prototype.renderEditDomainAuth = function(auth) {
  var this_object=this;
  var rows = new Array();
  rows.push('<p class="pull-right">'+form_renderHelpLink("Berechtigung")+'</p>');
  $.each(masterData.auth_table, function(k, modules) {
    rows.push("<table>");
    rows.push("<tr><td colspan=\"2\"><h3>"+k+"</h3>");
    $.each(modules, function(i,auths) {
      var checked="";
      if ((auth!=null) && (auth[auths.id]!=null)) checked="checked";
      rows.push('<tr><td>');
      if (auths.datenfeld==null)
        rows.push('<input type="checkbox" id="'+auths.id+'" '+checked+' class="cdb-checkbox"></input>');
      else {
        rows.push('<a href="" id="edit_'+auths.id+'">'+this_object.renderImage("options")+'</a>');
      }
      rows.push("&nbsp; <td>"+auths.auth+"<br><p><small>"+auths.bezeichnung+"</small>");
      
      if (auths.datenfeld!=null) {
        rows.push('<tr><td><td><span data-datenfeld="'+auths.datenfeld+'" class="datenbereich" id="spanedit_'+auths.id+'" style="display:none"><p></span>');
      }
    });
    rows.push("</table>");
  });
  return rows.join("");
};


StandardTableView.prototype.renderAuth = function(auth_id) {
  var res="";
  // Baumstruktur, muß erst mal durch die Module scannen!
  $.each(masterData.auth_table, function(k,module) {
    $.each(module, function(i,a) {
      if (a.id==auth_id) {
        res=k+":"+a.auth;
        return false;
      }
    });
  });
  if (res=="") res="unknown:"+auth_id;
  return res;
};


function renderDatenfeld(elem, auth) {
  var datenfeld=elem.attr("data-datenfeld");
  var auth_id=elem.attr("id").substr(9,99);
  var open=false;
  if (masterData[datenfeld]!=null) {
    if (masterData[datenfeld][-1]==null)
      masterData[datenfeld][-1]={id:-1, sortkey:-999, htmlclass:"super", bezeichnung:"<i>Alle</i>"};
    var arr=new Array();
    $.each(churchcore_sortData(masterData[datenfeld], "sortkey", false, true, "bezeichnung"), function(k,datas) {
      var checked=false;
      if ((auth!=null) && (auth[auth_id]!=null) 
            && (auth[auth_id][datas.id]!=null)) {
        var checked=true;
        open=true;
      }
      var htmlclass="";
      if (datas.htmlclass!=null) htmlclass=datas.htmlclass;
//      txt=txt+'<input type="checkbox" id="'+auth_id+'_'+datas.id+'" '+checked+' class="'+htmlclass+'"></input>&nbsp;'+datas.bezeichnung+"&nbsp; &nbsp;";          
      arr.push({id:auth_id+'_'+datas.id, checked:checked, htmlclass:htmlclass, bezeichnung:datas.bezeichnung});
    });
    if (open) elem.show();
    
    txt="";
    if (arr.length<10) {
      $.each(arr, function(k,a) { 
        txt=txt+'<input type="checkbox" id="'+a.id+'" '+(a.checked?"checked":"")+' class="'+a.htmlclass+'"></input>&nbsp;'+a.bezeichnung+"&nbsp; &nbsp;";          
      });          
    }
    else {
      $.each(arr, function(k,a) { 
        if (a.checked) {
          txt=txt+'<p>'+a.bezeichnung+'<a href="#" class="delAuth" data-id="'+a.id+'"> '+form_renderImage({src:"trashbox.png", width:16})+'</a>';          
          txt=txt+form_renderHidden({value:"checked", cssid:a.id});
        }
      });
      txt=txt+"<p>Weitere hinzuf&uuml;gen:<br/>";
      txt=txt+form_renderSelect({data:arr, cssid:"selectAuth"+auth_id, controlgroup:true, func:function(a){return !a.checked}});
      txt=txt+form_renderButton({label:"Hinzuf&uuml;gen", cssid:"add"+auth_id, htmlclass:"addAuth"});
    }
    elem.html(txt);
    
    elem.find("input:checkbox").click(function() {
      if ($(this).hasClass("super")) {
        if (($(this).attr("checked")=="checked"))
          $(this).parents("span").find("input:checkbox").each(function(k,a) {
            if (!$(this).hasClass("super"))
              $(this).removeAttr("checked");
          });
      }
      else {
        $(this).parents("span").find("input:checkbox.super").each(function(k,a) {
          $(this).removeAttr("checked");
        });
      }
    });
    elem.find("input.addAuth").click(function() {
      var id=$("#selectAuth"+auth_id).val();
      var data_id=id.substr(id.indexOf("_")+1,99);
      if (auth==null) auth=new Object();
      if (auth[auth_id]==null) auth[auth_id]=new Array();
      auth[auth_id][data_id]=data_id;
      renderDatenfeld(elem, auth);
      return false;
    });
    elem.find("a.delAuth").click(function() {
      var id=$(this).attr("data-id");
      var data_id=id.substr(id.indexOf("_")+1,99);
      delete auth[auth_id][data_id];
      renderDatenfeld(elem, auth);
      return false;
    });
  }
}

/**
 * Erstellt das Formular zum Editieren der Rechte
 * @param id 
 * @param auth
 * @param domain_type person, group oder status
 * @param func return function, wenn erfolgreich gespeichert mit (id) als parameter
 */
StandardTableView.prototype.editDomainAuth = function (id, auth, domain_type, func) {
  var this_object=this;
  var rows = new Array();

  if (masterData.cdb_gruppe==null) {
    var elem = this.showDialog("Lade Daten...", "Lade Autorisierungsdaten...", 300, 300);
    churchInterface.jsendRead({func:"loadAuthData"}, function(ok, json){
      elem.dialog("close");
      $.each(json, function(k, a) {
        masterData[k]=a;
      });
      masterData.cdb_gruppe=masterData.groups;
      if (masterData.cdb_gruppe==null) masterData.cdb_gruppe=new Object();
      return this_object.editDomainAuth(id, auth, domain_type, func);
    });
  } 
  else {    
    var elem = this.showDialog("Editieren der Rechte", this.renderEditDomainAuth(auth), 600, 600, {
      "Speichern": function() {
        var obj=new Object();
        elem.find("input[type=checkbox]").each(function(k,a) {
          // Nur positive übertragen
          if (a.checked)
            obj["authid"+a.id]=true;        
        });
        elem.find("input[type=hidden]").each(function(k,a) {
          obj["authid"+a.id]=true;        
        });
        obj.func="saveDomainAuth";
        obj.domain_type=domain_type;
        obj.id=id;
        churchInterface.jsendWrite(obj, null, false);
        if (typeof func =="function") func(id);
        $(this).dialog("close");      
      },
      "Abbruch": function() {
        $(this).dialog("close");
      }
    });
    elem.find("span.datenbereich").each(function() {
      renderDatenfeld($(this), auth);
    });
    // Bei Datenautorisierung nun den Inhalt zeigen
    $("a").click(function(k) {
      if (this.id.indexOf("edit_")==0) {
        $("#span"+this.id).show(); 
        return false;
      }    
    });
  }
};





})(jQuery);
