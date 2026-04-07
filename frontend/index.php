<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet Médical - Accueil</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🏥 Cabinet Médical</h1>
            <div class="nav-links">
                <?php
                session_start();
                if (isset($_SESSION['user_id'])): ?>
                    <span>Bonjour <?php echo $_SESSION['user_prenom']; ?></span>
                    <a href="pages/dashboard.php">Mon tableau de bord</a>
                    <a href="../backend/api/logout.php">Déconnexion</a>
                <?php else: ?>
                    <a href="pages/login.php">Connexion</a>
                    <a href="pages/register.php">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="hero">
        <div class="container">
            <h1>Prenez rendez-vous avec votre médecin</h1>
            <p>Simple, rapide et gratuit</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="pages/register.php" class="btn btn-primary">Commencer</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <h2>Nos médecins</h2>
        <div class="doctors-grid" id="doctorsGrid">
            <div class="loading">Chargement des médecins...</div>
        </div>
    </div>

    <script>
        // Charger les médecins
        fetch('../backend/api/get_doctors.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const grid = document.getElementById('doctorsGrid');
                    grid.innerHTML = '';
                    data.data.forEach(doctor => {
                        let buttonHtml = '';
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'patient'): ?>
                        buttonHtml = `<a href="pages/prendre_rdv.php?docteur=${doctor.docteur_id}" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Prendre RDV</a>`;
                        <?php else: ?>
                        buttonHtml = `<a href="pages/login.php" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Connectez-vous pour prendre RDV</a>`;
                        <?php endif; ?>
                        
                        grid.innerHTML += `
                            <div class="doctor-card">
                                <h3>Dr. ${doctor.prenom} ${doctor.nom}</h3>
                                <p><strong>Spécialité :</strong> ${doctor.specialite}</p>
                                <p><strong>Téléphone :</strong> ${doctor.telephone}</p>
                                <p><strong>Adresse :</strong> ${doctor.cabinet_adresse}</p>
                                <p><strong>Prix :</strong> ${doctor.consultation_price} €</p>
                                ${buttonHtml}
                            </div>
                        `;
                    });
                }
            });
    </script>
</body>
</html>