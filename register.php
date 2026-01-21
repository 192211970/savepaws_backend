<?php
header("Content-Type: application/json");
include 'db.php';

$response = array();

if (
    isset($_POST['name']) &&
    isset($_POST['phone']) &&
    isset($_POST['email']) &&
    isset($_POST['password']) &&
    isset($_POST['user_type'])
) {

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];   // normal password
    $user_type = trim($_POST['user_type']);

    // -------- Email Validation --------
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['status'] = "error";
        $response['message'] = "Invalid email format. Must be like name@gmail.com";
        echo json_encode($response);
        exit();
    }

    // -------- Phone Validation --------
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $response['status'] = "error";
        $response['message'] = "Invalid phone number. Must be exactly 10 digits.";
        echo json_encode($response);
        exit();
    }

    // -------- Check if email already exists --------
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $response['status'] = "error";
        $response['message'] = "Email already registered";
        echo json_encode($response);
        exit();
    }

    // -------- Insert user into DB --------
    $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password, user_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $phone, $email, $password, $user_type);

    if ($stmt->execute()) {
        $response['status'] = "success";
        $response['message'] = "User registered successfully";
    } else {
        $response['status'] = "error";
        $response['message'] = "Registration failed";
    }

} else {
    $response['status'] = "error";
    $response['message'] = "Required fields are missing";
}

echo json_encode($response);
?>
