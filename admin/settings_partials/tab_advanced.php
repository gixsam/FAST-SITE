<?php /* Fast Site Settings Partial — sec-advance-settings (Advanced Settings — 6 sub-sections) */ ?>
  <div class="section-card collapsed" id="sec-advance-settings">
    <div class="section-head" onclick="toggleSection('sec-advance-settings')">
      <h2> 5) ADVANCE SETTING</h2>
      <span class="arrow"></span>
    </div>
    <div class="section-body" style="display:flex; flex-direction:column; gap:1.2rem; padding:1.5rem;">
      
      <!-- SUB-SECTION 1: VIEW OPTION -->
      <div class="sub-section-card collapsed" id="subsec-view-option" style="border:1px solid rgba(255,255,255,0.06); border-radius:12px; background:rgba(0,0,0,0.15); overflow:hidden;">
        <div class="sub-section-head" onclick="toggleSubSection('subsec-view-option')" style="padding:1rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.01);">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--gold); margin:0;"> VIEW OPTION</h3>
          <span class="sub-arrow"></span>
        </div>
        <div class="sub-section-body" style="padding:1.2rem;  border-top:1px solid rgba(255,255,255,0.04);">
          <form method="POST" style="margin-bottom: 1.5rem;">
            <input type="hidden" name="action" value="save_view_option"/>
            <div class="field" style="margin-bottom:1.2rem;">
              <label style="font-weight:700; font-size:0.8rem; text-transform:uppercase;">Default Dashboard View Redirect Target</label>
              <select name="default_dashboard_view" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.6rem; border-radius:8px; outline:none;">
                <option value="staff_dash" <?= ($settings['default_dashboard_view'] ?? '') === 'staff_dash' ? 'selected' : '' ?>> Staff Dashboard View (Default)</option>
                <option value="user_site" <?= ($settings['default_dashboard_view'] ?? '') === 'user_site' ? 'selected' : '' ?>> User View Site Storefront</option>
                <option value="user_dash" <?= ($settings['default_dashboard_view'] ?? '') === 'user_dash' ? 'selected' : '' ?>> User Dashboard View</option>
                <option value="api_dash" <?= ($settings['default_dashboard_view'] ?? '') === 'api_dash' ? 'selected' : '' ?>> API Partners Dashboard</option>
                <option value="agent_dash" <?= ($settings['default_dashboard_view'] ?? '') === 'agent_dash' ? 'selected' : '' ?>> Agent Partners Dashboard</option>
                <option value="affiliate_dash" <?= ($settings['default_dashboard_view'] ?? '') === 'affiliate_dash' ? 'selected' : '' ?>> Affiliate Partners Dashboard</option>
                <option value="shopper_dash" <?= ($settings['default_dashboard_view'] ?? '') === 'shopper_dash' ? 'selected' : '' ?>> Marketplace Shopper Dashboard</option>
              </select>
              <p style="font-size:0.75rem; color:var(--muted); margin-top:0.35rem;">Choose the default dashboard view the Admin Panel should route the administrator to. Visiting dashboard.php directly will perform this redirect unless bypassed (via ?no_redirect=1).</p>
            </div>
            <button type="submit" class="btn"> Save View Option</button>
          </form>
          
          <div style="border-top:1px solid rgba(255,255,255,0.06); padding-top:1rem;">
            <h4 style="font-size:0.82rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.8rem;">Direct Quick Links:</h4>
            <div style="display:flex; flex-direction:column; gap:0.6rem; max-width:400px;">
              <a href="../index.php" target="_blank" class="view-link" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;"> USER VIEW SITE</a>
              <a href="../user/dashboard.php" target="_blank" class="view-link" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;"> USER DASHBOARD VIEW</a>
              <a href="dashboard.php?no_redirect=1" class="view-link" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;"> STAFF DASHBOARD VIEW</a>
              <a href="api_partners.php" class="view-link" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;"> API PARTNERS DASHBOARD</a>
              <a href="../user/dashboard.php" target="_blank" class="view-link" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;"> AGENT PARTNERS DASHBOARD</a>
              <a href="partner_shops.php" class="view-link" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;"> AFFILIATE PARTNERS DASHBOARD</a>
              <a href="../user/partner_orders.php" target="_blank" class="view-link" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;"> MARKETPLACE SHOPPER DASHBOARD</a>
            </div>
          </div>
        </div>
      </div>

      <!-- SUB-SECTION 2: HOMEPAGE CUSTOMISE -->
      <div class="sub-section-card collapsed" id="subsec-homepage-customise" style="border:1px solid rgba(255,255,255,0.06); border-radius:12px; background:rgba(0,0,0,0.15); overflow:hidden;">
        <div class="sub-section-head" onclick="toggleSubSection('subsec-homepage-customise')" style="padding:1rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.01);">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--gold); margin:0;"> HOMEPAGE CUSTOMISE (HOMEPAGE SECTION'S SYSTEM)</h3>
          <span class="sub-arrow"></span>
        </div>
        <div class="sub-section-body" style="padding:1.2rem;  border-top:1px solid rgba(255,255,255,0.04);">
          
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_homepage_customise"/>
            
            <h4 style="color:var(--gold); font-size:0.85rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-bottom:1rem; text-transform:uppercase;">1. Rearrange & Toggle Storefront Sections</h4>
            
            <div id="homepage-reorder-container" style="display:flex; flex-direction:column; gap:0.5rem; max-width:450px; background:rgba(0,0,0,0.25); padding:1rem; border-radius:12px; border:1px solid var(--border); margin-bottom:1.5rem;">
              <!-- Javascript populates list -->
            </div>
            
            <input type="hidden" name="homepage_sections_order" id="homepage_sections_order_val" value="<?= htmlspecialchars($settings['homepage_sections_order'] ?? 'hero,overview,search,promo,trending,gov_slider,travel_slider,our_services,features,partners,track,blogs') ?>"/>
            <input type="hidden" name="homepage_sections_visibility" id="homepage_sections_visibility_val" value="<?= htmlspecialchars($settings['homepage_sections_visibility'] ?? 'hero,overview,search,promo,trending,gov_slider,travel_slider,our_services,features,partners,track,blogs') ?>"/>

            <h4 style="color:var(--gold); font-size:0.85rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-bottom:1rem; text-transform:uppercase; margin-top:1.5rem;">2. Rename Sections & Upload Custom Logos (Optional)</h4>
            <p style="font-size:0.75rem; color:var(--muted); margin-bottom:1rem;">Provide a custom section heading (title), label/tag, or upload a custom section logo icon. Leave fields blank to keep default storefront titles and styling.</p>
            
            <div style="display:flex; flex-direction:column; gap:1.2rem;">
              <?php
                $sections_data = [
                  'overview' => ['label' => 'Welcome/Overview', 'has_title' => true, 'has_label' => true],
                  'trending' => ['label' => 'Trending Services', 'has_title' => true, 'has_label' => true],
                  'gov_slider' => ['label' => 'Government Services Slider', 'has_title' => true, 'has_label' => true],
                  'travel_slider' => ['label' => 'Travel Services Slider', 'has_title' => true, 'has_label' => true],
                  'our_services' => ['label' => 'Dynamic Our Services', 'has_title' => true, 'has_label' => true],
                  'features' => ['label' => 'Why Fast Site / Features', 'has_title' => true, 'has_label' => true],
                  'partners' => ['label' => 'Become Partners', 'has_title' => true, 'has_label' => true],
                  'track' => ['label' => 'Track Your Order', 'has_title' => true, 'has_label' => true],
                  'blogs' => ['label' => 'Tutorial Guides & News', 'has_title' => true, 'has_label' => true],
                ];
                foreach($sections_data as $skey => $sinfo):
                  $cur_lbl = $settings['homepage_label_' . $skey] ?? '';
                  $cur_title = $settings['homepage_title_' . $skey] ?? '';
                  $logo_path = $settings['homepage_logo_path_' . $skey] ?? '';
              ?>
                <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:10px;">
                  <strong style="color:var(--gold); font-size:0.8rem; display:block; margin-bottom:0.75rem; text-transform:uppercase;"><?= htmlspecialchars($sinfo['label']) ?> Section Settings</strong>
                  <div class="grid2" style="gap:1rem;">
                    <?php if($sinfo['has_label']): ?>
                      <div class="field">
                        <label>Section Tag Label</label>
                        <input type="text" name="homepage_label_<?= $skey ?>" value="<?= htmlspecialchars($cur_lbl) ?>" placeholder="e.g.  OFFICIAL CHANNELS"/>
                      </div>
                    <?php endif; ?>
                    <?php if($sinfo['has_title']): ?>
                      <div class="field">
                        <label>Section Main Title</label>
                        <input type="text" name="homepage_title_<?= $skey ?>" value="<?= htmlspecialchars($cur_title) ?>" placeholder="e.g. GOVERNMENT SERVICE"/>
                      </div>
                    <?php endif; ?>
                    <div class="field">
                      <label>Upload Section Logo (Optional)</label>
                      <input type="file" name="homepage_logo_file_<?= $skey ?>" accept=".jpg,.jpeg,.png,.webp"/>
                      <?php if($logo_path): ?>
                        <div style="margin-top:5px; display:flex; align-items:center; gap:8px;">
                          <span style="font-size:0.7rem; color:var(--muted);">Current Section Logo:</span>
                          <img src="../<?= htmlspecialchars($logo_path) ?>" style="height:20px; object-fit:contain; background:rgba(255,255,255,0.05); padding:2px; border-radius:4px;"/>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <div style="margin-top:1.5rem; margin-bottom: 2rem;">
              <button type="submit" class="btn"> Save Homepage Customization</button>
            </div>
          </form>
            
            <h4 style="color:var(--gold); font-size:0.85rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-bottom:1rem; text-transform:uppercase; margin-top:1.5rem;">3. Landing Descriptions & Government Slider play Content</h4>
            <form method="POST">
        <input type="hidden" name="action" value="save_branding"/>
        
        <h3 style="color:var(--gold); font-size:0.95rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-bottom:1rem;">What We Offer & Services Descriptions (ALL CAPS REQUIRED)</h3>
        
        <div class="field" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:4px;">
          <label style="font-weight:600; font-size:0.85rem;">What We Offer Description</label>
          <textarea name="what_we_offer" rows="2" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.5rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.85rem;"><?= htmlspecialchars($settings['what_we_offer'] ?? 'Premium credits, secure digital wallets, priority assistant services, PREMIUM DASHBOARD, EASY SERVICE.') ?></textarea>
        </div>
        
        <div class="field" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:4px;">
          <label style="font-weight:600; font-size:0.85rem;">Bangladesh Government Service Description</label>
          <textarea name="govt_service_desc" rows="3" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.5rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.85rem;"><?= htmlspecialchars($settings['govt_service_desc'] ?? 'BANGLADESH GOVERMENT SERVICE (NID, Passport, Birth/Death registration, Driving Licenses, land) FORM FILLUP AND CORRECTION ONLINE. NO HASSLE OF STANDING IN LINE.') ?></textarea>
        </div>
        
        <div class="field" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:4px;">
          <label style="font-weight:600; font-size:0.85rem;">Website Service Description</label>
          <textarea name="website_service_desc" rows="4" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.5rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.85rem;"><?= htmlspecialchars($settings['website_service_desc'] ?? 'WEBSITE SERVICE: WEBSITE BUILDING (LANDING PAGE, ECOMMERCE, WOOCOMMERCE), SEO, DOMAIN, HOSTING, ETC. WE DELIVER STATE-OF-THE-ART WEBSITES TAILORED FOR RAPID GROWTH. OUR FULL-SUITE SOLUTIONS INCLUDE PREMIUM SEO OPTIMIZATION, SECURE DOMAIN REGISTRATION, AND BLAZING-FAST HOSTING. ACCELERATE YOUR ONLINE PRESENCE WITH ZERO HASSLE AND GUARANTEED 99.9% UPTIME TO BUILD ABSOLUTE DIGITAL TRUST FOR YOUR BUSINESS.') ?></textarea>
        </div>
        
        <div class="field" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:4px;">
          <label style="font-weight:600; font-size:0.85rem;">Dropshipping API Description</label>
          <textarea name="dropshipping_api_desc" rows="4" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.5rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.85rem;"><?= htmlspecialchars($settings['dropshipping_api_desc'] ?? 'DROPSHIPPING API - SELL \'API\' TO THE DROPSHIPPERS. SYSTEMATICALLY EXPORT AND INTEGRATE INVENTORY WITH OUR PRE-BUILT DROPSHIPPING API. SELL OUR COMPLETE AND EXTENSIVE RANGE OF PRODUCTS DIRECTLY TO OTHER DROPSHIPPERS AND MERCHANTS WORLDWIDE. ENJOY AUTOMATED STOCK SYNCING, REAL-TIME PRICE UPDATES, AND INSTANT ORDER ROUTING. SCALE YOUR RESELLING OPERATION TO NEW HEIGHTS WITH OUR HIGHLY SECURED, SCALABLE, AND HIGH-SPEED API CONNECTIONS.') ?></textarea>
        </div>
        
        <div class="field" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:4px;">
          <label style="font-weight:600; font-size:0.85rem;">Marketplace Description</label>
          <textarea name="marketplace_desc" rows="4" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.5rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.85rem;"><?= htmlspecialchars($settings['marketplace_desc'] ?? 'MARKETPLACE - SHOPPER SELL THEIR OWN PRODUCT DIRECTLY IN THE MARKETPLACE. THERE ARE VARIETIES OF SHOPS FOR THE CUSTOMER TO BUY FROM HERE. A REVOLUTIONARY MULTI-VENDOR MARKETPLACE WHERE MERCHANTS AND INDEPENDENT SHOPPERS CAN SELL THEIR OWN UNIQUE PRODUCTS DIRECTLY TO CUSTOMERS. BROWSE THROUGH A WIDE VARIETY OF INDEPENDENT SHOPS OFFERING DIVERSE CATEGORIES. EXPERIENCE COMPLETE TRANSACTING SAFETY WITH FULL ESCROW PROTECTION, DIRECT SELLER COMMUNICATION, AND AN UNPARALLELED CONVENIENCE OF VARIETY.') ?></textarea>
        </div>

        <h3 style="color:var(--gold); font-size:0.95rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-top:1.5rem; margin-bottom:1rem;">Government Service Slideplay (Sub-sections) Customization</h3>
        
        <div class="grid2" style="gap:1rem; margin-bottom:1.5rem;">
          
          <!-- Slide 1 -->
          <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:8px; display:flex; flex-direction:column; gap:8px;">
            <strong style="color:var(--gold); display:block; margin-bottom:0.2rem; font-size:0.9rem;">Slide 1: Citizen Service</strong>
            <div class="field"><label>Title</label><input type="text" name="gov_slide1_title" value="<?= htmlspecialchars($settings['gov_slide1_title'] ?? 'CITIZEN SERVICE') ?>"/></div>
            <div class="field"><label>Description</label><textarea name="gov_slide1_desc" rows="3" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.4rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.8rem;"><?= htmlspecialchars($settings['gov_slide1_desc'] ?? '  - ASSISTANCE WITH NID, PASSPORTS, DRIVING LICENSES, BIRTH & DEATH REGISTRATIONS, AND MORE. ONLINE CORRECTIONS & APPLICATIONS.') ?></textarea></div>
            <div class="field"><label>Target Element ID</label><input type="text" name="gov_slide1_target" value="<?= htmlspecialchars($settings['gov_slide1_target'] ?? 'national') ?>"/></div>
          </div>
          
          <!-- Slide 2 -->
          <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:8px; display:flex; flex-direction:column; gap:8px;">
            <strong style="color:var(--gold); display:block; margin-bottom:0.2rem; font-size:0.9rem;">Slide 2: Smart Land Service</strong>
            <div class="field"><label>Title</label><input type="text" name="gov_slide2_title" value="<?= htmlspecialchars($settings['gov_slide2_title'] ?? 'SMART LAND SERVICE') ?>"/></div>
            <div class="field"><label>Description</label><textarea name="gov_slide2_desc" rows="3" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.4rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.8rem;"><?= htmlspecialchars($settings['gov_slide2_desc'] ?? '  - FAST AND TRANSPARENT PROCESSING FOR E-PORCHA, MUTATION, LAND TAX, DEED SEARCH, AND OFFICIAL LAND RECORD FORM FILLUP.') ?></textarea></div>
            <div class="field"><label>Target Element ID</label><input type="text" name="gov_slide2_target" value="<?= htmlspecialchars($settings['gov_slide2_target'] ?? 'land') ?>"/></div>
          </div>
          
          <!-- Slide 3 -->
          <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:8px; display:flex; flex-direction:column; gap:8px;">
            <strong style="color:var(--gold); display:block; margin-bottom:0.2rem; font-size:0.9rem;">Slide 3: MyGov BD</strong>
            <div class="field"><label>Title</label><input type="text" name="gov_slide3_title" value="<?= htmlspecialchars($settings['gov_slide3_title'] ?? 'MYGOV BD') ?>"/></div>
            <div class="field"><label>Description</label><textarea name="gov_slide3_desc" rows="3" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.4rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.8rem;"><?= htmlspecialchars($settings['gov_slide3_desc'] ?? '  - ONE-STOP SERVICE SYSTEM OF THE BANGLADESH GOVERNMENT. GET FAST AND SIMPLIFIED ACCESS TO 500+ DIVERSE PUBLIC SERVICES.') ?></textarea></div>
            <div class="field"><label>Target Element ID</label><input type="text" name="gov_slide3_target" value="<?= htmlspecialchars($settings['gov_slide3_target'] ?? 'mygov') ?>"/></div>
          </div>
          
          <!-- Slide 4 -->
          <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:8px; display:flex; flex-direction:column; gap:8px;">
            <strong style="color:var(--gold); display:block; margin-bottom:0.2rem; font-size:0.9rem;">Slide 4: Our Services</strong>
            <div class="field"><label>Title</label><input type="text" name="gov_slide4_title" value="<?= htmlspecialchars($settings['gov_slide4_title'] ?? 'OUR SERVICES') ?>"/></div>
            <div class="field"><label>Description</label><textarea name="gov_slide4_desc" rows="3" style="width:100%; background:var(--dark3); border:1px solid var(--border); color:#fff; padding:0.4rem; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.8rem;"><?= htmlspecialchars($settings['gov_slide4_desc'] ?? '  - CUSTOM, BLAZING-FAST AND HIGH-EFFICIENCY PRIVATE SOLUTIONS BUILT EXCLUSIVELY BY FAST SITE TO ASSIST WITH ALL TRANSACTIONS.') ?></textarea></div>
            <div class="field"><label>Target Element ID</label><input type="text" name="gov_slide4_target" value="<?= htmlspecialchars($settings['gov_slide4_target'] ?? 'dynamic-services') ?>"/></div>
          </div>
          
        </div>

        <button type="submit" class="btn"> Save Landing Page Customization</button>
      </form>
    </div>
          
        </div>

      <!-- SUB-SECTION 3: NAVIGATION CUSTOMISE -->
      <div class="sub-section-card collapsed" id="subsec-navigation-customise" style="border:1px solid rgba(255,255,255,0.06); border-radius:12px; background:rgba(0,0,0,0.15); overflow:hidden;">
        <div class="sub-section-head" onclick="toggleSubSection('subsec-navigation-customise')" style="padding:1rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.01);">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--gold); margin:0;"> NAVIGATION CUSTOMIZE</h3>
          <span class="sub-arrow"></span>
        </div>
        <div class="sub-section-body" style="padding:1.2rem;  border-top:1px solid rgba(255,255,255,0.04); display:flex; flex-direction:column; gap:2rem;">
          <div>
            <h4 style="color:var(--gold); font-size:0.85rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-bottom:1rem; text-transform:uppercase;">1. Rename Navigation Labels & Upload Custom Icons</h4>
            <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_nav_labels"/>
        <p style="font-size:0.8rem; color:var(--muted); margin-bottom:1.5rem;">Rename your navigation items, upload custom section logos (image/SVG), and choose if they display as <strong>LOGO ONLY</strong> in the top navigation bar:</p>
        
        <div style="display: flex; flex-direction: column; gap: 1.2rem;">
          
          <?php
            $nav_fields = [
              'dashboard' => ['label' => 'Dashboard', 'setting' => 'nav_dashboard', 'title' => 'Dashboard'],
              'service' => ['label' => 'Services', 'setting' => 'nav_services', 'title' => 'Services'],
              'all_user' => ['label' => 'All Users', 'setting' => 'nav_all_user', 'title' => 'All Users Dropdown'],
              'partners' => ['label' => 'Partners', 'setting' => 'nav_partners', 'title' => 'Partners Dropdown'],
              'directory' => ['label' => 'Trust Directory', 'setting' => 'nav_directory', 'title' => 'Trust Directory'],
              'payouts' => ['label' => 'Payouts', 'setting' => 'nav_payouts', 'title' => 'Payouts'],
              'give_task' => ['label' => 'Tasks', 'setting' => 'nav_tasks', 'title' => 'Give Task Dropdown'],
              'chat' => ['label' => 'Chats', 'setting' => 'nav_chats', 'title' => 'Chats Dropdown'],
              'settings' => ['label' => 'Settings', 'setting' => 'nav_settings', 'title' => 'Settings Dropdown'],
              'view_option' => ['label' => 'View Options', 'setting' => 'nav_view_option', 'title' => 'View Options Dropdown'],
              'access_control' => ['label' => 'Stuff Access Control', 'setting' => 'nav_access_control', 'title' => 'Stuff Access Control']
            ];
            
            // Sub-sections (label only)
            $sub_fields = [
              'nav_api_partners' => 'API Partners Dropdown Label',
              'nav_agents' => 'Agents Dropdown Label'
            ];
            
            foreach ($nav_fields as $key => $info):
              $lbl_val = $settings[$info['setting']] ?? $info['label'];
              $logo_only = ($settings['logo_only_' . $key] ?? '0') === '1' ? 'checked' : '';
              $logo_path = $settings['logo_path_' . $key] ?? '';
          ?>
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 1rem; border-radius: 10px;">
              <strong style="color:var(--gold); font-size:0.8rem; display:block; margin-bottom:0.75rem; text-transform:uppercase;"><?= htmlspecialchars($info['title']) ?></strong>
              <div class="grid2" style="gap: 1rem;">
                <div class="field">
                  <label>Rename Label</label>
                  <input type="text" name="<?= htmlspecialchars($info['setting']) ?>" value="<?= htmlspecialchars($lbl_val) ?>" style="font-size:0.85rem;"/>
                </div>
                <div class="field">
                  <label>Upload Custom Logo</label>
                  <input type="file" name="logo_file_<?= $key ?>" accept=".jpg,.jpeg,.png,.webp"/>
                  <span style="font-size:0.72rem; color:red; display:block; margin-top:2px;">recommended icon size: 100x100 px (square)</span>
                  <?php if(!empty($logo_path)): ?>
                    <div style="margin-top:5px; display:flex; align-items:center; gap:8px;">
                      <span style="font-size:0.7rem; color:var(--muted);">Current Logo:</span>
                      <img src="../<?= htmlspecialchars($logo_path) ?>" style="height:22px; object-fit:contain; background:rgba(255,255,255,0.05); padding:2px; border-radius:4px;"/>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="field" style="display:flex; align-items:center; gap:0.5rem; padding-top:1.5rem;">
                  <input type="checkbox" name="logo_only_<?= $key ?>" value="1" id="logo_only_<?= $key ?>" <?= $logo_only ?> style="width:auto; min-height:auto; cursor:pointer;"/>
                  <label for="logo_only_<?= $key ?>" style="margin:0; cursor:pointer; font-size:0.75rem; font-weight:700;">Show Logo Only (Hide Text)</label>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 1rem; border-radius: 10px;">
            <strong style="color:var(--gold); font-size:0.8rem; display:block; margin-bottom:0.75rem; text-transform:uppercase;">Sub-Section / Dropdown Labels</strong>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
              <?php foreach ($sub_fields as $sub_key => $sub_label): ?>
                <div class="field">
                  <label><?= htmlspecialchars($sub_label) ?></label>
                  <input type="text" name="<?= htmlspecialchars($sub_key) ?>" value="<?= htmlspecialchars($settings[$sub_key] ?? '') ?>"/>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div>
        
        <button type="submit" class="btn" style="margin-top: 1.5rem;"> Save Navigation Configurations</button>
      </form>
    </div>
          </div>
          <div>
            <h4 style="color:var(--gold); font-size:0.85rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-bottom:1rem; text-transform:uppercase;">2. Rearrange Navigation Ordering & Toggle Visibility</h4>
            <form method="POST">
        <input type="hidden" name="action" value="save_nav_positioning"/>
        <p style="font-size:0.8rem; color:var(--muted); margin-bottom:1rem;">Use the dropdown to select a menu or dropdown to rearrange, then use the Up () and Down () buttons and toggle visibility switches below:</p>
        
        <div class="field" style="max-width: 450px; margin-bottom: 1rem;">
          <label style="margin-bottom:0.5rem; display:block;">Select Menu / Dropdown to Reorder</label>
          <select id="reorder-section-selector" onchange="switchReorderSection()" style="width:100%; padding:0.6rem; border-radius:8px; background:#101018; border:1px solid var(--border); color:#fff; font-size:0.85rem; font-weight:600; cursor:pointer; outline:none;">
            <option value="main">TOP NAVIGATION BAR (MAIN SECTIONS)</option>
            <option value="all_user"> ALL USERS DROPDOWN ITEMS</option>
            <option value="partners"> PARTNERS DROPDOWN ITEMS</option>
            <option value="give_task"> GIVE TASK DROPDOWN ITEMS</option>
            <option value="chat"> CHATS DROPDOWN ITEMS</option>
            <option value="settings"> SYSTEM SETTINGS DROPDOWN ITEMS</option>
            <option value="view_option"> VIEW OPTIONS DROPDOWN ITEMS</option>
          </select>
        </div>

        <div class="field">
          <label style="margin-bottom:0.75rem; display:block;">Interactive Reorder & Visibility Controls</label>
          <div id="nav-reorder-list" style="display: flex; flex-direction: column; gap: 0.5rem; max-width: 450px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); padding: 0.8rem; border-radius: 12px; margin-bottom: 1rem;">
            <!-- Dynamically populated via javascript -->
          </div>
          <input type="hidden" name="nav_positioning" id="nav_positioning_val" value="<?= htmlspecialchars($settings['nav_positioning'] ?? 'dashboard,service,all_user,partners,directory,payouts,give_task,chat,settings,view_option,access_control') ?>"/>
          <input type="hidden" name="nav_visibility" id="nav_visibility_val" value="<?= htmlspecialchars($settings['nav_visibility'] ?? 'dashboard,service,all_user,partners,directory,payouts,give_task,chat,settings,view_option,access_control') ?>"/>
          
          <!-- Sub-sections positioning inputs -->
          <input type="hidden" name="sub_nav_pos_all_user" id="sub_nav_pos_all_user_val" value="<?= htmlspecialchars($settings['sub_nav_pos_all_user'] ?? 'users,staff,api_partners,agents,affiliates,shops,admin') ?>"/>
          <input type="hidden" name="sub_nav_vis_all_user" id="sub_nav_vis_all_user_val" value="<?= htmlspecialchars($settings['sub_nav_vis_all_user'] ?? 'users,staff,api_partners,agents,affiliates,shops,admin') ?>"/>
          
          <input type="hidden" name="sub_nav_pos_partners" id="sub_nav_pos_partners_val" value="<?= htmlspecialchars($settings['sub_nav_pos_partners'] ?? 'api_partners,agents,shops,deposits,withdrawals,disputes,settings') ?>"/>
          <input type="hidden" name="sub_nav_vis_partners" id="sub_nav_vis_partners_val" value="<?= htmlspecialchars($settings['sub_nav_vis_partners'] ?? 'api_partners,agents,shops,deposits,withdrawals,disputes,settings') ?>"/>
          
          <input type="hidden" name="sub_nav_pos_give_task" id="sub_nav_pos_give_task_val" value="<?= htmlspecialchars($settings['sub_nav_pos_give_task'] ?? 'staff,agents,affiliates') ?>"/>
          <input type="hidden" name="sub_nav_vis_give_task" id="sub_nav_vis_give_task_val" value="<?= htmlspecialchars($settings['sub_nav_vis_give_task'] ?? 'staff,agents,affiliates') ?>"/>
          
          <input type="hidden" name="sub_nav_pos_chat" id="sub_nav_pos_chat_val" value="<?= htmlspecialchars($settings['sub_nav_pos_chat'] ?? 'client,teammate') ?>"/>
          <input type="hidden" name="sub_nav_vis_chat" id="sub_nav_vis_chat_val" value="<?= htmlspecialchars($settings['sub_nav_vis_chat'] ?? 'client,teammate') ?>"/>
          
          <input type="hidden" name="sub_nav_pos_settings" id="sub_nav_pos_settings_val" value="<?= htmlspecialchars($settings['sub_nav_pos_settings'] ?? 'branding,profile,tnc,rename,all_sections') ?>"/>
          <input type="hidden" name="sub_nav_vis_settings" id="sub_nav_vis_settings_val" value="<?= htmlspecialchars($settings['sub_nav_vis_settings'] ?? 'branding,profile,tnc,rename,all_sections') ?>"/>
          
          <input type="hidden" name="sub_nav_pos_view_option" id="sub_nav_pos_view_option_val" value="<?= htmlspecialchars($settings['sub_nav_pos_view_option'] ?? 'user_site,user_dash,staff_dash,api_dash,agent_dash,affiliate_dash') ?>"/>
          <input type="hidden" name="sub_nav_vis_view_option" id="sub_nav_vis_view_option_val" value="<?= htmlspecialchars($settings['sub_nav_vis_view_option'] ?? 'user_site,user_dash,staff_dash,api_dash,agent_dash,affiliate_dash') ?>"/>
        </div>
        
        <button type="submit" class="btn"> Save Reorder Configuration</button>
      </form>
    </div>
  </div>

      <!-- SUB-SECTION 4: SET UP BKASH MERCHANT AND MOBILE APP APK -->
      <div class="sub-section-card collapsed" id="subsec-bkash-and-apk" style="border:1px solid rgba(255,255,255,0.06); border-radius:12px; background:rgba(0,0,0,0.15); overflow:hidden;">
        <div class="sub-section-head" onclick="toggleSubSection('subsec-bkash-and-apk')" style="padding:1rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.01);">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--gold); margin:0;"> SET UP BKASH MERCHANT AND MOBILE APP APK</h3>
          <span class="sub-arrow"></span>
        </div>
        <div class="sub-section-body" style="padding:1.2rem;  border-top:1px solid rgba(255,255,255,0.04); display:flex; flex-direction:column; gap:2rem;">
          <div>
            <h4 style="color:var(--gold); font-size:0.85rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-bottom:1rem; text-transform:uppercase;">1. BKASH MERCHANT API CONFIGURATION</h4>
            <div style="background:rgba(225, 29, 72, 0.08); border-left:4px solid #e11d48; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
        <h4 style="margin:0 0 0.5rem; color:#e11d48; font-weight:700;"> Will you get bKash API for free?</h4>
        <p style="margin:0; font-size:0.85rem; line-height:1.4; color:var(--text);">
          <strong>YES!</strong> As a bKash Merchant account holder (+8801963601472), you can obtain the <strong>bKash Payment Gateway (PGW) API for free</strong> (there are no upfront setup fees charged by bKash).
          To get credentials, register/login to the <a href="https://developer.bkash.com/" target="_blank" style="color:#e11d48; text-decoration:underline; font-weight:700;">bKash Developer Portal</a> or contact your bKash Account Manager.
          Once validated, you will receive the credential keys below to hook up seamless instant checkouts.
        </p>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_branding"/>
        <div class="grid2">
          <div class="field">
            <label>API Mode</label>
            <select name="bkash_api_mode" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);">
              <option value="sandbox" <?= ($settings['bkash_api_mode'] ?? '') === 'sandbox' ? 'selected' : '' ?>> Sandbox (Testing)</option>
              <option value="live" <?= ($settings['bkash_api_mode'] ?? '') === 'live' ? 'selected' : '' ?>> Live (Production)</option>
            </select>
          </div>
          <div class="field">
            <label>bKash Merchant Account Number</label>
            <input type="text" name="bkash_merchant_number" value="<?= htmlspecialchars($settings['bkash_merchant_number'] ?? '+8801963601472') ?>" style="background:var(--input-bg); border:1px solid var(--border); font-weight:700; color:var(--text); padding:0.65rem; border-radius:8px; width:100%;"/>
          </div>
          <div class="field">
            <label>App Key</label>
            <input type="password" name="bkash_app_key" value="<?= htmlspecialchars($settings['bkash_app_key'] ?? '') ?>" placeholder="Enter app key"/>
          </div>
          <div class="field">
            <label>App Secret</label>
            <input type="password" name="bkash_app_secret" value="<?= htmlspecialchars($settings['bkash_app_secret'] ?? '') ?>" placeholder="Enter app secret"/>
          </div>
          <div class="field">
            <label>API Username</label>
            <input type="text" name="bkash_username" value="<?= htmlspecialchars($settings['bkash_username'] ?? '') ?>" placeholder="Enter username"/>
          </div>
          <div class="field">
            <label>API Password</label>
            <input type="password" name="bkash_password" value="<?= htmlspecialchars($settings['bkash_password'] ?? '') ?>" placeholder="Enter password"/>
          </div>
          
          <!-- NEW: QR Code Upload Section -->
          <div class="field" style="grid-column: 1 / -1; margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 1rem;">
            <label style="color:var(--gold); font-size:0.9rem; font-weight:700;"> Upload bKash QR Code (For Manual Scanning)</label>
            <p style="font-size:0.75rem; color:var(--muted); margin-bottom:0.8rem;">Upload your Merchant QR code here so users can manually scan and pay from their bKash app if they don't want to use the automated API checkout.</p>
            <input type="file" name="bkash_qr_file" accept="image/*" style="width:100%; padding:0.65rem; border:1px dashed var(--brand); background:rgba(252,185,0,0.05); color:var(--text); border-radius:8px;"/>
            <?php if (!empty($settings['bkash_qr_url'])): ?>
              <div style="margin-top:0.8rem;">
                <span style="font-size:0.75rem; color:var(--muted); display:block; margin-bottom:0.4rem;">Current QR Code:</span>
                <img src="../<?= htmlspecialchars($settings['bkash_qr_url']) ?>" alt="bKash QR" style="max-width: 150px; border-radius: 8px; border: 2px solid var(--brand);"/>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <button type="submit" class="btn" style="margin-top:1rem;"> Save bKash Configuration</button>
      </form>
    </div>
          </div>
          <div>
            <h4 style="color:var(--gold); font-size:0.85rem; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem; margin-bottom:1rem; text-transform:uppercase;">2. MOBILE APP APK AUTO UPGRADE SETTING</h4>
            <form method="POST">
        <input type="hidden" name="action" value="save_mobile_app"/>
        <p style="font-size:0.8rem; color:var(--muted); margin-bottom:1.2rem;">Configure the active Android APK versions. When the values here are higher than the local code in the user or admin APK, users will be prompted to download the update from the specified URL:</p>
        
        <strong style="color:var(--gold); font-size:0.82rem; display:block; margin-bottom:0.8rem; text-transform:uppercase;">User App Configuration</strong>
        <div class="grid2" style="margin-bottom:1.5rem;">
          <div class="field">
            <label>User App Version Code (Integer, e.g. 2)</label>
            <input type="number" name="app_client_version_code" value="<?= htmlspecialchars($settings['app_client_version_code'] ?? '2') ?>" required/>
          </div>
          <div class="field">
            <label>User App Version Name (String, e.g. 1.1)</label>
            <input type="text" name="app_client_version_name" value="<?= htmlspecialchars($settings['app_client_version_name'] ?? '1.1') ?>" required/>
          </div>
          <div class="field" style="grid-column:span 2;">
            <label>User App APK Download URL</label>
            <input type="url" name="app_client_apk_url" value="<?= htmlspecialchars($settings['app_client_apk_url'] ?? 'https://fastsite.best-travel.ltd/fastsite.apk') ?>" required/>
          </div>
        </div>

        <strong style="color:var(--gold); font-size:0.82rem; display:block; margin-bottom:0.8rem; text-transform:uppercase;">Admin App Configuration</strong>
        <div class="grid2">
          <div class="field">
            <label>Admin App Version Code (Integer, e.g. 2)</label>
            <input type="number" name="app_admin_version_code" value="<?= htmlspecialchars($settings['app_admin_version_code'] ?? '2') ?>" required/>
          </div>
          <div class="field">
            <label>Admin App Version Name (String, e.g. 1.1)</label>
            <input type="text" name="app_admin_version_name" value="<?= htmlspecialchars($settings['app_admin_version_name'] ?? '1.1') ?>" required/>
          </div>
          <div class="field" style="grid-column:span 2;">
            <label>Admin App APK Download URL</label>
            <input type="url" name="app_admin_apk_url" value="<?= htmlspecialchars($settings['app_admin_apk_url'] ?? 'https://fastsite.best-travel.ltd/fastsite_admin.apk') ?>" required/>
          </div>
        </div>

        <button type="submit" class="btn" style="margin-top:1.5rem;"> Save App Upgrade Settings</button>
      </form>
    </div>
          </div>

      <!-- SUB-SECTION 5: ALL HEADER, SECTION AND SUB-SECTION DIRECTORY -->
      <div class="sub-section-card collapsed" id="subsec-directory" style="border:1px solid rgba(255,255,255,0.06); border-radius:12px; background:rgba(0,0,0,0.15); overflow:hidden;">
        <div class="sub-section-head" onclick="toggleSubSection('subsec-directory')" style="padding:1rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.01);">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--gold); margin:0;"> ALL HEADER, SECTION AND SUB_SECTION DIRECTORY</h3>
          <span class="sub-arrow"></span>
        </div>
        <div class="sub-section-body" style="padding:1.2rem;  border-top:1px solid rgba(255,255,255,0.04);">
          <link rel="stylesheet" href="/assets/css/admin.css">

          <script>
            function openSectionAndSub(secId, subId) {
              // 1. Expand parent section if collapsed
              const secEl = document.getElementById(secId);
              if (secEl) {
                secEl.classList.remove('collapsed');
              }
              // 2. Expand sub-section if collapsed
              if (subId) {
                const subEl = document.getElementById(subId);
                if (subEl) {
                  subEl.classList.remove('collapsed');
                  const body = subEl.querySelector('.sub-section-body');
                  const arrow = subEl.querySelector('.sub-arrow');
                  if (body) body.style.display = 'block';
                  if (arrow) arrow.innerHTML = '';
                }
              }
              // 3. Scroll to the element smoothly
              const target = document.getElementById(subId || secId);
              if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
              }
            }
          </script>

          <div class="dir-grid">
            
            <!-- Card 1: USER STOREFRONT -->
            <div class="directory-card" style="background:rgba(255,255,255,0.02); border:1px solid rgba(252,185,0,0.15); border-radius:12px; padding:1.2rem; display:flex; flex-direction:column; gap:0.8rem; box-shadow:0 4px 20px rgba(0,0,0,0.25);">
              <strong style="color:var(--gold); font-size:0.95rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid rgba(252,185,0,0.2); padding-bottom:0.5rem;">
                 'HEADER SECTION' - USER STOREFRONT
              </strong>
              <div style="display:flex; flex-direction:column; gap:0.6rem;">
                <!-- item 1 -->
                <div style="display:flex; flex-direction:column; gap:0.2rem; padding:0.4rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;"> GOVERNMENT SERVICE H.SECTION</span>
                  <div style="display:flex; gap:0.5rem; margin-top:0.2rem;">
                    <a href="../index.php#gov-services-slideplay" target="_blank" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> View Storefront</a>
                    <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-homepage-customise')" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid rgba(255,255,255,0.1); color:var(--text); text-decoration:none; display:inline-block; transition:all 0.2s;"> Customize</a>
                  </div>
                </div>
                <!-- item 2 -->
                <div style="display:flex; flex-direction:column; gap:0.2rem; padding:0.4rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;"> TRENDING H.SECTION</span>
                  <div style="display:flex; gap:0.5rem; margin-top:0.2rem;">
                    <a href="../index.php#trending" target="_blank" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> View Storefront</a>
                    <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-homepage-customise')" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid rgba(255,255,255,0.1); color:var(--text); text-decoration:none; display:inline-block; transition:all 0.2s;"> Customize</a>
                  </div>
                </div>
                <!-- item 3 -->
                <div style="display:flex; flex-direction:column; gap:0.2rem; padding:0.4rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;"> OUR SERVICE H.SECTION</span>
                  <div style="display:flex; gap:0.5rem; margin-top:0.2rem;">
                    <a href="../index.php#dynamic-services" target="_blank" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> View Storefront</a>
                    <a href="manage_directory.php" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid rgba(255,255,255,0.1); color:var(--text); text-decoration:none; display:inline-block; transition:all 0.2s;"> Manage Services</a>
                  </div>
                </div>
                <!-- item 4 -->
                <div style="display:flex; flex-direction:column; gap:0.2rem; padding:0.4rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;"> BECOME PARTNER'S H.SECTION</span>
                  <div style="display:flex; gap:0.5rem; margin-top:0.2rem;">
                    <a href="../index.php#partners" target="_blank" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> View Storefront</a>
                    <a href="javascript:void(0);" onclick="openSectionAndSub('sec-all-partners-program')" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid rgba(255,255,255,0.1); color:var(--text); text-decoration:none; display:inline-block; transition:all 0.2s;"> Partner Program</a>
                  </div>
                </div>
                <!-- item 5 -->
                <div style="display:flex; flex-direction:column; gap:0.2rem; padding:0.4rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;"> TRACK YOUR ORDER H.SECTION</span>
                  <div style="display:flex; gap:0.5rem; margin-top:0.2rem;">
                    <a href="../index.php#track" target="_blank" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> View Storefront</a>
                  </div>
                </div>
                <!-- item 6 -->
                <div style="display:flex; flex-direction:column; gap:0.2rem; padding:0.4rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;"> LATEST GUIDE NEWS H.SECTION</span>
                  <div style="display:flex; gap:0.5rem; margin-top:0.2rem;">
                    <a href="../index.php#blogs" target="_blank" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> View Storefront</a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 2: ADMIN PANEL HEADERS -->
            <div class="directory-card" style="background:rgba(255,255,255,0.02); border:1px solid rgba(252,185,0,0.15); border-radius:12px; padding:1.2rem; display:flex; flex-direction:column; gap:0.8rem; box-shadow:0 4px 20px rgba(0,0,0,0.25);">
              <strong style="color:var(--gold); font-size:0.95rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid rgba(252,185,0,0.2); padding-bottom:0.5rem;">
                 'HEADER SECTION - ADMIN PANEL'
              </strong>
              <div style="display:flex; flex-direction:column; gap:0.6rem;">
                <!-- 1. HOMEPAGE -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">1)  HOMEPAGE</span>
                  <a href="dashboard.php" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Dashboard</a>
                </div>
                <!-- 2. SERVICE -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">2)  SERVICE H.SECTION</span>
                  <a href="manage_directory.php" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Manage</a>
                </div>
                <!-- 3. ALL USER -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">3)  ALL USER H.SECTION</span>
                  <a href="users.php" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Users</a>
                </div>
                <!-- 4. PARTNERS -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">4)  PARTNERS H.SECTION</span>
                  <a href="partner_shops.php" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Partners</a>
                </div>
                <!-- 5. PAYOUT -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">5)  PAYOUT H.SECTION</span>
                  <a href="payouts.php" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Payouts</a>
                </div>
                <!-- 6. SETTING -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">6)  SETTING H.SECTION</span>
                  <a href="settings.php" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Settings</a>
                </div>
                <!-- 7. STORE VIEW OPTION -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">7)  STORE VIEW OPTION H.SECTION</span>
                  <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-view-option')" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Options</a>
                </div>
              </div>
            </div>

            <!-- Card 3: SECTION'S - ADMIN PANEL -->
            <div class="directory-card" style="background:rgba(255,255,255,0.02); border:1px solid rgba(252,185,0,0.15); border-radius:12px; padding:1.2rem; display:flex; flex-direction:column; gap:0.8rem; box-shadow:0 4px 20px rgba(0,0,0,0.25);">
              <strong style="color:var(--gold); font-size:0.95rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid rgba(252,185,0,0.2); padding-bottom:0.5rem;">
                 SECTION'S - ADMIN PANEL
              </strong>
              <div style="display:flex; flex-direction:column; gap:0.6rem;">
                <!-- 1. GENERAL SETTING -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">1)  GENERAL SETTING SECTION</span>
                  <a href="javascript:void(0);" onclick="openSectionAndSub('sec-general')" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Open</a>
                </div>
                <!-- 2. LOGO, BANNER AND MEDIA -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">2)  LOGO, BANNER & MEDIA SECTION</span>
                  <a href="javascript:void(0);" onclick="openSectionAndSub('sec-logo-banner-media')" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Open</a>
                </div>
                <!-- 3. STUFF ACCESS CONTROL -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.4rem 0.6rem; background:rgba(255,255,255,0.01); border-radius:6px; border:1px solid rgba(255,255,255,0.03);">
                  <span style="font-size:0.82rem; font-weight:700; color:#fff;">3)  STUFF ACCESS CONTROL SECTION</span>
                  <a href="javascript:void(0);" onclick="openSectionAndSub('sec-staff')" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Open</a>
                </div>
                <!-- 4. ADVANCE SETTING -->
                <div style="display:flex; flex-direction:column; gap:0.4rem; padding:0.5rem; background:rgba(255,255,255,0.01); border-radius:8px; border:1px solid rgba(255,255,255,0.03);">
                  <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:0.3rem; margin-bottom:0.3rem;">
                    <span style="font-size:0.82rem; font-weight:700; color:#fff;">4)  ADVANCE SETTING SECTION</span>
                    <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings')" class="dir-btn" style="font-size:0.68rem; padding:2px 6px; border-radius:4px; border:1px solid var(--border); color:var(--gold); text-decoration:none; display:inline-block; transition:all 0.2s;"> Open</a>
                  </div>
                  
                  <!-- SUB_SECTION'S -->
                  <div style="display:flex; flex-direction:column; gap:0.4rem; padding-left:0.6rem; border-left:1px dashed rgba(252,185,0,0.3);">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                      <span style="font-size:0.76rem; color:var(--text);">- VIEW OPTION</span>
                      <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-view-option')" style="font-size:0.7rem; color:var(--gold); text-decoration:none;">Go</a>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                      <span style="font-size:0.76rem; color:var(--text);">- HOMEPAGE CUSTOMISE</span>
                      <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-homepage-customise')" style="font-size:0.7rem; color:var(--gold); text-decoration:none;">Go</a>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                      <span style="font-size:0.76rem; color:var(--text);">- NAVIGATION CUSTOMISE</span>
                      <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-navigation-customise')" style="font-size:0.7rem; color:var(--gold); text-decoration:none;">Go</a>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                      <span style="font-size:0.76rem; color:var(--text);">- SET UP BKASH & MOBILE APK</span>
                      <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-bkash-and-apk')" style="font-size:0.7rem; color:var(--gold); text-decoration:none;">Go</a>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                      <span style="font-size:0.76rem; color:var(--text);">- ALL HEADER, SEC & SUB DIRECTORY</span>
                      <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-directory')" style="font-size:0.7rem; color:var(--gold); text-decoration:none;">Go</a>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                      <span style="font-size:0.76rem; color:var(--text);">- TERMS AND CONDITIONS</span>
                      <a href="javascript:void(0);" onclick="openSectionAndSub('sec-advance-settings', 'subsec-tnc')" style="font-size:0.7rem; color:var(--gold); text-decoration:none;">Go</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- SUB-SECTION 6: TERMS AND CONDITIONS -->
      <div class="sub-section-card collapsed" id="subsec-tnc" style="border:1px solid rgba(255,255,255,0.06); border-radius:12px; background:rgba(0,0,0,0.15); overflow:hidden;">
        <div class="sub-section-head" onclick="toggleSubSection('subsec-tnc')" style="padding:1rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.01);">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--gold); margin:0;"> TERMS AND CONDITIONS</h3>
          <span class="sub-arrow"></span>
        </div>
        <div class="sub-section-body" style="padding:1.2rem;  border-top:1px solid rgba(255,255,255,0.04);">
          <form method="POST">
        <input type="hidden" name="action" value="save_tnc"/>
        <div class="field">
          <label>Standard User Terms</label>
          <textarea name="user_tnc" rows="5" placeholder="Write user agreement terms..."><?= htmlspecialchars($settings['user_tnc'] ?? '') ?></textarea>
        </div>
        <div class="field">
          <label>Affiliate Agent Terms</label>
          <textarea name="agent_tnc" rows="5" placeholder="Write agent partner agreement terms..."><?= htmlspecialchars($settings['agent_tnc'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn"> Save Terms</button>
      </form>
    </div>
  </div>

    </div>
  </div>

<script>

function toggleSubSection(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.toggle('collapsed');
    const body = el.querySelector('.sub-section-body');
    const arrow = el.querySelector('.sub-arrow');
    if (body) {
      if (el.classList.contains('collapsed')) {
        body.style.gridTemplateRows = '0fr';
        if (arrow) arrow.innerHTML = '';
      } else {
        body.style.gridTemplateRows = '1fr';
        if (arrow) arrow.innerHTML = '';
      }
    }
  }
}
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.sub-section-card .sub-section-body').forEach(body => {
        body.style.display = 'grid';
        body.style.transition = 'grid-template-rows 0.3s ease';
        if (body.closest('.collapsed')) {
            body.style.gridTemplateRows = '0fr';
        } else {
            body.style.gridTemplateRows = '1fr';
        }
        
        // Wrap children so grid animation works
        if(body.children.length > 0 && !body.firstElementChild.classList.contains('anim-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'anim-wrapper';
            wrapper.style.overflow = 'hidden';
            while(body.firstChild) {
                wrapper.appendChild(body.firstChild);
            }
            body.appendChild(wrapper);
        }
    });
});

const homepageSectionLabels = {
  'hero': ' Hero Banner',
  'overview': ' Welcome/Overview Info',
  'search': ' Services Search Bar',
  'promo': ' Promo Banner Card',
  'trending': ' Trending Services Slider',
  'gov_slider': ' Government Services Slider',
  'travel_slider': ' Travel Services Slider',
  'our_services': ' Dynamic Our Services',
  'features': ' Features / Why Fast Site',
  'partners': ' Become Partners Program',
  'track': ' Track Your Order Form',
  'blogs': ' Tutorial Guides & News'
};

function runHomepageReorder() {
  const orderInput = document.getElementById('homepage_sections_order_val');
  const visInput = document.getElementById('homepage_sections_visibility_val');
  const container = document.getElementById('homepage-reorder-container');
  if (!orderInput || !visInput || !container) return;
  
  let order = orderInput.value.split(',').map(s => s.trim()).filter(Boolean);
  let visible = visInput.value.split(',').map(s => s.trim()).filter(Boolean);
  
  // Align missing keys
  const allKeys = Object.keys(homepageSectionLabels);
  allKeys.forEach(k => {
    if (!order.includes(k)) order.push(k);
  });
  
  function renderList() {
    container.innerHTML = '';
    order.forEach((key, index) => {
      const isVisible = visible.includes(key);
      const label = homepageSectionLabels[key] || key;
      
      const row = document.createElement('div');
      row.style.cssText = 'display:flex; align-items:center; justify-content:space-between; padding:0.6rem 0.8rem; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:8px; gap:1rem;';
      
      const left = document.createElement('div');
      left.style.cssText = 'display:flex; align-items:center; gap:0.5rem;';
      
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.checked = isVisible;
      checkbox.style.cssText = 'width:auto; min-height:auto; cursor:pointer;';
      checkbox.onchange = () => {
        if (checkbox.checked) {
          if (!visible.includes(key)) visible.push(key);
        } else {
          visible = visible.filter(k => k !== key);
        }
        updateInputs();
        renderList();
      };
      
      const textSpan = document.createElement('span');
      textSpan.innerHTML = label;
      textSpan.style.cssText = 'font-size:0.85rem; font-weight:600; color:' + (isVisible ? '#fff' : 'var(--muted)');
      
      left.appendChild(checkbox);
      left.appendChild(textSpan);
      
      const right = document.createElement('div');
      right.style.cssText = 'display:flex; gap:0.3rem;';
      
      const upBtn = document.createElement('button');
      upBtn.type = 'button';
      upBtn.innerHTML = '';
      upBtn.className = 'nav-reorder-btn';
      upBtn.disabled = index === 0;
      upBtn.onclick = () => {
        if (index > 0) {
          const temp = order[index];
          order[index] = order[index - 1];
          order[index - 1] = temp;
          updateInputs();
          renderList();
        }
      };
      
      const downBtn = document.createElement('button');
      downBtn.type = 'button';
      downBtn.innerHTML = '';
      downBtn.className = 'nav-reorder-btn';
      downBtn.disabled = index === order.length - 1;
      downBtn.onclick = () => {
        if (index < order.length - 1) {
          const temp = order[index];
          order[index] = order[index + 1];
          order[index + 1] = temp;
          updateInputs();
          renderList();
        }
      };
      
      right.appendChild(upBtn);
      right.appendChild(downBtn);
      row.appendChild(left);
      row.appendChild(right);
      container.appendChild(row);
    });
  }
  
  function updateInputs() {
    orderInput.value = order.join(',');
    const orderedVis = order.filter(k => visible.includes(k));
    visInput.value = orderedVis.join(',');
  }
  
  renderList();
}

window.addEventListener('DOMContentLoaded', () => {
  runHomepageReorder();
  
  // World Cup Banner settings toggle logic
  const bannerTypeSelect = document.querySelector('select[name="banner_type"]');
  const wcSettingsContainer = document.getElementById('wc-settings-container');
  if (bannerTypeSelect && wcSettingsContainer) {
      const toggleWcSettings = () => {
          wcSettingsContainer.style.display = (bannerTypeSelect.value === 'worldcup') ? 'block' : 'none';
      };
      bannerTypeSelect.addEventListener('change', toggleWcSettings);
      toggleWcSettings();
  }
});


function toggleSection(id) {
  document.getElementById(id).classList.toggle('collapsed');
}

// Visual navigation reordering system (including dropdown items/sub-sections)
const subNavLabelsMap = {
  'main': {
    'dashboard': ' DASHBOARD',
    'service': ' SERVICES',
    'all_user': ' ALL USERS',
    'partners': ' PARTNERS',
    'directory': ' TRUST DIRECTORY',
    'payouts': ' PAYOUTS',
    'give_task': ' GIVE TASK',
    'chat': ' CHATS',
    'settings': ' SYSTEM SETTING',
    'view_option': ' VIEW OPTIONS',
    'access_control': ' STUFF ACCESS CONTROL'
  },
  'all_user': {
    'users': ' USER',
    'staff': ' STUFF',
    'api_partners': ' API PARTNERS',
    'agents': ' AGENT PARTNERS',
    'affiliates': ' AFFILIATE PARTNERS',
    'shops': ' PARTNER SHOP\'S',
    'admin': ' ADMIN (MYSELF)'
  },
  'partners': {
    'api_partners': ' API PARTNERS',
    'agents': ' AGENT PARTNERS',
    'shops': ' PARTNER SHOPS',
    'deposits': ' COIN DEPOSITS',
    'withdrawals': ' PARTNER PAYOUTS',
    'disputes': ' DISPUTES ADJUDICATION',
    'settings': ' PARTNER SETTINGS'
  },
  'give_task': {
    'staff': ' GIVE TASK TO STAFF',
    'agents': ' GIVE TASK TO AGENTS',
    'affiliates': ' GIVE TASK TO AFFILIATE PARTNERS'
  },
  'chat': {
    'client': ' CLIENT SUPPORT CHAT',
    'teammate': ' INTER-TEAMMATE CHAT'
  },
  'settings': {
    'branding': ' BRANDING & GENERAL',
    'profile': ' PERSONAL PROFILE',
    'tnc': ' TERMS & CONDITIONS',
    'rename': ' Rename Navigation',
    'all_sections': ' ALL SECTION'
  },
  'view_option': {
    'user_site': ' USER VIEW SITE',
    'user_dash': ' USER DASHBOARD VIEW',
    'staff_dash': ' STAFF DASHBOARD VIEW',
    'api_dash': ' API PARTNERS DASHBOARD',
    'agent_dash': ' AGENT PARTNERS DASHBOARD',
    'affiliate_dash': ' AFFILIATE PARTNERS DASHBOARD'
  }
};

let currentReorderSection = 'main';

function switchReorderSection() {
  const selector = document.getElementById('reorder-section-selector');
  if (selector) {
    currentReorderSection = selector.value;
    runReorderList();
  }
}

function runReorderList() {
  let inputEl, inputVisEl;
  if (currentReorderSection === 'main') {
    inputEl = document.getElementById('nav_positioning_val');
    inputVisEl = document.getElementById('nav_visibility_val');
  } else {
    inputEl = document.getElementById('sub_nav_pos_' + currentReorderSection + '_val');
    inputVisEl = document.getElementById('sub_nav_vis_' + currentReorderSection + '_val');
  }
  
  const listEl = document.getElementById('nav-reorder-list');
  if (!inputEl || !listEl) return;
  
  const labelsMap = subNavLabelsMap[currentReorderSection];
  const defaultOrder = Object.keys(labelsMap).join(',');
  
  let currentVal = inputEl.value.trim();
  if (!currentVal) {
    currentVal = defaultOrder;
  }
  
  let keys = currentVal.split(',').map(k => k.trim()).filter(k => labelsMap[k]);
  
  // Ensure all known keys are included
  Object.keys(labelsMap).forEach(k => {
    if (!keys.includes(k)) {
      keys.push(k);
    }
  });

  let activeKeys = [];
  if (inputVisEl) {
    let visVal = inputVisEl.value.trim();
    if (!visVal) {
      visVal = defaultOrder;
    }
    activeKeys = visVal.split(',').map(k => k.trim());
  } else {
    activeKeys = keys.slice();
  }

  function renderList() {
    listEl.innerHTML = '';
    keys.forEach((key, index) => {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.alignItems = 'center';
      row.style.justifyContent = 'space-between';
      row.style.background = 'rgba(255, 255, 255, 0.02)';
      row.style.border = '1px solid rgba(255, 255, 255, 0.05)';
      row.style.borderRadius = '10px';
      row.style.padding = '0.6rem 0.8rem';
      
      // Left: Toggle + Title
      const leftDiv = document.createElement('div');
      leftDiv.style.display = 'flex';
      leftDiv.style.alignItems = 'center';
      leftDiv.style.gap = '0.8rem';

      const switchLabel = document.createElement('label');
      switchLabel.className = 'switch';
      
      const switchInput = document.createElement('input');
      switchInput.type = 'checkbox';
      switchInput.checked = activeKeys.includes(key);
      
      const switchSlider = document.createElement('span');
      switchSlider.className = 'slider';
      
      switchLabel.appendChild(switchInput);
      switchLabel.appendChild(switchSlider);

      const labelSpan = document.createElement('span');
      labelSpan.style.fontSize = '0.8rem';
      labelSpan.style.fontWeight = '600';
      labelSpan.style.transition = 'color 0.2s';
      labelSpan.style.color = switchInput.checked ? '#fff' : '#666688';
      labelSpan.textContent = labelsMap[key] || key;
      
      switchInput.onchange = () => {
        if (switchInput.checked) {
          if (!activeKeys.includes(key)) activeKeys.push(key);
        } else {
          activeKeys = activeKeys.filter(k => k !== key);
        }
        labelSpan.style.color = switchInput.checked ? '#fff' : '#666688';
        updateInput();
      };

      leftDiv.appendChild(switchLabel);
      leftDiv.appendChild(labelSpan);
      
      // Right: Reorder Up/Down buttons
      const btnsDiv = document.createElement('div');
      btnsDiv.style.display = 'flex';
      btnsDiv.style.gap = '0.3rem';
      
      // Move Up Button
      const upBtn = document.createElement('button');
      upBtn.type = 'button';
      upBtn.innerHTML = '';
      upBtn.className = 'nav-reorder-btn';
      upBtn.disabled = index === 0;
      upBtn.onclick = () => {
        if (index > 0) {
          const temp = keys[index];
          keys[index] = keys[index - 1];
          keys[index - 1] = temp;
          updateInput();
          renderList();
        }
      };
      
      // Move Down Button
      const downBtn = document.createElement('button');
      downBtn.type = 'button';
      downBtn.innerHTML = '';
      downBtn.className = 'nav-reorder-btn';
      downBtn.disabled = index === keys.length - 1;
      downBtn.onclick = () => {
        if (index < keys.length - 1) {
          const temp = keys[index];
          keys[index] = keys[index + 1];
          keys[index + 1] = temp;
          updateInput();
          renderList();
        }
      };
      
      btnsDiv.appendChild(upBtn);
      btnsDiv.appendChild(downBtn);
      row.appendChild(leftDiv);
      row.appendChild(btnsDiv);
      listEl.appendChild(row);
    });
  }
  
  function updateInput() {
    inputEl.value = keys.join(',');
    if (inputVisEl) {
      const orderedVis = keys.filter(k => activeKeys.includes(k));
      inputVisEl.value = orderedVis.join(',');
    }
  }
  
  renderList();
}

function initNavReorder() {
  runReorderList();
}

// Auto expand target section based on URL hash
function expandTargetHash() {
  const hash = window.location.hash;
  if (hash) {
    const el = document.querySelector(hash);
    if (el && el.classList.contains('collapsed')) {
      el.classList.remove('collapsed');
      el.scrollIntoView({ behavior: 'smooth' });
    }
  }
}

window.addEventListener('DOMContentLoaded', () => {
  expandTargetHash();
  window.addEventListener('hashchange', expandTargetHash);
  initNavReorder();
  
  // Local file preview handler for hero banner uploader
  const bannerFilesInput = document.getElementById('banner-files-input');
  if (bannerFilesInput) {
    bannerFilesInput.addEventListener('change', function(e) {
      const container = document.getElementById('new-banners-preview-container');
      const title = document.getElementById('new-banners-preview-title');
      if (!container || !title) return;
      container.innerHTML = '';
      
      if (this.files && this.files.length > 0) {
        title.style.display = 'block';
        Array.from(this.files).forEach((file) => {
          const card = document.createElement('div');
          card.style.cssText = 'position:relative; width:150px; background:rgba(0,0,0,0.4); border:1px dashed var(--gold); border-radius:8px; padding:6px; display:flex; flex-direction:column; align-items:center;';
          
          const reader = new FileReader();
          reader.onload = function(event) {
            if (file.type.startsWith('video/')) {
              card.innerHTML = `<video src="${event.target.result}" style="width:100%; height:80px; object-fit:contain; border-radius:4px;" muted></video>
                                <span style="font-size:0.6rem; color:var(--gold); margin-top:4px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; width:100%; text-align:center;">${escapeHtml(file.name)}</span>`;
            } else {
              card.innerHTML = `<img src="${event.target.result}" style="width:100%; height:80px; object-fit:contain; border-radius:4px;"/>
                                <span style="font-size:0.6rem; color:var(--gold); margin-top:4px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; width:100%; text-align:center;">${escapeHtml(file.name)}</span>`;
            }
          };
          reader.readAsDataURL(file);
          container.appendChild(card);
        });
      } else {
        title.style.display = 'none';
      }
    });
  }
});

function escapeHtml(text) {
  if (!text) return '';
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
