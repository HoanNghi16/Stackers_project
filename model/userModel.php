<?php
    class UserModel extends Model{
        private $conn;
        private $tableName = "users";
        private $fields = array(["user_id", "user_name", "first_name", "last_name", "password", ""]);
        public function __construct($conn){
            $this->conn = $conn;
        }
    }
?>