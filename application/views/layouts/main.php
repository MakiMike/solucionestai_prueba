<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?></title>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Partials -->
    <link href="<?= base_url('assets/css/partials.css'); ?>" rel="stylesheet">
    
    <!-- CSS Layout -->
    <style>
        /* ===== VARIABLES Y RESET ===== */
        :root {
            --purple-dark: #4a2d5f;
            --purple-accent: #6b3d80;
            --pink-dark: #9d4a6f;
            --pink-muted: #b87399;
            --black-main: #0f0f0f;
            --gray-dark: #1a1a1a;
            --gray-medium: #2d2d2d;
            --gray-light: #3a3a3a;
            --gray-border: #404040;
            --white: #e8e8e8;
            --white-faded: #b0b0b0;
            --transition: all 0.2s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: 'Courier New', monospace;
            background: var(--black-main);
            color: var(--white);
            line-height: 1.5;
            display: grid;
            grid-template-columns: 250px 1fr;
            grid-template-rows: auto 1fr auto;
            grid-template-areas:
                "sidebar navbar"
                "sidebar main"
                "sidebar footer";
            min-height: 100vh;
        }

        /* ===== NAVBAR ===== */
        nav {
            grid-area: navbar;
        }

        /* ===== SIDEBAR ===== */
        aside {
            grid-area: sidebar;
            height: 100vh;
            overflow-y: auto;
        }

        /* ===== MAIN CONTENT ===== */
        main {
            grid-area: main;
            padding: 2rem;
            background: var(--black-main);
            overflow-y: auto;
        }

        /* ===== FOOTER ===== */
        footer {
            grid-area: footer;
        }

        /* ===== RESPONSIVE - TABLET ===== */
        @media (max-width: 1024px) {
            body {
                grid-template-columns: 200px 1fr;
            }

            main {
                padding: 1.5rem;
            }

            aside {
                height: 100vh;
            }
        }

        /* ===== RESPONSIVE - MOBILE ===== */
        @media (max-width: 768px) {
            body {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto 1fr auto;
                grid-template-areas:
                    "navbar"
                    "sidebar"
                    "main"
                    "footer";
            }

            aside {
                height: auto;
                overflow: visible;
                border-right: none;
                border-bottom: 2px solid var(--pink-dark);
                max-height: 300px;
                overflow-y: auto;
            }

            main {
                padding: 1.5rem 1rem;
                min-height: calc(100vh - 400px);
            }
        }

        /* ===== RESPONSIVE - MOBILE PEQUEÑO ===== */
        @media (max-width: 480px) {
            main {
                padding: 1rem 0.75rem;
            }

            aside {
                max-height: 250px;
            }
        }

        /* ===== SCROLL BAR - MAIN ===== */
        main::-webkit-scrollbar {
            width: 8px;
        }

        main::-webkit-scrollbar-track {
            background: var(--gray-dark);
        }

        main::-webkit-scrollbar-thumb {
            background: var(--gray-light);
        }

        main::-webkit-scrollbar-thumb:hover {
            background: var(--pink-dark);
        }

        /* ===== SCROLL BAR - ASIDE ===== */
        aside::-webkit-scrollbar {
            width: 8px;
        }

        aside::-webkit-scrollbar-track {
            background: var(--gray-dark);
        }

        aside::-webkit-scrollbar-thumb {
            background: var(--gray-light);
        }

        aside::-webkit-scrollbar-thumb:hover {
            background: var(--pink-dark);
        }
    </style>
</head>

<body>

    <?php $this->load->view('partials/navbar'); ?>

    <?php $this->load->view('partials/sidebar'); ?>

    <main>

        <?php
        if (isset($content)) {
            $this->load->view($content);
        }
        ?>

    </main>

    <?php $this->load->view('partials/footer'); ?>

</body>

</html>