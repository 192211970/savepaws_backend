<?php
header("Content-Type: application/json");
include 'db.php';

$response = array();

if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['status'] = "error";
        $response['message'] = "Invalid email format";
        echo json_encode($response);
        exit();
    }

    // Check user in database
    $stmt = $conn->prepare("SELECT id, name, phone, email, password, user_type FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // Compare normal password
        if ($row['password'] === $password) {

            $response['status'] = "success";
            $response['message'] = "Login successful";
            $response['user'] = array(
                "id" => $row['id'],
                "name" => $row['name'],
                "phone" => $row['phone'],
                "email" => $row['email'],
                "user_type" => $row['user_type']
            );

        } else {
            $response['status'] = "error";
            $response['message'] = "Incorrect password";
        }
    } else {
        $response['status'] = "error";
        $response['message'] = "Email not found";
    }

} else {
    $response['status'] = "error";
    $response['message'] = "Email and password required";
}

echo json_encode($response);

?>
