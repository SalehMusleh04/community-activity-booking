<?php 


class User{
  private $conn ;
  private $table = 'users';

  public function __construct($db){
    $this->conn = $db;
  }
  
  public function emailExists($email){
    $sql = "SELECT user_id FROM {$this->table} WHERE email = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch() ? true : false;
  }

  public function register($name, $email , $password){
    if(!$this->emailExists($email)){
       $hashedPassword = password_hash($password , PASSWORD_DEFAULT);
    $sql = "INSERT INTO {$this->table} (name,email,password) VALUES (? , ? , ?) " ;
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([$name, $email , $hashedPassword]);
    }
    return false;
  }

  public function login($email , $password){
    $sql = "SELECT * FROM {$this->table} WHERE email = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['password'])){
        if($user['is_active'] == 1){
            unset($user['password']);
            return $user;
        }
    }
    return false;
  }

  public function getAllUsers(){
    $sql = "SELECT user_id, name, email, role, is_active FROM {$this->table}";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public function toggleStatus($id){
    $sql = "UPDATE {$this->table} SET is_active = NOT is_active WHERE user_id = ?";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([$id]);
  }

  public function getUserById($id){
    $sql = "SELECT user_id, name, email, role, is_active FROM {$this->table} WHERE user_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
  }

}







?>

