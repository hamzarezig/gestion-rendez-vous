-- ============================================
-- BASE DE DONNÉES POUR CABINET MÉDICAL
-- ============================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gestion_rdv;
USE gestion_rdv;

-- ============================================
-- TABLE DES UTILISATEURS
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'docteur') DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE DES DOCTEURS
-- ============================================
CREATE TABLE docteurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    specialite VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    cabinet_adresse TEXT,
    consultation_price DECIMAL(10,2),
    description TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE DES RENDEZ-VOUS
-- ============================================
CREATE TABLE rendezvous (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    docteur_id INT NOT NULL,
    date_rdv DATE NOT NULL,
    heure_rdv TIME NOT NULL,
    motif TEXT,
    statut ENUM('en_attente', 'confirme', 'annule', 'termine') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (docteur_id) REFERENCES docteurs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_creneau (docteur_id, date_rdv, heure_rdv)
);

-- ============================================
-- TABLE DES HORAIRES DE TRAVAIL (optionnelle)
-- ============================================
CREATE TABLE horaires_travail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    docteur_id INT NOT NULL,
    jour_semaine TINYINT NOT NULL COMMENT '1=Lundi,2=Mardi,3=Mercredi,4=Jeudi,5=Vendredi,6=Samedi,7=Dimanche',
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    pause_debut TIME,
    pause_fin TIME,
    actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (docteur_id) REFERENCES docteurs(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE DES NOTIFICATIONS (optionnelle)
-- ============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    lu BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- INSERTION DES DONNÉES DE TEST
-- ============================================

-- Insérer les docteurs (mot de passe: test123)
INSERT INTO users (nom, prenom, email, password, role) VALUES
('Martin', 'Sophie', 'sophie.martin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'docteur'),
('Bernard', 'Jean', 'jean.bernard@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'docteur');

-- Insérer les informations des docteurs
INSERT INTO docteurs (user_id, specialite, telephone, cabinet_adresse, consultation_price, description) VALUES
(1, 'Généraliste', '06 12 34 56 78', '15 rue de Paris, 75001 Paris', 50.00, 'Médecin généraliste avec 15 ans d''expérience. Consultations pour toute la famille.'),
(2, 'Cardiologue', '06 98 76 54 32', '8 avenue des Champs-Élysées, 75008 Paris', 80.00, 'Spécialiste en cardiologie. Prend en charge les pathologies cardiaques.');

-- Insérer un patient de test (mot de passe: test123)
INSERT INTO users (nom, prenom, email, password, role) VALUES
('Dupont', 'Marie', 'marie.dupont@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient');

-- Insérer un rendez-vous de test
INSERT INTO rendezvous (patient_id, docteur_id, date_rdv, heure_rdv, motif, statut) VALUES
(3, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Consultation de routine', 'confirme');

-- Insérer les horaires de travail pour le Dr Martin
INSERT INTO horaires_travail (docteur_id, jour_semaine, heure_debut, heure_fin, pause_debut, pause_fin) VALUES
(1, 1, '09:00:00', '17:00:00', '12:00:00', '14:00:00'),
(1, 2, '09:00:00', '17:00:00', '12:00:00', '14:00:00'),
(1, 3, '09:00:00', '17:00:00', '12:00:00', '14:00:00'),
(1, 4, '09:00:00', '17:00:00', '12:00:00', '14:00:00'),
(1, 5, '09:00:00', '16:00:00', '12:00:00', '14:00:00');

-- ============================================
-- AFFICHAGE DES DONNÉES POUR VÉRIFICATION
-- ============================================

SELECT '=== UTILISATEURS ===' AS '';
SELECT id, nom, prenom, email, role FROM users;

SELECT '=== DOCTEURS ===' AS '';
SELECT d.id, u.prenom, u.nom, d.specialite, d.telephone, d.consultation_price 
FROM docteurs d 
JOIN users u ON d.user_id = u.id;

SELECT '=== RENDEZ-VOUS ===' AS '';
SELECT r.id, 
       CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient,
       CONCAT(u_docteur.prenom, ' ', u_docteur.nom) AS docteur,
       r.date_rdv, r.heure_rdv, r.statut
FROM rendezvous r
JOIN users u_patient ON r.patient_id = u_patient.id
JOIN docteurs d ON r.docteur_id = d.id
JOIN users u_docteur ON d.user_id = u_docteur.id;