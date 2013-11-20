debug=false;
dayNames= ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];


function _(s) {  
  if (lang[s]==null) return "***"+s+"***";
  res=lang[s];
  $.each(arguments, function(k,a) {
    if (k>0) res=res.replace("{"+(k-1), a).replace("}","");        
  });  
  return res;  
}

function churchcore_handyformat() {
  return $(window).width()<767;  
}

/*
 * Check permission of auth. When datafield is given, this will be checked, too
 * E.g. user_access("edit category", 2);
 * @return true/false
 */
function user_access(auth, datafield) {
  if (masterData.auth!=null && masterData.auth[auth]!=null)
    if (datafield==null) return true;
    else if (masterData.auth[auth][datafield]!=null) return true;
    
  return false;
}

function churchcore_getBezeichnung(name, id) {
  if (masterData[name]==null)
    return '<span class="error">Stammdaten '+name+"?</span>";
  if (masterData[name][id]==null)
    return '<span class="error">Id:'+id+"?</span>";
  return masterData[name][id].bezeichnung;
}

/**
 * Sortiert Daten im Object
 * @param data Object, das sortiert werden soll
 * @param sortVariable, nach der sortiert werden soll
 * @param sortVariable2: wenn sortVariable gleich ist, dann gehe nach dem zweiten.
 * @return sortiertes Object
 */
function churchcore_sortData(data, sortVariable, reverse, alphanumeric, sortVariable2) {
  if ((alphanumeric==null) || (alphanumeric==true))
    return churchcore_sortData_alpha(data, sortVariable, reverse, sortVariable2);
  else
    return churchcore_sortData_numeric(data, sortVariable, reverse, sortVariable2);
}  

function churchcore_isObjectEmpty(obj) {
  if (obj==null) return false;
  var ret = true;
  $.each(obj, function(k,a) {
    ret=false;
    return false;
  });
  return ret;
}

function churchcore_countObjectElements(obj) {
  if (obj==null) return 0;
  var ret = 0;
  $.each(obj, function(k,a) {
    ret++;
  });
  return ret;
}

function churchcore_getFirstElement(obj) {
  if (obj==null) return null;
  var elem=null;
  $.each(obj, function(k,a) {
    elem=a;
    return false;
  });
  return elem;
}

function churchcore_sortData_alpha(data, sortVariable, reverse, sortVariable2) {
  if (data==null) return null;
  var r=1;
  if (reverse) r=(-1);

  function sortfunc(a,b) {
    if ((a==null) || (b==null)) return 0;
        
    var a1=a[sortVariable];
    var b1=b[sortVariable];
    
    if (a[sortVariable]==null) {
      if ((a[sortVariable2!=null] && (b[sortVariable2]!=null))) {
        a1=a[sortVariable2];
        b1=b[sortVariable2];
      }
      else return 1*r ;
    }
    else if (b1==null) { 
      if ((a[sortVariable2!=null] && (b[sortVariable2]!=null))) {
        a1=a[sortVariable2];
        b1=b[sortVariable2];
      }
      else return (-1)*r;
    }    
    
    if (((typeof a1)=="string") && ((typeof b1)=="string")) {
      a1 = a1.toLowerCase();
      a1 = a1.replace(/\u00e4/g,"azz");
      a1 = a1.replace(/\u00f6/g,"ozz");
      a1 = a1.replace(/\u00fc/g,"uzz");
      a1 = a1.replace(/\u00df/g,"szz");
    
      b1 = b1.toLowerCase();
      b1 = b1.replace(/\u00e4/g,"azz");
      b1 = b1.replace(/\u00f6/g,"ozz");
      b1 = b1.replace(/\u00fc/g,"uzz");
      b1 = b1.replace(/\u00df/g,"szz");
    }
    
    if (a1==b1)
      return 0;
    else if (a1>b1) return 1*r; 
    else return (-1)*r;
  }
  var arr = new Array();
  var i=0;
  // Hier muss ich mit i zŠhlen und nicht mit k, weil Arrays it Neg. Index Probleme machen.
  jQuery.each(data,function(k,a){
    arr[i]=a;
    i=i+1;
  });
  arr.sort(sortfunc);
  obj = new Object();
  jQuery.each(arr,function(k,a){
    if (a!=null)
      obj[k]=a;
  });
  return obj;
};

function churchcore_sortData_numeric(data, sortVariable, reverse, sortVariable2) {
  if (data==null) return null;
  var r=1;
  if (reverse) r=(-1);

  function sortfunc(a,b) {
    if ((a==null) || (b==null)) return 0;
    
    var a1=a[sortVariable];
    var b1=b[sortVariable];
    
    if (a[sortVariable]==null) {
      if ((a[sortVariable2!=null] && (b[sortVariable2]!=null))) {
        a1=a[sortVariable2];
        b1=b[sortVariable2];
      }
      else return 1*r ;
    }
    else if (b1==null) { 
      if ((a[sortVariable2!=null] && (b[sortVariable2]!=null))) {
        a1=a[sortVariable2];
        b1=b[sortVariable2];
      }
      else return (-1)*r;
    }    

    if (a1*1>b1*1) return 1*r;
    else return (-1)*r;
  }
  arr = new Array();
  jQuery.each(data,function(k,a){
    arr[k]=a;
  });
  arr.sort(sortfunc);
  obj = new Object();
  jQuery.each(arr,function(k,a){
    if (a!=null)
      obj[k]=a;
  });
  return obj;
};

function churchcore_sortArray(data, sortVariable, reverse, alphanumeric) {
  if ((alphanumeric==null) || (alphanumeric==true))
    return churchcore_sortArray_alpha(data, sortVariable, reverse);
  else
    return churchcore_sortArray_numeric(data, sortVariable, reverse);
}  
  

function churchcore_sortArray_alpha(arr, sortVariable, reverse) {
  var r=1;
  if (reverse) r=(-1);

  function sortfunc(a,b) {
    if ((a==null) || (b==null)) return 0;
    else if (a[sortVariable]==null) return 1*r ;
    else if (b[sortVariable]==null) return (-1)*r;
    
    if (a[sortVariable].toLowerCase()>b[sortVariable].toLowerCase()) return 1*r;
    else return (-1)*r;
  }
  return arr.sort(sortfunc);
};

function churchcore_sortArray_numeric(arr, sortVariable, reverse) {
  var r=1;
  if (reverse) r=(-1);

  function sortfunc(a,b) {
    if ((a==null) || (b==null)) return 0;
    else if (a[sortVariable]==null) return 1*r ;
    else if (b[sortVariable]==null) return (-1)*r;
    
    if (a[sortVariable]*1>b[sortVariable]*1) return 1*r;
    else return (-1)*r;
  }
  return arr.sort(sortfunc);
};

// Sortiert nach Sortkey oder wenn der nicht da ist nach Bezeichnung.
churchcore_sortMasterData = function (data) {
  function sortfunc(a,b) {
    if ((a==null) || (b==null)) return 0;        

    // Erst schauen, ob es ein SortKey gibt, sonst gehe nach Bezeichnung
    if ((a.sortkey!=null) || (b.sortkey!=null)) {
      if (a.sortkey==null) return 1;
      if (b.sortkey==null) return -1;      
      // Erzwinge numerisch
      if (a.sortkey*1>b.sortkey*1) return 1;
      else return -1;
    }
    else {
      // ID=0 soll als erstes stehen bleiben, denn das ist meistens "Unbekannt" o.Š.
      if ((a.id!=null) && (a.id==0)) return -1;
      if ((b.id!=null) && (b.id==0)) return 1;

      if (a.bezeichnung==null) return 1;
      if (b.bezeichnung==null) return -1;      
      
      var a1 = a.bezeichnung.toLowerCase();
      a1 = a1.replace(/\u00e4/g,"azz");
      a1 = a1.replace(/\u00f6/g,"ozz");
      a1 = a1.replace(/\u00fc/g,"uzz");
      a1 = a1.replace(/\u00df/g,"szz");
    
      var b1 = b.bezeichnung.toLowerCase();
      b1 = b1.replace(/\u00e4/g,"azz");
      b1 = b1.replace(/\u00f6/g,"ozz");
      b1 = b1.replace(/\u00fc/g,"uzz");
      b1 = b1.replace(/\u00df/g,"szz");
      if (a1>b1) return 1;
      else return -1;
    }
  }
  arr = new Array();
  jQuery.each(data,function(k,a){
    arr.push(a);
  });
  arr.sort(sortfunc);
  obj = new Object();
  jQuery.each(arr,function(k,a){
    if (a!=null)
      obj[k]=a;
  });
  return obj;
};

function churchcore_sendEmail(modulename, to, subject, body) {
  jQuery.getJSON("index.php?q="+modulename+"/ajax", { func: "send_email",  subject: subject, body:body, to:to}, function(json) {
  });
}

function churchcore_inObject(obj, objArray) {
  if ((obj==null) || (objArray==null)) return false;
  ret=false;
  jQuery.each(objArray, function(k,a) {
    if (a==obj) ret=true;
  });
  return ret;
}
function churchcore_inArray(obj, arrArray) {
  if ((obj==null) || (arrArray==null)) return false;
  ret=false;
  jQuery.each(arrArray, function(k,a) {
    if (a==obj) ret=true;
  });
  return ret;
}
function churchcore_removeFromArray(obj, arrArray) {
  jQuery.each(arrArray, function(k,a) {
    if (a==obj) {
      delete arrArray[k];
      return false;
    }
  });
}

function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

/**
 * Returns the time in Minute:Seconds
 */
String.prototype.formatMS = function() {
  var s=this%60+'';
  if (s.length==1) s='0'+s;
  return Math.round(this/60)+":"+s;
}

String.prototype.endsWith = function(suffix) {
  return this.indexOf(suffix, this.length - suffix.length) !== -1;
};
/**
 * Trimmt den String auf die Anzahl Zeichen und ergänzt es dann mit ".."
 * @param len Anzahl Max-Zeichen
 * @return den beschnittenen String
 */
String.prototype.trim = function (len) {
  var str=this.replace(/^(\s|\u00A0)+|(\s|\u00A0)+$/g, '');
  if (len==null) len=str.length;
  if (str.length>len) 
	return str.substr(0,len-2)+"..";
  else 
	return str;  	
};

/**
 * Recognize links and convert them as html a
 */
String.prototype.htmlize = function() {
  var _text=this.replace(/(http:\/\/\S*)/g, '<a target="_clean" href="$1">$1<\/a>');
  _text=_text.replace(/(https:\/\/\S*)/g, '<a target="_clean" href="$1">$1<\/a>');
  _text=_text.replace(/\n/g,'<br/>');
  return _text;
};

/**
*  Formatiert den String "2008-02-22 10:12:50" um in den Typ Datum, Uhrzeit ist optional
*/
String.prototype.toDateEn = function (withTime) {
  if (this==null) return null;
  
  var datetime=this.split(" ");
  var dates=datetime[0].split("-");
  if (((withTime!=null) && (!withTime)) || (datetime[1]==null))
    return new Date(dates[0], dates[1]-1, dates[2]);
  else {
    var times=datetime[1].split(":");
    return new Date(dates[0], dates[1]-1, dates[2], times[0], times[1]);
  }  
};


/**
*  Formatiert den String "22.02.2008" um in den Typ Datum
*/
String.prototype.toDateDe = function (withTime) {
  if (this==null) return null;
  if (withTime==null) withTime=false;
  var str=this;
  var i=str.indexOf(".");
  var day=0;
  
  if (i>0) 
    day=str.substr(0,i);
  else {
    var d = new Date();
    str=str.toLowerCase();
    // 0 hei§t heute!
    if (str=="0") { 
      return d;
    }
    // Wenn es sich um eine Differenzmeldung geht, also 15t    
    else if (str.indexOf("t")>0) { 
      d.addDays(str.substr(0,str.indexOf("t"))*(-1))
    }
    else if (str.indexOf("w")>0) { 
      d.addDays(str.substr(0,str.indexOf("w"))*(-7))
    }
    else if (str.indexOf("m")>0) { 
      d.setMonth(d.getMonth()-str.substr(0,str.indexOf("m"))*1);
    }
    else
      d=null;
    
    return d;
  }
  str=str.substr(i+1,99);
  
  i=str.indexOf(".");
  if (i>0) 
    month=str.substr(0,i);
  else return null; 
  str=str.substr(i+1,99);
  if (str.indexOf(" ")>0)
    year=str.substr(0,str.indexOf(" "));
  else year=str;
  
  if (!withTime)
    return new Date(year, month-1, day);
  
  i=str.indexOf(" ");
  if (i<=0)
    return new Date(year, month-1, day, 0, 0);
     
  str=str.substring(i+1, 99);
  i=str.indexOf(":");
  
  var hour=str.substr(0,i);
  str=str.substr(i+1,99);

  // Gibt es noch Sekunden?
  i=str.indexOf(":");
  if (i>0)
    minute=str.substr(0,i);
  else
    minute=str.substr(0,99);
  
  return new Date(year, month-1, day, hour, minute);
};


function churchcore_isAllDayDate(start, end) {
  if ((start.getMinutes()==0) && (start.getHours()==0)
      && (end!=null) && (end.getMinutes()==0) && (end.getHours()==0))
    return true;
  return false;
}

String.prototype.isGermanDateFormat = function() {
  return this.search(/([1-9]|0[1-9]|[12][0-9]|3[01])\.([1-9]|0[1-9]|1[012])\.(19|20)\d\d/)!=-1;  
};


/**
* Formatiert Date in den String "22.02.2008"
*/
Date.prototype.toStringDe = function (withTime) {
  if (this==null) return null;
  if (this==0) return "Kein Datum";
  if ((withTime!=null) && (withTime==true)) {
    withTime=" "+this.getHours()+":";
    if (this.getMinutes()<10)
      withTime=withTime+"0"+this.getMinutes();
    else withTime=withTime+this.getMinutes();
  }
  else withTime="";
    
  day=this.getDate();
  if (day<10) day="0"+day;
  month=this.getMonth()+1;
  if (month<10) month="0"+month;
  
  
  return day+"."+month+"."+this.getFullYear()+withTime;
};

/**
* Formatiert Date in den String "22.02.2008"
*/
Date.prototype.toStringDeTime = function () {
  if (this==null) return null;
  if (this==0) return "Kein Datum";
  withTime=this.getHours()+":";
  if (this.getMinutes()<10)
    withTime=withTime+"0"+this.getMinutes();
  else withTime=withTime+this.getMinutes();
  return withTime;
};

function churchcore_getTimeDiff(lastTime) {
  var d=new Date();
  return (d.getTime()-lastTime.getTime());  
}

Date.prototype.toStringEn = function (withTime) {
  if (this==null) return null;	
  d=this;
  if (d==0) return "Kein Datum";
  day=d.getDate();
  if (day<10) day="0"+day;
  month=d.getMonth()+1;
  if (month<10) month="0"+month;
  if (!withTime) 
    return d.getFullYear()+"-"+month+"-"+day;
  else 
  return d.getFullYear()+"-"+month+"-"+day+" "+d.getHours()+":"+d.getMinutes();
};


Date.prototype.addDays = function(days) {
  this.setDate(this.getDate()+days);
}; 

Date.prototype.getKW = function() {
  D0=new Date(this.getFullYear(),0,4); 
  if(D0.getDay()>0)while(D0.getDay()>0)D0.setDate(D0.getDate()+1); 
  D0.setDate(D0.getDate()-7); 
  diff=(this-D0)/6048e5; 
  diff=diff<=0?'53 (0)':Math.ceil(diff); 
  return diff;
};

Date.prototype.getAgeInYears = function() {
  var jetzt=new Date(); 
  jetzt=new Date(jetzt.getFullYear(),jetzt.getMonth(),jetzt.getDate()); 
  var d=new Date(this.getTime()); 
  d.setYear(jetzt.getFullYear()); 
  return d>jetzt?(jetzt.getFullYear()-this.getFullYear()-1):(jetzt.getFullYear()-this.getFullYear()); 
}; 

Date.prototype.getDayInText = function() {
  var ArrayTage = new Array
  ("Sonntag","Montag","Dienstag","Mittwoch","Donnerstag","Freitag","Samstag");
  return ArrayTage[this.getDay()];
};

Date.prototype.sameDay = function(date) {
  if (date==null) return false;
  return ((this.getFullYear()==date.getFullYear()) 
      && (this.getMonth()==date.getMonth()) 
      && (this.getDate()==date.getDate()));
};

Date.prototype.monthDiff = function(d) {
  var months;
  months = (d.getFullYear() - this.getFullYear()) * 12;
  months -= this.getMonth();
  months += d.getMonth();
  return months;
};

Date.prototype.dayDiff = function(d) {
  return (d-this)/(1000*60*60*24);
};


Date.prototype.withoutTime = function() {
  var d=new Date(this);
  d.setHours(0);
  d.setMinutes(0);
  d.setSeconds(0);
  return d;
};


function churchcore_isFullDay(startdate, enddate) {
  if ((startdate.getHours()==0) && (startdate.getMinutes()==0) && (startdate.getSeconds()==0)
      && ((enddate==null) || (enddate.getHours()==0) && (enddate.getMinutes()==0) && (enddate.getSeconds()==0)))
    return true;
 return false;
}

// Gibt true zurŸck, wenn sich die DatŸmer irgendwo Ÿberschneiden
// Wenn startdate=enddate und uhrzeit beides 0:00 gehe ich von ganztags aus
function churchcore_datesInConflict(startdate, enddate, startdate2, enddate2) {
  var _enddate=enddate;
  var _enddate2=enddate2;
  if (churchcore_isFullDay(startdate, enddate)) {
    // Wenn ganztags setze Enddatum auf 23:59:59
    _enddate.addDays(1);
    _enddate=new Date(_enddate.getTime()-1);
  }
  if (churchcore_isFullDay(startdate2, enddate2)) {
    // Wenn ganztags setze Enddatum auf 23:59:59
    _enddate2.addDays(1);
    _enddate2=new Date(_enddate.getTime()-1);
  }  
  // Enddatum2 liegt innerhalb des Datums
  if (((_enddate2>startdate) && (_enddate2<_enddate))
      // oder Startdatum 2liegt innerhalb des Datums
      || ((startdate2>startdate) && (startdate2<_enddate))
      // oder Datum2 komplett au§erhalb 1
      || ((startdate2<=startdate) && (_enddate2>=_enddate))
      // oder Datum2 komplett innerhalb 1
      || ((startdate2>=startdate) && (_enddate2<=_enddate))) {
    if (debug) { 
      console.log("DateConflict! Datum1:"+startdate.toStringDe(true)+"=>"+_enddate.toStringDe(true)+"   -    "+
        "Datum2:"+startdate2.toStringDe(true)+" "+_enddate2.toStringDe(true));
    }
    return true;
  }
  return false;    
}

  
function print_r(arr,level) {
  var dumped_text = "";
  if(!level) level = 0;
  if (level>10) return "";
  
  //The padding given at the beginning of the line.
  var level_padding = "";
  for(var j=0;j<level+1;j++) level_padding += "    ";
  
  if(typeof(arr) == 'object') { //Array/Hashes/Objects 
    for(var item in arr) {
      var value = arr[item];
      
      if(typeof(value) == 'object') { //If it is an array,
        dumped_text += level_padding + "'" + item + "' ...\n";
        dumped_text += print_r(value,level+1);
      } else {
        dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
      }
    }
  } else { //Stings/Chars/Numbers etc.
    dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
  }
  return dumped_text;
}


function cc_copyArray(source) {
  for (i in source) {
    if (typeof source[i] == 'source') {
        this[i] = new cloneObject(source[i]);
    }
    else{
        this[i] = source[i];
    }
  }
}


jQuery.extend(jQuery.ui.dialog.prototype, { 
  'addbutton': function(buttonName, func) { 
          var buttons = this.element.dialog('option', 'buttons'); 
          buttons[buttonName] = func; 
          this.element.dialog('option', 'buttons', buttons); 
  } 
}); 

// Kann Text in der TextArea einfŸgen an der Cursorposition
jQuery.fn.extend({
  insertAtCaret: function(myValue){
  var obj;
  if( typeof this[0].name !='undefined' ) obj = this[0];
  else obj = this;

  if (jQuery.browser.msie) {
    obj.focus();
    sel = document.selection.createRange();
    sel.text = myValue;
    obj.focus();
    }
  else if (jQuery.browser.mozilla || jQuery.browser.webkit) {
    var startPos = obj.selectionStart;
    var endPos = obj.selectionEnd;
    var scrollTop = obj.scrollTop;
    obj.value = obj.value.substring(0, startPos)+myValue+obj.value.substring(endPos,obj.value.length);
    obj.focus();
    obj.selectionStart = startPos + myValue.length;
    obj.selectionEnd = startPos + myValue.length;
    obj.scrollTop = scrollTop;
  } else {
    obj.value += myValue;
    obj.focus();
   }
 }
});

String.prototype.str_replace = function (a,b) {
  if (this==null) return null;
  var i;
  var txt=this;
  i=txt.indexOf(a);
  while (i>0) {
    txt=txt.substr(0,i)+b+txt.substr(i+1,999);
    i=txt.indexOf(a);
  }
  return txt;
};

Number.prototype.maskWithZero = function(length) {
  var s = this+"";
  while (s.length<length) {
    s="0"+s;
  }
  return s;
}

Storage.prototype.setObject = function(key, value) {
  if (typeof(localStorage) == 'object' ) {
    try {
      this.setItem(key, JSON.stringify(value));
    }  
    catch (e) {
      console.log("Fehler bei setObject von "+key+": "+e);
//      if (e == QUOTA_EXCEEDED_ERR) {
//      }
    }
  }
};
 
Storage.prototype.getObject = function(key) {
  try {
  if (typeof(localStorage) == 'object' ) 
    return JSON.parse(this.getItem(key));
  else return null;
  } catch (e) {
    console.log("Fehler beim einladen von "+key+"!");
    return null;
  }
};

function churchcore_openPopupWindow(url) {
  window.open(url,"ctPopUp","toolbar=no, menubar=no, scrollbars=no, resizable=no, location=no, directories=no, status=no,height=550,width=550");
}
function churchcore_openNewWindow(url) {
  window.open(url,"ctNew","");
}


// o enthŠlt alle fŸr DatŸmer notwendige Datums-Objekte
function churchcore_getAllDatesWithRepeats(o) {
  var dates=new Array();
  var diff=null;
  if (o.enddate!=null)
    diff=o.enddate.getTime()-o.startdate.getTime();    


  // repear_until gibt mir vor, wann Schlu§ ist.
  var _repeat_until=new Date(o.repeat_until);
  _repeat_until.addDays(1); // Da der Tag ja mit gelten soll!

  // Max verhindert, dass es durch Fehler zu einer Endlosschleife wird
  var max=999;
  if (o.additions!=null) additions=$.extend({},o.additions);
  else additions=new Object;
  additions[0]=new Object();
  additions[0].add_date=new Date(o.startdate).toStringEn();
  additions[0].with_repeat_yn=1;
  // d wird mein Iterator
  var d=null;
  $.each(additions, function(k,a) {
    d=a.add_date.toDateEn();
    d.setHours(o.startdate.getHours());
    d.setMinutes(o.startdate.getMinutes());

    do {
      var exception=false;
      if ((o.exceptions!=null)) {
        $.each(o.exceptions, function(k,e) {
          // wenn der Tag der gleiche ist, Ausnahme!
          if ((e!=null) && (churchcore_datesInConflict(e.except_date_start.toDateEn(), e.except_date_end.toDateEn(),
                    d, (diff!=null?new Date(d.getTime()+diff):d)))) {
            exception=true;
            return false;
          }
        });
      }
      if (!exception) {
        if (diff!=null)
          dates.push({startdate:new Date(d), enddate:new Date(d.getTime()+diff)});
        else
          dates.push({startdate:new Date(d), enddate:null});
      }
      if ((o.repeat_id==1) || (o.repeat_id==7)) {     
        d.addDays(o.repeat_id*o.repeat_frequence); // Also jede zweite Woche gleich 7*2 => 14 Tage
      }
      // monatlich nach Datum
      else if (o.repeat_id==31) {
        // Plus einen Monat, aber nur, wenn es den Tag auch gibt, z.B. wegen 31.
        var counter=0;
        do {
          var tester=new Date(d);
          tester.setMonth(d.getMonth()+counter+1*o.repeat_frequence);
          if (tester.getDate()==d.getDate()) {
            d.setMonth(d.getMonth()+counter+1*o.repeat_frequence);
            counter=999;
          }
          counter=counter+1;
        } while (counter<999);
      }
      // Monatlich nach Wochentag
      else if (o.repeat_id==32) {
        // letzten Wochentag finden
        if (o.repeat_option_id==6) {
          d.setMonth(d.getMonth()+1+1*o.repeat_frequence,1);
          d.addDays(-1);
          while (d.getDay()!=a.add_date.toDateEn().getDay()) {
            d.addDays(-1);
          }
        }
        // konkreten Wochentag, also z.B. der a.repeat_option_id te Monat
        else {
          var counter=0;
          d.setMonth(d.getMonth()+1*o.repeat_frequence,0);
          while (counter<o.repeat_option_id) {
            var m=d.getMonth();
            d.addDays(1);
            // PrŸfe ob ich den Monat Ÿberspringe, dann hat der Monat nicht genug Tage 
            // und Termin fŠllt flach, v.a. beim 5.Wochentag im Monat)
            if (d.getMonth()!=m) counter=0;
            if (d.getDay()==a.add_date.toDateEn().getDay()) counter=counter+1;
          }
        }
      }
      else if (o.repeat_id==365) {
        d.setFullYear(d.getFullYear()+1*o.repeat_frequence);
      }
      
      max=max-1;
    } while ((d<_repeat_until) && (max>0) && (a.with_repeat_yn==1) && (o.repeat_id>0) && (o.repeat_frequence>0));    
  }); 
  
  if (max==0) alert("Maximale Wiederholung erreicht! ["+o.id+"]");
  return dates;
}  

$(document).ready(function() {
  $("#email_admin").click(function() {
    var form = new CC_Form();
    form.addInput({label:"Betreff", type:"xlarge", cssid:"subject", required:true});
    form.addTextarea({label:"Inhalt", type:"xlarge", cssid:"text", cols:120, rows:6, required:true});
    var elem=form_showDialog("Dem Admin eine E-Mail schreiben", form.render(false, "vertical"), 400,400, {
      "Senden": function() {      
        var obj=form.getAllValsAsObject();
        if (obj!=null) {
          obj.func="sendEmailToAdmin";
          churchInterface.jsendWrite(obj, function(ok, data) {
            if (!ok) alert(data);
            else {
              alert("E-Mail wurde gesendet.");
              elem.dialog("close");          
            }
          }, null, false, "about");
        }
      },
      "Abbruch": function() {
        elem.dialog("close");
      }
    });
    return false;
  });
  $("#simulate_person").click(function() {
    var form = new CC_Form(_("simulate.person"));
    form.addInput({cssid:"simulate_input_person", label:_("name.of.person")});
    form_showCancelDialog(_("simulate.person"), form.render());
    form_autocompletePersonSelect("#simulate_input_person", true, function(a,b) {
      window.location.href="?q=simulate&id="+b.item.value+"&location="+settings.q;
    });
    return false;
  });
  window.setTimeout(function() {
    $("div.hide_automatically").hide('slow');
  }, 5000);
});
