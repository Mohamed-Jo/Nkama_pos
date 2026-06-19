const API = {

    async post(url, data = {}) {

        const token = document.querySelector(
            'meta[name="csrf-token"]'
        ).content;

        const response = await fetch(url, {

            method: "POST",

            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                "Accept": "application/json"
            },

            body: JSON.stringify(data)

        });

        return response.json();

    }

};