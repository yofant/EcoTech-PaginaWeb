document.addEventListener("DOMContentLoaded", () => {
    const navLinks = document.querySelectorAll(".admin-nav-link[data-panel-target]");
    const dashboardPanels = document.querySelectorAll(".dashboard-panel");

    navLinks.forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();

            const targetId = link.dataset.panelTarget;

            navLinks.forEach((navLink) => navLink.classList.remove("active"));
            dashboardPanels.forEach((panel) => panel.classList.remove("is-active"));

            link.classList.add("active");
            navLinks.forEach((navLink) => navLink.setAttribute("aria-current", navLink === link ? "page" : "false"));

            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add("is-active");
                targetPanel.scrollIntoView({ behavior: "smooth", block: "start" });
            }

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set("panel", targetId);
            currentUrl.searchParams.delete("user_action");
            currentUrl.searchParams.delete("id");
            currentUrl.searchParams.delete("empresa_action");
            currentUrl.searchParams.delete("id_empresa");
            window.history.replaceState({}, "", currentUrl);
        });
    });

    document.querySelectorAll("[data-confirm-delete]").forEach((link) => {
        link.addEventListener("click", (event) => {
            const confirmed = window.confirm("¿Seguro que deseas eliminar este registro?");

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
});
