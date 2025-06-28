document.addEventListener("DOMContentLoaded", function () {
  function fillSummary() {
  const summary = document.querySelector(".summary-output");
  if (!summary) return;

  const data = {
    address_from: document.querySelector("[name='address_from']").value,
    address_to: document.querySelector("[name='address_to']").value,
    package_type: document.querySelector("[name='package_type']").value,
    package_weight: document.querySelector("[name='package_weight']").value + ' ' + document.querySelector("[name='weight_unit']").value,
    slot: document.querySelector("[name='delivery_slot']").value,
    sender: document.querySelector("[name='sender_name']").value,
    name: document.querySelector("[name='user_name']").value,
    phone: document.querySelector("[name='user_phone']").value,
    email: document.querySelector("[name='user_email']").value,
    note: document.querySelector("[name='user_note']").value
  };

  summary.innerHTML = `
    <p><strong>Gönderici:</strong> ${data.sender} – ${data.address_from}</p>
    <p><strong>Teslimat:</strong> ${data.name} – ${data.address_to}</p>
    <p><strong>Paket Türü:</strong> ${data.package_type}</p>
    <p><strong>Ağırlık:</strong> ${data.package_weight}</p>
    <p><strong>Teslimat Saati:</strong> ${data.slot}</p>
    <p><strong>Telefon:</strong> ${data.phone}</p>
    <p><strong>Email:</strong> ${data.email}</p>
    <p><strong>Ek Not:</strong> ${data.note}</p>
  `;
}


function calcRouteDistance(origin, destination, callback) {
  if (!window.google || !window.google.maps) {
    callback(0);
    return;
  }
  var service = new google.maps.DistanceMatrixService();
  service.getDistanceMatrix(
    {
      origins: [origin],
      destinations: [destination],
      travelMode: 'DRIVING',
      unitSystem: google.maps.UnitSystem.METRIC,
    }, function(response, status) {
      if (status === 'OK') {
        var distanceText = response.rows[0].elements[0].distance.text; // "13.4 km"
        var km = parseFloat(distanceText.replace(',', '.'));
        callback(km);
      } else {
        callback(0);
      }
    }
  );
}

function updateTotalPrice(distance_km) {
  const settings = window.citycourierSettings || {};
  const kmPrice = parseFloat(settings.km_price || 0);
  const minPrice = parseFloat(settings.minimum_price || 0);
  
  const calc = Math.round(minPrice + (distance_km * kmPrice));
  const total = Math.max(minPrice, calc);

  const el = document.querySelector('.dv-order-total-price__value span');
  if (el) el.textContent = '₺ ' + total;

  const hidden = document.getElementById('total_price');
  if (hidden) hidden.value = total;
}


// Güncellenmiş Autocomplete & Map fonksiyonu
function initAutocompleteAndMaps() {
  const fromInput = document.getElementById("address_from");
  const toInput = document.getElementById("address_to");
  const geocoder = new google.maps.Geocoder();

  const fromMap = new google.maps.Map(document.getElementById("map_from"), {
    zoom: 13,
    center: { lat: 39.9208, lng: 32.8541 },
	styles: [
  {
    "featureType": "poi.business",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "road",
    "elementType": "labels.icon",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "transit",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "transit.line",
    "elementType": "geometry.fill",
    "stylers": [
      {
        "color": "#e1cd14"
      }
    ]
  }
],
    disableDefaultUI: true, // Google UI gizle
  });
  const toMap = new google.maps.Map(document.getElementById("map_to"), {
    zoom: 13,
    center: { lat: 39.9208, lng: 32.8541 },
	styles: [
  {
    "featureType": "poi.business",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "road",
    "elementType": "labels.icon",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "transit",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "transit.line",
    "elementType": "geometry.fill",
    "stylers": [
      {
        "color": "#e1cd14"
      }
    ]
  }
],
    disableDefaultUI: true,
  });

  const fromAutocomplete = new google.maps.places.Autocomplete(fromInput, { types: ["geocode"] });
  const toAutocomplete = new google.maps.places.Autocomplete(toInput, { types: ["geocode"] });

  let fromMarker = null;
  let toMarker = null;

  function tryUpdatePrice() {
    const from = fromInput.value.trim();
    const to = toInput.value.trim();
    if (from.length > 6 && to.length > 6) {
      calcRouteDistance(from, to, updateTotalPrice);
    }
  }

  fromMap.addListener("click", function (e) {
    const latLng = e.latLng;
    if (fromMarker) fromMarker.setMap(null);
    fromMarker = new google.maps.Marker({ map: fromMap, position: latLng });
    geocoder.geocode({ location: latLng }, function(results, status) {
      if (status === "OK" && results[0]) {
        fromInput.value = results[0].formatted_address;
        tryUpdatePrice();
      }
    });
  });

  toMap.addListener("click", function (e) {
    const latLng = e.latLng;
    if (toMarker) toMarker.setMap(null);
    toMarker = new google.maps.Marker({ map: toMap, position: latLng });
    geocoder.geocode({ location: latLng }, function(results, status) {
      if (status === "OK" && results[0]) {
        toInput.value = results[0].formatted_address;
        tryUpdatePrice();
      }
    });
  });

  fromAutocomplete.addListener("place_changed", function () {
    const place = fromAutocomplete.getPlace();
    if (!place.geometry) return;
    fromMap.setCenter(place.geometry.location);
    fromMap.setZoom(16);
    if (fromMarker) fromMarker.setMap(null);
    fromMarker = new google.maps.Marker({ map: fromMap, position: place.geometry.location });
    tryUpdatePrice();
  });

  toAutocomplete.addListener("place_changed", function () {
    const place = toAutocomplete.getPlace();
    if (!place.geometry) return;
    toMap.setCenter(place.geometry.location);
    toMap.setZoom(16);
    if (toMarker) toMarker.setMap(null);
    toMarker = new google.maps.Marker({ map: toMap, position: place.geometry.location });
    tryUpdatePrice();
  });

  fromInput.addEventListener('blur', tryUpdatePrice);
  toInput.addEventListener('blur', tryUpdatePrice);
}


if (typeof google !== "undefined" && google.maps && google.maps.places) {
  initAutocompleteAndMaps();
}



});
