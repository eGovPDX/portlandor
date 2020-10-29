app.factory('waterworksService', function ($http) {

	return {
		getUsersAddress: function (data) {
			//return the promise directly.
			url = 'https://www.portlandmaps.com/arcgis/rest/services/Public/Centerline_Geocoding_PDX/GeocodeServer/findAddressCandidates?Street=&City=&ZIP=&Single+Line+Input=' + data + '&outFields=&outSR=4326&searchExtent=&f=pjson';
			return $http.get(url).then(function (result) {
				//resolve the promise as the data
				return result.data.candidates;
			});
		},
		// getProjectDetail: function (ID) {
		// 	//return the promise directly.
		//     url = ecm_url + '/api/water-project-detail/';
		// 	return $http.jsonp(url + ID + "?callback=JSON_CALLBACK").then(function (result) {
		// 		return result.data.data[0].project;
		// 	});
		// },
		getProjects: function (callback) {
			// callback is 'getProjectsCallback'
		    var url = ecm_url + '/api/waterworks';
			return $http.jsonp(url, {params: {cb: 'callback'}}).then(callback);
		},
		getProjects2: function (callback) {
			// callback is 'getProjects2Callback'
		    var url = ecm_url + '/api/water-projects-geojson-file';
			return $http.jsonp(url).then(function (result) {
				return result.data;
			});
		},
		getProjectTypes: function (callback) {
			// // callback is 'getProjectTypesCallback'
		  //   var url = ecm_url + '/api/project-types';
			// return $http.jsonp(url).then(function (result) {
			// 	return result.data;
			// });
		},
	}
});

