/**
 * Fast Site Global Loader & Progress Bar System
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Inject Top Progress Bar HTML
    if (!document.getElementById('global-top-loader')) {
        const loader = document.createElement('div');
        loader.id = 'global-top-loader';
        document.body.prepend(loader);
    }
    
    // 2. Global Page Transition Loader (triggers on links and normal form submissions)
    const topLoader = document.getElementById('global-top-loader');
    
    function startTopLoader() {
        if(topLoader) {
            topLoader.style.opacity = '1';
            topLoader.style.width = '30%';
            setTimeout(() => { topLoader.style.width = '70%'; }, 200);
            setTimeout(() => { topLoader.style.width = '90%'; }, 800);
        }
    }
    
    // Listen to all anchor clicks (that don't have target="_blank" or #hash)
    document.addEventListener('click', function(e) {
        let target = e.target.closest('a');
        if (target && target.href && !target.href.includes('#') && target.target !== '_blank' && !target.hasAttribute('download')) {
            startTopLoader();
        }
    });

    // Listen to standard form submissions
    document.addEventListener('submit', function(e) {
        // Skip if form has a specific class indicating it's handled via custom AJAX
        if (!e.target.classList.contains('ajax-progress-form')) {
            startTopLoader();
        }
    });
});

/**
 * Global function to handle AJAX File Uploads with 1% to 100% Progress
 * @param {HTMLFormElement} formElement - The form to submit
 * @param {String} redirectUrl - Where to redirect on success
 */
function handleAjaxUploadProgress(formElement, redirectUrl = '') {
    // Inject Progress Overlay if not exists
    if (!document.getElementById('upload-progress-overlay')) {
        const overlayHtml = `
            <div id="upload-progress-overlay">
                <div class="progress-modal">
                    <div class="spinner"></div>
                    <h3 id="progress-title">Uploading...</h3>
                    <div class="progress-track">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="progress-text" id="progress-text">0%</div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', overlayHtml);
    }

    const overlay = document.getElementById('upload-progress-overlay');
    const fill = document.getElementById('progress-fill');
    const text = document.getElementById('progress-text');
    const submitBtn = formElement.querySelector('button[type="submit"]');

    // Prevent default
    formElement.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // UI State
        overlay.style.display = 'flex';
        fill.style.width = '0%';
        text.innerText = '0%';
        if(submitBtn) submitBtn.disabled = true;

        const formData = new FormData(formElement);
        const xhr = new XMLHttpRequest();

        xhr.open('POST', formElement.action || window.location.href, true);

        // Upload Progress Event
        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                let percentComplete = Math.round((event.loaded / event.total) * 100);
                fill.style.width = percentComplete + '%';
                text.innerText = percentComplete + '%';
                
                if (percentComplete === 100) {
                    document.getElementById('progress-title').innerText = 'Processing... Please wait.';
                }
            }
        };

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                // Success! The server processed the request.
                document.getElementById('progress-title').innerText = 'Complete! Redirecting...';
                fill.style.backgroundColor = '#00e676'; // Green success
                text.style.color = '#00e676';
                
                setTimeout(() => {
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        window.location.reload();
                    }
                }, 800);
            } else {
                // Server returned an error (e.g. 500)
                alert('Upload failed on the server. Please try again.');
                overlay.style.display = 'none';
                if(submitBtn) submitBtn.disabled = false;
            }
        };

        xhr.onerror = function() {
            alert('Network error occurred during upload. Please check your connection.');
            overlay.style.display = 'none';
            if(submitBtn) submitBtn.disabled = false;
        };

        xhr.send(formData);
    });
}
