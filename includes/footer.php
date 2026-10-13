</main>
<footer class="footer"><div><b>Escabeche Don Edgar</b><p>Ajisero artesanal · Santa Cruz, Bolivia</p></div><div><a href="privacy.php">Privacidad</a> · <a href="terms.php">Términos</a></div><small>© <?=date('Y')?> Escabeche Don Edgar</small></footer>
<a class="wa-float" target="_blank" rel="noopener" href="<?=h(whatsapp_url(setting('whatsapp','59170000000'),'Hola, quiero hacer un pedido de Escabeche Don Edgar.'))?>">WhatsApp</a>
<script>window.APP={installed:<?=installed()?'true':'false'?>,csrf:<?=json_encode(csrf_token())?>};</script><script src="assets/js/app.js" defer></script></body></html>
