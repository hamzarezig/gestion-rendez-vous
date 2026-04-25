<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Cabinet Médical</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/planning.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <h1>🏥 Cabinet Médical</h1>
        <div class="nav-links">
            <span><?php echo htmlspecialchars($_SESSION['user_prenom']); ?></span>
            <?php if ($role === 'docteur'): ?>
                <a href="doctor_dashboard.php">Planning</a>
            <?php else: ?>
                <a href="dashboard.php">Tableau de bord</a>
            <?php endif; ?>
            <a href="notifications.php">🔔 Notifications</a>
            <a href="../../backend/api/logout.php">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container" style="padding-top:28px;">
    <div style="max-width:760px;margin:0 auto;">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h2>🔔 Mes notifications</h2>
            <button class="btn btn-sm btn-primary" onclick="markAllRead()">Tout marquer comme lu</button>
        </div>

        <div id="notifList"><div class="loading">Chargement...</div></div>

        <hr style="margin:36px 0;">

        <!-- US-17: Preferences -->
        <?php if ($role === 'docteur'): ?>
        <div class="form-container" style="max-width:100%;margin:0;">
            <h3>⚙️ Préférences de notifications</h3>
            <p style="color:#666;margin-bottom:16px;">Choisissez les notifications que vous souhaitez recevoir.</p>
            <div id="prefMsg" class="alert" style="display:none;"></div>
            <div id="prefForm">
                <label class="pref-row"><input type="checkbox" id="pref_email_reservation"> Email lors d'une nouvelle réservation</label>
                <label class="pref-row"><input type="checkbox" id="pref_email_annulation">  Email lors d'une annulation</label>
                <label class="pref-row"><input type="checkbox" id="pref_email_rappel">      Email de rappel 48h avant le RDV</label>
                <label class="pref-row"><input type="checkbox" id="pref_app_reservation">   Notification in-app nouvelle réservation</label>
                <label class="pref-row"><input type="checkbox" id="pref_app_annulation">    Notification in-app annulation</label>
            </div>
            <button class="btn btn-primary" style="margin-top:16px;" onclick="savePrefs()">💾 Enregistrer</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function loadNotifications() {
    const res  = await fetch('../../backend/api/notifications.php?action=list');
    const data = await res.json();
    const list = document.getElementById('notifList');

    if (!data.data || !data.data.length) {
        list.innerHTML = '<p style="color:#aaa;text-align:center;padding:30px;">Aucune notification pour le moment.</p>';
        return;
    }

    const typeIcons = {
        rdv_reservation: '📅', rdv_modification: '✏️', rdv_annulation: '❌',
        rappel_rdv: '⏰', default: '🔔'
    };

    list.innerHTML = data.data.map(n => `
        <div class="notif-item ${n.lu ? 'notif-read' : 'notif-unread'}" onclick="markRead(${n.id}, this)">
            <span class="notif-icon">${typeIcons[n.type] || typeIcons.default}</span>
            <div class="notif-body">
                <p>${n.message}</p>
                <small>${new Date(n.created_at).toLocaleString('fr-FR')}</small>
            </div>
            ${!n.lu ? '<span class="notif-dot"></span>' : ''}
        </div>
    `).join('');
}

async function markRead(id, el) {
    el.classList.remove('notif-unread');
    el.classList.add('notif-read');
    el.querySelector('.notif-dot')?.remove();
    await fetch('../../backend/api/notifications.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'mark_read', id})
    });
}

async function markAllRead() {
    await fetch('../../backend/api/notifications.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'mark_read', id:0})
    });
    loadNotifications();
}

<?php if ($role === 'docteur'): ?>
async function loadPrefs() {
    const res  = await fetch('../../backend/api/notifications.php?action=preferences');
    const data = await res.json();
    const p    = data.data || {};
    Object.keys(p).forEach(k => {
        const el = document.getElementById('pref_' + k);
        if (el) el.checked = !!p[k];
    });
}

async function savePrefs() {
    const keys = ['email_reservation','email_annulation','email_rappel','app_reservation','app_annulation'];
    const prefs = {};
    keys.forEach(k => { prefs[k] = document.getElementById('pref_' + k).checked; });
    const res  = await fetch('../../backend/api/notifications.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'save_preferences', preferences: prefs})
    });
    const data = await res.json();
    const msg  = document.getElementById('prefMsg');
    msg.style.display='block';
    msg.className='alert alert-' + (data.success?'success':'error');
    msg.textContent = data.message;
}
loadPrefs();
<?php endif; ?>

loadNotifications();
</script>
</body>
</html>
