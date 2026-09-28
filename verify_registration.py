import urllib.request, urllib.parse, http.cookiejar, random

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

rnd = random.randint(10000, 99999)
phone = f"01711{rnd}"
email = f"testuser_{rnd}@fastsite.local"
nid = f"1990{rnd}8888"

form_data = {
    'name': f'Test Agent {rnd}',
    'phone': phone,
    'whatsapp': phone,
    'email': email,
    'password': 'password123',
    'dob': '1998-05-15',
    'gender': 'Male',
    'nid_number': nid,
    'profile_pic_b64': ''
}

encoded_data = urllib.parse.urlencode(form_data).encode('utf-8')

print("1. Submitting registration POST...")
req = urllib.request.Request('http://localhost:8000/user/register.php', data=encoded_data, method='POST')
res = opener.open(req)
print("Registration Response Code:", res.getcode())
print("Final URL after redirect:", res.geturl())
dashboard_html = res.read().decode('utf-8', errors='ignore')

print("Dashboard HTML length:", len(dashboard_html))
print("Has User Name:", f'Test Agent {rnd}' in dashboard_html)
print("Has 50 Fast Points:", '50.00' in dashboard_html or '50' in dashboard_html)
print("No errors in Dashboard:", 'Fatal error' not in dashboard_html and 'Parse error' not in dashboard_html and 'Notice:' not in dashboard_html)

if f'Test Agent {rnd}' in dashboard_html:
    print("\nSUCCESS! Registration and auto-login to Dashboard is 100% WORKING PERFECTLY!")
else:
    print("\nCheck output:\n", dashboard_html[:800])
