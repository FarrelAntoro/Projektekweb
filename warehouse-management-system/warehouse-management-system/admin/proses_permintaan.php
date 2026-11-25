<?php
require __DIR__ . '/../config/bootstrap.php'; require_admin();
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('/admin/permintaan.php');
verify_csrf();$id=(int)($_POST['id']??0);$aksi=$_POST['aksi']??'';
if($id<1||!in_array($aksi,['approve','reject'],true)){flash('error','Aksi tidak valid.');redirect('/admin/permintaan.php');}
$pdo->beginTransaction();
try{
 $st=$pdo->prepare("SELECT * FROM permintaan WHERE id=? FOR UPDATE");$st->execute([$id]);$p=$st->fetch();
 if(!$p||$p['status']!=='pending')throw new RuntimeException('Permintaan tidak ditemukan atau sudah diproses.');
 if($aksi==='approve'){
  $d=$pdo->prepare("SELECT * FROM detail_permintaan WHERE permintaan_id=?");$d->execute([$id]);$details=$d->fetchAll();
  foreach($details as $item){
   $s=$pdo->prepare("SELECT id,jumlah FROM stok WHERE barang_id=? AND gudang_id=? FOR UPDATE");
   $s->execute([$item['barang_id'],$item['gudang_id']]);$stock=$s->fetch();
   if(!$stock||(int)$stock['jumlah']<(int)$item['jumlah'])throw new RuntimeException('Stok tidak mencukupi untuk salah satu barang. Permintaan belum disetujui.');
   $up=$pdo->prepare("UPDATE stok SET jumlah=jumlah-? WHERE id=?");$up->execute([$item['jumlah'],$stock['id']]);
   $tr=$pdo->prepare("INSERT INTO transaksi_stok(barang_id,gudang_id,user_id,jenis,jumlah,keterangan) VALUES(?,?,?,'keluar',?,?)");
   $tr->execute([$item['barang_id'],$item['gudang_id'],current_user()['id'],$item['jumlah'],'Permintaan #'.$id.' disetujui']);
  }
  $status='approved';
 }else{$status='rejected';}
 $up=$pdo->prepare("UPDATE permintaan SET status=?,diproses_oleh=?,diproses_pada=NOW() WHERE id=?");
 $up->execute([$status,current_user()['id'],$id]);$pdo->commit();
 flash('success',$status==='approved'?'Permintaan disetujui dan stok telah dikurangi.':'Permintaan ditolak.');
}catch(Throwable $e){$pdo->rollBack();flash('error',$e instanceof RuntimeException?$e->getMessage():'Gagal memproses permintaan.');}
redirect('/admin/permintaan.php');
