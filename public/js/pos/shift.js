

let expectedCashValue = 0;

async function openCloseCashModal() {

    try {

        const res = await fetch("/admin/shifts/summary", {
            headers: {
                "Accept": "application/json"
            }
        });

        const data = await res.json();

        expectedCashValue = parseFloat(data.expected || 0);

        document.getElementById("expectedCash").innerText =
            expectedCashValue.toFixed(2);

        document.getElementById("countedCash").value = "";
        document.getElementById("cashDifference").innerText =
            (0 - expectedCashValue).toFixed(2);
        document.getElementById("closeNotes").value = "";

        document
            .getElementById("closeCashModal")
            .classList.remove("hidden");

    } catch (error) {

        console.error(error);
        alert("Erro ao carregar dados do caixa");

    }
}

function closeCloseCashModal() {

    document
        .getElementById("closeCashModal")
        .classList.add("hidden");

}

/* =============================
   REALTIME DIFFERENCE
============================= */

document.addEventListener("input", function (e) {

    if (e.target.id !== "countedCash") return;

    let counted = parseFloat(e.target.value || 0);

    let diff = counted - expectedCashValue;

    let diffEl = document.getElementById("cashDifference");

    diffEl.innerText = diff.toFixed(2);

    diffEl.classList.remove(
        "text-green-400",
        "text-red-400",
        "text-orange-400"
    );

    if (diff > 0) {
        diffEl.classList.add("text-green-400");
    } else if (diff < 0) {
        diffEl.classList.add("text-red-400");
    } else {
        diffEl.classList.add("text-orange-400");
    }

});

/* =============================
   CONFIRM CLOSE CASH
============================= */

async function confirmCloseCash() {

    try {

        const countedCash =
            parseFloat(document.getElementById("countedCash").value || 0);

        const notes =
            document.getElementById("closeNotes").value;

        const token =
            document.querySelector(
                'meta[name="csrf-token"]'
            ).content;

        const res = await fetch("/admin/close-shift", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                "Accept": "application/json"
            },
            body: JSON.stringify({
                counted_cash: countedCash,
                notes: notes
            })
        });

        const data = await res.json();

        if (!res.ok) {

            alert(
                data.message ||
                data.error ||
                "Erro ao fechar caixa"
            );

            return;
        }

        alert("Caixa fechado com sucesso");

        closeCloseCashModal();

        location.reload();

    } catch (error) {

        console.error(error);

        alert("Erro de conexão");

    }

}
