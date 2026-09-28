// index.js - Unified AI Chatbot Server
require('dotenv').config();
const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
const axios = require('axios');
const { GoogleGenerativeAI } = require('@google/generative-ai');

const app = express();
app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 5000;
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

// Initialize Gemini
const GEMINI_API_KEY = process.env.GEMINI_API_KEY;
let genAI = null;
if (GEMINI_API_KEY && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY') {
  try {
    genAI = new GoogleGenerativeAI(GEMINI_API_KEY);
    console.log('Google Generative AI initialized successfully.');
  } catch (err) {
    console.error('Error initializing Google Generative AI:', err.message);
  }
} else {
  console.warn('WARNING: GEMINI_API_KEY is not set. Chatbot will run in simulation mode.');
}

// Brand mapping and details
const BRANDS = {
  'fast-site': {
    name: 'Fast Site Assistant',
    instruction: `You are a bilingual smart AI Chatbot for Fast Site (fastsite.best-travel.ltd). Fast Site is a multi-service marketplace and directory. The other 4 sites are: Best Travel (travel agency), AT Ayra Mart (premium dress/fashion shop), Affi Bangla (affiliate marketing aggregator), and Enzor Motors (automobile parts shop). You must act as the general assistant for Fast Site. You know that these other 4 sites have stores/shops on the Fast Site Marketplace. However, you should stay quiet about them and not proactively talk about them unless the user explicitly asks about them or about travel, fashion, affiliate deals, or auto parts. You must communicate in a mix of Bangla and English (Banglish/Bangla phonetics is highly welcomed, like "Kemon achen? Kibhabe sahajjo korte pari?"). Your goal is to collect the client's information to fill a lead form: Name, Phone (WhatsApp number), Email, and their specific query/demand. Be friendly and conversational. Do not ask for all fields at once; ask one by one in a friendly way. Once you have successfully collected all 4 fields (Name, Phone, Email, and Demand), you MUST append this exact JSON structure on a new line at the very end of your response: {"lead_complete": true, "name": "<name>", "phone": "<phone>", "email": "<email>", "demand": "<demand>"}.`
  },
  'best-travel': {
    name: 'Best Travel Consultant',
    instruction: `You are a bilingual smart AI Chatbot for Best Travel (best-travel.ltd), a premium travel agency providing flights, tour packages, hotel bookings, and visa services. You must communicate in a mix of Bangla and English (Banglish/Bangla phonetics, e.g. "Apnar destination kothay? Kobe jete chan?"). Your goal is to help users with their travel queries and collect their details for a booking lead: Name, Phone (WhatsApp), Email, and their travel demand (destination, dates, number of travelers, budget). Ask for these details one by one. Once you have collected all 4 fields (Name, Phone, Email, and Demand), you MUST append this exact JSON structure on a new line at the very end of your response: {"lead_complete": true, "name": "<name>", "phone": "<phone>", "email": "<email>", "demand": "<demand>"}.`
  },
  'ayra-mart': {
    name: 'Ayra Mart Fashion Guide',
    instruction: `You are a bilingual smart AI Chatbot for AT Ayra Mart (atayramart.com), a premium fashion e-commerce shop selling traditional and fashionable dresses (panjabi, sarees, three-piece sets). You must communicate in a mix of Bangla and English (Banglish/Bangla phonetics, e.g. "Ki dhoroner dress khujchen?"). Help users find products, ask about sizes, color preferences, and collect their details for a lead/order: Name, Phone (WhatsApp), Email, and their specific interest/demand. Ask one by one. Once you have collected all 4 fields (Name, Phone, Email, and Demand), you MUST append this exact JSON structure on a new line at the very end of your response: {"lead_complete": true, "name": "<name>", "phone": "<phone>", "email": "<email>", "demand": "<demand>"}.`
  },
  'affi-bangla': {
    name: 'Affi Bangla Deal Finder',
    instruction: `You are a bilingual smart AI Chatbot for Affi Bangla (affibangla.best-travel.ltd), an affiliate aggregator offering deals, coupons, reviews, and product comparisons. You must communicate in a mix of Bangla and English (Banglish, e.g. "Kono specific product er coupon ba review khujchen?"). Help users find the best deals and coupons, and collect their details for a lead/newsletter sign-up: Name, Phone (WhatsApp), Email, and their specific query/interests. Ask one by one. Once you have collected all 4 fields (Name, Phone, Email, and Demand), you MUST append this exact JSON structure on a new line at the very end of your response: {"lead_complete": true, "name": "<name>", "phone": "<phone>", "email": "<email>", "demand": "<demand>"}.`
  },
  'enzor': {
    name: 'Enzor Motors Advisor',
    instruction: `You are a bilingual smart AI Chatbot for Enzor Motors (enzor.best-travel.ltd), a premium automobile parts shop selling car parts, accessories, and components. You must communicate in a mix of Bangla and English (Banglish, e.g. "Apnar gari er ki parts lagbe? Model number ti bolben?"). Help users check for parts and collect their details for a quotation lead: Name, Phone (WhatsApp), Email, and their specific parts/car demand. Ask one by one. Once you have collected all 4 fields (Name, Phone, Email, and Demand), you MUST append this exact JSON structure on a new line at the very end of your response: {"lead_complete": true, "name": "<name>", "phone": "<phone>", "email": "<email>", "demand": "<demand>"}.`
  }
};

let botNames = {
  'fast-site': 'Fast Site Assistant',
  'best-travel': 'Best Travel Guide',
  'ayra-mart': 'Ayra Mart Fashion Bot',
  'affi-bangla': 'Affi Bangla Deal Finder',
  'enzor': 'Enzor Motors Advisor'
};

async function refreshBotNames() {
  try {
    const phpDomain = process.env.FAST_SITE_URL || 'http://localhost';
    const res = await axios.get(`${phpDomain}/api/get_chatbot_settings.php`);
    if (res.data && res.data.status === 'success' && res.data.data) {
      botNames = res.data.data;
      console.log('[Bot Settings] Loaded names from database:', botNames);
    }
  } catch (e) {
    console.warn('[Bot Settings] Could not load bot names from API, using default/cached names.', e.message);
  }
}

// Initial fetch
refreshBotNames();

// In-memory conversation history and session states
const sessions = {};

// Facebook Page Mapping
const PAGE_BRAND_MAP = {
  '1096634650208777': 'fast-site',
  '821426857726532': 'best-travel',
  '938505109357388': 'ayra-mart'
};

const BRAND_PAGE_TOKENS = {
  'fast-site': process.env.FB_PAGE_TOKEN_FAST_SITE,
  'best-travel': process.env.FB_PAGE_TOKEN_BEST_TRAVEL,
  'ayra-mart': process.env.FB_PAGE_TOKEN_AYRA_MART
};

const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const pino = require('pino');
const qrcode = require('qrcode');
const path = require('path');

const sockets = {
  sayam: null,
  akash: null,
  shakib: null
};

const qrCodes = {
  sayam: null,
  akash: null,
  shakib: null
};

const connectionStatus = {
  sayam: 'Disconnected',
  akash: 'Disconnected',
  shakib: 'Disconnected'
};

async function startWhatsAppSession(sessionName) {
  try {
    const authDir = path.join(__dirname, `auth_info_${sessionName}`);
    const { state, saveCreds } = await useMultiFileAuthState(authDir);

    const sock = makeWASocket({
      auth: state,
      printQRInTerminal: false,
      logger: pino({ level: 'silent' })
    });

    sockets[sessionName] = sock;

    sock.ev.on('connection.update', async (update) => {
      const { connection, lastDisconnect, qr } = update;
      if (qr) {
        try {
          qrCodes[sessionName] = await qrcode.toDataURL(qr);
        } catch (err) {
          console.error(`Error generating QR code for ${sessionName}:`, err.message);
        }
      }

      if (connection === 'close') {
        connectionStatus[sessionName] = 'Disconnected';
        qrCodes[sessionName] = null;
        
        const lastDisconnectError = lastDisconnect?.error;
        const statusCode = lastDisconnectError?.output?.statusCode;
        const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
        
        console.log(`[WhatsApp ${sessionName}] Connection closed. Reconnecting: ${shouldReconnect}`);
        if (shouldReconnect) {
          setTimeout(() => startWhatsAppSession(sessionName), 5000);
        }
      } else if (connection === 'open') {
        connectionStatus[sessionName] = 'Connected';
        qrCodes[sessionName] = null;
        console.log(`[WhatsApp ${sessionName}] Connection established successfully!`);
      }
    });

    sock.ev.on('creds.update', saveCreds);
  } catch (err) {
    console.error(`[WhatsApp ${sessionName}] Failed to start session:`, err.message);
    setTimeout(() => startWhatsAppSession(sessionName), 10000);
  }
}

// Start WhatsApp Sessions
startWhatsAppSession('sayam');
startWhatsAppSession('akash');
startWhatsAppSession('shakib');

// QR Code HTML serving
app.get('/qr/:session', (req, res) => {
  const sessionName = req.params.session.toLowerCase();
  if (!['sayam', 'akash', 'shakib'].includes(sessionName)) {
    return res.status(404).send('Session not found');
  }

  const status = connectionStatus[sessionName];
  const qr = qrCodes[sessionName] || '';

  let html = `
<!DOCTYPE html>
<html>
<head>
  <title>Link WhatsApp - ${sessionName}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      background: #0f172a;
      color: #f8fafc;
      font-family: system-ui, sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
    }
    .card {
      background: rgba(30, 41, 59, 0.7);
      padding: 2.5rem;
      border-radius: 1rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      border: 1px solid rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      text-align: center;
      max-width: 400px;
    }
    h1 { margin-top: 0; color: #38bdf8; }
    img {
      background: white;
      padding: 10px;
      border-radius: 0.5rem;
      margin: 1.5rem 0;
      width: 250px;
      height: 250px;
    }
    .status {
      display: inline-block;
      padding: 0.5rem 1rem;
      border-radius: 9999px;
      font-weight: bold;
    }
    .Connected { background: #16a34a; color: white; }
    .Disconnected { background: #dc2626; color: white; }
  </style>
  <script>
    setInterval(() => {
      fetch('/status/wa/' + '${sessionName}')
        .then(res => res.json())
        .then(data => {
          if (data.status === 'Connected') {
            document.getElementById('status-badge').className = 'status Connected';
            document.getElementById('status-badge').innerText = 'Connected';
            document.getElementById('qr-container').innerHTML = '<h3 style="color:#4ade80">✓ Session Linked Successfully!</h3><p>You can close this tab now.</p>';
          } else {
            if (data.qr) {
              const img = document.getElementById('qr-img');
              if (img) img.src = data.qr;
              else {
                location.reload();
              }
            }
          }
        });
    }, 3000);
  </script>
</head>
<body>
  <div class="card">
    <h1>Link WhatsApp</h1>
    <p>Manager: <strong style="text-transform: capitalize;">${sessionName}</strong></p>
    <p>Status: <span id="status-badge" class="status ${status}">${status}</span></p>
    <div id="qr-container">
  `;

  if (status === 'Connected') {
    html += `
      <h3 style="color:#4ade80">✓ Session Linked Successfully!</h3>
      <p>You can close this tab now.</p>
    `;
  } else if (qr) {
    html += `
      <p>Scan this QR code with your WhatsApp Linked Devices:</p>
      <img id="qr-img" src="${qr}" alt="QR Code">
    `;
  } else {
    html += `
      <p style="color:#94a3b8">Generating QR code... Please wait.</p>
    `;
  }

  html += `
    </div>
  </div>
</body>
</html>
  `;

  res.send(html);
});

app.get('/status/wa/:session', (req, res) => {
  const sessionName = req.params.session.toLowerCase();
  if (!['sayam', 'akash', 'shakib'].includes(sessionName)) {
    return res.status(404).json({ error: 'Session not found' });
  }
  res.json({
    status: connectionStatus[sessionName],
    qr: qrCodes[sessionName]
  });
});

// Reusable WhatsApp Sender
async function sendWhatsAppAlert(bodyText, brandId) {
  let targetNumber = '8801337320544'; // Default (Sayam)
  let preferredSession = 'sayam';

  if (brandId === 'ayra-mart') {
    targetNumber = '8801866686524'; // Akash
    preferredSession = 'akash';
  } else if (brandId === 'enzor') {
    targetNumber = '8801627127534'; // Shakib
    preferredSession = 'shakib';
  }

  let sock = null;
  let activeSession = null;

  // Attempt preferred
  if (sockets[preferredSession] && connectionStatus[preferredSession] === 'Connected') {
    sock = sockets[preferredSession];
    activeSession = preferredSession;
  } else {
    // Attempt fallback from any other connected session
    for (const session of ['sayam', 'akash', 'shakib']) {
      if (sockets[session] && connectionStatus[session] === 'Connected') {
        sock = sockets[session];
        activeSession = session;
        break;
      }
    }
  }

  const jid = `${targetNumber}@s.whatsapp.net`;

  if (sock) {
    try {
      await sock.sendMessage(jid, { text: bodyText });
      console.log(`[WhatsApp Alert] Sent successfully to ${targetNumber} via ${activeSession}`);
    } catch (err) {
      console.error(`[WhatsApp Alert] Failed to send to ${targetNumber} via ${activeSession}:`, err.message);
    }
  } else {
    console.log(`[WhatsApp Simulation Alert] No WhatsApp sessions are connected. To ${targetNumber}:\n${bodyText}`);
  }
}

// Wrapper for compatibility
async function sendTwilioWhatsAppAlert(bodyText, brandId) {
  await sendWhatsAppAlert(bodyText, brandId);
}

// Core Chatbot Response Logic
async function handleChatInput(sessionId, brandId, userText, source = 'web') {
  if (!BRANDS[brandId]) brandId = 'fast-site';

  // Initialize session if not exists
  let isNewSession = false;
  if (!sessions[sessionId]) {
    sessions[sessionId] = {
      brand: brandId,
      source: source,
      history: [],
      notifiedStart: false,
      leadComplete: false
    };
    isNewSession = true;
  }

  if (isNewSession) {
    await refreshBotNames();
  }

  const brandName = botNames[brandId] || BRANDS[brandId].name;

  const session = sessions[sessionId];

  // ALERT 1: Chat Start Notification
  if (!session.notifiedStart) {
    session.notifiedStart = true;
    const startAlert = `🆕 [Chat Start Alert]\nA client has initiated a chat with the ${brandName} chatbot (${source}).\nSession ID: ${sessionId}`;
    await sendTwilioWhatsAppAlert(startAlert, brandId);
  }

  let rawResponse = '';

  if (genAI) {
    try {
      const model = genAI.getGenerativeModel({
        model: 'gemini-1.5-flash',
        systemInstruction: BRANDS[brandId].instruction
      });

      // Format history for Gemini chat
      const chatHistory = session.history.map(msg => ({
        role: msg.sender === 'user' ? 'user' : 'model',
        parts: [{ text: msg.text }]
      }));

      const chat = model.startChat({ history: chatHistory });
      const result = await chat.sendMessage(userText);
      rawResponse = result.response.text();
    } catch (err) {
      console.error(`[Gemini Error] brand: ${brandId}, session: ${sessionId}:`, err.message);
      rawResponse = `I'm sorry, I'm having trouble connecting to my brain right now. Can you try again?`;
    }
  } else {
    // Simulation Mode fallback
    const banglaGret = ["Kemon achen?", "Hello, ami kibhabe sahajjo korte pari?", "Apnar nam ti bolun kindly."];
    const mockGreet = banglaGret[Math.floor(Math.random() * banglaGret.length)];
    
    if (session.history.length === 0) {
      rawResponse = `${mockGreet} Welcome to ${brandName}. What is your name, phone, email, and requirements?`;
    } else if (session.history.length === 2) {
      rawResponse = "Ami apnar nam peyechi. Ebar apnar phone number ba WhatsApp number ti bolben?";
    } else if (session.history.length === 4) {
      rawResponse = "Apnar email address ti bolun kindly.";
    } else if (session.history.length === 6) {
      rawResponse = "Apnar demand ba requirements ti amader bolun.";
    } else {
      rawResponse = `Dhonnobad! I have recorded your details. A representative will contact you soon.\n\n{"lead_complete": true, "name": "Sayam Khan", "phone": "01866686524", "email": "sayam@fastsite.best-travel.ltd", "demand": "Looking for services"}`;
    }
  }

  // Parse Lead Completion JSON block
  let cleanedResponse = rawResponse;
  let leadData = null;

  const jsonMatch = rawResponse.match(/\{"lead_complete"\s*:\s*true[\s\S]*?\}/);
  if (jsonMatch) {
    try {
      leadData = JSON.parse(jsonMatch[0]);
      cleanedResponse = rawResponse.replace(jsonMatch[0], '').trim();
      
      // ALERT 2: Lead Captured Notification
      if (!session.leadComplete && leadData) {
        session.leadComplete = true;
        const leadAlert = `🏆 [Lead Captured Alert] (${brandName})\n` +
          `👤 Name: ${leadData.name || 'N/A'}\n` +
          `📞 Phone: ${leadData.phone || 'N/A'}\n` +
          `📧 Email: ${leadData.email || 'N/A'}\n` +
          `📝 Demand: ${leadData.demand || 'N/A'}\n` +
          `🔗 Session ID: ${sessionId}`;
        await sendTwilioWhatsAppAlert(leadAlert, brandId);
      }
    } catch (e) {
      console.error('Error parsing lead JSON block:', e.message);
    }
  }

  // Save history
  session.history.push({ sender: 'user', text: userText, timestamp: Date.now() });
  session.history.push({ sender: 'bot', text: cleanedResponse, timestamp: Date.now() });

  return {
    response: cleanedResponse,
    leadCaptured: !!leadData,
    leadDetails: leadData
  };
}

// ------------------------------------------------------------------
// WebSocket Socket.io Handler
// ------------------------------------------------------------------
io.on('connection', (socket) => {
  console.log(`[Socket] New connection: ${socket.id}`);

  socket.on('init', async (data) => {
    const { sessionId, brand } = data;
    socket.join(sessionId);
    console.log(`[Socket] Client initialized session: ${sessionId} for brand: ${brand}`);

    // If it's a new session, we run handleChatInput with a dummy trigger to initialize the session and alert start
    if (!sessions[sessionId]) {
      // Trigger chat initiation alert by calling handleChatInput with an empty or start message
      await handleChatInput(sessionId, brand, 'Hello', 'web');
      // Send welcoming message
      const welcomeText = brand === 'fast-site' 
        ? 'Assalamu Alaikum! Fast Site Smart Bot e apnake shagoto. Ami apnake amader services and website management somporke sahajjo korte pari. Apnar nam ti kindly bolben?'
        : `Assalamu Alaikum! ${BRANDS[brand]?.name || 'Assistant'} e apnake shagoto. Ami apnake kibhabe sahajjo korte pari? Kindly apnar nam ti bolben?`;
      socket.emit('message', { sender: 'bot', text: welcomeText });
    }
  });

  socket.on('message', async (data) => {
    const { sessionId, brand, text } = data;
    if (!sessionId || !text) return;

    socket.emit('typing', true);

    const result = await handleChatInput(sessionId, brand, text, 'web');

    socket.emit('typing', false);
    socket.emit('message', { sender: 'bot', text: result.response });
  });

  socket.on('disconnect', () => {
    console.log(`[Socket] Disconnected: ${socket.id}`);
  });
});

// ------------------------------------------------------------------
// HTTP Webhook for Facebook Messenger & Instagram DMs
// ------------------------------------------------------------------

// Verification Endpoint for Facebook Webhook Setup
app.get('/webhook/messenger', (req, res) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  const verifyToken = process.env.FB_VERIFY_TOKEN;

  if (mode === 'subscribe' && token === verifyToken) {
    console.log('[Facebook Webhook] Verified successfully.');
    res.status(200).send(challenge);
  } else {
    res.sendStatus(403);
  }
});

// Handles incoming Facebook Messenger and Instagram DM messages
app.post('/webhook/messenger', async (req, res) => {
  res.sendStatus(200); // Acknowledge receipt immediately

  const body = req.body;

  if (body.object === 'page' && body.entry) {
    for (const entry of body.entry) {
      if (!entry.messaging) continue;
      
      const pageId = entry.id;
      const brandId = PAGE_BRAND_MAP[pageId] || 'fast-site';
      const pageToken = BRAND_PAGE_TOKENS[brandId];

      if (!pageToken) {
        console.warn(`[Facebook Webhook] Warning: Missing access token for Page ID ${pageId} (${brandId})`);
        continue;
      }

      for (const event of entry.messaging) {
        if (event.message && event.message.text && !event.message.is_echo) {
          const senderId = event.sender.id;
          const userText = event.message.text.trim();
          console.log(`[Facebook Webhook] Message from PSID ${senderId} on Page ${pageId} (${brandId}): "${userText}"`);

          const sessionId = `fb-${pageId}-${senderId}`;

          // Get reply from AI
          const result = await handleChatInput(sessionId, brandId, userText, 'facebook');

          // Send message back to user via Facebook Send API
          try {
            await axios.post(
              `https://graph.facebook.com/v20.0/me/messages?access_token=${pageToken}`,
              {
                recipient: { id: senderId },
                messaging_type: 'RESPONSE',
                message: { text: result.response }
              }
            );
            console.log(`[Facebook Send API] Replied to PSID ${senderId}`);
          } catch (err) {
            console.error('[Facebook Send API] Error replying to user:', err.response ? err.response.data : err.message);
          }
        }
      }
    }
  }
});

// ------------------------------------------------------------------
// Health Check and Leads API
// ------------------------------------------------------------------
app.get('/status', (req, res) => {
  res.json({
    status: 'online',
    active_sessions: Object.keys(sessions).length,
    brands_configured: Object.keys(BRANDS),
    gemini_active: !!genAI,
    webhook_verify_token: process.env.FB_VERIFY_TOKEN
  });
});

app.get('/leads', (req, res) => {
  const completedLeads = [];
  for (const sId in sessions) {
    const s = sessions[sId];
    if (s.leadComplete) {
      const lastMsg = s.history[s.history.length - 1];
      completedLeads.push({
        sessionId: sId,
        brand: s.brand,
        source: s.source,
        history_length: s.history.length,
        created_at: s.history[0]?.timestamp
      });
    }
  }
  res.json({ leads: completedLeads });
});

// Start Express Server
server.listen(PORT, () => {
  console.log(`================================================================`);
  console.log(`Unified AI Chatbot Server is running on port ${PORT}`);
  console.log(`WebSocket endpoint: ws://localhost:${PORT}`);
  console.log(`Facebook Messenger Webhook GET/POST endpoint: /webhook/messenger`);
  console.log(`================================================================`);
});
