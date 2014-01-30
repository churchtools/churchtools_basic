
// Google Maps integration
var map = null;
var geocoder = null;

function _cdb_setGeoPoint(map, info, point) {
  if (map!=null)
    var marker = new google.maps.Marker({
      position: point, 
      map: map, 
      title:info+" "+point.lat()+" "+point.lng()
    });   
}

function _cdb_addPersonToMap(map,near_lat,near_lng) {
  var image = new google.maps.MarkerImage("https://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png",
      new google.maps.Size(32, 32),
      new google.maps.Point(0, 0),
      new google.maps.Point(6, 12),
      new google.maps.Size(12, 12)
      );

  jQuery.each(allPersons, function(k, a){
    if ((a.geolat!="")) {
      if ((near_lat==null) || 
           (((Math.abs(parseFloat(near_lng)-parseFloat(a.geolng))<0.06)) &&
           (Math.abs(parseFloat(near_lat)-parseFloat(a.geolat))<0.03))) {
        var myLatLng = new google.maps.LatLng(a.geolat, a.geolng);
        var beachMarker = new google.maps.Marker({
            position: myLatLng,
            map: map,
            icon: image,
            title: "Person: "+a.vorname+" "+a.name+" ["+a.id+"]"
        });
        google.maps.event.addDomListener(beachMarker, 'click', function() {
          churchInterface.setCurrentView(personView);
          personView.clearFilter();
          personView.setFilter("searchEntry",a.id);
          personView.renderView();
        });

      }
    }
  });
}

/**
 * The HomeControl adds a control to the map that simply
 * returns the user to Chicago. This constructor takes
 * the control DIV as an argument.
 */

function _cdb_addGroupButton2Maps(controlDiv, map) {

  // Set CSS styles for the DIV containing the control
  // Setting padding to 5 px will offset the control
  // from the edge of the map
  controlDiv.style.padding = '4px';

  // Set CSS for the control border
  var controlUI = document.createElement('DIV');
  controlUI.className = 'map-control-wrapper';
  controlUI.title = 'Klicke, um hier Gruppen anzuzeigen';
  controlDiv.appendChild(controlUI);

  // Set CSS for the control interior
  var controlText = document.createElement('DIV');
  controlText.className = 'map-control-label';
  controlText.innerHTML = 'Gruppen';
  controlUI.appendChild(controlText);

  // Setup the click event listeners: simply set the map to
  // Chicago
  google.maps.event.addDomListener(controlUI, 'click', function() {
    if (map.getZoom()<11)
      alert("Bitte erst weiter reinzoomen!");
    else 
      cdb_addGroupsToMap(map, map.getCenter().lat(), map.getCenter().lng());  
  });
}
/**
 * The HomeControl adds a control to the map that simply
 * returns the user to Chicago. This constructor takes
 * the control DIV as an argument.
 */

function _cdb_addPersonButton2Maps(controlDiv, map) {

  // Set CSS styles for the DIV containing the control
  // Setting padding to 5 px will offset the control
  // from the edge of the map
  controlDiv.style.padding = '4px';

  // Set CSS for the control border
  var controlUI = document.createElement('DIV');
  controlUI.className = 'map-control-wrapper';
  controlUI.title = 'Klicke, um Personen in der N&auml;he anzuzeigen';
  controlDiv.appendChild(controlUI);

  // Set CSS for the control interior
  var controlText = document.createElement('DIV');
  controlText.className = 'map-control-label';
  controlText.innerHTML = 'Personen';
  controlUI.appendChild(controlText);

  // Setup the click event listeners: simply set the map to
  // Chicago
  google.maps.event.addDomListener(controlUI, 'click', function() {
    if (map.getZoom()<12)
      alert("Bitte erst weiter reinzoomen!");
    else 
    _cdb_addPersonToMap(map, map.getCenter().lat(), map.getCenter().lng());  
  });
}

function _cdb_limitZoom(map) {
  google.maps.event.addListener(map, 'zoom_changed', function() {
    if (map.getZoom()>12) {
      alert("Ein weiterer Zoom ist leider nicht erlaubt!");
      map.setZoom(12);
    }
  });
}  

function cdb_prepareMap(id,latlng) {
  var myOptions = {
    zoom: 11,
    center: latlng,
    mapTypeId: google.maps.MapTypeId.ROADMAP
  };
  var docId=document.getElementById(id);
  // Karte wird momentan nicht im Browser angezeigt, also brauche sie auch nicht zu f�llen
  if (docId==null) 
    return null;

  var map =  new google.maps.Map(docId, myOptions);
  //Create the DIV to hold the control and
  // call the HomeControl() constructor passing
  // in this DIV.
  var groupControlDiv = document.createElement('DIV');
  var groupControl = new _cdb_addGroupButton2Maps(groupControlDiv, map);
  groupControlDiv.index = 1;
  map.controls[google.maps.ControlPosition.TOP_RIGHT].push(groupControlDiv);
  
  if (masterData.auth.viewalldata) {
    var personControlDiv = document.createElement('DIV');
    var personControl = new _cdb_addPersonButton2Maps(personControlDiv, map);
    personControlDiv.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(personControlDiv);
  }  

  if (masterData.settings.googleMapLayer==1) {
    // Traffic Layer:  
    var trafficLayer = new google.maps.TrafficLayer();
    trafficLayer.setMap(map);
  } 
  else if (masterData.settings.googleMapLayer==2) {
    // Bike Layer
    var bikeLayer = new google.maps.BicyclingLayer();
    bikeLayer.setMap(map);
  }
  else if (masterData.settings.googleMapLayer==3) {  
    //Transit Layer
    var transitOptions = {
        getTileUrl: function(coord, zoom) {
          return "http://mt1.google.com/vt/lyrs=m@155076273,transit:comp|vm:&" +
          "hl=en&opts=r&s=Galil&z=" + zoom + "&x=" + coord.x + "&y=" + coord.y;
        },
        tileSize: new google.maps.Size(256, 256),
        isPng: true
      };
    var transitMapType = new google.maps.ImageMapType(transitOptions);
    map.overlayMapTypes.insertAt(0, transitMapType);
  }  
  
  return map;
}

/**
 * 
 * @param {Object} address - Adresse in Textform
 * @param {Object} id - person_id
 */
function cdb_showGeoPerson(address, id, limit) {
  if (geocoder) {
    // Wenn Geo-Daten schon vorliegen
    if (allPersons[id].geolat!="") {
      var latlng = new google.maps.LatLng(allPersons[id].geolat, allPersons[id].geolng);
      map=cdb_prepareMap("map_canvas"+id,latlng);
      _cdb_setGeoPoint(map,address,latlng);
      if (limit==true)
        _cdb_limitZoom(map);
    } 
    // Geo-Daten müssen erst geholt werden
    else {
      geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
          map=cdb_prepareMap("map_canvas"+id,results[0].geometry.location);
          _cdb_setGeoPoint(map,address, results[0].geometry.location);
          // Speichern in das Array im Browser und in die Datenbank per AJAX
          var obj=new Object();
          obj["id"]=id;
          obj["func"] = "f_geocode_person";  
          obj["lat"]=results[0].geometry.location.lat();
          obj["lng"]=results[0].geometry.location.lng();
          allPersons[id].geolat=results[0].geometry.location.lat();
          allPersons[id].geolng=results[0].geometry.location.lng();
          churchInterface.jsendWrite(obj, function(ok, json) {
            if (!ok) alert("Fehler beim Speichern der Geocode-Daten: "+json);
          });

        } else {
          jQuery("#map_canvas"+id).html("Geocoding war nicht erfolgreich:" + status);
        }
      });
    }  
  }
}  

/**
 * 
 * @param {Object} address - Adresse in Textform
 * @param {Object} id - group_id
 */
function cdb_showGeoGruppe(address, id) {
  if (geocoder) {
    // Wenn Geo-Daten schon vorliegen
    if (masterData.groups[id].geolat!="") {
      var latlng = new google.maps.LatLng(masterData.groups[id].geolat, masterData.groups[id].geolng);
      map=cdb_prepareMap("map_canvasg"+id,latlng);
      _cdb_setGeoPoint(map,address,latlng);
    } 
    // Geo-Daten müssen erst geholt werden
    else {
      geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
          map=cdb_prepareMap("map_canvasg"+id,results[0].geometry.location);
          _cdb_setGeoPoint(map,address, results[0].geometry.location);
          // Speichern in das Array im Browser und in die Datenbank per AJAX
          var obj=new Object();
          obj["id"]=id;
          obj["func"] = "f_geocode_gruppe";  
          obj["lat"]=results[0].geometry.location.lat();
          obj["lng"]=results[0].geometry.location.lng();
          masterData.groups[id].geolat=results[0].geometry.location.lat();
          masterData.groups[id].geolng=results[0].geometry.location.lng();
          churchInterface.jsendWrite(obj, function(ok, json) {
            if (!ok) alert("Fehler beim Speichern der Geocode-Daten: "+json);
          });
  
        } else {
          jQuery("#map_canvas"+id).html("Geocoding war nicht erfolgreich:" + status);
        }
      });
    }  
  }  
} 

function cdb_addGroupsToMap(map,near_lat,near_lng, func) {

  var shadow = new google.maps.MarkerImage(masterData.modulespath+"/images/gruppe_shadow.png",
      new google.maps.Size(50, 50),
      new google.maps.Point(0, 0),
      new google.maps.Point(10, 25),
      new google.maps.Size(25, 25)
      );
  
  jQuery.each(masterData.groups, function(k, a){
    if ((a.geolat!="") && (a.valid_yn==1) && (a.versteckt_yn==0)) {
      if ((near_lat==null) || 
           (((Math.abs(parseFloat(near_lng)-parseFloat(a.geolng))<0.2)) &&
           (Math.abs(parseFloat(near_lat)-parseFloat(a.geolat))<0.1))) {

        url = "gruppe_standard.png";
        if ((a.distrikt_id!=null) && (masterData.districts[a.distrikt_id].imageurl!=null))
          url = masterData.districts[a.distrikt_id].imageurl;
        var image = new google.maps.MarkerImage(masterData.modulespath+"/images/"+url,
            new google.maps.Size(40, 50),
            new google.maps.Point(0, 0),
            new google.maps.Point(10, 25),
            new google.maps.Size(20, 25)
            );
        
        
        var myLatLng = new google.maps.LatLng(a.geolat, a.geolng);
        
        var title="Gruppe: "+a.bezeichnung+" ["+a.id+"]";
        if (a.distrikt_id!=null)
          title=title+"\n Distrikt: "+masterData.districts[a.distrikt_id].bezeichnung;
        if (a.treffzeit!="") title=title+"\n "+a.treffzeit;
        if ((a.treffpunkt!=null) && (a.treffpunkt!="")) title=title+"\n Ort: "+a.treffpunkt;
        if (a.treffname!="") title=title+"\n bei: "+a.treffname;
        if (a.zielgruppe!="") title=title+"\n Zielgruppe: "+a.zielgruppe;        
        
        var beachMarker = new google.maps.Marker({
            position: myLatLng,
            map: map,
            icon: image,
            shadow: shadow,
            title: title
        });
        
        google.maps.event.addDomListener(beachMarker, 'click', function() {
          if (func!=null) func(a.id);
          else if (typeof(groupView)!='undefined'){
            churchInterface.setCurrentView(groupView);
            groupView.clearFilter();
            groupView.setFilter("searchEntry",a.id);
            groupView.renderView();
          }
        });
        
      }
    }
  });
}

function cdb_initializeGoogleMaps() {
  churchInterface.setStatus("Initialisiere GoogleMap...");
  try {
     geocoder = new google.maps.Geocoder();
  }  
  catch (e) {
    jQuery("#cdb_info").append('<div class="alert alert-info googlemaps">GoogleMaps steht nicht zur Verf&uuml;gung. ('+e.message+')</div>');
    window.setTimeout(function() {$("#cdb_info div.googlemaps").hide('slow');},5000);
  };
}
