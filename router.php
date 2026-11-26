<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/';
if (str_starts_with($path,'/storage/')||str_starts_with($path,'/tests/')||$path==='/bootstrap.php'||$path==='/router.php'||str_starts_with($path,'/.')){http_response_code(404);exit('No encontrado');}
$file=__DIR__.$path;
if($path!=='/' && is_file($file))return false;
require __DIR__.'/index.php';
