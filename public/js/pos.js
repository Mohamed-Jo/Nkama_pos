let cart = [];

let payments = {
    cash: 0,
    card: 0,
    transf: 0,
    multi: 0
};

let selectedMethod = "cash";
let isProcessing = false;

/* =============================
   TABS (FIXED)
============================= */
function openTab(tabName, el) {

    document.querySelectorAll(".tab").forEach(tab => {
        tab.classList.remove("active");
    });

    if (el) el.classList.add("active");

    ["pos", "dashboard", "cash"].forEach(id => {
        document.getElementById(id).classList.add("hidden");
    });

    document.getElementById(tabName).classList.remove("hidden");
}

/* =============================
   CART
============================= */



/* =========================
   ADD ITEM
========================= */
function addItem(id, name, price) {

    price = parseFloat(price);

    const existing = cart.find(i => i.id === id);

    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({
            id,
            name,
            price,
            qty: 1
        });
    }

    renderCart();
}

/* =========================
   RENDER CART (SAP STYLE)
========================= */
function renderCart() {

    const cartEl = document.getElementById("cart");

    cartEl.innerHTML = "";

    let total = 0;
    let items = 0;

    cart.forEach((item, index) => {

        total += item.price * item.qty;
        items += item.qty;

        cartEl.innerHTML += `
        <div class="bg-slate-900 rounded-2xl p-3 border border-white/5">

            <div class="flex justify-between items-start gap-2">

                <div class="flex-1">

                    <div class="font-bold text-sm truncate">
                        ${item.name}
                    </div>

                    <div class="text-orange-400 font-black text-lg mt-1">
                        ${(item.price * item.qty).toFixed(2)} Kz
                    </div>

                

                </div>

                <!-- REMOVE ITEM -->
                <button onclick="removeItem(${index})"
                    class="bg-red-600 hover:bg-red-500 w-8 h-8 rounded-lg font-bold">
                    ✕
                </button>

            </div>

            <!-- CONTROLS -->
          
            <div class="flex items-center gap-2 mt-3">

                <button onclick="decreaseQty(${index})"
                    class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold">
                    −
                </button>

                <div class="px-4 py-2 rounded-xl bg-black text-sm font-bold">
                    ${item.qty}
                </div>

                <button onclick="increaseQty(${index})"
                    class="w-9 h-9 rounded-xl bg-orange-500 hover:bg-orange-400 text-black font-black">
                    +
                </button>

                <!-- REMOVE ALL OF THIS ITEM -->
                <button onclick="clearItem(${index})"
                    class="w-9 h-9 rounded-xl bg-red-700 hover:bg-red-600 font-bold">
                    🗑
                </button>

            </div>

        </div>`;
    });

    document.getElementById("total").innerText = total.toFixed(2);
    document.getElementById("itemsCount").innerText = items;
}

/* =========================
   INCREASE
========================= */
function increaseQty(index) {
    cart[index].qty++;
    renderCart();
}

/* =========================
   DECREASE
========================= */
function decreaseQty(index) {

    if (cart[index].qty > 1) {
        cart[index].qty--;
    } else {
        cart.splice(index, 1);
    }

    renderCart();
}

/* =========================
   REMOVE ITEM (ALL)
========================= */
function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

/* =========================
   CLEAR SINGLE ITEM FORCE
========================= */
function clearItem(index) {
    cart.splice(index, 1);
    renderCart();
}

/* =========================
   CLEAR ALL CART
========================= */
function clearCart() {

    if (!confirm("Deseja limpar todo o carrinho?")) return;

    cart = [];
    renderCart();
}

/* =========================
   GET TOTAL (UTIL)
========================= */
function getTotal() {
    return cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
}



/* =============================
   TOTALS
============================= */
function getTotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
}

function getPaid() {
    return payments.cash + payments.card + payments.transf + payments.multi;
}

/* =============================
   MODAL
============================= */
function openPayModal() {

    if (!cart.length) {
        alert("Carrinho vazio");
        return;
    }

    document.getElementById("payModal").classList.remove("hidden");

    setMethod("cash");
    clearInput();
    updatePaymentUI();
}

function closePayModal() {
    document.getElementById("payModal").classList.add("hidden");
}

/* =============================
   PAYMENT METHOD (FIXED HIGHLIGHT)
============================= */
function setMethod(method) {

    selectedMethod = method;

    document.querySelectorAll(".payBtn").forEach(btn => {
        btn.classList.remove("bg-orange-500", "text-black");
    });

    const btn = document.getElementById("btn-" + method);

    if (btn) {
        btn.classList.add("bg-orange-500", "text-black");
    }
}

/* =============================
   KEYPAD
============================= */
function key(val) {
    document.getElementById("input").value += val;
}

function clearInput() {
    document.getElementById("input").value = "";
}

/* =============================
   ADD PAYMENT
============================= */
function addPay() {

    let amount = parseFloat(document.getElementById("input").value || 0);

    if (amount <= 0) return;

    payments[selectedMethod] += amount;
    console.log("Pagamento adicionado:", selectedMethod, amount);
    clearInput();
    updatePaymentUI();
}

/* =============================
   UI UPDATE
============================= */
function updatePaymentUI() {

    const total = getTotal();
    const paid = getPaid();
    const diff = paid - total;

    document.getElementById("mTotal").innerText = total.toFixed(2);
    document.getElementById("paidAmount").innerText = paid.toFixed(2);

    if (diff >= 0) {
        document.getElementById("missingAmount").innerText = "0.00";
        document.getElementById("changeAmount").innerText = diff.toFixed(2);
    } else {
        document.getElementById("missingAmount").innerText = Math.abs(diff).toFixed(2);
        document.getElementById("changeAmount").innerText = "0.00";
    }

    document.getElementById("cash").innerText = payments.cash.toFixed(2);
    document.getElementById("card").innerText = payments.card.toFixed(2);
    document.getElementById("transf").innerText = payments.transf.toFixed(2);
    document.getElementById("multi").innerText = payments.multi.toFixed(2);
}

/* =============================
   RESET
============================= */
function resetSale() {

    cart = [];

    payments = {
        cash: 0,
        card: 0,
        transf: 0,
        multi: 0
    };

    clearInput();
    renderCart();
    updatePaymentUI();
    closePayModal();
}

/* =============================
   FINISH SALE (ANTI DUPLICATE + SESSION SAFE)
============================= */
async function finish() {

    if (cart.length === 0) {
        alert("Carrinho vazio");
        return;
    }

    if (isProcessing) return;

    isProcessing = true;

    try {

        const t = getTotal();
        const p = getPaid();

        if (p < t) {
            alert("Pagamento incompleto");
            isProcessing = false;
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]').content;
        console.log("Enviando venda:", { cart, total: t, payments });
        const res = await
            fetch("/admin/sales", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    items: cart,
                    total: t,
                    payments
                })
            });

        const data = await res.json().catch(() => null);

        if (!res.ok) {

            console.log("ERRO BACKEND:", data);

            alert(
                data?.message ||
                data?.error ||
                "Erro ao finalizar venda"
            );

            isProcessing = false;
            return;
        }

        if (data?.success) {

            alert("Venda concluída #" + data.sale_id);

            cart = [];

            payments = {
                cash: 0,
                card: 0,
                transf: 0,
                multi: 0
            };

            renderCart();
            updatePaymentUI();
            closePayModal();
        }

    } catch (err) {

        console.error(err);
        alert("Erro de rede ou servidor");

    } finally {
        isProcessing = false;
    }


}




      /* =============================
               CLOSE CASH MODAL
            ============================= */

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

        document.addEventListener("input", function(e) {

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
   


