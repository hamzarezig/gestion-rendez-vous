-- Add to your database.sql
CREATE TABLE IF NOT EXISTS jours_bloques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    docteur_id INT NOT NULL,
    date_bloquee DATE NOT NULL,
    demi_journee ENUM('matin','apres_midi','journee_entiere') DEFAULT 'journee_entiere',
    motif VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docteur_id) REFERENCES docteurs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_block (docteur_id, date_bloquee, demi_journee)
);
