<?php
class Room {
    private $conn;
    private $table_name = "rooms";

    public $id;
    public $room_number;
    public $room_type_id;
    public $floor;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT r.*, rt.name as room_type_name, rt.price_per_night 
                  FROM " . $this->table_name . " r 
                  JOIN room_types rt ON r.room_type_id = rt.id 
                  ORDER BY r.room_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function readAvailable($check_in, $check_out, $guests) {
        $query = "CALL get_available_rooms(:check_in, :check_out, :guests)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":check_in", $check_in);
        $stmt->bindParam(":check_out", $check_out);
        $stmt->bindParam(":guests", $guests);
        $stmt->execute();
        
        return $stmt;
    }

    public function updateStatus($room_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $room_id);
        
        return $stmt->execute();
    }
}
?>