<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'docteur') {
    header('Location: login.php'); exit();
}
require_once '../../backend/config/database.php';
$stmt = $pdo->prepare("SELECT d.*, u.nom, u.prenom FROM docteurs d JOIN users u ON d.user_id=u.id WHERE u.id=?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Planning - Cabinet Médical</title>
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

<div class="container" style="padding-top:24px;">
    <div class="planning-header">
        <h2>📅 Mon Planning</h2>
        <div class="view-tabs">
            <button class="view-tab active" data-view="semaine">Semaine</button>
            <button class="view-tab" data-view="jour">Jour</button>
            <button class="view-tab" data-view="mois">Mois</button>
        </div>
        <div class="nav-week">
            <button id="prevPeriod">‹ Précédent</button>
            <span id="periodLabel"></span>
            <button id="nextPeriod">Suivant ›</button>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="planning-toolbar">
        <button class="btn btn-primary btn-sm" onclick="openAddRdv()">+ Ajouter un RDV</button>
        <button class="btn btn-sm btn-warning" onclick="openBlockDay()">🚫 Bloquer un jour</button>
    </div>

    <!-- Calendar grid -->
    <div id="calendarContainer" class="calendar-container"></div>

    <!-- Blocked days list -->
    <div class="blocked-section">
        <h3>Jours bloqués</h3>
        <div id="blockedList"></div>
    </div>
</div>

<!-- Modal: Add RDV -->
<div id="addRdvModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:480px;">
        <button class="modal-close" onclick="closeModal('addRdvModal')">✕</button>
        <h3>Ajouter un rendez-vous</h3>
        <div class="form-group"><label>Patient (ID ou email)</label>
            <input type="text" id="addPatientSearch" placeholder="Email du patient...">
            <div id="patientSuggestions" style="font-size:.85rem;color:#888;margin-top:4px;"></div>
            <input type="hidden" id="addPatientId">
        </div>
        <div class="form-row">
            <div class="form-group"><label>Date</label><input type="date" id="addDate"></div>
            <div class="form-group"><label>Heure</label><input type="time" id="addHeure"></div>
        </div>
        <div class="form-group"><label>Motif</label><textarea id="addMotif" rows="2"></textarea></div>
        <div class="form-group"><label>Statut</label>
            <select id="addStatut">
                <option value="confirme">Confirmé</option>
                <option value="en_attente">En attente</option>
            </select>
        </div>
        <div id="addRdvMsg" class="alert" style="display:none;"></div>
        <button class="btn btn-primary" style="width:100%;" onclick="submitAddRdv()">Enregistrer</button>
    </div>
</div>

<!-- Modal: Block day -->
<div id="blockDayModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:420px;">
        <button class="modal-close" onclick="closeModal('blockDayModal')">✕</button>
        <h3>Bloquer un créneau</h3>
        <div class="form-group"><label>Date</label><input type="date" id="blockDate"></div>
        <div class="form-group"><label>Période</label>
            <select id="blockPeriode">
                <option value="journee_entiere">Journée entière</option>
                <option value="matin">Matin seulement</option>
                <option value="apres_midi">Après-midi seulement</option>
            </select>
        </div>
        <div class="form-group"><label>Motif (optionnel)</label><input type="text" id="blockMotif" placeholder="Congé, formation..."></div>
        <div id="blockMsg" class="alert" style="display:none;"></div>
        <button class="btn btn-primary" style="width:100%;" onclick="submitBlock()">Bloquer</button>
    </div>
</div>

<!-- Modal: Edit/view RDV -->
<div id="rdvDetailModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:460px;">
        <button class="modal-close" onclick="closeModal('rdvDetailModal')">✕</button>
        <h3>Détail du rendez-vous</h3>
        <div id="rdvDetailContent"></div>
        <div id="rdvDetailMsg" class="alert" style="display:none;"></div>
        <div style="display:flex;gap:10px;margin-top:14px;">
            <button class="btn btn-primary" style="flex:1;" onclick="submitEditRdv()">💾 Enregistrer</button>
            <button class="btn btn-danger"  style="flex:1;" onclick="cancelRdv()">❌ Annuler le RDV</button>
        </div>
    </div>
</div>

<script>
let currentView = 'semaine';
let currentDate = new Date();
let rdvData = [];
let editingRdvId = null;

const monthNames = ['Janvier','Février','Mars','Avril','Mai','Juin',
                    'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
const dayNames   = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];

const pad = n => String(n).padStart(2,'0');
const fmt = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
const fmtFr = s => { const p=s.split('-'); return `${p[2]}/${p[1]}/${p[0]}`; };

// ── View tabs ─────────────────────────────────────────────────────────────
document.querySelectorAll('.view-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.view-tab').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        currentView = btn.dataset.view;
        renderCalendar();
    });
});

document.getElementById('prevPeriod').addEventListener('click', () => shiftPeriod(-1));
document.getElementById('nextPeriod').addEventListener('click', () => shiftPeriod(1));

function shiftPeriod(dir) {
    if (currentView === 'jour')    currentDate.setDate(currentDate.getDate() + dir);
    if (currentView === 'semaine') currentDate.setDate(currentDate.getDate() + dir * 7);
    if (currentView === 'mois')    currentDate.setMonth(currentDate.getMonth() + dir);
    renderCalendar();
}

// ── Fetch & render ────────────────────────────────────────────────────────
async function renderCalendar() {
    let debut, fin;
    const y = currentDate.getFullYear(), m = currentDate.getMonth(), d = currentDate.getDate();

    if (currentView === 'jour') {
        debut = fin = fmt(currentDate);
        document.getElementById('periodLabel').textContent =
            `${dayNames[(currentDate.getDay()||7)-1]} ${d} ${monthNames[m]} ${y}`;
    } else if (currentView === 'semaine') {
        const dow = (currentDate.getDay() || 7) - 1;
        const mon = new Date(currentDate); mon.setDate(d - dow);
        const sun = new Date(mon); sun.setDate(mon.getDate() + 6);
        debut = fmt(mon); fin = fmt(sun);
        document.getElementById('periodLabel').textContent =
            `${mon.getDate()} ${monthNames[mon.getMonth()]} – ${sun.getDate()} ${monthNames[sun.getMonth()]} ${y}`;
    } else {
        debut = `${y}-${pad(m+1)}-01`;
        fin   = `${y}-${pad(m+1)}-${pad(new Date(y,m+1,0).getDate())}`;
        document.getElementById('periodLabel').textContent = `${monthNames[m]} ${y}`;
    }

    const res  = await fetch(`../../backend/api/get_planning.php?debut=${debut}&fin=${fin}`);
    const data = await res.json();
    rdvData = data.data || [];

    if (currentView === 'jour')    renderDayView(debut);
    else if (currentView === 'semaine') renderWeekView(debut);
    else renderMonthView(y, m);

    loadBlockedDays();
}

function renderDayView(dateStr) {
    const dayRdvs = rdvData.filter(r => r.date_rdv === dateStr);
    let html = `<div class="day-view">`;
    if (!dayRdvs.length) html += '<p class="no-rdv">Aucun rendez-vous ce jour.</p>';
    dayRdvs.forEach(r => { html += rdvCard(r); });
    html += '</div>';
    document.getElementById('calendarContainer').innerHTML = html;
}

function renderWeekView(mondayStr) {
    let html = '<div class="week-view">';
    for (let i=0; i<7; i++) {
        const d = new Date(mondayStr + 'T00:00:00');
        d.setDate(d.getDate() + i);
        const ds = fmt(d);
        const dayRdvs = rdvData.filter(r => r.date_rdv === ds);
        html += `<div class="week-col">
            <div class="week-col-header">${dayNames[i]}<br><small>${d.getDate()}/${d.getMonth()+1}</small></div>
            ${dayRdvs.length ? dayRdvs.map(rdvCard).join('') : '<p class="no-rdv-sm">—</p>'}
        </div>`;
    }
    html += '</div>';
    document.getElementById('calendarContainer').innerHTML = html;
}

function renderMonthView(y, m) {
    const firstDay = new Date(y, m, 1);
    const lastDay  = new Date(y, m+1, 0);
    let startDow = (firstDay.getDay() || 7) - 1;
    let html = '<div class="month-view"><div class="month-grid">';
    dayNames.forEach(d => { html += `<div class="month-head">${d.substring(0,3)}</div>`; });
    for (let i=0; i<startDow; i++) html += '<div class="month-cell empty"></div>';
    for (let d=1; d<=lastDay.getDate(); d++) {
        const ds = `${y}-${pad(m+1)}-${pad(d)}`;
        const dayRdvs = rdvData.filter(r => r.date_rdv === ds);
        html += `<div class="month-cell">
            <span class="month-day-num">${d}</span>
            ${dayRdvs.map(r=>`<div class="month-rdv-dot status-dot-${r.statut}" onclick="openRdvDetail(${r.id})">${r.heure_rdv.substring(0,5)} ${r.patient_prenom}</div>`).join('')}
        </div>`;
    }
    html += '</div></div>';
    document.getElementById('calendarContainer').innerHTML = html;
}

function rdvCard(r) {
    const statusLabels = { en_attente:'⏳ En attente', confirme:'✅ Confirmé', annule:'❌ Annulé', termine:'✔️ Terminé' };
    return `<div class="rdv-card rdv-${r.statut}" onclick="openRdvDetail(${r.id})">
        <strong>${r.heure_rdv.substring(0,5)}</strong> — ${r.patient_prenom} ${r.patient_nom}
        <span class="rdv-status">${statusLabels[r.statut]||r.statut}</span>
        ${r.motif ? `<p class="rdv-motif">${r.motif}</p>` : ''}
    </div>`;
}

// ── Add RDV ───────────────────────────────────────────────────────────────
function openAddRdv() {
    document.getElementById('addRdvMsg').style.display='none';
    document.getElementById('addPatientId').value='';
    document.getElementById('addPatientSearch').value='';
    document.getElementById('addDate').value=fmt(currentDate);
    document.getElementById('addRdvModal').style.display='flex';
}

// Patient search by email
document.getElementById('addPatientSearch').addEventListener('input', async function() {
    const q = this.value.trim();
    if (q.length < 3) { document.getElementById('patientSuggestions').textContent=''; return; }
    const res = await fetch(`../../backend/api/search_patients.php?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    const sug = document.getElementById('patientSuggestions');
    if (data.data && data.data.length) {
        sug.innerHTML = data.data.map(p =>
            `<span class="patient-sug" onclick="selectPatient(${p.id},'${p.prenom} ${p.nom}')">${p.prenom} ${p.nom} (${p.email})</span>`
        ).join(' ');
    } else {
        sug.textContent = 'Aucun patient trouvé';
    }
});

function selectPatient(id, name) {
    document.getElementById('addPatientId').value = id;
    document.getElementById('addPatientSearch').value = name;
    document.getElementById('patientSuggestions').innerHTML='';
}

async function submitAddRdv() {
    const msg = document.getElementById('addRdvMsg');
    const payload = {
        patient_id: parseInt(document.getElementById('addPatientId').value),
        date_rdv:   document.getElementById('addDate').value,
        heure_rdv:  document.getElementById('addHeure').value,
        motif:      document.getElementById('addMotif').value,
        statut:     document.getElementById('addStatut').value
    };
    if (!payload.patient_id || !payload.date_rdv || !payload.heure_rdv) {
        showMsg(msg, 'Remplissez tous les champs obligatoires.', 'error'); return;
    }
    const res  = await fetch('../../backend/api/manage_rdv.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
    });
    const data = await res.json();
    showMsg(msg, data.message, data.success?'success':'error');
    if (data.success) { setTimeout(()=>{ closeModal('addRdvModal'); renderCalendar(); }, 1200); }
}

// ── RDV Detail / Edit / Cancel ────────────────────────────────────────────
function openRdvDetail(id) {
    editingRdvId = id;
    const r = rdvData.find(x => x.id == id);
    if (!r) return;
    document.getElementById('rdvDetailMsg').style.display='none';
    document.getElementById('rdvDetailContent').innerHTML = `
        <p><strong>Patient :</strong> ${r.patient_prenom} ${r.patient_nom} (${r.patient_email})</p>
        <div class="form-row">
            <div class="form-group"><label>Date</label><input type="date" id="editDate" value="${r.date_rdv}"></div>
            <div class="form-group"><label>Heure</label><input type="time" id="editHeure" value="${r.heure_rdv.substring(0,5)}"></div>
        </div>
        <div class="form-group"><label>Motif</label><textarea id="editMotif" rows="2">${r.motif||''}</textarea></div>
        <div class="form-group"><label>Statut</label>
            <select id="editStatut">
                <option value="en_attente" ${r.statut==='en_attente'?'selected':''}>⏳ En attente</option>
                <option value="confirme"   ${r.statut==='confirme'?'selected':''}>✅ Confirmé</option>
                <option value="termine"    ${r.statut==='termine'?'selected':''}>✔️ Terminé</option>
                <option value="annule"     ${r.statut==='annule'?'selected':''}>❌ Annulé</option>
            </select>
        </div>
    `;
    document.getElementById('rdvDetailModal').style.display='flex';
}

async function submitEditRdv() {
    const msg = document.getElementById('rdvDetailMsg');
    const payload = {
        id:        editingRdvId,
        date_rdv:  document.getElementById('editDate').value,
        heure_rdv: document.getElementById('editHeure').value,
        motif:     document.getElementById('editMotif').value,
        statut:    document.getElementById('editStatut').value
    };
    const res  = await fetch('../../backend/api/manage_rdv.php', {
        method:'PUT', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
    });
    const data = await res.json();
    showMsg(msg, data.message, data.success?'success':'error');
    if (data.success) { setTimeout(()=>{ closeModal('rdvDetailModal'); renderCalendar(); }, 1000); }
}

async function cancelRdv() {
    if (!confirm('Confirmer l\'annulation de ce rendez-vous ?')) return;
    const msg = document.getElementById('rdvDetailMsg');
    const res  = await fetch('../../backend/api/manage_rdv.php', {
        method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id: editingRdvId})
    });
    const data = await res.json();
    showMsg(msg, data.message, data.success?'success':'error');
    if (data.success) { setTimeout(()=>{ closeModal('rdvDetailModal'); renderCalendar(); }, 1000); }
}

// ── Block days ────────────────────────────────────────────────────────────
function openBlockDay() {
    document.getElementById('blockMsg').style.display='none';
    document.getElementById('blockDate').value=fmt(currentDate);
    document.getElementById('blockDayModal').style.display='flex';
}

async function submitBlock() {
    const msg = document.getElementById('blockMsg');
    const payload = {
        date:         document.getElementById('blockDate').value,
        demi_journee: document.getElementById('blockPeriode').value,
        motif:        document.getElementById('blockMotif').value
    };
    const res  = await fetch('../../backend/api/jours_bloques.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
    });
    const data = await res.json();
    showMsg(msg, data.message, data.success?'success':'error');
    if (data.success) { setTimeout(()=>{ closeModal('blockDayModal'); loadBlockedDays(); }, 1000); }
}

async function loadBlockedDays() {
    const res  = await fetch('../../backend/api/jours_bloques.php');
    const data = await res.json();
    const list = document.getElementById('blockedList');
    if (!data.data || !data.data.length) { list.innerHTML='<p style="color:#aaa;">Aucun jour bloqué.</p>'; return; }
    const labels = { journee_entiere:'Journée entière', matin:'Matin', apres_midi:'Après-midi' };
    list.innerHTML = data.data.map(b => `
        <div class="blocked-item">
            📅 <strong>${fmtFr(b.date_bloquee)}</strong> — ${labels[b.demi_journee]}
            ${b.motif ? `<em>(${b.motif})</em>` : ''}
            <button class="btn-unblock" onclick="unblockDay(${b.id})">✕ Débloquer</button>
        </div>
    `).join('');
}

async function unblockDay(id) {
    const res  = await fetch('../../backend/api/jours_bloques.php', {
        method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id})
    });
    const data = await res.json();
    if (data.success) loadBlockedDays();
}

// ── Helpers ───────────────────────────────────────────────────────────────
function closeModal(id) { document.getElementById(id).style.display='none'; }
function showMsg(el, msg, type) {
    el.style.display='block'; el.className=`alert alert-${type}`; el.textContent=msg;
}

// Init
renderCalendar();
</script>
</body>
</html>
