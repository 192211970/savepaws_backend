<?php
header("Content-Type: application/json");
include("db.php");

/* =========================
   INPUTS
   ========================= */
$donation_id = $_POST['donation_id'] ?? null;
$action      = $_POST['action'] ?? null; // approve / reject

if (!$donation_id || !$action) {
    echo json_encode([
        "status" => "error",
        "message" => "donation_id and action are required"
    ]);
    exit;
}

/* =========================
   VALIDATE ACTION
   ========================= */
if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid action"
    ]);
    exit;
}

/* =========================
   CHECK DONATION EXISTS
   ========================= */
$check = $conn->prepare("
    SELECT donation_id, approval_status
    FROM donations
    WHERE donation_id = ?
");
$check->bind_param("i", $donation_id);
$check->execute();
$donation = $check->get_result()->fetch_assoc();

if (!$donation) {
    echo json_encode([
        "status" => "error",
        "message" => "Donation request not found"
    ]);
    exit;
}

/* =========================
   PREVENT DOUBLE ACTION
   ========================= */
if ($donation['approval_status'] !== 'Pending') {
    echo json_encode([
        "status" => "error",
        "message" => "Donation request already processed"
    ]);
    exit;
}

/* =========================
   PROCESS ACTION
   ========================= */
if ($action === 'approve') {

    $update = $conn->prepare("
        UPDATE donations
        SET approval_status = 'Approved'
        WHERE donation_id = ?
    ");
    $update->bind_param("i", $donation_id);
    $update->execute();



    echo json_encode([
        "status" => "success",
        "message" => "Donation request approved successfully"
    ]);
    exit;
}

if ($action === 'reject') {

    $update = $conn->prepare("
        UPDATE donations
        SET approval_status = 'Rejected',
            donation_status = 'Rejected'
        WHERE donation_id = ?
    ");
    $update->bind_param("i", $donation_id);
    $update->execute();



    echo json_encode([
        "status" => "success",
        "message" => "Donation request rejected"
    ]);
    exit;
}
?>