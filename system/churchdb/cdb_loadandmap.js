
function cdb_mapJsonDetails(json, person) {
  // Person kann u.U. nicht existieren, z.B. wenn eine neue von anderem User angelegt wurde
  if (person==null) person=new Object();
  each(json, function(k,a) {
    person[k]=a;
  });
  person.details=true;
  person.searchable=true;
  return person;
}

function cdb_mapJsonSearchable(json, person) {
  if (person!=null) {
    each(json, function(k,a) {
      person[k]=a;
    });
    person.searchable=true;
  };
  return person;
}

function cdb_addJsonRels(json, person) {
  if (person!=null) {
    if (person.rels==null)
      person.rels=new Object();
    obj=new Object();
    obj.id=json.id;
    obj.vater_id=json.v_id;
    obj.kind_id=json.k_id;
    obj.beziehungstyp_id=json.typ_id;
    person.rels[json.id]=obj;
  };
}

function cdb_mapJsonPerson2(json, person) {
  if (person!=null) {
    person.gruppe=json.g;
  };
  return person;
}

function cdb_mapJsonPerson1(json, person) {
  if (person==null) {
    person=new Object();
  }
  person.id=json.p_id;
  person.name=json.name;
  person.vorname=json.vorname;
  person.spitzname=json.spitzname;
  person.station_id=json.stn_id;
  person.status_id=json.sts_id;
  person.access=json.access;
  person.tel=json.tl;
  person.email=json.em;
  person.geolat=json.lat;
  person.geolng=json.lng;
  person.archiv_yn=json.archiv_yn;
  person.last_send=json.last_send;
  person.gruppe=json.groups;
  person.districts=json.districts;
  person.gruppentypen=json.gruppentypen;
  person.geburtsdatum=json.geb;
  return person;
}

function cdb_loadRelations(nextFunction) {
  var renderListNecessary=false;
  churchInterface.setStatus("Lade Relationen...");
  // Erst alle alten Relationen rausnehmen
  each(allPersons, function(k,a) {
    a.rels=null;
  });
  churchInterface.jsendRead({func:"getAllRels"}, function(ok, json) {
    if (json.tags!=null) {
      each(json.tags, function(k,a) {
        if (allPersons[a.id]!=null) {
          if (allPersons[a.id].tags==null)
            allPersons[a.id].tags=new Array();
          allPersons[a.id].tags.push(a.tag_id);
        }
      });
    }
    if (json.rels!=null) {
    	each(json.rels, function(k,a) {
        cdb_addJsonRels(a, allPersons[a.k_id]);
        cdb_addJsonRels(a, allPersons[a.v_id]);
      });
    }
    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction(true);
  });
}

function cdb_loadSearch(nextFunction) {
  churchInterface.setStatus("Lade Suchdaten...");
  churchInterface.jsendRead({func:"getSearchableData"}, function(ok, json) {
    each(json.searchable, function(k,a) {
      if (allPersons[a.id]!=null)
        allPersons[a.id]=cdb_mapJsonSearchable(a, allPersons[a.id]);
    });
    if (json.oldGroupRelations!=null)
    each(json.oldGroupRelations, function(k, groups) {
      if (allPersons[k]!=null) {
        if (allPersons[k].oldGroups==null) allPersons[k].oldGroups=new Array();
        each(groups, function(i, group) {
          group.id=k;
          allPersons[k].oldGroups.push(group);
        })
      }
    });

    churchInterface.clearStatus();
    if (nextFunction!=null) nextFunction();
  });
}


function cdb_loadPersonData(nextFunction, limit, p_id) {
  // Erst mal ein Timeout setzen, damit die History wirklich auch korrekt gesetzt wird und currentView gesetzt ist.
  window.setTimeout(function() {
    churchInterface.setStatus("Lade Personendaten...");
    timers["startAllPersons"]=new Date();
    churchInterface.jsendRead({func:"getAllPersonData", limit:limit, p_id:p_id}, function(ok, json) {
      timers["endAllPersons"]=new Date();
      if (json!=null) {
        each(json, function(k,a) {
          allPersons[a.p_id]=cdb_mapJsonPerson1(a, allPersons[a.p_id]);
        });
      }
      churchInterface.clearStatus();
      if (nextFunction!=null) nextFunction();
    });
  },1);
}

/**
 * @param _id entweder null fuer alle oder die p_id
 * @param func: Function (refreshListNecessary)
 */
function cdb_loadGroupMeetingStats(filter, _id, func) {
  if (_id==null) _id=-1;
  if ((masterData.auth.viewgroupstats) || (filter['filterOwnGroups']!=null)) {

    // Schraenke den Download nur ein auf die ausgewaehlte Gruppe, wenn er keine viewgroupstats-Rechte hat
    if (!masterData.auth.viewgroupstats)
      _id=filter['filterOwnGroups'];

    churchInterface.jsendRead({func:"GroupMeeting", sub:"stats", id: _id }, function(ok, json) {
      groupMeetingStats = new Object();
      each(json, function(k,a) {
        if (groupMeetingStats[a.id] == null) groupMeetingStats[a.id] = new Object();
        if (groupMeetingStats[a.id][a.g_id] == null) groupMeetingStats[a.id][a.g_id] = new Object();
        groupMeetingStats[a.id][a.g_id] = {
          ausgefallen: a.ausgefallen,
          stattgefunden: a.stattgefunden,
          dabei: a.dabei,
          datum: a.datum
        };
      })
      if (func!=null) func(true);
    });
  }
  else if (func!=null) func(false);
}
