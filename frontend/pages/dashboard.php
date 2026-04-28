<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../../backend/config/database.php';

$stmt = $pdo->prepare("
    SELECT r.*, u.nom AS docteur_nom, u.prenom AS docteur_prenom, d.specialite, d.cabinet_adresse, d.telephone
    FROM rendezvous r
    JOIN docteurs d ON r.docteur_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE r.patient_id = ?
    ORDER BY r.date_rdv DESC, r.heure_rdv DESC
");
$stmt->execute([$_SESSION['user_id']]);
$rdvs = $stmt->fetchAll();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon tableau de bord - Cabinet Médical</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/planning.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <h1>🏥 Cabinet Médical</h1>
        <div class="nav-links">
            <span>Bonjour <?php echo htmlspecialchars($_SESSION['user_prenom']); ?></span>
            <a href="dashboard.php">Tableau de bord</a>
            <a href="recherche_rdv.php">Prendre RDV</a>
            <?php if ($role === 'docteur'): ?>
                <a href="doctor_dashboard.php">Planning</a>
            <?php endif; ?>
            <a href="notifications.php">🔔 Notifications</a>

            <a href="../../backend/api/logout.php">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="dashboard">
        <div class="sidebar">
            <h3>Menu</h3>
            <ul>
                <li><a href="dashboard.php">📊 Mes rendez-vous</a></li>
                <li><a href="recherche_rdv.php">📅 Prendre rendez-vous</a></li>
                <li><a href="notifications.php">🔔 Notifications</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h2>Mes rendez-vous</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Rendez-vous pris avec succès !</div>
            <?php endif; ?>

            <?php if (empty($rdvs)): ?>
                <p>Vous n'avez pas encore de rendez-vous.</p>
                <a href="recherche_rdv.php" class="btn btn-primary">Prendre mon premier rendez-vous</a>
            <?php else: ?>
                <?php foreach($rdvs as $rdv): ?>
                    <?php
                    $statut = $rdv['statut'];
                    $isFuture = strtotime($rdv['date_rdv']) >= strtotime(date('Y-m-d'));
                    $canModify = $isFuture && $statut !== 'annule' && $statut !== 'termine';
                    ?>
                    <div class="rdv-card" id="rdv-<?php echo $rdv['id']; ?>">
                        <h4>Dr. <?php echo htmlspecialchars($rdv['docteur_prenom'] . ' ' . $rdv['docteur_nom']); ?></h4>
                        <p><strong>Spécialité :</strong> <?php echo htmlspecialchars($rdv['specialite']); ?></p>
                        <p><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?></p>
                        <p><strong>Heure :</strong> <?php echo substr($rdv['heure_rdv'], 0, 5); ?></p>
                        <p><strong>Motif :</strong> <?php echo htmlspecialchars($rdv['motif'] ?: 'Non spécifié'); ?></p>
                        <p><strong>Statut :</strong>
                            <span class="status-<?php echo $statut; ?>">
                                <?php $labels=['en_attente'=>'⏳ En attente','confirme'=>'✅ Confirmé','annule'=>'❌ Annulé','termine'=>'✔️ Terminé'];
                                      echo $labels[$statut] ?? $statut; ?>
                            </span>
                        </p>
                        <?php if ($canModify): ?>
                        <div style="display:flex;gap:10px;margin-top:10px;">
                            <button class="btn btn-primary btn-sm"
                                onclick="openEdit(<?php echo $rdv['id']; ?>,'<?php echo $rdv['date_rdv']; ?>','<?php echo substr($rdv['heure_rdv'],0,5); ?>','<?php echo addslashes($rdv['motif']); ?>')">
                                ✏️ Modifier
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="cancelRdv(<?php echo $rdv['id']; ?>)">
                                ❌ Annuler
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:460px;">
        <button class="modal-close" onclick="document.getElementById('editModal').style.display='none'">✕</button>
        <h3>Modifier le rendez-vous</h3>
        <input type="hidden" id="editId">

        <div class="form-group"><label>Nouvelle date</label>
            <input type="date" id="editDate" min="<?php echo date('Y-m-d'); ?>">
        </div>

        <div id="editSlotsSection" style="display:none;">
            <label>Créneaux disponibles</label>
            <div id="editSlotsGrid" class="slots-grid" style="margin-bottom:14px;"></div>
            <input type="hidden" id="editHeure">
        </div>

        <div class="form-group"><label>Motif</label>
            <textarea id="editMotif" rows="2"></textarea>
        </div>

        <div id="editMsg" class="alert" style="display:none;"></div>
        <button class="btn btn-primary" style="width:100%;" onclick="submitEdit()">💾 Enregistrer</button>
    </div>
</div>

<script>
let editDocteurId = null;

async function openEdit(id, date, heure, motif) {
    document.getElementById('editId').value    = id;
    document.getElementById('editDate').value  = date;
    document.getElementById('editMotif').value = motif;
    document.getElementById('editMsg').style.display='none';
    document.getElementById('editSlotsSection').style.display='none';
    document.getElementById('editHeure').value = heure;

    // Find docteur_id from the page data
    const res  = await fetch(`../../backend/api/get_rdv_detail.php?id=${id}`);
    const data = await res.json();
    if (data.success) editDocteurId = data.data.docteur_id;

    document.getElementById('editModal').style.display='flex';
}

document.getElementById('editDate').addEventListener('change', async function() {
    if (!editDocteurId || !this.value) return;
    const res  = await fetch(`../../backend/api/get_slots.php?docteur_id=${editDocteurId}&date=${this.value}`);
    const data = await res.json();
    const grid = document.getElementById('editSlotsGrid');
    const section = document.getElementById('editSlotsSection');

    if (!data.creneaux || !data.creneaux.length) {
        section.style.display='none'; return;
    }
    section.style.display='block';
    grid.innerHTML='';
    document.getElementById('editHeure').value='';
    data.creneaux.forEach(slot => {
        const btn = document.createElement('button');
        btn.className = 'slot-btn ' + (slot.disponible ? 'slot-free' : 'slot-taken');
        btn.textContent = slot.heure;
        btn.disabled = !slot.disponible;
        if (slot.disponible) btn.addEventListener('click', () => {
            document.querySelectorAll('#editSlotsGrid .slot-btn').forEach(b=>b.classList.remove('slot-selected'));
            btn.classList.add('slot-selected');
            document.getElementById('editHeure').value = slot.heure;
        });
        grid.appendChild(btn);
    });
});

async function submitEdit() {
    const msg = document.getElementById('editMsg');
    const payload = {
        id:        parseInt(document.getElementById('editId').value),
        date_rdv:  document.getElementById('editDate').value,
        heure_rdv: document.getElementById('editHeure').value,
        motif:     document.getElementById('editMotif').value
    };
    if (!payload.heure_rdv) { showMsg(msg,'Sélectionnez un créneau.','error'); return; }
    const res  = await fetch('../../backend/api/manage_rdv.php', {
        method:'PUT', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
    });
    const data = await res.json();
    showMsg(msg, data.message, data.success?'success':'error');
    if (data.success) setTimeout(()=>location.reload(), 1200);
}

async function cancelRdv(id) {
    if (!confirm('Confirmer l\'annulation de ce rendez-vous ?')) return;
    const res  = await fetch('../../backend/api/manage_rdv.php', {
        method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id})
    });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.message);
}

function showMsg(el, msg, type) {
    el.style.display='block'; el.className=`alert alert-${type}`; el.textContent=msg;
}
</script>
</body>
</html>
