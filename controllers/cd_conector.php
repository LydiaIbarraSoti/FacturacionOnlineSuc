<?php

// Este conector requiere la extension PHP-Curl instalada, ya que existen problemas en algunas versiones de PHP referentes a Http Lib

// Parametros
// $xml - String que contiene el CFDI a cancelar (en utf-8)
// $svc_user - Usuario del servicio de timbrado de Comercio Digital
// $svc_pwd  - Contrasena del servicio de Comercio Digital
// $pruebas - true o false, true indica que se utilizara el ambiente de pruebas, false indica que se utilizara el servicio de produccion
function timbrar($xmlData, $svc_user, $svc_pwd, $pruebas){
    if($pruebas){
            $url = "https://pruebas.comercio-digital.mx/timbre4/timbrarv5";
    }else{
        $url = "https://ws.comercio-digital.mx/timbre4/timbrarv5";
    }
    
    $ch = curl_init($url);
    //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva la verificación SSL

    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            "Expect:",
            "Content-Type: text/xml",
            'usrws: '.$svc_user,
            'pwdws: '.$svc_pwd,
            "tipo: XML"
        )
    );     
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HEADER,1);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error en la petición cURL: ' . curl_error($ch);
    }
    
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    list($response_body, $headers) = parse_response($response,$headerSize);
    $codigo = $headers['codigo'];
    $codigo_int = intval($codigo);
    if($codigo_int != 0){
        $mensaje = $headers['errmsg'];
        return array(
            "codigo" => $codigo_int,
            "mensaje" => $mensaje,
            "cuerpo" => "",
			"email"=>""
        );
    }else{
        return array(
            "codigo" => $codigo_int,
            "mensaje" => "",
            "cuerpo" => $response_body,
			"errEmail"=>""
        );
    }   
}
//Funcion que envia a timbrar Facturas Globales con mas de 2 megabyte de peso
//Para el uso de esta funcion es necesario enviar een formato .ZIP el xml.


function timbrarZ($xml, $svc_user, $svc_pwd, $pruebas){
   if($pruebas){
       $url = "https://pruebas.comercio-digital.mx/timbre4/timbrarv5";
    }else{
           $url = "https://ws.comercio-digital.mx/timbre4/timbrarv5";
    }
	
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva la verificación SSL
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            "Expect:",
            "Content-Type: text/plain",
            'usrws: '.$svc_user,
            'pwdws: '.$svc_pwd,
            "tipo: TIMBRE",
			"zip: ZIP"
        )
    );     
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20000);
    curl_setopt($ch, CURLOPT_HEADER,1);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    list($response_body, $headers) = parse_response($response,$headerSize);
    $codigo = $headers['codigo'];
    $codigo_int = intval($codigo);
    if($codigo_int != 0){
        $mensaje = $headers['errmsg'];
        return array(
            "codigo" => $codigo_int,
            "mensaje" => $mensaje,
            "cuerpo" => ""
        );
    }else{
        return array(
            "codigo" => $codigo_int,
            "mensaje" => "",
            "cuerpo" => $response_body
        );
		fclose($temp);
    }   
}

/*
 * Utilizar esta funcion cuando su sistema no sea capaz de generar
 * el XML de cancelacion para cualquiera de los distintos tipos de
 * comprobantes publicados por el SAT
 *
 * Necesitara proveer al servicio de Comercio Digital los
 * Certificados de Sellos Digitales del emisor para poder 
 * crear el XML de cancelacion y posteriormente enviarlo al SAT
 *
 *
 * Parametros de entrada:
 *   rfc (String): RFC que emitio el UUID a cancelar
 *   uuid (String): UUID que sera cancelada
 *   csd_cer (String): Certificado publico (archivo .cer) codificado en base 64 del CSD del contribuyente a realizar la cancelacion
 *   csd_key (String): Llave privada (archivo .key) codificada en base 64 del CSD del contribuyente a realizar la cancelacion
 *   csd_pwd (String): Contrasena del CSD del contribuyente a realizar la cancelacion
 *   svc_user (String): Usuario del servicio de timbrado de Comercio Digital
 *   svc_pwd (String): Contrasena del servicio de timbrado de Comercio Digital
 *   tipo (String): El tipo del comprobante al que corresponde la UUID a cancelar. Los valores predefinidos se definen a continuacion.
 *      valores permitidos:
 *          cfdi3.2
 *          cfdi3.3
 *          reten1.0
 *          reten1.1
 *          pagos1.0
 *          nomina1.0
 *   pruebas (Boolean): Variable booleana que indica si la peticion se enviara al ambiente de pruebas (true) o al ambiente productivo (false)
 *          
 */
function cancelar_csd($rfc, $uuid, $csd_cer, $csd_key, $csd_pwd, $svc_user, $svc_pwd, $tipo, $motivo, $uuidRel, $tipoC, $rfcR, $total, $pruebas){
    if($pruebas){
        $url = "https://pruebas.comercio-digital.mx/cancela4/cancelarUuid";
    }else{
        $url = "https://cancela.comercio-digital.mx/cancela4/cancelarUuid";
    }
    $request = "USER=$svc_user\nPWDW=$svc_pwd\nRFCE=$rfc\nUUID=$uuid\nPWDK=$csd_pwd\nKEYF=$csd_key\nCERT=$csd_cer\nTIPO1=$tipo\nMOTIVO=$motivo\nUUIDREL=$uuidRel\nRFCR=$rfcR\nTIPOC=$tipoC\nTOTAL=$total\nACUS=SI\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            "Expect:",
            "Content-Type: text/plain"
        )
    );     
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HEADER,1);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    list($response_body, $headers) = parse_response($response,$headerSize);
    $codigo = $headers['codigo'];
    $codigo_int = intval($codigo);
    if($codigo_int == 0){
        // El XML de cancelacion fue creado correctamente y enviado al SAT.
        // Aun falta parsear el acuse de cancelacion y evaluar el estado de la cancelacion de la UUID especificamente
        $uuids = parse_ack($response_body);
        return array(
            "codigo" => $codigo_int,
            "mensaje" => "",
			"body" => $request,
            "acuse" => $response_body,
            "uuids" => $uuids
        );
    }else{
        // La peticion no se envio al servicio del SAT ya que hubo un problema en la misma
        $mensaje = $headers['errmsg'];
        return array(
            "codigo" => $codigo_int,
            "mensaje" => $mensaje,
			"body" => $request,
            "acuse" => "",
            "uuids" => array()
        );
    }

}


/*
 * Utilice esta funcion cuando usted tenga la capacidad de generar el XML
 * de cancelacion para los diferentes tipos de Comprobantes Fiscales
 * y prefiera no enviar los Certificados de Sellos Digitales del emisor 
 * al servicio de Comercio Digital
 *
 *
 */
function cancelar_xml($xml, $svc_user, $svc_pwd, $tipo, $emaile, $emailr, $rfcr, $tipoc, $pruebas){
    if($pruebas){
        $url = "https://pruebas.comercio-digital.mx/cancela4/cancelarXml";
    }else{
        $url = "https://cancela.comercio-digital.mx/cancela4/cancelarXml";
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            "Expect:",
            "Content-Type: text/plain",
            "usrws: $svc_user",
            "pwdws: $svc_pwd",
            "tipo: $tipo",
			"emaile: $emaile",
			"emailr: $emailr",
			"rfcr: $rfcr",
			"tipoc: $tipoc"
        )
    );     
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HEADER,1);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    list($response_body, $headers) = parse_response($response,$headerSize);
    $codigo = $headers["codigo"];
    $codigo_int = intval($codigo);
	$mensaje = $headers['errmsg'];
    if($codigo_int == 0){
        // El XML de cancelacion fue validado correctamente y enviado al SAT.
        // Aun falta parsear el acuse de cancelacion y evaluar el estado de la cancelacion de la UUID especificamente
        $uuids = simplexml_load_string($response_body);
        return array(
            "codigo" => $codigo_int,
            "mensaje" => "",
            "acuse" => $response_body,
            "uuids" => $uuids
        );
    }else{
        // La peticion no se envio al servicio del SAT ya que se encontro un problema al validarlo o enviarlo al SAT.
        $mensaje = $headers['errmsg'];
        return array(
            "codigo" => $codigo_int,
            "mensaje" => $mensaje,
            "acuse" => "",
            "uuids" => array()
        );
    }

}


/*
 * Funcion de apoyo para parsear el acuse de cancelacion del SAT
 */

function parse_ack($acuse_string){
        $acuse=new SimpleXMLElement($acuse_string);
        $uuids = array();
        foreach($acuse->Folios as $folio){
            $uuid = (string)$folio->UUID;
            $uuids[$uuid] = (string)$folio->EstatusUUID;
        }
        return $uuids;
}


/*
 * Funcion de apoyo para parsear la respuesta de HTTP regresada por CURL
 */
function parse_response($response, $headerSize){
    $header_text = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    foreach (explode("\r\n", $header_text) as $i => $line)
        if ($i === 0)
            $headers['http_code'] = $line;
        else
        {
			$rep = str_replace("ERROR:","ERROR",$line);
            if(!empty($rep)){
                list ($key, $value) = explode(': ', $rep);
                $headers[$key] = $value;
            }
        }
    return array($body,$headers);
}

?>
