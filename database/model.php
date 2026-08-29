<?php

abstract class Model
{
    protected mysqli $conn;
    protected string $tableName;
    protected string $primaryKey;

    public function __construct($conn, $tableName, $primaryKey = 'id')
    {
        $this->conn = $conn;
        $this->tableName = $tableName;
        $this->primaryKey = $primaryKey;
    }

    /**
     * Lấy tất cả record
     */
    public function findAll(string $fields = "*"): array
    {
        $sql = "SELECT {$fields} FROM {$this->tableName}";

        $result = $this->conn->query($sql);

        if (!$result) {
            throw new Exception("Query failed: " . $this->conn->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Tìm record theo primary key
     */
    public function findById(mixed $id): ?array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE {$this->primaryKey} = ?";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param($this->getParamType($id), $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        return $data ?: null;
    }

    /**
     * Bộ lọc
     */
    public function filter(array $params): array
    {
        if (empty($params)) {
            return $this->findAll();
        }

        $conditions = [];
        $values = [];
        $types = "";

        foreach ($params as $field => $value) {
            $conditions[] = "`{$field}` = ?";
            $values[] = $value;
            $types .= $this->getParamType($value);
        }

        $where = implode(" AND ", $conditions);

        $sql = "SELECT * 
                FROM {$this->tableName} 
                WHERE {$where}";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Tạo record mới
     * Trả về ID của record vừa tạo.
     */
    public function create(array $params): int
    {
        if (empty($params)) {
            throw new InvalidArgumentException("Create data cannot be empty.");
        }

        $fields = array_keys($params);
        $columns = implode(", ", array_map(
            fn($field) => "`{$field}`",
            $fields
        ));

        $placeholders = implode(", ", array_fill(0, count($fields), "?"));

        $values = array_values($params);
        $types = "";

        foreach ($values as $value) {
            $types .= $this->getParamType($value);
        }

        $sql = "INSERT INTO {$this->tableName} 
                ({$columns}) 
                VALUES ({$placeholders})";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $this->conn->insert_id;
    }

    /**
     * Update record
     *
     * Ví dụ:
     * $userModel->update(1, [
     *     'username' => 'new_username',
     *     'email' => 'new@gmail.com'
     * ]);
     */
    public function update(mixed $id, array $params): bool
    {
        if (empty($params)) {
            throw new InvalidArgumentException("Update data cannot be empty.");
        }

        $set = [];
        $values = [];
        $types = "";

        foreach ($params as $field => $value) {
            $set[] = "`{$field}` = ?";
            $values[] = $value;
            $types .= $this->getParamType($value);
        }

        $values[] = $id;
        $types .= $this->getParamType($id);

        $setClause = implode(", ", $set);

        $sql = "UPDATE {$this->tableName}
                SET {$setClause}
                WHERE {$this->primaryKey} = ?";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    /**
     * Delete record
     */
    public function delete(mixed $id): bool
    {
        $sql = "DELETE FROM {$this->tableName}
                WHERE {$this->primaryKey} = ?";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param($this->getParamType($id), $id);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    /**
     * Xác định kiểu dữ liệu khi bind parameter
     *
     * i = integer
     * d = double
     * s = string
     * b = blob
     */
    private function getParamType(mixed $value): string
    {
        return match (gettype($value)) {
            'integer' => 'i',
            'double' => 'd',
            'boolean' => 'i',
            'NULL' => 's',
            default => 's'
        };
    }
}

