<?php /* Fast Site Settings Partial — sec-all-partners-program (Partners Program) */ ?>
  <div class="section-card collapsed" id="sec-all-partners-program">
    <div class="section-head" onclick="toggleSection('sec-all-partners-program')">
      <h2> 3) ALL PARTNER'S PROGRAM</h2>
      <span class="arrow"></span>
    </div>
    <div class="section-body" style="display:flex; flex-direction:column; gap:2rem;">
      <div style="border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:1.5rem;">
        <h3 style="color:var(--gold); font-size:1.05rem; font-weight:800; margin-bottom:1rem;"> CLIENT & REFERRAL MANAGEMENT</h3>
        <div class="program-row">
        <div class="program-info">
          <h4>Customer Accounts</h4>
          <p>Monitor user registration and orders.</p>
        </div>
        <a href="dashboard.php" class="view-link"> View Orders</a>
      </div>
      </div>
      <div style="border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:1.5rem;">
        <h3 style="color:var(--gold); font-size:1.05rem; font-weight:800; margin-bottom:1rem;"> AFFILIATE AGENT PROGRAM</h3>
        <div style="display:flex; gap:0.5rem; margin-bottom:1rem; flex-wrap:wrap;">
        <span class="view-link" style="font-size:0.72rem; pointer-events:none;"> <?= $agentCount ?> Active Agents</span>
        <span class="view-link" style="font-size:0.72rem; color:var(--red); border-color:rgba(255,82,82,0.3); pointer-events:none;"> <?= $pendingCount ?> Pending Review</span>
      </div>
      <div class="program-row">
        <div class="program-info">
          <h4>Agent Registry</h4>
          <p>View, verify, suspend or approve agents.</p>
        </div>
        <a href="agents.php" class="view-link"> View Agents</a>
      </div>
      <div class="program-row">
        <div class="program-info">
          <h4>Agent Target Tasks</h4>
          <p>Configure agent achievements and bonus cash payouts.</p>
        </div>
        <a href="tasks.php" class="view-link"> Manage Tasks</a>
      </div>
      <div class="program-row">
        <div class="program-info">
          <h4>Cash Out Payouts</h4>
          <p>Manage pending bKash/Nagad withdrawals.</p>
        </div>
        <a href="payouts.php" class="view-link"> View Payouts</a>
      </div>
      
      <div style="background: rgba(33, 150, 243, 0.05); border: 1px solid rgba(33, 150, 243, 0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <h4 style="color:var(--brand); margin-bottom: 0.5rem; font-size: 0.85rem;"> Referral Settings Guide</h4>
        <p style="font-size: 0.75rem; color: var(--muted); line-height: 1.4; margin-bottom: 0;">
          The system's referral rewards are dynamic based on the user's tier. 
          Normal users earn <b>Coins</b> (configured below under "Default Referral Reward (Coins)"), which can be withdrawn once they reach a minimum threshold.
          Agents earn <b>Cash Commissions</b> (configured below under "Agent Registration Commission"), which are added directly to their pending cash balance. 
          To edit these values, update the parameters below and save.
        </p>
      </div>

      <form method="POST" style="margin-top:0.2rem; padding-top:1.2rem; border-top:1px solid rgba(255,255,255,0.06);">
        <input type="hidden" name="action" value="save_payout"/>
        <div class="field" style="margin-bottom:0.75rem;"><strong style="font-size:0.8rem; color:var(--gold);">Default Agent Parameters</strong></div>
        <div class="grid2">
          <div class="field">
            <label>Global Default Commission %</label>
            <input type="number" name="default_commission_pct" step="0.1" min="0" max="100" value="<?= htmlspecialchars($settings['default_commission_pct'] ?? '20') ?>"/>
          </div>
          <div class="field">
            <label>Min Payout Limit ()</label>
            <input type="number" name="min_payout" step="1" min="0" value="<?= htmlspecialchars($settings['min_payout'] ?? '200') ?>"/>
          </div>
        </div>
        <button type="submit" class="btn"> Save Agent Defaults</button>
      </form>
    </div>
      <div>
        <h3 style="color:var(--gold); font-size:1.05rem; font-weight:800; margin-bottom:1rem;"> EXTERNAL & API PARTNERS</h3>
        <div class="program-row">
        <div class="program-info">
          <h4> API Partner Access</h4>
          <p>Register third-party developer integrations & Webhooks.</p>
        </div>
        <a href="api_partners.php" class="view-link"> API Partners</a>
      </div>
      <div class="program-row">
        <div class="program-info">
          <h4> Affiliate Partner Links</h4>
          <p>Manage custom external redirects (Daraz, Trip.com).</p>
        </div>
        <a href="partner_shops.php" class="view-link"> Manage Partners</a>
      </div>
    </div>
    </div>
  </div>
