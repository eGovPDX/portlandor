class PortlandAddressMapView {
  constructor($, $element, model) {
    this.$ = $;
    this.$element = $element;
    this.model = model;
  }

  ensureContainer(mapId) {
    let $container = this.$('#' + mapId);
    if ($container.length === 0) {
      $container = this.$('<div/>', { id: mapId, class: 'portland-address-verifier--map' })
        .css({ height: '360px' })
        .appendTo(this.$element);
    }
    return $container[0];
  }
}

window.PortlandAddressMapView = PortlandAddressMapView;
