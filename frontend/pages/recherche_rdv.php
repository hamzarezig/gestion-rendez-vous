<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher un médecin - Cabinet Médical</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/search.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🏥 Cabinet Médical</h1>
            <div class="nav-links">
                <?php
                session_start();
                if (isset($_SESSION['user_id'])): ?>
                    <span>Bonjour <?php echo htmlspecialchars($_SESSION['user_prenom']); ?></span>
                    <a href="dashboard.php">Mon tableau de bord</a>
                    <a href="../../backend/api/logout.php">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php">Connexion</a>
                    <a href="register.php">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container" style="padding-top: 30px;">

        <!-- ── F2 : Search bar ── -->
        <section class="search-section">
            <h2>Trouver un médecin</h2>
            <p class="search-subtitle">Recherchez par nom, spécialité ou localisation</p>

            <div class="search-bar-wrapper">
                <input type="text" id="searchInput" class="search-input"
                       placeholder="🔍  Nom du médecin, spécialité...">
                <select id="specialiteFilter" class="search-select">
                    <option value="">Toutes les spécialités</option>
                </select>
                <input type="text" id="localisationFilter" class="search-input search-input-sm"
                       placeholder="📍  Ville, adresse...">
                <button id="searchBtn" class="btn btn-primary">Rechercher</button>
            </div>
        </section>

        <!-- ── F2 : Results ── -->
        <section id="resultsSection">
            <div id="resultCount" class="result-count"></div>
            <div id="doctorsGrid" class="doctors-grid">
                <div class="loading">Chargement des médecins...</div>
            </div>
        </section>

        <!-- ── F3 : Booking modal ── -->
        <div id="bookingModal" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <button class="modal-close" id="closeModal">✕</button>

                <div id="modalDoctorInfo" class="modal-doctor-info"></div>

                <div class="booking-layout">
                    <!-- Calendar column -->
                    <div class="calendar-column">
                        <h4>Choisissez une date</h4>
                        <div class="mini-calendar" id="miniCalendar"></div>
                    </div>

                    <!-- Slots column -->
                    <div class="slots-column">
                        <h4 id="slotsTitle">Créneaux disponibles</h4>
                        <div id="slotsGrid" class="slots-grid">
                            <p class="slots-hint">← Sélectionnez une date</p>
                        </div>
                    </div>
                </div>

                <!-- Guest / confirmation form -->
                <div id="bookingForm" style="display:none;">
                    <hr style="margin: 20px 0;">
                    <h4>Vos informations</h4>
                    <div id="guestFields">
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Prénom *</label>
                                <input type="text" id="guestPrenom" placeholder="Marie">
                            </div>
                            <div class="form-group">
                                <label>Nom *</label>
                                <input type="text" id="guestNom" placeholder="Dupont">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" id="guestEmail" placeholder="marie@example.com">
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" id="guestTel" placeholder="06 12 34 56 78">
                            </div>
                        </div>
                        <?php else: ?>
                        <p style="color:#27ae60;">✅ Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Motif de consultation (optionnel)</label>
                        <textarea id="motifInput" rows="2" placeholder="Décrivez brièvement votre motif..."></textarea>
                    </div>

                    <div id="bookingMessage" class="alert" style="display:none;"></div>

                    <div class="booking-summary" id="bookingSummary"></div>

                    <button id="confirmBtn" class="btn btn-primary" style="width:100%; margin-top: 15px;">
                        ✅ Confirmer le rendez-vous
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ── State ──────────────────────────────────────────────────────────────
    let selectedDoctor = null;
    let selectedDate   = null;
    let selectedSlot   = null;
    let calYear, calMonth;

    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

    // ── DOM refs ───────────────────────────────────────────────────────────
    const modal        = document.getElementById('bookingModal');
    const doctorsGrid  = document.getElementById('doctorsGrid');
    const slotsGrid    = document.getElementById('slotsGrid');
    const slotsTitle   = document.getElementById('slotsTitle');
    const bookingForm  = document.getElementById('bookingForm');
    const bookingMsg   = document.getElementById('bookingMessage');
    const confirmBtn   = document.getElementById('confirmBtn');

    // ── Helpers ────────────────────────────────────────────────────────────
    const pad = n => String(n).padStart(2, '0');
    const fmt = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    const fmtFr = d => `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()}`;

    // ── F2 : Load & search doctors ─────────────────────────────────────────
    async function loadDoctors(q='', specialite='', localisation='') {
        doctorsGrid.innerHTML = '<div class="loading">Chargement...</div>';
        const params = new URLSearchParams({ q, specialite, localisation });
        const res  = await fetch(`../../backend/api/search_doctors.php?${params}`);
        const data = await res.json();

        // Populate specialties dropdown once
        const sel = document.getElementById('specialiteFilter');
        if (sel.options.length === 1 && data.specialites) {
            data.specialites.forEach(sp => {
                const opt = document.createElement('option');
                opt.value = sp; opt.textContent = sp;
                sel.appendChild(opt);
            });
        }

        document.getElementById('resultCount').textContent =
            data.total === 0 ? 'Aucun médecin trouvé.' :
            `${data.total} médecin${data.total > 1 ? 's' : ''} trouvé${data.total > 1 ? 's' : ''}`;

        if (!data.data || data.data.length === 0) {
            doctorsGrid.innerHTML = '<p class="no-result">Aucun médecin ne correspond à votre recherche.</p>';
            return;
        }

        doctorsGrid.innerHTML = '';
        data.data.forEach(doc => {
            const card = document.createElement('div');
            card.className = 'doctor-card';
            card.innerHTML = `
                <div class="doctor-avatar">${doc.prenom[0]}${doc.nom[0]}</div>
                <h3>Dr. ${doc.prenom} ${doc.nom}</h3>
                <span class="specialty-badge">${doc.specialite}</span>
                <p>📍 ${doc.cabinet_adresse || 'Non renseignée'}</p>
                <p>📞 ${doc.telephone || 'Non renseigné'}</p>
                <p class="price-tag">💶 ${doc.consultation_price} €</p>
                ${doc.description ? `<p class="doc-desc">${doc.description}</p>` : ''}
                <button class="btn btn-primary btn-full" onclick='openBooking(${JSON.stringify(doc)})'>
                    📅 Prendre rendez-vous
                </button>
            `;
            doctorsGrid.appendChild(card);
        });
    }

    // Search on button click or Enter
    document.getElementById('searchBtn').addEventListener('click', triggerSearch);
    document.getElementById('searchInput').addEventListener('keydown', e => { if(e.key==='Enter') triggerSearch(); });

    function triggerSearch() {
        loadDoctors(
            document.getElementById('searchInput').value.trim(),
            document.getElementById('specialiteFilter').value,
            document.getElementById('localisationFilter').value.trim()
        );
    }

    // ── F3 : Open booking modal ────────────────────────────────────────────
    function openBooking(doc) {
        selectedDoctor = doc;
        selectedDate   = null;
        selectedSlot   = null;
        bookingForm.style.display = 'none';
        bookingMsg.style.display  = 'none';

        document.getElementById('modalDoctorInfo').innerHTML = `
            <div class="modal-doc-header">
                <div class="doctor-avatar">${doc.prenom[0]}${doc.nom[0]}</div>
                <div>
                    <h3>Dr. ${doc.prenom} ${doc.nom}</h3>
                    <span class="specialty-badge">${doc.specialite}</span>
                    <p>📍 ${doc.cabinet_adresse || ''} &nbsp;|&nbsp; 💶 ${doc.consultation_price} €</p>
                </div>
            </div>
        `;

        const now = new Date();
        calYear  = now.getFullYear();
        calMonth = now.getMonth();
        renderCalendar();

        slotsGrid.innerHTML = '<p class="slots-hint">← Sélectionnez une date</p>';
        slotsTitle.textContent = 'Créneaux disponibles';

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    document.getElementById('closeModal').addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if(e.target === modal) closeModal(); });

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // ── Mini Calendar ──────────────────────────────────────────────────────
    function renderCalendar() {
        const cal = document.getElementById('miniCalendar');
        const today = new Date(); today.setHours(0,0,0,0);
        const firstDay = new Date(calYear, calMonth, 1);
        const lastDay  = new Date(calYear, calMonth+1, 0);

        const monthNames = ['Janvier','Février','Mars','Avril','Mai','Juin',
                            'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        const dayNames   = ['Lu','Ma','Me','Je','Ve','Sa','Di'];

        let html = `
            <div class="cal-nav">
                <button onclick="shiftMonth(-1)">‹</button>
                <span>${monthNames[calMonth]} ${calYear}</span>
                <button onclick="shiftMonth(1)">›</button>
            </div>
            <div class="cal-grid">
                ${dayNames.map(d=>`<div class="cal-head">${d}</div>`).join('')}
        `;

        // First day of month: getDay() 0=Sun → map to Mon-based
        let startDow = firstDay.getDay(); // 0=Sun
        startDow = startDow === 0 ? 6 : startDow - 1; // 0=Mon

        for(let i=0; i<startDow; i++) html += `<div></div>`;

        for(let d=1; d<=lastDay.getDate(); d++) {
            const date = new Date(calYear, calMonth, d);
            const dateStr = fmt(date);
            const isPast  = date < today;
            const isSel   = dateStr === selectedDate;
            const dow     = date.getDay(); // 0=Sun,6=Sat
            const isWeekend = dow === 0 || dow === 6;

            let cls = 'cal-day';
            if (isPast || isWeekend) cls += ' cal-disabled';
            else if (isSel) cls += ' cal-selected';
            else cls += ' cal-available';

            const click = (!isPast && !isWeekend) ? `onclick="selectDate('${dateStr}')"` : '';
            html += `<div class="${cls}" ${click}>${d}</div>`;
        }

        html += '</div>';
        cal.innerHTML = html;
    }

    function shiftMonth(dir) {
        calMonth += dir;
        if (calMonth < 0)  { calMonth = 11; calYear--; }
        if (calMonth > 11) { calMonth = 0;  calYear++; }
        renderCalendar();
    }

    // ── Slot loading ───────────────────────────────────────────────────────
    async function selectDate(dateStr) {
        selectedDate = dateStr;
        selectedSlot = null;
        bookingForm.style.display = 'none';
        renderCalendar(); // re-render to show selection

        const d = new Date(dateStr + 'T00:00:00');
        slotsTitle.textContent = `Créneaux du ${fmtFr(d)}`;
        slotsGrid.innerHTML = '<div class="loading" style="padding:20px;">Chargement...</div>';

        const res  = await fetch(`../../backend/api/get_slots.php?docteur_id=${selectedDoctor.docteur_id}&date=${dateStr}`);
        const data = await res.json();

        if (!data.success || !data.creneaux || data.creneaux.length === 0) {
            slotsGrid.innerHTML = `<p class="slots-hint">${data.message || 'Pas de créneaux disponibles ce jour.'}</p>`;
            return;
        }

        slotsGrid.innerHTML = '';
        data.creneaux.forEach(slot => {
            const btn = document.createElement('button');
            btn.className = 'slot-btn ' + (slot.disponible ? 'slot-free' : 'slot-taken');
            btn.textContent = slot.heure;
            btn.disabled = !slot.disponible;
            if (slot.disponible) {
                btn.addEventListener('click', () => selectSlot(slot.heure, btn));
            }
            slotsGrid.appendChild(btn);
        });
    }

    function selectSlot(heure, btnEl) {
        document.querySelectorAll('.slot-btn.slot-selected').forEach(b => b.classList.remove('slot-selected'));
        btnEl.classList.add('slot-selected');
        selectedSlot = heure;

        const d = new Date(selectedDate + 'T00:00:00');
        document.getElementById('bookingSummary').innerHTML = `
            <div class="summary-box">
                📋 <strong>Récapitulatif :</strong> Dr. ${selectedDoctor.prenom} ${selectedDoctor.nom}
                — ${fmtFr(d)} à <strong>${heure}</strong>
            </div>
        `;
        bookingForm.style.display = 'block';
        bookingForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ── Confirm booking ────────────────────────────────────────────────────
    confirmBtn.addEventListener('click', async () => {
        if (!selectedDoctor || !selectedDate || !selectedSlot) {
            showBookingMsg('Veuillez sélectionner une date et un créneau.', 'error');
            return;
        }

        const payload = {
            docteur_id: selectedDoctor.docteur_id,
            date_rdv:   selectedDate,
            heure_rdv:  selectedSlot,
            motif:      document.getElementById('motifInput').value.trim()
        };

        if (!isLoggedIn) {
            payload.guest_prenom = document.getElementById('guestPrenom').value.trim();
            payload.guest_nom    = document.getElementById('guestNom').value.trim();
            payload.guest_email  = document.getElementById('guestEmail').value.trim();
            payload.guest_tel    = document.getElementById('guestTel').value.trim();

            if (!payload.guest_prenom || !payload.guest_nom || !payload.guest_email) {
                showBookingMsg('Veuillez remplir votre prénom, nom et email.', 'error');
                return;
            }
        }

        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Confirmation en cours...';

        const res  = await fetch('../../backend/api/book_appointment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        confirmBtn.disabled = false;
        confirmBtn.textContent = '✅ Confirmer le rendez-vous';

        if (data.success) {
            showBookingMsg('🎉 ' + data.message, 'success');
            confirmBtn.style.display = 'none';
            // Refresh slot display
            selectDate(selectedDate);
        } else {
            showBookingMsg(data.message, 'error');
        }
    });

    function showBookingMsg(msg, type) {
        bookingMsg.style.display = 'block';
        bookingMsg.className = 'alert alert-' + type;
        bookingMsg.textContent = msg;
    }

    // ── Init ───────────────────────────────────────────────────────────────
    loadDoctors();
    </script>
</body>
</html>
