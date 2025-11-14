<?php
$xmlPath = __DIR__ . '/../data/vara.xml';
$jsonPath = __DIR__ . '/../data/vara.json';

if (file_exists($xmlPath)) {
    $xml = simplexml_load_file($xmlPath);
    if ($xml) {
        $data = [];
        foreach ($xml->xpath('//vara') as $v) {
            $data[] = [
                'id' => (string)$v->id,
                'nimetus' => (string)$v->nimetus,
                'seisund' => (string)$v->seisund,
                'maksumus' => (float)$v->maksumus,
                'vastutaja' => (string)$v->vastutaja,
                'ostukuupäev' => (string)$v->ostukuupäev,
                'asukoht' => (string)$v->asukoht,
                'markus' => (string)$v->markus,
            ];
        }
        file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));
    } else {
        echo "<p>Viga</p>";
        $data = [];
    }
} else {
    echo "<p>ei ole faili</p>";
    $data = [];
}

if (file_exists($jsonPath)) {
    $data = json_decode(file_get_contents($jsonPath), true);
} else {
    $data = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nimetus'])) {
    $newVara = [
        'id' => count($data) + 1,
        'nimetus' => $_POST['nimetus'],
        'seisund' => $_POST['seisund'],
        'maksumus' => floatval($_POST['maksumus']),
        'vastutaja' => $_POST['vastutaja'],
        'ostukuupäev' => $_POST['ostukuupaev'],
        'asukoht' => $_POST['asukoht'],
        'markus' => $_POST['markus'],
    ];
    $data[] = $newVara;
    file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));
    echo "<p>Uus vara lisatud!</p>";
}


$filterVastutaja = isset($_GET['vastutaja']) ? trim($_GET['vastutaja']) : "";
$filterPrice = isset($_GET['price']) ? floatval($_GET['price']) : 0;

$filtered = array_filter($data, function($v) use ($filterVastutaja, $filterPrice) {
    if ($filterVastutaja && strtolower($v['vastutaja']) !== strtolower($filterVastutaja)) return false;
    if ($filterPrice > 0 && floatval($v['maksumus']) < $filterPrice) return false;
    return true;
});
?>

<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <title>Varade tabel</title>
    <style>
        table { width:100%; border-collapse: collapse; margin-top:20px; }
        th, td { border:1px solid #444; padding:8px; text-align:left; }
        th { background:#eee; }
        form { margin-top:20px; padding:10px; border:1px solid #aaa; background:#f8f8f8; width:350px; }
        input[type="text"], input[type="number"], input[type="date"] { width:100%; padding:6px; margin-bottom:6px; }
        button { margin-top:10px; padding:8px 12px; }
    </style>
</head>
<body>

<h1>Varade tabel (JSON kaudu)</h1>


<form method="get">
    <label>Vastutaja:</label>
    <input type="text" name="vastutaja" placeholder="Näiteks: Mari" value="<?= htmlspecialchars($filterVastutaja) ?>">

    <label>Minimaalne maksumus (€):</label>
    <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($filterPrice) ?>">

    <button type="submit">Filtreeri</button>
</form>


<h2>Lisa uus vara</h2>
<form method="post">
    <input type="text" name="nimetus" placeholder="Nimetus" required>
    <input type="text" name="seisund" placeholder="Seisund" required>
    <input type="number" name="maksumus" step="0.01" placeholder="Maksumus" required>
    <input type="text" name="vastutaja" placeholder="Vastutaja" required>
    <input type="date" name="ostukuupaev" placeholder="Ostukuupäev">
    <input type="text" name="asukoht" placeholder="Asukoht">
    <input type="text" name="markus" placeholder="Märkus">
    <button type="submit">Lisa vara</button>
</form>

<table>
    <tr>
        <th>ID</th><th>Nimetus</th><th>Seisund</th><th>Maksumus</th><th>Vastutaja</th>
        <th>Ostukuupäev</th><th>Asukoht</th><th>Märkus</th>
    </tr>
    <?php if (empty($filtered)): ?>
        <tr><td colspan="8">Tulemused puuduvad.</td></tr>
    <?php else: ?>
        <?php foreach ($filtered as $v): ?>
            <tr>
                <td><?= $v['id'] ?></td>
                <td><?= htmlspecialchars($v['nimetus']) ?></td>
                <td><?= htmlspecialchars($v['seisund']) ?></td>
                <td><?= $v['maksumus'] ?></td>
                <td><?= htmlspecialchars($v['vastutaja']) ?></td>
                <td><?= htmlspecialchars($v['ostukuupäev']) ?></td>
                <td><?= htmlspecialchars($v['asukoht']) ?></td>
                <td><?= htmlspecialchars($v['markus']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

</body>
</html>
