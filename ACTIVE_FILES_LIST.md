# List of PHP Files Used by SavePaws Application
# Extracted from ApiService.kt

## ACTIVE PHP FILES (42 files - DO NOT DELETE)

### Authentication & User Management (5 files)
login.php
register.php
forgot_password.php
verify_otp.php
reset_password.php

### User Side - Cases & Donations (9 files)
report.php
get_ongoing_cases.php
get_user_cases.php
get_case_track.php
get_approved_donations.php
get_donation_details.php
get_user_donations.php
payment.php
get_admin_contact.php

### User Side - Profile (1 file)
get_user_profile.php

### Razorpay Payment (2 files)
create_razorpay_order.php
verify_razorpay_payment.php

### Rescue Center - Registration & Setup (2 files)
centerdetails.php
check_center_details.php

### Rescue Center - Cases Management (3 files)
get_center_pending_cases.php
get_center_accepted_cases.php
get_center_closed_cases.php

### Rescue Center - Case Actions (5 files)
accrej.php
activestatus.php
reach.php
spot.php
close.php

### Rescue Center - Donations (2 files)
donation.php
donation_req_history.php

### Rescue Center - Profile (1 file)
get_center_profile.php

### Admin - Dashboard & Stats (1 file)
admin_dashboard_stats.php

### Admin - Cases Management (3 files)
admin_pending_cases.php
admin_inprogress_cases.php
admin_closed_cases.php

### Admin - Centers Management (3 files)
admin_all_centers.php
admin_center_details.php
managing.php

### Admin - Donations Management (3 files)
admin_donations_list.php
admin_donation_details.php
donationapproval.php

### Admin - All Cases (1 file)
admin_all_cases.php

### Notifications (2 files)
send_notification.php
update_fcm_token.php

### Core/Utility (1 file)
db.php

---

## UNUSED/DUPLICATE FILES (DELETE THESE)

### Duplicate Files (3 files)
get_don_ad.php              # Duplicate of admin_donations_list.php
get_don_us.php              # Duplicate of get_approved_donations.php
user_donation.php           # Duplicate of get_user_donations.php

### Unused Case Filter Files (6 files)
all_critical_cases.php
all_critical_accept.php
all_critical_yet_to_accept.php
all_standard_cases.php
all_standard_accepted.php
all_standard_yet_to_accept.php

### Unclear/Unused Files (5 files)
All_closed_cases.php        # Unclear purpose
newcase.php                 # Possibly old version of report.php
rejected.php                # Unknown
delay.php                   # Unknown
recieved_don_history.php    # Possibly old version

### Test Files (2 files - Keep for debugging)
test_email.php
test_notification.php

### New Test File (1 file - Keep for server testing)
test_db_connection.php

---

## NON-PHP FILES (Move or Delete)

### Images
cat.jpg
dog.jpg
sdog.jpg
cow.avif
stray.avif

### Documents
OS - 1958.pdf
HOW_TO_ENABLE_EMAIL.md      # Keep this

### SQL Scripts (Keep these)
add_otp_to_users.sql
add_user_id_to_centers.sql

### Configuration (Keep this - IMPORTANT!)
service-account.json

### Scripts (Keep these)
cleanup_server.sh
CLEANUP_SCRIPT.bat

---

## SUMMARY

Total PHP Files: 67
- ✅ Active (Used): 42
- ❌ Unused/Duplicate: 14
- ⚠️ Test Files: 3 (keep for debugging)
- 📁 Non-PHP: 8

Files to Delete: 14 unused PHP files
