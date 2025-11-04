<?php
class IdRenewalApplication {
    private $conn;
    private $table_name = "id_renewal_applications";

    public $id;
    public $user_id;
    public $current_valid_from;
    public $current_valid_to;
    public $requested_valid_from;
    public $requested_valid_to;
    public $reason;
    public $status;
    public $approved_by;
    public $manager_notes;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new ID renewal application
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, current_valid_from, current_valid_to, requested_valid_from, 
                       requested_valid_to, reason, status, created_at, updated_at) 
                      VALUES (:user_id, :current_valid_from, :current_valid_to, :requested_valid_from, 
                              :requested_valid_to, :reason, 'pending', NOW(), NOW())";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":current_valid_from", $this->current_valid_from);
            $stmt->bindParam(":current_valid_to", $this->current_valid_to);
            $stmt->bindParam(":requested_valid_from", $this->requested_valid_from);
            $stmt->bindParam(":requested_valid_to", $this->requested_valid_to);
            $stmt->bindParam(":reason", $this->reason);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;

        } catch (PDOException $e) {
            error_log("ID Renewal Create Error: " . $e->getMessage());
            return false;
        }
    }

    // Get applications by user ID
    public function getByUserId($user_id) {
        $query = "SELECT idr.*, 
                         u.full_name as user_full_name,
                         approver.full_name as approved_by_name,
                         approver_role.role_name as approved_by_role
                  FROM " . $this->table_name . " idr 
                  LEFT JOIN users u ON idr.user_id = u.id 
                  LEFT JOIN users approver ON idr.approved_by = approver.id
                  LEFT JOIN roles approver_role ON approver.role_id = approver_role.id
                  WHERE idr.user_id = ? 
                  ORDER BY idr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get pending applications for managers
    public function getPendingApplications() {
        $query = "SELECT idr.*, 
                         u.full_name, 
                         u.profile_image,
                         u.company_id,
                         u.department_id,
                         c.company_name,
                         d.department_name
                  FROM " . $this->table_name . " idr 
                  JOIN users u ON idr.user_id = u.id 
                  LEFT JOIN companies c ON u.company_id = c.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  WHERE idr.status = 'pending' 
                  ORDER BY idr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get application by ID
    public function getById($id) {
        $query = "SELECT idr.*, 
                         u.full_name as user_full_name,
                         u.company_id,
                         u.department_id,
                         u.profile_image,
                         c.company_name,
                         d.department_name,
                         approver.full_name as approved_by_name,
                         approver_role.role_name as approved_by_role
                  FROM " . $this->table_name . " idr 
                  LEFT JOIN users u ON idr.user_id = u.id 
                  LEFT JOIN companies c ON u.company_id = c.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  LEFT JOIN users approver ON idr.approved_by = approver.id
                  LEFT JOIN roles approver_role ON approver.role_id = approver_role.id
                  WHERE idr.id = ? 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update application status (approve/reject)
    public function updateStatus($application_id, $status, $approved_by, $manager_notes = '') {
        try {
            $this->conn->beginTransaction();

            // Get application details for history
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$application_id]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$application) {
                throw new Exception("Application not found");
            }

            // Update application status
            $query = "UPDATE " . $this->table_name . " 
                      SET status = :status, 
                          approved_by = :approved_by, 
                          approved_at = NOW(), 
                          manager_notes = :manager_notes,
                          updated_at = NOW()
                      WHERE id = :id AND status = 'pending'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":approved_by", $approved_by);
            $stmt->bindParam(":manager_notes", $manager_notes);
            $stmt->bindParam(":id", $application_id);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update application status");
            }

            // Add to history table
            if ($status == 'approved' || $status == 'rejected') {
                $history_query = "INSERT INTO id_renewal_history 
                                 (id_renewal_application_id, user_id, current_valid_from, current_valid_to, 
                                  requested_valid_from, requested_valid_to, reason, status, approved_by, manager_notes, created_at) 
                                 VALUES (:application_id, :user_id, :current_valid_from, :current_valid_to, 
                                         :requested_valid_from, :requested_valid_to, :reason, :status, :approved_by, :manager_notes, NOW())";
                
                $history_stmt = $this->conn->prepare($history_query);
                $history_stmt->bindParam(":application_id", $application_id);
                $history_stmt->bindParam(":user_id", $application['user_id']);
                $history_stmt->bindParam(":current_valid_from", $application['current_valid_from']);
                $history_stmt->bindParam(":current_valid_to", $application['current_valid_to']);
                $history_stmt->bindParam(":requested_valid_from", $application['requested_valid_from']);
                $history_stmt->bindParam(":requested_valid_to", $application['requested_valid_to']);
                $history_stmt->bindParam(":reason", $application['reason']);
                $history_stmt->bindParam(":status", $status);
                $history_stmt->bindParam(":approved_by", $approved_by);
                $history_stmt->bindParam(":manager_notes", $manager_notes);

                if (!$history_stmt->execute()) {
                    throw new Exception("Failed to add application to history");
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ID Renewal Update Status Error: " . $e->getMessage());
            throw $e;
        }
    }

    // Get statistics
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

    // Check for duplicate pending applications
    public function hasPendingApplication($user_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE user_id = ? AND status = 'pending' 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->rowCount() > 0;
    }

    // Delete application (for cancellation)
    public function delete($application_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = ? AND user_id = ? AND status = 'pending'";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$application_id, $user_id]);
    }

    // Get applications with filters (for admin)
    public function getApplicationsWithFilters($filters = []) {
        $query = "SELECT idr.*, 
                         u.full_name, 
                         u.profile_image,
                         c.company_name,
                         d.department_name,
                         approver.full_name as approved_by_name
                  FROM " . $this->table_name . " idr 
                  JOIN users u ON idr.user_id = u.id 
                  LEFT JOIN companies c ON u.company_id = c.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  LEFT JOIN users approver ON idr.approved_by = approver.id
                  WHERE 1=1";
        
        $params = [];
        
        // Add filters
        if (!empty($filters['status'])) {
            $query .= " AND idr.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND idr.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['company_id'])) {
            $query .= " AND u.company_id = ?";
            $params[] = $filters['company_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(idr.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(idr.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $query .= " ORDER BY idr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>