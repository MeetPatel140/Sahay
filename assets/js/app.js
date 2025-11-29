let userLocation = null;

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
                    helperDiv.className = 'p-4 border rounded-lg mb-2 hover:bg-gray-50 cursor-pointer';
                    helperDiv.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold">${helper.full_name}</div>
                                <div class="text-sm text-gray-600">${helper.skill_tags}</div>
                                <div class="text-sm text-blue-600">₹${helper.base_rate}/hr • ${helper.distance.toFixed(1)}km away</div>
                            </div>
                            <button onclick="contactHelper('${helper.full_name}')" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                Contact
                            </button>
                        </div>
                    `;
                    helpersList.appendChild(helperDiv);
                });
            });
        });
    }
}

function loadNearbyTasks() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            fetch(`api/get_tasks.php?lat=${lat}&lng=${lng}`)
            .then(response => response.json())
            .then(data => {
                const tasksList = document.getElementById('tasks-list');
                if (!tasksList) return;
                
                if (data.success && data.tasks.length > 0) {
                    tasksList.innerHTML = '';
                    data.tasks.forEach(task => {
                        const taskDiv = document.createElement('div');
                        taskDiv.className = 'p-4 border rounded-lg mb-3 hover:bg-gray-50';
                        taskDiv.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="font-semibold text-green-600">₹${task.agreed_price}</div>
                                    <div class="text-sm text-gray-600 mb-2">by ${task.customer_name} • ${task.distance.toFixed(1)}km away</div>
                                    <div class="text-gray-800">${task.description}</div>
                                    <div class="text-xs text-gray-500 mt-2">${new Date(task.created_at).toLocaleString()}</div>
                                </div>
                                <button onclick="acceptTask(${task.task_id})" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-4">
                                    Accept Task
                                </button>
                            </div>
                        `;
                        tasksList.appendChild(taskDiv);
                    });
                } else {
                    tasksList.innerHTML = '<div class="text-gray-500">No tasks available nearby right now.</div>';
                }
            });
        });
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
            
            fetch('api/update_location.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `lat=${lat}&lng=${lng}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Location updated successfully! You are now visible to customers.');
                    loadNearbyTasks();
                } else {
                    alert('Failed to update location: ' + data.message);
                }
            });
        }, function(error) {
            alert('Please enable location access to use helper mode.');
        });
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
                taskDiv.className = 'p-4 border rounded-lg mb-2';
                
                const statusColor = {
                    'pending': 'text-yellow-600',
                    'accepted': 'text-blue-600',
                    'in_progress': 'text-purple-600',
                    'completed': 'text-green-600',
                    'cancelled': 'text-red-600'
                };
                
                taskDiv.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="font-semibold">₹${task.agreed_price}</span>
                                <span class="text-sm px-2 py-1 rounded ${statusColor[task.status]} bg-gray-100">${task.status.toUpperCase()}</span>
                                <span class="text-sm text-gray-500">${task.my_role === 'customer' ? 'Posted by you' : 'Accepted by you'}</span>
                            </div>
                            <div class="text-gray-800 mb-2">${task.description}</div>
                            <div class="text-sm text-gray-600">
                                ${task.other_person ? `With: ${task.other_person}` : 'No helper assigned yet'} • 
                                ${new Date(task.created_at).toLocaleDateString()}
                            </div>
                        </div>
                    </div>
                `;
                myTasksList.appendChild(taskDiv);
            });
        } else {
            myTasksList.innerHTML = '<div class="text-gray-500">No tasks yet.</div>';
        }
    });
}

function contactHelper(helperName) {
    alert(`Contact feature coming soon! Helper: ${helperName}`);
}

// Initialize based on current view
document.addEventListener('DOMContentLoaded', function() {
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