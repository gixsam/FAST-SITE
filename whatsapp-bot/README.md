# WhatsApp Business Cloud API & Gemini Chatbot Server

This is a production-ready Node.js Express server that integrates the official **WhatsApp Business Cloud API** with **Google Gemini 1.5 Flash** to automate customer service for:
1. **Best Travel** (Active immediately)
2. **Ayra Mart** (Standby, pre-configured)

Unlike scraping libraries, this official API-based solution runs extremely fast, uses virtually zero RAM/CPU (no browser automation required), and carries **zero risk** of phone number bans.

---

## 🛠️ Step-by-Step Meta API Setup

### Step 1: Create a Meta Developer App
1. Go to the [Meta for Developers Portal](https://developers.facebook.com/) and log in.
2. Click **My Apps** -> **Create App**.
3. Choose **Other** -> **Business** (or Choose **WhatsApp** if prompted).
4. Enter an App Name (e.g., `Best Travel Bot`) and select your Business Manager account, then click **Create App**.

### Step 2: Set Up WhatsApp Product
1. On the App Dashboard, scroll down to **Add products to your app** and click **Set up** under **WhatsApp**.
2. Click **Start using the API**.
3. Meta will generate a **Temporary Access Token** and a **Test Phone Number**.
4. In **Step 1: Send and receive messages**, add your personal phone number as a recipient to test.

### Step 3: Get a Permanent Access Token
Temporary tokens expire after 24 hours. To make your bot run continuously in production:
1. Go to your [Meta Business Suite Settings](https://business.facebook.com/settings).
2. Go to **Users** -> **System Users**.
3. Click **Add** to create a new System User (choose role: `Admin`).
4. Click **Generate New Token**.
5. Select your WhatsApp App from the dropdown list.
6. Check the permissions: `whatsapp_business_messaging` and `whatsapp_business_management`.
7. Click **Generate Token**. Copy and save this token (it will not be shown again). This is your **permanent access token** for the `.env` file!

### Step 4: Configure Webhooks
1. In your Meta App Dashboard, go to **WhatsApp** -> **Configuration**.
2. Click **Edit** under **Webhook**.
3. Set your Callback URL: `https://your-domain.com/webhook/best-travel` (or `/webhook/ayra-mart`).
4. Set the Verify Token: A custom string of your choice (must match the token in your `.env` file).
5. Click **Verify and save**. Meta will ping your server to verify the endpoint.
6. Under **Webhook fields**, click **Manage** and check **messages**. Click **Done**.

---

## 🚀 Hosting on Hostinger

### Method A: Hostinger Shared Node.js Hosting
If your Hostinger plan includes Node.js support:
1. Go to your Hostinger HPanel, navigate to the **Node.js** section, and click **Create Application**.
2. Upload this `whatsapp-bot` folder contents to the specified directory.
3. Configure the environment variables in HPanel or upload the `.env` file.
4. Set the **Application Startup File** to `index.js`.
5. Click **Run NPM Install** and then click **Start/Restart**.

### Method B: Hostinger VPS (Recommended for full control)
1. SSH into your VPS:
   ```bash
   ssh root@your-vps-ip
   ```
2. Install Node.js:
   ```bash
   curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
   sudo apt-get install -y nodejs
   ```
3. Clone/Upload this folder to your VPS.
4. Install PM2 (Process Manager) to keep the app running forever:
   ```bash
   npm install -g pm2
   pm2 start index.js --name "whatsapp-bot"
   pm2 save
   pm2 startup
   ```

---

## 🔧 Admin Controls & Unpause

If a customer triggers an escalation (e.g., asking for a human manager or complaining about a refund), the bot replies with the configured escalation message and **automatically pauses** AI responses for that number.

To reactivate the AI for a specific customer, make a simple browser request to:
`https://your-domain.com/unpause?phone=CUSTOMER_PHONE&session=best-travel`

To check all currently paused numbers and server status:
`https://your-domain.com/status`
