function startListening() {
    if (!('webkitSpeechRecognition' in window)) {
        alert("Voice not supported in this browser. Please use Chrome.");
        return;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.lang = 'hi-IN';
    
    recognition.onstart = function() {
        document.querySelector('button[onclick="startListening()"]').style.backgroundColor = '#dc2626';
        document.querySelector('button[onclick="startListening()"]').textContent = '🎤 Listening...';
    };

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        document.getElementById('task_input').value = transcript;
        
        if(transcript.includes("bijli") || transcript.includes("electrician")) {
            filterHelpers('electrician');
        }
    };

    recognition.onend = function() {
        document.querySelector('button[onclick="startListening()"]').style.backgroundColor = '#ef4444';
        document.querySelector('button[onclick="startListening()"]').textContent = '🎤 Voice Input';
    };

    recognition.start();
}

function filterHelpers(skill) {
    // Filter helpers based on skill - to be implemented
    console.log('Filtering helpers by:', skill);
}