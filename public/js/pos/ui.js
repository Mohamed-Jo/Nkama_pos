function openTab(tabName, el) {

    document.querySelectorAll(".tab").forEach(tab => {
        tab.classList.remove("active");
    });

    if (el) el.classList.add("active");

    ["pos", "dashboard", "cash"].forEach(id => {
        document.getElementById(id).classList.add("hidden");
    });

    const target = document.getElementById(tabName);
    if (target) target.classList.remove("hidden");
}