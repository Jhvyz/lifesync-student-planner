<?php
class User {
    private $conn;
    private $table_name = "tasks"; // Ilisi kini sa imong actual tasks table name sa database

    public function __construct($db) {
        $this->conn = $db;
    }

    private function runTransaction(callable $operation) {
        $this->conn->beginTransaction();

        try {
            $result = $operation();
            $this->conn->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    // 1. READ (Kuhaon tanang tasks para i-display sa index.php)
    public function getTasks($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPillars($user_id) {
        $query = "SELECT pillar_id, pillar_name, color_code
                  FROM pillars
                  WHERE user_id = :user_id
                  ORDER BY pillar_id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ensureDefaultPillars($user_id) {
        $defaults = [
            'Academics' => '#287b5d',
            'Household Chores' => '#d8874d',
            'Recovery / Self-Care' => '#6587bd',
        ];
        $pillars = $this->getPillars($user_id);
        $existingNames = array_column($pillars, 'pillar_name');
        $stmt = $this->conn->prepare(
            "INSERT INTO pillars (user_id, pillar_name, color_code)
             VALUES (:user_id, :pillar_name, :color_code)"
        );

        foreach ($defaults as $name => $color) {
            if (!in_array($name, $existingNames, true)) {
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':pillar_name' => $name,
                    ':color_code' => $color,
                ]);
            }
        }

        return $this->getPillars($user_id);
    }

    public function ownsPillar($pillar_id, $user_id) {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM pillars WHERE pillar_id = :pillar_id AND user_id = :user_id"
        );
        $stmt->execute([
            ':pillar_id' => $pillar_id,
            ':user_id' => $user_id,
        ]);
        return (bool)$stmt->fetchColumn();
    }

    public function findUserByLogin($login) {
        $query = "SELECT user_id, username, email, password_hash, full_name
                  FROM users
                  WHERE username = :username OR email = :email
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':username' => $login, ':email' => $login]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUser($username, $email, $password, $first_name, $last_name, $gender, $address) {
        $full_name = trim($first_name . ' ' . $last_name);
        $query = "INSERT INTO users (username, email, password_hash, full_name, first_name, last_name, gender, address)
                  VALUES (:username, :email, :password_hash, :full_name, :first_name, :last_name, :gender, :address)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':full_name' => $full_name,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':gender' => $gender,
            ':address' => $address,
        ]);
        $userId = (int)$this->conn->lastInsertId();
        $this->ensureDefaultPillars($userId);
        return $userId;
    }

    // 2. CREATE (I-insert ang bag-ong task sa database)
    public function addTask($user_id, $pillar_id, $title, $description, $estimated_minutes, $effort_level, $due_date, $status) {
        $title = htmlspecialchars(strip_tags($title));
        $description = htmlspecialchars(strip_tags($description));
        $due_date = $due_date !== '' ? $due_date : null;

        return $this->runTransaction(function () use (
            $user_id,
            $pillar_id,
            $title,
            $description,
            $estimated_minutes,
            $effort_level,
            $due_date,
            $status
        ) {
            $query = "INSERT INTO " . $this->table_name . "
                      (user_id, pillar_id, title, description, estimated_minutes, effort_level, due_date, status)
                      VALUES (:user_id, :pillar_id, :title, :description, :estimated_minutes, :effort_level, :due_date, :status)";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':user_id' => $user_id,
                ':pillar_id' => $pillar_id,
                ':title' => $title,
                ':description' => $description,
                ':estimated_minutes' => $estimated_minutes,
                ':effort_level' => $effort_level,
                ':due_date' => $due_date,
                ':status' => $status,
            ]);
        });
    }

    // 3. UPDATE (I-edit ang existing task)
    public function updateTask($task_id, $user_id, $pillar_id, $title, $description, $estimated_minutes, $effort_level, $due_date, $status) {
        $title = htmlspecialchars(strip_tags($title));
        $description = htmlspecialchars(strip_tags($description));
        $due_date = $due_date !== '' ? $due_date : null;

        return $this->runTransaction(function () use (
            $task_id,
            $user_id,
            $pillar_id,
            $title,
            $description,
            $estimated_minutes,
            $effort_level,
            $due_date,
            $status
        ) {
            $query = "UPDATE " . $this->table_name . "
                      SET pillar_id = :pillar_id,
                          title = :title,
                          description = :description,
                          estimated_minutes = :estimated_minutes,
                          effort_level = :effort_level,
                          due_date = :due_date,
                          status = :status
                      WHERE task_id = :task_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':task_id' => $task_id,
                ':user_id' => $user_id,
                ':pillar_id' => $pillar_id,
                ':title' => $title,
                ':description' => $description,
                ':estimated_minutes' => $estimated_minutes,
                ':effort_level' => $effort_level,
                ':due_date' => $due_date,
                ':status' => $status,
            ]);
        });
    }

    // 4. DELETE (I-delete ang task pinaagi sa task_id)
    public function deleteTask($task_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE task_id = :task_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }
    // Kuhaon ang tanang scheduled blocks uban ang title sa task
    public function getScheduleBlocks() {
    $query = "SELECT sb.*, t.title 
              FROM schedule_blocks sb 
              JOIN tasks t ON sb.task_id = t.task_id 
              ORDER BY sb.start_time ASC";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function addScheduleBlock($task_id, $start_time, $end_time) {
    $query = "INSERT INTO schedule_blocks (task_id, start_time, end_time) VALUES (:task_id, :start_time, :end_time)";
    $stmt = $this->conn->prepare($query);
    return $stmt->execute([
        ':task_id' => $task_id,
        ':start_time' => $start_time,
        ':end_time' => $end_time
    ]);
}
}