<?php

require_once "../config/database.php"; 

use \CfdiUtils\XmlResolver\XmlResolver;
use \CfdiUtils\CadenaOrigen\DOMBuilder;
use \CfdiUtils\Certificado\Certificado;

include 'cd_conector.php';
require __DIR__ . '/../vendor/autoload.php';

//**Ejemplo de ejecución basado en la URL**
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'crearCFDI40':
            crearCFDI40();        
        default:
            echo "Acción no válida.";
    }
} else {
    echo "Bienvenido al sistema de CFDI.";
}


function obtenerSiguienteFolio($db) {
    $query = "SELECT MAX(CAST(FOLIO AS UNSIGNED)) AS maxFolio FROM REPOSITORIOS";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxFolio = isset($result['maxFolio']) ? $result['maxFolio'] : 0;
    return $maxFolio + 1;
}

function crearCFDI40() {
    //echo("CrearCFDI");
    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {

        $rfc = $_POST["RFC"] ?? '';
        $nombre = $_POST["NOMBRE_FAC"] ?? '';
        $regimen = $_POST["REGIMEN_FAC"] ?? '';
        $importe = $_POST["IMPORTE"] ?? '';
        $usuario_creador = $_POST["USUARIO"] ?? '';
        $descripcion = $_POST["NOTAS"] ?? '';
         

        $dbInstance = new Database();
        $db = $dbInstance->getConnection();
        $siguienteFolio = obtenerSiguienteFolio($db);
        $folioStr = str_pad($siguienteFolio, 10, '0', STR_PAD_LEFT);

        $rutaCertificado = dirname(__FILE__) . '/CER_PRUEBAS/CSD_Sucursal_1_EKU9003173C9_20230517_223850.cer.pem';
        //$rutaCertificado = dirname(__FILE__) . '/00001000000507766751.cer.pem';

      // echo $rutaCertificado . "--------------------------------";

        if (!file_exists($rutaCertificado)) {
            throw new Exception('El archivo del certificado no fue encontrado en: ' . $rutaCertificado);
        }
        $certificado = new Certificado($rutaCertificado);
        $iva = $importe * 0.16; 
        $total = $importe + $iva; 

        $comprobanteAtributos = [
            'Version' => '4.0',
            'Serie' => 'WEB',
            'Folio' => $folioStr,
            'Fecha' =>  date('Y-m-d\TH:i:s'),//'2021-01-01T00:00:00',// date('Y-m-d\TH:i:s'),
            'FormaPago' => '99',
            'MetodoPago' => 'PPD',
            'LugarExpedicion' => '27056',
            'Exportacion' => '01',
            'TipoDeComprobante' => 'I',
            'SubTotal' => $importe,
            'Total' => $total,
            'Moneda' => 'MXN',
        ];
        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();
        $comprobante->addEmisor([
            'Nombre' => $nombre,
            'RegimenFiscal' => $regimen,
            'Rfc' => $rfc
        ]);
        $comprobante->addReceptor([
            'Rfc' => 'CLD0507145H6', //$rfcCFDI,
            'Nombre' => 'COMERCIALIZADORA DE LACTEOS Y DERIVADOS', //$nombreCFDI,
            'UsoCFDI' => 'G03',//$usoCFDI,
            'DomicilioFiscalReceptor' => '35079', //$domicilioCFDI,
            'RegimenFiscalReceptor' => '601'//$regimenCFDI
        ]);

        $comprobante->addConcepto([
            'ClaveProdServ' => '78101802',
            'NoIdentificacion' => 'UT421511',
            'Cantidad' => '1',
            'ClaveUnidad' => 'E48',
            'Unidad' => 'Servicio',
            'Descripcion' => empty($descripcion) ? 'Flete' : 'Flete ' . $descripcion,
            'ValorUnitario' => $importe,
            'Importe' => $importe,
            'ObjetoImp' => '02'
        ])->addTraslado([
            'Base' => $importe,
            'Impuesto' => '002',
            'TipoFactor' => 'Tasa',
            'TasaOCuota' => '0.160000',
            'Importe' => $iva 
        ]);

        $comprobante->addImpuestos([
            'TotalImpuestosTrasladados' => $iva
        ])->addTraslado([
            'Base' => $importe,
            'Impuesto' => '002',
            'TipoFactor' => 'Tasa',
            'TasaOCuota' => '0.160000',
            'Importe' => $importe * 0.16 

        ]);

        $rutaKey = file_get_contents(dirname(__FILE__) . '/CER_PRUEBAS/CSD_Sucursal_1_EKU9003173C9_20230517_223850.key.pem');
       // $rutaKey = file_get_contents(dirname(__FILE__) . '/CSD_MATRIZ_STR191030S77_20210614_113652.key.pem');
        //$creator->addSello($rutaKey, '12345678a');
        $creator->addSello($rutaKey, 'anipocino');
        $creator->moveSatDefinitionsToComprobante();

        $rutaGuardado = dirname(__FILE__) . '/xml/';
        
        if (!is_dir($rutaGuardado)) {
            mkdir($rutaGuardado, 0777, true);
        }

        // Generar un ID único (puede ser un timestamp o `uniqid()`)
        $idUnico = uniqid();

        $nombreArchivoXML = $rutaGuardado . $idUnico . "_" . $rfc . ".xml";

        $creator->saveXml($nombreArchivoXML);

        // Llama a la función de timbrado pasando el nombre del archivo
        timbradoCFDI4($nombreArchivoXML, $usuario_creador, $descripcion);
        generarURLFactura($nombreArchivoXML);
    }
}

/*function timbradoCFDI4($nombreXML)
{
    $xml = file_get_contents(dirname(__FILE__).'\\'.$nombreXML);
    //print_r($xml);
    $response = timbrar($xml,"AAA010101AAA","PWD",true);
    if($response["codigo"] == 0){
        // El comprobante se timbro correctamente
        //print_r($response);
        echo 'OK';
        //echo $response["cuerpo"];
    }else{
        // Hubo un problema al timbrar el comprobante
        echo $response["mensaje"] . "\n";
    }
}*/

//-----

// function timbradoCFDI4($nombreXML)
// {
//     // Asegúrate de que el archivo exista antes de intentar leerlo
//     if (!file_exists($nombreXML)) {
//         echo "Error: El archivo XML no existe en la ruta especificada: " . $nombreXML;
//         return;
//     }

//     $xml = file_get_contents($nombreXML);
//     // Proceso de timbrado
//     $response = timbrar($xml, "AAA010101AAA", "PWD", true);
//     if ($response["codigo"] == 0) {
//        // echo 'OK';
//         //print_r($response);
//         $xmlTimbrado = $response["cuerpo"];
//         print_r($xmlTimbrado);
//     } else {
//         echo "Error al timbrar: " . $response["mensaje"] . "\n";
//     }
// }

function timbradoCFDI4($nombreXML, $usuario_creador, $descripcion)
{
    // Asegúrate de que el archivo exista antes de intentar leerlo
    if (!file_exists($nombreXML)) {
        echo "Error: El archivo XML no existe en la ruta especificada: " . $nombreXML;
        return;
    }

    $xml = file_get_contents($nombreXML);

    // Proceso de timbrado
    $response = timbrar($xml, "AAA010101AAA", "PWD", true);
     // $response = timbrar($xml, "STR191030S77", "Uofo5UzoI", false);
    if ($response["codigo"] == 0) {
        $xmlTimbrado = $response["cuerpo"];
        echo($xmlTimbrado);

        // Definir ruta donde se guardará el XML timbrado
        $rutaGuardado = "xml_timbrados/";

        // Crear la carpeta si no existe
        if (!is_dir($rutaGuardado)) {
            mkdir($rutaGuardado, 0777, true);
        }

        $xml = simplexml_load_string($xmlTimbrado); // Cargar el archivo XML
        $namespaces = $xml->getNamespaces(true); // Obtener los espacios de nombres

        // Registrar los espacios de nombres
        $xml->registerXPathNamespace('cfdi', $namespaces['cfdi']);
        $xml->registerXPathNamespace('tfd', $namespaces['tfd']);

        // Realizar la consulta XPath
        foreach ($xml->xpath('//cfdi:Complemento/tfd:TimbreFiscalDigital') as $complemento) {
            $uuid = $complemento['UUID'];
        }

        // Generar nombre del archivo basado en el original
        $nombreArchivoTimbrado = $rutaGuardado . basename($nombreXML, ".xml") . "_". $uuid. "_timbrado.xml";

        echo($nombreArchivoTimbrado);

        // Guardar el archivo timbrado
        if (file_put_contents($nombreArchivoTimbrado, $xmlTimbrado)) {
            //echo "XML timbrado guardado correctamente en: " . $nombreArchivoTimbrado;
            print_r($xmlTimbrado);
           $respuesta =  guardarDatosFacturacion( $nombreArchivoTimbrado, $usuario_creador, $descripcion);

           if ($respuesta["ESTATUS"] == "OK")
           {
            //echo "Datos fiscales guardados con éxito.";
            echo "XML timbrado guardado correctamente en: " . $nombreArchivoTimbrado;
            } else {
                echo "Error al guardar datos fiscales: " . $respuesta["MSG"];
            }
        } else {
            echo "Error: No se pudo guardar el XML timbrado.";
        }
    } else {
        echo "Error al timbrar: " . $response["mensaje"];
    }
}
function guardarDatosFacturacion( $urlArchivo, $usuario_creador, $descripcion) {
    $db = (new Database())->getConnection();

    $diccionario = [
        "ESTATUS" => "OK",
        "MSG" => "DATOS FISCALES GUARDADOS CON ÉXITO."
    ];

    $error = [
        "ESTATUS" => "ERROR",
        "MSG" => "ERROR AL GUARDAR DATOS FISCALES."
    ];

    try
    {
        $db->beginTransaction();
        // XML COMPLETO
       // print_r($xmlTimbrado);

        $xml = simplexml_load_file($urlArchivo); // Cargar el archivo XML
        $namespaces = $xml->getNamespaces(true); // Obtener los espacios de nombres
        //print_r($namespaces);


        foreach ($xml->xpath('//cfdi:Comprobante') as $comprobante) {
            $version = $comprobante['Version'];
            $serie = $comprobante['Serie'];
            $folio = $comprobante['Folio'];
            $fecha_factura = $comprobante['Fecha'];
            $forma_pago = $comprobante['FormaPago'];
            $metodo_pago = $comprobante['MetodoPago'];
            $subtotal = $comprobante['SubTotal'];
            $total = $comprobante['Total'];
        }
        foreach ($xml->xpath('//cfdi:Emisor') as $emisor) {
            // echo "NombreEmisor: " . $emisor['Nombre'] . "<br>";
            // echo "RfcEmisor: " . $emisor['Rfc'] . "<br>";
            // echo "RegimenFiscal: " . $emisor['RegimenFiscal'] . "<br>";

           
            $rfc_emisor = $emisor['Rfc'];

        }
        foreach ($xml->xpath('//cfdi:Receptor') as $receptor) {
            // echo "NombreReceptor: " . $receptor['Nombre'] . "<br>";
            // echo "RfcReceptor: " . $receptor['Rfc'] . "<br>";
            // echo "UsoCFDI: " . $receptor['UsoCFDI'] . "<br>";
            // echo "DomicilioFiscalReceptor: " . $receptor['DomicilioFiscalReceptor'] . "<br>";
            // echo "RegimenFiscalReceptor: " . $receptor['RegimenFiscalReceptor'] . "<br>";


            $nombre_receptor = $receptor['Nombre'];
            $rfc_receptor = $receptor['Rfc'];
            $uso_cfdi = $receptor['UsoCFDI'];
            $domicilio_fiscal_receptor = $receptor['DomicilioFiscalReceptor'];
            $regimen_fiscal_receptor= $receptor['RegimenFiscalReceptor'];
        }

       // Registrar los espacios de nombres
        $xml->registerXPathNamespace('cfdi', $namespaces['cfdi']);
        $xml->registerXPathNamespace('tfd', $namespaces['tfd']);

        // Realizar la consulta XPath
        foreach ($xml->xpath('//cfdi:Complemento/tfd:TimbreFiscalDigital') as $complemento) {
            // echo "UUID: " . $complemento['UUID'] . "<br>";
            // echo "FechaTimbrado: " . $complemento['FechaTimbrado'] . "<br>";
            $uuid = $complemento['UUID'];
            $fecha_tim = $complemento['FechaTimbrado'];
        }

        if ($xml === false) {
            echo "No se pudo interpretar el XML después de la conversión.\n";
            return;
        }

        $fecha_creacion = date("Y-m-d H:i:s");


        if (!empty($fecha_factura) && !empty($rfc_emisor) && !empty($rfc_receptor) && !empty($uuid))
        {
               // Insertar datos en la base de datos
               $sql = "INSERT INTO REPOSITORIOS (
                FECHA_FACTURA, RFC_EMISOR, RFC_RECEPTOR, METODO_PAGO, FORMA_PAGO, SUBTOTAL, TOTAL, SERIE, FOLIO,
                RAZON_SOCIAL_REC, CP_FISCAL_REC, REGIMEN_FISCAL_REC, CERT_SAT, CERT_EMISOR, FECHA_TIMBRADO,
                UUID, USO_CFDI, VERSION, XML, USUARIO_CREADOR, FECHA_HR_CREACION, DESCRIPCION
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $fecha_factura, $rfc_emisor, $rfc_receptor, $metodo_pago, $forma_pago, $subtotal, $total, $serie, $folio, $nombre_receptor, $domicilio_fiscal_receptor,
                $regimen_fiscal_receptor, "", "", $fecha_tim, $uuid, $uso_cfdi, $version, $urlArchivo, $usuario_creador, $fecha_creacion, $descripcion
            ]);

            $db->commit();
            //echo json_encode($diccionario);
            return $diccionario;
        }else
        {
            //echo json_encode(["ESTATUS" => "ERROR", "MSG" => "Datos vacios en el XML."]);
            return $error;
        }

    } catch (Exception $e) {
        $db->rollBack();
        $error['MSG'] .= " Detalle del error: " . $e->getMessage();
        echo json_encode($error);
    }
}

// function generarURLFactura($archivoXML) {

//     $xml = simplexml_load_file($archivoXML); // Cargar el archivo XML
//     $namespaces = $xml->getNamespaces(true); // Obtener los espacios de nombres
//     //print_r($namespaces);


//     foreach ($xml->xpath('//cfdi:Comprobante') as $comprobante) {       
//         $total = $comprobante['Total'];
//         $sello = $comprobante['Sello'];
//     }
//     foreach ($xml->xpath('//cfdi:Emisor') as $emisor) {
//         $rfc_emisor = $emisor['Rfc'];
//     }
//     foreach ($xml->xpath('//cfdi:Receptor') as $receptor) {       
//         $rfc_receptor = $receptor['Rfc'];
//     }

//     // Registrar los espacios de nombres
//     $xml->registerXPathNamespace('cfdi', $namespaces['cfdi']);
//     $xml->registerXPathNamespace('tfd', $namespaces['tfd']);

//     // Realizar la consulta XPath
//     foreach ($xml->xpath('//cfdi:Complemento/tfd:TimbreFiscalDigital') as $complemento) {
//         $uuid = $complemento['UUID'];
//     }


//     $base_url = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?";
//     $query_params = http_build_query([
//         'id' => $uuid,
//         're' => $rfc_emisor,
//         'rr' => $rfc_receptor,
//         'tt' => number_format($total, 2, '.', ''),
//         'fe' => substr($sello, -8) // Últimos 8 caracteres del sello digital
//     ]);

//     echo($base_url . $query_params);
//     return $base_url . $query_params;
// }



?>