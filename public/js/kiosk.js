
let pin = "";
let kioskEnabled = false;

/* PIN */
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

    el.innerText = pin.length ? "•".repeat(pin.length) : "••••••••";

    if (pin.length === 0) {
        document.getElementById("pinBox").classList.remove("active");
    }
}

/* STATUS */
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
        s.style.color = "#94a3b8";
    }
}

/* LOGIN */
async function login() {

    if (pin.length !== 8) {
        setStatus("PIN deve ter 8 dígitos", true);
        return;
    }

    setStatus("A validar operador...");

    const res = await fetch("/pos/auth", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            pin
        })
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
        setStatus(data.message || "PIN inválido", true);
        pin = "";
        update();
        return;
    }

    setStatus("✔ A entrar...");

    setTimeout(() => {
        window.location.href = "/admin/dashboard";
    }, 500);
}

/* =========================
   FULLSCREEN KIOSK MODE
========================= */

function goFullScreen() {
    const el = document.documentElement;

    if (el.requestFullscreen) {
        el.requestFullscreen().catch(() => { });
    } else if (el.webkitRequestFullscreen) {
        el.webkitRequestFullscreen();
    } else if (el.msRequestFullscreen) {
        el.msRequestFullscreen();
    }
}

/* obrigatório após interação */
function enableKioskMode() {
    if (kioskEnabled) return;

    kioskEnabled = true;
    goFullScreen();

    document.removeEventListener("click", enableKioskMode);
    document.removeEventListener("touchstart", enableKioskMode);
}

document.addEventListener("click", enableKioskMode);
document.addEventListener("touchstart", enableKioskMode);

/* bloqueios */
document.addEventListener("keydown", function (e) {

    if (e.key === "Escape") {
        e.preventDefault();
        goFullScreen();
    }

    if (e.key === "F11") {
        e.preventDefault();
        goFullScreen();
    }

    if ((e.ctrlKey && e.key.toLowerCase() === "w") ||
        (e.ctrlKey && e.key.toLowerCase() === "r")) {
        e.preventDefault();
    }
});

document.addEventListener("contextmenu", e => e.preventDefault());

/* auto re-lock fullscreen */
document.addEventListener("fullscreenchange", () => {
    if (!document.fullscreenElement) {
        setTimeout(() => goFullScreen(), 200);
    }
});
