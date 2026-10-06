document.addEventListener("DOMContentLoaded", () => {
    const apiUrl = "../php/user_panel_api.php";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
    const currentRole = document.body.dataset.portalRole ?? "";
    const state = {
        user: null,
        equipment: [],
        points: [],
        operators: [],
        conversations: [],
        selectedConversation: null,
        renderedMessageIds: ""
    };

    const byId = (id) => document.getElementById(id);
    const alertBox = byId("portal-alert");

    function showAlert(message, kind = "error") {
        if (!alertBox) return;
        alertBox.textContent = message;
        alertBox.dataset.kind = kind;
        alertBox.hidden = false;
        window.clearTimeout(showAlert.timeout);
        showAlert.timeout = window.setTimeout(() => {
            alertBox.hidden = true;
        }, 6000);
    }

    async function request(action, payload = null, query = {}) {
        const url = new URL(apiUrl, window.location.href);
        if (payload) {
            url.searchParams.set("action", action);
        } else {
            Object.entries({ action, ...query }).forEach(([key, value]) => {
                url.searchParams.set(key, String(value));
            });
        }

        const response = await fetch(url, {
            method: payload ? "POST" : "GET",
            credentials: "same-origin",
            headers: payload
                ? { "Content-Type": "application/json", "X-CSRF-Token": csrfToken }
                : { Accept: "application/json" },
            body: payload ? JSON.stringify({ action, ...payload }) : undefined
        });
        let data;
        try {
            data = await response.json();
        } catch {
            throw new Error("El servidor devolvió una respuesta que no se pudo leer.");
        }
        if (!response.ok) {
            throw new Error(data.error || "No se pudo completar la solicitud.");
        }
        return data;
    }

    function make(tag, className, text) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    }

    function formatDate(value) {
        if (!value) return "";
        const date = new Date(value.replace(" ", "T"));
        if (Number.isNaN(date.getTime())) return "";
        return new Intl.DateTimeFormat("es-CO", { dateStyle: "short", timeStyle: "short" }).format(date);
    }

    function showEmpty(container, icon, title, detail = "") {
        if (!container) return;
        container.replaceChildren();
        const empty = make("div", "portal-empty-state");
        const content = make("div");
        const symbol = make("i", `fas ${icon}`);
        const heading = make("strong", "", title);
        content.append(symbol, heading);
        if (detail) content.append(make("p", "", detail));
        empty.append(content);
        container.append(empty);
    }

    function navigate(sectionId) {
        const section = byId(sectionId);
        const link = document.querySelector(`.portal-nav-link[data-portal-target="${CSS.escape(sectionId)}"]`);
        if (!section || !link) return;

        document.querySelectorAll(".portal-nav-link").forEach((item) => {
            const active = item === link;
            item.classList.toggle("active", active);
            item.setAttribute("aria-current", active ? "page" : "false");
        });
        document.querySelectorAll(".portal-section").forEach((item) => {
            const active = item === section;
            item.classList.toggle("is-active", active);
            item.hidden = !active;
        });
        window.history.replaceState({}, "", `#${sectionId}`);
    }

    document.querySelectorAll("[data-portal-target]").forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            navigate(link.dataset.portalTarget);
        });
    });

    function renderEquipmentCard(equipment) {
        const card = make("article", "portal-product-card");
        const art = make("div", "portal-product-art");
        const type = make("span", "portal-product-type", equipment.tipo_nombre || "Equipo");
        const icon = make("i", `fas ${/celular|tel[eé]fono/i.test(equipment.tipo_nombre || "") ? "fa-mobile-screen-button" : "fa-laptop"}`);
        art.append(type, icon);

        const copy = make("div", "portal-product-copy");
        copy.append(
            make("h3", "", [equipment.marca, equipment.modelo].filter(Boolean).join(" ") || "Equipo EcoTech"),
            make("p", "", equipment.descripcion || "Equipo disponible para una nueva oportunidad.")
        );
        const meta = make("div", "portal-product-meta");
        const vendor = make("span", "", `Vendedor: ${equipment.vendedor_nombre || "Comunidad EcoTech"}`);
        const button = make("button", "portal-product-contact");
        button.type = "button";
        if (Number(equipment.es_mio) === 1) {
            button.disabled = true;
            button.textContent = "Tu publicación";
        } else {
            button.innerHTML = '<i class="fas fa-comment-dots"></i> Consultar';
            button.addEventListener("click", () => startChat("vendedor", equipment.vendedor_id, equipment.equipo_id));
        }
        meta.append(vendor, button);
        copy.append(meta);
        card.append(art, copy);
        return card;
    }

    function renderEquipment() {
        const catalog = byId("all-equipment");
        const featured = byId("featured-equipment");
        if (!state.equipment.length) {
            showEmpty(catalog, "fa-laptop", "Aún no hay equipos publicados", "Vuelve pronto para conocer nuevos equipos.");
            if (featured) showEmpty(featured, "fa-seedling", "Pronto habrá equipos disponibles");
            return;
        }
        if (catalog) {
            catalog.replaceChildren(...state.equipment.map(renderEquipmentCard));
        }
        if (featured) {
            const featuredItems = state.equipment.slice(0, 3);
            featured.replaceChildren(...featuredItems.map(renderEquipmentCard));
        }
        updateSearch();
    }

    function updateSearch() {
        const search = byId("equipment-search");
        const query = (search?.value ?? "").trim().toLocaleLowerCase("es");
        const cards = byId("all-equipment")?.querySelectorAll(".portal-product-card") ?? [];
        cards.forEach((card) => {
            card.hidden = query !== "" && !card.textContent.toLocaleLowerCase("es").includes(query);
        });
        const visibleCount = [...cards].filter((card) => !card.hidden).length;
        if (cards.length > 0 && visibleCount === 0) {
            const catalog = byId("all-equipment");
            if (!byId("equipment-no-results")) {
                const empty = make("div", "portal-empty-state");
                empty.id = "equipment-no-results";
                empty.textContent = "No encontramos equipos que coincidan con tu búsqueda.";
                catalog.append(empty);
            }
        } else {
            byId("equipment-no-results")?.remove();
        }
    }

    byId("equipment-search")?.addEventListener("input", updateSearch);

    function renderPoints() {
        const container = byId("collection-points");
        if (!container) return;
        if (!state.points.length) {
            showEmpty(container, "fa-location-dot", "Aún no hay puntos de entrega publicados", "Consulta con un operador para coordinar una recogida o confirmar un punto cercano.");
            return;
        }

        container.replaceChildren();
        state.points.forEach((point) => {
            const card = make("article", "portal-point-card");
            const icon = make("span", "portal-point-icon");
            icon.append(make("i", "fas fa-location-dot"));
            const info = make("div", "portal-point-info");
            info.append(
                make("h2", "", point.nombre),
                make("p", "portal-point-city", [point.ciudad, point.departamento].filter(Boolean).join(", ")),
                make("p", "", point.direccion),
                make("p", "", `Horario: ${point.horario}`)
            );
            if (point.instrucciones) info.append(make("p", "", point.instrucciones));
            const link = make("a", "portal-map-link");
            link.href = `https://www.openstreetmap.org/search?query=${encodeURIComponent(`${point.nombre} ${point.direccion} ${point.ciudad}`)}`;
            link.target = "_blank";
            link.rel = "noopener noreferrer";
            link.append(document.createTextNode("Ver ubicación "), make("i", "fas fa-arrow-up-right-from-square"));
            info.append(link);
            card.append(icon, info);
            container.append(card);
        });
    }

    function renderOperatorOptions() {
        const select = byId("operator-select");
        if (!select) return;
        select.replaceChildren(new Option("Selecciona operador", ""));
        state.operators.forEach((operator) => {
            const name = [operator.nombre, operator.apellido].filter(Boolean).join(" ");
            select.add(new Option(name, operator.usuario_id));
        });
        if (!state.operators.length) {
            select.replaceChildren(new Option("Sin operadores", ""));
            byId("start-operator-chat").disabled = true;
        }
    }

    function renderConversations() {
        const list = byId("conversation-list");
        if (!list) return;
        list.replaceChildren();
        const total = byId("chat-total");
        if (total) total.textContent = String(state.conversations.length);
        const totalUnread = state.conversations.reduce((sum, item) => sum + Number(item.no_leidos || 0), 0);
        const unreadBadge = byId("unread-count");
        if (unreadBadge) {
            unreadBadge.textContent = String(totalUnread);
            unreadBadge.hidden = totalUnread === 0;
        }

        if (!state.conversations.length) {
            const empty = make("div", "portal-empty-state", "Tus conversaciones aparecerán aquí.");
            list.append(empty);
            return;
        }

        state.conversations.forEach((conversation) => {
            const button = make("button", "portal-conversation-item");
            button.type = "button";
            button.classList.toggle("active", state.selectedConversation?.conversacion_id === conversation.conversacion_id);
            const icon = make("span", "portal-contact-icon");
            icon.append(make("i", `fas ${conversation.tipo === "operador" ? "fa-headset" : "fa-user"}`));
            const copy = make("span", "portal-conversation-copy");
            copy.append(
                make("strong", "", conversation.contacto_nombre || "Contacto"),
                make("small", "", conversation.tipo === "operador"
                    ? "Coordinación de recogida"
                    : [conversation.equipo_marca, conversation.equipo_modelo].filter(Boolean).join(" ") || "Consulta de equipo")
            );
            button.append(icon, copy);
            const unread = Number(conversation.no_leidos || 0);
            if (unread > 0) button.append(make("span", "portal-conversation-badge", String(unread)));
            button.addEventListener("click", () => selectConversation(conversation));
            list.append(button);
        });
    }

    async function selectConversation(conversation) {
        state.selectedConversation = conversation;
        state.renderedMessageIds = "";
        byId("thread-empty").hidden = true;
        byId("thread-content").hidden = false;
        byId("thread-contact-name").textContent = conversation.contacto_nombre || "Contacto";
        byId("thread-context").textContent = conversation.tipo === "operador"
            ? "Coordinación de recogida"
            : [conversation.equipo_marca, conversation.equipo_modelo].filter(Boolean).join(" ") || "Consulta de equipo";
        renderConversations();
        await loadMessages();
        byId("message-input")?.focus();
    }

    async function loadMessages() {
        const conversation = state.selectedConversation;
        if (!conversation) return;
        try {
            const result = await request("messages", null, { conversation_id: conversation.conversacion_id });
            const ids = result.messages.map((message) => message.mensaje_id).join(",");
            if (ids === state.renderedMessageIds) return;
            const list = byId("message-list");
            const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
            list.replaceChildren();
            result.messages.forEach((message) => {
                const own = Number(message.emisor_id) === Number(state.user.id);
                const wrapper = make("article", `portal-message${own ? " own" : ""}`);
                wrapper.append(make("div", "portal-message-bubble", message.contenido));
                wrapper.append(make("time", "", formatDate(message.fecha_envio)));
                list.append(wrapper);
            });
            if (nearBottom || state.renderedMessageIds === "") {
                list.scrollTop = list.scrollHeight;
            }
            state.renderedMessageIds = ids;
        } catch (error) {
            showAlert(error.message);
        }
    }

    async function startChat(type, targetId, equipmentId = null) {
        try {
            const result = await request("start_chat", {
                type,
                target_id: Number(targetId),
                equipment_id: equipmentId ? Number(equipmentId) : null
            });
            await loadInitial(true);
            const conversation = state.conversations.find((item) => item.conversacion_id === result.conversation_id);
            if (conversation) {
                navigate("mensajes");
                await selectConversation(conversation);
                if (type === "operador") {
                    byId("message-input").value = "Hola, quisiera coordinar la recogida de mis equipos electrónicos.";
                    byId("message-input").focus();
                }
            }
        } catch (error) {
            showAlert(error.message);
        }
    }

    byId("start-operator-chat")?.addEventListener("click", () => {
        const operatorId = byId("operator-select")?.value;
        if (!operatorId) {
            showAlert("Selecciona un operador disponible.");
            return;
        }
        startChat("operador", operatorId);
    });

    byId("message-form")?.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!state.selectedConversation) return;
        const input = byId("message-input");
        const content = input.value.trim();
        if (!content) return;
        const button = byId("message-form").querySelector("button");
        button.disabled = true;
        try {
            await request("send_message", {
                conversation_id: state.selectedConversation.conversacion_id,
                content
            });
            input.value = "";
            await loadMessages();
            await loadInitial(true);
            byId("message-list").scrollTop = byId("message-list").scrollHeight;
        } catch (error) {
            showAlert(error.message);
        } finally {
            button.disabled = false;
            input.focus();
        }
    });

    function renderTypes(types) {
        const select = byId("publish-type");
        if (!select) return;
        select.replaceChildren(new Option("Selecciona un tipo", ""));
        types.forEach((type) => select.add(new Option(type.nombre, type.id)));
    }

    byId("publish-form")?.addEventListener("submit", async (event) => {
        event.preventDefault();
        const button = byId("publish-button");
        button.disabled = true;
        try {
            await request("publish_equipment", {
                type_id: byId("publish-type").value,
                brand: byId("publish-brand").value.trim(),
                model: byId("publish-model").value.trim(),
                description: byId("publish-description").value.trim()
            });
            byId("publish-form").reset();
            await loadInitial();
            showAlert("Tu equipo ya está publicado en el catálogo.", "success");
            navigate("equipos");
        } catch (error) {
            showAlert(error.message);
        } finally {
            button.disabled = false;
        }
    });

    function updateStats() {
        if (byId("stat-equipment")) byId("stat-equipment").textContent = String(state.equipment.length);
        if (byId("stat-points")) byId("stat-points").textContent = String(state.points.length);
        if (byId("stat-chats")) byId("stat-chats").textContent = String(state.conversations.length);
    }

    async function loadInitial(refresh = false) {
        const result = await request("initial");
        state.user = result.user;
        state.equipment = result.equipment ?? [];
        state.points = result.points ?? [];
        state.operators = result.operators ?? [];
        const selectedId = state.selectedConversation?.conversacion_id;
        state.conversations = result.conversations ?? [];
        state.selectedConversation = selectedId
            ? state.conversations.find((item) => item.conversacion_id === selectedId) ?? null
            : null;
        if (selectedId && !state.selectedConversation) {
            state.renderedMessageIds = "";
            byId("thread-content").hidden = true;
            byId("thread-empty").hidden = false;
        }
        if (!refresh) {
            renderEquipment();
            renderPoints();
            renderOperatorOptions();
            renderTypes(result.types ?? []);
        }
        renderConversations();
        updateStats();
        if (!refresh && location.hash) {
            const targetId = location.hash.slice(1);
            if (byId(targetId)?.classList.contains("portal-section")) navigate(targetId);
        }
    }

    async function bootstrap() {
        try {
            await loadInitial(true);
        } catch (error) {
            showAlert(error.message);
        } finally {
            const loading = byId("portal-loading");
            if (loading) loading.hidden = true;
        }
    }

    bootstrap();
    window.setInterval(async () => {
        try {
            await loadInitial();
            if (state.selectedConversation) await loadMessages();
        } catch (error) {
            showAlert(error.message);
        }
    }, 7000);
});
