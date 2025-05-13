<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/*
 * This is a wrapper file that will load the original template but with a fix for the Array to string conversion error.
 * Save this file as patch.php in the same directory as your BoletoCef.php file.
 */

// First, capture the original $consulta variable
$original_consulta = $consulta;

// Create a safe consulta array with the structure expected by the template
$consulta = [
    'CONTROLE_NEGOCIAL' => [
        'COD_RETORNO' => '1' // Default to "needs registration"
    ]
];

// Check if original consulta is an array with the expected structure
if (is_array($original_consulta) && 
    isset($original_consulta['CONTROLE_NEGOCIAL']) && 
    isset($original_consulta['CONTROLE_NEGOCIAL']['COD_RETORNO'])) {
    
    // Get the actual return code
    $consulta['CONTROLE_NEGOCIAL']['COD_RETORNO'] = $original_consulta['CONTROLE_NEGOCIAL']['COD_RETORNO'];
    
    // If consulta has CONSULTA_BOLETO structure, copy that too
    if (isset($original_consulta['CONSULTA_BOLETO']) && is_array($original_consulta['CONSULTA_BOLETO'])) {
        $consulta['CONSULTA_BOLETO'] = $original_consulta['CONSULTA_BOLETO'];
    }
}

// Now include the original template which will use our sanitized $consulta variable
include __DIR__ . '/../../templates/BoletoCef_template.php';
?>