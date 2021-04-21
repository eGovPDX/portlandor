 var app = angular.module('pdxProjects', ['ui.bootstrap','leaflet-directive','underscore','geolocation','ngSanitize','angular-carousel']);
 
 // GSC: Info for leaflet-directive: https://github.com/tombatossals/angular-leaflet-directive
 // This seems to be the primary library for interacting with leaflet in this app.
 // "Now you can start embedding maps to your application adding the custom tag <leaflet> with 
 // some attributes on your HTML code, and interacting with the scope of your controller."

app.factory('angularService', function($http) {
   return {
      getUsersAddress: function(data) {
          //return the promise directly.
          url = 'https://www.portlandmaps.com/arcgis/rest/services/Public/Centerline_Geocoding_PDX/GeocodeServer/findAddressCandidates?Street=&City=&ZIP=&Single+Line+Input=' + data + '&outFields=&outSR=4326&searchExtent=&f=pjson';
          return $http.get(url).then(function(result) {
              //resolve the promise as the data
                  return result.data.candidates;
          });
      },
      getProjectDetail: function(ID) {
          //return the promise directly.
          url = 'http://ecm.city/api/project-detail/';
          return $http.jsonp(url + ID + "?callback=JSON_CALLBACK").then(function(result) {
                  return result.data.data[0].project;
          });
      },
      getProjects: function() {
          var url = "http://ecm.city/api/projects-geojson?callback=JSON_CALLBACK";
          return $http.jsonp(url).then(function(result) {
                  return result.data;
          });
      },
      getProjects2: function() {
          var url = "http://ecm.city/api/projects-geojson-file?callback=JSON_CALLBACK";
          return $http.jsonp(url).then(function(result) {
                  return result.data;
          });
      },
      getProjectTypes: function() {
          var url = "http://ecm.city/api/project-types?callback=JSON_CALLBACK";
          return $http.jsonp(url).then(function(result) {
                  return result.data;
          });
      },
    }
});


app.filter('filteredprojects', function() {

  return function(projects, projTypes) {
    var result = projects.slice();
    var keepGoing = true;
    angular.forEach(projTypes, function(value, key) {
      if(value.checked && (keepGoing == true)) {
        keepGoing = false;
        for(var index = 0; index < result.length; index++) {
          project = result[index];
          
            if(project.properties.types.indexOf(value.name) == -1) {
              result.splice(index--,1);
            }
          
        }
      }
    });
    return result;
  }
});

app.controller('projects', [ '$scope', '$rootScope', '$timeout', '$modal', '$log', '$http', 'leafletData', "leafletBoundsHelpers", "_", "geolocation", "angularService", "$sce", "$compile", "leafletEvents", function ($scope, $rootScope, $timeout, $modal, $log, $http, leafletData, leafletBoundsHelpers, _, geolocation, angularService, $sce, $compile, leafletEvents) {
 
  $scope.homeMarker = false;
  $scope.mapBuilt = false;
  $scope._filtered = new Array();
  $scope.allprojects = {};
  $scope.projects = new Array();
  $scope.locate_show = true;
  $scope.closestpark_show = false;
  $scope.projTypes = new Array();
  $scope.predicate = 'count';
  $scope.detail = new Array();
  $scope.detail.photos = new Array();
  $scope.mySlideIndex = 0;
  $scope.groupedSlides = new Array();
  $scope.markers = {};
  $scope.markerLayer = new Array();
  $scope.markerLayerGroup = new L.LayerGroup();
  $scope.homeLayerGroup = new L.LayerGroup();
  $scope.addresses = new Array();
  $scope.isMobile = false;
  $scope.isPopupOpen= false;

  var local_icons = {
                defaultIcon: {},
                treeIcon: {
                    iconUrl: 'img/marker_trees.png',
                    iconSize:     [36, 32], // size of the icon
                    iconAnchor:   [17, 30], // point of the icon which will correspond to marker's location
                    popupAnchor:  [-3, -35] // point from which the popup should open relative to the iconAnchor
                },
                homeIcon: {
                    iconUrl: 'img/marker_home.png',
                    iconSize:     [32, 32], // size of the icon
                    iconAnchor:   [17, 30], // point of the icon which will correspond to marker's location
                    popupAnchor:  [-3, -35] // point from which the popup should open relative to the iconAnchor
                },
                waterIcon: {
                    iconUrl: 'img/marker_water2.png',
                    iconSize:     [40, 49], // size of the icon
                    iconAnchor:   [15, 44], // point of the icon which will correspond to marker's location
                    popupAnchor:  [5, -40] // point from which the popup should open relative to the iconAnchor
                }
  }

  if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
    $scope.isMobile = true;
    window.scrollTo(0,1);
  }

  // GSC: This section gets run on initial load
  angularService.getProjects().then(function(projectData) {
    $scope.allprojects.features = [];
    //remove null or undefined from data set
    var geoProjects = [];
    var geoFileProjects = [];
    for (var i = 0; i <= projectData.features.length; i++) { 
      if (projectData.features[i] === null || projectData.features[i] === undefined) {
      //  console.log(projectData.features[i]);
      }
      else{
        geoProjects.push(projectData.features[i]);
        }
    }
    angularService.getProjects2().then(function(projectData2) {
      $scope.allprojects2 = projectData2;
      //remove null or undefined from data set
      for (var i in $scope.allprojects2.features) {
        if ($scope.allprojects2.features[i] === null || $scope.allprojects2.features[i] === undefined) {
          $scope.allprojects2.features.splice([i],1);
        }
        else{
          geoFileProjects.push($scope.allprojects2.features[i]);
        }
      }
      $scope.allprojects.features = geoProjects.concat(geoFileProjects);
      $scope.projects = angular.copy($scope.allprojects.features); 

      $scope.addProjects($scope.projects);
      $scope.get_types($scope.projects);

    });


  });

  $scope.isIFrame = function(){
    if(self==top){
      return false;
    }
    else{
      return true;
    }
  }
  
  // Helper method for calculating the total
  $scope.total_projects = function(){
    var total = $scope.projects.length;
    return total;
  };
  
  $scope.get_types = function(projects){
    var cat = [];
    angular.forEach(projects, function(s){ 
      types = s.properties.types.split(","); 
      if(types.length>1){
        for(i=0;i<types.length;i++){
          var type = types[i].trim();
          cat.push(type);
        }
      }
      else
      cat.push(s.properties.types);

    });
    var obj = { };
    for (var i = 0, j = cat.length; i < j; i++) {
      if (obj[cat[i]]) {
        obj[cat[i]]++;
      }
      else { 
        obj[cat[i]] = 1;
      } 
    }
    var cat = [];
    angular.forEach(obj, function(s, key){
      arr = [];
      arr['name'] = key;
      arr['count'] = s;
      if(key!=''){
        cat.push(arr)
      }
    });

    $scope.projTypes = cat;
    /*
    angularService.getProjectTypes().then(function(types) {
      type = [];
      angular.forEach(types.data, function(s){ 
        arr = [];
        arr['icon'] = s.type.field_icon;
        arr['name'] = s.type.name.trim();
        arr['checked'] = false;
        type.push(arr);
      });
      $scope.typesNum = type.length;
      $scope.projTypes = type;
    });
 */
  };

  $scope.searchFilter = function (obj) {
    if((angular.isDefined(obj)) && (obj !== null)){
      var re = new RegExp($scope.searchText, 'i');
      return !$scope.searchText || re.test(obj.properties.name);
    }
  };

  $scope.avg_size = function(){
    var total = 0;
    angular.forEach($scope._filtered, function(s){
        total ++;
    });
    return total;
  };

  $scope.$watch($scope.avg_size, function() {
	if($scope.mapBuilt){
		if($scope._filtered.length != $scope.markers.length){
	       $scope.updateMap();
	    }
	}
  }); //end watch


  $scope.page_two = function(){
    var pageHeight = $('#map').parent().height();
    $('#scroll-wrapper').animate({
      scrollTop: pageHeight
    }, 800);
  }
  $scope.backUp = function(){
    $('#scroll-wrapper').animate({
      scrollTop: 0
    }, 800);
  }
//  $scope.page_two_nearest = function(){
//    var pageHeight = $('#map').parent().height();
//    $('#scroll-wrapper').animate({
//      scrollTop: pageHeight
//    }, 800);
//    $scope.findMyProjects();
//  }
  
//  $scope.show_closest = function(){
//    if($scope.closestpark_show)
//      $scope.closestpark_show = false;
//    else
//      $scope.closestpark_show = true;
//  }

  $scope.close_detail = function(){
    $scope.detail_show = false;
  }

  $scope.showFeatureBlock = function(){
    $("#name-block").hide();
    $("#address-block").hide();
    $("#feature-block").show();
  }

  $scope.showNameBlock = function(){
    $("#feature-block").hide();
    $("#address-block").hide();
    $("#name-block").show();
  }

  $scope.showAddressBlock = function(){
    $("#name-block").hide();
    $("#feature-block").hide();
    $("#address-block").show();
  }
  /* clear filters */
  $scope.resetProjects = function(){
    angular.forEach($scope.projTypes, function(s){
      s.checked = false;
    });
    $scope.searchText = null;
  }
  
//  $scope.findMyProjects = function() {
//      $scope.coords = geolocation.getLocation().then(function(data){
//        $scope.addHome(data.coords);
//        $scope.mylatlon = new L.LatLng(data.coords.latitude, data.coords.longitude);
//        $scope.locate_show = false;
//        leafletData.getMap().then(function(map) {
//          parkMarkers = L.geoJson($scope._filtered);
//          var closest_parks = L.GeometryUtil.closestLayer2(map, parkMarkers, $scope.mylatlon);
//          var five_closest = new Array();
//          var closest_markers = new Array();
//          for (var i in closest_parks) {
//            closest_markers.push(closest_parks[i].layer);
//            var park = [];
//            //var distanceTo = closest_parks.distance;
//            distanceTo = (closest_parks[i].distance * .000621371).toFixed(2);
//            park['distance'] = distanceTo;
//            park['name'] = closest_parks[i].layer.feature.title;
//            park['amenities'] = closest_parks[i].layer.feature.amenities;
//            park['address'] = closest_parks[i].layer.feature.address;
//            five_closest.push(park); 
//            if(i>3) break;
//          }
//          $scope.closest_parks = five_closest;
//          $scope.closest_markers = closest_markers;
//
//          var latlngs = [];
//          for (var i in $scope.closest_markers) {
//            latlngs.push($scope.closest_markers[i]._latlng);
//          }
//          $scope.closest_markers[0].togglePopup();
//          var bounds = L.latLngBounds(latlngs);
//          leafletData.getMap().then(function(map) {
//              map.fitBounds(bounds);
//              var popup = L.popup()
//              .setLatLng($scope.closest_markers[0]._latlng)
//              .setContent('<p>Your closest park is:<br />' + $scope.closest_parks[0]['name'] + '<br />' + $scope.closest_parks[0]['address'] + '</p>')
//              .openOn(map);
//          });
//          $scope.show_closest();
//        return {lat:data.coords.latitude, long:data.coords.longitude};
//
//        });
//      });
//
//  }
  
  $scope.get_location = function(){

    if(typeof $scope.searchAddress === 'undefined'){
     
      $scope.coords = geolocation.getLocation().then(function(data){
        $scope.addHome(data.coords);
        $scope.mylatlon = new L.LatLng(data.coords.latitude, data.coords.longitude);
      });

    }
    else{
      angularService.getUsersAddress($scope.searchAddress).then(function(detailData) {
        $scope.addresses = [];
        if(typeof detailData[0] === 'undefined'){
          $scope.homeLayerGroup.clearLayers();
          var address = {address:"No matching locations please try again with street and zip ie. (500 SW 5th Ave, 97204)"};
          $scope.addresses.push(address);
        }
        else if(detailData[0].score == 100){
              var correctAddress = 0;
              locateAddressOnMap(detailData, correctAddress);
              var address = {address:"Found: " + detailData[0].address};
              $scope.addresses.push(address);
        }
        else{ //add handling
              showOtherAdresses(detailData);
        }
      });

    }
  }

	function locateAddressOnMap(data, correctAddress){
	  var coords = {};
	  coords.latitude = data[correctAddress].location.y;
	  coords.longitude = data[correctAddress].location.x;
	  $scope.addHome(coords);
	  $scope.mylatlon = new L.LatLng(data[correctAddress].location.y, data[correctAddress].location.x);
	  
	}
	
	function showOtherAdresses(data){
	  $scope.addresses = data;
	}
	
	$scope.selectAddress = function(data, index){
	  if(typeof data[index].location != 'undefined')
	  locateAddressOnMap(data, index);
	} 

	$scope.close_others = function(x) {
	    angular.forEach($scope.projTypes, function (item) {
	            if(x != item.$$hashKey)
	            item.checked = false;
	        });
	    if($scope.isMobile){ //close menu if mobile
	        angular.element('#toggleSidebar').trigger('click');
	      }
	}

	$scope.openPopup = function(index) {
	  $scope.detail_show = false;
	  leafletData.getMap().then(function(map) {
    
      // Calculate the offset
      String(index);
      if($scope.isMobile){ //close menu if mobile
        angular.element('#toggleSidebar').trigger('click');
        map._layers[index].fire('click');
      }
      else{  //slide map right to make up for menu
        var offset = map.getSize().x*0.08;
        var x = map.latLngToContainerPoint(map._layers[index]._latlng).x;
        if(x<490){ //pan map right away from menu
          map.once('moveend', function(e) {
            map._layers[index].fire('click');
          });
          map.panTo(map._layers[index]._latlng);
          map.panBy(new L.Point(-offset, 0), {animate: false});
        }
        else{
          map._layers[index].fire('click');
        }
      }
      $scope.isPopupOpen = map._layers[index];

	  });
	} 

  /* MAPPING PIECE */
 
  angular.extend($scope, {
                portland: {
                    lat: 45.51,
                    lng: -122.69,
                    zoom: 12
                },
                defaults: {
                    tileLayer: "//stamen-tiles.a.ssl.fastly.net/terrain/{z}/{x}/{y}.png",
                    zoomControlPosition: 'bottomright',
                    tileLayerOptions: {
                        styleId:22677,
                        attribution: '<a href="http://maps.stamen.com/terrain/#12/37.7706/-122.3782">Stamen Terrain</a>, <a href="http://www.openstreetmap.org/">OpenStreetMap</a>, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
                        reuseTiles: true,
                    },
                    scrollWheelZoom: true,
                    maxZoom:16,
                    minZoom:12
                },
                layers: {
                    baselayers: {
                        stamen: {
                            name: 'Stamen Terrain',
                            type: 'xyz',
                            styleId:22677,
                            url: '//stamen-tiles.a.ssl.fastly.net/terrain/{z}/{x}/{y}.png',
                            attribution: '<a href="http://maps.stamen.com/terrain/#12/37.7706/-122.3782">Stamen Terrain</a>, <a href="http://www.openstreetmap.org/">OpenStreetMap</a>, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
                            reuseTiles: true,
                        }
                    },
                },
                markers:{},
  });

  // Mouse over function, called from the Leaflet Map Events
  // GSC: This does not appear to be a mouseover function; it does not fire on mouseover event.
  // It seems to be a function for setting up content that is displayed on mouseover and gets called on intial load.
  function addLabel(feature, layer) {
      if (feature.properties && feature.properties.name) {
        affectedLabel = new L.Label();
        affectedLabel.setContent(feature.properties.name);
        if(feature.geometry.type=="Point"){
         thisCoords = new L.LatLng(parseFloat(feature.geometry.coordinates[1]),parseFloat(feature.geometry.coordinates[0]));
         affectedLabel.setLatLng(thisCoords);
        }
        else
          affectedLabel.setLatLng(layer.getBounds().getCenter());

        layer.bindLabel(feature.properties.name);
        /*
        leafletData.getMap().then(function(map) {
        map.showLabel(affectedLabel);
        });
        */
      }
  }

  // Mouse over function, called from the Leaflet Map Events
  // GSC: This does not appear to be a mouseover function; it does not fire on mouseover event.
  // It seems to be a function for setting up content that is displayed on mouseover and gets called on intial load.
  function onEachFeature(feature, layer) {
      if (feature.properties && feature.properties.summary) {

        if(!feature.properties.contacts){
          var contacts = "";
        }
        else{
          var contacts = "<h5>Contact Info</h5>" + feature.properties.contacts;
        }
        if(feature.properties.photos!="false"){
          var popupContent = "<h4>" + feature.properties.name + "</h4>" + feature.properties.summary + '<span class="pointer" ng-click="showDetail(' + feature.properties.id + ')">More Info</span>';
        }
        else{
          var popupContent = "<h4>" + feature.properties.name + "</h4>" + feature.properties.description + contacts;
        }

        

        layer.bindPopup(popupContent);
        
        addLabel(feature, layer);
        
        if(feature.geometry.type != "Point"){
          addMarker2geometry(feature);
        }
        else{
          layer._leaflet_id = feature.properties.id;
        }
      }
  }

  function style(feature) {
	    return {
	        fillColor: '#0099ff',
	        weight: 6,
	        opacity: 1,
	        color: '#0099ff',
	        dashArray: '3',
	        fillOpacity: 0.6
	    };
	}

  function addMarker2geometry(feature){
      if(!feature.properties.contacts){
        var contacts = "";
      }
      else{
        var contacts = "<h5>Contact Info</h5>" + feature.properties.contacts;
      }
      if(feature.properties.photos!="false"){
          var popupContent = "<h4>" + feature.properties.name + "</h4>" + feature.properties.summary + '<span class="pointer" ng-click="showDetail(' + feature.properties.id + ')">More Info</span>';
      }
      else{
          var popupContent = "<h4>" + feature.properties.name + "</h4>" + feature.properties.description + contacts;
      }

      if(feature.geometry.type=="MultiPolygon"){
        lnglat = getCentroid(feature.geometry.coordinates[0][0]);
      }
      else if(typeof feature.geometry.coordinates[0][0][0] === 'undefined'){
        lnglat = getCentroid(feature.geometry.coordinates);
      }
      else{
        lnglat = getCentroid(feature.geometry.coordinates[0]);
      }

      var waterIcon = new L.Icon(local_icons.waterIcon);
      var marker = L.marker([lnglat[1], lnglat[0]],{icon: waterIcon});
      marker.bindLabel(feature.properties.name);
      //var marker = L.marker([lnglat[1], lnglat[0]]);

      //add hashkey as marker id to fire click event later
      marker._leaflet_id = feature.properties.id;

      marker.bindPopup(popupContent);

      $scope.markerLayerGroup.addLayer(marker);

  }

  function getCentroid (arr) { 
    return arr.reduce(function (x,y) {
        return [x[0] + y[0]/arr.length, x[1] + y[1]/arr.length] 
    }, [0,0]) 
  }

  $scope.addProjects = function(projects) {
        var newMarkers = new Array();

        angular.extend($scope, {
          geojson: {
            data: $scope.projects,
            style: style,
            onEachFeature: onEachFeature
            
          },
        });
        leafletData.getMap().then(function(map) {
          map.on('popupopen', function(e) {
            $scope.detail_show = false;
          });
        });
        /*
        var allProjects = L.geoJson($scope.projects);
        leafletData.getMap().then(function(map) {
        map.fitBounds(allProjects.getBounds());
        });
        */
      $scope.mapBuilt = true;
  };



  $scope.addHome = function(coords) {

    $scope.homeLayerGroup.clearLayers();

    var homeIcon = new L.Icon({
      iconUrl: 'img/marker_home.png',
      iconAnchor:   [17, 30]
    });
    var marker = L.marker([coords.latitude, coords.longitude],{icon: homeIcon});

    marker.bindLabel("My Location");
    $scope.homeLayerGroup.addLayer(marker);

    leafletData.getMap().then(function(map) {
      
      if($scope.isPopupOpen){
        $scope.isPopupOpen.closePopup();
        $scope.isPopupOpen=false;
      }
      $scope.homeLayerGroup.addTo(map);
      // center man on home
      map.panTo(new L.LatLng(coords.latitude, coords.longitude));
      //if mobile device close menu if not pan over.
      if($scope.isMobile){
        angular.element('#toggleSidebar').trigger('click');
      }
      else{ //pan to right to account for left menu
        // Calculate the offset
        var offset = map.getSize().x*0.10;
        map.panBy(new L.Point(-offset, 0), {animate: false});
      }
      //map.fitBounds(allProjects.getBounds());  //removing the zoom in 
    });

  };

  $scope.updateMap = function() {

    $scope.markerLayerGroup.clearLayers();

      angular.extend($scope, {
          geojson: {
            data: $scope._filtered,
            style: style,
            pointToLayer: function(feature, latlng) {
              marker = new L.marker(latlng, {icon: L.icon(local_icons.waterIcon)});
              //marker._leaflet_id = feature.$$hashKey;
              return marker;
            },
            onEachFeature: onEachFeature
            
          },
      });
      
      var allProjects = L.geoJson($scope._filtered);
      leafletData.getMap().then(function(map) {
      $scope.markerLayerGroup.addTo(map);
      //map.fitBounds(allProjects.getBounds());  //removing the zoom in 
      });

    /* keep buttons in checked state */
    $('#project_types').children('label').children('input').each(function () {
       var checkbox = $(this);
       var label = checkbox.parent('label');
       if (checkbox.is(':checked'))  {
          label.addClass('active');
       }
       else {
          label.removeClass('active');
       }
    });
  
  }

              
$scope.showDetail = function(itemID) {
  $('#detail').css('display','block');

  leafletData.getMap().then(function(map) {

      angularService.getProjectDetail(itemID).then(function(detailData) {

            var detailPhotos = [];
            var detailPhotosLarge = [];
            if(detailData.images !== null){

              var detailPhotosArr = detailData.images.split(',');
              angular.forEach(detailPhotosArr, function(photo){ 
                var photoObj = {};
                //var photolink = "<img src='' title='" + photo.alt + "' alt='" + photo.alt + "' />";
                photoObj.img = $sce.trustAsHtml("<img src='" + photo + "' title='" + detailData.title + "' alt='" + detailData.title + "' />");
                photoObj.title = detailData.title;
                detailPhotos.push(photoObj);
              });
              //get larger res images
              var detailLargePhotosArr = detailData.images_large.split(',');
              angular.forEach(detailLargePhotosArr, function(photo){ 
                var photoObj2 = {};
                //var photolink = "<img src='' title='" + photo.alt + "' alt='" + photo.alt + "' />";
                photoObj2.img = $sce.trustAsHtml("<img src='" + photo + "' title='" + detailData.title + "' alt='" + detailData.title + "' />");
                photoObj2.title = detailData.title;
                detailPhotosLarge.push(photoObj2);
              });
            }
            $scope.detail.description = "";

            if(detailData.description!='empty')
            $scope.detail.description = detailData.description;
            $scope.detail.photos = detailPhotos;
            $scope.detail.photosLarge = detailPhotosLarge;
            $scope.detail.bureaus = detailData.bureaus;
            $scope.detail.name = detailData.title;
            $scope.detail.contacts = $sce.trustAsHtml(detailData.contacts);


          if(detailData.types !== null){
            $scope.detail.type = []; 
            var typeArr = detailData.types.split(',');
            for (i=0; i<typeArr.length; i++){
              typeArr[i] = typeArr[i].trim();
            }
            angular.forEach($scope.types, function(type){ 
              if(typeArr.indexOf(type.name.trim()) > -1){
                $scope.detail.type.push(type);
              }
            });
          }
          $scope.detail_show = true;

          var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
          if(width<400){
            width = (width-10);
            $('#detail').width(width);
          }
          //reset slider
          $scope.mySlideIndex = 0;

          return;
          });
      });

}
  $scope.$on('leafletDirectiveMarker.click', function(e, args) {
    // Args will contain the marker name and other relevant information 
    
    $scope.showDetail(args.markerName);


  });

$scope.$on('leafletDirectiveMap.popupopen', function (event, leafletEvent) {

    var newScope = $scope.$new();

    $compile(leafletEvent.leafletEvent.popup._contentNode)(newScope);

/* Removing auto center 
    leafletData.getMap().then(function(map) {
       var px = map.project(leafletEvent.leafletEvent.popup._latlng); // find the pixel location on the map where the popup anchor is
       px.y -= leafletEvent.leafletEvent.popup._container.clientHeight/2 // find the height of the popup container, divide by 2, subtract from the Y axis of marker location
       map.panTo(map.unproject(px),{animate: true}); // pan to new center
    });
 */
});

//END MAPPING

  $scope.$watch('detail.photos', function(values) {
   
    var i, a = [], b;

    for (i = 0; i < $scope.detail.photos.length; i += 2) {
      b = { image1: $scope.detail.photos[i], image1Large: $scope.detail.photosLarge[i] };

      if ($scope.detail.photos[i + 1]) {
        b.image2 = $scope.detail.photos[i + 1];
        b.image2Large = $scope.detail.photosLarge[i + 1];
      }

      a.push(b);
    }
    $scope.groupedSlides = a;

  }, true);

  $scope.$on("leafletDirectiveMap.click", function(event, args) {
    $scope.detail_show = false;
  });

    $.asm = {};
    $.asm.panels = 1;
    

    $scope.showDisclaimer = function () {

    var modalInstance = $modal.open({
      templateUrl: 'myModalContent.html',
      controller: 'ModalInstanceCtrl',
      size: 'md'
    });

    modalInstance.result.then(function (selectedItem) {
      $scope.selected = selectedItem;
    }, function () {
      $log.info('Modal dismissed at: ' + new Date());
    });

  };

$scope.openLighBox = function (slide) {
  var modalInstance = $modal.open({
    templateUrl: 'myModalContent2.html',
    scope: $scope,
    controller: ModalInstanceCtrl2,
    resolve: {
      items: function () {
        return $scope.detail.photosLarge;
      },

      photo: function(){
          return slide.img.toString();
      }
    }
});
}
  /*

$(window).resize(function(){
    //alert(window.innerWidth);

    $scope.$apply(function(){
       leafletData.getMap().then(function(map) {
         console.log(map);
       });
    });
});

*/
}]); // end controller


angular.module('pdxProjects').controller('ModalInstanceCtrl', function ($scope, $modalInstance) {
  $scope.ok = function () {
    $modalInstance.close();
  };

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };
});

var ModalInstanceCtrl2 = function ($scope, $modalInstance, $sce, items, photo) {

  $scope.items = items;
  $scope.photo = $sce.trustAsHtml(photo);
  $scope.selected = {
    item: $scope.items[0]
  };

  $scope.ok = function () {
    $modalInstance.close($scope.selected.item);
  };

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };

};


$(function(){

  $('#show-results').click(function() {
    $('#toggleSidebar').trigger('click');
  });

  $('#toggleSidebar').click(function() {
      if ($.asm.panels === 1) {
        $("#map .leaflet-top").css('z-index',5);
        $('#toggleSidebar i').addClass('icon-chevron-left');
        $('#toggleSidebar i').removeClass('icon-chevron-right');
        return sidebar(2);
      } else {
        $('#toggleSidebar i').removeClass('icon-chevron-left');
        $('#toggleSidebar i').addClass('icon-chevron-right');
        return sidebar(1);
      }
    });

    $(".search-by-btn > .btn").click(function(){
      $(this).addClass("active").siblings().removeClass("active");
    });

    $("#name-block").hide();
    $("#feature-block").hide();
    $('#toggleSidebar').trigger('click');
    /*
     var pageHeight = $('#map').parent().height();
    $('#page1').css("height",pageHeight);
    $('#page2').css("height",pageHeight);
    $('.scroll-container').css("height",pageHeight*2);
    */
});


function sidebar(panels) {
        $.asm.panels = panels;
        var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
        if(width>480){
          var animateTo = -330;
        }
        else{
          var animateTo = -300;
        }
        if (panels === 1) {
          // $('#content').removeClass('span9');
          // $('#content').addClass('span12 no-sidebar');
          $('#sidebar').animate({
               left: animateTo
             },
             {
               easing: 'swing',
               duration: 800,
               complete: function(){
                  $("#map .leaflet-top").css('z-index',500);
              }
          });
        } else if (panels === 2) {
            $('#sidebar').animate({
                left: 0,
            });
            /*
        var pageHeight = $('#map').parent().height();
        $('#map').width($('#map').parent().width());
        $('#map').height(pageHeight);
        $('#sidebar').height(pageHeight);
        */
        }
};

String.prototype.decodeHTML = function() {
    var map = {"gt":">" /* , … */};
    return this.replace(/&(#(?:x[0-9a-f]+|\d+)|[a-z]+);?/gi, function($0, $1) {
        if ($1[0] === "#") {
            return String.fromCharCode($1[1].toLowerCase() === "x" ? parseInt($1.substr(2), 16)  : parseInt($1.substr(1), 10));
        } else {
            return map.hasOwnProperty($1) ? map[$1] : $0;
        }
    });
};

L.Map.prototype.panToOffset = function (latlng, offset, options) {
    var x = this.latLngToContainerPoint(latlng).x - offset[0]
    var y = this.latLngToContainerPoint(latlng).y - offset[1]
    var point = this.containerPointToLatLng([x, y])
    return this.setView(point, this._zoom, { pan: options })
}

 /*
 * L.CRS.Earth is the base class for all CRS representing Earth.
 */

L.CRS.Earth = L.extend({}, L.CRS, {
  wrapLng: [-180, 180],

  R: 6378137,

  // distane between two geographical points using spherical law of cosines approximation
  distance: function (latlng1, latlng2) {
    var rad = Math.PI / 180,
        lat1 = latlng1.lat * rad,
        lat2 = latlng2.lat * rad;

    return this.R * Math.acos(Math.sin(lat1) * Math.sin(lat2) +
        Math.cos(lat1) * Math.cos(lat2) * Math.cos((latlng2.lng - latlng1.lng) * rad));
  }
});