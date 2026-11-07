SAFETY CLUTCH SYSTEM - setup on XAMPP
1. Copy this folder ("safety_clutch") to  C:\xampp\htdocs\
2. Open XAMPP Control Panel -> Start Apache and MySQL.
3. Go to http://localhost/phpmyadmin -> Import -> choose database.sql -> Go.
4. Open http://localhost/safety_clutch/
5. config.php uses XAMPP defaults (user root, empty password). Change them if yours differ.

TRY IT (use two different browsers or one normal + one incognito window):
- Sign up as a DRIVER (pick vehicle type), then click "Go online".
- Sign up as a PASSENGER, book a ride with the same vehicle type.
- The driver sees the booking, accepts it, starts the trip, completes it.
- The passenger is asked to rate the driver (1-5 stars = safe-driving rating).

Discounts: 5% after 5 completed trips, 10% after 10, 15% after 20.
