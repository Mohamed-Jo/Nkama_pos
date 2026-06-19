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

                <button onclick="removeItem(${index})"
                    class="bg-red-600 w-8 h-8 rounded-lg font-bold">
                    ✕
                </button>

            </div>

            <div class="flex items-center gap-2 mt-3">

                <button onclick="decreaseQty(${index})"
                    class="w-9 h-9 rounded-xl bg-slate-800 font-bold">
                    −
                </button>

                <div class="px-4 py-2 rounded-xl bg-black text-sm font-bold">
                    ${item.qty}
                </div>

                <button onclick="increaseQty(${index})"
                    class="w-9 h-9 rounded-xl bg-orange-500 text-black font-black">
                    +
                </button>

                <button onclick="clearItem(${index})"
                    class="w-9 h-9 rounded-xl bg-red-700 font-bold">
                    🗑
                </button>

            </div>

        </div>`;
    });

    document.getElementById("total").innerText = total.toFixed(2);
    document.getElementById("itemsCount").innerText = items;
}

function increaseQty(index) {
    cart[index].qty++;
    renderCart();
}

function decreaseQty(index) {
    if (cart[index].qty > 1) {
        cart[index].qty--;
    } else {
        cart.splice(index, 1);
    }
    renderCart();
}

function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    if (!confirm("Deseja limpar o carrinho?")) return;
    cart = [];
    renderCart();
}

function getTotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
}