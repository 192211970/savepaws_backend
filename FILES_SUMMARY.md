# PHP Files Usage Summary

## 📊 Statistics
- **Total PHP Files:** 67
- **✅ Active (Used):** 42 files
- **❌ Duplicates:** 3 files
- **❌ Unused:** ~15 files
- **⚠️ Test Files:** 2 files
- **🚨 Critical Issues:** 1 file naming mismatch

---

## ✅ ACTIVE FILES BY CATEGORY

### 🔐 Authentication (5 files)
```
login.php                    - User/Center/Admin login
register.php                 - User/Organization registration
forgot_password.php          - Send OTP for password reset
verify_otp.php              - Verify OTP code
reset_password.php          - Reset user password
```

### 👤 User Side (9 files)
```
report.php                   - Report new animal case
get_ongoing_cases.php        - Get user cases with status filter
get_user_cases.php          - Get all user cases
get_case_track.php          - Track case progress timeline
get_approved_donations.php   - Public donation list
get_donation_details.php     - Single donation details
get_user_donations.php       - User donation history
payment.php                  - Process donation payment
get_admin_contact.php        - Get admin contact info
```

### 🏥 Rescue Center Side (13 files)
```
centerdetails.php            - Register new rescue center
check_center_details.php     - Check if center registered
get_center_pending_cases.php - Pending cases for center
get_center_accepted_cases.php- Accepted cases for center
get_center_closed_cases.php  - Closed cases for center
get_center_profile.php       - Center profile with stats
accrej.php                   - Accept/Reject case
activestatus.php            - Update center active status
reach.php                    - Mark reached location
spot.php                     - Mark spotted animal
close.php                    - Close case with rescue photo
donation.php                 - Create donation request
donation_req_history.php     - Center donation history
```

### 👨‍💼 Admin Side (11 files)
```
admin_dashboard_stats.php    - Dashboard statistics
admin_pending_cases.php      - Pending cases list
admin_inprogress_cases.php   - In-progress cases list
admin_closed_cases.php       - Closed cases list
admin_all_centers.php        - All centers list
admin_center_details.php     - Center details view
managing.php                 - Update center status
admin_donations_list.php     - Donations by status
admin_donation_details.php   - Donation details
donationapproval.php        - Approve/Reject donation
admin_all_cases.php         - All cases overview
```

### 💳 Payment Integration (2 files)
```
create_razorpay_order.php    - Create Razorpay order
verify_razorpay_payment.php  - Verify Razorpay payment
```

### 🔔 Notifications (2 files)
```
send_notification.php        - Send FCM notifications
update_fcm_token.php        - Update FCM token
```

### 🔧 Utility (1 file)
```
db.php                       - Database connection
```

---

## ❌ DUPLICATE FILES (DELETE THESE)

```
get_don_ad.php              → Use: admin_donations_list.php?status=pending
get_don_us.php              → Use: get_approved_donations.php
user_donation.php           → Use: get_user_donations.php
```

---

## ❌ UNUSED FILES (SAFE TO DELETE)

### Case Filtering (Not Used)
```
all_critical_cases.php
all_critical_accept.php
all_critical_yet_to_accept.php
all_standard_cases.php
all_standard_accepted.php
all_standard_yet_to_accept.php
All_closed_cases.php
```

### Unknown Purpose (Check Before Delete)
```
newcase.php                 - Possibly old version of report.php
rejected.php                - Unknown purpose
delay.php                   - Unknown purpose
recieved_don_history.php    - Possibly old version
```

---

## ⚠️ TEST FILES (KEEP FOR DEBUGGING)

```
test_email.php              - Test email sending
test_notification.php       - Test FCM notifications
```

---

## 📁 NON-PHP FILES

### Images (Move to uploads/)
```
cat.jpg
dog.jpg
sdog.jpg
cow.avif
stray.avif
```

### Documentation
```
HOW_TO_ENABLE_EMAIL.md      - Email setup guide
```

### Database Migrations
```
add_otp_to_users.sql        - Add OTP column
add_user_id_to_centers.sql  - Add user_id to centers
```

### Configuration
```
service-account.json        - Firebase credentials (KEEP SECRET!)
```

### Other
```
OS - 1958.pdf               - Unknown document
```

---

## 🚨 CRITICAL ISSUE

### File Naming Mismatch
**ApiService.kt references:** `admin_get_all_cases.php`  
**Actual file name:** `admin_all_cases.php`

**FIX:** Rename the file to match ApiService.kt
```bash
rename admin_all_cases.php admin_get_all_cases.php
```

---

## 🎯 DEPLOYMENT CHECKLIST

### Files to Upload (42 PHP files)
- ✅ All files listed in "ACTIVE FILES" section above
- ✅ db.php with production database credentials
- ✅ service-account.json for Firebase
- ✅ uploads/ folder with 777 permissions

### Files to EXCLUDE
- ❌ All duplicate files
- ❌ All unused files
- ❌ Test files (unless needed for debugging)
- ❌ Image files (unless needed)
- ❌ PDF files

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- mod_rewrite enabled
- File upload enabled
- Max upload size: 10MB+

---

## 📞 TROUBLESHOOTING

### Data Not Loading After Hosting?

1. **Check file naming mismatch** (see CRITICAL ISSUE above)
2. **Verify db.php credentials** match production database
3. **Check file permissions** (644 for .php, 755 for directories)
4. **Test endpoints individually** using browser/Postman
5. **Check PHP error logs** on server
6. **Verify uploads/ folder exists** and is writable

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| 404 Not Found | Wrong file path | Check BASE_URL in app |
| 500 Internal Error | PHP error | Check error logs |
| Empty response | No database data | Check database |
| Connection failed | Wrong db.php | Update credentials |

---

## 📝 NOTES

- All active files use prepared statements (SQL injection safe)
- All files return JSON responses
- Most files use POST method for security
- Image uploads go to uploads/ folder
- FCM notifications require service-account.json
