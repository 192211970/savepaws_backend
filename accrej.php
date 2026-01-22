<?php
header("Content-Type: application/json");
include("db.php");

// Inputs
$center_id = isset($_POST['center_id']) ? intval($_POST['center_id']) : null;
$case_id   = isset($_POST['case_id']) ? intval($_POST['case_id']) : null;
$response  = isset($_POST['response']) ? strtolower($_POST['response']) : null;
$reason    = isset($_POST['reason']) ? $_POST['reason'] : null;

if (!$center_id || !$case_id || !$response) {
    echo json_encode(["status"=>"error","message"=>"Missing required fields"]);
    exit;
}

// Normalize response
if ($response === "accepted") {
    $dbResponse = "Accept";
} elseif ($response === "rejected") {
    $dbResponse = "Reject";
} else {
    echo json_encode(["status"=>"error","message"=>"Invalid response"]);
    exit;
}

$conn->begin_transaction();

try {

    /* =================================================
       1️⃣ CASE MUST BE REPORTED
       ================================================= */
    $caseCheck = $conn->prepare("
        SELECT status
        FROM cases
        WHERE case_id = ?
    ");
    $caseCheck->bind_param("i", $case_id);
    $caseCheck->execute();
    $caseRow = $caseCheck->get_result()->fetch_assoc();

    if (!$caseRow || $caseRow['status'] !== 'Reported') {
        throw new Exception("Case is not eligible");
    }

    /* =================================================
       2️⃣ DETERMINE ESCALATION TYPE (None / Sent_again)
       ================================================= */
    $escMeta = $conn->prepare("
        SELECT
            MAX(remark) AS remark,
            MAX(case_type) AS case_type,
            SUM(response = 'Accept') AS already_accepted
        FROM case_escalations
        WHERE case_id = ?
    ");
    $escMeta->bind_param("i", $case_id);
    $escMeta->execute();
    $meta = $escMeta->get_result()->fetch_assoc();

    if ($meta['already_accepted'] > 0) {
        throw new Exception("Case already accepted by another center");
    }

    $remark   = $meta['remark'];
    $caseType = $meta['case_type'];

    /* =================================================
       3️⃣ FIRST-TIME ESCALATION (remark = None)
       ================================================= */
    if ($remark === 'None') {

        if ($dbResponse === 'Reject' && $caseType === 'Critical') {
            throw new Exception("Critical cases cannot be rejected");
        }

        if ($dbResponse === 'Accept') {

            // Accept this center
            $accept = $conn->prepare("
                UPDATE case_escalations
                SET status='Responded',
                    response='Accept',
                    responded_time=NOW()
                WHERE case_id=? AND center_id=? AND remark='None'
            ");
            $accept->bind_param("ii", $case_id, $center_id);
            $accept->execute();

            if ($accept->affected_rows === 0) {
                throw new Exception("No valid first-time escalation for this center");
            }

            // Mark others
            $others = $conn->prepare("
                UPDATE case_escalations
                SET status='Already_responded'
                WHERE case_id=? AND center_id!=? AND remark='None'
            ");
            $others->bind_param("ii", $case_id, $center_id);
            $others->execute();

            // Update case_status
            $cs = $conn->prepare("
                UPDATE case_status
                SET acceptance_status='Accepted',
                    center_id=?,
                    case_took_up_time=NOW()
                WHERE case_id=?
            ");
            $cs->bind_param("ii", $center_id, $case_id);
            $cs->execute();

            // Update cases
            $c = $conn->prepare("
                UPDATE cases SET status='Accepted' WHERE case_id=?
            ");
            $c->bind_param("i", $case_id);
            $c->execute();

            // Increment center handled cases
            $incCenter = $conn->prepare("
                UPDATE centers
                SET total_cases_handled = total_cases_handled + 1
                WHERE center_id = ?
            ");
            $incCenter->bind_param("i", $center_id);
            $incCenter->execute();





        }

        if ($dbResponse === 'Reject') {
            $rej = $conn->prepare("
                UPDATE case_escalations
                SET status='Responded',
                    response='Reject',
                    rejected_reason=?,
                    responded_time=NOW()
                WHERE case_id=? AND center_id=? AND remark='None'
            ");
            $rej->bind_param("sii", $reason, $case_id, $center_id);
            $rej->execute();
        }
    }

    /* =================================================
       4️⃣ SENT-AGAIN ESCALATION (ACCEPT ONLY)
       ================================================= */
    elseif ($remark === 'Sent_again') {

        if ($dbResponse === 'Reject') {
            throw new Exception("Sent-again cases cannot be rejected");
        }

        // Accept this center
        $accept = $conn->prepare("
            UPDATE case_escalations
            SET status='Responded',
                response='Accept',
                responded_time=NOW()
            WHERE case_id=? AND center_id=? AND remark='Sent_again' AND status='Pending'
        ");
        $accept->bind_param("ii", $case_id, $center_id);
        $accept->execute();

        if ($accept->affected_rows === 0) {
            throw new Exception("No pending sent-again escalation for this center");
        }

        // Mark remaining Sent_again + Resent rows
        $others = $conn->prepare("
    UPDATE case_escalations
    SET status = 'Already_responded'
    WHERE case_id = ?
      AND remark = 'Resent'
");
$others->bind_param("i", $case_id);
$others->execute();


        // Update case_status
        $cs = $conn->prepare("
            UPDATE case_status
            SET acceptance_status='Accepted',
                center_id=?,
                case_took_up_time=NOW()
            WHERE case_id=?
        ");
        $cs->bind_param("ii", $center_id, $case_id);
        $cs->execute();

        // Update cases
        $c = $conn->prepare("
            UPDATE cases SET status='Accepted' WHERE case_id=?
        ");
        $c->bind_param("i", $case_id);
        $c->execute();

        // Increment center handled cases
$incCenter = $conn->prepare("
    UPDATE centers
    SET total_cases_handled = total_cases_handled + 1
    WHERE center_id = ?
");
$incCenter->bind_param("i", $center_id);
$incCenter->execute();

    }

    else {
        throw new Exception("Unsupported escalation state");
    }

    $conn->commit();

    echo json_encode([
        "status"=>"success",
        "message"=>"Case processed successfully",
        "case_id"=>$case_id,
        "center_id"=>$center_id
    ]);

} catch (Exception $e) {

    $conn->rollback();
    echo json_encode([
        "status"=>"error",
        "message"=>$e->getMessage()
    ]);
}
?>
