const COLOR = "#8A2BE2";
const OUTLINE = "#FFFFFF";

export default {
  point: {
    type: "simple-marker", // autocasts as new SimpleMarkerSymbol()
    color: COLOR,
    size: "14px",
    outline: {
      // autocasts as new SimpleLineSymbol()
      color: OUTLINE,
      width: 3 // points
    }
  },
  multipoint: {
    type: "simple-marker", // autocasts as new SimpleMarkerSymbol()
    color: COLOR,
    size: "14px",
    outline: {
      // autocasts as new SimpleLineSymbol()
      color: OUTLINE,
      width: 3 // points
    }
  },
  polyline: {
    // symbol used for polylines
    type: "simple-line", // autocasts as new SimpleLineSymbol()
    color: COLOR,
    width: 3
  },
  polygon: {
    // symbol used for polygons
    type: "simple-fill", // autocasts as new SimpleFillSymbol()
    color: OUTLINE,
    style: "solid",
    outline: {
      color: COLOR,
      width: 2
    }
  }
};
