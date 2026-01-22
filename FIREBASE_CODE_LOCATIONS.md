# Firebase Code Locations in PHP Files

## Quick Reference: What to Remove from Each File

---

## 📁 **report.php**

### **Line 4:**
```php
include("send_notification.php");  // ❌ DELETE THIS LINE
```

### **Lines 39-40 (in SELECT query):**
```php
SELECT c.center_id, c.center_name, c.latitude, c.longitude, u.fcm_token,  // ❌ REMOVE ", u.fcm_token"
```

### **Lines 101-102 (in critical case SELECT query):**
```php
SELECT c.center_id, c.center_name, c.latitude, c.longitude, u.fcm_token,  // ❌ REMOVE ", u.fcm_token"
```

### **Lines 130-135 (notification block):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
// --- SEND NOTIFICATION TO NEAREST CENTER ---
if (!empty($nearest['fcm_token'])) {
    $notifTitle = "New Rescue Case Nearby";
    $notifBody = "A $animal_type needs help near your center.";
    sendNotification($nearest['fcm_token'], $notifTitle, $notifBody);
}
```

### **Lines 170-175 (critical case notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
// --- SEND NOTIFICATION TO CENTER ---
if (!empty($c['fcm_token'])) {
    $notifTitle = "CRITICAL: Rescue Case Assigned";
    $notifBody = "A critical $animal_type case has been assigned to your center.";
    sendNotification($c['fcm_token'], $notifTitle, $notifBody);
}
```

---

## 📁 **accrej.php**

### **Line 133:**
```php
include_once 'send_notification.php';  // ❌ DELETE THIS LINE
```

### **Lines 137-155 (user notification on accept):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
// 🔔 SEND NOTIFICATION TO USER
SELECT u.fcm_token 
FROM users u 
INNER JOIN cases c ON c.user_id = u.id 
WHERE c.id = ?

if (!empty($uRow['fcm_token'])) {
    sendNotification(
        $uRow['fcm_token'],
        "Case Accepted",
        "Your rescue case has been accepted by {$center_name}."
    );
}
```

### **Lines 157-167 (admin notification for critical cases):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
// 🔔 SEND NOTIFICATION TO ADMIN
$adminQ = $conn->query("SELECT fcm_token FROM users WHERE user_type = 'Admin' LIMIT 1");
if ($adminRow = $adminQ->fetch_assoc()) {
    sendNotification(
        $adminRow['fcm_token'],
        "Critical Case Escalation",
        "All centers rejected case #$case_id. Manual intervention needed."
    );
}
```

### **Lines 187-195 (rejection notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
sendNotification(
    $uRow['fcm_token'],
    "Case Update",
    "Your case has been reviewed. Reason: $reason"
);
```

---

## 📁 **reach.php**

### **Line 42:**
```php
include_once 'send_notification.php';  // ❌ DELETE THIS LINE
```

### **Lines 46-62 (user notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
// 🔔 SEND NOTIFICATION TO USER
SELECT u.fcm_token 
FROM users u 
INNER JOIN cases c ON c.user_id = u.id 
WHERE c.id = ?

if (!empty($uRow['fcm_token'])) {
    sendNotification(
        $uRow['fcm_token'],
        "Rescuer On The Way",
        "The rescue team has reached your reported location."
    );
}
```

---

## 📁 **close.php**

### **Line 94:**
```php
include_once 'send_notification.php';  // ❌ DELETE THIS LINE
```

### **Lines 98-115 (user notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
// 🔔 SEND NOTIFICATION TO USER
SELECT u.fcm_token 
FROM users u 
INNER JOIN cases c ON c.user_id = u.id 
WHERE c.id = ?

if (!empty($uRow['fcm_token'])) {
    sendNotification(
        $uRow['fcm_token'],
        "Case Closed Successfully",
        "Your rescue case has been completed. Thank you for helping!"
    );
}
```

### **Lines 118-127 (admin notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
$adminQ = $conn->query("SELECT fcm_token FROM users WHERE user_type = 'Admin' LIMIT 1");
if ($adminRow = $adminQ->fetch_assoc()) {
    sendNotification(
        $adminRow['fcm_token'],
        "Case Closed",
        "Case #$case_id has been successfully closed."
    );
}
```

---

## 📁 **donation.php**

### **Line 111:**
```php
include_once 'send_notification.php';  // ❌ DELETE THIS LINE
```

### **Lines 112-121 (admin notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
$adminQ = $conn->query("SELECT fcm_token FROM users WHERE user_type = 'Admin' LIMIT 1");
if ($adminRow = $adminQ->fetch_assoc()) {
    sendNotification(
        $adminRow['fcm_token'],
        "New Donation Request",
        "A new donation request has been submitted for approval."
    );
}
```

---

## 📁 **donationapproval.php**

### **Line 75:**
```php
include_once 'send_notification.php';  // ❌ DELETE THIS LINE (first occurrence)
```

### **Lines 79-91 (approval notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
SELECT u.fcm_token 
FROM users u 
INNER JOIN centers c ON c.user_id = u.id 
INNER JOIN donations d ON d.center_id = c.center_id 
WHERE d.donation_id = ?

if (!empty($cRow['fcm_token'])) {
    sendNotification($cRow['fcm_token'], "Donation Approved", "Your donation request #$donation_id has been approved.");
}
```

### **Line 112:**
```php
include_once 'send_notification.php';  // ❌ DELETE THIS LINE (second occurrence)
```

### **Lines 114-126 (rejection notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
SELECT u.fcm_token 
FROM users u 
INNER JOIN centers c ON c.user_id = u.id 
INNER JOIN donations d ON d.center_id = c.center_id 
WHERE d.donation_id = ?

if (!empty($cRow['fcm_token'])) {
    sendNotification($cRow['fcm_token'], "Donation Rejected", "Your donation request #$donation_id was rejected.");
}
```

---

## 📁 **payment.php**

### **Line 85:**
```php
include_once 'send_notification.php';  // ❌ DELETE THIS LINE
```

### **Lines 89-101 (center notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
// 🔔 NOTIFICATIONS (Center: Paid, User: Thank You)
SELECT u.fcm_token, d.amount 
FROM users u 
INNER JOIN centers c ON c.user_id = u.id 
INNER JOIN donations d ON d.center_id = c.center_id 
WHERE d.donation_id = ?

if (!empty($cRow['fcm_token'])) {
    sendNotification($cRow['fcm_token'], "Donation Received!", "You received ₹" . $cRow['amount'] . " for Donation #$donation_id");
}
```

### **Lines 104-111 (user notification):**
```php
// ❌ DELETE THIS ENTIRE BLOCK
$uQ = $conn->prepare("SELECT fcm_token FROM users WHERE id = ?");
$uQ->bind_param("i", $user_id);
$uQ->execute();
if ($uRow = $uQ->get_result()->fetch_assoc()) {
    if (!empty($uRow['fcm_token'])) {
        sendNotification($uRow['fcm_token'], "Payment Successful", "Thank you! Your donation was successful.");
    }
}
```

---

## 📁 **Files to DELETE Completely**

### **1. send_notification.php** ❌
Entire file - Contains Firebase messaging service

### **2. update_fcm_token.php** ❌
Entire file - Updates FCM tokens in database

### **3. test_notification.php** ❌
Entire file - Testing script for notifications

### **4. service-account.json** ❌
Entire file - Firebase service account credentials

---

## 🔍 Search Commands to Verify Removal

After making changes, run these commands to ensure all Firebase code is removed:

```bash
# Windows Command Prompt
cd C:\Users\srive\AndroidStudioProjects\saveparse\app\src\phpflies

# Search for Firebase references
findstr /i "firebase" *.php
findstr /i "sendNotification" *.php
findstr /i "send_notification.php" *.php

# Expected: No results (clean removal)
```

---

## ✅ Summary

**Total Removals:**
- 🗑️ **4 files** to delete completely
- ✏️ **7 files** to modify
- 📝 **~25 code blocks** to remove
- 🔗 **~10 include statements** to delete
- 📊 **~15 fcm_token references** to remove from queries

**Estimated Time:** 30-40 minutes

---

**All core functionality (case management, donations, payments) will continue to work perfectly - only push notifications will be disabled.**
