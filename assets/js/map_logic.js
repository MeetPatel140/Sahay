var map = L.map('map').setView([20.5937, 78.9629], 5);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

function addHelperMarker(lat, lng, name) {
    L.marker([lat, lng]).addTo(map)
        .bindPopup(`<b>${name}</b><br>Available Helper`)
        .openPopup();
}

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        map.setView([lat, lng], 13);
        
        L.marker([lat, lng]).addTo(map)
            .bindPopup('Your Location')
            .openPopup();
    });
}