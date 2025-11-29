var map;
var userMarker;
var helperMarkers = [];

function initializeMap() {
    if (!document.getElementById('map')) return;
    
    // Initialize map centered on India
    map = L.map('map').setView([20.5937, 78.9629], 5);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Get user's current location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Center map on user location
            map.setView([lat, lng], 13);
            
            // Add user location marker
            userMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'user-location-marker',
                    html: '<div style="background: #3b82f6; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                })
            }).addTo(map);
            
            userMarker.bindPopup('<b>📍 Your Location</b>').openPopup();
            
            // Load and display nearby helpers on map
            loadHelpersOnMap(lat, lng);
            
        }, function(error) {
            console.error('Geolocation error:', error);
            alert('Please enable location access for better experience.');
        });
    }
}

function loadHelpersOnMap(userLat, userLng) {
    fetch(`api/get_helpers.php?lat=${userLat}&lng=${userLng}`)
    .then(response => response.json())
    .then(helpers => {
        // Clear existing helper markers
        helperMarkers.forEach(marker => map.removeLayer(marker));
        helperMarkers = [];
        
        // Add helper markers
        helpers.forEach((helper, index) => {
            // Generate random nearby coordinates for demo (in real app, use actual helper locations)
            const helperLat = userLat + (Math.random() - 0.5) * 0.02;
            const helperLng = userLng + (Math.random() - 0.5) * 0.02;
            
            const helperMarker = L.marker([helperLat, helperLng], {
                icon: L.divIcon({
                    className: 'helper-marker',
                    html: '<div style="background: #10b981; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                })
            }).addTo(map);
            
            helperMarker.bindPopup(`
                <div class="p-2">
                    <b>${helper.full_name}</b><br>
                    <small class="text-gray-600">${helper.skill_tags}</small><br>
                    <span class="text-green-600 font-semibold">₹${helper.base_rate}/hr</span><br>
                    <small>${helper.distance.toFixed(1)}km away</small>
                </div>
            `);
            
            helperMarkers.push(helperMarker);
        });
    })
    .catch(error => {
        console.error('Error loading helpers on map:', error);
    });
}

function addHelperMarker(lat, lng, name, skills, rate) {
    const marker = L.marker([lat, lng], {
        icon: L.divIcon({
            className: 'helper-marker',
            html: '<div style="background: #10b981; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white;"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        })
    }).addTo(map);
    
    marker.bindPopup(`
        <div class="p-2">
            <b>${name}</b><br>
            <small>${skills}</small><br>
            <span class="text-green-600">₹${rate}/hr</span>
        </div>
    `);
    
    return marker;
}

function updateUserLocation(lat, lng) {
    if (userMarker) {
        userMarker.setLatLng([lat, lng]);
        map.setView([lat, lng], 13);
    }
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initializeMap, 500); // Small delay to ensure DOM is ready
});