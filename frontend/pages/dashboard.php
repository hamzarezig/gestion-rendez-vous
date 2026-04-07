<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../../backend/config/database.php';

// Récupérer les rendez-vous du patient
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        u.nom as docteur_nom,
        u.prenom as docteur_prenom,
        d.specialite,
        d.cabinet_adresse,
        d.telephone
    FROM rendezvous r
    JOIN docteurs d ON r.docteur_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE r.patient_id = ?
    ORDER BY r.date_rdv DESC, r.heure_rdv DESC
");
$stmt->execute([$_SESSION['user_id']]);
$rdvs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon tableau de bord - Cabinet Médical</title>
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
        <div class="dashboard">
            <div class="sidebar">
                <h3>Menu</h3>
                <ul>
                    <li><a href="dashboard.php">📊 Mes rendez-vous</a></li>
                    <li><a href="prendre_rdv.php">📅 Prendre rendez-vous</a></li>
                </ul>
            </div>
            
            <div class="main-content">
                <h2>Mes rendez-vous</h2>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Rendez-vous pris avec succès !
                    </div>
                <?php endif; ?>
                
                <?php if (empty($rdvs)): ?>
                    <p>Vous n'avez pas encore de rendez-vous.</p>
                    <a href="prendre_rdv.php" class="btn btn-primary">Prendre mon premier rendez-vous</a>
                <?php else: ?>
                    <?php foreach($rdvs as $rdv): ?>
                        <div class="rdv-card">
                            <h4>Dr. <?php echo $rdv['docteur_prenom'] . ' ' . $rdv['docteur_nom']; ?></h4>
                            <p><strong>Spécialité :</strong> <?php echo $rdv['specialite']; ?></p>
                            <p><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?></p>
                            <p><strong>Heure :</strong> <?php echo substr($rdv['heure_rdv'], 0, 5); ?></p>
                            <p><strong>Motif :</strong> <?php echo $rdv['motif'] ?: 'Non spécifié'; ?></p>
                            <p><strong>Statut :</strong> 
                                <span class="status-<?php echo $rdv['statut']; ?>">
                                    <?php 
                                    $statuts = [
                                        'en_attente' => '⏳ En attente',
                                        'confirme' => '✅ Confirmé',
                                        'annule' => '❌ Annulé',
                                        'termine' => '✔️ Terminé'
                                    ];
                                    echo $statuts[$rdv['statut']];
                                    ?>
                                </span>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>