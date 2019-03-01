import Wkt from 'terraformer-wkt-parser';
import Arcgis from './terraformer-arcgis-parser';

export default {
  toArcgis: (wkt) => {
    if(wkt) {
      var geojson = Wkt.parse(wkt);
      var geometry = Arcgis.convert(geojson);
      return geometry;
    }
  },
  toWkt: (geometry) => {
    if (geometry) {
      var geojson = Arcgis.parse(geometry);
      var wkt = Wkt.convert(geojson);
      return wkt;
    }
  }
}
