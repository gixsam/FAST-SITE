<?php /* Fast Site Settings Partial — sec-general (General Branding & Settings) */ ?>
  <div class="section-card collapsed" id="sec-general">
    <div class="section-head" onclick="toggleSection('sec-general')">
      <h2> 1) GENERAL</h2>
      <span class="arrow"></span>
    </div>
    <div class="section-body" style="display:flex; flex-direction:column; gap:2rem;">
      <div style="border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:1.5rem;">
        <h3 style="color:var(--gold); font-size:1.05rem; font-weight:800; margin-bottom:1rem;"> BRANDING & GENERAL SETTINGS</h3>
        <div class="logo-preview-wrap">
        <?php if(!empty($settings['logo_url'])): ?>
          <img src="/<?= htmlspecialchars(ltrim($settings['logo_url'], '/')) ?>" class="logo-preview" alt="Logo"/>
          <div>
            <div style="font-weight:700; font-size:0.85rem;">Current Brand Logo</div>
            <div style="font-size:0.7rem; color:var(--muted);">Stored locally on Hostinger</div>
          </div>
        <?php else: ?>
          <div class="logo-preview" style="display:flex; align-items:center; justify-content:center; font-size:1.5rem;"></div>
          <div><div style="font-weight:700; font-size:0.85rem;">No logo uploaded.</div></div>
        <?php endif; ?>
      </div>
      
      <div class="grid2" style="border-top:1px solid rgba(255,255,255,0.06); margin-top:1.5rem; padding-top:1.5rem; gap:1.2rem;">
        <!-- ADMIN APK PREVIEW -->
        <div style="background:rgba(16,18,28,0.95); border:1px solid var(--gold); padding:1rem; border-radius:14px;">
          <h4 style="color:var(--gold); font-size:0.95rem; font-weight:800; margin-bottom:0.8rem;"> 🛡️ FAST SITE HQ (ADMIN PANEL APK)</h4>
          <div class="logo-preview-wrap" style="display:flex; align-items:center; gap:12px;">
            <?php if(!empty($settings['admin_apk_photo'])): ?>
              <img src="/<?= htmlspecialchars(ltrim($settings['admin_apk_photo'], '/')) ?>" class="logo-preview" style="border-radius:14px; width:65px; height:65px; object-fit:cover; border:2px solid var(--gold);" alt="FAST SITE HQ Icon"/>
            <?php else: ?>
              <img src="/assets/images/fast_site_hq_admin_icon.jpg" class="logo-preview" style="border-radius:14px; width:65px; height:65px; object-fit:cover; border:2px solid var(--gold);" alt="FAST SITE HQ Icon"/>
            <?php endif; ?>
            <div>
              <div style="font-weight:800; font-size:0.9rem; color:#fff;"><?= htmlspecialchars($settings['admin_apk_app_name'] ?? 'FAST SITE HQ') ?></div>
              <div style="font-size:0.75rem; color:var(--gold); font-weight:700;"><?= htmlspecialchars($settings['admin_apk_app_version'] ?? 'v2.0.4-hq') ?></div>
            </div>
          </div>
        </div>

        <!-- USER APK PREVIEW -->
        <div style="background:rgba(16,18,28,0.95); border:1px solid rgba(59,130,246,0.5); padding:1rem; border-radius:14px;">
          <h4 style="color:#60a5fa; font-size:0.95rem; font-weight:800; margin-bottom:0.8rem;"> 🌍 FAST SITE WORLD (USER MARKETPLACE APK)</h4>
          <div class="logo-preview-wrap" style="display:flex; align-items:center; gap:12px;">
            <?php if(!empty($settings['apk_app_photo'])): ?>
              <img src="/<?= htmlspecialchars(ltrim($settings['apk_app_photo'], '/')) ?>" class="logo-preview" style="border-radius:14px; width:65px; height:65px; object-fit:cover; border:2px solid #60a5fa;" alt="FAST SITE WORLD Icon"/>
            <?php else: ?>
              <img src="/assets/images/fast_site_world_app_icon.jpg" class="logo-preview" style="border-radius:14px; width:65px; height:65px; object-fit:cover; border:2px solid #60a5fa;" alt="FAST SITE WORLD Icon"/>
            <?php endif; ?>
            <div>
              <div style="font-weight:800; font-size:0.9rem; color:#fff;"><?= htmlspecialchars($settings['apk_app_name'] ?? 'FAST SITE WORLD') ?></div>
              <div style="font-size:0.75rem; color:#60a5fa; font-weight:700;"><?= htmlspecialchars($settings['apk_app_version'] ?? 'v2.0.4-world') ?></div>
            </div>
          </div>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_branding"/>
        <div class="grid2">
          <div class="field"><label>Site Name</label><input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'Fast Site') ?>"/></div>
          <div class="field"><label>WhatsApp Number (Admin)</label><input type="tel" name="whatsapp_number" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>" placeholder="e.g. 01963601472"/></div>
          <div class="field"><label>WhatsApp Number (Staff)</label><input type="tel" name="staff_whatsapp_number" value="<?= htmlspecialchars($settings['staff_whatsapp_number'] ?? '') ?>" placeholder="e.g. 018XXXXXXXX"/></div>
          <div class="field"><label>Admin Panel Header Name</label><input type="text" name="admin_header_name" value="<?= htmlspecialchars($settings['admin_header_name'] ?? 'FAST SITE') ?>"/></div>
          <div class="field"><label>Admin Panel Header Tagline</label><input type="text" name="admin_header_tag" value="<?= htmlspecialchars($settings['admin_header_tag'] ?? 'OVERPOWERED FUNCTIONAL SYSTEM') ?>"/></div>
          <div class="field"><label>Business Hours</label><input type="text" name="business_hours" value="<?= htmlspecialchars($settings['business_hours'] ?? '') ?>"/></div>
          <div class="field"><label>Upload Brand Logo (Unlimited Size)</label><input type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp"/><span style="font-size:0.72rem; color:red; display:block; margin-top:2px;">recommended size: 300x80 px (png preferred)</span></div>
          <div class="field"><label>Facebook URL</label><input type="url" name="facebook_url" value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/yourpage"/></div>
          <div class="field"><label>Instagram URL</label><input type="url" name="instagram_url" value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/yourprofile"/></div>
          <div class="field"><label>Twitter/X URL</label><input type="url" name="twitter_url" value="<?= htmlspecialchars($settings['twitter_url'] ?? '') ?>" placeholder="https://x.com/yourprofile"/></div>
          <div class="field"><label>YouTube Channel URL</label><input type="url" name="youtube_url" value="<?= htmlspecialchars($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/c/yourchannel"/></div>
          <div class="field"><label>Affi Bangla Blog URL</label><input type="url" name="affi_bangla_url" value="<?= htmlspecialchars($settings['affi_bangla_url'] ?? 'http://affibangla.best-travel.ltd') ?>" placeholder="e.g. http://affibangla.best-travel.ltd"/></div>
          
          <div style="grid-column:span 2; border-top:1px solid rgba(255,255,255,0.06); padding-top:1rem; margin-top:0.5rem;">
            <h4 style="color:var(--gold); font-size:0.9rem; font-weight:800; margin-bottom:1rem;">🛡️ FAST SITE HQ (ADMIN PANEL APK) SETTINGS</h4>
            <div class="grid2">
              <div class="field"><label>Admin App Display Name</label><input type="text" name="admin_apk_app_name" value="<?= htmlspecialchars($settings['admin_apk_app_name'] ?? 'FAST SITE HQ') ?>" placeholder="e.g. FAST SITE HQ"/></div>
              <div class="field"><label>FAST SITE HQ Logo / Icon Upload</label><input type="file" name="admin_apk_photo_file" accept=".jpg,.jpeg,.png,.webp"/><span style="font-size:0.72rem; color:#9ca3af; display:block; margin-top:2px;">Upload Admin Crown Shield icon</span></div>
              <div class="field"><label>FAST SITE HQ Download Link (.apk)</label><input type="text" name="admin_apk_download_url" value="<?= htmlspecialchars($settings['admin_apk_download_url'] ?? 'fastsite_hq.apk') ?>" placeholder="e.g. fastsite_hq.apk"/></div>
              <div class="field"><label>FAST SITE HQ Version</label><input type="text" name="admin_apk_app_version" value="<?= htmlspecialchars($settings['admin_apk_app_version'] ?? 'v2.0.4-hq') ?>" placeholder="e.g. v2.0.4-hq"/></div>
            </div>
          </div>

          <div style="grid-column:span 2; border-top:1px solid rgba(255,255,255,0.06); padding-top:1rem; margin-top:0.5rem;">
            <h4 style="color:#60a5fa; font-size:0.9rem; font-weight:800; margin-bottom:1rem;">🌍 FAST SITE WORLD (USER MARKETPLACE APK) SETTINGS</h4>
            <div class="grid2">
              <div class="field"><label>User App Display Name</label><input type="text" name="apk_app_name" value="<?= htmlspecialchars($settings['apk_app_name'] ?? 'FAST SITE WORLD') ?>" placeholder="e.g. FAST SITE WORLD"/></div>
              <div class="field"><label>FAST SITE WORLD Logo / Icon Upload</label><input type="file" name="apk_app_photo_file" accept=".jpg,.jpeg,.png,.webp"/><span style="font-size:0.72rem; color:#9ca3af; display:block; margin-top:2px;">Upload User App Globe icon</span></div>
              <div class="field"><label>FAST SITE WORLD Download Link (.apk)</label><input type="text" name="apk_download_url" value="<?= htmlspecialchars($settings['apk_download_url'] ?? 'fastsite_storefront.apk') ?>" placeholder="e.g. fastsite_storefront.apk"/></div>
              <div class="field"><label>FAST SITE WORLD Version</label><input type="text" name="apk_app_version" value="<?= htmlspecialchars($settings['apk_app_version'] ?? 'v2.0.4-world') ?>" placeholder="e.g. v2.0.4-world"/></div>
              <div class="field" style="grid-column:span 2; margin-top:0.5rem;">
                <label style="color:var(--gold); font-weight:800;">📱 Mobile APK Layout Grid Mode (Touch-Optimized)</label>
                <select name="mobile_grid_cols" style="background:#080911; border:1px solid var(--gold); color:#fff; padding:0.6rem; border-radius:8px;">
                  <option value="2" <?= ($settings['mobile_grid_cols'] ?? '2') === '2' ? 'selected' : '' ?>>✌️ Double-Column Grid (2 Cards Side-by-Side — Recommended for Mobile Apps)</option>
                  <option value="1" <?= ($settings['mobile_grid_cols'] ?? '2') === '1' ? 'selected' : '' ?>>☝️ Single-Column Stack (1 Full Width Card per Row)</option>
                </select>
                <span style="font-size:0.72rem; color:#9ca3af; display:block; margin-top:2px;">Customize how products and shop cards appear on smartphone screens and mobile APKs!</span>
              </div>
            </div>
          </div>

          <div class="field" style="grid-column:span 2; margin-top:1rem;"><label>Global Notice Bar Text (Appears in Dashboards)</label><input type="text" name="global_notice" value="<?= htmlspecialchars($settings['global_notice'] ?? 'Welcome to Fast Site World! Exciting new offers available.') ?>"/></div>
          
          <div style="grid-column: span 2; margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 1.5rem;">
            <h4 style="color:var(--gold); font-size:0.95rem; font-weight:800; margin-bottom:1rem;"> SMART CHATBOT CUSTOMIZATION (BOT NAMES)</h4>
            <div class="grid2">
              <div class="field"><label>Fast Site Bot Name</label><input type="text" name="chatbot_name_fast_site" value="<?= htmlspecialchars($settings['chatbot_name_fast_site'] ?? 'Fast Site Assistant') ?>"/></div>
              <div class="field"><label>Best Travel Bot Name</label><input type="text" name="chatbot_name_best_travel" value="<?= htmlspecialchars($settings['chatbot_name_best_travel'] ?? 'Best Travel Guide') ?>"/></div>
              <div class="field"><label>Ayra Mart Bot Name</label><input type="text" name="chatbot_name_ayra_mart" value="<?= htmlspecialchars($settings['chatbot_name_ayra_mart'] ?? 'Ayra Mart Fashion Bot') ?>"/></div>
              <div class="field"><label>Affi Bangla Bot Name</label><input type="text" name="chatbot_name_affi_bangla" value="<?= htmlspecialchars($settings['chatbot_name_affi_bangla'] ?? 'Affi Bangla Deal Finder') ?>"/></div>
              <div class="field"><label>Enzor Motors Bot Name</label><input type="text" name="chatbot_name_enzor" value="<?= htmlspecialchars($settings['chatbot_name_enzor'] ?? 'Enzor Motors Advisor') ?>"/></div>
            </div>
          </div>
        </div>
        <button type="submit" class="btn" style="margin-top: 1rem;"> Save Branding</button>
      </form>
    </div>
      <div style="border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:1.5rem;">
        <h3 style="color:var(--gold); font-size:1.05rem; font-weight:800; margin-bottom:1rem;"> MY PERSONAL PROFILE & SOCIALS</h3>
        <form method="POST">
        <input type="hidden" name="action" value="update_staff_profile"/>
        <div class="grid2">
          <div class="field">
            <label>My Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($staffUser['email'] ?? '') ?>" placeholder="personal@email.com"/>
          </div>
          <div class="field">
            <label>My WhatsApp Number</label>
            <input type="tel" name="whatsapp" value="<?= htmlspecialchars($staffUser['whatsapp'] ?? '') ?>" placeholder="e.g. 01963601472"/>
          </div>
          <div class="field">
            <label>My Facebook URL</label>
            <input type="url" name="facebook" value="<?= htmlspecialchars($staffUser['facebook'] ?? '') ?>" placeholder="https://facebook.com/myprofile"/>
          </div>
          <div class="field">
            <label>My Instagram URL</label>
            <input type="url" name="instagram" value="<?= htmlspecialchars($staffUser['instagram'] ?? '') ?>" placeholder="https://instagram.com/myprofile"/>
          </div>
          <div class="field">
            <label>My Twitter/X URL</label>
            <input type="url" name="twitter" value="<?= htmlspecialchars($staffUser['twitter'] ?? '') ?>" placeholder="https://x.com/myprofile"/>
          </div>
          <div class="field">
            <label>My YouTube URL</label>
            <input type="url" name="youtube" value="<?= htmlspecialchars($staffUser['youtube'] ?? '') ?>" placeholder="https://youtube.com/mychannel"/>
          </div>
        </div>
        <button type="submit" class="btn"> Save My Socials</button>
      </form>
    </div>
      <div>
        <h3 style="color:var(--gold); font-size:1.05rem; font-weight:800; margin-bottom:1rem;"> CHANGE PASSWORD</h3>
        <form method="POST">
        <input type="hidden" name="action" value="change_password"/>
        <div class="field"><label>Current Password</label><input type="password" name="current_password" required/></div>
        <div class="grid2">
          <div class="field"><label>New Password</label><input type="password" name="new_password" required/></div>
          <div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" required/></div>
        </div>
        <button type="submit" class="btn"> Update Password</button>
      </form>
    </div>
    </div>
  </div>
