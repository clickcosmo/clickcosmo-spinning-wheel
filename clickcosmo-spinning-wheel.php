<?php
/*
Plugin Name: ClickCOSMO Spinning Wheel
Description: Responsive WordPress spinning wheel for name selection with bulk entry, persistent lists, automatic winner removal, and optional hidden winner targeting [clickcosmo_wheel].
Version: 1.0.1
Author: ClickCOSMO
Author URI: https://clickcosmo.com
ClickCOSMO Support: yes
*/
// NOTE: When using this shortcode on Elementor Canvas pages,
// add a black background fix in Page Settings → Advanced → Custom CSS:
// html, body, .elementor, .elementor-section-wrap { background-color: #000 !important; }

if (!defined('ABSPATH')) exit;

function clickcosmo_wheel_shortcode() {
    ob_start();
    ?>
<style>
    /* Existing Styles */
    *,*::before,*::after{box-sizing:border-box}
    /* NOTE: We keep --size for larger screens, but adjust it below */
    :root { --size: 560px; --accent:#ffd54d; --text:#e8e8ff; --rim:#2a2a45; }
    html, body { height:100%; margin:0; }
    body {color:var(--text); font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; display:grid; place-items:center; }
    .wrap { width:min(95vw,900px); display:grid; gap:16px; justify-items:center; }
    .board { position:relative; width:var(--size); height:var(--size); display:grid; place-items:center; }
    /* The canvas itself should always be 900x900 as the drawing is fixed, we scale the board instead */
    #wheel { width:100%; height:100%; background:transparent; filter: drop-shadow(0 10px 30px rgba(0,0,0,.45)); } 
.pointer { 
      position:absolute; 
      top:0px; /* CHANGED from -8px to 0px */ 
      left:50%; 
      transform:translateX(-50%);
      width:0; 
      height:0; 
      border-left:16px solid transparent; 
      border-right:16px solid transparent; 
      border-bottom:24px solid var(--accent); 
      filter: drop-shadow(0 2px 6px rgba(0,0,0,.4)); 
    }
        #agencyLogo {
        position: absolute; /* Position it relative to the .board container */
        top: 50%;          /* Center vertically */
        left: 50%;         /* Center horizontally */
        transform: translate(-50%, -50%); /* Adjust for its own size to perfectly center */
        width: 60px;       /* Adjust this size as needed for your logo */
        height: 60px;      /* Keep aspect ratio by setting both, or auto one */
        border-radius: 50%; /* If your logo is square, this will make it round */
        object-fit: contain; /* Ensures the image fits without cropping or stretching */
        z-index: 15;       /* Make sure it's above the wheel and the pointer */
    }
    .controls { width:min(95vw,560px); border:1px solid #22283a; background:#14182a; border-radius:12px; padding:12px; display:grid; gap:10px; overflow:hidden; }
    .row { display:flex; gap:10px; align-items:center; justify-content:space-between; width:100%; }
    .flexcol { display:grid; gap:6px; width:100%; }
    
    .input-row { display: flex; gap: 6px; align-items: center; } 
    
    input.name-input { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #333a55; background:#0f1324; color:#e8e8ff; }
    
    .delete-name {
        background: #333a55;
        color: #e8e8ff;
        font-weight: 800;
        border: none;
        padding: 0; 
        width: 38px; 
        height: 38px; 
        border-radius: 8px;
        cursor: pointer;
        box-shadow: none;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 18px;
        line-height: 1;
    }
    .delete-name:active { transform: translateY(1px); }

    button { background: linear-gradient(180deg,#ffd54d,#f7b801); color:#2b1d00; font-weight:800; letter-spacing:.3px; border:none; padding:12px 16px; border-radius:10px; cursor:pointer; box-shadow:0 8px 18px rgba(247,184,1,.3), inset 0 1px 0 rgba(255,255,255,.35); }
    button:active { transform: translateY(1px); }
    .winner { min-height:1.6em; text-align:center; font-size:18px; }

    /* Custom Modal Styles (Adjusted) */
    #modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: none; 
        place-items: center; 
        z-index: 1000;
    }
    #modal-content {
        background: #14182a;
        border: 2px solid var(--accent);
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(247, 184, 1, 0.5);
        text-align: center;
        width: min(90vw, 400px);
    }
    #modal-title {
        color: var(--accent);
        font-size: 2.0em; 
        font-weight: 800; 
        margin-bottom: 10px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        font-family: 'Poppins', 'Helvetica Neue', Arial, sans-serif;
    }
    #modal-winner-name {
        color: var(--text);
        font-size: 2.5em; 
        font-weight: 600; 
        margin: 10px 0 20px 0;
        letter-spacing: 0.5px;
        font-family: 'Poppins', 'Helvetica Neue', Arial, sans-serif; 
        word-break: break-word;
    }
    #modal-close-button {
        /* Reusing the existing button style for consistency */
    }

    /* === MOBILE FIX === */
    @media (max-width: 580px) {
        /* Redefine the size variable for smaller screens */
        :root {
            --size: 90vw; /* Make the size 90% of the viewport width */
        }
        
        /* Reduce font size on the wheel itself for better fit */
        #wheel {
            /* This is a trick to scale down the text drawn on the 900x900 canvas */
            transform: scale(calc(var(--size) / 900px)); 
            /* Push the canvas to the top-left to hide the extra space created by scale */
            transform-origin: top left; 
            width: 900px;
            height: 900px;
        }

        .board {
            /* Now that the wheel is scaled, we size the board to contain the scaled wheel */
            width: var(--size);
            height: var(--size);
            overflow: hidden; /* Hide the scaled-out portion */
        }

        .controls {
            /* Ensure the controls container doesn't exceed the viewport width */
            width: 90vw;
        }
    }
</style>
<center>
  <div class="wrap">
    <div class="board">
      <div class="pointer"></div>
      <canvas id="wheel" width="900" height="900" aria-label="spinning wheel"></canvas>
<a href="/"><img src="<?php echo esc_url( get_site_icon_url() ?: plugins_url( 'wheel-logo.png', __FILE__ ) ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?> Logo" id="agencyLogo"></a>
    </div>

    <div class="controls">
      <div class="row">
        <div class="flexcol" id="namesWrap">
          </div>
      </div>
      <div class="row">
        <button id="addName" type="button">+ Add name</button>
        <button id="clearNames" type="button" style="background: #333a55; color: #e8e8ff; box-shadow: none;">Clear All Names</button>
      </div>
      <div class="row">
        <div style="opacity:.7;font-size:12px;">~ <strong>Paste</strong> names separated with comma to import them!<br/>~ Winner is removed automatically after each spin.</div>      </div>
      <div class="row">
        <button id="spin">SPIN 🎯</button>
        <div id="status">Ready</div>
      </div>
      <div class="winner0" id="winner"></div>
    </div><span style="font-size:8px; color:#FFF !important;">© <a href="/" style="color:#FFF"><strong><?php echo esc_html( get_bloginfo('name') ); ?></strong></a>. Designed by <a href="https://clickcosmo.com" target="_blank" style="color:#FFF"><strong>ClickCOSMO</strong></a></span>
  </div>

  <div id="modal-overlay">
      <div id="modal-content">
          <div id="modal-title">CONGRATULATIONS!</div> 
          <div id="modal-winner-name"></div>
          <button id="modal-close-button">Spin Again</button>
      </div>
  </div>
</center>
<script>
    // Local Storage Key
    const STORAGE_KEY = 'luckyWheelNames';

    // Get DOM elements
    const modalOverlay = document.getElementById('modal-overlay');
    const modalWinnerName = document.getElementById('modal-winner-name');
    const modalCloseButton = document.getElementById('modal-close-button');
    const spinButton = document.getElementById('spin');
    const clearButton = document.getElementById('clearNames');
    const namesWrap = document.getElementById('namesWrap');
    
    // Global state
    let winnerInputEl = null;

    // *** NEW FUNCTION: Save the current active names to Local Storage ***
    function saveNames() {
        const namesToSave = getAllNameInputElements()
            .map(el => el.value)
            .filter(v => v.trim() !== ''); // Only save non-empty names
        
        localStorage.setItem(STORAGE_KEY, JSON.stringify(namesToSave));
    }

    // Function to close the modal and either prepare for next spin OR remove the winner
    function closeModal() {
        modalOverlay.style.display = 'none';
        
        if (winnerInputEl) {
            // Remove the parent container (.input-row)
            winnerInputEl.closest('.input-row').remove(); 
            winnerInputEl = null; 
            
            // Re-run setup functions to update the name list and redraw the wheel
            labels = namesFromInputs();
            drawWheel();
            saveNames(); // *** SAVE NAMES after removal ***
        }
        
        spinButton.disabled = false;
        document.getElementById('status').textContent = 'Ready';
        document.getElementById('winner').textContent = ''; 
    }

    modalCloseButton.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', (e) => {
        if (e.target.id === 'modal-overlay') {
            closeModal();
        }
    });

    // ===== Dynamic names and Rigging Logic =====
    
    function getAllNameInputElements() {
        return Array.from(document.querySelectorAll('.name-input'));
    }

    // This function returns the active names AND updates the global mapping for removal
    function namesFromInputs(){
      const vals = []; 
      const allInputs = getAllNameInputElements();
      const activeInputMap = []; 

      allInputs.forEach(el => { 
        const v = (el.value || '').trim(); 
        if(v) {
            vals.push(v); 
            activeInputMap.push(el);
        }
      });
      
      while (vals.length < 2) {
          vals.push('Player '+(vals.length+1));
          activeInputMap.push(null); 
      }
      
      window.activeInputMap = activeInputMap; 
      
      return vals;
    }

    function addNameInput(value = '') {
      const input = document.createElement('input'); 
      input.className = 'name-input'; 
      input.placeholder = 'Name ' + (namesWrap.querySelectorAll('.name-input').length + 1);
      input.value = value;
      
      const deleteBtn = document.createElement('button');
      deleteBtn.className = 'delete-name';
      deleteBtn.type = 'button';
      deleteBtn.innerHTML = '×'; // Times symbol for 'X'

      const container = document.createElement('div');
      container.className = 'input-row';
      container.appendChild(input);
      container.appendChild(deleteBtn);

      namesWrap.appendChild(container); 
      
      // Add event listeners
      deleteBtn.addEventListener('click', () => {
          container.remove();
          labels = namesFromInputs();
          drawWheel();
          saveNames(); // *** SAVE NAMES after delete ***
      });
      
      input.addEventListener('input', saveNames); // *** SAVE NAMES on input change ***

      return input;
    }
    
    // *** NEW FUNCTION: Load names from Local Storage ***
    function loadNames() {
        namesWrap.innerHTML = '';
        const savedNamesJSON = localStorage.getItem(STORAGE_KEY);

        if (savedNamesJSON) {
            try {
                const savedNames = JSON.parse(savedNamesJSON);
                if (savedNames && savedNames.length > 0) {
                    savedNames.forEach(name => addNameInput(name));
                    return; // Stop here if names were loaded
                }
            } catch (e) {
                console.error("Error parsing saved names from localStorage:", e);
                // Fall through to initialize defaults if loading fails
            }
        }
        
        // If no names were loaded or the list was empty, initialize defaults
        addNameInput('');
        addNameInput('');
        addNameInput('');
    }
    
    // *** NEW FEATURE: CLEAR ALL NAMES (now clears storage) ***
    function clearAllNames() {
        localStorage.removeItem(STORAGE_KEY); // *** CLEAR LOCAL STORAGE ***
        namesWrap.innerHTML = '';
        loadNames(); // Reset inputs to the default three
        labels = namesFromInputs();
        drawWheel();
    }
    clearButton.addEventListener('click', clearAllNames);


    // <--- THIS BLOCK IS REMOVED/REPLACED BY THE NEXT ONE FOR MOBILE SUPPORT --->
    /*
    namesWrap.addEventListener('keydown', (e) => {
      const el = e.target; 
      if (!el.classList || !el.classList.contains('name-input')) return;
      
      if (e.key === '~' || e.keyCode === 192) { 
        e.preventDefault(); 
        el.dataset.rigged = '1';
        saveNames(); 
      }
    });
    */
    // <--- END REMOVED BLOCK --->

    // *** RIGGING FIX: Use the 'input' event for mobile reliability ***
    namesWrap.addEventListener('input', (e) => {
      const el = e.target; 
      if (!el.classList || !el.classList.contains('name-input')) return;
      
      let currentValue = el.value || '';
      
      // 1. Rigging Check: If the tilde is in the value, flag it as rigged and remove the tilde
      if (currentValue.includes('~')) {
          el.dataset.rigged = '1'; // Rig the entry
          currentValue = currentValue.replace(/~/g, ''); // Remove the tilde from the input field
      }
      
      // 2. Sanitization: Keep only safe characters (same as before)
      const sanitizedValue = currentValue.replace(/[^A-Za-z0-9 ,]/g,''); 
      
      // Apply the cleaned/sanitized value back to the input
      if (el.value !== sanitizedValue) {
          el.value = sanitizedValue;
      }

      // 3. Un-rig if the field is cleared
      if (el.value.trim() === '') {
          delete el.dataset.rigged;
      }
      
      // Update the wheel on every input (important for responsiveness)
      labels = namesFromInputs(); 
      drawWheel();
      saveNames(); 
    });


    // *** PASTE LOGIC ***
    namesWrap.addEventListener('paste', (e) => {
      const el = e.target; 
      if (!el.classList || !el.classList.contains('name-input')) return;
      
      e.preventDefault();
      let text = (e.clipboardData || window.clipboardData).getData('text') || '';
      let riggedFlagged = false;

      if (text.includes('~')) {
        riggedFlagged = true;
        text = text.replace(/~/g, '');
      }
      
      if (text.includes(',') || text.includes('\n')) {
          const names = text.split(/,|\n/)
              .map(name => name.trim()) 
              .filter(name => name.length > 0);
          
          if (names.length > 0) {
              el.value = names[0];
              if (riggedFlagged) { el.dataset.rigged = '1'; }
              
              for (let i = 1; i < names.length; i++) {
                  addNameInput(names[i]); 
              }
              saveNames(); // *** SAVE NAMES after paste import ***
              return;
          }
      }
      
      el.value = text;
      
      if (riggedFlagged) { el.dataset.rigged = '1'; }
      saveNames(); // *** SAVE NAMES after single paste ***
    });
    // *** END PASTE LOGIC ***

    function getRiggedIndex(){
      const els = getAllNameInputElements();
      let activeNameCount = 0;
      let riggedIndex = -1;
      
      for (let i = 0; i < els.length; i++){
        const el = els[i];
        const v = (el.value || '').trim();
        
        if (v) {
            if (riggedIndex === -1 && el.dataset && el.dataset.rigged === '1'){
                riggedIndex = activeNameCount; 
            }
            activeNameCount++;
        }
      }
      
      labels = namesFromInputs();
      return riggedIndex;
    }

    document.getElementById('addName').addEventListener('click', () => {
      addNameInput().focus();
      saveNames(); // *** SAVE NAMES after adding input ***
    });

    // ===== Canvas setup and Animation (UNCHANGED) =====
    const cvs = document.getElementById('wheel');
    const ctx = cvs.getContext('2d');
    const SIZE = cvs.width, CENTER = SIZE/2, R = SIZE*0.44;
    const COLORS = ['#6da7ff','#ff6db0','#ffd54d','#9df3c4','#f3a6ff'];

    let rotation = -Math.PI/2;
    let spinning = false;

    function drawWheel(){
      ctx.clearRect(0,0,SIZE,SIZE);
      ctx.save(); ctx.translate(CENTER,CENTER); ctx.rotate(rotation);
      const n = labels.length; 
      const TAU = Math.PI*2; 
      const sweep = TAU/n;

      ctx.beginPath(); 
      ctx.arc(0,0,R+12,0,TAU); 
      ctx.lineWidth=16; 
      ctx.strokeStyle='#2a2a45'; 
      ctx.stroke();

      for(let i=0; i<n; i++){
        const start = i*sweep, end = start + sweep;
        ctx.beginPath(); 
        ctx.moveTo(0,0); 
        ctx.arc(0,0,R,start,end); 
        ctx.closePath();
        ctx.fillStyle = COLORS[i % COLORS.length]; 
        ctx.fill();
        ctx.save(); 
        const mid=start+sweep/2; 
        ctx.rotate(mid); 
        ctx.translate(R*0.65,0); 
        ctx.rotate(Math.PI/2);
        ctx.fillStyle = '#0b0b12'; 
        ctx.font='bold 48px system-ui, -apple-system, Segoe UI, Roboto, Arial';
        ctx.textAlign='center'; 
        ctx.textBaseline='middle';
        const text = labels[i];
        ctx.fillText(text,0,0);
        ctx.restore();
      }

      ctx.beginPath(); 
      ctx.arc(0,0,36,0,TAU); 
      ctx.fillStyle='#0b0b12'; 
      ctx.fill(); 
      ctx.lineWidth=4; 
      ctx.strokeStyle='#2a2a45'; 
      ctx.stroke();
      ctx.restore();
    }
    
    function getLabelAtPointer(){
        const TAU = Math.PI * 2;
        if (labels.length === 0) return '';
        const sweep = TAU / labels.length;
        
        const pointerAngle = -Math.PI / 2;
        const angleRelativeToCanvasStart = (TAU + (pointerAngle - rotation)) % TAU;
        const correctIndex = Math.floor(angleRelativeToCanvasStart / sweep) % labels.length;
        
        return labels[correctIndex];
    }


    function easeOutCubic(t){ return 1 - Math.pow(1-t,3); }

    function spinToIndex(index){
      if(spinning || index < 0 || index >= labels.length) return; 
      
      spinning=true; 
      document.getElementById('winner').textContent=''; 
      document.getElementById('status').textContent='Spinning…';
      document.getElementById('winner').textContent = ''; 
      spinButton.disabled = true;

      const TAU=Math.PI*2; 
      const sweep = TAU/labels.length; 
      const pointerAngle = -Math.PI/2; 

      const targetCenterAngle = index * sweep + sweep / 2; 
      
      const minimumSpins = 4; // Guaranteed minimum spins
      const startRot = rotation;
      
      let finalEndRot = pointerAngle - targetCenterAngle + (minimumSpins * TAU);

      while (finalEndRot <= startRot) {
          finalEndRot += TAU; 
      }
      
      const randomExtraSpins = 1 + Math.floor(Math.random() * 2);
      finalEndRot += randomExtraSpins * TAU; 
      
      
      const dur=3300+Math.random()*900; 
      const t0=performance.now();
      
      function frame(t){ 
        const p=Math.min(1,(t-t0)/dur); 
        
        rotation = (p < 1) ? startRot + (finalEndRot-startRot)*easeOutCubic(p) : finalEndRot; 
        
        drawWheel(); 
        if(p<1) {
          requestAnimationFrame(frame); 
        } else { 
          spinning=false; 
          
          const landed=getLabelAtPointer(); 
          
          // --- POP-UP CODE ---
          const winnerText = landed || labels[index] || 'Error';
          modalWinnerName.textContent = winnerText;
          modalOverlay.style.display = 'grid'; 
          
          // Set winner element for removal
          if (window.activeInputMap && window.activeInputMap[index]) {
             winnerInputEl = window.activeInputMap[index];
          } else {
             winnerInputEl = null; 
          }
          // --- END POP-UP CODE ---

          document.getElementById('status').textContent='Done.'; 
          
          // Un-rig ALL input fields
          document.querySelectorAll('.name-input').forEach(el => {
              if (el.dataset.rigged === '1') {
                  delete el.dataset.rigged;
              }
          });
        } 
      }
      requestAnimationFrame(frame);
    }

    // Spin Button Logic
    document.getElementById('spin').addEventListener('click', () => {
      const rigIdx = getRiggedIndex(); 
      
      if (labels.length === 0) return;
      
      drawWheel();

      const idx = rigIdx >= 0 ? rigIdx : Math.floor(Math.random() * labels.length);
      
      spinToIndex(idx);
    });

    // Idle wobble animation is disabled for a clean stop
    function idle(now){ 
      if(spinning) return requestAnimationFrame(idle); 
      drawWheel(); 
      requestAnimationFrame(idle); 
    }

    // --- INITIALIZATION ---
    loadNames(); // *** LOAD NAMES first thing ***
    labels = namesFromInputs();
    drawWheel(); 
    requestAnimationFrame(idle);
</script>
    <?php
    return ob_get_clean();
}
add_shortcode('clickcosmo_wheel', 'clickcosmo_wheel_shortcode');

if ( is_admin() ) {
    $cc_support_file = plugin_dir_path( __FILE__ ) . 'includes/admin/cc-plugin-support-contact.php';

    if ( file_exists( $cc_support_file ) ) {
        require_once $cc_support_file;
    }
}
