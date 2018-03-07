(function ($) {
  $(document).ready(function ($) {

    var buttonSelector = ".bp-accordion--button";

    $(".paragraph--bp-accordion--container").each(function () {
      if ($(".panel-collapse.in", this).length === $(".panel-collapse", this).length) {
        changePanelButtonToCollapse($(buttonSelector, this));
      }
    });

    $(".panel-collapse.in").each(function () {
      changeAccordionAlt($(this).siblings(".panel-heading").find("a"));
    });

    $(buttonSelector).click(function () {
      if (!$(this).hasClass("active")) {
        openAllPanels(this);
      }
      else {
        closeAllPanels(this);
      }
    });

    $(".paragraph--type--bp-accordion").on('shown.bs.collapse', function () {
      var numPanelOpen = $(this).find(".panel-collapse.in").length;
      var totalNumberPanels = $(this).find(".panel-collapse").length;
      changeAccordionAlt($(".panel-title a", this));
      if (numPanelOpen === totalNumberPanels) {
        changePanelButtonToCollapse($(this).siblings(buttonSelector));
      }
    });

    $(".paragraph--type--bp-accordion").on('hidden.bs.collapse', function () {
      var numPanelOpen = $(this).find(".panel-collapse.in").length;
      changeAccordionAlt($(".panel-title a", this));
      if (numPanelOpen === 0) {
        changePanelButtonToExpand($(this).siblings(buttonSelector));
      }
    });

    function openAllPanels (id) {
      $(id).siblings('.paragraph').find(".panel-collapse").collapse('show');
    }

    function closeAllPanels (id) {
      $(id).siblings('.paragraph').find(".panel-collapse").collapse('hide');
    }

    function changePanelButtonToCollapse(id) {
      if ($(id).hasClass("active")){
        return;
      }
      $(id).attr("title", Drupal.t("Click to collapse all accordions in this section."));
      $(id).text(Drupal.t("Collapse All"));
      $(id).toggleClass("active");
    }

    function changePanelButtonToExpand (id) {
      if (!$(id).hasClass("active")){
        return;
      }
      $(id).attr("title", Drupal.t("Click to expand all accordions in this section."));
      $(id).text(Drupal.t("Expand All"));
      $(id).toggleClass("active");
    }

    function changeAccordionAlt (id) {
      if ($(id).attr("aria-expanded") === 'true') {
        $(id).attr("alt", Drupal.t("Currently open. Click to collapse this section."));
      } else {
        $(id).attr("alt", Drupal.t("Currently closed. Click to expand this section."));
      }
    }

  });
})(jQuery);
