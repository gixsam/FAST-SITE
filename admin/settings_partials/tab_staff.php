<?php /* Fast Site Settings Partial — sec-staff (Staff Access Control) */ ?>
  <?php if($isAdmin): ?>
  <div class="section-card collapsed" id="sec-staff">
    <div class="section-head" onclick="toggleSection('sec-staff')">
      <h2> 4) STUFF ACCESS CONTROL</h2>
      <span class="arrow"></span>
    </div>
    <div class="section-body">
      <form method="POST" style="margin-bottom:1.5rem;">
        <input type="hidden" name="action" value="add_staff"/>
        <div class="grid2">
          <div class="field"><label>New Staff Username</label><input type="text" name="new_username" required/></div>
          <div class="field"><label>Temporary Password</label><input type="password" name="new_password" required/></div>
        </div>
        <button type="submit" class="btn"> Add New Staff Account</button>
      </form>
      
      <div class="desktop-table-wrap">
        <?php if(empty($staff)): ?>
          <div class="empty" style="text-align:center; padding:2rem; color:#8888aa;">No staff users found.</div>
        <?php else: ?>
          <div class="desktop-table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>System Role</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach($staff as $s): ?>
                <tr>
                  <td><?= $s['id'] ?></td>
                  <td style="font-weight:700; color:#fff;"><?= htmlspecialchars($s['username']) ?></td>
                  <td><span class="view-link" style="font-size:0.65rem; pointer-events:none; padding:1px 6px; min-height:22px;"><?= strtoupper($s['role']) ?></span></td>
                  <td>
                    <?php if($s['id'] != 1): ?>
                      <form method="POST" style="margin:0;" onsubmit="return confirm('WARNING: Are you sure you want to delete this staff account?');">
                        <input type="hidden" name="action" value="delete_staff"/>
                        <input type="hidden" name="user_id" value="<?= $s['id'] ?>"/>
                        <button class="btn-del-sm"> Remove</button>
                      </form>
                    <?php else: ?>
                      <span style="font-size:0.75rem; color:var(--muted);">System Root Owner</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile Stacked Cards -->
          <div class="mobile-cards-wrap">
            <?php foreach($staff as $s): ?>
            <div class="box" style="margin-bottom:1rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
              <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <div>
                  <div style="font-weight:700; color:#fff; font-size:1.1rem;"><?= htmlspecialchars($s['username']) ?></div>
                  <div style="font-size:0.75rem; color:var(--muted);">ID: <?= $s['id'] ?></div>
                </div>
                <span class="view-link" style="font-size:0.65rem; pointer-events:none; padding:2px 8px;"><?= strtoupper($s['role']) ?></span>
              </div>

              <?php if($s['id'] != 1): ?>
                <form method="POST" style="margin-top:10px;" onsubmit="return confirm('WARNING: Are you sure you want to delete this staff account?');">
                  <input type="hidden" name="action" value="delete_staff"/>
                  <input type="hidden" name="user_id" value="<?= $s['id'] ?>"/>
                  <button type="submit" class="btn-del-sm" style="width:100%; padding:8px;"> Remove</button>
                </form>
              <?php else: ?>
                <div style="margin-top:10px; font-size:0.85rem; color:var(--gold); text-align:center; padding:8px; background:rgba(252,185,0,0.1); border-radius:6px; border:1px solid rgba(252,185,0,0.2);">System Root Owner</div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      
      <div style="margin-top: 1.5rem; display: flex; justify-content: flex-start;">
        <a href="staff_access.php" class="view-link"> MANAGE PERMISSIONS</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
