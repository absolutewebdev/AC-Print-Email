/**
 * @license GPL-2.0-or-later
 */





/* -------- ac-print-email plugin polish + accessibility only -------------

Responsibilities:

- Keyboard navigation
- Focus management
- ARIA-expanded state syncing
- Escape key handling
- Outside-click close (if not already present)
- aria-live announcements (“Copied ✓”)

*/

(function () {
  'use strict';

  function qs(el, sel) { return el ? el.querySelector(sel) : null; }
  function qsa(el, sel) { return el ? Array.prototype.slice.call(el.querySelectorAll(sel)) : []; }

  function getToolbar(node) {
    return node && node.closest ? node.closest('.acpe-toolbar[data-acpe]') : null;
  }

  function getMenu(toolbar) {
    return qs(toolbar, '.acpe-menu'); // <-- YOUR CLASS
  }

  function getToggle(toolbar) {
    return qs(toolbar, '.acpe-email-toggle');
  }

  function isOpen(toolbar) {
    const toggle = getToggle(toolbar);
    const menu = getMenu(toolbar);
    if (!toggle || !menu) return false;
    return toggle.getAttribute('aria-expanded') === 'true' && menu.hidden === false;
  }

  function openMenu(toolbar, focusFirst) {
    const toggle = getToggle(toolbar);
    const menu = getMenu(toolbar);
    if (!toggle || !menu) return;

    toggle.setAttribute('aria-expanded', 'true');
    menu.hidden = false;

    if (focusFirst) {
      const items = qsa(menu, '[role="menuitem"]');
      if (items[0]) items[0].focus();
    }
  }

  function closeMenu(toolbar, returnFocus) {
    const toggle = getToggle(toolbar);
    const menu = getMenu(toolbar);
    if (!toggle || !menu) return;

    toggle.setAttribute('aria-expanded', 'false');
    menu.hidden = true;

    if (returnFocus) toggle.focus();
  }

  function closeAll(exceptToolbar) {
    qsa(document, '.acpe-toolbar[data-acpe]').forEach(tb => {
      if (exceptToolbar && tb === exceptToolbar) return;
      if (isOpen(tb)) closeMenu(tb, false);
    });
  }

  function focusFirst(toolbar) {
    const menu = getMenu(toolbar);
    if (!menu) return;
    const items = qsa(menu, '[role="menuitem"]');
    items[0] && items[0].focus();
  }

  function focusLast(toolbar) {
    const menu = getMenu(toolbar);
    if (!menu) return;
    const items = qsa(menu, '[role="menuitem"]');
    items[items.length - 1] && items[items.length - 1].focus();
  }

  // --- KEYBOARD NAVIGATION ---
  document.addEventListener('keydown', function (e) {
    const key = (e.key === 'Down') ? 'ArrowDown' : (e.key === 'Up') ? 'ArrowUp' : e.key;

    // Which toolbar are we in? (works even if target is a <span>)
    const toolbar = getToolbar(e.target);
    if (!toolbar) return;

    const toggle = getToggle(toolbar);
    const menu = getMenu(toolbar);
    if (!toggle || !menu) return;

    const toggleEl = e.target.closest ? e.target.closest('.acpe-email-toggle') : null;
    const inMenu   = e.target.closest ? !!e.target.closest('.acpe-menu') : false;

    // If focus is on Email toggle (or inside it)
    if (toggleEl) {
      if (key === 'Enter' || key === ' ' || key === 'ArrowDown') {
        e.preventDefault();
        closeAll(toolbar);
        openMenu(toolbar, false);
        requestAnimationFrame(() => focusFirst(toolbar));
        return;
      }

      if (key === 'ArrowUp') {
        e.preventDefault();
        closeAll(toolbar);
        openMenu(toolbar, false);
        requestAnimationFrame(() => focusLast(toolbar));
        return;
      }

      if (key === 'Escape') {
        e.preventDefault();
        closeAll();
        return;
      }
    }

    // If focus is inside the menu
    if (inMenu && isOpen(toolbar)) {
      const items = qsa(menu, '[role="menuitem"]');
      if (!items.length) return;

      const currentIndex = items.indexOf(document.activeElement);

      if (key === 'Escape') {
        e.preventDefault();
        closeMenu(toolbar, true);
        return;
      }

      if (key === 'ArrowDown') {
        e.preventDefault();
        const next = items[(currentIndex + 1 + items.length) % items.length];
        next && next.focus();
        return;
      }

      if (key === 'ArrowUp') {
        e.preventDefault();
        const prev = items[(currentIndex - 1 + items.length) % items.length];
        prev && prev.focus();
        return;
      }

      if (key === 'Home') {
        e.preventDefault();
        items[0].focus();
        return;
      }

      if (key === 'End') {
        e.preventDefault();
        items[items.length - 1].focus();
        return;
      }

      if (key === 'Tab') {
        closeAll();
        return;
      }
    }
  }, true); // ✅ capture phase here (correct place)

})(); // end IIFE
