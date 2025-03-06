<?php
// models/Repositorio.php
class Repositorio {
    private $conn;
    private $table = "REPOSITORIOS";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para obtener las facturas filtradas por el nombre del usuario
    public function getFacturasByUser($userName) {
        $query = "SELECT REPOSITORIO_ID, FOLIO, FECHA_FACTURA, RFC_EMISOR, RFC_RECEPTOR, METODO_PAGO, TOTAL 
                  FROM " . $this->table . " 
                  WHERE USUARIO_CREADOR = :usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $userName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
