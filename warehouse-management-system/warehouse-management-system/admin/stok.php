<?php
require __DIR__ . '/../config/bootstrap.php'; require_admin(); $errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$barang=(int)($_POST['barang_id']??0);$gudang=(int)($_POST['gudang_id']??0);$jumlah=(int)($_POST['jumlah']??0);$jenis=$_POST['jenis']??'masuk';$ket=trim($_POST['keterangan']??'');
 if($barang<1||$gudang<1||$jumlah<1||!in_array($jenis,['masuk','keluar'],true))$errors[]='Data transaksi stok tidak valid.';
 else{$pdo->beginTransaction();try{
  $st=$pdo->prepare("SELECT id,jumlah FROM stok WHERE barang_id=? AND gudang_id=? FOR UPDATE");$st->execute([$barang,$gudang]);$row=$st->fetch();
  if(!$row && $jenis==='keluar')throw new RuntimeException('Stok belum tersedia.');
  if($jenis==='masuk'){
   if($row){$up=$pdo->prepare("UPDATE stok SET jumlah=jumlah+? WHERE id=?");$up->execute([$jumlah,$row['id']]);}
   else{$up=$pdo->prepare("INSERT INTO stok(barang_id,gudang_id,jumlah) VALUES(?,?,?)");$up->execute([$barang,$gudang,$jumlah]);}
  }else{
   if((int)$row['jumlah']<$jumlah)throw new RuntimeException('Stok tidak mencukupi untuk transaksi keluar.');
   $up=$pdo->prepare("UPDATE stok SET jumlah=jumlah-? WHERE id=?");$up->execute([$jumlah,$row['id']]);
  }
  $tr=$pdo->prepare("INSERT INTO transaksi_stok(barang_id,gudang_id,user_id,jenis,jumlah,keterangan) VALUES(?,?,?,?,?,?)");
  $tr->execute([$barang,$gudang,current_user()['id'],$jenis,$jumlah,$ket?:'Penyesuaian stok oleh admin']);
  $pdo->commit();flash('success','Transaksi stok berhasil dicatat.');redirect('/admin/stok.php');
 }catch(Throwable $e){$pdo->rollBack();$errors[]=$e instanceof RuntimeException?$e->getMessage():'Transaksi stok gagal.';}}}
$barang=$pdo->query("SELECT id,nama_barang,kode_barang FROM barang ORDER BY nama_barang")->fetchAll();$gudang=$pdo->query("SELECT id,nama_gudang FROM gudang ORDER BY nama_gudang")->fetchAll();
$rows=$pdo->query("SELECT s.jumlah,b.kode_barang,b.nama_barang,b.satuan,g.nama_gudang FROM stok s JOIN barang b ON b.id=s.barang_id JOIN gudang g ON g.id=s.gudang_id ORDER BY b.nama_barang,g.nama_gudang")->fetchAll();
$logs=$pdo->query("SELECT t.*,b.nama_barang,g.nama_gudang FROM transaksi_stok t JOIN barang b ON b.id=t.barang_id JOIN gudang g ON g.id=t.gudang_id ORDER BY t.id DESC LIMIT 15")->fetchAll();
page_header('Manajemen Stok');
?>
<div class="page-heading">
    <div>
        <span class="eyebrow">INVENTARIS</span>
        <h1>Manajemen Stok</h1>
        <p>Catat stok masuk dan stok keluar secara manual.</p>
    </div>
</div>
<?php foreach($errors as $x): ?>
<div class="alert alert-error"><?= e($x) ?></div><?php endforeach; ?>
<div class="form-card">
    <h2>Catat Transaksi Stok</h2>
    <form method="post" class="form-grid"><?= csrf_field() ?>
        <label>Barang<select name="barang_id" required="required">
                <option value="">Pilih barang</option><?php foreach($barang as $b): ?>
                <option value="<?= (int)$b['id'] ?>"><?= e($b['kode_barang'].' — '.$b['nama_barang']) ?></option><?php endforeach; ?></select>
        </label>
        <label>Gudang<select name="gudang_id" required="required">
                <option value="">Pilih gudang</option><?php foreach($gudang as $g): ?>
                <option value="<?= (int)$g['id'] ?>"><?= e($g['nama_gudang']) ?></option><?php endforeach; ?></select>
        </label>
        <label>Jenis<select name="jenis">
                <option value="masuk">Stok Masuk</option>
                <option value="keluar">Stok Keluar</option>
            </select>
        </label>
        <label>Jumlah<input type="number" name="jumlah" min="1" required="required"></label>
        <label class="span-2">Keterangan<input name="keterangan" maxlength="255"></label>
        <div class="form-actions">
            <button class="btn btn-primary">Simpan Transaksi</button>
        </div>
    </form>
</div>
<div class="section-heading">
    <h2>Posisi Stok</h2>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Barang</th>
                <th>Gudang</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody><?php foreach($rows as $r): ?>
            <tr>
                <td><?= e($r['kode_barang']) ?></td>
                <td><?= e($r['nama_barang']) ?></td>
                <td><?= e($r['nama_gudang']) ?></td>
                <td><?= (int)$r['jumlah'] ?>
                    <?= e($r['satuan']) ?></td>
            </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="section-heading">
    <h2>Riwayat Transaksi Stok</h2>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Barang</th>
                <th>Gudang</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody><?php foreach($logs as $r): ?>
            <tr>
                <td><?= e($r['tanggal']) ?></td>
                <td><?= e($r['nama_barang']) ?></td>
                <td><?= e($r['nama_gudang']) ?></td>
                <td><?= e(strtoupper($r['jenis'])) ?></td>
                <td><?= (int)$r['jumlah'] ?></td>
                <td><?= e($r['keterangan']) ?></td>
            </tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php page_footer(); ?>