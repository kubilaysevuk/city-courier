function initCityCourierAutocomplete() {
  const inputA = document.getElementById("address_from");
  const inputB = document.getElementById("address_to");

  if (typeof google === "undefined") return;

  if (inputA) new google.maps.places.Autocomplete(inputA);
  if (inputB) new google.maps.places.Autocomplete(inputB);
}

document.addEventListener("DOMContentLoaded", initCityCourierAutocomplete);
