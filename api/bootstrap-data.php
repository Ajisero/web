<?php
require __DIR__.'/../includes/bootstrap.php'; header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true,'installed'=>installed(),'products'=>products(),'delivery_zones'=>delivery_zones(),'settings'=>['whatsapp'=>setting('whatsapp','59170000000'),'qr_image'=>setting('qr_image','assets/img/qr-demo.webp'),'business_lat'=>setting('business_lat',''),'business_lng'=>setting('business_lng',''),'pickup_address'=>setting('pickup_address','Doble Vía La Guardia, km 20 (referencia; configurar dirección exacta)')]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
