<?php
class Booking {
    private $conn;
    private $table_name = "bookings";

    public $id;
    public $user_id;
    public $room_id;
    public $check_in_date;
    public $check_out_date;
    public $guests_count;
    public $total_price;
    public $status;
    public $special_requests;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        // Сначала рассчитываем стоимость
        $nights = (strtotime($this->check_out_date) - strtotime($this->check_in_date)) / (60 * 60 * 24);
        
        // Получаем цену номера
        $price_query = "SELECT rt.price_per_night 
                        FROM rooms r 
                        JOIN room_types rt ON r.room_type_id = rt.id 
                        WHERE r.id = :room_id";
        
        $stmt = $this->conn->prepare($price_query);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->execute();
        $price_per_night = $stmt->fetchColumn();
        
        $this->total_price = $nights * $price_per_night;

        // Создаем бронирование
        $query = "INSERT INTO bookings 
                  (user_id, room_id, check_in_date, check_out_date, guests_count, total_price, special_requests, status) 
                  VALUES (:user_id, :room_id, :check_in_date, :check_out_date, :guests_count, :total_price, :special_requests, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":check_in_date", $this->check_in_date);
        $stmt->bindParam(":check_out_date", $this->check_out_date);
        $stmt->bindParam(":guests_count", $this->guests_count);
        $stmt->bindParam(":total_price", $this->total_price);
        $stmt->bindParam(":special_requests", $this->special_requests);
        $stmt->bindParam(":status", $this->status);
        
        if ($stmt->execute()) {
            // Обновляем статус комнаты
            $update_room = "UPDATE rooms SET status = 'occupied' WHERE id = :room_id";
            $stmt2 = $this->conn->prepare($update_room);
            $stmt2->bindParam(":room_id", $this->room_id);
            $stmt2->execute();
            
            return true;
        }
        
        return false;
    }

    public function readByUser($user_id) {
        $query = "SELECT b.*, r.room_number, rt.name as room_type_name 
                  FROM bookings b
                  JOIN rooms r ON b.room_id = r.id
                  JOIN room_types rt ON r.room_type_id = rt.id
                  WHERE b.user_id = :user_id 
                  ORDER BY b.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function readAll() {
        $query = "SELECT b.*, u.username, u.email, u.first_name, u.last_name, 
                         r.room_number, rt.name as room_type_name
                  FROM bookings b
                  JOIN users u ON b.user_id = u.id
                  JOIN rooms r ON b.room_id = r.id
                  JOIN room_types rt ON r.room_type_id = rt.id
                  ORDER BY b.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function updateStatus($booking_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $booking_id);
        
        if ($stmt->execute()) {
            // Если бронирование отменено или завершено, освобождаем комнату
            if ($status === 'cancelled' || $status === 'completed') {
                $room_query = "SELECT room_id FROM bookings WHERE id = :id";
                $room_stmt = $this->conn->prepare($room_query);
                $room_stmt->bindParam(":id", $booking_id);
                $room_stmt->execute();
                $room_id = $room_stmt->fetchColumn();
                
                $update_room = "UPDATE rooms SET status = 'available' WHERE id = :room_id";
                $update_stmt = $this->conn->prepare($update_room);
                $update_stmt->bindParam(":room_id", $room_id);
                $update_stmt->execute();
            }
            
            return true;
        }
        
        return false;
    }
}
?>