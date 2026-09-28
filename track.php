<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Track Order — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --gold: #fcb900;
      --dark: #0a0a0f;
      --dark-card: rgba(18, 18, 26, 0.65);
      --border: rgba(255, 255, 255, 0.06);
      --text: #f8f8f8;
      --muted: #9ca3af;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: var(--dark); color: var(--text); min-height: 100vh; }
    
    .top-header {
      background: rgba(13, 13, 20, 0.9);
      backdrop-filter: blur(18px);
      border-bottom: 1px solid var(--border);
      padding: 0.8rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
    }
    .brand { font-size: 1.2rem; font-weight: 700; color: var(--gold); text-decoration: none; }
    
    .container { max-width: 600px; margin: 3rem auto; padding: 0 1rem; }
    .card { background: var(--dark-card); border: 1px solid var(--border); border-radius: 16px; padding: 2rem; }
    
    h1 { font-size: 1.8rem; margin-bottom: 0.5rem; text-align: center; }
    p { color: var(--muted); text-align: center; margin-bottom: 2rem; }
    
    .input-group { margin-bottom: 1rem; }
    input { width: 100%; padding: 1rem; border-radius: 8px; border: 1px solid var(--border); background: rgba(0,0,0,0.3); color: #fff; font-size: 1.1rem; }
    button { width: 100%; padding: 1rem; border-radius: 8px; border: none; background: var(--gold); color: #000; font-size: 1.1rem; font-weight: 700; cursor: pointer; }
    
    #result { margin-top: 2rem; display: none; padding: 1.5rem; border-radius: 8px; background: rgba(255,255,255,0.05); }
    .res-row { display: flex; justify-content: space-between; margin-bottom: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.8rem; }
    .res-label { color: var(--muted); font-size: 0.9rem; }
    .res-val { font-weight: 600; }
  </style>
</head>
<body>
  <div class="top-header">
    <a href="index.php" class="brand">← Back to Marketplace</a>
  </div>

  <div class="container">
    <div class="card">
      <h1>Track Public Order</h1>
      <p>Enter your Reference ID (e.g., FS-1025) to check escrow status securely.</p>
      
      <div class="input-group">
        <input type="text" id="refId" placeholder="Reference ID..." autocomplete="off">
      </div>
      <button onclick="trackOrder()">Track Order ➔</button>
      
      <div id="result"></div>
    </div>
  </div>

  <script>
    async function trackOrder() {
        const ref = document.getElementById('refId').value.trim();
        const resDiv = document.getElementById('result');
        if(!ref) return alert('Please enter a Reference ID');
        
        resDiv.style.display = 'block';
        resDiv.innerHTML = '<div style="text-align:center; color:#888;">Tracking...</div>';
        
        try {
            const req = await fetch('track_order.php?ref=' + encodeURIComponent(ref));
            const data = await req.json();
            if(data.success) {
                resDiv.innerHTML = `
                    <div class="res-row"><span class="res-label">Status</span><span class="res-val" style="color:var(--gold);">${data.order.status.toUpperCase()}</span></div>
                    <div class="res-row"><span class="res-label">Service/Product</span><span class="res-val">${data.order.service}</span></div>
                    <div class="res-row"><span class="res-label">Date</span><span class="res-val">${data.order.created_at}</span></div>
                    <div class="res-row"><span class="res-label">Name</span><span class="res-val">${data.order.user_name}</span></div>
                    <div style="text-align:center; margin-top:1rem; font-size:0.8rem; color:#888;">Escrow Protected 🛡️</div>
                `;
            } else {
                resDiv.innerHTML = `<div style="text-align:center; color:#ef4444;">${data.message}</div>`;
            }
        } catch(e) {
            resDiv.innerHTML = `<div style="text-align:center; color:#ef4444;">Network error.</div>`;
        }
    }
  </script>
</body>
</html>
