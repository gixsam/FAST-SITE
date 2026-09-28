<!-- ============================================================
     FAST SITE — Global Image Crop & Preview System v2.0
     Supports: multi-image queue, 1:1 crop, live preview grid,
     remove individual images, base64 submission.
     ============================================================ -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
/* ─── Cropper Modal Overlay ─── */
#gc-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.92);
  backdrop-filter: blur(6px);
  z-index: 99999;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
#gc-overlay.show { display: flex; }

#gc-box {
  background: #14141e;
  border: 1px solid rgba(252,185,0,0.25);
  border-radius: 16px;
  width: 100%;
  max-width: 640px;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.7);
}

#gc-header {
  padding: 1rem 1.2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  flex-shrink: 0;
}
#gc-header h3 { color:#fff; margin:0; font-size:1rem; font-weight:700; }
#gc-queue-info { font-size:0.78rem; color:#fcb900; font-weight:700; background:rgba(252,185,0,0.1); padding:3px 9px; border-radius:50px; }

#gc-close-btn {
  background: none; border: none; color: #9ca3af;
  font-size: 1.4rem; cursor: pointer; line-height:1;
  transition: color 0.2s;
}
#gc-close-btn:hover { color: #fff; }

#gc-crop-area {
  flex: 1;
  min-height: 0;
  background: #000;
  position: relative;
  overflow: hidden;
}
#gc-crop-area img {
  display: block;
  max-width: 100%;
  max-height: 100%;
}

#gc-toolbar {
  padding: 0.8rem 1.2rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  border-top: 1px solid rgba(255,255,255,0.06);
  flex-wrap: wrap;
  flex-shrink: 0;
  background: #10101a;
}

.gc-tool-btn {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  color: #fff;
  border-radius: 8px;
  padding: 0.45rem 0.7rem;
  font-size: 0.78rem;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
  white-space: nowrap;
}
.gc-tool-btn:hover { background: rgba(252,185,0,0.12); border-color: rgba(252,185,0,0.3); color: #fcb900; }

.gc-ratio-btn {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  color: #9ca3af;
  border-radius: 8px;
  padding: 0.4rem 0.7rem;
  font-size: 0.75rem;
  cursor: pointer;
  font-weight: 700;
  transition: all 0.2s;
}
.gc-ratio-btn.active,
.gc-ratio-btn:hover { background: rgba(252,185,0,0.1); border-color: rgba(252,185,0,0.3); color: #fcb900; }

.gc-spacer { flex: 1; }

#gc-skip-btn {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  color: #9ca3af;
  border-radius: 8px;
  padding: 0.5rem 1rem;
  font-size: 0.82rem;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}
#gc-skip-btn:hover { background: rgba(255,82,82,0.1); border-color: rgba(255,82,82,0.25); color: #ff5252; }

#gc-apply-btn {
  background: linear-gradient(135deg, #fcb900, #ff9100);
  border: none;
  color: #000;
  border-radius: 8px;
  padding: 0.55rem 1.4rem;
  font-size: 0.85rem;
  cursor: pointer;
  font-weight: 800;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(252,185,0,0.25);
}
#gc-apply-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(252,185,0,0.4); }

/* ─── Preview Grid (on the upload form) ─── */
.gc-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 0.8rem;
  margin-top: 1rem;
}

.gc-preview-item {
  position: relative;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid rgba(255,255,255,0.08);
  background: #0d0d15;
  aspect-ratio: 1;
  transition: border-color 0.2s;
}
.gc-preview-item:first-child {
  border-color: rgba(252,185,0,0.5);
}
.gc-preview-item img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}
.gc-preview-remove {
  position: absolute;
  top: 4px; right: 4px;
  width: 22px; height: 22px;
  border-radius: 50%;
  background: rgba(0,0,0,0.75);
  border: 1px solid rgba(255,82,82,0.5);
  color: #ff5252;
  font-size: 0.75rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  line-height: 1;
  transition: all 0.2s;
}
.gc-preview-remove:hover { background: #ff5252; color: #fff; border-color: #ff5252; }
.gc-thumb-badge {
  position: absolute;
  bottom: 4px; left: 4px;
  background: #fcb900;
  color: #000;
  font-size: 0.55rem;
  font-weight: 800;
  padding: 2px 5px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  pointer-events: none;
}

/* ─── Drop Zone ─── */
.gc-dropzone {
  border: 2px dashed rgba(255,255,255,0.12);
  border-radius: 12px;
  padding: 2rem 1rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  background: rgba(255,255,255,0.02);
  position: relative;
}
.gc-dropzone:hover,
.gc-dropzone.drag-over {
  border-color: rgba(252,185,0,0.5);
  background: rgba(252,185,0,0.04);
}
.gc-dropzone input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}
.gc-dropzone-icon { font-size: 2.2rem; margin-bottom: 0.5rem; display: block; }
.gc-dropzone-text { color: #9ca3af; font-size: 0.82rem; line-height: 1.5; }
.gc-dropzone-text strong { color: #fcb900; }
</style>

<!-- ─── Crop Modal DOM ─── -->
<div id="gc-overlay" role="dialog" aria-modal="true" aria-label="Image Cropper">
  <div id="gc-box">
    <div id="gc-header">
      <h3>✂️ Crop Image</h3>
      <span id="gc-queue-info"></span>
      <button id="gc-close-btn" type="button" onclick="gcSkip()" title="Skip / Close">✕</button>
    </div>
    <div id="gc-crop-area">
      <img id="gc-image" src="" alt="Crop preview"/>
    </div>
    <div id="gc-toolbar">
      <!-- Ratio Buttons -->
      <button type="button" class="gc-ratio-btn active" id="gc-ratio-1-1" onclick="gcSetRatio(1/1, this)">1:1</button>
      <button type="button" class="gc-ratio-btn" id="gc-ratio-4-3" onclick="gcSetRatio(4/3, this)">4:3</button>
      <button type="button" class="gc-ratio-btn" id="gc-ratio-16-9" onclick="gcSetRatio(16/9, this)">16:9</button>
      <button type="button" class="gc-ratio-btn" id="gc-ratio-free" onclick="gcSetRatio(NaN, this)">Free</button>
      <!-- Rotate / Flip -->
      <button type="button" class="gc-tool-btn" onclick="gcRotate(-90)" title="Rotate Left">↺</button>
      <button type="button" class="gc-tool-btn" onclick="gcRotate(90)" title="Rotate Right">↻</button>
      <button type="button" class="gc-tool-btn" onclick="gcFlipX()" title="Flip H">⇄</button>
      <button type="button" class="gc-tool-btn" onclick="gcFlipY()" title="Flip V">⇅</button>
      <span class="gc-spacer"></span>
      <button type="button" id="gc-skip-btn" onclick="gcSkip()">Skip</button>
      <button type="button" id="gc-apply-btn" onclick="gcApply()">✓ Apply Crop</button>
    </div>
  </div>
</div>

<script>
/* ============================================================
   FastSite Crop & Preview Engine v2.0
   ============================================================ */
(function() {
  'use strict';

  // ── State ──────────────────────────────────────────────────
  let _cropper   = null;
  let _queue     = [];   // array of {file, originalDataUrl}
  let _queueIdx  = 0;
  let _results   = [];   // array of {dataUrl, filename}
  let _flipX     = 1;
  let _flipY     = 1;
  let _targetGrid = null; // the preview grid container
  let _hiddenContainer = null; // where we put hidden base64 inputs
  let _inputName = 'images_b64[]'; // name for base64 hidden inputs

  // ── Public helpers ─────────────────────────────────────────
  window.gcRotate = (deg) => { if (_cropper) _cropper.rotate(deg); };
  window.gcFlipX  = () => {
    _flipX *= -1;
    if (_cropper) _cropper.scaleX(_flipX);
  };
  window.gcFlipY  = () => {
    _flipY *= -1;
    if (_cropper) _cropper.scaleY(_flipY);
  };
  window.gcSetRatio = (ratio, btn) => {
    document.querySelectorAll('.gc-ratio-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    if (_cropper) _cropper.setAspectRatio(ratio);
  };
  window.gcApply = applyCrop;
  window.gcSkip  = skipCrop;

  // ── Init on DOMContentLoaded ───────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    // Find the images input
    const originalInput = document.getElementById('images-input');
    const dropzone = document.getElementById('gc-dropzone');
    const dropInput = dropzone ? (dropzone.querySelector('input[type="file"]') || originalInput) : originalInput;

    if (dropInput) {
      dropInput.addEventListener('change', handleFilePick);
    }

    // Drag & Drop handlers on dropzone
    if (dropzone && dropInput) {
      dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
      dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
      dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        if (e.dataTransfer.files.length > 0) {
          handleFilePick({ target: { files: e.dataTransfer.files } });
        }
      });
    }

    // Locate preview grid and hidden input container
    _targetGrid = document.getElementById('gc-preview-grid');
    _hiddenContainer = document.getElementById('gc-hidden-inputs');
  });

  // ── File pick handler ──────────────────────────────────────
  const MAX_IMAGES = 5;

  function handleFilePick(event) {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;

    // Accept image MIME types OR image extensions for Android WebView compatibility
    let imageFiles = files.filter(f => {
      if (f.type && f.type.startsWith('image/')) return true;
      const name = (f.name || '').toLowerCase();
      return /\.(jpe?g|png|webp|gif|bmp|heic|jfif|svg)$/i.test(name) || (!f.type && name.length > 0) || !f.type;
    });
    if (!imageFiles.length) {
      // Fallback: use all selected files if filter was too strict
      imageFiles = files;
    }

    // Count already added
    const existingCount = (_targetGrid || document.getElementById('gc-preview-grid'))
      ? (_targetGrid || document.getElementById('gc-preview-grid')).children.length
      : 0;

    const remaining = MAX_IMAGES - existingCount;
    if (remaining <= 0) {
      alert(`Maximum ${MAX_IMAGES} images allowed. Remove an existing image first.`);
      event.target.value = '';
      return;
    }

    if (imageFiles.length > remaining) {
      alert(`You can only add ${remaining} more image(s). Only the first ${remaining} will be used.`);
      imageFiles = imageFiles.slice(0, remaining);
    }

    // Build queue
    _queue = imageFiles.map(f => ({ file: f, originalDataUrl: null }));
    _queueIdx = 0;
    _flipX = 1; _flipY = 1;

    // Reset the file input value so user can re-pick the same files
    event.target.value = '';

    // Read first file and open modal
    readAndOpen(0);
  }

  function readAndOpen(idx) {
    if (idx >= _queue.length) return;
    _queueIdx = idx;
    _flipX = 1; _flipY = 1;

    const reader = new FileReader();
    reader.onload = (e) => {
      _queue[idx].originalDataUrl = e.target.result;
      openModal(e.target.result);
    };
    reader.readAsDataURL(_queue[idx].file);
  }

  function openModal(dataUrl) {
    const overlay = document.getElementById('gc-overlay');
    const img     = document.getElementById('gc-image');
    const info    = document.getElementById('gc-queue-info');

    // Update queue counter
    info.textContent = `${_queueIdx + 1} / ${_queue.length}`;
    info.style.display = _queue.length > 1 ? 'inline-block' : 'none';

    // Set image source
    img.src = dataUrl;
    overlay.classList.add('show');

    // Destroy old cropper
    if (_cropper) { _cropper.destroy(); _cropper = null; }

    // Init new cropper after render
    setTimeout(() => {
      _cropper = new Cropper(img, {
        aspectRatio: 1,        // default 1:1 for product images
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 0.9,
        restore: false,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
        ready() {
          // Reset ratio buttons UI
          document.querySelectorAll('.gc-ratio-btn').forEach(b => b.classList.remove('active'));
          const r11 = document.getElementById('gc-ratio-1-1');
          if (r11) r11.classList.add('active');
        }
      });
    }, 80);
  }

  function closeModal() {
    const overlay = document.getElementById('gc-overlay');
    overlay.classList.remove('show');
    if (_cropper) { _cropper.destroy(); _cropper = null; }
  }

  // ── Apply crop: get canvas → dataUrl → add to preview ─────
  function applyCrop() {
    if (!_cropper) return;

    _cropper.getCroppedCanvas({
      maxWidth: 1200,
      maxHeight: 1200,
      fillColor: '#ffffff',
      imageSmoothingEnabled: true,
      imageSmoothingQuality: 'high',
    }).toBlob(blob => {
      const reader = new FileReader();
      reader.onload = (e) => {
        addToPreview(e.target.result, _queue[_queueIdx].file.name);
        closeModal();
        advance();
      };
      reader.readAsDataURL(blob);
    }, 'image/jpeg', 0.88);
  }

  // ── Skip: use original un-cropped image ───────────────────
  function skipCrop() {
    addToPreview(_queue[_queueIdx].originalDataUrl, _queue[_queueIdx].file.name);
    closeModal();
    advance();
  }

  // ── Move to next in queue ─────────────────────────────────
  function advance() {
    _queueIdx++;
    if (_queueIdx < _queue.length) {
      setTimeout(() => readAndOpen(_queueIdx), 200);
    }
  }

  // ── Add image to preview grid & hidden input ───────────────
  function addToPreview(dataUrl, filename) {
    if (!_targetGrid) {
      _targetGrid = document.getElementById('gc-preview-grid');
    }
    if (!_hiddenContainer) {
      _hiddenContainer = document.getElementById('gc-hidden-inputs');
    }
    if (!_targetGrid || !_hiddenContainer) return;

    const idx = _targetGrid.children.length; // index for ordering
    const isFirst = idx === 0;

    // Preview item
    const item = document.createElement('div');
    item.className = 'gc-preview-item';
    item.dataset.idx = idx;

    const img = document.createElement('img');
    img.src = dataUrl;
    img.alt = filename;
    item.appendChild(img);

    // Hidden base64 input for form submission
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = _inputName;
    hiddenInput.value = dataUrl;
    _hiddenContainer.appendChild(hiddenInput);

    // Remove button
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'gc-preview-remove';
    removeBtn.innerHTML = '✕';
    removeBtn.title = 'Remove image';
    removeBtn.addEventListener('click', () => removePreviewItem(item, hiddenInput));
    item.appendChild(removeBtn);

    // Thumbnail badge on first image
    if (isFirst) {
      const badge = document.createElement('span');
      badge.className = 'gc-thumb-badge';
      badge.textContent = 'Thumbnail';
      item.appendChild(badge);
    }

    _targetGrid.appendChild(item);
  }

  function removePreviewItem(item, hiddenInput) {
    item.remove();
    hiddenInput.remove();
    // Re-badge: first child gets the thumbnail badge
    const items = _targetGrid.querySelectorAll('.gc-preview-item');
    items.forEach((el, i) => {
      let badge = el.querySelector('.gc-thumb-badge');
      if (i === 0) {
        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'gc-thumb-badge';
          badge.textContent = 'Thumbnail';
          el.appendChild(badge);
        }
        el.style.borderColor = 'rgba(252,185,0,0.5)';
      } else {
        if (badge) badge.remove();
        el.style.borderColor = '';
      }
    });
  }

})();
</script>
