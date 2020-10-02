
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
    $scope.resetMarker = function () {
    	if ($scope.clickedMarker) $scope.clickedMarker.setIcon(new L.Icon(WATER_ICON));
    	$scope.clickedMarker = null;
    }
    
    // called whenever a marker is clicked in the map. behavior is different depending on mobile or desktop view.
    $scope.markerClick = function(project, target) {

        // if marker is disabled, do nothing
        if (project.properties.disabled) return;
    	
        // if same marker is clicked again, reset it; different handling for mobile and desktop
        if (target == $scope.clickedMarker) {
            if (!isMobileView) {
                // hide detail
                $scope.hideDetail();
            } else {
                // clear modal
                clearModal();
            }
            $scope.resetMarker();
            return;
        }
    	
    	if (isMobileView) {
    		populateModal(project, target);
    	} else {
    		populateModal(project, target);
    		//$scope.showDetail(project);
    	}
    }

    // var prevMapCenter;
    // var prevMapZoom;

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
    	
    	// if (isMobileView) {
    	// 	$scope.hideSearch();
    	// 	populateModal(project, $scope.markers[project.properties.id]);
    	// 	$scope.openPopup(project);
    	// } else {
    	// 	$scope.showDetail(project);
    	// 	panToMarker(project.properties.id);
    	// }

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
    	//clearModal();
    	
    	// if project is passed in, use that as the selected one to populate detail. otherwise, look in selectedProject.
    	if (project) {
    		$scope.selectedProject = project;
    	}
    	var id = $scope.selectedProject.properties.id;
    	populateSelectedProject(id);
    	
      	// toggle views - show detail and hide search
		$scope.detailVisible = true;
		// $scope.mainHeadVisible = true;
		// $scope.searchVisible = false;
		// $scope.searchHeadVisible = false;

        // add carousel and slide classes to slider container. this must not be done until the content is loaded
        // or the slider will throw an error.
        $("#DetailCarousel").addClass("carousel").addClass("slide");
    }

    $scope.hideDetail = function () {
    	$scope.detailVisible = false;
    }

    // search is always visible in desktop view (detail view superimposes it). in mobile
    // view, it gets toggled. NOTE: if the modal isn't closed before showing the search panel, 
    // the open modal prevents page scrolling for some reason if the page is long.
    $scope.showSearch = function () {
    	// clearModal();
    	
    	// if (isMobileView) {
    	// 	// show search panel and search header
    	// 	$scope.searchVisible = true;
    	// 	$scope.searchHeadVisible = true;
    	// 	$scope.mainHeadVisible = false;
    	// } else {
    	// 	// desktop view
    	// 	// make sure detail view is hidden so that the search panel layer beneath it is visible
    	// 	$scope.detailVisible = false;
    	// }
    }

    $scope.hideSearch = function () {
    	// // only hide search panel if mobile view
    	// if (isMobileView) {
    	// 	$scope.searchVisible = false;
    	// 	$scope.searchHeadVisible = false;
    	// 	$scope.mainHeadVisible = true;
    	// }
    }
    
    // controls the action associated with the back arrow in the header. it behaves
    // differently in different contexts.
    $scope.goBack = function () {
   //  	if (isMobileView) {
   //  		if ($scope.searchVisible) {
   //  			$scope.hideSearch();
   //  		} else if ($scope.detailVisible) {
   //  			$scope.hideDetail();
   //  		} else {
   //  			document.location = BACK_URL;
   //  		}
   //  	} else {
			// $scope.detailVisible = false;
			// $scope.searchVisible = true;
   //  	}
   //  	return false;
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
    		if ((type == "All" || (element.properties.types && element.properties.types.indexOf(type) > -1)) &&
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
    	$scope.hideSearch();
    	$scope.hideDetail();
    	//alert(project.properties.id);

    	//var marker = $scope.markers[project.properties.id];
    	$scope.selectedProject = project;

    	// using custom popups, so marker.openPopup() won't help us here.
    	// do it manually: pan to latlng, then populate popup.
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

            // if ($scope.locationResults < 1) {
            //     // set timer to hide no results message
            //     setTimeout(function () { 
            //         alert('o hide');
            //         $scope.hideNoAddressResultsMessage = true; 
            //     }, 4000);
            // }
    	});

    }
    
    $scope.searchEnterSubmit = function (event)
    {
    	if (event.which == 13) {
    		$scope.locationSearch();
    	}
    }
    
    // $scope.isNoSearchResults = function () {
    // 	if ($scope.locationResults && $scope.locationResults.length < 1) return true;
    // 	return false;
    // }
    
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
        if ($scope.clickedMarker) $scope.clickedMarker.setIcon(new L.Icon(WATER_ICON));
    }

    // $scope.returnView = function () {
    //     // returns view to center/zoom of where it was before detail item was clicked.
    //     $scope.map.panTo(prevMapCenter);
    //     $scope.map.setZoom(prevMapZoom);
    // }


    // helper functions ////////////////////////////////////////
    // these might be moved into $scope functions.

    function panToMarker(id) {
        // get marker
        var marker = $scope.markers[id];
        var latlng = marker._latlng;
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

    	// hide search panel
    	$scope.hideSearch();

    	// reset clicked marker
    	$scope.resetMarker();
    }

    // closes the modal popup that appears when markers are clicked.
    // if the modal isn't closed before the search or detail panel is opened,
    // the page won't scroll if it's long. also, it's more tidy this way.
    function clearModal() {
    	$('#MapPopup').modal("hide");

    	// don't reset marker. it should still be highlighted when user goes back
    	// from detail or search panel.
    	//$scope.resetMarker();
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
        // get waterworks projects from ECM and add to $scope.allProjects array.
        // there are two services/feeds, and both need to be retrieved and combined.
        // (not sure why there are two--different input methods in ECM?)

    	// NOTE: the app now retrieves all needed content on initial project load,
    	// rather than making additional round trips to the server for individual
    	// project details. the content was already being passed in the geoJson,
    	// so let's use it!

        // create empty geoJson layer; we'll add features to it later
		$scope.geoJsonLayer = L.geoJson().addTo($scope.map);
		
		var url = ecm_url + '/api/water-projects-geojson?callback=waterProjects';
		var url2 = ecm_url + '/api/water-projects-geojson-file?callback=waterProjectsFile';

		$http.jsonp(url).then(function () {
			//$http.jsonp(url2);
		});

		window.setTimeout(function() {
			if ($scope.allProjects == null || $scope.allProjects.length < 1) {
				alert('An error has occcurred while retrieving WaterWorks project data. Please refresh the page to try again in a few minutes.');
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

        // add projects to map
        addProjectsToMap($scope.projects);
    }

    function setUpContent(project) {
    	// the intake form for the project data includes several project types,
    	// with options and sub-fields under each type. The content collected there
    	// needs to be compiled and concatenated into summary, location and dates
    	// properties before being displayed to the user. custom logic is used to
    	// munge the various options and subfields into usable content. also put
    	// any images into an array.

    	project.properties.name = htmlDecode(project.properties.name);
        project.displayTitle = project.properties.name;

    	// split comma-delimited list of image urls into array
    	if (project.properties.photos) project.displayPhotos = project.properties.photos.split(', ');
    	if (project.properties.thumbnailUrl) project.displayThumbnail = project.properties.thumbnailUrl;
    	
    	// create summary content from project type options. Some of the options contain replacement tokens
    	// in brackets with the label for the associated subfield. for example, the token [Area] is associated
    	// with the subfield Area, and the subfield's content should replace the token.
    	project.displaySummary = "";
    	
    	// location and dates fields are populated using any location/date content from
    	// the project type option subfields. these will have to be hand-picked and hard-coded
    	// since the various fields are not normalized;
    	var location = "";
    	var dates = "";
    	
    	if (project.properties.dirty_water_options) {
    		var options = project.properties.dirty_water_options;
    		if (project.properties.dirty_water_area) {
    			options = options.replace(FIELD_DIRTY_WATER_AREA_TOKEN, project.properties.dirty_water_area);
    			location += project.properties.dirty_water_area + ' area, ';
    		}
    		if (project.properties.dirty_water_reason) {
    			options = options.replace(FIELD_DIRTY_WATER_REASON_TOKEN, project.properties.dirty_water_reason);
    		}
    		project.displaySummary += options;
    	}
    	
    	if (project.properties.temp_shut_off_options) {
    		var options = project.properties.temp_shut_off_options;
    		if (project.properties.temp_shut_off_date) {
    			options = options.replace(FIELD_SHUT_DOWN_DATE_TOKEN, project.properties.temp_shut_off_date);
    			//dates += project.properties.temp_shut_off_date + ', ';
    		}
				if (project.properties.temp_shut_off_num_res_customers !== null && project.properties.temp_shut_off_num_res_customers > 0) {
					options = options.replace(FIELD_SHUT_DOWN_NUM_RESCUSTOMERS_TOKEN, project.properties.temp_shut_off_num_res_customers);
    		}
				if (project.properties.temp_shut_off_num_bus_customers !== null && project.properties.temp_shut_off_num_bus_customers >= 0) {
					options = options.replace(FIELD_SHUT_DOWN_NUM_BUSCUSTOMERS_TOKEN, project.properties.temp_shut_off_num_bus_customers);
    		}
    		if (project.properties.temp_shut_off_location) {
    			options = options.replace(FIELD_SHUT_DOWN_LOCATION_TOKEN, project.properties.temp_shut_off_location);
    			if (location.indexOf(project.properties.temp_shut_off_location) < 0) location += project.properties.temp_shut_off_location + ' area, ';
    		}
    		if (project.properties.temp_shut_off_reason) {
    			options = options.replace(FIELD_SHUT_DOWN_REASON_TOKEN, project.properties.temp_shut_off_reason);
    		}
    		if (project.properties.temp_shut_off_duration) {
                options = options.replace(FIELD_SHUT_DOWN_DURATION_TOKEN, project.properties.temp_shut_off_duration);

    			// // Date example from Drupal: Monday, May 16, 2016 - 14:15 to Tuesday, May 31, 2016 - 14:15
    			// // NOTE: if user leaves the default values in the intake form, there will only
    			// // be a single date in this field. use it for both from and to, which will result
    			// // in a duration of 0 hours.
    			// var arrDates = project.properties.temp_shut_off_duration.split(' to ');
    			// var from = arrDates[0];
    			// var to = arrDates.length > 1 ? arrDates[1] : arrDates[0];
    			// var dtFrom = convertDrupalDateToDateTime(from);
    			// var dtTo = convertDrupalDateToDateTime(to);
    			// var diffHours = (dtTo - dtFrom) / 1000 / 60 / 60;
    			// var date = (dtFrom.getMonth() + 1) + '/' + dtFrom.getDate() + '/' + dtFrom.getFullYear();
    			
    			// options = options.replace(FIELD_SHUT_DOWN_DURATION_HOURS_TOKEN, diffHours);
    			// options = options.replace(FIELD_SHUT_DOWN_DURATION_START_TOKEN, getFormattedTime(dtFrom));
    			// options = options.replace(FIELD_SHUT_DOWN_DURATION_END_TOKEN, getFormattedTime(dtTo));
    			// options = options.replace(FIELD_SHUT_DOWN_DURATION_DATE_TOKEN, date);

    			// dates += from + ' to ' + to + ', ';
                dates += project.properties.temp_shut_off_duration;
    		}
    		project.displaySummary += options;
    	}
    	
    	if (project.properties.main_break_options) {
    		var options = project.properties.main_break_options;
    		if (project.properties.main_break_location) {
    			options = options.replace(FIELD_MAIN_BREAK_LOCATION_TOKEN, project.properties.main_break_location);
    			if (location.indexOf(project.properties.main_break_location) < 0) {
    				location += location.length == 0 ? 'Near ' : 'near ';
    				location += project.properties.main_break_location + ', ';
    			}
    		}
    		if (project.properties.main_break_completed_date) {
					options = options.replace(FIELD_MAIN_BREAK_COMPLETION_DATE_TOKEN, project.properties.main_break_completed_date);
					dates += 'Completion date ' + project.properties.main_break_completed_date + ', ';
    		}
    		if (project.properties.main_break_impacted_street) {
    			options = options.replace(FIELD_MAIN_BREAK_IMPACTED_STREET_TOKEN, project.properties.main_break_impacted_street);
    			if (location.indexOf(project.properties.main_break_impacted_street) < 0) location += project.properties.main_break_impacted_street + ', ';
    		}
    		if (project.properties.main_break_num_homes) {
					options = options.replace(FIELD_MAIN_BREAK_NUMBER_HOMES_TOKEN, project.properties.main_break_num_homes);
    		}
    		if (project.properties.main_break_num_homes_street) {
					options = options.replace(FIELD_MAIN_BREAK_STREET_NAME_TOKEN, project.properties.main_break_num_homes_street);
    		}
    		if (project.properties.main_break_material) {
					options = options.replace(FIELD_MAIN_BREAK_PIPE_MATERIAL_TOKEN, project.properties.main_break_material);
    		}
    		if (project.properties.main_break_size) {
					options = options.replace(FIELD_MAIN_BREAK_PIPE_SIZE_TOKEN, project.properties.main_break_size);
    		}
    		if (project.properties.main_break_year) {
					options = options.replace(FIELD_MAIN_BREAK_PIPE_YEAR_TOKEN, project.properties.main_break_year);
    		}
    		project.displaySummary += options;
    	}
    	
    	if (project.properties.main_flushing_options) {
    		var options = project.properties.main_flushing_options;
    		if (project.properties.main_flushing_impacted_neighborhood) {
					options = options.replace(FIELD_MAIN_FLUSHING_NEIGHBORHOOD_TOKEN, project.properties.main_flushing_impacted_neighborhood);
					if (location.indexOf(project.properties.main_flushing_impacted_neighborhood) < 0) location += project.properties.main_flushing_impacted_neighborhood + ' neighborhood, ';
    		}
    		if (project.properties.main_flushing_season) {
    			options = options.replace(FIELD_MAIN_FLUSHING_SEASON_TOKEN, project.properties.main_flushing_season);
    		}
    		if (project.properties.main_flushing_year) {
    			options = options.replace(FIELD_MAIN_FLUSHING_YEAR_TOKEN, project.properties.main_flushing_year);
    		}
    		if (project.properties.main_flushing_season && project.properties.main_flushing_year) {
    			dates += project.properties.main_flushing_season + ' ' + project.properties.main_flushing_year + ', ';
    		}
    		if (project.properties.main_flushing_neighborhood_intersection) {
					options = options.replace(FIELD_MAIN_FLUSHING_INTERSECTION_TOKEN, project.properties.main_flushing_neighborhood_intersection);
					if (location.indexOf(project.properties.main_flushing_neighborhood_intersection) < 0) location += project.properties.main_flushing_neighborhood_intersection + ' area, ';
    		}
    		if (project.properties.main_flushing_date) {
    			options = options.replace(FIELD_MAIN_FLUSHING_DATE_TOKEN, project.properties.main_flushing_date);
    			dates += project.properties.main_flushing_date + ', ';
    		}
    		project.displaySummary += options;
    	}

        if (project.properties.public_info_phone) {
            project.properties.contacts = project.properties.contacts.replace(FIELD_PUBLIC_INFO_PHONE, project.properties.public_info_phone);
        }

    	project.displayDescription = project.properties.description; //"<p>Pellentesque eleifend libero non lectus pulvinar, id bibendum magna fermentum. Sed varius facilisis dolor, nec rutrum sem aliquet quis. Aenean sollicitudin tortor ac hendrerit rutrum. Maecenas non ullamcorper leo. Fusce rutrum fringilla ligula eget fermentum. Morbi rutrum odio sed urna blandit finibus. Maecenas pulvinar vitae sem sed dignissim. Nunc non eros ut dolor dignissim lobortis. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nunc ac magna arcu. Morbi vel ultricies odio.</p>";
    	//project.displayDescription = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam eget varius velit. Sed convallis nisi eros, a facilisis augue sodales ac. Suspendisse vitae aliquet nisi. Suspendisse sed accumsan lorem. Morbi nec sodales nulla. Cras eget nisl quis massa tristique sagittis ac non tellus. Vestibulum condimentum posuere ornare. Ut nibh lacus, accumsan eget tincidunt ac, porta eu eros. Quisque placerat efficitur massa ut sollicitudin. Donec non euismod libero. In vel metus vitae purus porttitor mattis. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nulla rutrum magna augue, non egestas enim eleifend ut.</p>";

    	if (location.endsWith(', ')) { location = location.substring(0, location.length-2); }
    	project.displayLocation = location;
    	if (dates.endsWith(', ')) { dates = dates.substring(0, dates.length-2); }
    	project.displayDates = dates;
    	
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
            		$scope.markers[project.properties.id] = marker;
            		return marker;
            	}
            }).addTo($scope.map);
        });

        return true;
    }

    // gets called on each map feature (project) as it's added to the map.
    // use this function to set up popups, icons, etc.
    function onEachFeature(project, layer) {

        // add marker to geometry (point markers are added automatically by geoJson
        if (project.geometry.type != "Point") {
            if (project.geometry.type == "MultiPolygon") {
                lnglat = getCentroid(project.geometry.coordinates[0][0]);
            } else if (typeof project.geometry.coordinates[0][0][0] === 'undefined') {
                lnglat = getCentroid(project.geometry.coordinates);
            } else {
                lnglat = getCentroid(project.geometry.coordinates[0]);
            }
            var marker = L.marker([lnglat[1], lnglat[0]], {
            	icon: new L.Icon(WATER_ICON),
            	title: project.properties.name
            }).addTo($scope.map);
            marker.feature = project;
            $scope.markers[project.properties.id] = marker;
            marker.on('click', function (e) {
            	$scope.markerClick(project, e.target);
            	$scope.selectedProject = project;
            });
        } else {
        	// points
	        layer.on('click', function (e) {
	        	$scope.markerClick(project, e.target);
            	$scope.selectedProject = project;
	        });
        }
    }
    
    // grabs the appropriate content for the project, HTML-formats it,
    // stuffs it in the modal dialog, and shows the dialog.
    function populateModal(project, target) {
    	
    	if (isMobileView) {
    		// if mobile view, show modal popup
	    	var content = "";
	
	    	if (project.displayThumbnail) {
	    		content += '<div class="teaserThumb" style="background-image: url(' + project.displayThumbnail + ');" title="Thumbnail image for ' + project.displayTitle + '"></div>';
	    	}
	
	    	content += '<div class="teaserContent"><h3>' + project.properties.name + '</h3>';
	
	    	content += '<span class="type">' + project.properties.types + "</span>";
	    	if (project.displayLocation) content += '<span class="location">' + project.displayLocation + '</span>';
	    	if (project.displayDates) content += '<span class="dates">' + project.displayDates + '</span>';
	    	content += '</div>';
	
	    	$('#MapPopup .modal-body').html(content);
	    	$('#MapPopup').modal('show').show();
	    	
    	} else {
    		// it's desktop view, show detail instead of modal popup
        	if (project) {
        		$scope.selectedProject = project;
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
//    	if ($scope.clickedMarker) $scope.clickedMarker.setIcon(new L.Icon(WATER_ICON));
//    	target.setIcon(new L.Icon(WATER_ICON_SELECTED));
//    	$scope.clickedMarker = target;
    }
    
    function highlightMarkerByProjectId(id) {
    	// turn off previously selected marker, if set
    	if ($scope.clickedMarker) $scope.clickedMarker.setIcon(new L.Icon(WATER_ICON));
    	// find this selected marker
    	var marker = $scope.markers[id];
    	$scope.clickedMarker = marker;
    	marker.setIcon(new L.Icon(WATER_ICON_SELECTED));    	
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

        //     $scope.allProjects.forEach(function (element) {
        //     if ((type == "All" || (element.properties.types && element.properties.types.indexOf(type) > -1)) &&
        //         (keyword == "" || typeof(keyword) === 'undefined' || element.properties.name.toLowerCase().indexOf(keyword) > -1)) {
        //         $scope.projects.push(element);
        //         // highlight marker
        //         var marker = $scope.markers[element.properties.id];
        //         var test = marker;
        //     }
        // });

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

