# Accessibility

## Overview
We are committed to making ClockWise accessible to all users. This repository documents the WCAG 2.1 Level AA improvements applied across the Daily Time Record and Leave Management System, the scope of work, testing approach, and known limitations.

## Scope
**Target standard:** WCAG 2.1 Level AA  
**Areas covered:** UI styles, semantic markup, keyboard interaction, forms, modals, calendar grid, and server-side validation.

## What was changed (high-level)
- **CSS:** global focus styles, contrast adjustments, responsive reflow, touch target sizing, reduced-motion support.
- **Markup:** semantic landmarks, proper headings, table headers with `scope`, labelled form controls.
- **Behavior:** keyboard focus management, modal focus trap, accessible alerts (`role="alert"` / `aria-live`), explicit error handling.
- **Files modified:** `main.css`, `dashboard.css`, `form.css`, `login.css`, `calendar.css`, and PHP templates such as `login.php`, `admin-dashboard.php`, `user-dashboard.php`, `add-employee.php`, `edit-employee.php`, `add-department.php`, `add-shift.php`.

## Key implementation notes
- **Skip link:** `a[href="#main-content"]` visible on focus to bypass navigation.
- **Contrast:** Muted text updated from `#6C757D` to `#595959`; status colours recalibrated to meet contrast ratios.
- **Focus visible:** Global `:focus-visible` rule with a 3px gold outline (`#FFB81C`).
- **Forms:** `<label for="...">` associations, `aria-required="true"`, `aria-invalid="true"`, and `aria-describedby` for per-field errors.
- **Calendar:** Marked up as `role="grid"`; each day cell is `role="gridcell"`, `tabindex="0"`, and has an `aria-label` summarizing date and status.
- **Modals:** Full focus trap; Escape closes modal; focus returns to trigger element on close.
- **Reduced motion:** Animations disabled when `prefers-reduced-motion: reduce` is set.
- **Touch targets:** Interactive elements meet 44×44 CSS pixel minimum.

## Testing checklist
- Automated: axe DevTools, WAVE, Lighthouse Accessibility.
- Screen readers: NVDA + Chrome (Windows), VoiceOver + Safari (macOS/iOS).
- Keyboard-only: Tab through all interactive elements; verify logical focus order.
- Contrast: WebAIM Contrast Checker for custom colours.
- Zoom & reflow: Verify at 200% zoom and 320px viewport width (no horizontal scroll).
- Manual verification: confirm `aria-live` announcements for dynamic updates and server-side validation messages.

## Known limitations and next steps
- Replace `window.confirm()` with a custom `role="alertdialog"` confirmation component with full focus trap.
- Add visible password show/hide toggles for password fields.
- Ensure all remaining form fields include `autocomplete` attributes.
- Ensure server-side validation messages are announced via `aria-live` for AJAX flows.