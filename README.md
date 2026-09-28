# 🚀 FAST SITE — Ecosystem Escrow Hub & Universal Marketplace

**Production Domain:** [https://fastsite.best-travel.ltd](https://fastsite.best-travel.ltd)  
**Architecture:** Custom Pure PHP Backend (No Framework), MySQL / MariaDB (Hostinger), SQLite Failover Engine, Google Stitch *Nocturne Aurum* Design System.

---

## 🏢 The Ecosystem (7 Connected Websites)

1. **Fast Site (HUB)** — `https://fastsite.best-travel.ltd` (Core Escrow Marketplace & API Hub)
2. **Affi Bangla** — `https://affibangla.best-travel.ltd` (Affiliate Aggregator)
3. **Best Travel** — `https://best-travel.ltd` (Travel Agency Booking Engine)
4. **Enzor Motor** — `https://enzor.best-travel.ltd` (Automobile Parts E-Commerce)
5. **Ayra Mart** — `https://atayramart.com` (Fashion Retail E-Commerce)
6. **Manza** — `https://manza.best-travel.ltd` (News & Content Portal)
7. **GixSam** — `https://gixsam.best-travel.ltd` (Personal Portfolio & Executive Services)

---

## 🏛️ Platform Highlights

- **100% Escrow Protection:** 72-Hour automated holding vault (`includes/escrow_engine.php`) with buyer inspection periods and 1-click disputes.
- **Dynamic Root-Relative Media Engine:** Seamless cross-environment media resolution (`/uploads/...`, `/assets/...`) across local host, Cloudflare tunnels, and Hostinger production.
- **1-Tap Bi-Directional Mode Switcher:** Instant shifting between Buyer Mode and Shop Mode with synchronized 5-slot bottom floating docks.
- **Google Stitch Nocturne Aurum Tokens:** Deep obsidian void background (`#0A0D1A`), frosted glass cards (`rgba(18, 22, 43, 0.85)`), amber/gold accents (`#F59E0B`), and 44px+ touch targets.
- **Automated CI/CD Pipeline:** GitHub Actions workflow (`.github/workflows/deploy.yml`) synchronizing codebase updates directly to Hostinger production.

---

## 🚀 Continuous Deployment to Hostinger

This repository uses **GitHub Actions** to automatically deploy code to Hostinger via FTPS upon every push to the `main` branch.

### Required GitHub Secrets:
To activate automated upload, add these repository secrets in **Settings ➔ Secrets and variables ➔ Actions**:
- `FTP_SERVER`: Hostinger FTP Host (e.g., `ftp.best-travel.ltd` or server IP)
- `FTP_USERNAME`: Hostinger FTP User (e.g., `u422364295`)
- `FTP_PASSWORD`: Hostinger FTP Password
- `FTP_DIR`: `public_html/` (target directory)

---
*Maintained by gixsam & Google Antigravity AI.*
