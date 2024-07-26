// GLOBALS ///////////////////////////////////
const BASE_LAYER = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
const AERIAL_LAYER = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
const DEFAULT_LATITUDE = 45.54;
const DEFAULT_LONGITUDE = -122.65;
const DEFAULT_ZOOM = 11;
const ZOOM_POSITION = 'topright';

// CONSTRUCTOR ///////////////////////////////
class LocationWidgetView {
    constructor(jQuery, element, model, settings, L) {
        this.$ = jQuery;
        this.model = model;
        this.settings = settings;
        this.$element = element;
        this.L = L;
        this.map = null;

        console.log('LocationWidgetView is plugged in');
    }

    initMap() {
        console.log('Initializing map');

        if (this.$('#location_map_container').length < 1) {
            console.log('Location widget map div not found.');
            return false;
        }
        if (this.map) {
            alert('Map already initialized! This should never happen.');
            this.map.off();
            this.map.remove();
            this.map = undefined;
        }
        this.map = new this.L.Map("location_map_container", {
            center: new this.L.LatLng(DEFAULT_LATITUDE, DEFAULT_LONGITUDE),
            zoomControl: false,
            zoom: DEFAULT_ZOOM,
            gestureHandling: true
        });

        this.map.addLayer(BASE_LAYER);
        this.map.addControl(new this.L.control.zoom({ position: ZOOM_POSITION }));
        this.map.addControl(this.generateLocateControl());
        this.map.addControl(this.generateAerialControl());

        return true;
    }

    generateLocateControl() {
        const LocateControl = L.Control.extend({
            options: {
                position: "bottomright"
            },
            onAdd: function (map) {
                const locateControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
                locateControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_locate.png)";
                locateControlContainer.title = 'Locate Me';
                locateControlContainer.onclick = () => this.handleLocateButtonClick(map);
                return locateControlContainer;
            }
        });

        return new LocateControl();
    }

    generateAerialControl() {
        const AerialControl = L.Control.extend({
            options: {
                position: "bottomright"
            },
            onAdd: function (map) {
                const aerialControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
                aerialControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png)";
                aerialControlContainer.title = 'Aerial view';
                aerialControlContainer.onclick = () => this.handleAerialButtonClick(map);
                return aerialControlContainer;
            }
        });

        return new AerialControl();
    }

    handleLocateButtonClick(map) {
        // Handle locate button click event
        console.log('Locate button clicked');
        // Add your locate logic here
    }

    handleAerialButtonClick(map) {
        // Handle aerial button click event
        console.log('Aerial button clicked');
        // Add your aerial view logic here
    }
}
