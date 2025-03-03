<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consultafacturas extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load = new CI_Loader();
        $this->config = new CI_Config();
        $this->load->model('Facturas_model'); // Asegúrate de cargar el modelo donde se harán las consultas
    }

    public function index() {
        // Solo aceptamos método POST
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->output->set_status_header(405); // Método no permitido
            echo json_encode(['cod_ret' => 405, 'cod_ret_mensaje' => 'Método no permitido']);
            return;
        }

        // Obtenemos los datos del JSON enviado en el body
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $facturas = $this->Facturas_model->consultar_facturas($data['_fecha_inicio'], $data['_fecha_final']);

        // Preparamos la respuesta
        $respuesta = [
            "cod_ret" => 200,
            "cod_ret_mensaje" => "Respuesta exitosa",
            "facturas" => $facturas
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($respuesta));
    }
}
