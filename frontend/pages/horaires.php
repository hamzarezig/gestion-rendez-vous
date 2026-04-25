<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'docteur') {
    header('Location: login.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Horaires - Cabinet Médical</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/planning.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <h1>🏥 Cabinet Médical</h1>
        <div class="nav-links">
            <span>Dr. <?php echo htmlspecialchars($_SESSION['user_prenom']); ?></span>
            <a href="doctor_dashboard.php">Planning</a>
            <a href="horaires.php">Horaires</a>
            <a href="../../backend/api/logout.php">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container" style="padding-top:28px;">
    <div class="form-container" style="max-width:700px;">
        <h2>⏰ Mes horaires de travail</h2>
        <p style="color:#666;margin-bottom:20px;">Définissez vos jours et heures de consultation. Les créneaux seront générés automatiquement toutes les 30 minutes.</p>

        <div id="horaireMsg" class="alert" style="display:none;"></div>

        <form id="horaireForm">
            <table class="horaire-table">
                <thead>
                    <tr>
                        <th>Actif</th>
                        <th>Jour</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Pause début</th>
                        <th>Pause fin</th>
                    </tr>
                </thead>
                <tbody id="horaireBody"></tbody>
            </table>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:20px;">💾 Enregistrer mes horaires</button>
        </form>
    </div>
</div>

<script>
const jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];

async function loadHoraires() {
    const res  = await fetch('../../backend/api/get_horaires.php');
    const data = await res.json();
    const existing = {};
    (data.data || []).forEach(h => { existing[h.jour_semaine] = h; });

    const tbody = document.getElementById('horaireBody');
    tbody.innerHTML = '';
    jours.forEach((jour, i) => {
        const dow = i + 1;
        const h   = existing[dow] || {};
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="checkbox" name="actif_${dow}" ${h.actif ? 'checked' : ''} onchange="toggleRow(this,${dow})"></td>
            <td><strong>${jour}</strong></td>
            <td><input type="time" name="debut_${dow}"       value="${h.heure_debut||'09:00'}"  class="time-input" ${h.actif?'':'disabled'}></td>
            <td><input type="time" name="fin_${dow}"         value="${h.heure_fin||'17:00'}"    class="time-input" ${h.actif?'':'disabled'}></td>
            <td><input type="time" name="pause_debut_${dow}" value="${h.pause_debut||'12:00'}"  class="time-input" ${h.actif?'':'disabled'}></td>
            <td><input type="time" name="pause_fin_${dow}"   value="${h.pause_fin||'14:00'}"    class="time-input" ${h.actif?'':'disabled'}></td>
        `;
        tbody.appendChild(row);
    });
}

function toggleRow(cb, dow) {
    const row = cb.closest('tr');
    row.querySelectorAll('input[type="time"]').forEach(inp => inp.disabled = !cb.checked);
}

document.getElementById('horaireForm').addEventListener('submit', async e => {
    e.preventDefault();
    const horaires = [];
    for (let dow=1; dow<=7; dow++) {
        const actif = document.querySelector(`input[name="actif_${dow}"]`).checked;
        horaires.push({
            jour_semaine: dow,
            actif:        actif,
            heure_debut:  document.querySelector(`input[name="debut_${dow}"]`).value,
            heure_fin:    document.querySelector(`input[name="fin_${dow}"]`).value,
            pause_debut:  document.querySelector(`input[name="pause_debut_${dow}"]`).value,
            pause_fin:    document.querySelector(`input[name="pause_fin_${dow}"]`).value
        });
    }
    const res  = await fetch('../../backend/api/save_horaires.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({horaires})
    });
    const data = await res.json();
    const msg  = document.getElementById('horaireMsg');
    msg.style.display='block';
    msg.className = 'alert alert-' + (data.success?'success':'error');
    msg.textContent = data.message;
});

loadHoraires();
</script>
</body>
</html>
