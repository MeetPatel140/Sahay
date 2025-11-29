function startListening() {
    if (!('webkitSpeechRecognition' in window)) {
        showToast('Voice not supported. Use Chrome', 'error');
        return;
    }

    const recognition = new webkitSpeechRecognition();
    const appLang = localStorage.getItem('app_language') || 'en-IN';
    recognition.lang = appLang;
    recognition.continuous = false;
    recognition.interimResults = false;
    
    const statusText = document.getElementById('status-text');
    const micBtn = document.querySelector('[onclick="startListening()"]');
    
    recognition.onstart = function() {
        if (micBtn) micBtn.classList.add('voice-active');
        if (statusText) statusText.textContent = 'Listening...';
    };

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        document.getElementById('task_input').value = transcript;
        if (statusText) statusText.textContent = 'Got it!';
    };
    
    recognition.onerror = function(event) {
        if (statusText) statusText.textContent = '';
        if (micBtn) micBtn.classList.remove('voice-active');
    };
    
    recognition.onend = function() {
        if (micBtn) micBtn.classList.remove('voice-active');
        setTimeout(() => {
            if (statusText) statusText.textContent = '';
        }, 2000);
    };

    recognition.start();
}

function changeLanguage(lang) {
    localStorage.setItem('app_language', lang);
    showToast('Language changed', 'success');
    document.getElementById('settings-panel').classList.add('hidden');
}

function openSettings() {
    document.getElementById('settings-panel').classList.remove('hidden');
}

function closeSettings() {
    document.getElementById('settings-panel').classList.add('hidden');
}
