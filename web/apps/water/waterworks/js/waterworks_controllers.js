
app.controller('projects', ['$scope', '$http', 'waterworksService', '$sce', '$window', function ($scope, $http, waterworksService, $sce, $window) {

	// globals ///////////////////////////////////////////////////

	// make this variable available in HTML; set in waterworks.js; returns true if width < 880
	$scope.isMobileView = isMobileView;

	// allProjects is an array for all projects retrieved from ECM
	$scope.allProjects = [];

	// projects is an array for projects that should be currently displayed.
	// the projects list in the search panel is bound to this list.
	$scope.projects = [];

	// initialize base map and store a reference to it.
	$scope.map = initBaseMap();

	// holds a reference to previously clicked marker, so we can revert it
	// if another one is clicked.
	$scope.clickedMarker;
	$scope.clickedGeometry;
	$scope.clickedProjectId;

	// flags to control visiblity of detail and search panels
	if (isMobileView) {
		$scope.detailVisible = false;
		$scope.searchVisible = false;
		$scope.mainHeadVisible = true;
		$scope.searchHeadVisible = false;
	} else {
		$scope.detailVisible = false;
		$scope.searchVisible = true;
		$scope.mainHeadVisible = true;
		$scope.searchHeadVisible = false;
	}
	
	// tracks which project is selected to be displayed in detail pane
	$scope.selectedProject;

	// tracks which filter was last clicked.
	$scope.selectedFilter;

	// use an assoc array to track all markers, so we can retrieve them by id and activate them.
	$scope.markers = [];

	// stores location search results, gets bound to repeater in search panel.
	$scope.locationResults;

	// stores a reference to the home marker created when searching by address.
	$scope.homeMarker;

	// initialization ///////////////////////////////////////////

	// projects initially contains a copy of allProjects, but may be filtered based on user input.
	// this is the array that's used to populate the map.
	initProjects();
	
	// functions ///////////////////////////////////////////////

	// called by popup close button; resets highlighted marker.
	// REFACTOR: reset all markers that match clickedProjectId. they may be an exact match ("12345"),
	// or they may be in a group ("12345-1", "12345-2", "12345-3", etc)
	$scope.resetMarker = function () {
		if ($scope.clickedProjectId) {
			var keys = Object.keys($scope.markers);
			for (var i = 0; i < keys.length; i++) {
				if (keys[i] == $scope.clickedProjectId || keys[i].indexOf($scope.clickedProjectId + "-") === 0) {
					// it's a match, reset icon
					$scope.markers[keys[i]].setIcon(new L.Icon(WATER_ICON));
				}
			}
		}
		//if ($scope.clickedMarker) $scope.clickedMarker.setIcon(new L.Icon(WATER_ICON));
		$scope.clickedMarker = null;
		$scope.clickedProjectId = null;
		$scope.clickedGeometry = null;
	}
	
	// called whenever a marker is clicked in the map. behavior is different depending on mobile or desktop view.
	var lastClick = 0;
	var delay = 200;
	$scope.markerClick = function(project, target) {

		if (lastClick >= (Date.now() - delay)) return;
		lastClick = Date.now();

		// if marker is disabled, do nothing
		if (project.properties.disabled) return;

		// REFACTOR: if same geometry is clicked again, reset all associated markers
		if (project.geometry == $scope.clickedGeometry) {
			if (!isMobileView) {
				// hide detail
				$scope.hideDetail();
			} else {
				// clear modal
				clearModal();
			}
			$scope.resetMarker();
			return;
		} else if ($scope.clickedGeometry) {
			$scope.resetMarker();
			if (!isMobileView) {
				$scope.hideDetail();
			} else {
				clearModal();
			}
			return;
		}

		if (isMobileView) {
			populateModal(project, target);
		} else {
			populateModal(project, target);
		}
	}

	$scope.listViewClick = function (project) {
		// fires when an item in the project list is clicked.
		// if isMobileView, hide search panel and focus selected marker.
		// if !isMobileView, show detail panel and focus selected marker.

		// NOTE: clicking item in list view zooms the map to the appropriate marker.
		// before zooming, capture the previous zoom level and lat/lon, so that we
		// can revert to them when user closes detail panel.

		// prevMapCenter = $scope.map.getCenter();
		// prevMapZoom = $scope.map.getZoom();

		if (!isMobileView) {
				$scope.showDetail(project);
				panToMarker(project.properties.id);
				//$scope.map.setZoom(15);
		} else {
				$scope.searchVisible = false;
				populateModal(project, $scope.markers[project.properties.id]);
				$scope.openPopup(project);
		}
    	
	}

	$scope.toggleSearch = function () {
			$scope.searchVisible = $scope.searchVisible ? false : true;
	}
	
	// show the detail panel when:
	// 		desktop - search result is clicked; marker is clicked
	//		mobile  - when popup is clicked
	// in desktop view, detail panel overlays search panel, but search panel is always visible beneath it.
	// in mobile view, search panel needs to be toggled too.
	$scope.showDetail = function (project) {
    	
		// if project is passed in, use that as the selected one to populate detail. otherwise, look in selectedProject.
		if (project) {
			$scope.selectedProject = project;
		}
		var id = $scope.selectedProject.properties.id;
		populateSelectedProject(id);
		
		// toggle views - show detail and hide search
		$scope.detailVisible = true;

		// add carousel and slide classes to slider container. this must not be done until the content is loaded
		// or the slider will throw an error.
		$("#DetailCarousel").addClass("carousel").addClass("slide");
	}

	$scope.hideDetail = function () {
		$scope.detailVisible = false;
		$scope.$apply();
	}

	// filters projects based on project type AND keyword. this function
	// gets called directly by the project type buttons. it also gets called by
	// the keyword keyup event handler helper function. both type and keyword
	// must be considered when filtering.
	// NOTE: if type = "", uses the type stored in $scope.selectedFilter. this allows
	// the name filtering keyup handler to not disturb the currently selected type.
	$scope.filterProjects = function (type, $event, keyword) {
		// reset projects array and rebuild it every time this is called
		$scope.projects = [];

		// reset all markers to normal state
		resetAllMarkers();

		// if All button is clicked, reset name filter field.
		if (type == "All") {
			$scope.nameFilter = "";
		}

		// set selected filter if not empty. if empty, filter on the previously selected type.
		if (type && type.length > 0) $scope.selectedFilter = type;
		else type = $scope.selectedFilter;
		if (!type || typeof(type) === 'undefined' || type == "") type = "All";

		// filtering is case-insensitive
		if (keyword) keyword = keyword.toLowerCase();
		$scope.allProjects.forEach(function (element) {
			if ((type == "All" || (element.properties.type && element.properties.type.indexOf(type) > -1)) &&
				(keyword == "" || typeof(keyword) === 'undefined' || element.properties.name.toLowerCase().indexOf(keyword) > -1)) {
				$scope.projects.push(element);
			} else {
				makeMarkerGray(element.properties.id);
			}
		});

		// if this is keyword search, reset all button states then highlight the selected one
		if ($event) {
			$('#SearchPanel .filters .btn').removeClass('btn-primary').addClass('btn-default');
			$(event.target).removeClass('btn-default');
			$(event.target).addClass('btn-primary');
		}
	}

	$scope.nameFilter_keyup = function($event) {
		// if value is empty or less than 3 chars, disregard the filter term value.
		if (!$event.target.value || $event.target.value.length < 3) {
			$scope.filterProjects("", null, "");
		} else {
			var filterTerm = $event.target.value.toLowerCase();
			$scope.filterProjects("", null, filterTerm);
		}
	}

	$scope.openPopup = function (project) {
		// hide search and detail panels
		$scope.hideDetail();

		//var marker = $scope.markers[project.properties.id];
		$scope.selectedProject = project;

		// using custom popups, so marker.openPopup() won't help us here.
		// do it manually: pan to latlng, then populate popup.
		// this only pans to the first marker associated with the project
		panToMarker(project.properties.id);
		$scope.map.setZoom(15);
		//    	var latlng = marker._latlng;
		//    	$scope.map.panTo(L.latLng(latlng.lat, latlng.lng));
		//    	populateModal(project, marker);
	}

	$scope.locationSearch = function () {
		
		var searchTerm = $scope.addressSearch;
		
		// if term is undefined or empty return null results
		if (!searchTerm || searchTerm.length < 1) return [];

		// look up address
		waterworksService.getUsersAddress(searchTerm).then(function(detailData) {
			$scope.locationResults = [];
			if (typeof detailData[0] === 'undefined') {
				// no results found
				// do something

			} else if (detailData[0].score == 100) {
				// exact match found, locate on map and hide remaining results
				$scope.selectAddress(detailData[0]);
			} else {
				// load/display address results
				$scope.locationResults = detailData;
			}
		});
	}
    
	$scope.searchEnterSubmit = function (event) {
		if (event.which == 13) {
			$scope.locationSearch();
		}
	}
    
	$scope.selectAddress = function (result) {
		var coords = {};
		coords.latitude = result.location.y;
		coords.longitude = result.location.x;
		addHome(coords);
		$scope.map.panTo(L.latLng(coords.latitude, coords.longitude));
		$scope.locationResults = null;
		$scope.searchVisible = false;
		// put selected address in search field
		$scope.addressSearch = result.address;
		$scope.map.setZoom(14);

		// hide modal if open
		clearModal();
	}

	$scope.resetSelectedMarker = function () {
		$scope.resetMarker();
		//if ($scope.clickedMarker) $scope.clickedMarker.setIcon(new L.Icon(WATER_ICON));
	}

	// helper functions ////////////////////////////////////////
	// these might be moved into $scope functions.

	// returns all markers associated with a project id
	function findMarkersByProjectId(id) {
		var keys = Object.keys($scope.markers);
		var markers = [];
		for (var i = 0; i < keys.length; i++) {
			if (keys[i] == id || keys[i].indexOf(id + "-") === 0) {
				markers.push($scope.markers[keys[i]]);
			}
		}
		return markers;
	}

	function panToMarker(id) {
		// get marker
		var markers = findMarkersByProjectId(id);// $scope.markers[id];
		if (markers.length < 1) return;
		var latlng = markers[0]._latlng;
		$scope.map.panTo(L.latLng(latlng.lat, latlng.lng));
		highlightMarkerByProjectId(id);
	}

	// adds the home icon and centers on it based on a set of coordinates.
	// used by the location search.
	function addHome(coords) {

		if ($scope.homeMarker) $scope.map.removeLayer($scope.homeMarker);

		// create home icon and add to map; only allow one instance of home marker
		var homeIcon = new L.Icon(HOME_ICON);
		$scope.homeMarker = new L.Marker([coords.latitude, coords.longitude], {icon: homeIcon, title: 'My Location'}).addTo($scope.map);

		// reset clicked marker
		$scope.resetMarker();
	}

	// closes the modal popup that appears when markers are clicked.
	// if the modal isn't closed before the search or detail panel is opened,
	// the page won't scroll if it's long. also, it's more tidy this way.
	function clearModal() {
		$('#MapPopup').modal("hide");
	}


	// find the selected project in the allProjects array and put it in
	// $scope.selectedProject, which is bound to the project detail pane.
	function populateSelectedProject(id) {

		var found = $scope.allProjects.find(function (project) {
			return project.properties.id == id;
		});

		$scope.selectedProject = found;
		var title = $scope.selectedProject.properties.name;
		var test = title;

		// highlight selected marker
		highlightMarkerByProjectId(id);
	}

	function initBaseMap() {
		// initializes the base leaflet map and positions zoom controls
		var layer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', {
				attribution: "PortlandMaps ESRI"
		});
		var zoomcontrols = new L.control.zoom({ position: ZOOM_POSITION });
		var map = new L.Map("LeafletMap", {
				center: new L.LatLng(DEFAULT_LATITUDE, -122.65),
				zoomControl: false,
				zoom: DEFAULT_ZOOM
		});
		map.addLayer(layer);
		map.addControl(zoomcontrols);
		return map;
	}

	function initProjects() {

		// NOTE: the app now retrieves all needed content on initial project load,
		// rather than making additional round trips to the server for individual
		// project details. the content was already being passed in the geoJson,
		// so let's use it!

		// create empty geoJson layer; we'll add features to it later
		$scope.geoJsonLayer = L.geoJson().addTo($scope.map);
	
		var url = ecm_url + '/api/waterworks';
		$.getJSON(
			url,
			function(json) { 
				$window.waterProjects(json);
			}
		)

		// show error if data isn't loaded safter 5 seconds
		window.setTimeout(function() {
			if ($scope.allProjects == null || $scope.allProjects.length < 1) {
				alert('No water project data exists or an error has occcurred. Please refresh the page to try again in a few minutes.');
			}
		}, 5000);

	}
	
	$window.waterProjects = function(projectData) {
		if (projectData == null || projectData.features == null || projectData.features.length < 1) return;
		projectData.features.forEach(function(feature) {
			if (feature != null) $scope.allProjects.push(feature);
		});
		postLoadInit();
	}


	// this function gets called after the project data is loaded from the ECM.
	// use it to perform any init activities that are dependent on the allProjects
	// list being populated.
	function postLoadInit() {

		// munge content to populate summary, location and date properties
		// so that they're ready to use in the page.
		$scope.allProjects.forEach(function (project) {
			project = setUpContent(project);
		});

		// put allProjects into $scope.projects.
		// projects is used to populate map and might get filtered.
		$scope.projects = $scope.allProjects;
		$scope.$apply();

		// add projects to map
		addProjectsToMap($scope.projects);
	}

	function setUpContent(project) {
		// perform any content manipulation tasks here

		if (project.properties.thumbnail == "") project.properties.thumbnail = null;

		if (project.properties.businesses != "" || project.properties.residences != "") {
			project.properties.description += "<p>";
			if (project.properties.residences != "") {
				project.properties.description += "Residences affected: " + project.properties.residences + "<br>";
			}
			if (project.properties.businesses != "") {
				project.properties.description += "Businesses affected: " + project.properties.businesses;
			}
			project.properties.description += "</p>";
		}

		return project;
	}
    
	function getFormattedTime (dateTime) {
		var hours24 = parseInt(dateTime.getHours().toString().substring(0, 2),10);
		var hours = ((hours24 + 11) % 12) + 1;
		var amPm = hours24 > 11 ? ' PM' : ' AM';
		var minutes = dateTime.getMinutes();

		return hours + ':' + minutes + amPm;
	};
	
	// we need to hack the date format a bit in order to convert to DateTime object.
	// "Monday, May 16, 2016 - 14:15" needs to be changed to "May 15, 2016 14:15", for example.
	function convertDrupalDateToDateTime(strDate) {
		var cnt = strDate.indexOf(',');
		strDate = strDate.substring(cnt+2, strDate.length); // remove day of week
		strDate = strDate.replace(' - ', ' ');
		return new Date(strDate);
	}

	function addProjectsToMap(projects) {

		// if projects arg is undefined, use scope var as default.
		// $scope.projects should always hold array of features we want added to map, even if filtered.
		projects = typeof a !== 'undefined' ? projects : $scope.projects;

		projects.forEach(function (project) {
			var counter = 0;
			L.geoJson(project, {
			  onEachFeature: onEachFeature,
				pointToLayer: function (feature, latlng) {
					var marker = L.marker(latlng, {
						icon: L.icon({
								iconUrl: 'img/marker_water.png' + CACHEBUSTER,
								iconSize: [40, 49], // size of the icon
								iconAnchor: [15, 44], // point of the icon which will correspond to marker's location
								popupAnchor: [5, -40] // point from which the popup should open relative to the iconAnchor
						}),
						title: project.properties.name
					});
					// there may be multiple markers for a project, so using the id as the array key won't be unique enough.
					// would a timestamp work? if we want to use the geometry array index, we'd need to switch case on the
					// type, since the arrays are a little different for some.
					// we also may need to easily get the collection of all markers for a given project in the onEachFeature
					// function or on click.
					$scope.markers[project.properties.id.toString() + "-p" + counter.toString()] = marker;
					counter += 1;
					return marker;
				}
			}).addTo($scope.map);
		});

		// $scope.projects = projects;

		return true;
	}

	// gets called on each map feature (project) as it's added to the map.
	// use this function to set up popups, icons, etc.
	function onEachFeature(project, layer, counter) {

		// the code below needs to support GeometryCollection type...

		// this section needs to be refactored to also support GeometryCollection.
		// this is the use case where there are multiple shapes of different types.
		// we need to attach each geometry to the map, whether it's at project.geometry.coordinates,
		// or project.geometry.geometries[i].coordinates. we want all of the geometries
		// associated with a project to be

		// point
		// multi point
		// line
		// multi line
		// polygon *
		// multi polygon
		// rectangle
		// multi rectangle
		// geometry collection

		var lnglat = [];
		var centroid;

		switch(project.geometry.type) {
			case "Point":
				//addShapeToMap(project.geometry.coordinates, project, counter);
				addPointToMap(layer, project);
				break; // project.geometry.coordinates
			case "Polygon":
				centroid = getCentroid(project.geometry.coordinates[0]);
				addShapeToMap(centroid, project, counter);
				break;
			case "LineString":
				centroid = getCentroid(project.geometry.coordinates);
				addShapeToMap(centroid, project, counter);
				break;
			case "MultiPolygon":
				//for (var i = 0; i < project.geometry.coordinates.length; i++) {
					centroid = getCentroid(project.geometry.coordinates[0][0]);
				addShapeToMap(centroid, project, counter);
				//}
				break; // project.geometry.coordinates[i]
			case "MultiPoint":
				//for (var i = 0; i < project.geometry.coordinates.length; i++) {
				addPointToMap(layer, project, counter);
				//}
				break; // project.geometry.coordinates[i]
			case "GeometryCollection":
				for (var i = 0; i < project.geometry.geometries.length; i++) {
					var project2 = { geometry: project.geometry.geometries[i], type: project.type, properties: project.properties }
					onEachFeature(project2, layer, i);
				}
				break; // project.geometry.geometries[i].geometry.coordinates[i], project.geometry.geometries[i].geometry.type
			case "MultiLineString":
			default:
				for (var i = 0; i < project.geometry.coordinates.length; i++) {
					centroid = getCentroid(project.geometry.coordinates[i]);
					addShapeToMap(centroid, project, i);
				}
				break; // project.geometry.coordinates[i]
		}
		return;
	}

	function addShapeToMap(lnglat, project, counter) {
		var marker = L.marker([lnglat[1], lnglat[0]], {
			icon: new L.Icon(WATER_ICON),
			title: project.properties.name
		}).addTo($scope.map);
		marker.feature = project;
		var counterString = counter ? "-" + counter : "";
		$scope.markers[project.properties.id + counterString] = marker;
		marker.on('click', function (e) {
			$scope.markerClick(project, e.target);
			$scope.selectedProject = project;
		});
	}

	function addPointToMap(layer, project) {
		layer.on('click', function (e) {
			$scope.markerClick(project, e.target);
			$scope.selectedProject = project;
		});

	}
    
	// grabs the appropriate content for the project, HTML-formats it,
	// stuffs it in the modal dialog, and shows the dialog.
	function populateModal(project, target) {
		
		if (isMobileView) {
			// if mobile view, show modal popup
			var content = "";

			if (project.displayThumbnail) {
				content += '<div class="teaserThumb" style="background-image: url(' + project.displayThumbnail + ');" title="Thumbnail image for ' + project.properties.name + '"></div>';
			}

			content += '<div class="teaserContent"><h3>' + project.properties.name + '</h3>';

			content += '<span class="type">' + project.properties.type + "</span>";
			if (project.properties.location) content += '<span class="location">' + project.properties.location + '</span>';
			if (project.properties.date) content += '<span class="dates">' + project.properties.date + '</span>';
			content += '</div>';

			$('#MapPopup .modal-body').html(content);
			$('#MapPopup').modal('show').show();
			
		} else {
			// it's desktop view, show detail instead of modal popup
			if (project) {
				$scope.selectedProject = project;
				$scope.selectedGeometry = project.geometry;
			}
			var id = $scope.selectedProject.properties.id;
			populateSelectedProject(id);
			$scope.showDetail($scope.selectedProject);
			
			// kluge: these elements aren't really in ng scope, since they are dynamically generated by leaflet (seemingly).
			// so we're kludging it with some jquery to activate a link that is within the scope (from the search panel list).
			// is this feasible? what if the list is filtered and the item isn't present? no, not feasible for that reason.
			// we need a hidden link that can be fake-clicked with jquery.
			$('#clickHelper' + $scope.selectedProject.properties.id).click();
		}
    	
		// highlight marker
		highlightMarkerByProjectId(project.properties.id)
	}
    
	function highlightMarkerByProjectId(id) {
		// NOTE: multi-geometry projects will have a marker id appended to the id passed as an arg
		// so we need to highlight all markers with array keys that start with the project id.

		// WARNING: this gets called for every marker, so it might get called multiple times for each project.
		// there's currently no harm in that, excpet a little extra processing.
		// TODO: can we change this to a per-project click event?

		var keys = Object.keys($scope.markers);

		for (var i = 0; i < keys.length; i++) {
			var marker = $scope.markers[keys[i]];
			if (keys[i] == id.toString() || keys[i].indexOf(id + "-") === 0) {
				// the marker belongs to the project, highlight it
				$scope.markers[keys[i]].setIcon(new L.Icon(WATER_ICON_SELECTED));
				$scope.clickedProjectId = id;
			}
		}

		var thisGeometry = findProjectById(id).geometry;
		$scope.clickedGeometry = thisGeometry;
	}

	function findProjectById(id) {
		for (var i = 0; i < $scope.projects.length; i++) {
			if ($scope.projects[i].properties.id == id) return $scope.projects[i];
		}
		return false;
	}

	function resetAllMarkers() {
			$scope.markers.forEach(function (marker) {
					makeMarkerNormal(marker.feature.properties.id);
			});
	}

	function grayAllMarkers() {
			$scope.markers.forEach(function (marker) {
					makeMarkerGray(marker.feature.properties.id);
			});
	}

	function makeMarkerNormal(id) {
			var marker = $scope.markers[id];
			marker.feature.properties.disabled = false;
			marker.setIcon(new L.Icon(WATER_ICON));
	}

	function makeMarkerGray(id) {
			var marker = $scope.markers[id];
			marker.feature.properties.disabled = true;
			marker.setIcon(new L.Icon(WATER_ICON_GRAY));
	}

	function getCentroid(arr) {
			return arr.reduce(function (x, y) {
					return [x[0] + y[0] / arr.length, x[1] + y[1] / arr.length]
			}, [0, 0])
	}


}]);

