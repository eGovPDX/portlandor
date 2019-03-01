import converter from './converter';

export class ValueWrapper {
  constructor(valueField) {
    this.valueField = document.getElementById(valueField);
  }

  set(geometry) { 
    this.valueField.value = converter.toWkt(geometry);
  }

  clear() {
    this.valueField.value = NULL;
  }

  get() {
    return this.valueField.value;
  }
}