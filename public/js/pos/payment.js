(function (window) {
    const Payment = {
        parse(value) {
            if (typeof value === "number") {
                return Number.isFinite(value) ? value : 0;
            }

            return parseFloat(String(value || "0").replace(",", ".")) || 0;
        },

        format(value) {
            return this.parse(value).toLocaleString("pt-PT") + " Kz";
        },

        roundUp(value, increment) {
            const step = this.parse(increment) || 1;
            return Math.ceil(this.parse(value) / step) * step;
        },

        sumBreakdown(breakdown) {
            return Object.values(breakdown || {}).reduce((sum, value) => {
                return sum + this.parse(value);
            }, 0);
        },

        missing(total, paid) {
            return Math.max(this.parse(total) - this.parse(paid), 0);
        },

        change(total, paid, cashPart) {
            const excess = this.parse(paid) - this.parse(total);
            const maxCashChange = cashPart === undefined ? excess : this.parse(cashPart);

            return Math.min(Math.max(excess, 0), Math.max(maxCashChange, 0));
        },

        buildBreakdown(cash, card, transfer, customerCard) {
            return {
                cash: this.parse(cash),
                card: this.parse(card),
                transfer: this.parse(transfer),
                customer_card: this.parse(customerCard)
            };
        }
    };

    window.NkamaPOSPayment = Payment;

    // Compatibility for older POS screens that still load this file directly.
    function legacyPayments() {
        if (typeof payments !== "undefined") return payments;
        return window.payments || {};
    }

    function legacyCart() {
        if (typeof cart !== "undefined") return cart;
        return window.cart || [];
    }

    window.getPaid = window.getPaid || function () {
        const payments = legacyPayments();
        return Payment.sumBreakdown({
            cash: payments.cash,
            card: payments.card,
            transf: payments.transf,
            multi: payments.multi
        });
    };

    window.clearInput = window.clearInput || function () {
        const input = document.getElementById("input");
        if (input) input.value = "";
    };

    window.key = window.key || function (value) {
        const input = document.getElementById("input");
        if (input) input.value += value;
    };

    window.openPayModal = window.openPayModal || function () {
        if (!legacyCart().length) {
            alert("Carrinho vazio");
            return;
        }

        const modal = document.getElementById("payModal");
        if (modal) modal.classList.remove("hidden");

        if (typeof window.setMethod === "function") window.setMethod("cash");
        if (typeof window.clearInput === "function") window.clearInput();
        if (typeof window.updatePaymentUI === "function") window.updatePaymentUI();
    };

    window.closePayModal = window.closePayModal || function () {
        const modal = document.getElementById("payModal");
        if (modal) modal.classList.add("hidden");
    };

    window.setMethod = window.setMethod || function (method) {
        if (typeof selectedMethod !== "undefined") {
            selectedMethod = method;
        } else {
            window.selectedMethod = method;
        }

        document.querySelectorAll(".payBtn").forEach(button => {
            button.classList.remove("bg-orange-500", "text-black");
        });

        const button = document.getElementById("btn-" + method);
        if (button) button.classList.add("bg-orange-500", "text-black");
    };

    window.addPay = window.addPay || function () {
        const amount = Payment.parse(document.getElementById("input")?.value);
        const payments = legacyPayments();
        const method = typeof selectedMethod !== "undefined" ? selectedMethod : window.selectedMethod;

        if (amount <= 0 || !method) return;

        payments[method] = Payment.parse(payments[method]) + amount;

        if (typeof window.clearInput === "function") window.clearInput();
        if (typeof window.updatePaymentUI === "function") window.updatePaymentUI();
    };

    window.updatePaymentUI = window.updatePaymentUI || function () {
        if (typeof getTotal !== "function") return;

        const total = getTotal();
        const paid = window.getPaid();
        const diff = paid - total;
        const fields = {
            mTotal: total,
            paidAmount: paid,
            missingAmount: diff >= 0 ? 0 : Math.abs(diff),
            changeAmount: diff >= 0 ? diff : 0,
            cash: legacyPayments().cash,
            card: legacyPayments().card,
            transf: legacyPayments().transf,
            multi: legacyPayments().multi
        };

        Object.entries(fields).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.innerText = Payment.parse(value).toFixed(2);
        });
    };

    window.resetSale = window.resetSale || function () {
        if (typeof cart !== "undefined") {
            cart = [];
        } else {
            window.cart = [];
        }

        const currentPayments = legacyPayments();
        currentPayments.cash = 0;
        currentPayments.card = 0;
        currentPayments.transf = 0;
        currentPayments.multi = 0;

        if (typeof window.clearInput === "function") window.clearInput();
        if (typeof renderCart === "function") renderCart();
        if (typeof window.updatePaymentUI === "function") window.updatePaymentUI();
        if (typeof window.closePayModal === "function") window.closePayModal();
    };
})(window);
