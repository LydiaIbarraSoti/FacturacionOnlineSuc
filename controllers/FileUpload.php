<?php 

//class FileUpload extends Controller
require_once "../config/database.php"; 

use PhpCfdi\CfdiCleaner\Cleaner;

include 'cd_conector.php';
require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

    $checkin = new FileUpload();

    //**Ejemplo de ejecución basado en la URL**
    if (isset($_GET['action'])) 
    {
        switch ($_GET['action']) 
        {
            case 'buscarXML':
                $folio = isset($_GET["folio"]) ? $_GET["folio"] : null;
                if ($folio != null)
                {
                    $mensaje = $checkin->buscarXML($folio);
                    echo json_encode(["success" => true, "message" => $folio, "data" => $mensaje]);
                }
                else
                {
                    echo json_encode(["success" => false, "message" => $folio]);
                }
        }
    } else {
        echo "Bienvenido al sistema de CFDI.";
    }

class FileUpload
{
   
    public function buscarXML($folio)
    {
        $db = (new Database())->getConnection();
        $sqlBuscar = "SELECT XML FROM REPOSITORIOS WHERE REPOSITORIO_ID = ?";
        $stmt = $db->prepare($sqlBuscar);
        $stmt->execute([$folio]);
        
        $rutaXML = $stmt->fetchColumn(); // Obtiene la ruta del XML
    
        if ($rutaXML) {
            return $this->upload($rutaXML); // Llamada correcta
        } else {
            return "No se encontró el XML.";
        }
    }
    
    public function upload($rutaXML) 
    { 
        //helper(['form', 'url', 'filesystem']);

        // Verifica si la ruta del XML es válida
        if (!file_exists($rutaXML)) {
            return "Error: El archivo XML no existe en la ruta especificada.";
        }

        // Obtiene el contenido del XML desde la ruta dada
        $string = file_get_contents($rutaXML);
        
        // Limpia el XML
        $xmlClean = Cleaner::staticClean($string);
        
        // Crea la estructura de nodos XML
        $comprobante = \CfdiUtils\Nodes\XmlNodeUtils::nodeFromXmlString($xmlClean);

        // Obtiene el nombre del archivo sin extensión
        $filenameWithoutExt = pathinfo($rutaXML, PATHINFO_FILENAME);
        $pdfFilename = $filenameWithoutExt . '.pdf';  

        // Crea el objeto CfdiData
        $cfdiData = (new \PhpCfdi\CfdiToPdf\CfdiDataBuilder())->build($comprobante);

        // Crea el convertidor
        $converter = new \PhpCfdi\CfdiToPdf\Converter(
            new \PhpCfdi\CfdiToPdf\Builders\Html2PdfBuilder()
        );

        // Define la ruta donde se guardará el PDF
        $pdfPath = '../public/uploads/' . $pdfFilename;

        // Genera el PDF
        $converter->createPdfAs($cfdiData, $pdfPath);
        
        // Genera la URL accesible para el navegador
        $pdfUrl = '../public/uploads/' . $pdfFilename;

        // Abre el PDF en una nueva ventana
        return $pdfUrl;
    }
    
}