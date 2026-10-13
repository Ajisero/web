<?php
require __DIR__.'/includes/bootstrap.php';
$token=$_GET['token']??''; $order=null; $items=[]; $history=[];
if(installed() && preg_match('/^[a-f0-9]{48}$/',$token)){
 $s=db()->prepare('SELECT * FROM orders WHERE public_token=?'); $s->execute([$token]); $order=$s->fetch();
 if($order){$q=db()->prepare('SELECT * FROM order_items WHERE order_id=?');$q->execute([$order['id']]);$items=$q->fetchAll();$q=db()->prepare('SELECT status,created_at FROM order_status_history WHERE order_id=? ORDER BY id');$q->execute([$order['id']]);$history=$q->fetchAll();}
}
$title='Seguimiento de pedido'; require __DIR__.'/includes/header.php';
$labels=['received'=>'Pedido recibido','awaiting_receipt'=>'Esperando comprobante','receipt_received'=>'Comprobante recibido','paid'=>'Pago confirmado','preparing'=>'Preparando','ready'=>'Listo para entrega','on_the_way'=>'En camino','delivered'=>'Entregado','cancelled'=>'Cancelado'];
?>
<section class="section narrow"><div class="section-head"><span class="eyebrow">SEGUIMIENTO</span><h1>Tu pedido</h1></div><?php if(!$order): ?><div class="empty-state"><h2>No encontramos ese pedido</h2><p>Revisa el enlace o contáctanos por WhatsApp.</p></div><?php else: ?><div class="tracking-card"><div><span>Pedido</span><h2>#<?=h($order['public_id'])?></h2></div><div class="status-pill"><?=h($labels[$order['status']]??$order['status'])?></div><div class="progress-list"><?php foreach($history as $hrow): ?><div class="progress-item"><span>✓</span><div><b><?=h($labels[$hrow['status']]??$hrow['status'])?></b><small><?=h($hrow['created_at'])?></small></div></div><?php endforeach; ?></div><hr><h3>Resumen</h3><?php foreach($items as $i): ?><div class="line"><span><?=h($i['product_name'])?> × <?=h((string)$i['quantity'])?></span><b><?=money((float)$i['line_total'])?></b></div><?php endforeach; ?><div class="line"><span>Delivery</span><b><?=money((float)$order['delivery_cost'])?></b></div><div class="line grand"><span>Total</span><b><?=money((float)$order['total'])?></b></div></div><?php endif; ?></section>
<?php require __DIR__.'/includes/footer.php'; ?>
