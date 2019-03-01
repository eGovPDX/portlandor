import $ from 'jquery';

const fieldMap = {
  'Street' : 'address-line1',
  'City' : 'locality',
  'State' : 'administrative-area',
  'ZIP' : 'postal-code'
};

const stateMap = {
  'OREGON' : 'OR'
};

export class AddressWrapper {
  constructor(addressField) {
    const selector = `.field--type-address.field--name-${addressField.replace(/_/g, '-')}`;
    this.addressField = $(selector);
    this.address = {};

    // set up ONE handler for this class, rather than every time we change address
    $(document).ajaxComplete((event, xhr, settings) => {
      // check that this is an ajax request from the country code selection, then
      if (
        settings.extraData 
        && settings.extraData._drupal_ajax 
        && settings.extraData._triggering_element_name === `${addressField}[0][address][country_code]`
      ) {
        this.fillFields();
      }
    });
  }

  fillFields() {
    for (const component in fieldMap) {
      if (fieldMap.hasOwnProperty(component)) {
        const addressClass = fieldMap[component];
        if (component === 'State') {
          this.address[component] = stateMap[this.address[component]] || this.address[component];
        }
        this.addressField.find(`.${addressClass}`).val(this.address[component]).change();
      }
    }
  }

  set(address) {
    const countryCode = 'US';
    this.address = address;

    if (this.addressField.find('.country.form-select').length) {
      // Set the country.
      // this will trigger an AJAX call which we are listening to above.
      this.addressField.find('.country.form-select').val(countryCode).trigger('change');
    }
    else {
      this.fillFields();
    }
  }
}