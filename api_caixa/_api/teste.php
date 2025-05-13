<?php


    require_once '../_validator/Validator.class.php';
    require_once 'Integra.class.php';

    $int = new Integra();

    //$retorno = $int->RegistraBoletoCaixa('14000000000000001', '2023-01-25', '3.00', '1', '2023-01-18', 'Lourival marques', '04317159120');
    $retorno = $int->ConsultaBoletoCaixa('14000000000000001');
    if($retorno['CONTROLE_NEGOCIAL']['COD_RETORNO'] == '1'){
        echo 'sem registro';
    }else{
        echo 'com registro';
    }
    echo '<pre>';
    print_r($retorno);
