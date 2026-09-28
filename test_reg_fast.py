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

# Custom opener that doesn't hang on redirect
class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def http_error_302(self, req, fp, code, msg, headers):
        return fp
    http_error_301 = http_error_303 = http_error_307 = http_error_302

opener2 = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), NoRedirectHandler)

req = urllib.request.Request('http://127.0.0.1:8000/user/register.php', data=encoded_data, method='POST')
res = opener2.open(req, timeout=5)
print("Registration Status:", res.status)
print("Redirect Location:", res.headers.get('Location'))

# Now load dashboard with the cookies from registration
req_dash = urllib.request.Request('http://127.0.0.1:8000/user/dashboard.php')
res_dash = opener2.open(req_dash, timeout=5)
print("Dashboard Status:", res_dash.status)
dash_html = res_dash.read().decode('utf-8', errors='ignore')
print("Dashboard HTML length:", len(dash_html))
print("Contains Username:", f'Test Agent {rnd}' in dash_html)
print("Contains Welcome Modal/Notice:", 'welcome' in dash_html.lower() or 'dashboard' in dash_html.lower())
