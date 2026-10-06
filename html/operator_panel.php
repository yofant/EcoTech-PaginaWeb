<?php
session_start();

if (!isset($_SESSION['usuario']['correo'])) {
    header('Location: login_user.php?status=session_expired');
    exit();
}

if (strcasecmp((string) ($_SESSION['usuario']['rol'] ?? ''), 'Operador') !== 0) {
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$nombreOperador = trim((string) ($_SESSION['usuario']['nombre'] ?? ''));
if ($nombreOperador === '') {
    $nombreOperador = 'Operador EcoTech';
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#07110d" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>" />
    <title>Bandeja de recogidas | EcoTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/user_panel.css" />
</head>

<body class="portal-body" data-portal-role="Operador">
    <div class="portal-shell">
        <aside class="portal-sidebar">
            <a class="portal-brand" href="index.php" aria-label="EcoTech, volver al sitio">
                <span class="portal-brand-mark"><i class="fas fa-leaf"></i></span>
                <span><em>Eco</em>Tech</span>
            </a>
            <p class="portal-sidebar-caption">Centro de recogidas</p>
            <nav class="portal-nav" aria-label="Navegación del portal">
                <a href="#mensajes" class="portal-nav-link active" data-portal-target="mensajes" aria-current="page">
                    <i class="fas fa-inbox"></i><span>Bandeja de solicitudes</span>
                </a>
            </nav>
            <div class="portal-account">
                <span class="portal-avatar"><?php echo htmlspecialchars(strtoupper(substr($nombreOperador, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="portal-account-info">
                    <strong><?php echo htmlspecialchars($nombreOperador, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small>Operador de recogidas</small>
                </span>
                <a href="../php/logout.php" class="portal-logout" aria-label="Cerrar sesión" title="Cerrar sesión">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <main class="portal-main">
            <header class="portal-topbar">
                <div>
                    <span class="portal-breadcrumb">EcoTech <i class="fas fa-chevron-right"></i> Operaciones</span>
                    <p class="portal-topbar-subtitle">Responde solicitudes y coordina recogidas de equipos.</p>
                </div>
                <a href="index.php" class="portal-site-link"><i class="fas fa-arrow-up-right-from-square"></i> Ver sitio</a>
            </header>
            <div id="portal-alert" class="portal-alert" role="status" hidden></div>
            <div id="portal-loading" class="portal-loading" role="status">
                <span class="portal-spinner"></span> Cargando solicitudes...
            </div>
            <section class="portal-section is-active" id="mensajes" aria-labelledby="messages-title">
                <div class="portal-section-heading portal-page-heading">
                    <div>
                        <span class="portal-eyebrow">Atención a la comunidad</span>
                        <h1 id="messages-title">Solicitudes de recogida</h1>
                        <p>Conversa con las personas que necesitan coordinar la entrega de sus equipos electrónicos.</p>
                    </div>
                </div>
                <div class="portal-chat operator-chat">
                    <aside class="portal-chat-sidebar">
                        <div class="portal-chat-sidebar-head">
                            <div>
                                <h2>Solicitudes</h2>
                                <p>Conversaciones asignadas a tu equipo</p>
                            </div>
                            <span class="portal-chat-total" id="chat-total">0</span>
                        </div>
                        <div class="portal-conversation-list" id="conversation-list"></div>
                    </aside>
                    <div class="portal-chat-thread">
                        <div class="portal-thread-empty" id="thread-empty">
                            <span><i class="fas fa-headset"></i></span>
                            <h2>Selecciona una solicitud</h2>
                            <p>Los mensajes nuevos de usuarios aparecerán en la bandeja.</p>
                        </div>
                        <div class="portal-thread-content" id="thread-content" hidden>
                            <header class="portal-thread-header">
                                <span class="portal-contact-icon" id="thread-contact-icon"><i class="fas fa-user"></i></span>
                                <div>
                                    <strong id="thread-contact-name">Contacto</strong>
                                    <small id="thread-context">Coordinación de recogida</small>
                                </div>
                                <span class="portal-online-indicator">Chat seguro</span>
                            </header>
                            <div class="portal-message-list" id="message-list" aria-live="polite"></div>
                            <form class="portal-message-form" id="message-form">
                                <label class="visually-hidden" for="message-input">Escribe tu respuesta</label>
                                <input id="message-input" type="text" maxlength="2000" autocomplete="off" placeholder="Escribe tu respuesta..." required />
                                <button type="submit" class="portal-send-button" aria-label="Enviar respuesta"><i class="fas fa-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script src="../Js/user_portal.js"></script>
</body>

</html>
