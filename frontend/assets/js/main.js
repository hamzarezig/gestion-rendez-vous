// Fonction pour afficher les messages
function showMessage(elementId, message, type) {
    const msgDiv = document.getElementById(elementId);
    if (msgDiv) {
        msgDiv.style.display = 'block';
        msgDiv.className = `alert alert-${type}`;
        msgDiv.innerHTML = message;
        
        // Masquer automatiquement après 5 secondes
        setTimeout(() => {
            msgDiv.style.display = 'none';
        }, 5000);
    }
}

// Fonction pour valider un email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Fonction pour formater une date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// Fonction pour formater l'heure
function formatTime(timeString) {
    return timeString.substring(0, 5);
}

// Gestion du menu mobile
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('show');
        });
    }
});