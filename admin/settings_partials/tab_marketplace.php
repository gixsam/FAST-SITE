<?php /* Fast Site Settings Partial — sec-marketplace-config (Marketplace & Payments) */ ?>
  <div class="section-card collapsed" id="sec-marketplace-config">
    <div class="section-head" onclick="toggleSection('sec-marketplace-config')">
      <h2> MARKETPLACE & PAYMENT SETTINGS</h2>
      <span class="arrow"></span>
    </div>
    <div class="section-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_branding"/>
        
        <div class="field">
          <label>Platform Coin Name</label>
          <input type="text" name="coin_name" value="<?= htmlspecialchars($settings['coin_name'] ?? 'Fast Coin') ?>" placeholder="e.g. Fast Coin"/>
        </div>
        <div class="field">
          <label>User Cashback Percentage (%)</label>
          <input type="number" step="0.1" name="cashback_pct" value="<?= htmlspecialchars($settings['cashback_pct'] ?? '2') ?>" placeholder="e.g. 2"/>
        </div>
        <div class="field">
          <label>Gemini API Key (For AI Assistant)</label>
          <div style="font-size: 0.75rem; color: #fcb900; background: rgba(252,185,0,0.1); padding: 0.4rem 0.8rem; border-radius: 6px; margin-bottom: 0.5rem; border: 1px solid rgba(252,185,0,0.3);">
            <strong>Note:</strong> A Google One AI Premium subscription does NOT provide developer API access. You must create a FREE Developer API Key at <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color: #fff; text-decoration: underline;">Google AI Studio</a>.
          </div>
          <input type="text" name="gemini_api_key" value="<?= htmlspecialchars($settings['gemini_api_key'] ?? '') ?>" placeholder="AIzaSy..."/>
        </div>

        <div class="field" style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(255,255,255,0.06);">
          <strong style="color:var(--gold); display:block; margin-bottom:0.5rem;"> Marketplace WhatsApp Support</strong>
          <p style="font-size:0.75rem; color:var(--muted); margin-bottom:0.5rem;">Separate number for marketplace inquiries.</p>
          <input type="text" name="marketplace_whatsapp" value="<?= htmlspecialchars($settings['marketplace_whatsapp'] ?? '') ?>" placeholder="01963601472"/>
        </div>

        <div class="field" style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(255,255,255,0.06);">
          <strong style="color:var(--gold); display:block; margin-bottom:0.5rem;"> Platform Coin Name</strong>
          <p style="font-size:0.75rem; color:var(--muted); margin-bottom:0.5rem;">The currency name used for marketplace escrow (e.g. Fast Coin, Fast Points).</p>
          <input type="text" name="coin_name" value="<?= htmlspecialchars($settings['coin_name'] ?? 'Fast Coin') ?>" placeholder="e.g. Fast Coin"/>
        </div>

        <div class="field" style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(255,255,255,0.06);">
          <strong style="color:var(--gold); display:block; margin-bottom:0.5rem;"> Manual Payment Configuration</strong>
          <p style="font-size:0.75rem; color:var(--muted); margin-bottom:0.5rem;">Target mobile banking number for manual checkout transfers.</p>
          <input type="text" name="manual_payment_number" value="<?= htmlspecialchars($settings['manual_payment_number'] ?? '01963601472') ?>" placeholder="e.g. 01963601472"/>
        </div>

        <div class="grid2" style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(255,255,255,0.06);">
          <div class="field">
            <label>bKash QR Code Image</label>
            <?php if(!empty($settings['bkash_qr_url'])): ?>
              <div style="margin-bottom:0.5rem;"><img src="../<?= htmlspecialchars($settings['bkash_qr_url']) ?>" style="height:80px; border-radius:8px; border:1px solid var(--border); padding:4px;" alt="bKash QR"/></div>
            <?php endif; ?>
            <input type="file" name="bkash_qr_file" accept=".jpg,.png,.webp"/>
          </div>
          <div class="field">
            <label>Nagad QR Code Image</label>
            <?php if(!empty($settings['nagad_qr_url'])): ?>
              <div style="margin-bottom:0.5rem;"><img src="../<?= htmlspecialchars($settings['nagad_qr_url']) ?>" style="height:80px; border-radius:8px; border:1px solid var(--border); padding:4px;" alt="Nagad QR"/></div>
            <?php endif; ?>
            <input type="file" name="nagad_qr_file" accept=".jpg,.png,.webp"/>
          </div>
        </div>

        <div class="field" style="margin-bottom:1rem;">
          <strong style="color:var(--gold); display:block; margin-bottom:0.5rem;"> Shipping Cost Parameters</strong>
        </div>
        <div class="grid2">
          <div class="field">
            <label>Inside Dhaka (Tk)</label>
            <input type="number" name="shipping_dhaka_in" value="<?= htmlspecialchars($settings['shipping_dhaka_in'] ?? '60') ?>" min="0"/>
          </div>
          <div class="field">
            <label>Outside Dhaka (Tk)</label>
            <input type="number" name="shipping_dhaka_out" value="<?= htmlspecialchars($settings['shipping_dhaka_out'] ?? '120') ?>" min="0"/>
          </div>
        </div>
        <div class="field">
            <label>Soft File / Digital Item (Tk)</label>
            <input type="number" name="shipping_soft" value="<?= htmlspecialchars($settings['shipping_soft'] ?? '0') ?>" min="0"/>
        </div>

        <button type="submit" class="btn" style="margin-top:1.5rem;"> Save Marketplace & Payment Config</button>
      </form>
    </div>
  </div>
