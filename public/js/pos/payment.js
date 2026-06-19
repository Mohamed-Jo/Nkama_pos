function getPaid(){
    return payments.cash + payments.card + payments.transf + payments.multi;
}

function openPayModal(){

    if(!cart.length){
        alert("Carrinho vazio");
        return;
    }

    document.getElementById("payModal").classList.remove("hidden");

    setMethod("cash");
    clearInput();
    updatePaymentUI();
}

function closePayModal(){
    document.getElementById("payModal").classList.add("hidden");
}

function setMethod(method){

    selectedMethod = method;

    document.querySelectorAll(".payBtn").forEach(b=>{
        b.classList.remove("bg-orange-500","text-black");
    });

    const btn = document.getElementById("btn-"+method);
    if(btn) btn.classList.add("bg-orange-500","text-black");
}

function key(v){
    document.getElementById("input").value += v;
}

function clearInput(){
    document.getElementById("input").value = "";
}

function addPay(){

    let amount = parseFloat(document.getElementById("input").value || 0);
    if(amount <= 0) return;

    payments[selectedMethod] += amount;

    clearInput();
    updatePaymentUI();
}

function updatePaymentUI(){

    const total = getTotal();
    const paid = getPaid();
    const diff = paid - total;

    document.getElementById("mTotal").innerText = total.toFixed(2);
    document.getElementById("paidAmount").innerText = paid.toFixed(2);

    document.getElementById("missingAmount").innerText =
        diff >= 0 ? "0.00" : Math.abs(diff).toFixed(2);

    document.getElementById("changeAmount").innerText =
        diff >= 0 ? diff.toFixed(2) : "0.00";

    document.getElementById("cash").innerText = payments.cash.toFixed(2);
    document.getElementById("card").innerText = payments.card.toFixed(2);
    document.getElementById("transf").innerText = payments.transf.toFixed(2);
    document.getElementById("multi").innerText = payments.multi.toFixed(2);
}

function resetSale(){

    cart = [];

    payments = { cash:0, card:0, transf:0, multi:0 };

    clearInput();
    renderCart();
    updatePaymentUI();
    closePayModal();
}