<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fichier téléchargé</title>
    <style>
        /* Ton style existant */
        #custom-alert {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            padding: 20px;
            z-index: 9999;
        }
        .checkmark {
            font-size: 48px;
            color: green;
            margin-bottom: 10px;
            animation: bounce 1s infinite;
        }
        button {
            padding: 10px 20px;
            background-color: green;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: darkgreen;
        }
    </style>
</head>
<body>
    <div id='custom-alert'>
        <div class='alert-content'>
            <div class='checkmark'>
                &#x2714; <!-- Symbole OK -->
            </div>
            <p>Fichier fusionné et téléchargé avec succès !</p>
            <button onclick='closeAlert()'>OK</button>
        </div>
    </div>
    <script>
        function closeAlert() {
            document.getElementById('custom-alert').style.display = 'none';
            window.location.href = 'acceuil_suivi_paiement.php'; // Retour à la page principale
        }
    </script>
</body>
</html>
