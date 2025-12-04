<?php
function xmlToArray(SimpleXMLElement $xml) {
    $arr = [];
    foreach ($xml->attributes() as $k => $v) {
        $arr["@" . $k] = (string)$v;
    }
    foreach ($xml->children() as $k => $child) {
        $value = xmlToArray($child);
        if (isset($arr[$k])) {
            if (!is_array($arr[$k]) || !isset($arr[$k][0])) {
                $arr[$k] = [$arr[$k]];
            }
            $arr[$k][] = $value;
        } else {
            $arr[$k] = $value;
        }
    }
    if (!$xml->children() && !$xml->attributes()) return (string)$xml;
    return $arr;
}

$xmlPath  = __DIR__ . '/../data/vara.xml';
$jsonPath = __DIR__ . '/../data/vara.json';

if (file_exists($xmlPath) && !file_exists($jsonPath)) {
    $xml = simplexml_load_file($xmlPath);
    if ($xml) {
        $xmlArray = xmlToArray($xml);
        file_put_contents($jsonPath, json_encode($xmlArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

$json = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $currentMaxId = 0;
    if (isset($json["vara"]) && is_array($json["vara"])) {
        foreach ($json["vara"] as $item) {
            if (isset($item["id"]) && intval($item["id"]) > $currentMaxId) {
                $currentMaxId = intval($item["id"]);
            }
        }
    }
    $newId = $currentMaxId + 1;
    $newItem = [
        "id" => $newId,
        "nimetus" => $_POST["nimetus"] ?? "",
        "seisund" => [
            "kirjeldus" => $_POST["seisund"] ?? "",
            "ostukuupäev" => [
                "@kuupäev"  => $_POST["ostukuupäev"] ?? "",
                "maksumus"  => $_POST["maksumus"] ?? ""
            ]
        ],
        "vastutaja" => $_POST["vastutaja"] ?? "",
        "asukoht"   => $_POST["asukoht"] ?? "",
        "markus"    => $_POST["markus"] ?? ""
    ];
    $json["vara"][] = $newItem;
    file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$data = [];
$rows = $json["vara"] ?? [];
foreach ($rows as $v) {
    $data[] = [
        "id"          => $v["id"] ?? "",
        "nimetus"     => $v["nimetus"] ?? "",
        "seisund"     => $v["seisund"]["kirjeldus"] ?? "",
        "maksumus"    => $v["seisund"]["ostukuupäev"]["maksumus"] ?? "",
        "vastutaja"   => $v["vastutaja"] ?? "",
        "ostukuupäev" => $v["seisund"]["ostukuupäev"]["@kuupäev"] ?? "",
        "asukoht"     => $v["asukoht"] ?? "",
        "markus"      => $v["markus"] ?? ""
    ];
}

$filterType      = $_GET['filterType'] ?? 'none';
$filterVastutaja = trim($_GET['vastutaja'] ?? "");
$filterPrice     = floatval($_GET['price'] ?? 0);

$filtered = array_filter($data, function($v) use ($filterType, $filterVastutaja, $filterPrice) {
    if ($filterType === "none") return true;
    if ($filterType === "vastutaja") {
        return $filterVastutaja !== "" && strtolower($v['vastutaja']) === strtolower($filterVastutaja);
    }
    if ($filterType === "price") {
        return $filterPrice > 0 && floatval($v['maksumus']) >= $filterPrice;
    }
    return true;
});
?>
<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <title>Varade tabel (XML → JSON)</title>
    <style>
        table { width:100%; border-collapse: collapse; margin-top:20px; }
        th, td { border:1px solid #444; padding:8px; text-align:left; }
        th { background:#eee; }
        form { margin-top:20px; padding:10px; border:1px solid #aaa; background:#f8f8f8; width:350px; }
        input[type="text"], input[type="number"], input[type="date"], select { width:100%; padding:6px; margin-bottom:6px; }
        button { margin-top:10px; padding:8px 12px; }
    </style>
</head>
<body>

<h1>Varade tabel (loetud XML → JSON)</h1>

<form method="get">
    <label>Filtri tüüp:</label>
    <select name="filterType" id="filterType">
        <option value="none" <?= ($filterType === "none") ? "selected" : "" ?>>— vali —</option>
        <option value="vastutaja" <?= ($filterType === "vastutaja") ? "selected" : "" ?>>Vastutaja</option>
        <option value="price" <?= ($filterType === "price") ? "selected" : "" ?>>Maksumus</option>
    </select>

    <div id="vastutajaBlock" style="display:none;">
        <label>Vastutaja:</label>
        <input type="text" name="vastutaja" value="<?= htmlspecialchars($filterVastutaja) ?>">
    </div>

    <div id="priceBlock" style="display:none;">
        <label>Minimaalne maksumus (€):</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($filterPrice) ?>">
    </div>

    <button type="submit">Filtreeri</button>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>Nimetus</th>
        <th>Seisund</th>
        <th>Maksumus</th>
        <th>Vastutaja</th>
        <th>Ostukuupäev</th>
        <th>Asukoht</th>
        <th>Märkus</th>
    </tr>

    <?php if(empty($filtered)): ?>
        <tr><td colspan="8">Tulemused puuduvad.</td></tr>
    <?php else: ?>
        <?php foreach ($filtered as $v): ?>
            <tr>
                <td><?= $v['id'] ?></td>
                <td><?= htmlspecialchars($v['nimetus']) ?></td>
                <td><?= htmlspecialchars($v['seisund']) ?></td>
                <td><?= $v['maksumus'] ?> €</td>
                <td><?= htmlspecialchars($v['vastutaja']) ?></td>
                <td><?= htmlspecialchars($v['ostukuupäev']) ?></td>
                <td><?= htmlspecialchars($v['asukoht']) ?></td>
                <td><?= htmlspecialchars($v['markus']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<h2>Lisa uus vara</h2>
<form method="post" style="border:1px solid #aaa; padding:15px; width:350px; background:#f0f0f0;">
    <input type="hidden" name="add" value="1">
    <label>Nimetus:</label>
    <input type="text" name="nimetus" required>
    <label>Seisund (kirjeldus):</label>
    <input type="text" name="seisund" required>
    <label>Maksumus (€):</label>
    <input type="number" step="0.01" name="maksumus" required>
    <label>Ostukuupäev:</label>
    <input type="date" name="ostukuupäev" required>
    <label>Vastutaja:</label>
    <input type="text" name="vastutaja" required>
    <label>Asukoht:</label>
    <input type="text" name="asukoht">
    <label>Märkus:</label>
    <input type="text" name="markus">
    <button type="submit">Lisa</button>
</form>

<script>
    function updateVisibility() {
        let type = document.getElementById("filterType").value;
        document.getElementById("vastutajaBlock").style.display = (type === "vastutaja") ? "block" : "none";
        document.getElementById("priceBlock").style.display = (type === "price") ? "block" : "none";
    }
    updateVisibility();
    document.getElementById("filterType").addEventListener("change", updateVisibility);
</script>

</body>
</html>
