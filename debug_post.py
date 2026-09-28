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

req = urllib.request.Request('http://localhost:8000/user/register.php', data=encoded_data, method='POST')
try:
    res = opener.open(req)
    print("Response HTML:\n", res.read().decode('utf-8', errors='ignore'))
except urllib.error.HTTPError as e:
    print("HTTP Error:", e.code)
    print("Error content:\n", e.read().decode('utf-8', errors='ignore'))
