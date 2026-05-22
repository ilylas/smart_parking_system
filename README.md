# SmartPark — AI-Powered Parking System

## Stack
- **Frontend**: HTML5, CSS3, Vanilla JS, Chart.js 4, Leaflet.js (maps)
- **Backend**: PHP 8+, PDO/MySQL
- **AI**: Rule-based occupancy predictor (engine.php)

## Setup

1. Import `config/schema.sql` into MySQL
2. Copy project to a PHP-capable web root (Apache/Nginx + PHP-FPM)
3. Optionally set DB env vars: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. Open `pages/login.html` in your browser

### Default admin login
- Email: `admin@test.com`
- Password: `admin`

## Bug Fixes Applied

### Critical Bugs Fixed
1. **Auth mismatch** — Backend used PHP sessions; frontend stored user in localStorage and never sent session cookies. All API calls returned 401. Fixed: `auth.php` now accepts user_id from JSON body or query string (standard for SPA frontends).
2. **Reservations tab always empty** — `get_reservations.php` called `requireAdmin()` which failed due to auth mismatch above. Now fixed with flexible auth and proper user-scoped filtering.
3. **My Bookings dead link** — Sidebar item had no function. Now a full working tab showing user's own reservations with cancel functionality.
4. **No map** — Home page had no map. Added interactive Leaflet.js map with per-spot markers, popup booking, facility boundary, AI-recommended spot highlight.
5. **French AI labels** — `engine.php` returned `Saturé`, `Moyen`, `Faible affluence`. Fixed to English: `High Demand`, `Moderate`, `Low Traffic`.
6. **reserve.php trusted client user_id** — Security fix: now uses authenticated user_id from auth helper, not client-sent value.

### New Features Added
- 🗺 **Interactive Leaflet map** (home & admin) — real-time spot markers, colour-coded, clickable for booking
- 📋 **My Bookings tab** — full reservation history with cancel button
- ❌ **Cancel reservation API** (`cancel_reservation.php`) — user cancels own, admin cancels any
- 💰 **Revenue summary card** in admin reservations tab
- 🔍 **Search + filter** for admin reservations (by user, spot, status)
- 🗺 **Admin Live Map tab** with spot status overlay

## File Structure
```
smart_parking/
├── pages/
│   ├── login.html       # Auth portal
│   ├── register.html    # New account
│   ├── home.html        # User dashboard + map + bookings
│   └── admin.html       # Admin panel
├── api/
│   ├── login_process.php
│   ├── register_process.php
│   ├── logout.php
│   ├── stats.php
│   ├── getPlaces.php
│   ├── addPlace.php
│   ├── deletePlace.php
│   ├── reserve.php
│   ├── get_reservations.php
│   └── cancel_reservation.php  ← NEW
├── config/
│   ├── db.php
│   ├── auth.php         ← Fixed (session + body auth)
│   └── schema.sql
├── ai/
│   └── engine.php       ← Fixed (English labels)
└── assets/css/main.css
```
