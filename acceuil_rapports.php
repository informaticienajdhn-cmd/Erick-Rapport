<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports Enregistrés</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <script src="js/common.js"></script>
</head>
<body class="body-import">
    <header>
        <h1>📁 RAPPORTS ENREGISTRÉS</h1>
    </header>
    <div class="container">
        <div id="rapports-list" style="margin-top: 20px; max-height: 600px; overflow-y: auto; padding-right: 10px;">
            <p style="text-align: center; color: #6b7280;">Chargement...</p>
        </div>
    </div>

    <style>
        #rapports-list::-webkit-scrollbar {
            width: 8px;
        }

        #rapports-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #rapports-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #rapports-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .rapport-item {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }

        .rapport-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #2563eb;
        }

        .rapport-info {
            flex: 1;
        }

        .rapport-nom {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .rapport-details {
            font-size: 12px;
            color: #6b7280;
        }

        .rapport-actions {
            display: flex;
            gap: 8px;
            margin-left: 15px;
        }

        .rapport-actions button {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .btn-download {
            background: #10b981;
            color: white;
        }

        .btn-download:hover {
            background: #059669;
        }

        .btn-rename {
            background: #f59e0b;
            color: white;
        }

        .btn-rename:hover {
            background: #d97706;
        }

        .btn-delete-rapport {
            background: #ef4444;
            color: white;
        }

        .btn-delete-rapport:hover {
            background: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
    </style>

</body>
</html>
