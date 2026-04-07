<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$docteur_id = $_GET['docteur'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre rendez-vous - Cabinet Médical</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🏥 Cabinet Médical</h1>
            <div class="nav-links">
                <span>Bonjour <?php echo $_SESSION['user_prenom']; ?></span>
                <a href="dashboard.php">Tableau de bord</a>
                <a href="prendre_rdv.php">Prendre RDV</a>
                <a href="../../backend/api/logout.php">Déconnexion</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h2>Prendre rendez-vous</h2>
            
            <div id="message" style="display:none;" class="alert"></div>
            
            <form id="rdvForm">
                <div class="form-group">
                    <label>Médecin :</label>
                    <select id="docteur_id" required>
                        <option value="">Choisissez un médecin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Date :</label>
                    <input type="date" id="date_rdv" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Heure :</label>
                    <input type="time" id="heure_rdv" required>
                </div>
                
                <div class="form-group">
                    <label>Motif (optionnel) :</label>
                    <textarea id="motif" rows="3" placeholder="Décrivez brièvement le motif de votre consultation..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Confirmer le rendez-vous</button>
            </form>
        </div>
    </div>
    
    <script>
        // Charger la liste des médecins
        fetch('../../backend/api/get_doctors.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('docteur_id');
                    data.data.forEach(doctor => {
                        const option = document.createElement('option');
                        option.value = doctor.docteur_id;
                        option.textContent = `Dr. ${doctor.prenom} ${doctor.nom} - ${doctor.specialite} (${doctor.consultation_price}€)`;
                        select.appendChild(option);
                    });
                    
                    // Pré-sélectionner si un docteur est passé en paramètre
                    <?php if ($docteur_id): ?>
                    select.value = <?php echo $docteur_id; ?>;
                    <?php endif; ?>
                }
            });
        
        // Soumettre le formulaire
        document.getElementById('rdvForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const data = {
                docteur_id: document.getElementById('docteur_id').value,
                date_rdv: document.getElementById('date_rdv').value,
                heure_rdv: document.getElementById('heure_rdv').value,
                motif: document.getElementById('motif').value
            };
            
            const response = await fetch('../../backend/api/book_appointment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            const msgDiv = document.getElementById('message');
            msgDiv.style.display = 'block';
            
            if (result.success) {
                msgDiv.className = 'alert alert-success';
                msgDiv.innerHTML = result.message + ' Redirection...';
                setTimeout(() => {
                    window.location.href = 'dashboard.php?success=1';
                }, 2000);
            } else {
                msgDiv.className = 'alert alert-error';
                msgDiv.innerHTML = result.message;
            }
        });
    </script>
</body>
</html>