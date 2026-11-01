<?php
$r=[];foreach(['postgres'=>5432,'mongo'=>27017] as $host=>$port){$ip=gethostbyname($host);$s=@fsockopen($host,$port,$errno,$err,2);$r[$host]=['dns'=>$ip!==$host,'tcp'=>(bool)$s];if($s)fclose($s);}echo json_encode($r);
