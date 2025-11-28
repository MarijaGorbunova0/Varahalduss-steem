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
        input, button { padding: 6px; margin: 4px 0; width: 100%; }
        form { max-width: 400px; margin-bottom: 20px; }
    </style>
    <link rel="stylesheet" href="Andmebaas.css">
</head>
<body>

<h1>Varade Haldus</h1>

<div id="loginForm">
    <h2>Login</h2>
    <input type="text" id="username" placeholder="Kasutaja nimi" required>
    <input type="password" id="password" placeholder="Salasõna" required>
    <button id="loginBtn">logi sisse</button>
</div>


<div id="mainContent" style="display:none;">
    <p>Tsau, <span id="currentUser"></span>! roll: <span id="role"></span></p>

    <form id="filterForm">
        <input type="text" id="filterVastutaja" placeholder="filterima Vastutajad">
        <input type="number" id="filterPrice" placeholder="min maksumus">
        <button type="submit">Filtreerima</button>
    </form>

    <table id="varaTable">
        <thead>
        <tr>
            <th>ID</th><th>Nimetus</th><th>Seisund</th><th>Maksumus</th><th>Vastutaja</th>
            <th>Ostukuupäev</th><th>Asukoht</th><th>Märkus</th>
        </tr>
        </thead>
        <tbody>
        <tr><td colspan="8">ei ole andmed</td></tr>
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
    <button id="logoutBtn">logi välja</button>
</div>

<script>
    const apiUrl = "http://localhost:5012/api";


    let currentUser = null;
    let userRole = null;

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
        document.getElementById("loginForm").style.display = "block";
        document.getElementById("mainContent").style.display = "none";
    });

    async function fetchVaras(filterVastutaja = "", filterPrice = 0) {
        try {
            const params = new URLSearchParams();
            params.append("user", currentUser);
            if (userRole === "admin") params.append("admin", "true");

            const res = await fetch(`${apiUrl}/vara?${params.toString()}`);
            if (!res.ok) throw new Error("ei saanud laadida andmed");

            let data = await res.json();

            if (userRole !== "admin") {
                data = data.filter(v => v.vastutaja === currentUser);
            }

            if (filterVastutaja) {
                data = data.filter(v => v.vastutaja.toLowerCase().includes(filterVastutaja.toLowerCase()));
            }
            if (filterPrice > 0) {
                data = data.filter(v => parseFloat(v.maksumus) >= filterPrice);
            }

            const tbody = document.getElementById("varaTable").querySelector("tbody");
            tbody.innerHTML = "";
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8">Ei ole</td></tr>`;
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
        fetchVaras(filterVastutaja, filterPrice);
    });

    document.getElementById("addVaraForm").addEventListener("submit", async (e) => {
        e.preventDefault();

        const newVara = {
            nimetus: document.getElementById("nimetus").value,
            seisund: document.getElementById("seisund").value,
            maksumus: parseFloat(document.getElementById("maksumus").value),
            vastutaja: document.getElementById("vastutaja").value,
            ostukuupaev: document.getElementById("ostukuupaev").value,
            asukoht: document.getElementById("asukoht").value,
            markus: document.getElementById("markus").value
        };

        try {
            const res = await fetch(`${apiUrl}/vara`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(newVara)
            });

            if (!res.ok) throw new Error("ei saanud lisada andmed");

            alert("c");
            fetchVaras();

        } catch (err) {
            alert(err.message);
        }
    });
</script>

</body>
</html>
