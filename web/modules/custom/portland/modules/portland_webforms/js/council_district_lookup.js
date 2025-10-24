(($, Drupal) => {
  Drupal.behaviors.councilDistrictLookup = {
    attach() {
      const sectionEl = $("#edit-container-district-result");
      try {
        const districts = JSON.parse($("input[name='hidden_districts_json']").val());
        $("input[name='report_location[location_region_id]']").on("change", (e) => {
          if (e.target.value === "") return;

          const district = districts.find((d) => d.districtNum === e.target.value);
          if (!district) {
            sectionEl.html(
              `<p class="fw-bold invalid-feedback">${Drupal.t("No district found for this location.")}</p>`,
            );
            return;
          }

          sectionEl.html("");
          $(sectionEl, "div").append(
            `<h2>${Drupal.t("You're in !district", { "!district": `<a href="${district.url}">${district.name}</a>` })}</h2>`,
          );
          $(sectionEl, "div").append(district.html);
          
          setTimeout(() => sectionEl[0].scrollIntoView({ behavior: "smooth", block: "nearest" }), 0);
        });
      } catch (e) {
        sectionEl.html(
          `<p class="fw-bold invalid-feedback">${Drupal.t('There was an error loading districts, please try again later or <a href="/feedback">contact website support</a>...')}</p>`,
        );
      }
    },
  };

  Drupal.behaviors.councilDistrictBoundaries = {
    attach() {
      const districtColors = {
        1: "#7CB195",
        2: "#9AC4EA",
        3: "#FBD986",
        4: "#C08FA4",
      };
      const { lMap } = drupalSettings.webform.portland_location_picker;
      fetch(
        "https://www.portlandmaps.com/arcgis/rest/services/Public/CGIS_Layers/MapServer/11/query?where=1%3D1&f=geojson",
      )
        .then((res) => res.json())
        .then(({ features }) => {
          const layer = L.geoJson(features, {
            coordsToLatLng(coords) {
              return new L.LatLng(coords[1], coords[0]);
            },
            onEachFeature(feature, layer) {
              const district = feature.properties.DISTRICT;

              lMap.openTooltip(
                Drupal.t("District @num", { "@num": district }),
                layer.getBounds().getCenter(),
                {
                  className: "fs-6",
                  permanent: true,
                  direction: "center",
                  opacity: 1,
                },
              );
              layer.setStyle({
                color: "#383838",
                fillColor: districtColors[district],
                fillOpacity: 0.5,
                weight: 1,
              });
            },
          });

          layer.addTo(lMap);
        });
    },
  };
})(jQuery, Drupal);
