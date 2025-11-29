let userLocation = null;
let locationPermissionGranted = false;
let locationCheckAttempted = false;

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

function postTask(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    if (userLocation) {
        formData.append('lat', userLocation.lat);
        formData.append('lng', userLocation.lng);
    }
    
    fetch('api/post_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Task posted successfully! Helpers will be notified.');
            event.target.reset();
            loadNearbyHelpers();
        } else {
            alert('Failed to post task: ' + data.message);
        }
    });
}

function loadNearbyHelpers() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            userLocation = {lat, lng};
            locationPermissionGranted = true;
            hideLocationAlert();
            
            fetch(`api/get_helpers.php?lat=${lat}&lng=${lng}`)
            .then(response => response.json())
            .then(helpers => {
                const helpersList = document.getElementById('helpers-list');
                if (!helpersList) return;
                
                helpersList.innerHTML = '<h3 class="font-bold mb-3">Available Helpers Nearby:</h3>';
                
                if (helpers.length === 0) {
                    helpersList.innerHTML += '<div class="text-gray-500">No helpers available nearby right now.</div>';
                    return;
                }
                
                helpers.forEach(helper => {
                    const helperDiv = document.createElement('div');
                    helperDiv.className = 'helper-card p-4 border rounded-2xl mb-3 shadow-lg hover:shadow-xl transition-all duration-300';
                    helperDiv.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold text-gray-800">${helper.full_name}</div>
                                <div class="text-sm text-gray-600">${helper.skill_tags}</div>
                                <div class="text-sm text-teal-600 font-medium">₹${helper.base_rate}/hr • ${helper.distance.toFixed(1)}km away</div>
                            </div>
                            <button onclick="contactHelper('${helper.full_name}')" class="bg-teal-500 text-white px-4 py-2 rounded-full text-sm hover:bg-teal-600 transition-colors">
                                Contact
                            </button>
                        </div>
                    `;
                    helpersList.appendChild(helperDiv);
                });
            });
        }, function(error) {
            locationPermissionGranted = false;
            showLocationAlert();
        });
    } else {
        showLocationAlert();
    }
}

function loadNearbyTasks() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            locationPermissionGranted = true;
            hideLocationAlert();
            
            fetch(`api/get_tasks.php?lat=${lat}&lng=${lng}`)
            .then(response => response.json())
            .then(data => {
                const tasksList = document.getElementById('tasks-list');
                if (!tasksList) return;
                
                if (data.success && data.tasks.length > 0) {
                    tasksList.innerHTML = '';
                    data.tasks.forEach(task => {
                        const taskDiv = document.createElement('div');
                        taskDiv.className = 'task-card p-4 rounded-2xl mb-3 shadow-lg hover:shadow-xl transition-all duration-300';
                        taskDiv.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <div class="font-bold text-xl text-teal-600">₹${task.agreed_price}</div>
                                        <div class="text-sm text-gray-500">${task.distance.toFixed(1)}km away</div>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-2">by ${task.customer_name}</div>
                                    <div class="text-gray-800 mb-2">${task.description}</div>
                                    <div class="text-xs text-gray-400">${new Date(task.created_at).toLocaleString()}</div>
                                </div>
                                <button onclick="acceptTask(${task.task_id})" class="bg-orange-400 hover:bg-orange-500 text-white px-6 py-3 rounded-full font-medium transition-all duration-300 hover:scale-105 ml-4">
                                    Accept
                                </button>
                            </div>
                        `;
                        tasksList.appendChild(taskDiv);
                    });
                } else {
                    tasksList.innerHTML = '<div class="text-gray-500 text-center py-8"><i class="fas fa-search text-3xl mb-2 opacity-50"></i><div>No tasks available nearby right now.</div></div>';
                }
            });
        }, function(error) {
            locationPermissionGranted = false;
            showLocationAlert();
        });
    } else {
        showLocationAlert();
    }
}

function acceptTask(taskId) {
    if (confirm('Are you sure you want to accept this task?')) {
        fetch('api/accept_task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `task_id=${taskId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Task accepted successfully!');
                loadNearbyTasks();
                loadMyTasks();
            } else {
                alert('Failed to accept task: ' + data.message);
            }
        });
    }
}

function updateLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            locationPermissionGranted = true;
            hideLocationAlert();
            
            fetch('api/update_location.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `lat=${lat}&lng=${lng}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Location updated successfully! You are now visible to customers.', 'success');
                    loadNearbyTasks();
                } else {
                    showToast('Failed to update location: ' + data.message, 'error');
                }
            });
        }, function(error) {
            locationPermissionGranted = false;
            showLocationAlert();
        });
    } else {
        showLocationAlert();
    }
}

function loadMyTasks() {
    fetch('api/my_tasks.php')
    .then(response => response.json())
    .then(data => {
        const myTasksList = document.getElementById('my-tasks-list');
        if (!myTasksList) return;
        
        if (data.success && data.tasks.length > 0) {
            myTasksList.innerHTML = '';
            data.tasks.forEach(task => {
                const taskDiv = document.createElement('div');
                taskDiv.className = 'task-card p-4 rounded-2xl mb-3 shadow-lg';
                
                const statusStyles = {
                    'pending': 'status-pending',
                    'accepted': 'status-accepted',
                    'in_progress': 'status-in_progress',
                    'completed': 'status-completed',
                    'cancelled': 'status-cancelled'
                };
                
                taskDiv.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="font-bold text-lg text-teal-600">₹${task.agreed_price}</span>
                                <span class="text-xs px-3 py-1 rounded-full ${statusStyles[task.status]}">${task.status.toUpperCase()}</span>
                            </div>
                            <div class="text-sm text-gray-500 mb-1">${task.my_role === 'customer' ? 'Posted by you' : 'Accepted by you'}</div>
                            <div class="text-gray-800 mb-2 text-sm">${task.description}</div>
                            <div class="text-xs text-gray-400">
                                ${task.other_person ? `With: ${task.other_person}` : 'No helper assigned yet'} • 
                                ${new Date(task.created_at).toLocaleDateString()}
                            </div>
                        </div>
                    </div>
                `;
                myTasksList.appendChild(taskDiv);
            });
        } else {
            myTasksList.innerHTML = '<div class="text-gray-500 text-center py-4"><i class="fas fa-clipboard-list text-2xl mb-2 opacity-50"></i><div>No tasks yet.</div></div>';
        }
    });
}

function contactHelper(helperName) {
    alert(`Contact feature coming soon! Helper: ${helperName}`);
}

function showLocationAlert() {
    if (!locationCheckAttempted) {
        const alertDiv = document.getElementById('location-alert');
        if (alertDiv) {
            alertDiv.classList.remove('hidden');
        }
    }
}

function hideLocationAlert() {
    const alertDiv = document.getElementById('location-alert');
    if (alertDiv) {
        alertDiv.classList.add('hidden');
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function checkLocationPermission() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                locationPermissionGranted = true;
                locationCheckAttempted = true;
                hideLocationAlert();
            },
            function(error) {
                locationPermissionGranted = false;
                locationCheckAttempted = true;
                if (error.code === error.PERMISSION_DENIED) {
                    showLocationAlert();
                }
            }
        );
    }
}

// Initialize based on current view
document.addEventListener('DOMContentLoaded', function() {
    // Check location permission on load
    checkLocationPermission();
    
    if (document.getElementById('customer-view')) {
        loadNearbyHelpers();
    }
    
    if (document.getElementById('helper-view')) {
        loadNearbyTasks();
        // Auto-update location when in helper mode
        updateLocation();
    }
    
    loadMyTasks();
    
    // Auto-refresh every 30 seconds
    setInterval(() => {
        if (document.getElementById('customer-view')) {
            loadNearbyHelpers();
        }
        if (document.getElementById('helper-view')) {
            loadNearbyTasks();
        }
        loadMyTasks();
    }, 30000);
});