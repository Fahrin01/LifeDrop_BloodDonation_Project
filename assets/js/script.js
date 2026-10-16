document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Mobile hamburger nav ---------- */
    const hamburger = document.getElementById('hamburgerBtn');
    const navLinks = document.getElementById('navLinks');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function () {
            navLinks.classList.toggle('open');
        });
    }

    /* ---------- Generic dropdown toggler (notifications / user menu) ---------- */
    function setupDropdown(btnId, dropdownId) {
        const btn = document.getElementById(btnId);
        const dropdown = document.getElementById(dropdownId);
        if (!btn || !dropdown) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.notif-dropdown.open, .user-menu-dropdown.open').forEach(function (el) {
                if (el !== dropdown) el.classList.remove('open');
            });
            dropdown.classList.toggle('open');
        });
    }
    setupDropdown('notifBellBtn', 'notifDropdown');
    setupDropdown('userMenuBtn', 'userMenuDropdown');

    document.addEventListener('click', function () {
        document.querySelectorAll('.notif-dropdown.open, .user-menu-dropdown.open').forEach(function (el) {
            el.classList.remove('open');
        });
    });

    /* ---------- Dependent Division -> District -> Area dropdowns ---------- */
    // Any select with [data-division] loads districts into the select with
    // the id given in data-target-district; that in turn loads areas into
    // the select with id given in its own data-target-area.
    document.querySelectorAll('select[data-division]').forEach(function (divisionSelect) {
        const districtSelectId = divisionSelect.getAttribute('data-target-district');
        const districtSelect = districtSelectId ? document.getElementById(districtSelectId) : null;
        if (!districtSelect) return;

        divisionSelect.addEventListener('change', function () {
            loadDistricts(divisionSelect.value, districtSelect, districtSelect.dataset.selected || '');
        });
    });

    document.querySelectorAll('select[data-district]').forEach(function (districtSelect) {
        const areaListId = districtSelect.getAttribute('data-target-area');
        const areaList = areaListId ? document.getElementById(areaListId) : null;
        if (!areaList) return;

        districtSelect.addEventListener('change', function () {
            loadAreas(districtSelect.value, areaList);
        });
    });

    function loadDistricts(division, targetSelect, preselect) {
        targetSelect.innerHTML = '<option value="">Loading...</option>';
        if (!division) {
            targetSelect.innerHTML = '<option value="">Select District</option>';
            return;
        }
        fetch(window.SITE_URL + '/includes/ajax_locations.php?type=districts&division=' + encodeURIComponent(division))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                let html = '<option value="">Select District</option>';
                data.forEach(function (d) {
                    const sel = (d === preselect) ? 'selected' : '';
                    html += '<option value="' + d + '" ' + sel + '>' + d + '</option>';
                });
                targetSelect.innerHTML = html;
                targetSelect.dispatchEvent(new Event('change'));
            })
            .catch(function () {
                targetSelect.innerHTML = '<option value="">Select District</option>';
            });
    }

    // Areas are a free-text input with a <datalist> of suggestions, so the
    // person can pick a known area or type their own if it isn't listed.
    function loadAreas(district, targetDatalist) {
        targetDatalist.innerHTML = '';
        if (!district) return;
        fetch(window.SITE_URL + '/includes/ajax_locations.php?type=areas&district=' + encodeURIComponent(district))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                let html = '';
                data.forEach(function (a) {
                    html += '<option value="' + a + '"></option>';
                });
                targetDatalist.innerHTML = html;
            })
            .catch(function () {
                targetDatalist.innerHTML = '';
            });
    }

    // Initial load on page render, in case a division/district is preselected
    // (e.g. when re-showing a form after a validation error).
    document.querySelectorAll('select[data-division]').forEach(function (divisionSelect) {
        if (divisionSelect.value) {
            divisionSelect.dispatchEvent(new Event('change'));
        }
    });

    /* ---------- Confirm destructive actions ---------- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    /* ---------- Auto-hide flash messages ---------- */
    document.querySelectorAll('.flash').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 6000);
    });

    /* ---------- Simple polling for message threads ---------- */
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages && chatMessages.dataset.pollUrl) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
        setInterval(function () {
            fetch(chatMessages.dataset.pollUrl)
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    const wasAtBottom = chatMessages.scrollTop + chatMessages.clientHeight >= chatMessages.scrollHeight - 40;
                    chatMessages.innerHTML = html;
                    if (wasAtBottom) chatMessages.scrollTop = chatMessages.scrollHeight;
                });
        }, 5000);
    }
});
