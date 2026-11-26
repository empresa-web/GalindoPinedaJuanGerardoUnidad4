<?php
require __DIR__.'/bootstrap.php';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/';
if($path==='/api/campaigns' && $_SERVER['REQUEST_METHOD']==='GET'){
 $out=[];foreach($campaigns as $c){$c['raised']=total_by_campaign($c['id']);$out[]=$c;} json_out(['success'=>true,'data'=>$out,'count'=>count($out)]);
}
if($path==='/api/stats' && $_SERVER['REQUEST_METHOD']==='GET'){
 $all=pledges();json_out(['success'=>true,'data'=>['campaigns'=>count($campaigns),'pledges'=>count($all),'pledged_mxn'=>array_sum(array_column($all,'amount'))]]);
}
if(str_starts_with($path,'/api/')) json_out(['success'=>false,'error'=>'Endpoint inexistente'],404);
$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST' && $path==='/pledge'){
 if(!verify_csrf()){$error='Solicitud rechazada: token CSRF inválido.';http_response_code(403);}
 else {
   $id=filter_var($_POST['campaign_id']??null,FILTER_VALIDATE_INT);
   $name=trim((string)($_POST['name']??''));$email=trim((string)($_POST['email']??''));
   $amount=filter_var($_POST['amount']??null,FILTER_VALIDATE_INT);
   if(!find_campaign((int)$id)||!$amount||$amount<10||$amount>100000||strlen($name)<3||strlen($name)>100||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>200){$error='Verifica campaña, nombre, correo y monto (entre $10 y $100,000 MXN).';http_response_code(422);}
   elseif(!empty($_SESSION['last_pledge']) && time()-$_SESSION['last_pledge']<5){$error='Espera unos segundos antes de enviar otra aportación.';http_response_code(429);}
   elseif(save_pledge(['id'=>bin2hex(random_bytes(8)),'campaign_id'=>(int)$id,'name'=>$name,'email'=>$email,'amount'=>(int)$amount,'created_at'=>gmdate('c')])){$_SESSION['last_pledge']=time();header('Location: /?ok=1');exit;}
   else {$error='No fue posible guardar la aportación. Verifica los permisos de storage/.';http_response_code(500);}
 }
}
if($_SERVER['REQUEST_METHOD']==='POST' && $path==='/login'){
 if(!verify_csrf()){$error='Token CSRF inválido.';http_response_code(403);}
 elseif(($_SESSION['login_locked_until']??0)>time()){$error='Demasiados intentos. Intenta de nuevo en 60 segundos.';http_response_code(429);}
 else {
  $password=getenv('ADMIN_PASSWORD');
  if($password && hash_equals('admin', (string)($_POST['username']??'')) && password_verify((string)($_POST['password']??''),password_hash($password,PASSWORD_DEFAULT))){session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['login_attempts']=0;header('Location: /admin');exit;}
  $_SESSION['login_attempts']=($_SESSION['login_attempts']??0)+1;
  if($_SESSION['login_attempts']>=5){$_SESSION['login_locked_until']=time()+60;$_SESSION['login_attempts']=0;}
  $error='Usuario o contraseña incorrectos.';http_response_code(401);
 }
}
if($_SERVER['REQUEST_METHOD']==='POST' && $path==='/logout'){
 if(!verify_csrf()){http_response_code(403);exit('CSRF inválido');}
 $_SESSION=[];session_regenerate_id(true);header('Location: /');exit;
}
if($path==='/admin')require_admin();
if(!in_array($path,['/','/pledge','/login','/admin','/logout'],true)){http_response_code(404);$error='Página no encontrada.';}
?><!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Donaciones Solidarias, un proyecto académico demostrativo"><title>Donaciones Solidarias | Unidad 3</title><link rel="stylesheet" href="/assets/style.css"><script src="/assets/app.js" defer></script></head><body>
<header class="top"><div class="wrap nav"><a href="/" class="brand"><span class="brand-icon">✦</span> Donaciones<span>Solidarias</span></a><nav><a href="/#campanas">Campañas</a><a href="/#acerca">Acerca de</a><a href="/#servicios">Web services</a><a href="/admin">Panel</a></nav></div></header>
<main>
<?php if($path==='/login'): ?>
<section class="wrap form-section"><div class="form-card"><p class="eyebrow">ACCESO RESTRINGIDO</p><h1>Panel de administración</h1><p>Accede para consultar las aportaciones de demostración.</p><?php if($error):?><p class="alert error"><?=e($error)?></p><?php endif;?><form method="post" action="/login"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Usuario<input name="username" autocomplete="username" required placeholder="admin"></label><label>Contraseña<input name="password" type="password" autocomplete="current-password" required></label><button>Entrar al panel</button></form></div></section>
<?php elseif($path==='/admin'): $all=pledges(); ?>
<section class="wrap admin"><p class="eyebrow">ÁREA PROTEGIDA</p><div class="adminhead"><div><h1>Panel de aportaciones</h1><p>Registros demostrativos; no se procesan pagos reales.</p></div><form action="/logout" method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><button class="outline">Cerrar sesión</button></form></div><div class="metrics"><div><strong><?=count($all)?></strong><span>Aportaciones registradas</span></div><div><strong>$<?=number_format(array_sum(array_column($all,'amount')))?></strong><span>MXN comprometidos</span></div><div><strong><?=count($campaigns)?></strong><span>Campañas activas</span></div></div><div class="tablewrap"><table><thead><tr><th>Fecha</th><th>Campaña</th><th>Donante</th><th>Correo</th><th>Monto (MXN)</th></tr></thead><tbody><?php foreach(array_reverse($all) as $p):$camp=find_campaign((int)$p['campaign_id']);?><tr><td><?=e(substr($p['created_at'],0,10))?></td><td><?=e($camp['title']??'—')?></td><td><?=e($p['name'])?></td><td><?=e($p['email'])?></td><td>$<?=number_format($p['amount'])?></td></tr><?php endforeach;if(!$all):?><tr><td colspan="5">Todavía no hay aportaciones registradas.</td></tr><?php endif;?></tbody></table></div></section>
<?php else: ?>
<section class="hero"><div class="wrap hero-grid"><div><p class="eyebrow">PROYECTO WEB · UNIDAD 3</p><h1>Pequeñas acciones.<br><em>Grandes cambios.</em></h1><p class="lead">Conecta con causas que importan. Registra un compromiso de apoyo y descubre cómo una comunidad puede generar impacto.</p><a class="btn" href="#campanas">Explorar campañas <span>↗</span></a><p class="disclaimer">Sitio académico. No solicita ni procesa pagos.</p></div><div class="hero-art"><span class="star">✦</span><div class="heart">♥</div><div class="note note1">COMUNIDAD<br><b>+ impacto</b></div><div class="note note2">APOYO<br><b>que transforma</b></div></div></div></section>
<div class="wrap"><?php if(isset($_GET['ok'])):?><div class="alert success" role="status">¡Aportación de demostración registrada correctamente! Gracias por participar.</div><?php endif;?><?php if($error):?><div class="alert error" role="alert"><?=e($error)?></div><?php endif;?></div>
<section class="wrap section" id="campanas"><div class="section-title"><div><p class="eyebrow">NUESTRAS CAUSAS</p><h2>Elige dónde hacer la diferencia</h2></div><p>Tres iniciativas locales con metas claras.</p></div><div class="cards"><?php foreach($campaigns as $c):$raised=total_by_campaign($c['id']);?><article class="campaign"><div class="campaign-art art<?=$c['id']?>"><span><?=$c['emoji']?></span></div><div class="campaign-body"><span class="category"><?=e($c['category'])?></span><h3><?=e($c['title'])?></h3><p><?=e($c['desc'])?></p><div class="progress"><span style="width:<?=min(100,round($raised/$c['goal']*100))?>%"></span></div><div class="money"><strong>$<?=number_format($raised)?></strong><small>de $<?=number_format($c['goal'])?> MXN</small></div><a href="#donar" class="action" data-campaign="<?=$c['id']?>">Quiero apoyar <span>↗</span></a></div></article><?php endforeach;?></div></section>
<section class="donate-bg" id="donar"><div class="wrap donate-grid"><div><p class="eyebrow">PARTICIPA</p><h2>Registra tu compromiso de ayuda</h2><p>Tu aportación se almacena como una intención de apoyo con fines educativos. No se realiza ningún cargo.</p><div class="callout">🔒 Formulario protegido con validaciones, token CSRF y transmisión local/HTTPS según despliegue.</div><div class="exchange"><h3>Conversor informativo de divisas</h3><p>Calcula una equivalencia aproximada usando un servicio externo.</p><div class="exchange-input"><input type="number" id="mxn" value="250" min="1" aria-label="Monto en pesos"><span>MXN</span><span class="equals">≈</span><strong id="usd">Consultando...</strong><span>USD</span></div><small id="fx-status">Fuente: Frankfurter API (tipo de referencia; no es cotización de pago).</small></div></div><div class="form-card"><h3>Formulario de aportación</h3><form method="post" action="/pledge" id="pledge-form"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Campaña<select name="campaign_id" id="campaign" required><?php foreach($campaigns as $c):?><option value="<?=$c['id']?>"><?=e($c['title'])?></option><?php endforeach;?></select></label><label>Nombre completo<input type="text" name="name" minlength="3" maxlength="100" required placeholder="Ej. Ana Martínez"></label><label>Correo electrónico<input type="email" name="email" maxlength="200" required placeholder="correo@ejemplo.com"></label><label>Monto de apoyo (MXN)<input type="number" name="amount" min="10" max="100000" step="1" value="250" required></label><button type="submit">Registrar aportación <span>↗</span></button><small>No se recopilan datos bancarios ni se transfieren fondos.</small></form></div></div></section>
<section class="wrap section service-grid" id="servicios"><div id="acerca"><p class="eyebrow">TRANSPARENCIA TECNOLÓGICA</p><h2>Desarrollado con buenas prácticas</h2><p>Ejemplo académico de una aplicación PHP con controles de seguridad, endpoints REST propios y consumo de una API de divisas de terceros.</p></div><div class="service-box"><h3>API REST propia</h3><p>Consulta información pública estructurada en formato JSON.</p><a href="/api/campaigns" target="_blank" rel="noopener">GET /api/campaigns ↗</a><a href="/api/stats" target="_blank" rel="noopener">GET /api/stats ↗</a></div></section>
<?php endif;?></main><footer><div class="wrap foot"><div><b>✦ DonacionesSolidarias</b><p>Prototipo académico para Desarrollo Web Integral.</p></div><div>Unidad 3 · 2026<br>Sin transacciones reales</div></div></footer></body></html>
