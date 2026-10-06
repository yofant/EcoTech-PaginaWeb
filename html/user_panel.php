<?php
session_start();

if (!isset($_SESSION['usuario']['correo'])) {
    header('Location: login_user.php?status=session_expired');
    exit();
}

if (!in_array($_SESSION['usuario']['rol'] ?? '', ['Usuario', 'Vendedor'], true)) {
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$nombreUsuario = trim((string) ($_SESSION['usuario']['nombre'] ?? ''));
if ($nombreUsuario === '') {
    $nombreUsuario = 'Usuario EcoTech';
}
$rolUsuario = (string) $_SESSION['usuario']['rol'];
$esVendedor = $rolUsuario === 'Vendedor';
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#07110d" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>" />
    <title>Mi espacio | EcoTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"
        integrity="sha384-/o6I2CkkWC//PSjvWC/eYN7l3xM3tJm8ZzVkCOfp//W05QcE3mlGskpoHB6XqI+B" crossorigin="anonymous" />
    <link rel="stylesheet" href="../css/user_panel.css" />
</head>

<body class="portal-body" data-portal-role="<?php echo $esVendedor ? 'Vendedor' : 'Usuario'; ?>">
    <div class="portal-shell">
        <aside class="portal-sidebar" aria-label="Navegación principal">
            <a class="portal-brand" href="index.php" aria-label="EcoTech, volver al sitio">
                <span class="portal-brand-mark"><i class="fas fa-leaf"></i></span>
                <span><em>Eco</em>Tech</span>
            </a>
            <p class="portal-sidebar-caption">Mi espacio EcoTech</p>

            <nav class="portal-nav" aria-label="Navegación del portal">
                <a href="#inicio" class="portal-nav-link active" data-portal-target="inicio" aria-current="page">
                    <i class="fas fa-house"></i><span>Inicio</span>
                </a>
                <a href="#equipos" class="portal-nav-link" data-portal-target="equipos" aria-current="false">
                    <i class="fas fa-laptop"></i><span>Explorar equipos</span>
                </a>
                <a href="#puntos" class="portal-nav-link" data-portal-target="puntos" aria-current="false">
                    <i class="fas fa-location-dot"></i><span>Puntos de entrega</span>
                </a>
                <a href="#mensajes" class="portal-nav-link" data-portal-target="mensajes" aria-current="false">
                    <i class="fas fa-comments"></i><span>Mis conversaciones</span>
                    <span class="portal-nav-count" id="unread-count" hidden>0</span>
                </a>
                <?php if ($esVendedor) { ?>
                    <a href="#publicar" class="portal-nav-link" data-portal-target="publicar" aria-current="false">
                        <i class="fas fa-circle-plus"></i><span>Publicar un equipo</span>
                    </a>
                <?php } ?>
            </nav>

            <div class="portal-account">
                <span class="portal-avatar"><?php echo htmlspecialchars(strtoupper(substr($nombreUsuario, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="portal-account-info">
                    <strong><?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small><?php echo $esVendedor ? 'Vendedor' : 'Usuario'; ?></small>
                </span>
                <a href="../php/logout.php" class="portal-logout" aria-label="Cerrar sesión" title="Cerrar sesión">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <main class="portal-main">
            <header class="portal-topbar">
                <div>
                    <span class="portal-breadcrumb">EcoTech <i class="fas fa-chevron-right"></i> Mi espacio</span>
                    <p class="portal-topbar-subtitle">Conecta tus equipos con nuevas oportunidades.</p>
                </div>
                <a href="index.php" class="portal-site-link"><i class="fas fa-arrow-up-right-from-square"></i> Ver sitio</a>
            </header>

            <output id="portal-alert" class="portal-alert" aria-live="polite" hidden></output>
            <output id="portal-loading" class="portal-loading" aria-live="polite">
                <span class="portal-spinner"></span> Cargando tu espacio...
            </output>

            <section class="portal-section is-active" id="inicio" aria-labelledby="welcome-title">
                <div class="portal-welcome">
                    <div class="portal-welcome-copy">
                        <span class="portal-eyebrow"><span></span> Comunidad EcoTech</span>
                        <h1 id="welcome-title">Hola, <?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p>Encuentra tecnología para darle una segunda vida, conversa con vendedores y coordina la entrega de tus equipos.</p>
                        <a href="#equipos" class="portal-button portal-button-primary" data-portal-target="equipos">
                            Explorar equipos <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="portal-welcome-art" aria-hidden="true">
                        <span class="portal-orbit portal-orbit-one"></span>
                        <span class="portal-orbit portal-orbit-two"></span>
                        <span class="portal-art-device"><i class="fas fa-laptop"></i></span>
                        <span class="portal-art-leaf"><i class="fas fa-leaf"></i></span>
                    </div>
                </div>

                <div class="portal-stat-grid">
                    <article class="portal-stat-card">
                        <span class="portal-stat-icon"><i class="fas fa-laptop"></i></span>
                        <span><small>Equipos disponibles</small><strong id="stat-equipment">—</strong></span>
                    </article>
                    <article class="portal-stat-card">
                        <span class="portal-stat-icon"><i class="fas fa-location-dot"></i></span>
                        <span><small>Puntos de entrega</small><strong id="stat-points">—</strong></span>
                    </article>
                    <article class="portal-stat-card">
                        <span class="portal-stat-icon"><i class="fas fa-comments"></i></span>
                        <span><small>Conversaciones</small><strong id="stat-chats">—</strong></span>
                    </article>
                </div>

                <div class="portal-section-heading">
                    <div>
                        <span class="portal-eyebrow">Descubre</span>
                        <h2>Equipos que buscan un nuevo hogar</h2>
                    </div>
                    <a href="#equipos" class="portal-text-link" data-portal-target="equipos">Ver todos <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="portal-product-grid" id="featured-equipment"></div>

                <div class="portal-help-banner">
                    <span class="portal-help-icon"><i class="fas fa-truck-fast"></i></span>
                    <div>
                        <h2>¿Necesitas coordinar una recogida?</h2>
                        <p>Escríbele a nuestro equipo de operadores para encontrar la mejor opción.</p>
                    </div>
                    <a href="#mensajes" class="portal-button portal-button-outline" data-portal-target="mensajes">Contactar operador</a>
                </div>
            </section>

            <section class="portal-section" id="equipos" aria-labelledby="equipment-title" hidden>
                <div class="portal-section-heading portal-page-heading">
                    <div>
                        <span class="portal-eyebrow">Economía circular</span>
                        <h1 id="equipment-title">Explorar equipos</h1>
                        <p>Conoce los equipos publicados por vendedores de la comunidad y conversa directamente con ellos.</p>
                    </div>
                    <label class="portal-search">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="search" id="equipment-search" placeholder="Buscar por equipo, marca o vendedor" aria-label="Buscar equipos" />
                    </label>
                </div>
                <div class="portal-product-grid" id="all-equipment"></div>
            </section>

            <section class="portal-section" id="puntos" aria-labelledby="points-title" hidden>
                <div class="portal-section-heading portal-page-heading">
                    <div>
                        <span class="portal-eyebrow">Entrega responsable</span>
                        <h1 id="points-title">Puntos de entrega</h1>
                        <p>Consulta los centros activos donde puedes llevar equipos electrónicos.</p>
                    </div>
                </div>
                <div class="portal-point-grid" id="collection-points"></div>
            </section>

            <section class="portal-section" id="mensajes" aria-labelledby="messages-title" hidden>
                <div class="portal-section-heading portal-page-heading">
                    <div>
                        <span class="portal-eyebrow">Estamos para ayudarte</span>
                        <h1 id="messages-title">Mis conversaciones</h1>
                        <p>Habla con vendedores sobre un equipo o coordina una recogida con un operador.</p>
                    </div>
                </div>
                <div class="portal-chat">
                    <aside class="portal-chat-sidebar" aria-label="Mensajes y conversaciones">
                        <div class="portal-chat-sidebar-head">
                            <div>
                                <h2>Mensajes</h2>
                                <p id="chat-list-caption">Tus chats recientes</p>
                            </div>
                            <span class="portal-chat-total" id="chat-total">0</span>
                        </div>
                        <div class="portal-chat-operator">
                            <span class="portal-contact-icon"><i class="fas fa-headset"></i></span>
                            <div>
                                <strong>Recogida de equipos</strong>
                                <small>Coordina con un operador</small>
                            </div>
                            <select id="operator-select" aria-label="Selecciona un operador">
                                <option value="">Operador...</option>
                            </select>
                            <button type="button" id="start-operator-chat" class="portal-chat-start" aria-label="Iniciar chat de recogida">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <div class="portal-conversation-list" id="conversation-list"></div>
                    </aside>
                    <div class="portal-chat-thread">
                        <div class="portal-thread-empty" id="thread-empty">
                            <span><i class="fas fa-comments"></i></span>
                            <h2>Abre una conversación</h2>
                            <p>Elige un chat de la lista o escribe a un vendedor desde su publicación.</p>
                        </div>
                        <div class="portal-thread-content" id="thread-content" hidden>
                            <header class="portal-thread-header">
                                <span class="portal-contact-icon" id="thread-contact-icon"><i class="fas fa-user"></i></span>
                                <div>
                                    <strong id="thread-contact-name">Contacto</strong>
                                    <small id="thread-context">Chat de EcoTech</small>
                                </div>
                                <span class="portal-online-indicator">Chat seguro</span>
                            </header>
                            <div class="portal-message-list" id="message-list" aria-live="polite"></div>
                            <form class="portal-message-form" id="message-form">
                                <label class="visually-hidden" for="message-input">Escribe tu mensaje</label>
                                <input id="message-input" type="text" maxlength="2000" autocomplete="off" placeholder="Escribe un mensaje..." required />
                                <button type="submit" class="portal-send-button" aria-label="Enviar mensaje"><i class="fas fa-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <?php if ($esVendedor) { ?>
                <section class="portal-section" id="publicar" aria-labelledby="publish-title" hidden>
                    <div class="portal-section-heading portal-page-heading">
                        <div>
                            <span class="portal-eyebrow">Comparte tecnología</span>
                            <h1 id="publish-title">Publicar un equipo</h1>
                            <p>Describe el equipo con claridad para que otra persona pueda encontrarlo.</p>
                        </div>
                    </div>
                    <div class="portal-publish-layout">
                        <form class="portal-form-card" id="publish-form">
                            <div class="portal-form-grid">
                                <label class="portal-form-field portal-field-wide">
                                    <span>Tipo de equipo <b>*</b></span>
                                    <select id="publish-type" required>
                                        <option value="">Selecciona un tipo</option>
                                    </select>
                                </label>
                                <label class="portal-form-field">
                                    <span>Marca <b>*</b></span>
                                    <input id="publish-brand" maxlength="100" required placeholder="Ej. Lenovo" />
                                </label>
                                <label class="portal-form-field">
                                    <span>Modelo <b>*</b></span>
                                    <input id="publish-model" maxlength="100" required placeholder="Ej. ThinkPad T480" />
                                </label>
                                <label class="portal-form-field portal-field-wide">
                                    <span>Descripción</span>
                                    <textarea id="publish-description" maxlength="200" rows="4" placeholder="Cuéntanos sobre el estado y las características del equipo."></textarea>
                                    <small>Máximo 200 caracteres</small>
                                </label>
                            </div>
                            <div class="portal-form-note"><i class="fas fa-circle-info"></i> No publiques información personal ni datos de acceso del equipo.</div>
                            <button type="submit" class="portal-button portal-button-primary" id="publish-button">
                                Publicar equipo <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                        <aside class="portal-publish-aside" aria-label="Consejos para publicar">
                            <span class="portal-publish-illustration"><i class="fas fa-recycle"></i></span>
                            <h2>Una publicación puede alargar la vida de un equipo.</h2>
                            <p>Cuando alguien tenga interés, podrán conversar aquí mismo para acordar los siguientes pasos.</p>
                        </aside>
                    </div>
                </section>
            <?php } ?>
        </main>
    </div>

    <script src="../Js/user_portal.js"></script>
</body>

</html>
