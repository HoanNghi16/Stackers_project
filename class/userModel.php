<?php

class UserModel extends BaseModel
{
    protected array $fields = [
        "id"       => "int",
        "username" => "string",
        "email"    => "string",
        "password" => "string",
    ]

    public function __construct(mysqli $conn)
    {
        parent::__construct(
            $conn
        );
    }
}

