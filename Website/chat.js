function toggleChat() {
    const window = document.getElementById('chat-window');
    window.style.display = window.style.display === 'flex' ? 'none' : 'flex';
    if (window.style.display === 'flex') {
        fetchMessages();
        document.querySelector('.chat-notification').style.display = 'none';
        setTimeout(() => {
            const container = document.getElementById('chat-messages');
            container.scrollTop = container.scrollHeight;
        }, 100);
    }
}

function fetchMessages() {
    fetch('../Website/chat_handler.php?action=fetch')
        .then(res => res.json())
        .then(data => {
            if (data.messages) {
                renderMessages(data.messages);
            }
        });
}

function renderMessages(messages) {
    const container = document.getElementById('chat-messages');
    container.innerHTML = '';
    messages.forEach(msg => {
        const div = document.createElement('div');
        div.className = `message message-${msg.sender_type}`;
        div.textContent = msg.message;
        container.appendChild(div);
    });
    container.scrollTop = container.scrollHeight;
}

function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;

    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('message', message);

    fetch('../Website/chat_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            fetchMessages();
        }
    });
}

// Auto fetch every 5 seconds if open
setInterval(() => {
    const window = document.getElementById('chat-window');
    if (window && window.style.display === 'flex') {
        fetchMessages();
    }
}, 5000);

// Handle Enter key
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('chat-input');
    if (input) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    }
});
