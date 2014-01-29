 
 
// Constructor
function CDBStandardTableView() {
  StandardTableView.call(this);
  this.name="CDBStandardTableView";
}

Temp.prototype = StandardTableView.prototype;
CDBStandardTableView.prototype = new Temp();
cdbStandardTableView = new CDBStandardTableView();

(function($) {
  

/**
 * 
 * @param searchEntry
 * @param tags
 * @return Gibt false, wenn Tag gesucht wird und nicht vorhanden ist, ansonsten true
 */
CDBStandardTableView.prototype.checkFilterTag = function(searchEntry, tags) {
  function _hasTag(tag_id, tags) {
    if (tags==null) return false;
    return $.inArray(tag_id,tags)>-1;
  }
  if (searchEntry.indexOf("TAG:")==0) {
    var tag=searchEntry.substr(4,99);
    var dabei=false;
    $.each(tag.split(","), function(i,b) {
      if (b>0) {
        if (_hasTag(b, tags)) dabei=true;
      }
      else {
        var in_tag=-1;
        $.each(masterData.tags, function(j,c) {
          if (c.bezeichnung.toUpperCase()==b)
            in_tag=c.id;
        });
        if ((in_tag>=0) && (_hasTag(in_tag, tags))) dabei=true;
      }
    });
    if (!dabei) return false;
  }
  return true;
};


CDBStandardTableView.prototype.addTagCallbacks = function(id, func) {
  if (masterData.tags!=null) {
    var availableTags = new Array;
    
    $.each(churchcore_sortData(masterData.tags,"bezeichnung"), function(k,b) {
      availableTags.push(b.bezeichnung);
    });
    
    $("#input_tag"+id).addClass("form-autocomplete");
    $("#input_tag"+id).autocomplete({
      source: availableTags
    });
  }  
  
  $("#input_tag"+id).keyup(function(c){
    var val=$(this).val();
    if (c.which==27) {
      $("#add_tag_field"+id).toggle();
    } 
    else if (c.which==13) {
      $("#add_tag_field"+id).toggle();
      var tag_id=null;
      if (masterData.tags!=null)
        $.each(masterData.tags, function(k,b) {
          if (b.bezeichnung.toUpperCase()==val.toUpperCase()) {
            tag_id=b.id;
            return;
          }
        });
      if (tag_id==null) {
        churchInterface.jsendWrite({func:"addNewTag", bezeichnung:val},
          function(ok, b){
            var o = new Object();
            o.id=b;
            o.bezeichnung=val;
            if (masterData.tags==null)
              masterData.tags=new Object();
            masterData.tags[b]=o;
            tag_id=b;
            func(tag_id);
          });
      }
      else func(tag_id);
    }
  });
};

CDBStandardTableView.prototype.renderTag = function(a, deletable) {
  if (deletable==null) deletable=false;
  var _text='<span class="tag"><a href="#" title="'+a+'" id="search_tag'+a+'">';
  if (masterData.tags[a]!=null)
    _text=_text+masterData.tags[a].bezeichnung;
  else
    _text=_text+"null["+a+"]";
  
  _text=_text+'</a>';
  if (deletable) 
    _text=_text+'&nbsp;<a href="#" id="del_tag'+a+'"><img width=16px src="'+masterData.modulespath+'/images/trashbox.png" align="absmiddle"/></a>';
  _text=_text+'</span>';
  return _text;
};

CDBStandardTableView.prototype.renderTags = function(tags, authEdit, id) {
  var rows=new Array();
  var this_object=this;
  rows.push('<div class="ui-widget" style="text-align:right;position:relative;"><p>');
  if (tags!=null) {
    $.each(tags, function(k,a) {
      rows.push(this_object.renderTag(a, authEdit)+"&nbsp; ");          
    }); 
  }
  if (authEdit) { 
    rows.push('&nbsp;<a href="" title="Tag hinzuf&uuml;gen" id="add_tag"><img id="add_tag_icon" width=16px src="'+masterData.modulespath+'/images/plus.png" align="absmiddle"/></a>');      
    rows.push('<span id="add_tag_field'+id+'" style="display:none"><input type="text" id="input_tag'+id+'"></input></span>');
  }
  rows.push('</div>');
  return rows.join("");
};



})(jQuery);
