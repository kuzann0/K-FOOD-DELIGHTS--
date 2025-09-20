let map;
let marker;
let geocoder;
let autocomplete;
let infoWindow;

// Initialize the map
function initMap() {
  // Default to a central location in your delivery area
  // Center on Manila by default
  const defaultLocation = { lat: 14.5995, lng: 120.9842 }; // Manila, Philippines
  const defaultBounds = {
    north: 14.7557, // North Manila boundary
    south: 14.4804, // South Manila boundary
    east: 121.0355, // East Manila boundary
    west: 120.9346, // West Manila boundary
  };

  // Create map instance
  map = new google.maps.Map(document.getElementById("map"), {
    center: defaultLocation,
    zoom: 15,
    mapTypeId: "roadmap",
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
    zoomControl: true,
    styles: [
      {
        featureType: "poi",
        elementType: "labels",
        stylers: [{ visibility: "off" }],
      },
    ],
  });

  // Initialize geocoder
  geocoder = new google.maps.Geocoder();

  // Create info window
  infoWindow = new google.maps.InfoWindow();

  // Create marker
  marker = new google.maps.Marker({
    map: map,
    draggable: true,
    animation: google.maps.Animation.DROP,
  });

  // Initialize Places Autocomplete
  autocomplete = new google.maps.places.Autocomplete(
    document.getElementById("pac-input"),
    {
      types: ["address"],
      bounds: new google.maps.LatLngBounds(
        new google.maps.LatLng(defaultBounds.south, defaultBounds.west),
        new google.maps.LatLng(defaultBounds.north, defaultBounds.east)
      ),
      strictBounds: true,
      componentRestrictions: { country: "ph" },
    }
  );
  autocomplete.bindTo("bounds", map);

  // Event listeners
  google.maps.event.addListener(marker, "dragend", function () {
    updateLocationInfo(marker.getPosition());
  });

  autocomplete.addListener("place_changed", function () {
    const place = autocomplete.getPlace();
    if (!place.geometry) {
      alert("No location found for the entered address");
      return;
    }
    updateMapWithPlace(place);
  });

  // Map type controls
  document
    .querySelectorAll(".map-control-btn[data-map-type]")
    .forEach((button) => {
      button.addEventListener("click", function () {
        const mapType = this.getAttribute("data-map-type");
        map.setMapTypeId(mapType);
        // Update active state
        document
          .querySelectorAll(".map-control-btn[data-map-type]")
          .forEach((btn) => {
            btn.classList.remove("active");
          });
        this.classList.add("active");
      });
    });

  // Try to get user's current location or use saved address
  const savedAddress = document.getElementById("address").value;
  if (savedAddress) {
    geocodeAddress(savedAddress);
  } else {
    getCurrentLocation();
  }
}

// Get current location
function getCurrentLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const pos = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        };
        map.setCenter(pos);
        marker.setPosition(pos);
        updateLocationInfo(pos);
      },
      () => {
        handleLocationError(true, infoWindow, map.getCenter());
      }
    );
  } else {
    handleLocationError(false, infoWindow, map.getCenter());
  }
}

// Handle location errors
function handleLocationError(browserHasGeolocation, infoWindow, pos) {
  infoWindow.setPosition(pos);
  infoWindow.setContent(
    browserHasGeolocation
      ? "Error: The Geolocation service failed."
      : "Error: Your browser doesn't support geolocation."
  );
  infoWindow.open(map);
}

// Update map with place details
function updateMapWithPlace(place) {
  map.setCenter(place.geometry.location);
  map.setZoom(17);
  marker.setPosition(place.geometry.location);
  updateLocationInfo(place.geometry.location, place);
}

// Geocode address
function geocodeAddress(address) {
  geocoder.geocode({ address: address }, (results, status) => {
    if (status === "OK") {
      updateMapWithPlace(results[0]);
    } else {
      console.error("Geocode was not successful:", status);
    }
  });
}

// Update location information
function updateLocationInfo(location, place = null) {
  geocoder.geocode({ location: location }, (results, status) => {
    if (status === "OK" && results[0]) {
      const formattedAddress = results[0].formatted_address;

      // Check if location is within delivery bounds
      const lat = location.lat();
      const lng = location.lng();
      const isWithinBounds =
        lat >= defaultBounds.south &&
        lat <= defaultBounds.north &&
        lng >= defaultBounds.west &&
        lng <= defaultBounds.east;

      document.getElementById("formatted-address").textContent =
        formattedAddress;
      document.getElementById("pac-input").value = formattedAddress;

      // Show warning if outside delivery area
      const confirmBtn = document.querySelector(".confirm-location-btn");
      if (!isWithinBounds) {
        alert(
          "Warning: The selected location appears to be outside our delivery area. Please select a location within Manila."
        );
        if (confirmBtn) {
          confirmBtn.disabled = true;
          confirmBtn.style.opacity = "0.5";
        }
      } else if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.style.opacity = "1";
      }

      // Find nearby landmark
      const service = new google.maps.places.PlacesService(map);
      service.nearbySearch(
        {
          location: location,
          radius: 200,
          type: ["point_of_interest"],
        },
        (results, status) => {
          if (status === "OK" && results[0]) {
            document.getElementById("landmark").textContent = results[0].name;
          }
        }
      );

      // Update hidden fields
      document.getElementById("address").value = formattedAddress;
      document.getElementById("latitude").value = location.lat();
      document.getElementById("longitude").value = location.lng();
    }
  });
}

// Confirm location
function confirmLocation() {
  const address = document.getElementById("address").value;
  const landmark = document.getElementById("landmark").textContent;

  if (!address) {
    alert("Please select a valid delivery address");
    return;
  }

  // Add landmark to delivery instructions if exists
  const deliveryInstructions = document.getElementById("deliveryInstructions");
  if (landmark && landmark !== "Not specified") {
    const existingInstructions = deliveryInstructions.value;
    const landmarkText = `Nearest landmark: ${landmark}`;
    deliveryInstructions.value = existingInstructions
      ? `${existingInstructions}\n${landmarkText}`
      : landmarkText;
  }

  alert("Delivery location confirmed!");
}
