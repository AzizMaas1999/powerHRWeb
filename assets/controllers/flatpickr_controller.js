import { Controller } from '@hotwired/stimulus';
import flatpickr from 'flatpickr';

export default class extends Controller {
  connect() {
    flatpickr(this.element, {
      dateFormat: "Y-m-d",  // format classique pour Symfony
      altInput: true,
      altFormat: "d/m/Y",
      allowInput: true,
    });
  }
}
