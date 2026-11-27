/**
 * Panel layout: off-canvas sidebar drawer for narrow screens (issue #33).
 *
 * Loaded in <head> by layouts/panel.php so the `has-drawer` class is set before the first paint
 * (no flash of the stacked sidebar). Without JavaScript the sidebar simply stacks above the content.
 * Presentation only: no data, no requests, no business logic.
 */
(() => {
    'use strict';

    const root = document.documentElement;
    root.classList.add('has-drawer');

    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('panel-sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (!toggle || !sidebar || !overlay) {
            return;
        }

        const narrow = window.matchMedia('(max-width: 900px)');
        const focusableSelector = 'a[href], button:not([disabled])';

        const isOpen = () => sidebar.classList.contains('is-open');

        const open = () => {
            sidebar.classList.add('is-open');
            overlay.hidden = false;
            root.classList.add('drawer-open');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Cerrar menú');

            const first = sidebar.querySelector(focusableSelector);
            if (first) {
                first.focus();
            }
        };

        const close = (returnFocus = true) => {
            sidebar.classList.remove('is-open');
            overlay.hidden = true;
            root.classList.remove('drawer-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Abrir menú');

            if (returnFocus) {
                toggle.focus();
            }
        };

        toggle.addEventListener('click', () => {
            if (isOpen()) {
                close();
            } else {
                open();
            }
        });

        overlay.addEventListener('click', () => close());

        document.addEventListener('keydown', (event) => {
            if (!isOpen()) {
                return;
            }

            if (event.key === 'Escape') {
                close();
                return;
            }

            // Keep Tab inside the open drawer.
            if (event.key === 'Tab') {
                const items = Array.from(sidebar.querySelectorAll(focusableSelector));
                if (items.length === 0) {
                    return;
                }

                const first = items[0];
                const last = items[items.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });

        // Growing past the breakpoint (rotation, resize) leaves the desktop layout in a clean state.
        const onBreakpointChange = (event) => {
            if (!event.matches && isOpen()) {
                close(false);
            }
        };

        if (typeof narrow.addEventListener === 'function') {
            narrow.addEventListener('change', onBreakpointChange);
        } else {
            narrow.addListener(onBreakpointChange);
        }
    });

    // HU-22: progressive enhancement for the topbar notification bell popover.
    // Without JS, clicking the bell navigates directly to /notificaciones.
    document.addEventListener('DOMContentLoaded', () => {
        const wrapper = document.getElementById('notification-bell-wrapper');
        const toggle = document.getElementById('notification-bell-toggle');
        const popover = document.getElementById('notification-popover');

        if (!wrapper || !toggle || !popover) {
            return;
        }

        const closePopover = () => {
            popover.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        const openPopover = () => {
            popover.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        };

        toggle.addEventListener('click', (event) => {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }
            event.preventDefault();
            if (popover.hidden) {
                openPopover();
            } else {
                closePopover();
            }
        });

        document.addEventListener('click', (event) => {
            if (!popover.hidden && !wrapper.contains(event.target)) {
                closePopover();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !popover.hidden) {
                closePopover();
                toggle.focus();
            }
        });
    });
})();
