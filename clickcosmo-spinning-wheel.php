<?php
/*
Plugin Name: ClickCOSMO Spinning Wheel
Description: Responsive WordPress spinning wheel for name selection with bulk entry, persistent lists, automatic winner removal, and optional hidden winner targeting [clickcosmo_wheel].
Version: 1.0.2
Author: ClickCOSMO
Author URI: https://clickcosmo.com
ClickCOSMO Support: yes
*/
// NOTE: When using this shortcode on Elementor Canvas pages,
// add a black background fix in Page Settings → Advanced → Custom CSS:
// html, body, .elementor, .elementor-section-wrap { background-color: #000 !important; }

if (!defined('ABSPATH')) exit;

function clickcosmo_wheel_shortcode() {
    static $instance_number = 0;
    static $styles_rendered = false;

    $instance_number++;

    $instance_id = 'ccsw-' . $instance_number . '-' . wp_rand(1000, 999999);
    $page_id = get_queried_object_id();
    $storage_scope = $page_id ? (string) $page_id : 'global';
    $storage_key = 'clickcosmoSpinningWheelNames:' . $storage_scope . ':' . $instance_number;
    $can_import_legacy_storage = ($instance_number === 1);
    $show_admin_tip = current_user_can('manage_options');

    ob_start();

    if (!$styles_rendered) {
        $styles_rendered = true;
        ?>
<style>
    .clickcosmo-spinning-wheel,
    .clickcosmo-spinning-wheel *,
    .clickcosmo-spinning-wheel *::before,
    .clickcosmo-spinning-wheel *::after {
        box-sizing: border-box;
    }

    .clickcosmo-spinning-wheel {
        --ccsw-size: min(560px, 90vw);
        --ccsw-accent: #ffd54d;
        --ccsw-text: #e8e8ff;
        --ccsw-rim: #2a2a45;
        color: var(--ccsw-text);
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        width: 100%;
        display: grid;
        place-items: center;
    }

    .clickcosmo-spinning-wheel .ccsw-wrap {
        width: min(95vw, 900px);
        display: grid;
        gap: 16px;
        justify-items: center;
    }

    .clickcosmo-spinning-wheel .ccsw-board {
        position: relative;
        width: var(--ccsw-size);
        height: var(--ccsw-size);
        display: grid;
        place-items: center;
    }

    .clickcosmo-spinning-wheel .ccsw-wheel {
        width: 100%;
        height: 100%;
        background: transparent;
        filter: drop-shadow(0 10px 30px rgba(0,0,0,.45));
    }

    .clickcosmo-spinning-wheel .ccsw-pointer {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 16px solid transparent;
        border-right: 16px solid transparent;
        border-bottom: 24px solid var(--ccsw-accent);
        filter: drop-shadow(0 2px 6px rgba(0,0,0,.4));
        z-index: 20;
    }

    .clickcosmo-spinning-wheel .ccsw-site-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: contain;
        z-index: 15;
    }

    .clickcosmo-spinning-wheel .ccsw-controls {
        width: min(95vw, 560px);
        border: 1px solid #22283a;
        background: #14182a;
        border-radius: 12px;
        padding: 12px;
        display: grid;
        gap: 10px;
        overflow: hidden;
    }

    .clickcosmo-spinning-wheel .ccsw-row {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    .clickcosmo-spinning-wheel .ccsw-flexcol {
        display: grid;
        gap: 6px;
        width: 100%;
    }

    .clickcosmo-spinning-wheel .ccsw-input-row {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .clickcosmo-spinning-wheel .ccsw-name-input {
        width: 100%;
        padding: 8px 10px;
        border-radius: 8px;
        border: 1px solid #333a55;
        background: #0f1324;
        color: #e8e8ff;
    }

    .clickcosmo-spinning-wheel .ccsw-button {
        background: linear-gradient(180deg,#ffd54d,#f7b801);
        color: #2b1d00;
        font-weight: 800;
        letter-spacing: .3px;
        border: none;
        padding: 12px 16px;
        border-radius: 10px;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(247,184,1,.3), inset 0 1px 0 rgba(255,255,255,.35);
    }

    .clickcosmo-spinning-wheel .ccsw-button:active,
    .clickcosmo-spinning-wheel .ccsw-delete-name:active {
        transform: translateY(1px);
    }

    .clickcosmo-spinning-wheel .ccsw-button:disabled {
        cursor: default;
        opacity: .65;
    }

    .clickcosmo-spinning-wheel .ccsw-secondary-button {
        background: #333a55;
        color: #e8e8ff;
        box-shadow: none;
    }

    .clickcosmo-spinning-wheel .ccsw-delete-name {
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

    .clickcosmo-spinning-wheel .ccsw-help-text {
        opacity: .7;
        font-size: 12px;
    }

    .clickcosmo-spinning-wheel .ccsw-admin-tip {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 9px 10px;
        border: 1px dashed #5e668a;
        border-radius: 8px;
        background: #0f1324;
        color: #e8e8ff;
        font-size: 12px;
        line-height: 1.4;
    }

    .clickcosmo-spinning-wheel .ccsw-admin-tip[hidden] {
        display: none;
    }

    .clickcosmo-spinning-wheel .ccsw-admin-tip code {
        color: var(--ccsw-accent);
        font-weight: 800;
    }

    .clickcosmo-spinning-wheel .ccsw-admin-tip-hide {
        flex-shrink: 0;
        background: #333a55;
        color: #e8e8ff;
        border: none;
        border-radius: 7px;
        padding: 6px 9px;
        cursor: pointer;
        font: inherit;
        font-weight: 700;
    }

    .clickcosmo-spinning-wheel .ccsw-status {
        min-width: 54px;
        text-align: right;
    }

    .clickcosmo-spinning-wheel .ccsw-winner {
        min-height: 1.6em;
        text-align: center;
        font-size: 18px;
    }

    .clickcosmo-spinning-wheel .ccsw-credit {
        font-size: 8px;
        color: #fff;
    }

    .clickcosmo-spinning-wheel .ccsw-credit a {
        color: #fff;
    }

    .clickcosmo-spinning-wheel .ccsw-modal-overlay {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: none;
        place-items: center;
        z-index: 100000;
    }

    .clickcosmo-spinning-wheel .ccsw-modal-content {
        background: #14182a;
        border: 2px solid var(--ccsw-accent);
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(247, 184, 1, 0.5);
        text-align: center;
        width: min(90vw, 400px);
    }

    .clickcosmo-spinning-wheel .ccsw-modal-title {
        color: var(--ccsw-accent);
        font-size: 2em;
        font-weight: 800;
        margin-bottom: 10px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        font-family: 'Poppins', 'Helvetica Neue', Arial, sans-serif;
    }

    .clickcosmo-spinning-wheel .ccsw-modal-winner-name {
        color: var(--ccsw-text);
        font-size: 2.5em;
        font-weight: 600;
        margin: 10px 0 20px;
        letter-spacing: .5px;
        font-family: 'Poppins', 'Helvetica Neue', Arial, sans-serif;
        word-break: break-word;
    }

    @media (max-width: 580px) {
        .clickcosmo-spinning-wheel {
            --ccsw-size: 90vw;
        }

        .clickcosmo-spinning-wheel .ccsw-controls {
            width: 90vw;
        }

        .clickcosmo-spinning-wheel .ccsw-admin-tip {
            align-items: flex-start;
        }
    }
</style>
        <?php
    }
    ?>
<div id="<?php echo esc_attr($instance_id); ?>" class="clickcosmo-spinning-wheel">
    <div class="ccsw-wrap">
        <div class="ccsw-board">
            <div class="ccsw-pointer"></div>
            <canvas class="ccsw-wheel" data-role="wheel" width="900" height="900" aria-label="Spinning wheel"></canvas>
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img
                    src="<?php echo esc_url(get_site_icon_url() ?: plugins_url('wheel-logo.png', __FILE__)); ?>"
                    alt="<?php echo esc_attr(get_bloginfo('name')); ?> Logo"
                    class="ccsw-site-logo"
                >
            </a>
        </div>

        <div class="ccsw-controls">
            <div class="ccsw-row">
                <div class="ccsw-flexcol" data-role="names-wrap"></div>
            </div>

            <div class="ccsw-row">
                <button class="ccsw-button" data-role="add-name" type="button">+ Add name</button>
                <button class="ccsw-button ccsw-secondary-button" data-role="clear-names" type="button">Clear All Names</button>
            </div>

            <div class="ccsw-row">
                <div class="ccsw-help-text">~ <strong>Paste</strong> names separated with comma to import them!<br>~ Winner is removed automatically after each spin.</div>
            </div>

            <?php if ($show_admin_tip) : ?>
                <div class="ccsw-admin-tip" data-role="admin-tip">
                    <div><strong>Admin Tip:</strong> Add <code>~</code> anywhere in a name to target that entry as the next winner. The marker disappears immediately and is never shown on the wheel.</div>
                    <button type="button" class="ccsw-admin-tip-hide" data-role="hide-admin-tip">Hide</button>
                </div>
            <?php endif; ?>

            <div class="ccsw-row">
                <button class="ccsw-button" data-role="spin" type="button">SPIN 🎯</button>
                <div class="ccsw-status" data-role="status">Ready</div>
            </div>

            <div class="ccsw-winner" data-role="winner"></div>
        </div>

        <span class="ccsw-credit">
            © <a href="<?php echo esc_url(home_url('/')); ?>"><strong><?php echo esc_html(get_bloginfo('name')); ?></strong></a>.
            Designed by <a href="https://clickcosmo.com" target="_blank" rel="noopener noreferrer"><strong>ClickCOSMO</strong></a>
        </span>
    </div>

    <div class="ccsw-modal-overlay" data-role="modal-overlay" role="dialog" aria-modal="true" aria-label="Winner">
        <div class="ccsw-modal-content">
            <div class="ccsw-modal-title">CONGRATULATIONS!</div>
            <div class="ccsw-modal-winner-name" data-role="modal-winner-name"></div>
            <button class="ccsw-button" data-role="modal-close" type="button">Spin Again</button>
        </div>
    </div>
</div>
<script>
(() => {
    const root = document.getElementById(<?php echo wp_json_encode($instance_id); ?>);
    if (!root) return;

    const STORAGE_KEY = <?php echo wp_json_encode($storage_key); ?>;
    const LEGACY_STORAGE_KEY = 'luckyWheelNames';
    const CAN_IMPORT_LEGACY_STORAGE = <?php echo $can_import_legacy_storage ? 'true' : 'false'; ?>;
    const ADMIN_TIP_HIDDEN_KEY = 'clickcosmoSpinningWheelAdminTipHidden';

    const modalOverlay = root.querySelector('[data-role="modal-overlay"]');
    const modalWinnerName = root.querySelector('[data-role="modal-winner-name"]');
    const modalCloseButton = root.querySelector('[data-role="modal-close"]');
    const spinButton = root.querySelector('[data-role="spin"]');
    const clearButton = root.querySelector('[data-role="clear-names"]');
    const addButton = root.querySelector('[data-role="add-name"]');
    const namesWrap = root.querySelector('[data-role="names-wrap"]');
    const statusEl = root.querySelector('[data-role="status"]');
    const winnerEl = root.querySelector('[data-role="winner"]');
    const adminTip = root.querySelector('[data-role="admin-tip"]');
    const hideAdminTipButton = root.querySelector('[data-role="hide-admin-tip"]');
    const cvs = root.querySelector('[data-role="wheel"]');
    const ctx = cvs.getContext('2d');

    const SIZE = cvs.width;
    const CENTER = SIZE / 2;
    const R = SIZE * 0.44;
    const COLORS = ['#6da7ff','#ff6db0','#ffd54d','#9df3c4','#f3a6ff'];

    let labels = [];
    let activeInputMap = [];
    let winnerInputEl = null;
    let rotation = -Math.PI / 2;
    let spinning = false;

    function storageGet(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function storageSet(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (e) {
            // Storage can be unavailable in privacy-restricted browser contexts.
        }
    }

    function storageRemove(key) {
        try {
            window.localStorage.removeItem(key);
        } catch (e) {
            // Storage can be unavailable in privacy-restricted browser contexts.
        }
    }

    if (adminTip) {
        if (storageGet(ADMIN_TIP_HIDDEN_KEY) === '1') {
            adminTip.hidden = true;
        }

        if (hideAdminTipButton) {
            hideAdminTipButton.addEventListener('click', () => {
                adminTip.hidden = true;
                storageSet(ADMIN_TIP_HIDDEN_KEY, '1');
            });
        }
    }

    function getAllNameInputElements() {
        return Array.from(namesWrap.querySelectorAll('.ccsw-name-input'));
    }

    function saveNames() {
        const namesToSave = getAllNameInputElements()
            .map(el => el.value)
            .filter(v => v.trim() !== '');

        storageSet(STORAGE_KEY, JSON.stringify(namesToSave));
    }

    function cleanVisibleName(value) {
        return String(value || '')
            .replace(/~/g, '')
            .replace(/[\u0000-\u001F\u007F]/g, '');
    }

    function clearRiggedEntries(exceptElement = null) {
        getAllNameInputElements().forEach(el => {
            if (el !== exceptElement) {
                delete el.dataset.rigged;
            }
        });
    }

    function namesFromInputs() {
        const vals = [];
        activeInputMap = [];

        getAllNameInputElements().forEach(el => {
            const value = (el.value || '').trim();
            if (value) {
                vals.push(value);
                activeInputMap.push(el);
            }
        });

        while (vals.length < 2) {
            vals.push('Player ' + (vals.length + 1));
            activeInputMap.push(null);
        }

        return vals;
    }

    function addNameInput(value = '', rigged = false) {
        const input = document.createElement('input');
        input.className = 'ccsw-name-input';
        input.placeholder = 'Name ' + (namesWrap.querySelectorAll('.ccsw-name-input').length + 1);
        input.value = cleanVisibleName(value);

        if (rigged) {
            input.dataset.rigged = '1';
        }

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'ccsw-delete-name';
        deleteBtn.type = 'button';
        deleteBtn.setAttribute('aria-label', 'Remove name');
        deleteBtn.textContent = '×';

        const container = document.createElement('div');
        container.className = 'ccsw-input-row';
        container.appendChild(input);
        container.appendChild(deleteBtn);
        namesWrap.appendChild(container);

        deleteBtn.addEventListener('click', () => {
            container.remove();
            labels = namesFromInputs();
            drawWheel();
            saveNames();
        });

        return input;
    }

    function loadNames() {
        namesWrap.innerHTML = '';

        let savedNamesJSON = storageGet(STORAGE_KEY);
        let loadedLegacyNames = false;

        if (!savedNamesJSON && CAN_IMPORT_LEGACY_STORAGE) {
            savedNamesJSON = storageGet(LEGACY_STORAGE_KEY);
            loadedLegacyNames = !!savedNamesJSON;
        }

        if (savedNamesJSON) {
            try {
                const savedNames = JSON.parse(savedNamesJSON);
                if (Array.isArray(savedNames) && savedNames.length > 0) {
                    savedNames.forEach(name => addNameInput(name));

                    if (loadedLegacyNames) {
                        saveNames();
                    }
                    return;
                }
            } catch (e) {
                console.error('Error parsing saved spinning wheel names:', e);
            }
        }

        addNameInput('');
        addNameInput('');
        addNameInput('');
    }

    function clearAllNames() {
        storageRemove(STORAGE_KEY);
        namesWrap.innerHTML = '';
        addNameInput('');
        addNameInput('');
        addNameInput('');
        labels = namesFromInputs();
        drawWheel();
    }

    namesWrap.addEventListener('input', (event) => {
        const el = event.target;
        if (!el.classList || !el.classList.contains('ccsw-name-input')) return;

        let currentValue = el.value || '';

        if (currentValue.includes('~')) {
            clearRiggedEntries(el);
            el.dataset.rigged = '1';
            currentValue = currentValue.replace(/~/g, '');
        }

        const cleanedValue = cleanVisibleName(currentValue);
        if (el.value !== cleanedValue) {
            el.value = cleanedValue;
        }

        if (el.value.trim() === '') {
            delete el.dataset.rigged;
        }

        labels = namesFromInputs();
        drawWheel();
        saveNames();
    });

    namesWrap.addEventListener('paste', (event) => {
        const el = event.target;
        if (!el.classList || !el.classList.contains('ccsw-name-input')) return;

        event.preventDefault();

        const pastedText = (event.clipboardData || window.clipboardData).getData('text') || '';
        const rawNames = pastedText
            .split(/,|\r?\n/)
            .map(name => name.trim())
            .filter(name => name.length > 0);

        if (rawNames.length === 0) {
            return;
        }

        let riggedAssigned = false;
        const parsedNames = rawNames.map(rawName => {
            const hasRigMarker = rawName.includes('~');
            const shouldRig = hasRigMarker && !riggedAssigned;

            if (shouldRig) {
                riggedAssigned = true;
            }

            return {
                value: cleanVisibleName(rawName),
                rigged: shouldRig
            };
        }).filter(item => item.value.trim() !== '');

        if (parsedNames.length === 0) {
            return;
        }

        if (riggedAssigned) {
            clearRiggedEntries();
        }

        el.value = parsedNames[0].value;
        if (parsedNames[0].rigged) {
            el.dataset.rigged = '1';
        }

        for (let i = 1; i < parsedNames.length; i++) {
            addNameInput(parsedNames[i].value, parsedNames[i].rigged);
        }

        labels = namesFromInputs();
        drawWheel();
        saveNames();
    });

    function getRiggedIndex() {
        const inputs = getAllNameInputElements();
        let activeNameCount = 0;
        let riggedIndex = -1;

        for (let i = 0; i < inputs.length; i++) {
            const el = inputs[i];
            const value = (el.value || '').trim();

            if (value) {
                if (riggedIndex === -1 && el.dataset.rigged === '1') {
                    riggedIndex = activeNameCount;
                }
                activeNameCount++;
            }
        }

        labels = namesFromInputs();
        return riggedIndex;
    }

    function drawWheel() {
        ctx.clearRect(0, 0, SIZE, SIZE);
        ctx.save();
        ctx.translate(CENTER, CENTER);
        ctx.rotate(rotation);

        const n = labels.length;
        const TAU = Math.PI * 2;
        const sweep = TAU / n;

        ctx.beginPath();
        ctx.arc(0, 0, R + 12, 0, TAU);
        ctx.lineWidth = 16;
        ctx.strokeStyle = '#2a2a45';
        ctx.stroke();

        for (let i = 0; i < n; i++) {
            const start = i * sweep;
            const end = start + sweep;

            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.arc(0, 0, R, start, end);
            ctx.closePath();
            ctx.fillStyle = COLORS[i % COLORS.length];
            ctx.fill();

            ctx.save();
            const mid = start + sweep / 2;
            ctx.rotate(mid);
            ctx.translate(R * 0.65, 0);
            ctx.rotate(Math.PI / 2);
            ctx.fillStyle = '#0b0b12';
            ctx.font = 'bold 48px system-ui, -apple-system, Segoe UI, Roboto, Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(labels[i], 0, 0);
            ctx.restore();
        }

        ctx.beginPath();
        ctx.arc(0, 0, 36, 0, TAU);
        ctx.fillStyle = '#0b0b12';
        ctx.fill();
        ctx.lineWidth = 4;
        ctx.strokeStyle = '#2a2a45';
        ctx.stroke();
        ctx.restore();
    }

    function getLabelAtPointer() {
        const TAU = Math.PI * 2;
        if (labels.length === 0) return '';

        const sweep = TAU / labels.length;
        const pointerAngle = -Math.PI / 2;
        const angleRelativeToCanvasStart = ((pointerAngle - rotation) % TAU + TAU) % TAU;
        const correctIndex = Math.floor(angleRelativeToCanvasStart / sweep) % labels.length;

        return labels[correctIndex];
    }

    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    function closeModal() {
        modalOverlay.style.display = 'none';

        if (winnerInputEl) {
            const row = winnerInputEl.closest('.ccsw-input-row');
            if (row) {
                row.remove();
            }
            winnerInputEl = null;

            labels = namesFromInputs();
            drawWheel();
            saveNames();
        }

        spinButton.disabled = false;
        statusEl.textContent = 'Ready';
        winnerEl.textContent = '';
    }

    function spinToIndex(index) {
        if (spinning || index < 0 || index >= labels.length) return;

        spinning = true;
        winnerEl.textContent = '';
        statusEl.textContent = 'Spinning…';
        spinButton.disabled = true;

        const TAU = Math.PI * 2;
        const sweep = TAU / labels.length;
        const pointerAngle = -Math.PI / 2;
        const targetCenterAngle = index * sweep + sweep / 2;
        const minimumSpins = 4;
        const randomExtraSpins = 1 + Math.floor(Math.random() * 2);
        const startRot = rotation;

        const targetRotation = pointerAngle - targetCenterAngle;
        const currentNormalized = ((startRot % TAU) + TAU) % TAU;
        const targetNormalized = ((targetRotation % TAU) + TAU) % TAU;
        const targetDelta = (targetNormalized - currentNormalized + TAU) % TAU;
        const finalEndRot = startRot + ((minimumSpins + randomExtraSpins) * TAU) + targetDelta;

        const duration = 3300 + Math.random() * 900;
        const startTime = performance.now();

        function frame(time) {
            const progress = Math.min(1, (time - startTime) / duration);
            rotation = progress < 1
                ? startRot + (finalEndRot - startRot) * easeOutCubic(progress)
                : finalEndRot;

            drawWheel();

            if (progress < 1) {
                requestAnimationFrame(frame);
                return;
            }

            spinning = false;

            // Keep the stored angle small without changing the visible wheel position.
            rotation = ((finalEndRot % TAU) + TAU) % TAU;
            drawWheel();

            const landed = getLabelAtPointer();
            const winnerText = labels[index] || landed || 'Error';
            modalWinnerName.textContent = winnerText;
            modalOverlay.style.display = 'grid';
            modalCloseButton.focus();

            winnerInputEl = activeInputMap[index] || null;
            statusEl.textContent = 'Done.';

            clearRiggedEntries();
        }

        requestAnimationFrame(frame);
    }

    modalCloseButton.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', (event) => {
        if (event.target === modalOverlay) {
            closeModal();
        }
    });

    clearButton.addEventListener('click', clearAllNames);

    addButton.addEventListener('click', () => {
        addNameInput().focus();
        saveNames();
    });

    spinButton.addEventListener('click', () => {
        const riggedIndex = getRiggedIndex();
        if (labels.length === 0) return;

        drawWheel();
        const index = riggedIndex >= 0
            ? riggedIndex
            : Math.floor(Math.random() * labels.length);

        spinToIndex(index);
    });

    function idle() {
        if (spinning) {
            requestAnimationFrame(idle);
            return;
        }

        drawWheel();
        requestAnimationFrame(idle);
    }

    loadNames();
    labels = namesFromInputs();
    drawWheel();
    requestAnimationFrame(idle);
})();
</script>
    <?php
    return ob_get_clean();
}
add_shortcode('clickcosmo_wheel', 'clickcosmo_wheel_shortcode');

if (is_admin()) {
    $cc_support_file = plugin_dir_path(__FILE__) . 'includes/admin/cc-plugin-support-contact.php';

    if (file_exists($cc_support_file)) {
        require_once $cc_support_file;
    }
}
