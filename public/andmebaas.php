<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Varade Haldus</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #eee; }
        input, button, select { padding: 6px; margin: 4px 0; width: 100%; }
        form { max-width: 400px; margin-bottom: 20px; }
        button { cursor: pointer; }
    </style>
</head>
<body>

<h1>Varade Haldus</h1>

<div id="loginForm">
    <h2>Login</h2>
    <input type="text" id="username" placeholder="Kasutaja nimi" required>
    <input type="password" id="password" placeholder="Salasõna" required>
    <button id="loginBtn">Logi sisse</button>
</div>

<div id="mainContent" style="display:none;">
    <p>Tsau, <span id="currentUser"></span>! roll: <span id="role"></span></p>

    <form id="filterForm">
        <label>Vali filtri tüüp:</label>
        <select id="filterType">
            <option value="none">— vali —</option>
            <option value="vastutaja">Vastutaja</option>
            <option value="price">Maksumus</option>
            <option value="markus">Märkus</option>
        </select>

        <div id="vastutajaBlock" style="display:none;">
            <input type="text" id="filterVastutaja" placeholder="Vastutaja nimi">
        </div>

        <div id="priceBlock" style="display:none;">
            <input type="number" id="filterPrice" placeholder="Minimaalne maksumus">
        </div>

        <div id="markusBlock" style="display:none;">
            <input type="text" id="filterMarkus" placeholder="Märkus">
        </div>

        <button type="submit">Filtreerima</button>
    </form>

    <table id="varaTable">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nimetus</th>
            <th>Seisund</th>
            <th>Maksumus</th>
            <th>Vastutaja</th>
            <th>Ostukuupäev</th>
            <th>Asukoht</th>
            <th>Märkus</th>
            <th>Tegevus</th>
        </tr>
        </thead>
        <tbody>
        <tr><td colspan="9">Ei ole andmed</td></tr>
        </tbody>
    </table>

    <h2>Lisada Varas</h2>
    <form id="addVaraForm">
        <input type="text" id="nimetus" placeholder="Nimetus" required>
        <input type="text" id="seisund" placeholder="Seisund" required>
        <input type="number" id="maksumus" placeholder="Maksumus" required>
        <input type="text" id="vastutaja" placeholder="Vastutaja" required>
        <input type="date" id="ostukuupaev" placeholder="Ostukuupäev">
        <input type="text" id="asukoht" placeholder="Asukoht">
        <input type="text" id="markus" placeholder="Märkus">
        <button type="submit">Lisada</button>
    </form>

    <button id="logoutBtn">Logi välja</button>
</div>

<script>
    const apiUrl = "http://localhost:5012/api";
    let currentUser = null;
    let userRole = null;
    let editingId = null;

    document.getElementById("loginBtn").addEventListener("click", async () => {
        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value;
        try {
            const res = await fetch(`${apiUrl}/auth/login`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ username, password })
            });
            if (!res.ok) throw new Error("Vale login või salasõna");
            const data = await res.json();
            currentUser = data.username;
            userRole = data.role;
            document.getElementById("currentUser").textContent = currentUser;
            document.getElementById("role").textContent = userRole;
            document.getElementById("loginForm").style.display = "none";
            document.getElementById("mainContent").style.display = "block";
            fetchVaras();
        } catch (err) {
            alert(err.message);
        }
    });

    document.getElementById("logoutBtn").addEventListener("click", () => {
        currentUser = null;
        userRole = null;
        editingId = null;
        document.getElementById("loginForm").style.display = "block";
        document.getElementById("mainContent").style.display = "none";
    });

    async function fetchVaras(filterVastutaja = "", filterPrice = 0, filterMarkus = "") {
        try {
            const params = new URLSearchParams();
            params.append("user", currentUser);
            if (userRole === "admin") params.append("admin", "true");

            const res = await fetch(`${apiUrl}/vara?${params.toString()}`);
            if (!res.ok) throw new Error("Ei saanud laadida andmed");

            let data = await res.json();
            data = data.map(v => ({
                id: v.id,
                nimetus: v.nimetus || v.Nimetus,
                seisund: v.seisund || v.Seisund,
                maksumus: v.maksumus || v.Maksumus,
                vastutaja: v.vastutaja || v.Vastutaja,
                ostukuupaev: v.ostukuupaev || v.Ostukuupaev,
                asukoht: v.asukoht || v.Asukoht,
                markus: v.markus || v.Markus
            }));

            if (userRole !== "admin") data = data.filter(v => v.vastutaja === currentUser);

            const filterType = document.getElementById("filterType").value;
            if (filterType === "vastutaja" && filterVastutaja) {
                data = data.filter(v => (v.vastutaja || "").toLowerCase().includes(filterVastutaja.toLowerCase()));
            }
            if (filterType === "price" && filterPrice > 0) {
                data = data.filter(v => parseFloat(v.maksumus) >= filterPrice);
            }
            if (filterType === "markus" && filterMarkus) {
                data = data.filter(v => (v.markus || "").toLowerCase().includes(filterMarkus.toLowerCase()));
            }

            const tbody = document.getElementById("varaTable").querySelector("tbody");
            tbody.innerHTML = "";
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9">Ei ole andmed</td></tr>`;
                return;
            }

            for (const v of data) {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                <td>${v.id}</td>
                <td>${v.nimetus}</td>
                <td>${v.seisund}</td>
                <td>${v.maksumus}</td>
                <td>${v.vastutaja}</td>
                <td>${v.ostukuupaev || ""}</td>
                <td>${v.asukoht || ""}</td>
                <td>${v.markus || ""}</td>
                <td>
                    <button onclick='editVara(${v.id})'>Muuda</button>
                    <button onclick='deleteVara(${v.id})' style="margin-left:5px; color:red;">Kustuta</button>
                </td>
            `;
                tbody.appendChild(tr);
            }
        } catch (err) {
            alert(err.message);
            console.error(err);
        }
    }

    document.getElementById("filterForm").addEventListener("submit", (e) => {
        e.preventDefault();
        const filterVastutaja = document.getElementById("filterVastutaja").value.trim();
        const filterPrice = parseFloat(document.getElementById("filterPrice").value) || 0;
        const filterMarkus = document.getElementById("filterMarkus").value.trim();
        fetchVaras(filterVastutaja, filterPrice, filterMarkus);
    });

    function updateFilterVisibility() {
        const type = document.getElementById("filterType").value;
        document.getElementById("vastutajaBlock").style.display = type === "vastutaja" ? "block" : "none";
        document.getElementById("priceBlock").style.display = type === "price" ? "block" : "none";
        document.getElementById("markusBlock").style.display = type === "markus" ? "block" : "none";
    }
    document.getElementById("filterType").addEventListener("change", updateFilterVisibility);
    updateFilterVisibility();

    // --- Add or Save Vara ---
    document.getElementById("addVaraForm").addEventListener("submit", async (e) => {
        e.preventDefault();

        const ostukuupaevValue = document.getElementById("ostukuupaev").value;
        const vara = {
            Nimetus: document.getElementById("nimetus").value.trim() || null,
            Seisund: document.getElementById("seisund").value.trim() || null,
            Maksumus: parseFloat(document.getElementById("maksumus").value) || 0,
            Vastutaja: document.getElementById("vastutaja").value.trim() || null,
            Ostukuupaev: ostukuupaevValue ? new Date(ostukuupaevValue).toISOString() : null,
            Asukoht: document.getElementById("asukoht").value.trim() || null,
            Markus: document.getElementById("markus").value.trim() || null
        };

        try {
            let res;
            if (editingId) {
                res = await fetch(`${apiUrl}/vara/${editingId}`, {
                    method: "PUT",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(vara)
                });
                if (!res.ok) throw new Error("Ei saanud salvestada andmed");
                alert("Andmed on uuendatud");
                editingId = null;
                document.querySelector("#addVaraForm button").textContent = "Lisada";
            } else {
                res = await fetch(`${apiUrl}/vara`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(vara)
                });
                if (!res.ok) throw new Error("Ei saanud lisada andmed");
                alert("Andmed on lisatud");
            }

            document.getElementById("addVaraForm").reset();
            fetchVaras();
        } catch (err) {
            alert(err.message);
            console.error(err);
        }
    });


    function editVara(id) {
        const tbody = document.querySelector("#varaTable tbody");
        const row = [...tbody.children].find(r => parseInt(r.children[0].textContent) === id);
        if (!row) return;

        document.getElementById("nimetus").value = row.children[1].textContent.trim();
        document.getElementById("seisund").value = row.children[2].textContent.trim();
        document.getElementById("maksumus").value = parseFloat(row.children[3].textContent) || 0;
        document.getElementById("vastutaja").value = row.children[4].textContent.trim();
        document.getElementById("ostukuupaev").value = row.children[5].textContent.trim();
        document.getElementById("asukoht").value = row.children[6].textContent.trim();
        document.getElementById("markus").value = row.children[7].textContent.trim();

        editingId = id;
        document.querySelector("#addVaraForm button").textContent = "Salvesta muutmine";
    }
    
    async function deleteVara(id) {
        if (!confirm("Kas oled kindel, et soovid selle kirje kustutada?")) return;

        try {
            const res = await fetch(`${apiUrl}/vara/${id}`, { method: "DELETE" });
            if (!res.ok) throw new Error("Ei saanud kustutada andmeid");
            alert("Kirje on kustutatud");
            fetchVaras();
        } catch (err) {
            alert(err.message);
            console.error(err);
        }
    }
</script>

</body>
</html>
