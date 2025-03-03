<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Facturas_model extends CI_Model {
 public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    

    public function consultar_facturas($fecha_inicio, $fecha_final) {
        $SERVIDOR =  $_SERVER['HTTP_HOST'] ;
        
        $this->db->select("FECHA_HR_CREACION as fecha_factura, RFC_EMISOR as rfc_emisor, RFC_RECEPTOR as rfc_receptor, RAZON_SOCIAL_REC as razon_social_receptor, CP_FISCAL_REC as domicilio_fiscal_receptor, REGIMEN_FISCAL_REC as regimen_fiscal_receptor, CERT_SAT as cert_sat, CERT_EMISOR as cert_emisor, FECHA_TIMBRADO as fecha_tim, UUID as uuid, SUBTOTAL as subtotal, TOTAL as total, SERIE as serie, FOLIO as folio, METODO_PAGO as metodo_pago, FORMA_PAGO as forma_pago, USO_CFDI as uso_cfdi, XML as ruta_xml, DESCRIPCION, USUARIO_CREADOR");
        $this->db->from('REPOSITORIOS');
        $this->db->where('DATE(FECHA_HR_CREACION) >=', $fecha_inicio);
        $this->db->where('DATE(FECHA_HR_CREACION) <=', $fecha_final);
        $query = $this->db->get();

        $facturas = $query->result_array();

        $facturas = $query->result_array();
    $path_base = $_SERVER['DOCUMENT_ROOT'] . "/controllers/";
    $url_base = "https://" . $_SERVER['HTTP_HOST'] . "/controllers/";

    foreach ($facturas as &$factura) {
        $ruta_completa = $path_base . $factura['ruta_xml'];
        if (file_exists($ruta_completa)) {
            $factura['archivo'] = base64_encode(file_get_contents($ruta_completa));
            $factura['url_archivo'] = $url_base . $factura['ruta_xml'];
        } else {
            $factura['archivo'] = false;
            $factura['url_archivo'] = null; // Opción para indicar que no hay URL disponible
        }
    }


        return $facturas;
    }
}
