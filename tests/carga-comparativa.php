<?php
// Mismo generador, ruta y concurrencia para ambas fases; sin credenciales.
$url='http://frontend:8080/health/ready';
if(($argv[1]??'')==='worker'){
 $end=(float)$argv[2];$file=$argv[3];$ok=0;$bad=0;$ms=[];
 $context=stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true,'header'=>'Connection: close']]);
 while(microtime(true)<$end){
  $start=microtime(true);$body=@file_get_contents($url,false,$context);$headers=$http_response_header??[];
  $code=preg_match('~^HTTP/\S+ (\d+)~',$headers[0]??'',$m)?(int)$m[1]:0;
  if($code===200&&($body!==false)&&((json_decode($body,true)['status']??'')==='ok')){$ok++;$ms[]=(microtime(true)-$start)*1000;}else{$bad++;}
 }
 file_put_contents($file,json_encode(['ok'=>$ok,'bad'=>$bad,'latencies'=>$ms]));exit;
}
$seconds=(int)($argv[1]??300);$users=(int)($argv[2]??24);$start=microtime(true);$processes=[];
for($i=0;$i<$users;$i++){
 $file='/tmp/load-'.$i.'.json';$pipes=[];
 $proc=proc_open([PHP_BINARY,__FILE__,'worker',(string)($start+$seconds),$file],[0=>['pipe','r'],1=>['file','/tmp/worker.log','a'],2=>['file','/tmp/worker.log','a']],$pipes);
 if(!is_resource($proc))throw new RuntimeException('No se pudo crear un trabajador');fclose($pipes[0]);$processes[]=[$proc,$file];
}
$ok=0;$bad=0;$ms=[];
foreach($processes as [$proc,$file]){if(proc_close($proc)!==0)throw new RuntimeException('Trabajador fallo');$row=json_decode(file_get_contents($file),true);$ok+=$row['ok'];$bad+=$row['bad'];$ms=array_merge($ms,$row['latencies']);}
sort($ms);$elapsed=microtime(true)-$start;$total=$ok+$bad;
echo json_encode(['ruta'=>$url,'concurrencia'=>$users,'duracion_seg'=>round($elapsed,3),'solicitudes'=>$total,'correctas'=>$ok,'fallos'=>$bad,'error_pct'=>$total?round($bad/$total*100,4):null,'req_seg'=>round($total/$elapsed,3),'p95_ms'=>$ms?round($ms[(int)ceil(count($ms)*0.95)-1],3):null],JSON_PRETTY_PRINT).PHP_EOL;
