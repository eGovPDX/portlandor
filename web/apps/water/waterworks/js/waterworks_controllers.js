
app.controller('projects', ['$scope', '$http', 'waterworksService', '$sce', '$window', function ($scope, $http, waterworksService, $sce, $window) {

	// globals ///////////////////////////////////////////////////

	// make this variable available in HTML; set in waterworks.js; returns true if width < 880
	$scope.isMobileView = isMobileView;

	// Ensure header visibility is defined immediately (ng-show depends on it).
	$scope.mainHeadVisible = true;
	$scope.searchHeadVisible = false;

	// If the page loads wide and then rotates to mobile, Angular's $scope.isMobileView
	// can become stale (the global is updated by jQuery, but Angular won't digest).
	// Keep it synced so ng-class/ng-show react, and reset searchVisible when
	// entering mobile so the search icon can open the panel.
	var lastIsMobileView = $scope.isMobileView;
	function syncMobileViewFromViewport() {
		var nextIsMobileView = calculateIsMobileView();
		// keep the shared global up-to-date (used throughout this file)
		isMobileView = nextIsMobileView;
		$scope.isMobileView = nextIsMobileView;

		// When crossing breakpoints, normalize panel state.
		// Desktop expects the search panel visible; mobile expects it hidden until opened.
		if (lastIsMobileView !== nextIsMobileView) {
			if (nextIsMobileView) {
				$scope.searchVisible = false;
				$scope.detailVisible = false;
			} else {
				$scope.searchVisible = true;
			}
			lastIsMobileView = nextIsMobileView;
		}
	}

	var onViewportChange = function () {
		// AngularJS 1.2.x: prefer $evalAsync to avoid "$digest already in progress".
		if ($scope.$evalAsync) {
			$scope.$evalAsync(syncMobileViewFromViewport);
		} else if (!$scope.$root || !$scope.$root.$$phase) {
			$scope.$apply(syncMobileViewFromViewport);
		} else {
			syncMobileViewFromViewport();
		}
	};

	angular.element($window).on('resize orientationchange', onViewportChange);
	$scope.$on('$destroy', function () {
		angular.element($window).off('resize orientationchange', onViewportChange);
	});

	function runInAngular(fn) {
		if (typeof fn !== 'function') return;
		// AngularJS 1.2.x safe async digest entry.
		if (typeof $scope.$evalAsync === 'function') {
			$scope.$evalAsync(fn);
		} else if (!$scope.$root || !$scope.$root.$$phase) {
			$scope.$apply(fn);
		} else {
			fn();
		}
	}

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

	// Tracks where focus should return when closing the detail panel.
	// type: 'teaser' | 'marker'
	$scope.lastFocusReturn = null;

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

	// If the search sidebar is visible on initial desktop load, start keyboard focus there
	// so Tab doesn't jump into Leaflet marker icons first.
	var didAutoFocusSidebar = false;
	function maybeAutoFocusSearchSidebar() {
		if (didAutoFocusSidebar) return;
		if (isMobileView) return;
		if (!$scope.searchVisible) return;
		var doc = $window && $window.document;
		if (!doc) return;
		var active = doc.activeElement;
		// Don't steal focus if the user already focused something.
		if (active && active !== doc.body && active !== doc.documentElement) return;
		var allFilterButton = doc.getElementById('Filter-All');
		if (allFilterButton && typeof allFilterButton.focus === 'function') {
			didAutoFocusSidebar = true;
			allFilterButton.focus();
		}
	}
	setTimeout(maybeAutoFocusSearchSidebar, 0);
	
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

		// If the same geometry is clicked again, toggle it closed.
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
			// Continue to handle the newly clicked marker (avoid requiring a second click).
		}

		if (isMobileView) {
			populateModal(project, target);
		} else {
			// If the user clicked a map marker, return focus there on detail close.
			if (project && project.properties && project.properties.id != null) {
				$scope.lastFocusReturn = { type: 'marker', id: project.properties.id };
			}
			populateModal(project, target);
			// Keep existing desktop behavior consistent with keyboard activation: zoom to marker.
			if (project && project.properties && project.properties.id != null) {
				panToMarker(project.properties.id, 14);
			}
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

		// Remember which project teaser opened the detail panel so we can restore focus on close.
		if (project && project.properties && project.properties.id != null) {
			$scope.lastTeaserProjectId = project.properties.id;
			$scope.lastFocusReturn = { type: 'teaser', id: project.properties.id };
		}

		if (!isMobileView) {
				$scope.showDetail(project);
		} else {
				$scope.searchVisible = false;
				populateModal(project, $scope.markers[project.properties.id]);
				$scope.openPopup(project);
		}
    	
		// pan to marker and set zoom to 14
		panToMarker(project.properties.id, 14);
	}

	function restoreFocusToLastOrigin() {
		var info = $scope.lastFocusReturn;
		if (!info || info.id == null) return;
		setTimeout(function () {
			if (info.type === 'marker') {
				var marker = $scope.markers && $scope.markers[('' + info.id)];
				var el = null;
				if (marker) {
					if (typeof marker.getElement === 'function') {
						el = marker.getElement();
					}
					el = el || marker._icon;
				}
				if (el && typeof el.focus === 'function') {
					el.focus();
					return;
				}
			}
			// Default/fallback: return focus to the teaser card.
			var teaserEl = document.getElementById('Teaser' + info.id);
			if (teaserEl && typeof teaserEl.focus === 'function') {
				teaserEl.focus();
			}
		}, 0);
	}

	$scope.closeDetail = function () {
		$scope.detailVisible = false;
		if (typeof $scope.resetSelectedMarker === 'function') {
			$scope.resetSelectedMarker();
		}
		restoreFocusToLastOrigin();
	}

	$scope.projectTeaserKeydown = function ($event, project) {
		if (!$event) return;
		var keyCode = $event.keyCode || $event.which;
		// Enter (13) or Space (32)
		if (keyCode !== 13 && keyCode !== 32) return;
		if (typeof $event.preventDefault === 'function') $event.preventDefault();
		if (typeof $event.stopPropagation === 'function') $event.stopPropagation();
		$scope.listViewClick(project);
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

		// If a marker popup modal is open, close it before showing detail.
		clearModal();
    	
		// if project is passed in, use that as the selected one to populate detail. otherwise, look in selectedProject.
		if (project) {
			$scope.selectedProject = project;
		}
		var id = $scope.selectedProject.properties.id;
		populateSelectedProject(id);
		
		// toggle views - show detail and hide search
		$scope.detailVisible = true;

		// Move focus into the details panel for keyboard/screen reader users.
		setTimeout(function () {
			var detailTitle = document.getElementById('ProjectDetailTitle');
			if (detailTitle && typeof detailTitle.focus === 'function') {
				detailTitle.focus();
				return;
			}
			var closeButton = document.getElementById('ProjectDetailClose');
			if (closeButton && typeof closeButton.focus === 'function') {
				closeButton.focus();
				return;
			}
			var detailPanel = document.getElementById('ProjectDetail');
			if (detailPanel && typeof detailPanel.focus === 'function') {
				detailPanel.focus();
			}
		}, 0);

		// add carousel and slide classes to slider container. this must not be done until the content is loaded
		// or the slider will throw an error.
		$("#DetailCarousel").addClass("carousel").addClass("slide");
	}

	$scope.hideDetail = function () {
		$scope.detailVisible = false;
		//$scope.$apply();
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

		// if this is keyword search, reset all button states then highlight the selected one
		if ($event) {
			$('#SearchPanel .filters .btn').removeClass('btn-primary').addClass('btn-default');
			$(event.target).removeClass('btn-default');
			$(event.target).addClass('btn-primary');
		}

		// if All button is clicked, reset name filter field; markers have already
		// been reset, so we don't need to spin through them all again.
		if (type == "All") {
			$scope.nameFilter = "";
			$scope.projects = $scope.allProjects;
			return false;
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
		panToMarker(project.properties.id, 15);
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

	// Allows users to dismiss the address search results/no-results message.
	// locationResults is null when no search is being shown.
	$scope.clearLocationResults = function () {
		$scope.locationResults = null;
	}

	// Auto-dismiss results/no-results as soon as the user edits the address field.
	// Only clear if a search has already produced a visible state.
	$scope.addressSearchChanged = function () {
		if ($scope.locationResults != null) {
			$scope.locationResults = null;
		}
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
		//$scope.map.setZoom(14); // rounding error with setZoom causes zoom to not center on latlng
		$scope.map.panTo(L.latLng(coords.latitude, coords.longitude));
		$scope.locationResults = null;
		$scope.searchVisible = false;
		// put selected address in search field
		$scope.addressSearch = result.address;

		// hide modal if open
		clearModal();
	}

	$scope.resetSelectedMarker = function () {
		$scope.resetMarker();
		//if ($scope.clickedMarker) $scope.clickedMarker.setIcon(new L.Icon(WATER_ICON));
	}

	// helper functions ////////////////////////////////////////
	// these might be moved into $scope functions.

	function applyMarkerAccessibility(marker, project) {
		if (!marker || !project || !project.properties) return;
		var ariaLabel = project.properties.name;
		if (ariaLabel == null) ariaLabel = '';
		ariaLabel = ('' + ariaLabel).trim();
		var isDisabled = !!project.properties.disabled;

		function ensureMarkerInView() {
			try {
				if (!$scope.map || !marker) return;
				if (typeof marker.getLatLng !== 'function') return;
				var latlng = marker.getLatLng();
				if (!latlng) return;
				if (typeof $scope.map.getBounds !== 'function') return;
				var bounds = $scope.map.getBounds();
				if (!bounds) return;
				// Use a slightly shrunken bounds so focused markers aren't right on the edge.
				var innerBounds = (typeof bounds.pad === 'function') ? bounds.pad(-0.12) : bounds;
				if (typeof innerBounds.contains === 'function' && innerBounds.contains(latlng)) return;
				if (typeof $scope.map.panTo === 'function') {
					$scope.map.panTo(latlng, { animate: true, duration: 0.5 });
				}
			} catch (e) {
				// No-op: never block focus if panning fails.
			}
		}

		function setAttributes() {
			var el = null;
			if (typeof marker.getElement === 'function') {
				el = marker.getElement();
			}
			el = el || marker._icon;
			if (!el) return;
			el.setAttribute('role', 'button');
			el.setAttribute('tabindex', '0');
			el.setAttribute('aria-label', ariaLabel);
			el.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');

			// When a marker receives keyboard focus, ensure it is visible in the map viewport.
			if (el.getAttribute('data-ww-pan') !== '1') {
				el.setAttribute('data-ww-pan', '1');
				el.addEventListener('focus', function () {
					if (isDisabled) return;
					// Delay slightly so Leaflet has applied any transforms for the new focus target.
					setTimeout(ensureMarkerInView, 0);
				});
			}

			// Add key activation (Enter/Space) once per element.
			if (el.getAttribute('data-ww-keyboard') !== '1') {
				el.setAttribute('data-ww-keyboard', '1');
				el.addEventListener('keydown', function (event) {
					var key = event && (event.key || event.keyCode || event.which);
					var isEnter = key === 'Enter' || key === 13;
					var isSpace = key === ' ' || key === 'Spacebar' || key === 32;
					if (!isEnter && !isSpace) return;

					// Prevent page scroll on Space.
					if (typeof event.preventDefault === 'function') event.preventDefault();
					if (typeof event.stopPropagation === 'function') event.stopPropagation();
					if (isDisabled) return;

					var activate = function () {
						if (typeof $scope.showDetail === 'function') {
							if (isMobileView) {
								$scope.searchVisible = false;
							}
							if (project && project.properties && project.properties.id != null) {
								$scope.lastFocusReturn = { type: 'marker', id: project.properties.id };
							}
							$scope.showDetail(project);
							panToMarker(project.properties.id, 14);
						}
					};

					// AngularJS 1.2.x safe async digest entry.
					if (typeof $scope.$evalAsync === 'function') {
						$scope.$evalAsync(activate);
					} else if (typeof $scope.$apply === 'function') {
						try {
							$scope.$apply(activate);
						} catch (e) {
							activate();
						}
					} else {
						activate();
					}
				});
			}
		}

		marker.on('add', setAttributes);
		setAttributes();
	}

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

	function panToMarker(id, zoom = null) {
		// get marker
		var markers = findMarkersByProjectId(id);// $scope.markers[id];
		if (markers.length < 1) return;
		var latlng = markers[0]._latlng;
		$scope.map.setView(L.latLng(latlng.lat, latlng.lng), zoom);
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
		var $mapPopup = $('#MapPopup');
		if (!$mapPopup || $mapPopup.length < 1) return;
		try {
			$mapPopup.modal('hide');
		} catch (e) {
			// Ignore if bootstrap modal isn't initialized.
		}
		// Ensure the teaser overlay can't linger above the detail pane.
		$mapPopup.removeClass('in').hide().attr('aria-hidden', 'true');
		$mapPopup.find('.modal-body').empty();
		$('.modal-backdrop').remove();
		$('body').removeClass('modal-open');
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
				tap: false,
				zoomControl: false,
				zoom: DEFAULT_ZOOM
		});
		map.addLayer(layer);
		map.addControl(zoomcontrols);
		map.getContainer().removeAttribute("tabindex");
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
		}, 7000);

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
		projects = typeof projects !== 'undefined' ? projects : $scope.projects;
		
		function getRepresentativeLngLat(project) {
			if (!project || !project.geometry) return null;
			var g = project.geometry;
			// Some datasets store multiple geometries here even when type isn't GeometryCollection.
			if (g.geometries && g.geometries.length) {
				for (var gi = 0; gi < g.geometries.length; gi++) {
					var nestedProject = { geometry: g.geometries[gi], properties: project.properties, type: project.type };
					var nestedLngLat = getRepresentativeLngLat(nestedProject);
					if (nestedLngLat) return nestedLngLat;
				}
				return null;
			}
			switch (g.type) {
				case 'Point':
					return g.coordinates;
				case 'MultiPoint':
					return (g.coordinates && g.coordinates.length) ? g.coordinates[0] : null;
				case 'Polygon':
					return (g.coordinates && g.coordinates.length) ? getCentroid(g.coordinates[0]) : null;
				case 'MultiPolygon':
					return (g.coordinates && g.coordinates.length && g.coordinates[0].length) ? getCentroid(g.coordinates[0][0]) : null;
				case 'LineString':
					return (g.coordinates && g.coordinates.length) ? getCentroid(g.coordinates) : null;
				case 'MultiLineString':
					return (g.coordinates && g.coordinates.length && g.coordinates[0].length) ? getCentroid(g.coordinates[0]) : null;
				case 'GeometryCollection':
					if (g.geometries && g.geometries.length) {
						for (var i = 0; i < g.geometries.length; i++) {
							var project2 = { geometry: g.geometries[i], properties: project.properties, type: project.type };
							var ll = getRepresentativeLngLat(project2);
							if (ll) return ll;
						}
					}
					return null;
				default:
					// Best-effort fallback for unexpected geometry structures.
					if (g.coordinates && g.coordinates.length && typeof g.coordinates[0][0] === 'number') return g.coordinates[0];
					return null;
			}
		}

		function addNonPointGeometriesToMap(project) {
			if (!project || !project.geometry) return;
			var g = project.geometry;

			// Some datasets store multiple geometries here even when type isn't GeometryCollection.
			if (g.geometries && g.geometries.length) {
				for (var gi = 0; gi < g.geometries.length; gi++) {
					addNonPointGeometriesToMap({
						type: 'Feature',
						geometry: g.geometries[gi],
						properties: project.properties
					});
				}
				return;
			}

			if (g.type === 'GeometryCollection') {
				// GeometryCollection may include Point geometries. If we pass it directly to L.geoJson,
				// Leaflet will auto-create default markers (marker-icon-2x.png) that won't have our a11y.
				if (g.geometries && g.geometries.length) {
					for (var i = 0; i < g.geometries.length; i++) {
						addNonPointGeometriesToMap({
							type: 'Feature',
							geometry: g.geometries[i],
							properties: project.properties
						});
					}
				}
				return;
			}

			if (g.type === 'Point' || g.type === 'MultiPoint') return;

			try {
				L.geoJson(project, {
					filter: function (feature) {
						var t = feature && feature.geometry && feature.geometry.type;
						return t !== 'Point' && t !== 'MultiPoint';
					}
				}).addTo($scope.map);
			} catch (e) {
				// Ignore geometry render failures; marker creation below still proceeds.
			}
		}

		projects.forEach(function (project) {
			if (!project || !project.properties || project.properties.id == null) return;

			// Render non-point geometries (polygons/lines), but never allow Leaflet to auto-create
			// default Point markers (marker-icon-2x.png).
			addNonPointGeometriesToMap(project);

			// Create exactly one marker per project.
			var rep = getRepresentativeLngLat(project);
			if (!rep || rep.length < 2) return;
			var marker = L.marker([rep[1], rep[0]], {
				icon: new L.Icon(WATER_ICON),
				title: project.properties.name
			}).addTo($scope.map);
			marker.feature = project;
			applyMarkerAccessibility(marker, project);
			$scope.markers[project.properties.id.toString()] = marker;
			marker.on('click', function (e) {
				runInAngular(function () {
					$scope.markerClick(project, e.target);
					$scope.selectedProject = project;
				});
			});
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
			case "MultiPolygon":
				for (var i = 0; i < project.geometry.coordinates.length; i++) {
					centroid = getCentroid(project.geometry.coordinates[i][0]);
					addShapeToMap(centroid, project, i);
				}
				break; // project.geometry.coordinates[i]
			case "LineString":
				centroid = getCentroid(project.geometry.coordinates);
				addShapeToMap(centroid, project, counter);
				break;
			case "MultiPoint":
				addPointToMap(layer, project, counter);
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
		applyMarkerAccessibility(marker, project);
		var counterString = counter ? "-" + counter : "";
		$scope.markers[project.properties.id + counterString] = marker;
		marker.on('click', function (e) {
			runInAngular(function () {
				$scope.markerClick(project, e.target);
				$scope.selectedProject = project;
			});
		});
	}

	function addPointToMap(layer, project) {
		layer.on('click', function (e) {
			runInAngular(function () {
				$scope.markerClick(project, e.target);
				$scope.selectedProject = project;
			});
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
			$scope.allProjects.forEach(function (project) {
				makeMarkerNormal(project.properties.id);
			});
	}

	function grayAllMarkers() {
			$scope.allProjects.forEach(function (project) {
					makeMarkerGray(project.properties.id);
			});
	}

	function makeMarkerNormal(id) {
		// find markers that match this ID; there may be multiples connected to this project
		var keys = Object.keys($scope.markers);
		for (var i = 0; i < keys.length; i++) {
			if (keys[i] == id || keys[i].indexOf(id + "-") === 0) {
				// it's a match, make icon normal
				var marker = $scope.markers[keys[i]];
				if (marker.feature) {
					marker.feature.properties.disabled = false;
				} else if (marker.properties) {
					marker.properties.disabled = false;
				} else {
					return false;
				}
				marker.setIcon(new L.Icon(WATER_ICON));
			}
		}
	}

	function makeMarkerGray(id) {
		// find markers that match this ID; there may be multiples connected to this project
		var keys = Object.keys($scope.markers);
		for (var i = 0; i < keys.length; i++) {
			if (keys[i] == id || keys[i].indexOf(id + "-") === 0) {
				// it's a match, make icon normal
				var marker = $scope.markers[keys[i]];
				if (marker.feature) {
					marker.feature.properties.disabled = true;
				} else if (marker.properties) {
					marker.properties.disabled = true;
				} else {
					return false;
				}
				marker.setIcon(new L.Icon(WATER_ICON_GRAY));
			}
		}
	}

	function getCentroid(arr) {
			return arr.reduce(function (x, y) {
					return [x[0] + y[0] / arr.length, x[1] + y[1] / arr.length]
			}, [0, 0])
	}


}]);

