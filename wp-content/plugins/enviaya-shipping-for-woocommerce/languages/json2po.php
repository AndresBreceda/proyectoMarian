<?php 
$textDomain = 'wc-enviaya-shipping';

$langs = array( 'de_DE','es_AR','es_BO','es_BR','es_BZ','es_CL','es_CO','es_CR','es_CU','es_DO','es_EA','es_EC','es_GQ','es_GT','es_HN','es_IC','es_NI','es_PA','es_PE','es_PR','es_PY','es_SV','es_US','es_UY','es_VE','es_ES');

foreach($langs as $lang){

    echo '<pre>';var_dump( copy($textDomain.'-es_MX.po',$textDomain.'-'.$lang.'.po') );echo '</pre>';
    
}

foreach(glob("*.po") as $k => $v){
   
    exec("msgfmt {$v} -o ".pathinfo($v,PATHINFO_FILENAME).".mo");
}
exit();

exec("msgfmt {$textDomain}-es_ES.po -o {$textDomain}-es_ES.mo");
exec("msgfmt {$textDomain}-es_MX.po -o {$textDomain}-es_MX.mo");

echo '<pre>';var_dump('FINISH');echo '</pre>';exit();


?>