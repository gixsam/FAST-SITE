import subprocess
import urllib.request
import urllib.error
import sys
import os

ROOT = r"d:\TECH\WEBSITE\FAST SITE\fast site"

php_files = [
    "includes/user_sidebar.php",
    "user/dashboard.php",
    "partner/nav.php",
    "partner/dashboard.php",
    "partner/index.php",
    "partner/logout.php",
    "partner/product_add.php",
    "partner/product_edit.php",
    "partner/product_delete.php",
    "partner/profile.php",
    "partner/api_docs.php"
]

print("=" * 80)
print("INDEPENDENT AUDITOR LIVE VERIFICATION SUITE")
print("=" * 80)

# 1. PHP Syntax lint
print("\n[PHASE 1] Full PHP Syntax Linting (php -l)")
syntax_errors = []
for rel in php_files:
    path = os.path.join(ROOT, rel.replace('/', os.sep))
    res = subprocess.run(["php", "-l", path], capture_output=True, text=True)
    if res.returncode != 0 or "No syntax errors detected" not in res.stdout:
        print(f"  FAIL: {rel}\n  {res.stderr or res.stdout}")
        syntax_errors.append((rel, res.stderr or res.stdout))
    else:
        print(f"  PASS: {rel} (Syntax OK)")

# 2. Localhost Live HTTP Endpoint checks
print("\n[PHASE 2] Local Server Live Host Endpoint Testing (http://localhost:8000)")

endpoints = [
    ("/index.php", [200]),
    ("/user/login.php", [200]),
    ("/user/dashboard.php", [302]),
    ("/partner/dashboard.php", [302]),
    ("/partner/index.php", [302]),
    ("/partner/logout.php", [302]),
    ("/partner/product_add.php", [302]),
    ("/partner/product_edit.php", [302]),
    ("/partner/product_delete.php", [302]),
    ("/partner/profile.php", [302]),
    ("/partner/api_docs.php", [302])
]

class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def http_error_302(self, req, fp, code, msg, headers):
        return fp
    http_error_301 = http_error_302
    http_error_303 = http_error_302
    http_error_307 = http_error_302

opener = urllib.request.build_opener(NoRedirectHandler)

endpoint_failures = []
for ep, expected_statuses in endpoints:
    url = f"http://localhost:8000{ep}"
    req = urllib.request.Request(url, headers={"User-Agent": "Auditor-P87-M3/1.0"})
    try:
        resp = opener.open(req, timeout=5)
        status = resp.getcode()
        loc = resp.headers.get("Location", "")
        if status in expected_statuses:
            print(f"  PASS: {ep} returned HTTP {status} (Expected: {expected_statuses}) -> Location: '{loc}'")
        else:
            print(f"  FAIL: {ep} returned HTTP {status} (Expected: {expected_statuses})")
            endpoint_failures.append((ep, status, expected_statuses))
    except urllib.error.HTTPError as e:
        if e.code in expected_statuses:
            loc = e.headers.get("Location", "")
            print(f"  PASS: {ep} returned HTTP {e.code} (Expected: {expected_statuses}) -> Location: '{loc}'")
        else:
            print(f"  FAIL: {ep} HTTPError {e.code}")
            endpoint_failures.append((ep, e.code, expected_statuses))
    except Exception as ex:
        print(f"  FAIL: {ep} Exception: {ex}")
        endpoint_failures.append((ep, str(ex), expected_statuses))

print("\n" + "=" * 80)
print(f"Syntax Errors: {len(syntax_errors)}")
print(f"Endpoint Failures: {len(endpoint_failures)}")
print("=" * 80)

if syntax_errors or endpoint_failures:
    print("VERDICT: FAILURE DETECTED")
    sys.exit(1)
else:
    print("VERDICT: 100% CLEAN")
    sys.exit(0)
