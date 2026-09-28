import sys

with open('admin/wallet.php', 'r', encoding='utf-8') as f:
    content = f.read()

debit_handler = r'''
// Handle Admin Coin Debit
if (\['REQUEST_METHOD'] === 'POST' && isset(\['action']) && \['action'] === 'admin_debit_coins') {
    \ = trim(\['target_user'] ?? '');
    \ = floatval(\['amount'] ?? 0);
    \ = trim(\['note'] ?? 'Admin Manual Deduction');
    
    if (!empty(\) && \ > 0) {
        \ = \->prepare("SELECT id, name FROM users WHERE id = ? OR email = ? OR phone = ? LIMIT 1");
        \->execute([\, \, \]);
        \ = \->fetch(PDO::FETCH_ASSOC);
        
        if (\) {
            \ = \['id'];
            \ = \['name'] ?: "User #\";
            try {
                \->beginTransaction();
                \->prepare("UPDATE users SET coins_balance = GREATEST(0, coins_balance - ?) WHERE id = ?")->execute([\, \]);
                
                \->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, description, status) VALUES (?, 'admin_debit', ?, 'Admin Manual Deduction', ?, 'completed')")
                    ->execute([\, \, \]);

                \->commit();
                \ = "<div style='color:#ef4444; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(239,68,68,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(239,68,68,0.3); display:flex; align-items:center; gap:8px;'>? Successfully deducted " . number_format(\, 2) . " Coins from \ (#\)!</div>";
                
                // Refresh total
                \ = (float)(\->query("SELECT SUM(coins_balance) FROM users")->fetchColumn() ?: 0);
            } catch (Exception \) {
                if (\->inTransaction()) {
                    \->rollBack();
                }
                \ = "<div style='color:#ff5252; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(255,82,82,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(255,82,82,0.3); display:flex; align-items:center; gap:8px;'>?? Failed to deduct coins: " . htmlspecialchars(\->getMessage()) . "</div>";
            }
        } else {
            \ = "<div style='color:#ff5252; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(255,82,82,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(255,82,82,0.3); display:flex; align-items:center; gap:8px;'>?? User not found!</div>";
        }
    }
}
'''
debit_handler = debit_handler.replace('\$', '$')
content = content.replace('// Fetch recent global transactions safely', debit_handler + '\n// Fetch recent global transactions safely')

debit_html = r'''
    <!-- -- INSTANT COIN DEBIT TOOL -- -->
    <div class="panel-card" style="border-color: rgba(239, 68, 68, 0.3);">
      <h3 style="color:#ef4444; font-size:1.15rem; font-weight:800; margin-bottom:0.4rem; display:flex; align-items:center; gap:8px;">
        <span>??</span> Instant Fast Site Coins Debit (Manual Penalty / Payout Deduction)
      </h3>
      <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">
        Manually deduct Fast Site Coins from any user's balance.
      </p>

      <form method="POST" class="gift-form-grid">
        <input type="hidden" name="action" value="admin_debit_coins"/>
        
        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Target User ID, Email or Phone *</label>
          <input type="text" name="target_user" class="custom-input" placeholder="e.g. 5 or user@email.com or 01337320544" required />
        </div>

        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Amount (Coins) *</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="custom-input" placeholder="e.g. 500" required />
        </div>

        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Note / Reason *</label>
          <input type="text" name="note" class="custom-input" placeholder="e.g. Manual Cash Payout Completed" required />
        </div>

        <button type="submit" class="btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color:#fff; font-weight:900; padding: 0.8rem 1.6rem; border-radius: 10px; border:none; cursor:pointer; font-size:0.95rem; white-space:nowrap;">
          Deduct Coins ??
        </button>
      </form>
    </div>
'''

content = content.replace('<!-- -- 3. RECENT GLOBAL TRANSACTIONS -- -->', debit_html + '\n    <!-- -- 3. RECENT GLOBAL TRANSACTIONS -- -->')

# Update Global Financial Transactions Query
new_tx_query = r'''
\ = \->query("
    SELECT ct.*, u.name as user_name, u.phone as user_phone
    FROM coin_transactions ct
    LEFT JOIN users u ON ct.user_id = u.id
    ORDER BY ct.created_at DESC LIMIT 20
")->fetchAll();
'''
new_tx_query = new_tx_query.replace('\$', '$')

# Replace lines 89 to 139 with new query
start_marker = '// Fetch recent global transactions safely with User & Shop names'
end_marker = '\ = array_slice(\, 0, 15);'

start_idx = content.find(start_marker)
end_idx = content.find(end_marker)
if start_idx != -1 and end_idx != -1:
    end_idx += len(end_marker)
    content = content[:start_idx] + '// Fetch recent global transactions safely from coin_transactions' + new_tx_query + content[end_idx:]

with open('admin/wallet.php', 'w', encoding='utf-8') as f:
    f.write(content)
