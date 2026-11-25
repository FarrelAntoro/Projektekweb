<?php
require __DIR__ . '/../config/bootstrap.php'; require_user();
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $barangId=(int)($_POST['barang_id']??0); $gudangId=(int)($_POST['gudang_id']??0);
    $jumlah=(int)($_POST['jumlah']??0); $catatan=trim($_POST['catatan']??'');
    if($barangId<1 || $gudangId<1 || $jumlah<1) $errors[]='Pilih barang, gudang, dan jumlah minimal 1.';
    else {
        $check=$pdo->prepare("SELECT id FROM barang WHERE id=?"); $check->execute([$barangId]);
        $check2=$pdo->prepare("SELECT id FROM gudang WHERE id=?"); $check2->execute([$gudangId]);
        if(!$check->fetch() || !$check2->fetch()) $errors[]='Barang atau gudang tidak valid.';
        else {
            $pdo->beginTransaction();
            try {
                $stmt=$pdo->prepare("INSERT INTO permintaan (user_id,catatan) VALUES (?,?)");
                $stmt->execute([current_user()['id'],$catatan ?: null]); $requestId=(int)$pdo->lastInsertId();
                $stmt=$pdo->prepare("INSERT INTO detail_permintaan (permintaan_id,barang_id,gudang_id,jumlah) VALUES (?,?,?,?)");
                $stmt->execute([$requestId,$barangId,$gudangId,$jumlah]);
                $pdo->commit(); flash('success','Permintaan berhasil dikirim dan menunggu persetujuan admin.'); redirect('/user/riwayat.php');
            } catch(Throwable $e) { $pdo->rollBack(); $errors[]='Permintaan gagal disimpan.'; }
        }
    }
}
$barang=$pdo->query("SELECT id,kode_barang,nama_barang,satuan FROM barang ORDER BY nama_barang")->fetchAll();
$gudang=$pdo->query("SELECT id,nama_gudang FROM gudang ORDER BY nama_gudang")->fetchAll();
page_header('Buat Permintaan');
?>
<div class="page-heading"><div><span class="eyebrow">DISTRIBUSI</span><h1>Buat Permintaan Barang</h1><p>Permintaan akan diperiksa oleh admin sebelum stok dikeluarkan.</p></div></div>
<div class="form-card">
<?php foreach($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<?php if(!$barang || !$gudang): ?><div class="alert alert-warning">Data barang atau gudang belum tersedia. Hubungi admin.</div><?php else: ?>
<form method="post" class="form-stack"><?= csrf_field() ?>
<label>Barang<select name="barang_id" required><option value="">Pilih barang</option><?php foreach($barang as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['kode_barang'].' — '.$b['nama_barang'].' ('.$b['satuan'].')') ?></option><?php endforeach; ?></select></label>
<label>Gudang<select name="gudang_id" required><option value="">Pilih gudang</option><?php foreach($gudang as $g): ?><option value="<?= (int)$g['id'] ?>"><?= e($g['nama_gudang']) ?></option><?php endforeach; ?></select></label>
<label>Jumlah<input type="number" name="jumlah" min="1" step="1" required></label>
<label>Catatan (opsional)<textarea name="catatan" rows="3" maxlength="1000" placeholder="Tuliskan alasan atau kebutuhan barang"></textarea></label>
<button class="btn btn-primary">Kirim Permintaan</button></form><?php endif; ?></div>
<?php page_footer(); ?>
