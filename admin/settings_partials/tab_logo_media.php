<?php /* Fast Site Settings Partial — sec-logo-banner-media (Logo, Banners & Media) */ ?>
  <div class="section-card collapsed" id="sec-logo-banner-media">
    <div class="section-head" onclick="toggleSection('sec-logo-banner-media')">
      <h2> 2) LOGO, BANNERS AND MEDIA</h2>
      <span class="arrow"></span>
    </div>
    <div class="section-body" style="display:flex; flex-direction:column; gap:2rem;">
      <div style="border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:1.5rem;">
        <h3 style="color:var(--gold); font-size:1.05rem; font-weight:800; margin-bottom:1rem;"> HERO BANNER & MEDIA MANAGER</h3>
        <div style="margin-bottom: 1.5rem;">
        <h4 style="font-size:0.85rem; color:var(--gold); margin-bottom:0.8rem; text-transform:uppercase;">Active Banner Media (Click '' to remove)</h4>
        <div id="active-banners-container" style="display:flex; flex-wrap:wrap; gap:1rem; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:1rem;">
          <?php 
          $bannerType = $settings['banner_type'] ?? 'photo';
          
          $bannerMediaPaths = [];
          if (!empty($settings['banner_media_paths'])) {
              $bannerMediaPaths = json_decode($settings['banner_media_paths'], true) ?: [];
          }
          if (empty($bannerMediaPaths) && !empty($settings['banner_media_path'])) {
              $bannerMediaPaths[] = $settings['banner_media_path'];
          }
          
          if ($bannerType === 'iframe'):
          ?>
            <div style="display:flex; align-items:center; gap:1rem;">
              <div style="display:flex; align-items:center; justify-content:center; font-size:1.5rem; background:#222; color:#fff; height:60px; width:60px; border-radius:8px;"></div>
              <div>
                <div style="font-weight:700; font-size:0.85rem;">Active Custom HTML / Ad Code</div>
                <div style="font-size:0.7rem; color:var(--muted);">Rendering custom HTML wrapper</div>
              </div>
            </div>
          <?php 
          elseif (!empty($bannerMediaPaths)):
              foreach ($bannerMediaPaths as $idx => $path):
                  $isVid = preg_match('/\.(mp4|webm|ogg|mov)$/i', $path);
          ?>
            <div class="banner-preview-card" style="position:relative; width:150px; background:rgba(0,0,0,0.4); border:1px solid rgba(252,185,0,0.2); border-radius:8px; padding:6px; display:flex; flex-direction:column; align-items:center;">
              <input type="hidden" name="existing_banners[]" value="<?= htmlspecialchars($path) ?>"/>
              <?php if ($isVid): ?>
                <video src="../<?= htmlspecialchars($path) ?>" style="width:100%; height:80px; object-fit:contain; border-radius:4px;" muted></video>
              <?php else: ?>
                <img src="/<?= htmlspecialchars(ltrim($path, '/')) ?>" style="width:100%; height:80px; object-fit:contain; border-radius:4px;"/>
              <?php endif; ?>
              <button type="button" onclick="this.parentNode.remove()" style="position:absolute; top:-8px; right:-8px; background:var(--red); color:#fff; border:none; border-radius:50%; width:22px; height:22px; font-size:11px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-weight:800; box-shadow:0 2px 8px rgba(0,0,0,0.5);"></button>
              <span style="font-size:0.6rem; color:var(--muted); margin-top:4px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; width:100%; text-align:center;"><?= htmlspecialchars(basename($path)) ?></span>
            </div>
          <?php 
              endforeach;
          else:
          ?>
            <div style="display:flex; align-items:center; gap:1rem;">
              <div style="display:flex; align-items:center; justify-content:center; font-size:1.5rem; height:60px; width:60px; border-radius:8px; background:rgba(255,255,255,0.05);"></div>
              <div><div style="font-weight:700; font-size:0.85rem;">No banner media loaded (default fallback).</div></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_branding"/>
        <div class="grid2">
          <div class="field">
            <label>Banner Display Type</label>
            <select name="banner_type" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);">
              <option value="photo" <?= ($settings['banner_type'] ?? '') === 'photo' ? 'selected' : '' ?>> Single/Multiple High-Res Image(s)</option>
              <option value="video" <?= ($settings['banner_type'] ?? '') === 'video' ? 'selected' : '' ?>> Auto-Play Video Banner</option>
              <option value="iframe" <?= ($settings['banner_type'] ?? '') === 'iframe' ? 'selected' : '' ?>> Custom HTML / RSS / Ad Script (No pixel loss)</option>
              <option value="worldcup" <?= ($settings['banner_type'] ?? '') === 'worldcup' ? 'selected' : '' ?>> FIFA World Cup 2026 Scoreboard (Dynamic)</option>
            </select>
          </div>
          <div class="field">
            <label>Upload Media File(s) (Image/Video - Support Multiple)</label>
            <input type="file" id="banner-files-input" name="banner_files[]" accept=".jpg,.jpeg,.png,.webp,video/mp4,video/webm" multiple/>
            <span style="font-size:0.72rem; color:red; display:block; margin-top:2px;">recommended banner: 1200x450 px or high-res 1920x1080 px | video: 1280x720 px (mp4, max 10mb)</span>
          </div>
          <div class="field" style="grid-column:span 2;">
            <label>Banner Redirect URL (When clicked)</label>
            <input type="url" name="banner_redirect_url" value="<?= htmlspecialchars($settings['banner_redirect_url'] ?? '') ?>" placeholder="https://yourlink.com"/>
          </div>
          <div class="field" style="grid-column:span 2;">
            <label>Custom HTML / Iframe / RSS Feed Code (Used when Display Type is Custom HTML)</label>
            <textarea name="banner_custom_html" rows="4" placeholder="e.g. &lt;iframe src='...' width='100%' height='250'&gt;&lt;/iframe&gt;" style="width:100%; font-family:monospace; background:var(--input-bg); color:var(--text); border-radius:8px; border:1px solid var(--border); padding:0.5rem;"><?= htmlspecialchars($settings['banner_custom_html'] ?? '') ?></textarea>
          </div>
        </div>
        
        <!--  FIFA WORLD CUP SCOREBOARD SETTINGS -->
        <div class="field" id="wc-settings-container" style="display: none; margin-top: 1.5rem; border: 1.5px dashed rgba(252, 185, 0, 0.35); padding: 1.5rem; border-radius: 12px; background: rgba(0,0,0,0.25); box-shadow: 0 4px 16px rgba(0,0,0,0.5);">
          <h3 style="color: var(--gold); margin-bottom: 1rem; font-size: 1.05rem; font-weight: 800; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; text-transform: uppercase;"> FIFA World Cup Scoreboard Manager</h3>
          <div class="grid2">
            <div class="field">
              <label>Match Status</label>
              <select name="wc_match_status" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);">
                <option value="none" <?= ($settings['wc_match_status'] ?? '') === 'none' ? 'selected' : '' ?>>None (Hide Scoreboard)</option>
                <option value="upcoming" <?= ($settings['wc_match_status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming Match (Countdown)</option>
                <option value="live" <?= ($settings['wc_match_status'] ?? '') === 'live' ? 'selected' : '' ?>>Live Match (Real-time Score)</option>
              </select>
            </div>
            <div class="field">
              <label>Kickoff Time (for Countdown / Schedule)</label>
              <input type="datetime-local" name="wc_kickoff_time" value="<?= htmlspecialchars($settings['wc_kickoff_time'] ?? '') ?>" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
          </div>
          <div class="grid2" style="margin-top: 1rem;">
            <div class="field">
              <label>Home Team Name</label>
              <input type="text" name="wc_home_team" value="<?= htmlspecialchars($settings['wc_home_team'] ?? 'TBD') ?>" placeholder="e.g. Argentina" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
            <div class="field">
              <label>Home Team Flag URL</label>
              <input type="url" name="wc_home_flag" value="<?= htmlspecialchars($settings['wc_home_flag'] ?? '') ?>" placeholder="e.g. https://flagcdn.com/w80/ar.png" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
          </div>
          <div class="grid2" style="margin-top: 1rem;">
            <div class="field">
              <label>Away Team Name</label>
              <input type="text" name="wc_away_team" value="<?= htmlspecialchars($settings['wc_away_team'] ?? 'TBD') ?>" placeholder="e.g. France" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
            <div class="field">
              <label>Away Team Flag URL</label>
              <input type="url" name="wc_away_flag" value="<?= htmlspecialchars($settings['wc_away_flag'] ?? '') ?>" placeholder="e.g. https://flagcdn.com/w80/fr.png" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
          </div>
          <div class="grid2" style="margin-top: 1rem;">
            <div class="field">
              <label>Home Score (Live)</label>
              <input type="number" name="wc_home_score" value="<?= htmlspecialchars($settings['wc_home_score'] ?? '0') ?>" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
            <div class="field">
              <label>Away Score (Live)</label>
              <input type="number" name="wc_away_score" value="<?= htmlspecialchars($settings['wc_away_score'] ?? '0') ?>" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
          </div>
          <div class="grid2" style="margin-top: 1rem;">
            <div class="field">
              <label>Match Time / Elapsed (e.g. 75', HT, Live)</label>
              <input type="text" name="wc_time_elapsed" value="<?= htmlspecialchars($settings['wc_time_elapsed'] ?? '') ?>" placeholder="e.g. 45' or HT" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
            <div class="field">
              <label>Stadium & Group Info</label>
              <input type="text" name="wc_stadium" value="<?= htmlspecialchars($settings['wc_stadium'] ?? '') ?>" placeholder="e.g. Estadio Azteca — Group A" style="width:100%; padding:0.65rem; border-radius:8px; background:var(--input-bg); color:var(--text); border:1px solid var(--border);"/>
            </div>
          </div>
        </div>

        <!-- Selected Banners Preview (Local Preview before saving) -->
        <div id="new-banners-preview-title" style="margin-top: 1.2rem; margin-bottom: 0.8rem; ">
          <h4 style="font-size:0.85rem; color:var(--gold); text-transform:uppercase;">Selected Files Preview (Will be uploaded on save)</h4>
        </div>
        <div id="new-banners-preview-container" style="display:flex; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;"></div>

        <button type="submit" class="btn" style="margin-top:1rem;"> Save Banner Media Settings</button>
      </form>
    </div>
      <div>
        <h3 style="color:var(--gold); font-size:1.05rem; font-weight:800; margin-bottom:1rem;"> BANGLA NEWS TICKER FEED</h3>
        <form method="POST">
        <input type="hidden" name="action" value="save_branding"/>
        <div class="field">
          <label>Bangla Newspaper RSS Feed URL</label>
          <input type="url" name="rss_feed_url" value="<?= htmlspecialchars($settings['rss_feed_url'] ?? 'https://www.prothomalo.com/feed') ?>" placeholder="e.g. https://www.prothomalo.com/feed"/>
          <p style="font-size:0.75rem; color:var(--muted); margin:0.35rem 0 0 0;">
            Provide a valid XML RSS feed. Examples:
            <br/>— Prothom Alo: <code>https://www.prothomalo.com/feed</code>
            <br/>— BDNews24 Bangla: <code>https://bangla.bdnews24.com/?widgetName=rssfeed&amp;widgetId=1151&amp;getXmlFeed=true</code>
            <br/>— Somoy News RSS or BBC Bangla RSS.
          </p>
        </div>
        <button type="submit" class="btn" style="margin-top:1rem;"> Save RSS Configuration</button>
      </form>
    </div>
    </div>
  </div>
