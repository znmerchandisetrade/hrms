<?php
class LateLetterApplication {
    private $conn;
    private $table_name = "late_letter_applications";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByUserId($user_id) {
        $query = "SELECT ll.*, u.full_name as approved_by_name 
                  FROM " . $this->table_name . " ll 
                  LEFT JOIN users u ON ll.approved_by = u.id 
                  WHERE ll.user_id = ? 
                  ORDER BY ll.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingApplications() {
        $query = "SELECT ll.*, u.full_name, u.profile_image 
                  FROM " . $this->table_name . " ll 
                  JOIN users u ON ll.user_id = u.id 
                  WHERE ll.status = 'pending' 
                  ORDER BY ll.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($application_id, $status, $approved_by, $manager_notes = '') {
        try {
            $this->conn->beginTransaction();

            // Get application details
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$application_id]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$application) {
                throw new Exception("Application not found");
            }

            // Update application status
            $query = "UPDATE " . $this->table_name . " 
                      SET status = ?, approved_by = ?, approved_at = NOW(), manager_notes = ? 
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$status, $approved_by, $manager_notes, $application_id]);

            // Add to history if approved or rejected
            if ($status == 'approved' || $status == 'rejected') {
                $history_query = "INSERT INTO late_letter_history 
                                 (late_letter_application_id, user_id, late_date, arrival_time, 
                                  reason, status, approved_by, manager_notes) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $history_stmt = $this->conn->prepare($history_query);
                $history_stmt->execute([
                    $application_id,
                    $application['user_id'],
                    $application['late_date'],
                    $application['arrival_time'],
                    $application['reason'],
                    $status,
                    $approved_by,
                    $manager_notes
                ]);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getStats() {
        $query = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                  SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                  SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                  FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>