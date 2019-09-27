(function ($) {
  var hinterXHR = new XMLHttpRequest();

  function handleAddressInput(event) {
    // retireve the input element
    var value = $(event.target).val();
    var option = $('#addresslist').find("[value='" + value + "']");

    if (option.length > 0) {
      $('input.locality').val(option.data("city"));
      $('input.postal-code').val(option.data("zip"));

      $("input.administrative-area option").each(function() {
        this.selected =  (this.text.toLowerCase() === option.data("state").toLowerCase());
      });
    }
  }

  function handleAddressKeyup(event) {
    // retireve the input element
    var input = event.target;

    // retrieve the datalist element
    var addresslist = document.getElementById('addresslist');

    // minimum number of characters before we start to generate suggestions
    var min_characters = 2;

    if (input.value.length < min_characters ) { 
        return;
    } else { 

        // abort any pending requests
        hinterXHR.abort();

        hinterXHR.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {

                // We're expecting a json response so we convert it to an object
                var response = JSON.parse( this.responseText ); 

                // clear any previously loaded options in the datalist
                addresslist.innerHTML = "";

                response.candidates.forEach(function(item) {
                    // Create a new <option> element.
                    var option = document.createElement('option');
                    option.value = item.address;
                    // Set default when the data is incomplete
                    var city = item.attributes.city ? item.attributes.city : 'Portland';
                    var state = item.attributes.state ? item.attributes.state : 'Oregon';
                    var zip_code = item.attributes.zip_code ? item.attributes.zip_code : '';


                    option.text = item.address + ', ' + city + ', ' + state;
                    if(zip_code) option.text += ', ' + item.attributes.zip_code;
                    option.setAttribute('data-city', city);
                    option.setAttribute('data-state', state);
                    option.setAttribute('data-zip', zip_code);

                    // attach the option to the datalist element
                    addresslist.appendChild(option);
                });
            }
        };

        hinterXHR.open("GET", "https://www.portlandmaps.com/api/suggest/?alt_coords=1&api_key=" + drupalSettings.portlandmaps_api_key + "&query=" + encodeURIComponent(input.value), true);
        hinterXHR.send();
    }
  }

  // On keyup, query PortlandMaps Suggest API
  $( document ).ready(function() {
    // Bind event listeners to our input element
    var address_input = $('input.address-line1');
    if(address_input) {
      address_input.on("keyup", handleAddressKeyup);
      address_input.on("input", handleAddressInput);
    }
  });

})(jQuery);