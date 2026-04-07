<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Cabinet Médical</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Connexion</h2>
        
        <div id="message" style="display:none;" class="alert"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label>Email :</label>
                <input type="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label>Mot de passe :</label>
                <input type="password" id="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            Pas encore de compte ? <a href="register.php">S'inscrire</a>
        </p>
        
        <hr style="margin: 20px 0;">
        
        <p style="text-align: center; font-size: 0.9em; color: #666;">
            <strong>Comptes de test :</strong><br>
            Patient: marie.dupont@email.com / test123<br>
            Docteur: sophie.martin@email.com / test123
        </p>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            const response = await fetch('../../backend/api/login.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({email, password})
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                const msgDiv = document.getElementById('message');
                msgDiv.style.display = 'block';
                msgDiv.className = 'alert alert-error';
                msgDiv.innerHTML = data.message;
            }
        });
    </script>
</body>
</html>