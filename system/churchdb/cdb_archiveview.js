	 
 
// Constructor
function ArchiveView() {
  PersonView.call(this);
  this.name="ArchiveView";
  
}

Temp.prototype = PersonView.prototype;
ArchiveView.prototype = new Temp();
archiveView = new ArchiveView();

function cdb_loadPersonArchiveData(func) {
  churchInterface.setStatus("Lade Personendaten...");
  churchInterface.jsonRead({func:"getAllPersonArchiveData"}, function(json) {
    if (json.persons!=null) {
      jQuery.each(json.persons, function(k,a) {
        allPersons[a.p_id]=cdb_mapJsonPerson1(a, allPersons[a.p_id]);
      });
    }  
    churchInterface.clearStatus();
    func();
  });
}

ArchiveView.prototype.getListHeader = function() {
  var t=this;
  if (t.dataloaded==null) {
    var elem = this.showDialog("Lade Archiv", "Lade Archiv...", 300,300);
    cdb_loadPersonArchiveData(function() {
      elem.dialog("close");
      t.dataloaded=true;
      t.renderList();
    });
    return null;
  }
  else {
    return personView.getListHeader();
  } 
};
