async function finish(){

    if(!cart.length){
        alert("Carrinho vazio");
        return;
    }

    if(isProcessing) return;

    isProcessing = true;

    try{

        const total = getTotal();
        const paid = getPaid();

        if(paid < total){
            alert("Pagamento incompleto");
            isProcessing = false;
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]').content;

        const res = await fetch("/admin/sales",{
            method:"POST",
            headers:{
                "Content-Type":"application/json",
                "X-CSRF-TOKEN":token
            },
            body: JSON.stringify({
                items: cart,
                total,
                payments
            })
        });

        const data = await res.json().catch(()=>null);

        if(!res.ok){
            alert(data?.message || "Erro venda");
            return;
        }

        alert("Venda #" + data.sale_id);
        resetSale();

    }catch(e){
        alert("Erro rede");
    }finally{
        isProcessing = false;
    }
}

/* ================= CASH ================= */

async function openCloseCashModal(){

    const res = await fetch("/admin/shifts/summary");
    const data = await res.json();

    expectedCashValue = parseFloat(data.expected || 0);

    document.getElementById("expectedCash").innerText =
        expectedCashValue.toFixed(2);

    document.getElementById("closeCashModal").classList.remove("hidden");
}

function closeCloseCashModal(){
    document.getElementById("closeCashModal").classList.add("hidden");
}

/* LIVE DIFF */
document.addEventListener("input",(e)=>{

    if(e.target.id !== "countedCash") return;

    let counted = parseFloat(e.target.value || 0);
    let diff = counted - expectedCashValue;

    let el = document.getElementById("cashDifference");
    el.innerText = diff.toFixed(2);

    el.className =
        diff > 0 ? "text-green-400" :
        diff < 0 ? "text-red-400" :
        "text-orange-400";
});

/* CONFIRM */
async function confirmCloseCash(){

    const token = document.querySelector('meta[name="csrf-token"]').content;

    const res = await fetch("/admin/close-shift",{
        method:"POST",
        headers:{
            "Content-Type":"application/json",
            "X-CSRF-TOKEN":token
        },
        body: JSON.stringify({
            counted_cash: parseFloat(document.getElementById("countedCash").value || 0),
            notes: document.getElementById("closeNotes").value
        })
    });

    const data = await res.json();

    if(!res.ok){
        alert(data.message || "Erro caixa");
        return;
    }

    alert("Caixa fechado");
    closeCloseCashModal();
    location.reload();
}