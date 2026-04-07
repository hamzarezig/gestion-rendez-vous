<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Cabinet Médical</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        function toggleDoctorFields() {
            const role = document.getElementById('role').value;
            const doctorFields = document.getElementById('doctor-fields');
            doctorFields.style.display = role === 'docteur' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h2>Inscription</h2>
        
        <div id="message" style="display:none;" class="alert"></div>
        
        <form id="registerForm">
            <div class="form-group">
                <label>Nom :</label>
                <input type="text" id="nom" required>
            </div>
            
            <div class="form-group">
                <label>Prénom :</label>
                <input type="text" id="prenom" required>
            </div>
            
            <div class="form-group">
                <label>Email :</label>
                <input type="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label>Mot de passe :</label>
                <input type="password" id="password" required>
                <small style="color: #666;">Minimum 6 caractères</small>
            </div>
            
            <div class="form-group">
                <label>Je suis :</label>
                <select id="role" onchange="toggleDoctorFields()" required>
                    <option value="patient">Patient</option>
                    <option value="docteur">Médecin</option>
                </select>
            </div>
            
            <div id="doctor-fields" style="display:none;">
                <div class="form-group">
                    <label>Spécialité :</label>
                    <input type="text" id="specialite">
                </div>
                <div class="form-group">
                    <label>Téléphone :</label>
                    <input type="tel" id="telephone">
                </div>
                <div class="form-group">
                    <label>Adresse du cabinet :</label>
                    <textarea id="adresse" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Prix consultation (€) :</label>
                    <input type="number" id="prix" step="0.01">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">S'inscrire</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            Déjà un compte ? <a href="login.php">Se connecter</a>
        </p>
    </div>
    
    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const data = {
                nom: document.getElementById('nom').value,
                prenom: document.getElementById('prenom').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role').value
            };
            
            if (data.role === 'docteur') {
                data.specialite = document.getElementById('specialite').value;
                data.telephone = document.getElementById('telephone').value;
                data.adresse = document.getElementById('adresse').value;
                data.prix = document.getElementById('prix').value;
            }
            
            const response = await fetch('../../backend/api/register.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            const msgDiv = document.getElementById('message');
            msgDiv.style.display = 'block';
            
            if (result.success) {
                msgDiv.className = 'alert alert-success';
                msgDiv.innerHTML = result.message + ' Redirection vers la connexion...';
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                msgDiv.className = 'alert alert-error';
                msgDiv.innerHTML = result.message;
            }
        });
    </script>
</body>
</html>