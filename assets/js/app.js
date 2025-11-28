function toggleMode(isHelperMode) {
    const newMode = isHelperMode ? 'helper' : 'customer';
    
    fetch('api/set_mode.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `mode=${newMode}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert("Could not switch mode. Please try again.");
            document.getElementById('mode-toggle').checked = !isHelperMode;
        }
    });
}

function loadNearbyHelpers() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            fetch(`api/get_helpers.php?lat=${lat}&lng=${lng}`)
            .then(response => response.json())
            .then(helpers => {
                const helpersList = document.getElementById('helpers-list');
                helpersList.innerHTML = '<h3 class="font-semibold">Available Helpers Nearby:</h3>';
                
                helpers.forEach(helper => {
                    const helperDiv = document.createElement('div');
                    helperDiv.className = 'p-3 border rounded-lg';
                    helperDiv.innerHTML = `
                        <div class="font-semibold">${helper.full_name}</div>
                        <div class="text-sm text-gray-600">${helper.skill_tags}</div>
                        <div class="text-sm">₹${helper.base_rate}/hr • ${helper.distance.toFixed(1)}km away</div>
                    `;
                    helpersList.appendChild(helperDiv);
                });
            });
        });
    }
}

if (document.getElementById('customer-view')) {
    loadNearbyHelpers();
}