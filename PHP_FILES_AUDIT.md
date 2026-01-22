# PHP Files Audit Report
**Generated:** 2026-01-21  
**Purpose:** Identify unused, duplicate, and problematic PHP files

---

## ✅ ACTIVE PHP FILES (Used in ApiService.kt)

### User Side
1. ✅ **login.php** - User/Center/Admin login
2. ✅ **register.php** - User/Organization registration
3. ✅ **report.php** - Report new animal case
4. ✅ **get_ongoing_cases.php** - Get user cases (ongoing/closed filter)
5. ✅ **get_user_cases.php** - Get all user cases
6. ✅ **get_case_track.php** - Track case progress
7. ✅ **get_approved_donations.php** - Public donation list
8. ✅ **get_donation_details.php** - Donation details
9. ✅ **get_user_donations.php** - User donation history
10. ✅ **payment.php** - Process payment
11. ✅ **create_razorpay_order.php** - Create Razorpay order
12. ✅ **verify_razorpay_payment.php** - Verify Razorpay payment
13. ✅ **forgot_password.php** - Send OTP for password reset
14. ✅ **verify_otp.php** - Verify OTP
15. ✅ **reset_password.php** - Reset password
16. ✅ **get_admin_contact.php** - Get admin contact info
17. ✅ **update_fcm_token.php** - Update FCM token for notifications

### Center Side
18. ✅ **centerdetails.php** - Register rescue center
19. ✅ **check_center_details.php** - Check if center registered
20. ✅ **get_center_pending_cases.php** - Center pending cases
21. ✅ **get_center_accepted_cases.php** - Center accepted cases
22. ✅ **get_center_closed_cases.php** - Center closed cases (used for dashboard)
23. ✅ **get_center_profile.php** - Center profile with stats
24. ✅ **accrej.php** - Accept/Reject case
25. ✅ **activestatus.php** - Update center active status
26. ✅ **reach.php** - Mark reached location
27. ✅ **spot.php** - Mark spotted animal
28. ✅ **close.php** - Close case with rescue photo
29. ✅ **donation.php** - Create donation request
30. ✅ **donation_req_history.php** - Center donation history

### Admin Side
31. ✅ **admin_dashboard_stats.php** - Admin dashboard statistics
32. ✅ **admin_pending_cases.php** - Admin pending cases
33. ✅ **admin_inprogress_cases.php** - Admin in-progress cases
34. ✅ **admin_closed_cases.php** - Admin closed cases
35. ✅ **admin_all_centers.php** - All centers list
36. ✅ **admin_center_details.php** - Center details for admin
37. ✅ **managing.php** - Update center status (approve/reject/suspend)
38. ✅ **admin_donations_list.php** - Admin donations by status
39. ✅ **admin_donation_details.php** - Admin donation details
40. ✅ **donationapproval.php** - Approve/Reject donation

### Utility Files
41. ✅ **db.php** - Database connection (included in all files)
42. ✅ **send_notification.php** - Send FCM notifications (used by triggers)

---

## ⚠️ POTENTIALLY UNUSED/DUPLICATE FILES

### 🔴 DUPLICATE/SIMILAR FUNCTIONALITY

1. **get_don_ad.php** ❌ DUPLICATE
   - Purpose: Get pending donations for admin
   - **DUPLICATE OF:** `admin_donations_list.php?status=pending`
   - **Recommendation:** DELETE - Not used in ApiService.kt

2. **get_don_us.php** ❌ DUPLICATE
   - Purpose: Get approved donations for users
   - **DUPLICATE OF:** `get_approved_donations.php`
   - **Recommendation:** DELETE - Not used in ApiService.kt

3. **user_donation.php** ❌ DUPLICATE
   - Purpose: Get user donation history
   - **DUPLICATE OF:** `get_user_donations.php`
   - **Recommendation:** DELETE - Not used in ApiService.kt

4. **All_closed_cases.php** ❌ UNCLEAR
   - Purpose: Unknown (possibly admin or center closed cases)
   - **Similar to:** `admin_closed_cases.php` or `get_center_closed_cases.php`
   - **Recommendation:** CHECK USAGE - If not used, DELETE

5. **admin_all_cases.php** ❌ NOT USED
   - Purpose: Get all admin cases
   - **Note:** ApiService.kt references `admin_get_all_cases.php` (which doesn't exist!)
   - **Recommendation:** RENAME to `admin_get_all_cases.php` OR update ApiService.kt

### 🔴 UNUSED CASE FILTERING FILES

6. **all_critical_cases.php** ❌ NOT USED
   - Purpose: Get all critical cases
   - **Recommendation:** DELETE if not needed

7. **all_critical_accept.php** ❌ NOT USED
   - Purpose: Get accepted critical cases
   - **Recommendation:** DELETE if not needed

8. **all_critical_yet_to_accept.php** ❌ NOT USED
   - Purpose: Get pending critical cases
   - **Recommendation:** DELETE if not needed

9. **all_standard_cases.php** ❌ NOT USED
   - Purpose: Get all standard cases
   - **Recommendation:** DELETE if not needed

10. **all_standard_accepted.php** ❌ NOT USED
    - Purpose: Get accepted standard cases
    - **Recommendation:** DELETE if not needed

11. **all_standard_yet_to_accept.php** ❌ NOT USED
    - Purpose: Get pending standard cases
    - **Recommendation:** DELETE if not needed

### 🔴 UNUSED UTILITY FILES

12. **newcase.php** ❌ NOT USED
    - Purpose: Unknown (possibly old case reporting)
    - **Similar to:** `report.php`
    - **Recommendation:** DELETE if `report.php` is the active version

13. **rejected.php** ❌ NOT USED
    - Purpose: Unknown (possibly handle rejected cases)
    - **Recommendation:** CHECK USAGE - If not used, DELETE

14. **delay.php** ❌ NOT USED
    - Purpose: Unknown (possibly delay/escalation handling)
    - **Recommendation:** CHECK USAGE - If not used, DELETE

15. **recieved_don_history.php** ❌ NOT USED
    - Purpose: Unknown (possibly center received donations)
    - **Similar to:** `donation_req_history.php`
    - **Recommendation:** CHECK USAGE - If not used, DELETE

### 🔴 TEST FILES

16. **test_email.php** ⚠️ TEST FILE
    - Purpose: Test email sending
    - **Recommendation:** KEEP for debugging, but not for production

17. **test_notification.php** ⚠️ TEST FILE
    - Purpose: Test FCM notifications
    - **Recommendation:** KEEP for debugging, but not for production

### 🔴 NON-PHP FILES IN PHP DIRECTORY

18. **cat.jpg, dog.jpg, sdog.jpg, cow.avif, stray.avif** ❌ IMAGES
    - **Recommendation:** MOVE to `uploads/` or delete

19. **OS - 1958.pdf** ❌ PDF
    - **Recommendation:** MOVE to documentation folder or delete

20. **service-account.json** ⚠️ FIREBASE CREDENTIALS
    - **Recommendation:** KEEP but ensure it's in .gitignore

21. **add_otp_to_users.sql** ✅ SQL MIGRATION
    - **Recommendation:** KEEP for database setup

22. **add_user_id_to_centers.sql** ✅ SQL MIGRATION
    - **Recommendation:** KEEP for database setup

23. **HOW_TO_ENABLE_EMAIL.md** ✅ DOCUMENTATION
    - **Recommendation:** KEEP

---

## 🚨 CRITICAL ISSUE FOUND

### Missing File in ApiService.kt
**Line 207:** `admin_get_all_cases.php`
- This file is referenced in ApiService.kt but **DOES NOT EXIST**
- Actual file is: `admin_all_cases.php`

**Fix Options:**
1. Rename `admin_all_cases.php` → `admin_get_all_cases.php`
2. Update ApiService.kt line 207 to use `admin_all_cases.php`

---

## 📋 RECOMMENDED ACTIONS

### Immediate Actions (Fix Backend Issues)

1. **Fix Missing File Issue:**
   ```bash
   # Option 1: Rename the file
   mv admin_all_cases.php admin_get_all_cases.php
   
   # OR Option 2: Update ApiService.kt line 207
   ```

2. **Delete Duplicate Files:**
   ```bash
   rm get_don_ad.php
   rm get_don_us.php
   rm user_donation.php
   ```

3. **Delete Unused Case Filter Files:**
   ```bash
   rm all_critical_cases.php
   rm all_critical_accept.php
   rm all_critical_yet_to_accept.php
   rm all_standard_cases.php
   rm all_standard_accepted.php
   rm all_standard_yet_to_accept.php
   ```

4. **Move/Delete Non-PHP Files:**
   ```bash
   mv *.jpg uploads/
   mv *.avif uploads/
   mv "OS - 1958.pdf" ../docs/
   ```

5. **Review and Delete if Unused:**
   - `All_closed_cases.php`
   - `newcase.php`
   - `rejected.php`
   - `delay.php`
   - `recieved_don_history.php`

### For Production Deployment

6. **Ensure .gitignore includes:**
   ```
   service-account.json
   test_*.php
   *.jpg
   *.png
   *.avif
   *.pdf
   ```

---

## 📊 SUMMARY

- **Total PHP Files:** 67
- **Active/Used Files:** 42
- **Duplicate Files:** 3
- **Unused Files:** ~15
- **Test Files:** 2
- **Non-PHP Files:** 7
- **Critical Issues:** 1 (missing file reference)

---

## ✅ FINAL RECOMMENDATION

**After hosting, if data is not loading properly:**

1. Check if `admin_all_cases.php` exists on server
2. Verify all 42 active files are uploaded
3. Ensure `db.php` has correct database credentials
4. Check PHP error logs on server
5. Test each endpoint individually using Postman/browser
6. Verify file permissions (644 for PHP files, 755 for directories)
7. Ensure `uploads/` directory exists and is writable (777)

**Most likely cause of data not loading:**
- Missing `admin_get_all_cases.php` file
- Incorrect database credentials in `db.php`
- Wrong file paths in hosted environment
- PHP version incompatibility
