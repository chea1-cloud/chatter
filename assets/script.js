// Small piece of front-end interactivity: a live character counter
// for the chatter message box, used on create.php and edit.php.
document.addEventListener('DOMContentLoaded', function () {
    var textarea = document.getElementById('message');
    var counter = document.getElementById('char-count');
    if (textarea && counter) {
        var max = parseInt(textarea.getAttribute('maxlength'), 10) || 280;

        function update() {
            var remaining = max - textarea.value.length;
            counter.textContent = remaining + ' characters left';
            counter.style.color = remaining < 20 ? '#f87171' : '#94a3b8';
        }

        textarea.addEventListener('input', update);
        update();
    }

    // Chat view: always scroll to the newest (bottom) message on load,
    // like a real messaging app.
    var chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
