<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

try {
    $pdo = database();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string)($_POST['csrf'] ?? '');
        if (!hash_equals($_SESSION['csrf'], $token)) {
            throw new RuntimeException('Sesi formulir tidak valid. Muat ulang halaman.');
        }

        $action = (string)($_POST['action'] ?? '');

        if ($action === 'add') {
            $sku = trim((string)($_POST['sku'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);

            if ($sku === '' || $name === '' || $stock === false || $stock === null || $stock < 0) {
                throw new RuntimeException('Isi kode barang, nama barang, dan stok dengan benar.');
            }

            $statement = $pdo->prepare('INSERT INTO items (sku, name, stock) VALUES (:sku, :name, :stock)');
            $statement->execute([':sku' => $sku, ':name' => $name, ':stock' => $stock]);
            $success = 'Barang berhasil ditambahkan.';
        } elseif ($action === 'adjust') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $change = filter_var($_POST['change'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $change === false || $change === null || $change === 0) {
                throw new RuntimeException('Perubahan stok tidak valid.');
            }

            $statement = $pdo->prepare('UPDATE items SET stock = stock + :change, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND stock + :change >= 0');
            $statement->execute([':change' => $change, ':id' => $id]);
            if ($statement->rowCount() === 0) {
                throw new RuntimeException('Stok tidak boleh negatif atau barang tidak ditemukan.');
            }
            $success = 'Stok berhasil diperbarui.';
        }
    }

    $items = $pdo->query('SELECT id, sku, name, stock FROM items ORDER BY name COLLATE NOCASE')->fetchAll();
    $totalItems = count($items);
    $totalStock = array_sum(array_column($items, 'stock'));
} catch (Throwable $exception) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = $exception instanceof PDOException && str_contains($exception->getMessage(), 'UNIQUE')
            ? 'Kode barang sudah digunakan.'
            : $exception->getMessage();
    } else {
        $error = $exception->getMessage();
    }
    // Tetap baca isi database walaupun proses POST gagal.
    // Browser mungkin mengirim ulang formulir yang sebenarnya sudah tersimpan.
    try {
        $pdo = $pdo ?? database();
        $items = $pdo->query('SELECT id, sku, name, stock FROM items ORDER BY name COLLATE NOCASE')->fetchAll();
    } catch (Throwable $readException) {
        $items = [];
        $error .= ' (Data gagal dibaca: ' . $readException->getMessage() . ')';
    }
    $totalItems = count($items);
    $totalStock = array_sum(array_column($items, 'stock'));
}

function e(string|int $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#173b35">
    <title>Mini Inventory — Uji PHP + SQL</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand-mark">MI</div>
    <div><strong>Mini Inventory</strong><small>PHP + SQLite workflow test</small></div>
    <span class="status"><i></i> Local</span>
</header>

<main class="container">
    <section class="intro">
        <p class="eyebrow">PROOF OF CONCEPT</p>
        <h1>Inventaris sederhana,<br><span>semua tersimpan lokal.</span></h1>
        <p>Uji alur GitHub → Git Pull → PHP → SQLite → Preview Android.</p>
    </section>

    <?php if ($success !== ''): ?><div class="notice success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>

    <section class="stats">
        <article class="stat-card"><span>Jenis barang</span><strong><?= e($totalItems) ?></strong></article>
        <article class="stat-card"><span>Total stok</span><strong><?= e($totalStock) ?></strong></article>
    </section>

    <section class="panel">
        <div class="section-heading"><div><p class="eyebrow">DATA BARU</p><h2>Tambah barang</h2></div><span class="step">01</span></div>
        <form method="post" class="item-form">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="add">
            <label>Kode barang<input name="sku" maxlength="40" placeholder="BRG-001" required></label>
            <label>Nama barang<input name="name" maxlength="120" placeholder="Contoh: Masker medis" required></label>
            <label>Stok awal<input name="stock" type="number" min="0" step="1" value="0" required></label>
            <button class="button primary" type="submit">+ Simpan barang</button>
        </form>
    </section>

    <section class="panel">
        <div class="section-heading"><div><p class="eyebrow">DATABASE SQLITE</p><h2>Daftar barang</h2></div><span class="step">02</span></div>
        <?php if (!$items): ?>
            <div class="empty"><strong>Belum ada barang</strong><p>Tambahkan barang pertama melalui formulir di atas.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Barang</th><th>Stok</th><th>Penyesuaian</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= e($item['name']) ?></strong><small><?= e($item['sku']) ?></small></td>
                        <td><span class="stock"><?= e($item['stock']) ?></span></td>
                        <td>
                            <form method="post" class="adjust-form">
                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                <input type="hidden" name="action" value="adjust">
                                <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                <button name="change" value="-1" type="submit" aria-label="Kurangi stok">−</button>
                                <button name="change" value="1" type="submit" aria-label="Tambah stok">+</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </section>
    <footer>Mini Inventory · Data disimpan di <code>database/inventory.sqlite</code></footer>
</main>
</body>
</html>
