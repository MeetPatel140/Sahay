function startListening() {
    if (!('webkitSpeechRecognition' in window)) {
        alert("Voice recognition not supported. Please use Chrome browser.");
        return;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.lang = 'hi-IN'; // Hindi by default
    recognition.continuous = false;
    recognition.interimResults = false;
    
    const button = document.querySelector('button[onclick="startListening()"]');
    const statusText = document.getElementById('status-text');
    
    recognition.onstart = function() {
        button.style.backgroundColor = '#dc2626';
        button.textContent = '🔴 Listening...';
        button.classList.add('voice-active');
        if (statusText) statusText.textContent = 'Listening... Speak now!';
    };

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        document.getElementById('task_input').value = transcript;
        
        // Auto-categorize based on keywords
        const text = transcript.toLowerCase();
        if (text.includes('bijli') || text.includes('electrician') || text.includes('light')) {
            filterHelpers('electrician');
        } else if (text.includes('paani') || text.includes('plumber') || text.includes('pipe')) {
            filterHelpers('plumber');
        } else if (text.includes('safai') || text.includes('cleaning')) {
            filterHelpers('cleaning');
        }
        
        if (statusText) statusText.textContent = 'Voice input captured!';
    };

    recognition.onerror = function(event) {
        console.error('Speech recognition error:', event.error);
        if (statusText) statusText.textContent = 'Voice input failed. Try again.';
        resetButton();
    };

    recognition.onend = function() {
        resetButton();
        setTimeout(() => {
            if (statusText) statusText.textContent = '';
        }, 2000);
    };

    function resetButton() {
        button.style.backgroundColor = '#ef4444';
        button.textContent = '🎤 Voice Input';
        button.classList.remove('voice-active');
    }

    recognition.start();
}

function filterHelpers(skill) {
    const helpersList = document.getElementById('helpers-list');
    if (!helpersList) return;
    
    const helpers = helpersList.querySelectorAll('div[class*="border"]');
    let visibleCount = 0;
    
    helpers.forEach(helper => {
        const skillText = helper.textContent.toLowerCase();
        if (skillText.includes(skill.toLowerCase())) {
            helper.style.display = 'block';
            helper.style.border = '2px solid #10b981';
            visibleCount++;
        } else {
            helper.style.display = 'none';
        }
    });
    
    if (visibleCount === 0) {
        const noResults = document.createElement('div');
        noResults.className = 'text-gray-500 p-4';
        noResults.textContent = `No ${skill} helpers found nearby. Showing all helpers.`;
        helpersList.appendChild(noResults);
        
        // Show all helpers again after 3 seconds
        setTimeout(() => {
            helpers.forEach(helper => {
                helper.style.display = 'block';
                helper.style.border = '1px solid #d1d5db';
            });
            noResults.remove();
        }, 3000);
    }
}

// Language switching function
function switchLanguage(lang) {
    const recognition = window.currentRecognition;
    if (recognition) {
        recognition.lang = lang;
    }
}