# PHP Firebase Removal Plan

## Overview
This document outlines all Firebase/FCM integrations in your PHP backend and provides a safe removal strategy.

---

## 📊 Firebase Integration Analysis

### **Files to DELETE Completely:**
1. ✅ `send_notification.php` - Core Firebase notification service
2. ✅ `update_fcm_token.php` - FCM token update endpoint
3. ✅ `test_notification.php` - Notification testing script
4. ✅ `service-account.json` - Firebase service account credentials

### **Files to MODIFY (Remove notification code):**
1. ✏️ `report.php` - Sends notifications to rescue centers
2. ✏️ `accrej.php` - Sends notifications on case accept/reject
3. ✏️ `reach.php` - Sends notifications when rescuer reaches location
4. ✏️ `close.php` - Sends notifications when case is closed
5. ✏️ `donation.php` - Sends notifications to admin for new donation requests
6. ✏️ `donationapproval.php` - Sends notifications on donation approval/rejection
7. ✏️ `payment.php` - Sends notifications on successful payment

---

## 🔍 Detailed File Analysis

### **1. report.php**
**Lines to Remove:**
- Line 4: `include("send_notification.php");`
- Lines 39-40: `fcm_token` field in SELECT query
- Lines 101-102: `fcm_token` field in SELECT query (critical cases)
- Lines 130-135: Notification sending block (nearest center)
- Lines 170-175: Notification sending block (all centers for critical cases)

**Impact:** Case reporting will still work, but centers won't receive push notifications.

---

### **2. accrej.php**
**Lines to Remove:**
- Line 133: `include_once 'send_notification.php';`
- Lines 137-155: Entire notification block for user (case accepted)
- Lines 157-167: Entire notification block for admin (critical case escalation)
- Lines 187-195: Notification block for rejection

**Impact:** Accept/Reject functionality will work, but users won't receive notifications.

---

### **3. reach.php**
**Lines to Remove:**
- Line 42: `include_once 'send_notification.php';`
- Lines 46-62: Entire notification block to user

**Impact:** "Reached Location" status update will work, but users won't be notified.

---

### **4. close.php**
**Lines to Remove:**
- Line 94: `include_once 'send_notification.php';`
- Lines 98-115: Notification block to user
- Lines 118-127: Notification block to admin

**Impact:** Case closure will work, but users and admins won't receive notifications.

---

### **5. donation.php**
**Lines to Remove:**
- Line 111: `include_once 'send_notification.php';`
- Lines 112-121: Notification block to admin

**Impact:** Donation requests will be created, but admin won't receive notifications.

---

### **6. donationapproval.php**
**Lines to Remove:**
- Line 75: `include_once 'send_notification.php';`
- Lines 79-91: Notification block for approval
- Line 112: `include_once 'send_notification.php';`
- Lines 114-126: Notification block for rejection

**Impact:** Donation approval/rejection will work, but centers won't receive notifications.

---

### **7. payment.php**
**Lines to Remove:**
- Line 85: `include_once 'send_notification.php';`
- Lines 89-101: Notification block to center (donation received)
- Lines 104-111: Notification block to user (payment successful)

**Impact:** Payment processing will work, but users and centers won't receive notifications.

---

## 🗄️ Database Considerations

### **Option 1: Leave `fcm_token` Column (Recommended)**
- **Pros:** No database migration needed, no risk of breaking queries
- **Cons:** Unused column in database
- **Action:** None required

### **Option 2: Remove `fcm_token` Column (Optional)**
- **Pros:** Cleaner database schema
- **Cons:** Requires SQL migration, potential for errors
- **SQL Command:**
```sql
ALTER TABLE users DROP COLUMN fcm_token;
```
- **⚠️ Warning:** Only do this if you're 100% sure no other code references this column

---

## 📝 Step-by-Step Removal Instructions

### **Phase 1: Delete Firebase Service Files**

```bash
# Navigate to PHP directory
cd C:\Users\srive\AndroidStudioProjects\saveparse\app\src\phpflies

# Delete Firebase-specific files
del send_notification.php
del update_fcm_token.php
del test_notification.php
del service-account.json
```

---

### **Phase 2: Clean Up PHP Files**

For each file below, I'll provide the exact code blocks to remove:

#### **2.1: report.php**
Remove these sections:
1. Line 4: Delete `include("send_notification.php");`
2. In the SELECT query (around line 39), remove `, u.fcm_token` from the SELECT clause
3. In the critical case SELECT query (around line 101), remove `, u.fcm_token` from the SELECT clause
4. Remove the entire notification block (lines 130-135):
```php
// --- SEND NOTIFICATION TO NEAREST CENTER ---
if (!empty($nearest['fcm_token'])) {
    $notifTitle = "New Rescue Case Nearby";
    $notifBody = "A $animal_type needs help near your center.";
    sendNotification($nearest['fcm_token'], $notifTitle, $notifBody);
}
```
5. Remove the critical case notification block (lines 170-175):
```php
// --- SEND NOTIFICATION TO CENTER ---
if (!empty($c['fcm_token'])) {
    $notifTitle = "CRITICAL: Rescue Case Assigned";
    $notifBody = "A critical $animal_type case has been assigned to your center.";
    sendNotification($c['fcm_token'], $notifTitle, $notifBody);
}
```

#### **2.2: accrej.php**
Remove these sections:
1. Line 133: Delete `include_once 'send_notification.php';`
2. Remove entire notification blocks (lines 137-167 and 187-195)

#### **2.3: reach.php**
Remove these sections:
1. Line 42: Delete `include_once 'send_notification.php';`
2. Remove notification block (lines 46-62)

#### **2.4: close.php**
Remove these sections:
1. Line 94: Delete `include_once 'send_notification.php';`
2. Remove notification blocks (lines 98-127)

#### **2.5: donation.php**
Remove these sections:
1. Line 111: Delete `include_once 'send_notification.php';`
2. Remove notification block (lines 112-121)

#### **2.6: donationapproval.php**
Remove these sections:
1. Line 75: Delete `include_once 'send_notification.php';`
2. Remove notification blocks (lines 79-91 and 114-126)

#### **2.7: payment.php**
Remove these sections:
1. Line 85: Delete `include_once 'send_notification.php';`
2. Remove notification blocks (lines 89-111)

---

### **Phase 3: Verify Removal**

Search for any remaining Firebase references:
```bash
# Search for "firebase" in all PHP files
findstr /i "firebase" *.php

# Search for "fcm" in all PHP files
findstr /i "fcm" *.php

# Search for "notification" in all PHP files
findstr /i "sendNotification" *.php
```

Expected result: No matches (except in comments or database column names)

---

### **Phase 4: Test All Endpoints**

Test these critical workflows:
1. ✅ **Report Case** - Should create case without errors
2. ✅ **Accept/Reject Case** - Should update status without errors
3. ✅ **Reach Location** - Should update status without errors
4. ✅ **Close Case** - Should close case without errors
5. ✅ **Create Donation** - Should create donation request without errors
6. ✅ **Approve/Reject Donation** - Should update status without errors
7. ✅ **Process Payment** - Should process payment without errors

---

## ⚠️ Important Notes

### **What Will Still Work:**
- ✅ All case reporting and tracking
- ✅ All rescue center operations
- ✅ All donation features
- ✅ All payment processing
- ✅ All admin operations
- ✅ All user authentication

### **What Will Stop Working:**
- ❌ Push notifications to users
- ❌ Push notifications to rescue centers
- ❌ Push notifications to admins
- ❌ FCM token updates

### **No Data Loss:**
- All existing cases, donations, payments, and user data remain intact
- Only the notification delivery mechanism is removed

---

## 🔄 Rollback Plan

If you need to restore Firebase functionality:
1. Restore deleted files from backup or version control
2. Re-add `include` statements in modified files
3. Re-add notification code blocks
4. Upload `service-account.json` back to server

---

## 📊 Summary

**Total Files to Delete:** 4
**Total Files to Modify:** 7
**Database Changes:** None required (optional cleanup available)
**Risk Level:** LOW ✅
**Estimated Time:** 30-45 minutes

---

## ✅ Completion Checklist

- [ ] Phase 1: Deleted 4 Firebase service files
- [ ] Phase 2: Modified 7 PHP files to remove notification code
- [ ] Phase 3: Verified no Firebase references remain
- [ ] Phase 4: Tested all critical endpoints
- [ ] Phase 5: Documented changes
- [ ] Phase 6: Committed to version control (if applicable)

---

**Ready to proceed? I can help you make these changes automatically!**
