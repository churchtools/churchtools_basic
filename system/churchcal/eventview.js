
//var fc = $.eventCalendar = { version: "1.0.0" };

$.fn.eventCalendar = function(options) {

  // method calling
  if (typeof options == 'string') {
    var args = Array.prototype.slice.call(arguments, 1);
    var res;
    this.each(function() {
      var calendar = $.data(this, 'eventCalendar');
      if (calendar && $.isFunction(calendar[options])) {
        var r = calendar[options].apply(calendar, args);
        if (res === undefined) {
          res = r;
        }
        if (options == 'destroy') {
          $.removeData(this, 'eventCalendar');
        }
      }
      else alert("no function: "+options)
    });
    if (res !== undefined) {
      return res;
    }
    return this;
  }
    
  this.each(function(i, _element) {
    var element = $(_element);
    var calendar = new eventCalendar(element, options);
    element.data('eventCalendar', calendar); 
  });  
  return this;
};


function eventCalendar(element, options, eventSources) {
  var t = this;
  var timer=null;
  t.render=render;
  t.addEventSource=addEventSource;
  t.removeEventSource=removeEventSource;
  t.startdate=(options.startdate!=null?options.startdate:new Date());
  t.startdate=t.startdate.withoutTime();
  t.enddate=options.enddate;
  
  t.eventSources=new Array();
  
  function addEventSource(s) {
    t.eventSources.push(s);
    t.render();
  }
  function removeEventSource(s) {
    each(t.eventSources, function(k,a) {
      if (a==s) delete t.eventSources[k];
    });
    t.render();
  }

  function render() {
      renderCalendar();
      var d2 = new Date();
      var d = new Date(d2.getFullYear()+"");  
      var year=d.getFullYear();  
      var allData= new Array();
       checker= new Object(); // To check if all data is loaded
      // Get together events with same name and category
      each(t.eventSources, function(k,s) {
        if (s!=null) {
          checker[s.category_id]=true;
          var events = s.events(moment(t.startdate), null, null, function(_events) {
            each(_events, function(i,event) {
              event.container=s.container;
              event.category_id=s.category_id;
              event.compare=s.category_id+"_"+event.start.toStringEn(false)+"_"+event.title;
              var drin=false;
              // For minical we put together dates on one day with same category
              if (minical) {
                each(allData, function(i,b) {
                  if (b.start!=event.start && b.compare==event.compare) {
                    drin=true;
                    if (b.multi==null) {
                      b.multi=new Array();
                      b.multi.push(b.start);
                    }
                    if (!churchcore_inArray(event.start, b.multi)) b.multi.push(event.start);
                    return false;
                  }
                });
              }
              if (!drin) allData.push(event);
            });
          });
          delete checker[s.category_id];
          if (churchcore_countObjectElements(checker)==0) {
            _render();
          }
        }
      });
      
      function _render() {
        var rows = new Array();          
        rows.push('<table class="table table-condensed">');
        var _filter=filterName.toUpperCase();
        var count=0;
        each(churchcore_sortData(allData, "start"), function(k,a) {
          if (a.start>=t.startdate && (t.enddate==null || a.start<=t.enddate)) {
            if ((filterName=="") || (a.title.toUpperCase().indexOf(_filter)>=0)
                  || (a.notizen!=null && a.notizen.toUpperCase().indexOf(_filter)>=0)) {
              rows.push('<tr class="c'+a.category_id+'"><td>');
              
              if (!minical) {
                if ((a.notizen!=null) || (a.link!=null)) 
                  rows.push('<a href="#" class="event event-name" data-id="'+k+'">'+a.title+'</a>');
                else
                  rows.push('<span class="event-name">'+a.title+'</span>');
                rows.push('<span class="event-category">'+a.container.getName(a.category_id)+'</span>');
                rows.push(_renderDate(a));
              }
              // MiniCalender
              else {
                rows.push('<p>');
                if (a.multi==null) {
                  rows.push(_renderDate(a));
                }
                else { 
                  rows.push('<p class="event-date">');
                  rows.push('<span class="date-date">');
                  rows.push(a.start.toStringDe()+"&nbsp;");
                  rows.push('</span><span class="date-time">');
                  each(churchcore_sortData(a.multi, null, true), function(i,b) {
                    rows.push(b.toStringDeTime()+" | ");
                  });
                  rows.push('</span>');
                }
                rows.push('<br>');
                
                if (a.link!=null) 
                  rows.push('<a href="'+a.link+'" class="event-name" target="_parent">'+a.title+'</a>');
                else
                  rows.push('<span class="event-name">'+a.title+'</span>');
                if (a.notizen!=null) rows.push('<br><span class="event-description">'+a.notizen.trim(100)+'</span>');
                
              }
              
  
              var notizen="";
              if ((a.notizen!=null) && (a.notizen!="")) notizen=a.notizen.htmlize();
              
              if ((a.link!=null) && (a.link!="")) {
                if (notizen!="") notizen=notizen+'<br><br>';
                notizen=notizen+'<a class="btn" href="'+a.link+'" '+(embedded?"":'target="_clean"')+'>Weitere Informationen &raquo;</a>'; 
              }
              
              rows.push('<div style="display:none" id="entry'+k+'"><div class="well">'+notizen+'</div></div>');
              count++;
              if (count>=max_entries) return false;
            }
          }
        });
        if ((count>max_entries) && (!minical)) rows.push("<tr><td>...");
        rows.push('</table>');
        if (count==0) rows.push(_("no.entry.found"));
        
        $("#calendar").html(rows.join(""));  
        
        $("#calendar a.event").click(function() {
          var id=$(this).attr("data-id");
          var elem=$("#entry"+id);
          elem.animate({ height: 'toggle'}, "fast");
          return false;
        });
      }
      
      
  //    timer=null;
  //  },50);
  }
  
  function _renderDate(a) {
    var rows=new Array();
    rows.push('<p class="event-date">');
    rows.push('<span class="date-date">');
      rows.push(a.start.toStringDe(false)+'&nbsp;');
    rows.push('</span>');
    rows.push('<span class="date-time">');
    if (!a.allDay)
      rows.push(a.start.toStringDeTime());
    if (a.end!=null) {
      if (a.end.getDate()!=a.start.getDate()) {
        rows.push(" - "+a.end.toStringDe(!a.allDay));
      }
      else 
        rows.push(" - "+a.end.toStringDeTime());
    }
    rows.push('</span>');    
    return rows.join("");
  }

  function renderCalendar() {
    $("#minicalendar").datepicker({
      dateFormat: 'dd.mm.yy',
      showButtonPanel: true,
      dayNamesMin: dayNamesMin,
      monthNames: getMonthNames(), 
      currentText: _("today"),
      firstDay: 1,
      onSelect : function(dateText, inst) {
        t.startdate=dateText.toDateDe();
        if (embedded)  $("#minicalendar").hide();
        render();
      },
      onChangeMonthYear:function(year, month, inst) {
        var dt = new Date();
        // Wenn es der aktuelle Monat ist, dann gehe auf den heutigen Tag
        if ((dt.getFullYear()==year) && (dt.getMonth()+1==month))
          t.startdate=dt;
        else
          t.startdate=new Date(year, month-1);
        render();
      }
      
    });    
    $("#minicalendar").datepicker($.datepicker.regional['de']);
    //$("#minicalendar").datepicker('setDate', t.startDate.toStringDe());
    shortcut.add("esc", function() {
      $("#minicalendar").hide();
    });  

  };

}
