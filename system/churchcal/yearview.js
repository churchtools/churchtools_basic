
//var fc = $.yearCalendar = { version: "1.0.0" };

$.fn.yearCalendar = function(options) {

  // method calling
  if (typeof options == 'string') {
    var args = Array.prototype.slice.call(arguments, 1);
    var res;
    this.each(function() {
      var calendar = $.data(this, 'yearCalendar');
      if (calendar && $.isFunction(calendar[options])) {
        var r = calendar[options].apply(calendar, args);
        if (res === undefined) {
          res = r;
        }
        if (options == 'destroy') {
          $.removeData(this, 'yearCalendar');
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
    var calendar = new YearCalendar(element, options);
    element.data('yearCalendar', calendar); // TODO: look into memory leak implications
    //calendar.render();
  });  
  return this;
};



function YearCalendar(element, options, eventSources) {
  var t = this;
  var renderNecessary=false;
  t.render=render;
  t.addEventSource=addEventSource;
  t.removeEventSource=removeEventSource;
  
  t.eventSources=new Array();
  
  function addEventSource(s) {
    if (s.container instanceof CalAbsentsType) {
      t.eventSources.push(s);
      t.render();
    }
  }
  function removeEventSource(s) {
    $.each(t.eventSources, function(k,a) {
      if (a==s) delete t.eventSources[k];
    });
    t.render();
  }
  
  function _monthView(year, month, names) {
    var rows = new Array();  
    rows.push("<h3>"+monthNames[month]+" "+year+"</h3>");
    rows.push('<table class="table table-condensed table-bordered"><tr><th>Name');
    
    var d = new Date(year, month, 1);
    while (d.getMonth()==month) {
      rows.push('<th><a href="?q=churchcal&viewname=calView&date='+d.toStringEn(false)+'">'
                    +d.getDate().maskWithZero(2)+'</a>');
      d.addDays(1); 
    }

    $.each(churchcore_sortData(names, "name"), function(k,event) {
      rows.push('<tr><td>'+event.name);      
      var d = new Date(year, month, 1);
      while (d.getMonth()==month) {
        var drin=false;
        var title="";
        var color="";
        $.each(event.events, function(i,e) {
          if ((e.start<=d) && (e.end>=d)) {
            drin=true;
            title=e.title+' ';
            color=e.color;
            if (e.start.toStringDe()==e.end.toStringDe())
              title=title+e.start.toStringDe();
            else
              title=title+e.start.toStringDe()+'-'+e.end.toStringDe();
            if (e.bezeichnung!=null)
              title=title+" "+e.bezeichnung;
          }
        });
        if (!drin)
          rows.push('<td>');
        else
          rows.push('<td style="background:'+color+'" title="'+title+'">');
        d.addDays(1); 
      }
    });

    rows.push('</table>');  
    return rows.join("");
  }
  
  function render() {
    renderNecessary=true;
    window.setTimeout(function() {
      if (renderNecessary==true) {
        renderNecessary=false;

        var d2 = new Date();
        var d = new Date(d2.getFullYear()+"");  
        var year=d.getFullYear();  
        var rows = new Array();  
        
        var names= new Object();
        $.each(t.eventSources, function(k,s) {
          if (s!=null) {
            $.each(s.events, function(i,event) {
              if (names[event.id]==null) {
                names[event.id]=new Object();
                names[event.id].events=new Array();
              }
              names[event.id].name=event.title;
              names[event.id].sortkey=event.sortkey;
              
              if ((event.color==null) || (event.color=="")) event.color=s.color;
              
              names[event.id].events.push(event);
            });
          }
        });
        for (var month=0; month<12; month++) {
          rows.push(_monthView(year, month, names)); 
        }
        $("#calendar").html(rows.join(""));      
      }
    },10);
  }
}
