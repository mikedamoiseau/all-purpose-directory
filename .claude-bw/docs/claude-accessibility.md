# Accessibility Standards Reference

All UI (admin and frontend) follows WCAG 2.1 Level AA guidelines. When modifying UI code, follow these patterns.

## Required Patterns

### Tooltips must be keyboard accessible

- Add `tabindex="0"` for focus
- Use `role="tooltip"` and `aria-describedby`
- Show on both `:hover` and `:focus`
- Include screen reader text for help icons

### Collapsible sections require

- `role="button"` and `tabindex="0"` on headers
- `aria-expanded` (toggle true/false)
- `aria-controls` pointing to body ID
- Keyboard handlers for Enter/Space

### Form fields with descriptions

- Link via `aria-describedby` to description id
- Labels must be associated with inputs via `for`/`id`

### Decorative icons (dashicons, SVGs)

- Add `aria-hidden="true"`

### Dynamic content (badges, counters, AJAX results)

- Use `aria-live="polite"` for announcements
- Use `aria-busy="true"` during loading states

### Interactive elements

- All clickable elements must be focusable
- Focus states must be visible (don't remove outlines without replacement)
- Focus trap for modals/dialogs

---

## Color Contrast

- Minimum 4.5:1 ratio for normal text
- Minimum 3:1 for large text (18px+ or 14px+ bold)
- Check disabled/muted states especially
- Don't convey information by color alone

---

## Helper Classes

- `.screen-reader-text` - Visually hidden but accessible to screen readers

---

## Translation

All user-facing strings must use WordPress translation functions:

- `__()` for strings
- `_e()` for echoed strings
- `esc_html__()` for escaped output
- `esc_attr__()` for attributes
- `_n()` for plurals
- Text domain: `all-purpose-directory`
