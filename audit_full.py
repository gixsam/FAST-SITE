import urllib.request, urllib.parse, http.cookiejar, random, sys

BASE = 'http://localhost:8000'

class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def http_error_302(self, req, fp, code, msg, headers):
        return fp
    http_error_301 = http_error_303 = http_error_307 = http_error_302

results = []

def check(o, path, label, expected_status=200, needs_content=None):
    try:
        req = urllib.request.Request(BASE + path)
        res = o.open(req, timeout=8)
        status = res.status
        html = res.read().decode('utf-8', errors='ignore')
        content_ok = True
        if needs_content:
            content_ok = needs_content.lower() in html.lower()
        errors = ['Fatal error', 'Parse error', 'Uncaught ', 'Exception:']
        has_error = any(e in html for e in errors)
        err_snippet = ''
        if has_error:
            for line in html.split('\n'):
                if any(e in line for e in errors):
                    err_snippet = line.strip()[:150]
                    break
        ok = (status == expected_status and not has_error and content_ok)
        results.append({'label': label, 'path': path, 'status': status, 'expected': expected_status, 'ok': ok, 'has_php_error': has_error, 'err_snippet': err_snippet, 'content_ok': content_ok})
        return html
    except Exception as e:
        results.append({'label': label, 'path': path, 'status': 'ERR', 'expected': expected_status, 'ok': False, 'has_php_error': False, 'err_snippet': str(e), 'content_ok': False})
        return ''

# Step 1: Register a new user
rnd = random.randint(50000, 99999)
phone = f"01900{rnd}"
email = f"testaudit{rnd}@fastsite.local"
nid   = f"2000{rnd}12"

cj_reg = http.cookiejar.CookieJar()
reg_opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj_reg), NoRedirectHandler)

reg_data = urllib.parse.urlencode({
    'name': f'Audit User {rnd}',
    'phone': phone,
    'whatsapp': phone,
    'email': email,
    'password': 'audit_pass_123',
    'dob': '1995-01-10',
    'gender': 'Male',
    'nid_number': nid,
    'profile_pic_b64': ''
}).encode()

reg_req = urllib.request.Request(BASE + '/user/register.php', data=reg_data, method='POST')
reg_res = reg_opener.open(reg_req, timeout=8)
reg_redirect = reg_res.headers.get('Location', 'NONE')
print(f"REGISTER -> status={reg_res.status}, redirect={reg_redirect}")

# Use reg session for auth tests
check(reg_opener, '/user/dashboard.php', 'User Dashboard (after register)', 200, 'Dashboard')
check(reg_opener, '/user/profile.php', 'User Profile Page', 200)
check(reg_opener, '/user/wallet.php', 'User Wallet Page', 200)
check(reg_opener, '/user/tasks.php', 'User Tasks Page', 200)
check(reg_opener, '/user/missions.php', 'User Missions Page', 200)
check(reg_opener, '/user/partner_orders.php', 'User Partner Orders', 200)
check(reg_opener, '/user/notifications.php', 'User Notifications', 200)
check(reg_opener, '/user/deposit.php', 'User Deposit Page', 200)
check(reg_opener, '/user/create_shop.php', 'User Create Shop', 200)
check(reg_opener, '/user/become_agent.php', 'User Become Agent', 200)
check(reg_opener, '/user/messages.php', 'User Messages', 200)

# Public pages
pub_opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirectHandler)
check(pub_opener, '/', 'Homepage / Marketplace', 200, 'Fast Site')
check(pub_opener, '/product_detail.php?id=1', 'Product Detail (ID=1)', 200)
check(pub_opener, '/user/login.php', 'User Login Page', 200)
check(pub_opener, '/user/register.php', 'User Register Page', 200, 'Create Free Account')
check(pub_opener, '/user/forgot_password.php', 'Forgot Password Page', 200)
check(pub_opener, '/track.php', 'Order Tracking Page', 200)

print("\n== AUDIT RESULTS ==")
ok_count = fail_count = 0
for r in results:
    icon = 'OK  ' if r['ok'] else 'FAIL'
    if r['ok']:
        ok_count += 1
        print(f"  [{icon}] [{r['status']}] {r['label']}")
    else:
        fail_count += 1
        note = ''
        if r['has_php_error']:
            note = f"PHP ERROR: {r['err_snippet']}"
        elif not r['content_ok']:
            note = 'CONTENT MISSING'
        elif str(r['status']) != str(r['expected']):
            note = f"Expected {r['expected']}, got {r['status']}"
        else:
            note = r['err_snippet']
        print(f"  [{icon}] [{r['status']}] {r['label']} => {r['path']}")
        if note:
            print(f"          >>> {note}")

print(f"\nTOTAL: {ok_count} OK, {fail_count} FAILED out of {len(results)} checks")
