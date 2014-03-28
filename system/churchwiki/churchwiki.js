 
// Constructor
function WikiView() {
  StandardTableView.call(this);
  this.name="WikiView";
  this.allDataLoaded=false;
  this.listViewTableHeight=646;
  this.init=false;
  this.printview=false;
}

Temp.prototype = StandardTableView.prototype;
WikiView.prototype = new Temp();
wikiView = new WikiView();


var edit=false;
var currentPage=null;
var masterData=null;

WikiView.prototype.renderSidebar = function () {
  var rows = new Array();
  
  var text=$("#editor").html();  
  rows.push('<ul id="navlist" class="hidden-phone nav nav-list bs-docs-sidenav affix-top">');
  rows.push('<li><a href="#start">Nach oben</a>');
  reg = new RegExp("<h(1|2)>(.*)</h(1|2)>","gi");  
  var result;
  while ((result = reg.exec(text)) !== null) {
    rows.push('<li><a href="#'+result[2]+'">'+result[2]+'</a>');
  }  
  $("#sidebar").html(rows.join(""));
  if (!churchcore_handyformat()) // Nicht beim Handy, sonst bleibt das immer im Bild stehen!
    $("#navlist").affix({offset: {top: 15}});

};

WikiView.prototype.cancelEditMode = function() {
  if (edit==true) {
    CKEDITOR.instances.editor.destroy();
    $("#editor").removeAttr("contenteditable");  
    editor=false;
  }  
};


// Turn off automatic editor creation first.
WikiView.prototype.editMode = function (setToEdit) {
  var t=this;
  
  if ((edit==false) && (setToEdit)) {
    edit=true;
    $("a.editwiki").html("&Auml;nderungen speichern");
    $("#editor").attr("contenteditable","true");
    $("#editor").html(currentPage.text);
    
    CKEDITOR.inline( "editor", {
      toolbar : [
       //                 { name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates' ] },
                        { name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
                        { name: 'editing', groups: [ 'find', 'spellchecker' ], items: [ 'Find', 'Replace', '-', 'Scayt' ] },
                        { name: 'links', items: [ 'Link', 'Unlink'] },
                        { name: 'insert', items: [ 'Image', /*'Flash', */'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe', 'MediaEmbed' ] },
                        { name: 'tools', items: [ 'Maximize', 'ShowBlocks', 'filebrowser', 'Source' ] },
                        { name: 'others', items: [ '-' ] },
                        '/',
                        { name: 'styles', items: [ 'Format', 'Font', 'FontSize' ] },
                        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', /*'Subscript', 'Superscript', */'-', 'RemoveFormat' ] },
                        { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
                        { name: 'paragraph', groups: [ 'list', 'indent', 'align' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] }
                      ],
      extraPlugins : 'tableresize'
    });
    //CKEDITOR.config.scayt_autoStartup = true;
    CKEDITOR.config.scayt_sLang = 'de_DE';
    $("#editor").focus();
    drafter=new Drafter("wiki", {
      getContent: function() {
        if  (CKEDITOR.instances.editor!=null)
          return CKEDITOR.instances.editor.getData();
        else return null;
      },
      setContent: function(content) { 
        CKEDITOR.instances.editor.setData(content);
      },
      setStatus: function(txt) {churchInterface.setStatus(txt, true);} 
    });
    CKEDITOR.instances.editor.on('change', function() {  churchInterface.clearStatus();  });

  }
  else if ((edit) || (!setToEdit)){
    edit=false;
    $("a.editwiki").html("Text editieren");
    var text=CKEDITOR.instances.editor.getData();
    if (text==currentPage.text) { 
      drafter.clear();
      alert("Keine neue Version, da nichts angepasst wurde.");
      t.renderPage(currentPage);
    }
    else {
      churchInterface.jsendWrite({func:"save", doc_id:currentPage.doc_id, wikicategory_id:currentPage.wikicategory_id, val:CKEDITOR.instances.editor.getData()},
        function(ok) {
          if (ok) {
            currentPage.text=text;
            drafter.clear();
          }
          t.renderPage(currentPage);
        }, null, false);
    }
    CKEDITOR.instances.editor.destroy();
    $("#editor").removeAttr("contenteditable");          
  }
};

WikiView.prototype.loadHistory = function (doc_id, wikicategory_id) {
  var t=this;
  edit=false;
  churchInterface.jsendWrite({func:"loadHistory", doc_id:doc_id, wikicategory_id:wikicategory_id}, function(ok, data) {
    if (ok) {
      currentPage.history=churchcore_sortData(data, 'id', true, false);
      t.renderPage(currentPage);
    }
  });    
};


WikiView.prototype.renderFiles = function() {
  var t=this;
  if (masterData.auth.edit[currentPage.wikicategory_id]) {
    t.renderFilelist("", currentPage.file, null, function(file_id) {
      delete currentPage.file[0].files[file_id];
      t.renderFiles();
    },50);
  }
  else {
    t.renderFilelist("", currentPage.file, null, null, 50);
  }

  $("div.filelist span.tooltip-file").each(function() {
    var tooltip=$(this);
    tooltip.tooltips({
      data:{id:tooltip.attr("data-id") 
           },
      render:function(data) {
        return t.renderTooltipForFiles(tooltip, currentPage.file[0].files[data.id], masterData.auth.edit[currentPage.wikicategory_id]);  
      },      
      afterRender: function(element, data) {
        return t.tooltipCallbackForFiles(data.id, element, currentPage.file, 0);
      }
    });    
  });    
  
  t.addTableContentCallbacks("#cdb_content");
};


WikiView.prototype.renderPage = function (content) {
  var t = this;
  var rows = new Array();
  if (currentPage.doc_id!="main")
    rows.push('<a class="btn wiki-link pull-right" data-doc-id="main" href="#">Hauptseite</a>');
  rows.push('<anchor id="start"><h1>'+masterData.wikicategory[currentPage.wikicategory_id].bezeichnung+' - '+(currentPage.doc_id=="main"?"Hauptseite":currentPage.doc_id)+'</h1></section><br/>');

  rows.push('<div class="well editable" id="editor">');
  if (content.text==null) content.text="";
    var text=content.text.replace(/<h(1|2)>(.*)<\/h(1|2)>/gi, '<anchor id="$2"/><h$1>$2</h$1>');  
    text=text.replace(/\[\[([ ()a-zA-Z0-9&;-]*)\]\]/gi, '<a href="#" class="wiki-link" data-doc-id="$1">$1</a>');
    text=text.replace(/\img:(.*)/gi, '<a href="#" data-doc-id="$1">$1</a>');
    //$text = preg_replace('/img:([\w\.-]+)/', '<img src="'.$files_dir.'/files/help/$1"/>', $text);   
    rows.push(text);  
  rows.push('</div>');
  
  
  if (!t.printview) {
    if (((masterData.auth.edit[currentPage.wikicategory_id]) || (currentPage.file!=null))) {
      rows.push('<div class="well">');
        rows.push('<div id="filelist" class="filelist" data-id="0"></div>');
        if (masterData.auth.edit[currentPage.wikicategory_id]) 
          rows.push('<p><div id="upload_button">Nochmal bitte...</div>');
      rows.push('</div>');
    }
  }
  
  rows.push('<form class="form-inline">');

  
  if (!t.printview) {
    rows.push('<a class="btn wiki-link" data-doc-id="main" href="#">Hauptseite</a>&nbsp;');
    if (masterData.auth.edit[currentPage.wikicategory_id]) {
      rows.push('<a class="btn editwiki" href="#">Text editieren</a> &nbsp; &nbsp; ');
      if (currentPage.modified_date!=null)
        rows.push(form_renderCheckbox({controlgroup:false, cssid:"showonstartpage", checked:currentPage.auf_startseite_yn==1, label:"Auf Startseite anzeigen"}));
    }
  }

  rows.push("<p class='pull-right' style='text-align:right'><small>");
  if (currentPage.modified_date!=null)
    if (currentPage.history==null)
      if (settings.user.id!=-1)
        rows.push('<a href="#" class="viewversion">Version '+currentPage.version_no+' vom '+currentPage.modified_date.toDateEn(true).toStringDe(true)+
           " - "+currentPage.vorname+" "+currentPage.name+"</a>");
      else  
        rows.push('Letzte &Auml;nderung vom '+currentPage.modified_date.toDateEn(true).toStringDe(true));
    else {
      rows.push(form_renderSelect({data:currentPage.history, sort:false, selected:currentPage.version_no, controlgroup:false, cssid:"selectHistory"}));
    }
  else
    rows.push("Neu");

  if (settings.user.id!=-1) {
    if ((masterData.encrypted==null) || (masterData.encrypted==false))
      rows.push('<br><a href="http://intern.churchtools.de/?q=help&doc=Verschluesselung" target="_clean">unverschl&uuml;sselt</a>');
    else
      rows.push("<br>verschl&uuml;sselt");
  }
  if (!t.printview)
    rows.push(' - <a href="?q=churchwiki/printview#WikiView/filterWikicategory_id:'+currentPage.wikicategory_id+'/doc:'+currentPage.doc_id+'" target="_clean">Druckansicht</a>');
  rows.push("</small>");
  
  rows.push('</form>');

  $("#cdb_content").html(rows.join(""));
  
  t.renderFiles();

  if ((masterData.auth.edit[currentPage.wikicategory_id]) && (!t.printview)) {
     uploader = new qq.FileUploader({
      element: document.getElementById('upload_button'),
      action: "?q=churchwiki/uploadfile",
      params: {
        domain_type:"wiki_"+currentPage.wikicategory_id,
        domain_id:currentPage.doc_id
      },
      multiple:true,
      debug:true,
      onSubmit: function() {
      },
      onComplete: function(ok, filename, res) {
        if (res.success) {
          if (currentPage.file==null)
            currentPage.file=new Array();
          if (currentPage.file[0]==null)
            currentPage.file[0]=new Object();
          var files=currentPage.file[0].files;
          if (files==null) files=new Array();
          files[res.id]={filename:res.filename, bezeichnung:res.bezeichnung, id:res.id, domain_type:"wiki_"+currentPage.wikicategory_id, domain_id:currentPage.doc_id};;
          currentPage.file[0].files=files;
          t.renderFiles();
        }
        else {
          alert("Sorry, Fehler beim Hochladen aufgetreten! Datei zu gross oder Dateiname schon vergeben?")
        }
      }
    });
  }
  
  
  
  $.extend($.tablesorter.themes.bootstrap, {
    // these classes are added to the table. To see other table classes available,
    // look here: http://twitter.github.com/bootstrap/base-css.html#tables
    table      : 'table table-bordered',
    header     : 'bootstrap-header', // give the header a gradient background
    footerRow  : '',
    footerCells: '',
    icons      : '', // add "icon-white" to make them white; this icon class is added to the <i> in the header
    sortNone   : 'bootstrap-icon-unsorted',
    sortAsc    : 'icon-chevron-up',
    sortDesc   : 'icon-chevron-down',
    active     : '', // applied when column is sorted
    hover      : '', // use custom css here - bootstrap class may not override it
    filterRow  : '', // filter row class
    even       : '', // odd row zebra striping
    odd        : ''  // even row zebra striping
  });
  $("#cdb_content table:not(.table-nosort)").tablesorter({
      theme : "bootstrap",
      widthFixed: true,
      headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!
      // widget code contained in the jquery.tablesorter.widgets.js file
      // use the zebra stripe widget if you plan on hiding any rows (filter widget)
      widgets : [ "uitheme", "zebra"],
      widgetOptions : {
        // using the default zebra striping class name, so it actually isn't included in the theme variable above
        // this is ONLY needed for bootstrap theming if you are using the filter widget, because rows are hidden
        zebra : ["even", "odd"],
        // reset filters button
        filter_reset : ".reset"
        // set the uitheme widget to use the bootstrap theme class names
        // this is no longer required, if theme is set
        // ,uitheme : "bootstrap"
      }
  });
  $("html, body").animate({ scrollTop: 0 }, 500);
  
  $("a.wiki-link").click(function(k) {
    var new_doc_id=$(this).attr("data-doc-id");
    if (new_doc_id=="Hauptseite") new_doc_id="main";
    t.filter["doc"]=new_doc_id;
    t.historyCreateStep();
    return false;
  });
  $("a.editwiki").click(function(k) {
    t.editMode(true);
    return false;
  });
  $("a.viewversion").click(function(k) {
    t.cancelEditMode();
    t.loadHistory(currentPage.doc_id, currentPage.wikicategory_id);
    return false;
  });
  $("#selectHistory").change(function(k) {
    t.cancelEditMode();
    t.loadDoc(currentPage.doc_id, currentPage.wikicategory_id, $(this).val());
    return false;
  });
  $("#showonstartpage").change(function(k) {
    if ($(this).attr("checked")=="checked")
      currentPage.auf_startseite_yn=1;
    else
      currentPage.auf_startseite_yn=0;
    churchInterface.jsendWrite({func:"showonstartpage", doc_id:currentPage.doc_id, version_no:currentPage.version_no, 
             wikicategory_id:currentPage.wikicategory_id, auf_startseite_yn:currentPage.auf_startseite_yn},
             null, true, false);
    
    return false;
  });

  
  t.renderNavi();    
  t.renderSidebar();
  
  if (currentPage.text=="Neue Seite") t.editMode(true);
};

WikiView.prototype.renderNavi = function () {
  var t=this;

  if ((masterData.auth.view!=false) || (masterData.auth.admin)) {

    var navi = new CC_Navi();
    //navi.addEntry(currentPage.wikicategory_id==0,"alistview0","Standard");
    
    var dabei=false;
    $.each(churchcore_sortMasterData(masterData.wikicategory), function(k,a) {
      if (masterData.auth.view[a.id] && a.in_menu_yn==1) {
        if (currentPage.wikicategory_id==a.id) dabei=true;
        navi.addEntry(currentPage.wikicategory_id==a.id,"alistview"+a.id,masterData.wikicategory[a.id].bezeichnung);
      }
    });
    if (!dabei && masterData.wikicategory[currentPage.wikicategory_id]!=null) 
      navi.addEntry(true,"alistview"+currentPage.wikicategory_id,
          masterData.wikicategory[currentPage.wikicategory_id].bezeichnung);
    
    if (masterData.auth.admin)
      navi.addEntry(false, "editCategory", "Kategorien anpassen");
    if (navi.countElement()>1)
      navi.renderDiv("cdb_navi", churchcore_handyformat());
    
    //this.implantStandardFilterCallbacks(this, "cdb_search");         
    
    $("#cdb_navi a").click(function () {
/*      if (CKEDITOR.instances.editor!=null)
        CKEDITOR.instances.editor.destroy();*/      
      var id=$(this).attr("id");
      $.each(masterData.auth.view, function(k,a) {
        if (id=="alistview"+a) {
          t.filter["filterWikicategory_id"]=a;
          t.filter["doc"]="main";
          t.historyCreateStep();
        }
      });
      if (id=="editCategory")
        churchInterface.setCurrentView(maintainView); 
      return false;
    });
  }
};

WikiView.prototype.loadDoc = function(doc_id, wikicategory_id, version_no) {
  var t=this;
  edit=false;
  churchInterface.jsendWrite({func:"load", doc_id:doc_id, wikicategory_id:wikicategory_id, version_no:version_no}, function(ok, data) {
    if (ok) {
      if (data==null || !data) {
        currentPage = new Object();
        currentPage.text="Neue Seite";
        currentPage.wikicategory_id=wikicategory_id;
        currentPage.doc_id=doc_id;
      }
      else if ((currentPage==null) || (currentPage.doc_id!=doc_id) || (currentPage.wikicategory_id!=wikicategory_id)) {
        currentPage=data;
        if ((data.files!=null) && (!churchcore_isObjectEmpty(data.files)) && (!t.printview)){
          currentPage.file=new Array();
          var o= new Object();
          o.files=data.files[currentPage.doc_id];
          currentPage.file.push(o);
        }
      }
      else {
        currentPage.text=data.text;
        currentPage.version_no=data.version_no;
      }
      t.renderPage(currentPage);
    }
  });  
};

function churchwiki_loadMasterData() {
  churchInterface.jsendWrite({func:"getMasterData"}, function(ok, data) {
    if (ok) {
      masterData=data;
    }
  },false);    
}

function cdb_loadMasterData(nextFunction) {
  churchwiki_loadMasterData(); 
  if (nextFunction!=null) nextFunction();
}

WikiView.prototype.renderList = function() {
};

WikiView.prototype.renderView = function() {
  var t=this;
  if (t.init==false) {
    t.init=true;
    if (t.initView()) return
  } 
  $("#cdb_menu").html("");
  if ((t.filter["doc"]!=null) && (t.filter["filterWikicategory_id"]!=null)) {
    if (edit) t.cancelEditMode();
    t.loadDoc(t.filter["doc"], t.filter["filterWikicategory_id"]);
    shortcut.add("Ctrl+s", function() {
      if (edit) {
        t.editMode(false);
        return false;
      }
    });
    shortcut.add("Ctrl+e", function() {
      if (!edit) {
        t.editMode(true);       
        return false;
      }
    });
    shortcut.add("esc", function() {
      if (edit) {
        if ((CKEDITOR.instances.editor.getData()==currentPage.text) || 
            (confirm("Wirklich die Anpassungen verwerfen?"))) {
          t.renderPage(currentPage);
          CKEDITOR.instances.editor.destroy();
          $("#editor").removeAttr("contenteditable");          
          edit=false;
        }
      }
      return false;
    });
  }
};

WikiView.prototype.initView = function() {
  var t=this;
  if ($("#printview").val()=="true")
    t.printview=true;
  if (t.filter["filterWikicategory_id"]==null) {
    if (masterData.auth.view!=null)  {
      $.each(churchcore_sortMasterData(masterData.wikicategory), function(k,a) {
        if (masterData.auth.view[a.id] && t.filter["filterWikicategory_id"]==null)
          t.filter["filterWikicategory_id"]=a.id;
      });
    }
    if (t.filter["filterWikicategory_id"]==0)
      t.filter["filterWikicategory_id"]=0;
  }

  var refresh=false;
  if ($("#doc_id").val()!=null) {
    t.filter["doc"]=$("#doc_id").val();
    refresh=true;
  }
  else if (t.filter["doc"]==null) {
    t.filter["doc"]="main";
    refresh=true;
  }

  if (refresh) {
    t.historyCreateStep();
    return true;
  }
  return false;
};



$(document).ready(function() {
  churchInterface.setModulename("churchwiki");
  churchInterface.registerView("WikiView", wikiView);
  churchInterface.registerView("MaintainView", maintainView);
  churchwiki_loadMasterData();
  churchInterface.activateHistory("WikiView");
});
