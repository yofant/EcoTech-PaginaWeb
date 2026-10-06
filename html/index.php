<?php
session_start();
$usuarioSesion = $_SESSION['usuario'] ?? null;
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description"
        content="EcoTech gestiona residuos electrónicos (RAEE): recolección, reacondicionamiento y trazabilidad de equipos tecnológicos para una economía circular." />
    <title>EcoTech | Gestión responsable de residuos electrónicos</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />

    <!-- Tipografía del tema -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet" />

    <!-- Sistema de diseño + layout de la portada.
         El parámetro ?v= usa la fecha de modificación del archivo: el
         navegador guarda caché en las visitas normales y solo vuelve a
         descargar el CSS cuando realmente lo editas. -->
    <link rel="stylesheet" href="../css/theme.css?v=2" />
    <link rel="stylesheet" href="../css/style.css?v=2" />

    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>

    <!-- ===================== Navegación ===================== -->
    <nav class="navbar navbar-expand-lg eco-navbar" data-navbar>
        <div class="container">
            <a href="index.php" class="navbar-brand">
                <span class="eco-brand__mark"><i class="bi bi-recycle"></i></span>
                <span class="eco-brand__text"><em>Eco</em>Tech</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPrincipal"
                aria-controls="navbarPrincipal" aria-expanded="false" aria-label="Abrir menú de navegación">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarPrincipal">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link is-active" href="index.php" aria-current="page">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="nosotros.html">Nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="servicios.html">Servicios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="productos.html">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="herramientas.html">Tecnología</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto.php">Contacto</a>
                    </li>
                    <?php if (!empty($usuarioSesion)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($usuarioSesion['nombre'] ?: 'Mi Cuenta'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                                <?php if (strcasecmp((string) ($usuarioSesion['rol'] ?? ''), 'Administrador') === 0): ?>
                                    <li><a class="dropdown-item" href="admin_panel.php"><i class="bi bi-speedometer2 me-2"></i>Panel Admin</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php elseif (in_array($usuarioSesion['rol'] ?? '', ['Usuario', 'Vendedor'], true)): ?>
                                    <li><a class="dropdown-item" href="user_panel.php"><i class="bi bi-grid me-2"></i>Mi espacio EcoTech</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php elseif (strcasecmp((string) ($usuarioSesion['rol'] ?? ''), 'Operador') === 0): ?>
                                    <li><a class="dropdown-item" href="operator_panel.php"><i class="bi bi-inbox me-2"></i>Bandeja de recogidas</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php elseif (strcasecmp((string) ($usuarioSesion['rol'] ?? ''), 'Tecnico') === 0): ?>
                                    <li><a class="dropdown-item" href="tecnico.php"><i class="bi bi-tools me-2"></i>Panel Técnico</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php elseif (strcasecmp((string) ($usuarioSesion['rol'] ?? ''), 'Auditor') === 0): ?>
                                    <li><a class="dropdown-item" href="auditor_panel.php"><i class="bi bi-clipboard2-data me-2"></i>Panel de Auditoría</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="../php/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="eco-navbar__cta" href="login_user.php">
                                <i class="bi bi-box-arrow-in-right"></i> Ingresar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <!-- ===================== 1. Hero ===================== -->
        <section class="hero">
            <div class="container">
                <div class="hero__grid">
                    <div data-reveal>
                        <span class="eco-eyebrow">Economía circular</span>
                        <h1 class="hero__title">La tecnología puede tener una <span
                                class="eco-gradient-text">segunda vida</span></h1>
                        <p class="hero__lead">
                            Gestionamos residuos electrónicos (RAEE) de forma responsable: recolectamos,
                            reacondicionamos y damos trazabilidad completa a cada equipo que vuelve a circular.
                        </p>

                        <div class="hero__actions">
                            <a href="servicios.html" class="eco-btn eco-btn--primary eco-btn--lg">
                                Conoce nuestros servicios <i class="bi bi-arrow-right"></i>
                            </a>
                            <a href="#contacto" class="eco-btn eco-btn--ghost eco-btn--lg">
                                Solicita una recolección
                            </a>
                        </div>

                        <ul class="hero__points">
                            <li><i class="bi bi-check-circle-fill"></i> Hogares y empresas</li>
                            <li><i class="bi bi-check-circle-fill"></i> Trazabilidad certificada</li>
                            <li><i class="bi bi-check-circle-fill"></i> Sin costo de retiro</li>
                        </ul>
                    </div>

                    <div class="hero__visual delay-3" data-reveal>
                        <span class="hero__badge hero__badge--tl">
                            <i class="bi bi-shield-check"></i> Proceso certificado
                        </span>
                        <img src="../images/logo-ecotech.png" alt="Logotipo de EcoTech" fetchpriority="high" />
                        <span class="hero__badge hero__badge--br">
                            <i class="bi bi-recycle"></i> +5 t de RAEE gestionadas
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== 2. Cifras de impacto ===================== -->
        <section class="eco-strip">
            <div class="container">
                <div class="row text-center">
                    <div class="col-6 col-lg-3" data-reveal>
                        <div class="eco-stat__value">+1.500</div>
                        <p class="eco-stat__label">Dispositivos recolectados</p>
                    </div>
                    <div class="col-6 col-lg-3 delay-1" data-reveal>
                        <div class="eco-stat__value">+900</div>
                        <p class="eco-stat__label">Equipos reacondicionados</p>
                    </div>
                    <div class="col-6 col-lg-3 delay-4" data-reveal>
                        <div class="eco-stat__value">+20</div>
                        <p class="eco-stat__label">Aliados y organizaciones</p>
                    </div>
                    <div class="col-6 col-lg-3 delay-5" data-reveal>
                        <div class="eco-stat__value">+5 t</div>
                        <p class="eco-stat__label">RAEE gestionadas</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== 3. Quiénes somos ===================== -->
        <section class="eco-section">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-5" data-reveal>
                        <div class="about__panel">
                            <span class="eco-eyebrow">Nuestro compromiso</span>
                            <ul class="about__pillars">
                                <li>
                                    <i class="bi bi-leaf"></i>
                                    <div>
                                        <h3>Responsabilidad ambiental</h3>
                                        <p>Cada equipo se trata bajo protocolos que evitan la liberación de
                                            sustancias contaminantes.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="bi bi-lightbulb"></i>
                                    <div>
                                        <h3>Innovación aplicada</h3>
                                        <p>Plataforma digital y herramientas modernas para facilitar la gestión de
                                            residuos desde cualquier lugar.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="bi bi-eye"></i>
                                    <div>
                                        <h3>Transparencia</h3>
                                        <p>Reportes claros y verificables sobre el destino de cada dispositivo
                                            recolectado.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="bi bi-people"></i>
                                    <div>
                                        <h3>Inclusión social</h3>
                                        <p>Entregamos tecnología reacondicionada a comunidades con recursos
                                            limitados.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-7 delay-2" data-reveal>
                        <span class="eco-eyebrow">Quiénes somos</span>
                        <h2 class="eco-title">Sostenibilidad que se siente en <span
                                class="eco-gradient-text">cada decisión</span></h2>
                        <p class="eco-lead eco-lead--left">
                            EcoTech es una iniciativa centrada en la gestión responsable de residuos electrónicos.
                            Ofrecemos soluciones de recolección, reciclaje y reacondicionamiento para dispositivos
                            obsoletos, fundada sobre los principios de la economía circular.
                        </p>
                        <p class="eco-lead eco-lead--left">
                            A través de nuestra plataforma digital y aplicación móvil, particulares, empresas y
                            universidades pueden dar una segunda vida a sus equipos, promoviendo sostenibilidad,
                            innovación y acceso a tecnología para comunidades de bajos recursos.
                        </p>

                        <ul class="about__pills">
                            <li class="eco-tag">Economía circular</li>
                            <li class="eco-tag">Trazabilidad</li>
                            <li class="eco-tag">Inclusión digital</li>
                        </ul>

                        <a href="nosotros.html" class="eco-btn eco-btn--primary">
                            Conoce nuestra historia <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== 4. Servicios ===================== -->
        <section class="eco-section eco-section--tight home-services">
            <div class="container">
                <div class="eco-section__head" data-reveal>
                    <span class="eco-eyebrow">Qué hacemos</span>
                    <h2 class="eco-title">Servicios de gestión de RAEE</h2>
                    <p class="eco-lead">
                        Alternativas eficientes para que personas, empresas y organizaciones manejen sus
                        dispositivos tecnológicos en desuso con responsabilidad ambiental.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6 col-lg-4" data-reveal>
                        <div class="eco-card eco-card--center">
                            <div class="eco-card__icon"><i class="bi bi-truck"></i></div>
                            <h3 class="eco-card__title">Recolección</h3>
                            <p class="eco-card__text">
                                Retiramos computadores, celulares y periféricos en hogares, empresas e
                                instituciones, evitando la contaminación y promoviendo prácticas de disposición responsable.
                            </p>
                            <a href="servicios.html" class="eco-btn eco-btn--ghost">Más información</a>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 delay-1" data-reveal>
                        <div class="eco-card eco-card--center">
                            <div class="eco-card__icon"><i class="bi bi-tools"></i></div>
                            <h3 class="eco-card__title">Reacondicionamiento</h3>
                            <p class="eco-card__text">
                                Diagnóstico, reparación y optimización para extender la vida útil del equipo y
                                entregar tecnología de calidad a un menor costo.
                            </p>
                            <a href="servicios.html" class="eco-btn eco-btn--ghost">Más información</a>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 delay-4" data-reveal>
                        <div class="eco-card eco-card--center">
                            <div class="eco-card__icon"><i class="bi bi-diagram-3"></i></div>
                            <h3 class="eco-card__title">Trazabilidad</h3>
                            <p class="eco-card__text">
                                Monitoreo de cada dispositivo desde su recolección hasta su destino final, con
                                información clara y verificable en todo momento.
                            </p>
                            <a href="servicios.html" class="eco-btn eco-btn--ghost">Más información</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== 5. Campañas e iniciativas ===================== -->
        <section class="eco-section eco-section--tight showcase">
            <div class="container">
                <div class="eco-section__head" data-reveal>
                    <span class="eco-eyebrow">Iniciativas en marcha</span>
                    <h2 class="eco-title">Campañas y proyectos de impacto</h2>
                    <p class="eco-lead">
                        Conoce las acciones continuas donde la comunidad y las organizaciones se unen para dar un destino responsable a sus equipos.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-4" data-reveal>
                        <figure class="showcase__frame mb-0">
                            <img src="../images/Imagen1.png"
                                alt="Campaña de reciclaje de equipos de cómputo" loading="lazy" />
                            <figcaption class="showcase__caption">
                                <h3>Reciclaje de equipos</h3>
                                <span>Computadores</span>
                            </figcaption>
                        </figure>
                    </div>

                    <div class="col-12 col-md-4 delay-1" data-reveal>
                        <figure class="showcase__frame mb-0">
                            <img src="../images/Imagen2.png"
                                alt="Campañas de reciclaje, tecnología sustentable y recuperación de componentes"
                                loading="lazy" />
                            <figcaption class="showcase__caption">
                                <h3>Tecnología sustentable</h3>
                                <span>Reuso y materiales</span>
                            </figcaption>
                        </figure>
                    </div>

                    <div class="col-12 col-md-4 delay-4" data-reveal>
                        <figure class="showcase__frame mb-0">
                            <img src="../images/Imagen3.png"
                                alt="Campañas de reparación, reacondicionamiento y destrucción segura de datos"
                                loading="lazy" />
                            <figcaption class="showcase__caption">
                                <h3>Reacondicionamiento</h3>
                                <span>Reparación y datos</span>
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== 6. Nuestro equipo ===================== -->
        <section class="eco-section">
            <div class="container">
                <div class="eco-section__head" data-reveal>
                    <span class="eco-eyebrow">Nuestro equipo</span>
                    <h2 class="eco-title">Personas detrás del proyecto</h2>
                    <p class="eco-lead">
                        Un equipo multidisciplinario que combina experiencia técnica, criterio de diseño y
                        compromiso con el impacto ambiental.
                    </p>
                </div>

                <div class="row g-4 justify-content-center">
                    <div class="col-12 col-md-6 col-lg-4" data-reveal>
                        <div class="eco-card eco-card--center team-member">
                            <div class="team-member__photo">
                                <img src="../images/Karoline.jpeg" alt="Retrato de Karolain Diaz" loading="lazy" />
                            </div>
                            <h3 class="team-member__name">Karolain Diaz</h3>
                            <span class="team-member__role">Frontend &amp; Bases de datos</span>
                            <p class="team-member__bio">
                                Desarrolladora frontend enfocada en interfaces modernas, intuitivas y responsivas, con
                                experiencia en configuración de bases de datos.
                            </p>
                            <div class="team-member__socials">
                                <a href="#" aria-label="LinkedIn de Karolain Diaz"><i class="bi bi-linkedin"></i></a>
                                <a href="#" aria-label="GitHub de Karolain Diaz"><i class="bi bi-github"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 delay-1" data-reveal>
                        <div class="eco-card eco-card--center team-member">
                            <div class="team-member__photo">
                                <img src="../images/Daniel.jpeg" alt="Retrato de Daniel Ibarra" loading="lazy" />
                            </div>
                            <h3 class="team-member__name">Daniel Ibarra</h3>
                            <span class="team-member__role">Backend &amp; Frontend</span>
                            <p class="team-member__bio">
                                Desarrollador con habilidades en frontend y backend, orientado a integrar diseño y
                                lógica de negocio en soluciones tecnológicas funcionales.
                            </p>
                            <div class="team-member__socials">
                                <a href="#" aria-label="LinkedIn de Daniel Ibarra"><i class="bi bi-linkedin"></i></a>
                                <a href="#" aria-label="GitHub de Daniel Ibarra"><i class="bi bi-github"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 delay-4" data-reveal>
                        <div class="eco-card eco-card--center team-member">
                            <div class="team-member__photo">
                                <img src="../images/Yofan.jpeg" alt="Retrato de Yofan Tellez" loading="lazy" />
                            </div>
                            <h3 class="team-member__name">Yofan Tellez</h3>
                            <span class="team-member__role">Backend &amp; Frontend</span>
                            <p class="team-member__bio">
                                Desarrollador full stack en formación, enfocado en construir aplicaciones web dinámicas
                                y un aprendizaje continuo.
                            </p>
                            <div class="team-member__socials">
                                <a href="#" aria-label="LinkedIn de Yofan Tellez"><i class="bi bi-linkedin"></i></a>
                                <a href="#" aria-label="GitHub de Yofan Tellez"><i class="bi bi-github"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== 7. Contacto ===================== -->
        <section class="eco-section" id="contacto">
            <div class="container">
                <div class="eco-section__head" data-reveal>
                    <span class="eco-eyebrow">Escríbenos</span>
                    <h2 class="eco-title">Comentarios y sugerencias</h2>
                    <p class="eco-lead">
                        ¿Tienes equipos para donar o necesitas un servicio? Cuéntanos y te respondemos a la brevedad.
                    </p>
                </div>

                <div class="home-contact">
                    <div data-reveal>
                        <h3 class="eco-title eco-title--sm">Hablemos</h3>
                        <p class="eco-lead eco-lead--left">
                            Atendemos a hogares, empresas, universidades y entidades que buscan una ruta responsable
                            para sus dispositivos tecnológicos.
                        </p>

                        <ul class="home-contact__list">
                            <li>
                                <i class="bi bi-truck"></i>
                                <span>Recolección programada en toda la ciudad y municipios cercanos.</span>
                            </li>
                            <li>
                                <i class="bi bi-shield-check"></i>
                                <span>Certificación y documentación del proceso de gestión.</span>
                            </li>
                            <li>
                                <i class="bi bi-people"></i>
                                <span>Convenios con aliados y programas de inclusión social.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="home-contact__panel delay-2" data-reveal>
                        <form action="../php/smtp.php" method="POST" class="eco-form" novalidate>
                            <div class="mb-3">
                                <label class="form-label" for="nombre">Nombre completo</label>
                                <input type="text" name="nombre" id="nombre" class="form-control"
                                    placeholder="Tu nombre" required />
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email">Correo electrónico</label>
                                <input type="email" name="email" id="email" class="form-control"
                                    placeholder="tu@correo.com" required />
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="mensaje">Mensaje</label>
                                <textarea name="mensaje" id="mensaje" class="form-control" rows="4"
                                    placeholder="Cuéntanos en qué podemos ayudarte" required></textarea>
                            </div>

                            <button class="eco-btn eco-btn--primary eco-btn--block eco-btn--lg" type="submit">
                                Enviar mensaje <i class="bi bi-send"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ===================== Pie de página ===================== -->
    <footer class="eco-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="eco-footer__brand">
                        <span class="eco-brand__mark"><i class="bi bi-recycle"></i></span>
                        <span><em class="eco-gradient-text">Eco</em>Tech</span>
                    </div>
                    <p class="eco-footer__text">
                        Gestión responsable de residuos electrónicos, reacondicionamiento de equipos y trazabilidad
                        al servicio de una economía circular.
                    </p>
                </div>

                <div class="col-6 col-lg-3">
                    <h4 class="eco-footer__title">Navegación</h4>
                    <ul class="eco-footer__links">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="nosotros.html">Nosotros</a></li>
                        <li><a href="servicios.html">Servicios</a></li>
                        <li><a href="productos.html">Productos</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-4">
                    <h4 class="eco-footer__title">Contacto</h4>
                    <ul class="eco-footer__links">
                        <li><a href="contacto.php">Formulario de contacto</a></li>
                        <li><a href="herramientas.html">Tecnología</a></li>
                        <li><a href="login_user.php">Iniciar sesión</a></li>
                        <li><a href="terminos_condiciones.html">Términos y condiciones</a></li>
                    </ul>
                </div>
            </div>

            <div class="eco-footer__bottom d-flex flex-column flex-md-row justify-content-between gap-2">
                <p>&copy; 2026 EcoTech. Todos los derechos reservados.</p>
                <p>Diseñado y desarrollado en Colombia.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <!-- Theme y Alertas JS -->
    <script src="../Js/theme.js?v=2"></script>
    <script src="../Js/alertas.js?v=2"></script>

    <script>
        // Muestra la alerta correspondiente al parámetro ?status= de la URL
        const parametrosUrl = new URLSearchParams(window.location.search);
        const estado = parametrosUrl.get('status');

        if (estado && typeof mostrarAlerta === 'function') {
            mostrarAlerta(estado);
        }
    </script>

</body>

</html>
