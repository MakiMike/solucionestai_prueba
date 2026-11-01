<?php
$data=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
foreach($data['users'] as $email=>$hash) {
    $matches=array();
    foreach($data['candidates'] as $label=>$password) if(password_verify($password,$hash)) $matches[]=$label;
    echo $email.': '.($matches ? implode(',',$matches) : 'sin coincidencia').PHP_EOL;
}
