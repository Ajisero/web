<?php
declare(strict_types=1); require __DIR__.'/../includes/bootstrap.php'; header('Content-Type: application/json; charset=utf-8');
function out(array $a,int $code=200){http_response_code($code);echo json_encode($a,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST') out(['ok'=>false,'message'=>'Método no permitido'],405);
if(!installed()) out(['ok'=>false,'message'=>'Primero instala la aplicación desde /install/.'],503);
try{
 verify_csrf($_POST['csrf']??null);
 $name=trim($_POST['customer_name']??''); $wa=trim($_POST['whatsapp']??'');
 if(mb_strlen($name)<3) throw new RuntimeException('Ingresa tu nombre completo.');
 $digits=preg_replace('/\D+/','',$wa); if(strlen($digits)<7||strlen($digits)>15) throw new RuntimeException('Ingresa un WhatsApp válido.');
 $cart=json_decode($_POST['cart_json']??'[]',true); if(!is_array($cart)||!$cart) throw new RuntimeException('Tu carrito está vacío.');
 $zoneId=(int)($_POST['delivery_zone_id']??0); $q=db()->prepare('SELECT * FROM delivery_zones WHERE id=? AND active=1');$q->execute([$zoneId]);$zone=$q->fetch();if(!$zone) throw new RuntimeException('Selecciona una opción de entrega.');
 $address=trim($_POST['address']??''); if(stripos($zone['name'],'Retiro')===false && $address==='') throw new RuntimeException('Ingresa la dirección de entrega.');
 $productStmt=db()->prepare('SELECT id,name,price,stock,active FROM products WHERE id=?'); $items=[];$subtotal=0;
 foreach($cart as $row){$pid=(int)($row['id']??0);$qty=max(1,min(50,(int)($row['qty']??0)));$productStmt->execute([$pid]);$p=$productStmt->fetch();if(!$p||!$p['active'])throw new RuntimeException('Uno de los productos ya no está disponible.'); if($p['stock']!==null && (int)$p['stock']<$qty)throw new RuntimeException('No hay stock suficiente de '.$p['name'].'.');$line=(float)$p['price']*$qty;$subtotal+=$line;$items[]=['id'=>$p['id'],'name'=>$p['name'],'price'=>(float)$p['price'],'qty'=>$qty,'line'=>$line];}
 $delivery=(int)$zone['requires_quote']===1?0:(float)$zone['price'];$total=$subtotal+$delivery;
 $facade=upload_file($_FILES['facade']??[], 'facades',['jpg','jpeg','png','webp']);
 $receipt=upload_file($_FILES['receipt']??[], 'receipts',['jpg','jpeg','png','webp','pdf']);
 $status=$receipt?'receipt_received':'awaiting_receipt';$publicId=public_order_id();$token=public_token();
 $pdo=db();$pdo->beginTransaction();
 $ins=$pdo->prepare('INSERT INTO orders(public_id,public_token,customer_name,whatsapp,address,reference_text,lat,lng,facade_path,payment_method,receipt_path,subtotal,delivery_zone_id,delivery_label,delivery_cost,total,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
 $ins->execute([$publicId,$token,$name,$digits,$address,trim($_POST['reference']??''),($_POST['lat']??'')?:null,($_POST['lng']??'')?:null,$facade,$_POST['payment_method']??'qr',$receipt,$subtotal,$zoneId,$zone['name'],$delivery,$total,$status]);$oid=(int)$pdo->lastInsertId();
 $it=$pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,unit_price,quantity,line_total) VALUES(?,?,?,?,?,?)');foreach($items as $i)$it->execute([$oid,$i['id'],$i['name'],$i['price'],$i['qty'],$i['line']]);
 $hs=$pdo->prepare('INSERT INTO order_status_history(order_id,status,note,created_at) VALUES(?,?,?,NOW())');$hs->execute([$oid,'received','Pedido creado']);if($status!=='received')$hs->execute([$oid,$status,$receipt?'Comprobante adjuntado':'Pendiente de comprobante']);
 $pdo->commit();
 $tracking='tracking.php?token='.$token;$businessWa=setting('whatsapp','59170000000');$message="Hola, realicé el pedido {$publicId}.\nTotal: ".money($total).".\nMétodo: QR.\nSeguimiento: ".$tracking;
 out(['ok'=>true,'public_id'=>$publicId,'token'=>$token,'tracking_url'=>$tracking,'whatsapp_url'=>whatsapp_url($businessWa,$message)]);
}catch(Throwable $e){if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();out(['ok'=>false,'message'=>$e->getMessage()],422);}
