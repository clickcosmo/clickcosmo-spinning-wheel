(() => {
    const LEGACY_STORAGE_KEY = 'luckyWheelNames';
    const ADMIN_TIP_HIDDEN_KEY = 'clickcosmoSpinningWheelAdminTipHidden';

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

    function initWheel(root) {
        if (!root || root.dataset.ccswInitialized === '1') {
            return;
        }

        root.dataset.ccswInitialized = '1';

        const storageKey = root.dataset.storageKey || 'clickcosmoSpinningWheelNames:global:1';
        const canImportLegacyStorage = root.dataset.canImportLegacyStorage === '1';

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
        const canvas = root.querySelector('[data-role="wheel"]');

        if (!modalOverlay || !modalWinnerName || !modalCloseButton || !spinButton || !clearButton || !addButton || !namesWrap || !statusEl || !winnerEl || !canvas) {
            return;
        }

        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        const size = canvas.width;
        const center = size / 2;
        const radius = size * 0.44;
        const colors = ['#6da7ff', '#ff6db0', '#ffd54d', '#9df3c4', '#f3a6ff'];

        let labels = [];
        let activeInputMap = [];
        let winnerInputEl = null;
        let rotation = -Math.PI / 2;
        let spinning = false;

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
                .map((element) => element.value)
                .filter((value) => value.trim() !== '');

            storageSet(storageKey, JSON.stringify(namesToSave));
        }

        function cleanVisibleName(value) {
            return String(value || '')
                .replace(/~/g, '')
                .replace(/[\u0000-\u001F\u007F]/g, '');
        }

        function clearRiggedEntries(exceptElement = null) {
            getAllNameInputElements().forEach((element) => {
                if (element !== exceptElement) {
                    delete element.dataset.rigged;
                }
            });
        }

        function namesFromInputs() {
            const values = [];
            activeInputMap = [];

            getAllNameInputElements().forEach((element) => {
                const value = (element.value || '').trim();
                if (value) {
                    values.push(value);
                    activeInputMap.push(element);
                }
            });

            while (values.length < 2) {
                values.push('Player ' + (values.length + 1));
                activeInputMap.push(null);
            }

            return values;
        }

        function addNameInput(value = '', rigged = false) {
            const input = document.createElement('input');
            input.className = 'ccsw-name-input';
            input.placeholder = 'Name ' + (namesWrap.querySelectorAll('.ccsw-name-input').length + 1);
            input.value = cleanVisibleName(value);

            if (rigged) {
                input.dataset.rigged = '1';
            }

            const deleteButton = document.createElement('button');
            deleteButton.className = 'ccsw-delete-name';
            deleteButton.type = 'button';
            deleteButton.setAttribute('aria-label', 'Remove name');
            deleteButton.textContent = '×';

            const container = document.createElement('div');
            container.className = 'ccsw-input-row';
            container.appendChild(input);
            container.appendChild(deleteButton);
            namesWrap.appendChild(container);

            deleteButton.addEventListener('click', () => {
                container.remove();
                labels = namesFromInputs();
                drawWheel();
                saveNames();
            });

            return input;
        }

        function loadNames() {
            namesWrap.innerHTML = '';

            let savedNamesJson = storageGet(storageKey);
            let loadedLegacyNames = false;

            if (!savedNamesJson && canImportLegacyStorage) {
                savedNamesJson = storageGet(LEGACY_STORAGE_KEY);
                loadedLegacyNames = !!savedNamesJson;
            }

            if (savedNamesJson) {
                try {
                    const savedNames = JSON.parse(savedNamesJson);
                    if (Array.isArray(savedNames) && savedNames.length > 0) {
                        savedNames.forEach((name) => addNameInput(name));

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
            storageRemove(storageKey);
            namesWrap.innerHTML = '';
            addNameInput('');
            addNameInput('');
            addNameInput('');
            labels = namesFromInputs();
            drawWheel();
        }

        namesWrap.addEventListener('input', (event) => {
            const element = event.target;
            if (!element.classList || !element.classList.contains('ccsw-name-input')) {
                return;
            }

            let currentValue = element.value || '';

            if (currentValue.includes('~')) {
                clearRiggedEntries(element);
                element.dataset.rigged = '1';
                currentValue = currentValue.replace(/~/g, '');
            }

            const cleanedValue = cleanVisibleName(currentValue);
            if (element.value !== cleanedValue) {
                element.value = cleanedValue;
            }

            if (element.value.trim() === '') {
                delete element.dataset.rigged;
            }

            labels = namesFromInputs();
            drawWheel();
            saveNames();
        });

        namesWrap.addEventListener('paste', (event) => {
            const element = event.target;
            if (!element.classList || !element.classList.contains('ccsw-name-input')) {
                return;
            }

            event.preventDefault();

            const pastedText = (event.clipboardData || window.clipboardData).getData('text') || '';
            const rawNames = pastedText
                .split(/,|\r?\n/)
                .map((name) => name.trim())
                .filter((name) => name.length > 0);

            if (rawNames.length === 0) {
                return;
            }

            let riggedAssigned = false;
            const parsedNames = rawNames
                .map((rawName) => {
                    const hasRigMarker = rawName.includes('~');
                    const shouldRig = hasRigMarker && !riggedAssigned;

                    if (shouldRig) {
                        riggedAssigned = true;
                    }

                    return {
                        value: cleanVisibleName(rawName),
                        rigged: shouldRig
                    };
                })
                .filter((item) => item.value.trim() !== '');

            if (parsedNames.length === 0) {
                return;
            }

            if (riggedAssigned) {
                clearRiggedEntries();
            }

            element.value = parsedNames[0].value;
            if (parsedNames[0].rigged) {
                element.dataset.rigged = '1';
            }

            for (let index = 1; index < parsedNames.length; index++) {
                addNameInput(parsedNames[index].value, parsedNames[index].rigged);
            }

            labels = namesFromInputs();
            drawWheel();
            saveNames();
        });

        function getRiggedIndex() {
            const inputs = getAllNameInputElements();
            let activeNameCount = 0;
            let riggedIndex = -1;

            for (let index = 0; index < inputs.length; index++) {
                const element = inputs[index];
                const value = (element.value || '').trim();

                if (value) {
                    if (riggedIndex === -1 && element.dataset.rigged === '1') {
                        riggedIndex = activeNameCount;
                    }
                    activeNameCount++;
                }
            }

            labels = namesFromInputs();
            return riggedIndex;
        }

        function drawWheel() {
            context.clearRect(0, 0, size, size);
            context.save();
            context.translate(center, center);
            context.rotate(rotation);

            const count = labels.length;
            const fullTurn = Math.PI * 2;
            const sweep = fullTurn / count;

            context.beginPath();
            context.arc(0, 0, radius + 12, 0, fullTurn);
            context.lineWidth = 16;
            context.strokeStyle = '#2a2a45';
            context.stroke();

            for (let index = 0; index < count; index++) {
                const start = index * sweep;
                const end = start + sweep;

                context.beginPath();
                context.moveTo(0, 0);
                context.arc(0, 0, radius, start, end);
                context.closePath();
                context.fillStyle = colors[index % colors.length];
                context.fill();

                context.save();
                const middle = start + sweep / 2;
                context.rotate(middle);
                context.translate(radius * 0.65, 0);
                context.rotate(Math.PI / 2);
                context.fillStyle = '#0b0b12';
                context.font = 'bold 48px system-ui, -apple-system, Segoe UI, Roboto, Arial';
                context.textAlign = 'center';
                context.textBaseline = 'middle';
                context.fillText(labels[index], 0, 0);
                context.restore();
            }

            context.beginPath();
            context.arc(0, 0, 36, 0, fullTurn);
            context.fillStyle = '#0b0b12';
            context.fill();
            context.lineWidth = 4;
            context.strokeStyle = '#2a2a45';
            context.stroke();
            context.restore();
        }

        function getLabelAtPointer() {
            const fullTurn = Math.PI * 2;
            if (labels.length === 0) {
                return '';
            }

            const sweep = fullTurn / labels.length;
            const pointerAngle = -Math.PI / 2;
            const angleRelativeToCanvasStart = ((pointerAngle - rotation) % fullTurn + fullTurn) % fullTurn;
            const correctIndex = Math.floor(angleRelativeToCanvasStart / sweep) % labels.length;

            return labels[correctIndex];
        }

        function easeOutCubic(value) {
            return 1 - Math.pow(1 - value, 3);
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
            if (spinning || index < 0 || index >= labels.length) {
                return;
            }

            spinning = true;
            winnerEl.textContent = '';
            statusEl.textContent = 'Spinning…';
            spinButton.disabled = true;

            const fullTurn = Math.PI * 2;
            const sweep = fullTurn / labels.length;
            const pointerAngle = -Math.PI / 2;
            const targetCenterAngle = index * sweep + sweep / 2;
            const minimumSpins = 4;
            const randomExtraSpins = 1 + Math.floor(Math.random() * 2);
            const startRotation = rotation;

            const targetRotation = pointerAngle - targetCenterAngle;
            const currentNormalized = ((startRotation % fullTurn) + fullTurn) % fullTurn;
            const targetNormalized = ((targetRotation % fullTurn) + fullTurn) % fullTurn;
            const targetDelta = (targetNormalized - currentNormalized + fullTurn) % fullTurn;
            const finalRotation = startRotation + ((minimumSpins + randomExtraSpins) * fullTurn) + targetDelta;

            const duration = 3300 + Math.random() * 900;
            const startTime = performance.now();

            function frame(time) {
                const progress = Math.min(1, (time - startTime) / duration);
                rotation = progress < 1
                    ? startRotation + (finalRotation - startRotation) * easeOutCubic(progress)
                    : finalRotation;

                drawWheel();

                if (progress < 1) {
                    requestAnimationFrame(frame);
                    return;
                }

                spinning = false;
                rotation = ((finalRotation % fullTurn) + fullTurn) % fullTurn;
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
            if (labels.length === 0) {
                return;
            }

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
    }

    function initAllWheels() {
        document.querySelectorAll('.clickcosmo-spinning-wheel').forEach(initWheel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllWheels, { once: true });
    } else {
        initAllWheels();
    }
})();
