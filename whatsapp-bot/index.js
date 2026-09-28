require('dotenv').config();
const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const qrcode = require('qrcode-terminal');
const fs = require('fs');
const path = require('path');
const pino = require('pino');
const { GoogleGenerativeAI } = require('@google/generative-ai');

const GEMINI_API_KEY = process.env.GEMINI_API_KEY;
const ADMIN_WHATSAPP_NUMBER = process.env.ADMIN_WHATSAPP_NUMBER || ''; // e.g., '8801700000000'

let genAI = null;
let model = null;

if (GEMINI_API_KEY && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY') {
    genAI = new GoogleGenerativeAI(GEMINI_API_KEY);
    model = genAI.getGenerativeModel({
        model: 'gemini-1.5-flash',
        systemInstruction: `You are the AI support assistant for Fast Site, a premium digital marketplace and service portal. 
        You help users with general queries, marketplace orders, wallet deposits, and partner inquiries. Keep your tone professional, helpful, and concise.
        ESCALATION RULE: If the user asks for a human, admin, manager, or complains, you MUST reply with exactly '[ESCALATE] I am looping in a senior staff member to assist you. Please hold on.'`
    });
}

const PAUSE_FILE = path.join(__dirname, 'paused_users.json');

function getPausedUsers() {
    if (!fs.existsSync(PAUSE_FILE)) {
        fs.writeFileSync(PAUSE_FILE, JSON.stringify([]));
    }
    return JSON.parse(fs.readFileSync(PAUSE_FILE, 'utf8'));
}

function pauseUser(phone) {
    const db = getPausedUsers();
    if (!db.includes(phone)) {
        db.push(phone);
        fs.writeFileSync(PAUSE_FILE, JSON.stringify(db, null, 2));
    }
}

function isUserPaused(phone) {
    return getPausedUsers().includes(phone);
}

async function startBot() {
    const { state, saveCreds } = await useMultiFileAuthState('auth_info_baileys');
    const sock = makeWASocket({
        auth: state,
        printQRInTerminal: false,
        logger: pino({ level: 'silent' }) // suppress verbose logs
    });

    sock.ev.on('connection.update', (update) => {
        console.log('Connection update:', update);
        const { connection, lastDisconnect, qr } = update;
        if (qr) {
            qrcode.generate(qr, { small: true });
            console.log('Scan the QR code above with your WhatsApp app to link Fast Site Bot.');
        }
        if (connection === 'close') {
            const shouldReconnect = lastDisconnect.error?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('Connection closed due to', lastDisconnect.error, ', reconnecting:', shouldReconnect);
            if (shouldReconnect) {
                startBot();
            }
        } else if (connection === 'open') {
            console.log('✅ Fast Site WhatsApp Bot is now ONLINE and connected!');
        }
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('messages.upsert', async (m) => {
        const msg = m.messages[0];
        if (!msg.message || msg.key.fromMe) return;

        const senderId = msg.key.remoteJid;
        const text = msg.message.conversation || msg.message.extendedTextMessage?.text;

        if (!text) return;

        // Extract phone number from JID
        const phone = senderId.split('@')[0];

        // Skip groups
        if (senderId.includes('@g.us')) return;

        console.log(`[Message from ${phone}]: ${text}`);

        // Check if user is paused (Live Agent Handoff active)
        if (isUserPaused(phone)) {
            console.log(`User ${phone} is paused. AI will not reply.`);
            return;
        }

        // If AI is not configured, just return
        if (!model) return;

        try {
            const result = await model.generateContent(text);
            const responseText = result.response.text();

            if (responseText.includes('[ESCALATE]')) {
                // Remove the prefix
                const cleanResponse = responseText.replace('[ESCALATE]', '').trim();
                
                // Send message to user
                await sock.sendMessage(senderId, { text: cleanResponse });
                
                // Pause AI for this user
                pauseUser(phone);
                
                // Send alert to Admin/Staff
                if (ADMIN_WHATSAPP_NUMBER) {
                    const adminJid = `${ADMIN_WHATSAPP_NUMBER}@s.whatsapp.net`;
                    const alertMsg = `🚨 *URGENT HANDOFF REQUIRED* 🚨\n\nUser: +${phone}\nReason: The AI Bot escalated this conversation.\n\nPlease take over the chat immediately!`;
                    await sock.sendMessage(adminJid, { text: alertMsg });
                    console.log(`Admin alert sent to ${ADMIN_WHATSAPP_NUMBER}`);
                }

            } else {
                await sock.sendMessage(senderId, { text: responseText });
            }
        } catch (error) {
            console.error('Error generating AI response:', error);
        }
    });
}

startBot();
