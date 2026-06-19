let cart = [];

let payments = {
    cash: 0,
    card: 0,
    transf: 0,
    multi: 0
};

let selectedMethod = "cash";
let isProcessing = false;

let expectedCashValue = 0;