<?php
session_start();
// Si no hay sesión iniciada, redirige al login
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivos Clínicos - MahuDent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
        
        :root {
            --brand-primary: #0f766e; 
            --brand-secondary: #ccfbf1; 
            --brand-accent: #14b8a6; 
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f8fafc; 
        }
        
        .bg-brand { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
        .bg-brand-light { background-color: var(--brand-secondary); }

        /* Estilo para recortar texto largo con puntos suspensivos */
        .truncate-2-lines {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <?php $page_title = 'Radiografías'; include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <div class="w-full">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center max-w-2xl mx-auto mt-10">
                        <div class="w-24 h-24 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="folder-search" class="w-12 h-12"></i>
                        </div>
                        <h2 class="text-2xl font-black text-slate-800 mb-4">Módulo de Archivos Clínicos Centralizado</h2>
                        <p class="text-slate-500 mb-8">
                            Para mantener la integridad médica y el orden, todas las radiografías, fotografías intraorales y documentos (como Consentimientos Informados) ahora se gestionan <b>directamente dentro del perfil de cada paciente</b>.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="pacientes.php" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg transition flex items-center justify-center gap-2">
                                <i data-lucide="users" class="w-5 h-5"></i> Ir al Directorio de Pacientes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
