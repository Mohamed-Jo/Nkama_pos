let pin = "";

function add(n) {
    if (pin.length >= 8) return;

    pin += n;
    update();

    document.getElementById("pinBox").classList.add("active");
}

function clearPin() {
    pin = "";
    update();
}

function update() {
    const el = document.getElementById("pinView");

    el.innerText = pin.length ? "*".repeat(pin.length) : "********";

    if (pin.length === 0) {
        document.getElementById("pinBox").classList.remove("active");
    }
}

function setStatus(msg, error = false) {
    const s = document.getElementById("status");
    s.innerText = msg;

    if (error) {
        s.style.color = "#ef4444";
        document.getElementById("pinBox").classList.add("shake");

        setTimeout(() => {
            document.getElementById("pinBox").classList.remove("shake");
        }, 400);
    } else {
        s.style.color = currentKioskTheme() === "light" ? "#475569" : "#94a3b8";
    }
}

async function login() {
    if (pin.length !== 8) {
        setStatus("PIN deve ter 8 digitos", true);
        return;
    }

    setStatus("A validar operador...");

    const res = await fetch("/pos/auth", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ pin })
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
        setStatus(data.message || "PIN invalido", true);
        pin = "";
        update();
        return;
    }

    setStatus("A entrar...");

    setTimeout(() => {
        window.location.href = "/admin/dashboard";
    }, 500);
}

function currentKioskTheme() {
    return document.documentElement.dataset.theme === "light" ? "light" : "dark";
}

function setKioskTheme(theme) {
    const normalized = theme === "light" ? "light" : "dark";
    document.documentElement.dataset.theme = normalized;
    localStorage.setItem("nkama_theme", normalized);
    updateKioskThemeButton();
}

function toggleKioskTheme() {
    setKioskTheme(currentKioskTheme() === "light" ? "dark" : "light");
}

function updateKioskThemeButton() {
    const button = document.getElementById("kiosk-theme-btn");
    if (!button) return;

    button.textContent = currentKioskTheme() === "light" ? "Claro" : "Escuro";
}

document.addEventListener("keydown", function (e) {
    if ((e.ctrlKey && e.key.toLowerCase() === "w") ||
        (e.ctrlKey && e.key.toLowerCase() === "r")) {
        e.preventDefault();
    }
});

document.addEventListener("contextmenu", e => e.preventDefault());

document.addEventListener("DOMContentLoaded", () => {
    updateKioskThemeButton();
});
