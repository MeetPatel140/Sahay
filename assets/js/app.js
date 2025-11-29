let userLocation = null;
let locationPermissionGranted = false;
let locationCheckAttempted = false;

function toggleMode(isHelperMode) {
    try {
        const newMode = isHelperMode ? 'helper' : 'customer';
        fetch('api/set_mode.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `mode=${newMode}`
        })
        .then(r => r.json())
        .then(d => d.success ? location.reload() : (showToast('Failed', 'error'), document.getElementById('mode-toggle').checked = !isHelperMode))
        .catch(e => showToast('Error: ' + e.message, 'error'));
    } catch(e) { console.error(e); }
}

function postTask(event) {
    try {
        event.preventDefault();
        const formData = new FormData(event.target);
        if (userLocation) {
            formData.append('lat', userLocation.lat);
            formData.append('lng', userLocation.lng);
        }
        fetch('api/post_task.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => d.success ? (showToast('Task posted!', 'success'), event.target.reset(), loadNearbyHelpers()) : showToast('Failed: ' + d.message, 'error'))
        .catch(e => showToast('Error: ' + e.message, 'error'));
    } catch(e) { console.error(e); }
}

function loadNearbyHelpers() {
    try {
        if (!navigator.geolocation) { showLocationAlert(); return; }
        navigator.geolocation.getCurrentPosition(function(pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            userLocation = {lat, lng};
            locationPermissionGranted = true;
            hideLocationAlert();
            fetch(`api/get_helpers.php?lat=${lat}&lng=${lng}`)
            .then(r => r.json())
            .then(helpers => {
                const list = document.getElementById('helpers-list');
                if (!list) return;
                if (helpers.length === 0) { list.innerHTML = '<div class="text-gray-400 text-center py-4">No helpers nearby</div>'; return; }
                list.innerHTML = '<h3 class="font-bold mb-3 text-gray-900">Available Helpers</h3>';
                helpers.forEach(h => {
                    const div = document.createElement('div');
                    div.className = 'helper-card';
                    div.innerHTML = `<div class="flex justify-between items-center"><div><div class="font-semibold text-gray-900">${h.full_name}</div><div class="text-sm text-gray-500">${h.skill_tags}</div><div class="text-sm text-teal font-medium mt-1">₹${h.base_rate}/hr • ${h.distance.toFixed(1)}km</div></div><button type="button" class="bg-teal text-white px-4 py-2 rounded-full text-sm" onclick="contactHelper(${h.user_id})"><i class="fas fa-phone"></i></button></div>`;
                    list.appendChild(div);
                });
                document.getElementById('helpers-panel').classList.remove('hidden');
            })
            .catch(e => showToast('Error: ' + e.message, 'error'));
        }, () => { locationPermissionGranted = false; showLocationAlert(); });
    } catch(e) { console.error(e); }
}

function loadNearbyTasks() {
    try {
        if (!navigator.geolocation) { showLocationAlert(); return; }
        navigator.geolocation.getCurrentPosition(function(pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            locationPermissionGranted = true;
            hideLocationAlert();
            fetch(`api/get_tasks.php?lat=${lat}&lng=${lng}`)
            .then(r => r.json())
            .then(d => {
                const list = document.getElementById('tasks-list');
                if (!list) return;
                if (d.success && d.tasks.length > 0) {
                    list.innerHTML = '';
                    d.tasks.forEach(t => {
                        const div = document.createElement('div');
                        div.className = 'task-card';
                        div.innerHTML = `<div class="flex justify-between items-start"><div class="flex-1"><div class="flex items-center gap-2 mb-1"><div class="font-bold text-lg text-teal">₹${t.agreed_price}</div><div class="text-xs text-gray-400">${t.distance.toFixed(1)}km</div></div><div class="text-sm text-gray-600 mb-2">${t.description}</div><div class="text-xs text-gray-400">by ${t.customer_name}</div></div><button type="button" class="bg-orange-500 text-white px-5 py-2 rounded-full text-sm font-medium" onclick="acceptTask(${t.task_id})">Accept</button></div>`;
                        list.appendChild(div);
                    });
                } else {
                    list.innerHTML = '<div class="text-gray-400 text-center py-8"><i class="fas fa-search text-4xl mb-3 opacity-30"></i><div class="text-sm">No tasks nearby</div></div>';
                }
            })
            .catch(e => showToast('Error: ' + e.message, 'error'));
        }, () => { locationPermissionGranted = false; showLocationAlert(); });
    } catch(e) { console.error(e); }
}

function acceptTask(taskId) {
    try {
        fetch('api/accept_task.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `task_id=${taskId}` })
        .then(r => r.json())
        .then(d => d.success ? (showToast('Task accepted!', 'success'), loadNearbyTasks()) : showToast('Failed: ' + d.message, 'error'))
        .catch(e => showToast('Error: ' + e.message, 'error'));
    } catch(e) { console.error(e); }
}

function completeTask(taskId) {
    try {
        fetch('api/complete_task.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `task_id=${taskId}` })
        .then(r => r.json())
        .then(d => d.success ? (showToast('Task completed!', 'success'), loadMyTasks()) : showToast('Failed: ' + d.message, 'error'))
        .catch(e => showToast('Error: ' + e.message, 'error'));
    } catch(e) { console.error(e); }
}

function cancelTask(taskId) {
    try {
        if (!confirm('Cancel this task?')) return;
        fetch('api/cancel_task.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `task_id=${taskId}` })
        .then(r => r.json())
        .then(d => d.success ? (showToast('Task cancelled', 'success'), loadMyTasks()) : showToast('Failed: ' + d.message, 'error'))
        .catch(e => showToast('Error: ' + e.message, 'error'));
    } catch(e) { console.error(e); }
}

function rateHelper(taskId) {
    try {
        const rating = prompt('Rate helper (1-5 stars):');
        if (!rating || rating < 1 || rating > 5) return;
        fetch('api/rate_helper.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `task_id=${taskId}&rating=${rating}` })
        .then(r => r.json())
        .then(d => d.success ? showToast('Rating submitted!', 'success') : showToast('Failed: ' + d.message, 'error'))
        .catch(e => showToast('Error: ' + e.message, 'error'));
    } catch(e) { console.error(e); }
}

function updateLocation() {
    try {
        if (!navigator.geolocation) { showLocationAlert(); return; }
        navigator.geolocation.getCurrentPosition(function(pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            locationPermissionGranted = true;
            hideLocationAlert();
            fetch('api/update_location.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `lat=${lat}&lng=${lng}` })
            .then(r => r.json())
            .then(d => d.success && loadNearbyTasks())
            .catch(e => console.error(e));
        }, () => { locationPermissionGranted = false; showLocationAlert(); });
    } catch(e) { console.error(e); }
}

function loadMyTasks() {
    try {
        fetch('api/my_tasks.php')
        .then(r => r.json())
        .then(d => {
            const list = document.getElementById('my-tasks-list');
            if (!list) return;
            if (d.success && d.tasks.length > 0) {
                list.innerHTML = '';
                d.tasks.forEach(t => {
                    const div = document.createElement('div');
                    div.className = 'task-card';
                    const statusStyles = {'pending': 'status-pending', 'accepted': 'status-accepted', 'in_progress': 'status-in_progress', 'completed': 'status-completed', 'cancelled': 'status-cancelled'};
                    let actions = '';
                    if (t.status === 'in_progress' && t.my_role === 'helper') actions = `<button type="button" class="text-xs bg-green-500 text-white px-3 py-1 rounded-full mt-2" onclick="completeTask(${t.task_id})">Complete</button>`;
                    if (t.status === 'pending' || t.status === 'accepted') actions += `<button type="button" class="text-xs bg-red-500 text-white px-3 py-1 rounded-full mt-2 ml-2" onclick="cancelTask(${t.task_id})">Cancel</button>`;
                    if (t.status === 'completed' && t.my_role === 'customer') actions += `<button type="button" class="text-xs bg-yellow-500 text-white px-3 py-1 rounded-full mt-2 ml-2" onclick="rateHelper(${t.task_id})">Rate</button>`;
                    div.innerHTML = `<div><div class="flex items-center gap-2 mb-2"><span class="font-bold text-teal">₹${t.agreed_price}</span><span class="text-xs px-2 py-1 rounded-full ${statusStyles[t.status]}">${t.status}</span></div><div class="text-sm text-gray-600 mb-1">${t.description}</div><div class="text-xs text-gray-400">${new Date(t.created_at).toLocaleDateString()}</div>${actions}</div>`;
                    list.appendChild(div);
                });
            } else {
                list.innerHTML = '<div class="text-gray-400 text-center py-4">No tasks yet</div>';
            }
        })
        .catch(e => showToast('Error: ' + e.message, 'error'));
    } catch(e) { console.error(e); }
}

function loadNotifications() {
    try {
        fetch('api/get_notifications.php')
        .then(r => r.json())
        .then(d => {
            if (d.success && d.notifications.length > 0) {
                const badge = document.getElementById('notif-badge');
                if (badge) { badge.textContent = d.notifications.length; badge.classList.remove('hidden'); }
            }
        })
        .catch(e => console.error(e));
    } catch(e) { console.error(e); }
}

function contactHelper(helperId) { showToast('Calling helper...', 'info'); }

function openWallet() {
    try {
        const panel = document.getElementById('wallet-panel');
        if (panel) panel.classList.remove('hidden');
    } catch(e) { console.error(e); }
}

function closeWallet() {
    try {
        const panel = document.getElementById('wallet-panel');
        if (panel) panel.classList.add('hidden');
    } catch(e) { console.error(e); }
}

function processAddMoney(event) {
    try {
        event.preventDefault();
        const amount = document.getElementById('add-amount').value;
        fetch('api/add_money.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `amount=${amount}` })
        .then(r => r.json())
        .then(d => d.success ? (showToast(`₹${amount} added!`, 'success'), setTimeout(() => { closeWallet(); location.reload(); }, 1500)) : showToast('Failed: ' + d.message, 'error'))
        .catch(e => showToast('Error: ' + e.message, 'error'));
    } catch(e) { console.error(e); }
}

function openMenu() {
    try {
        const menu = document.getElementById('menu');
        if (menu) menu.classList.remove('hidden');
    } catch(e) { console.error(e); }
}

function closeMenu() {
    try {
        const menu = document.getElementById('menu');
        if (menu) menu.classList.add('hidden');
    } catch(e) { console.error(e); }
}

function openMyTasks() {
    try {
        closeMenu();
        loadMyTasks();
        const panel = document.getElementById('my-tasks-panel');
        if (panel) panel.classList.remove('hidden');
    } catch(e) { console.error(e); }
}

function closeMyTasks() {
    try {
        const panel = document.getElementById('my-tasks-panel');
        if (panel) panel.classList.add('hidden');
    } catch(e) { console.error(e); }
}

function openSettings() {
    try {
        closeMenu();
        const panel = document.getElementById('settings-panel');
        if (panel) panel.classList.remove('hidden');
    } catch(e) { console.error(e); }
}

function closeSettings() {
    try {
        const panel = document.getElementById('settings-panel');
        if (panel) panel.classList.add('hidden');
    } catch(e) { console.error(e); }
}

function openNotifications() {
    try {
        const panel = document.getElementById('notif-panel');
        if (panel) panel.classList.remove('hidden');
    } catch(e) { console.error(e); }
}

function closeNotifications() {
    try {
        const panel = document.getElementById('notif-panel');
        if (panel) panel.classList.add('hidden');
    } catch(e) { console.error(e); }
}

function changeLanguage(lang) {
    try {
        localStorage.setItem('app_language', lang);
        showToast('Language changed', 'success');
        closeSettings();
    } catch(e) { console.error(e); }
}

function startListening() {
    try {
        if (!('webkitSpeechRecognition' in window)) { showToast('Voice not supported', 'error'); return; }
        const recognition = new webkitSpeechRecognition();
        const appLang = localStorage.getItem('app_language') || 'en-IN';
        recognition.lang = appLang;
        recognition.continuous = false;
        recognition.interimResults = false;
        const statusText = document.getElementById('status-text');
        const micBtn = document.querySelector('[onclick="startListening()"]');
        recognition.onstart = () => { if (micBtn) micBtn.classList.add('voice-active'); if (statusText) statusText.textContent = 'Listening...'; };
        recognition.onresult = (e) => { document.getElementById('task_input').value = e.results[0][0].transcript; if (statusText) statusText.textContent = 'Got it!'; };
        recognition.onerror = () => { if (statusText) statusText.textContent = ''; if (micBtn) micBtn.classList.remove('voice-active'); };
        recognition.onend = () => { if (micBtn) micBtn.classList.remove('voice-active'); setTimeout(() => { if (statusText) statusText.textContent = ''; }, 2000); };
        recognition.start();
    } catch(e) { console.error(e); }
}

function showLocationAlert() {
    try {
        const alertDiv = document.getElementById('location-alert');
        if (alertDiv && !locationCheckAttempted) alertDiv.classList.remove('hidden');
    } catch(e) { console.error(e); }
}

function hideLocationAlert() {
    try {
        const alertDiv = document.getElementById('location-alert');
        if (alertDiv) alertDiv.classList.add('hidden');
    } catch(e) { console.error(e); }
}

function showToast(message, type = 'info') {
    try {
        const toast = document.createElement('div');
        toast.className = `fixed top-20 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-full shadow-2xl z-50 text-sm font-medium ${type === 'success' ? 'bg-green-500 text-white' : type === 'error' ? 'bg-red-500 text-white' : 'bg-gray-900 text-white'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 2500);
    } catch(e) { console.error(e); }
}

function checkLocationPermission() {
    try {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(() => { locationPermissionGranted = true; locationCheckAttempted = true; hideLocationAlert(); }, () => { locationPermissionGranted = false; locationCheckAttempted = true; showLocationAlert(); });
    } catch(e) { console.error(e); }
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        checkLocationPermission();
        if (!localStorage.getItem('app_language')) localStorage.setItem('app_language', 'en-IN');
        if (document.getElementById('customer-view')) setTimeout(() => loadNearbyHelpers(), 1000);
        if (document.getElementById('helper-view')) { loadNearbyTasks(); updateLocation(); }
        loadNotifications();
        setInterval(() => { if (document.getElementById('helper-view')) loadNearbyTasks(); loadNotifications(); }, 15000);
    } catch(e) { console.error(e); }
});
