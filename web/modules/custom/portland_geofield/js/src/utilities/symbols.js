import tinycolor from "tinycolor2";

const FILL = tinycolor("#8A2BE2")
  .setAlpha(0.2)
  .toRgb();

export default {
  point: {
    type: "simple-marker", // autocasts as new SimpleMarkerSymbol()
    color: [FILL.r, FILL.g, FILL.b, FILL.a],
    size: "14px",
    outline: {
      // autocasts as new SimpleLineSymbol()
      color: [FILL.r, FILL.g, FILL.b],
      width: 1 // points
    }
  },
  polyline: {
    // symbol used for polylines
    type: "simple-line", // autocasts as new SimpleLineSymbol()
    color: [FILL.r, FILL.g, FILL.b],
    width: 2
  },
  polygon: {
    // symbol used for polygons
    type: "simple-fill", // autocasts as new SimpleFillSymbol()
    color: [FILL.r, FILL.g, FILL.b, FILL.a],
    style: "solid",
    outline: {
      color: [FILL.r, FILL.g, FILL.b],
      width: 1
    }
  }
};
