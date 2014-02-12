 (function($) {

	
// Constructor
function MapView() {
//  StandardTableView.call(this);
  PersonView.call(this);
  this.name="MapView";
}

//Temp.prototype = StandardTableView.prototype;
Temp.prototype = PersonView.prototype;
MapView.prototype = new Temp();
mapView = new MapView();

MapView.prototype.getData = function() {
  return allPersons;
};

MapView.prototype.renderList = function() {
  t=this;
  
  t.createMultiselect("Status", f("status_id"), masterData.status);
  t.createMultiselect("Station", f("station_id"), masterData.station);
  t.createMultiselect("Bereich", f("bereich_id"), masterData.auth.dep);
  
  $("#cdb_content").html('<div id="map_canvas" style="width: 100%; height: 480px"></div>');
  if (geocoder) {  
    var image = new google.maps.MarkerImage("http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png",
        new google.maps.Size(32, 32),
        new google.maps.Point(0, 0),
        new google.maps.Point(8, 16),
        new google.maps.Size(16, 16)
        );

    var latlng = new google.maps.LatLng(masterData.home_lat, masterData.home_lng);
    map=cdb_prepareMap("map_canvas",latlng);
    
    max=500;
    $.each(allPersons, function(k, a){
      if ((max>0) && (t.checkFilter(a))) {
        if (a.geolat!="") {
          max=max-1;
          var latlng = new google.maps.LatLng(a.geolat, a.geolng);
          var beachMarker = new google.maps.Marker({
            position: latlng,
            map: map,
            icon: image,
            title: a.vorname+" "+a.name+" ["+a.id+"]"
          });  
        }
        if ((max==0) && (confirm("Es werden bereits 500 Personen dargestellt. Sollen wirklich noch mehr angezeigt werden? Es kann dann bei langsameren Computern zu Wartezeiten kommen.")))
          max=10000;
      }  
    });
    
    if (!masterData.auth.viewalldata)
      _cdb_limitZoom(map);

    cdb_addGroupsToMap(map);
  }
};

})(jQuery);
