# Privacy Policy Page — Design Spec
**Date:** 2026-06-02
**Status:** Approved

---

## Overview

Add a public `/privacy-policy` page to the CDRRMO Tandag landing site. The page is required for Google Play Store and Apple App Store compliance (both require a publicly accessible privacy policy URL for apps that collect user data). It is linked from the footer of the existing landing page (`Welcome.vue`).

---

## Architecture

### New files
| File | Purpose |
|------|---------|
| `app/Http/Controllers/PrivacyPolicyController.php` | Invokable controller; returns `Inertia::render('PrivacyPolicy')` |
| `resources/js/Pages/PrivacyPolicy.vue` | Static Inertia page, no server props |

### Modified files
| File | Change |
|------|--------|
| `routes/web.php` | Add `Route::get('/privacy-policy', PrivacyPolicyController::class)->name('privacy-policy')` |
| `resources/js/Pages/Welcome.vue` | Add "Privacy Policy" link in footer |

---

## Route

```
GET /privacy-policy  →  PrivacyPolicyController  →  PrivacyPolicy.vue
```

No authentication required. No props passed from server — content is entirely static in the Vue component.

---

## PrivacyPolicy.vue — Page Structure

### 1. Navigation (sticky header)
Reuses the Welcome page nav pattern (`welcome-nav`, `welcome-nav__inner`, `welcome-brand`). Contains:
- CDRRMO logo + brand text on the left (links back to `/`)
- A single "← Back to home" link on the right

No section navigation pills needed (single-page doc, no scroll spy).

### 2. Hero header section
A blue gradient header (same palette as `welcome-hero` but shorter, no parallax/orbit decorations) containing:
- Small eyebrow label: "CDRRMO · City of Tandag"
- Page title: "Privacy Policy"
- Subtitle: "Effective [date]"

### 3. Content body
Centered, max-width ~680px, white background card. Sections:

1. **Introduction** — Who we are, what this policy covers, that it applies to the CDRRMO mobile app and this website.
2. **Information We Collect** — Four subsections:
   - *Account information*: name, email address, phone number (collected at registration)
   - *Location data*: GPS coordinates when submitting incident reports (with user permission)
   - *Incident reports and media*: text descriptions, photos/files submitted via the app
   - *Device identifiers*: push notification tokens, device type, OS version
3. **How We Use Your Information** — Operational purposes: processing reports, sending alerts, improving the service, contacting users about their reports.
4. **Information Sharing** — We do not sell data. Limited sharing with: City Government of Tandag CDRRMO staff, third-party services required to operate the platform (e.g., push notification providers). No advertising networks.
5. **Data Retention** — Report data retained as part of official CDRRMO records per local government retention rules. Account data retained until account deletion is requested.
6. **Your Rights** — Right to access, correct, or delete personal data. Contact details for exercising rights.
7. **Security** — Brief statement on security practices.
8. **Changes to This Policy** — We may update this policy; the effective date will be updated and users will be notified via the app.
9. **Contact** — CDRRMO Tandag City contact info (office address / email placeholder).

### 4. Footer
Identical to the `welcome-footer` in `Welcome.vue` — same copyright text and CDRRMO logo. Includes the Privacy Policy link (self-referential; style as plain text or omit the link on this page itself to avoid confusion).

---

## Welcome.vue Footer Change

The existing footer row is:
```
[Logo] © YYYY CDRRMO · City Government of Tandag   |   [disclaimer text]
```

Change to add a Privacy Policy link. The right column gains:
```
[disclaimer text] · <Link href="/privacy-policy">Privacy Policy</Link>
```

The link is styled muted, consistent with `welcome-footer__muted`.

---

## Styling

`PrivacyPolicy.vue` uses scoped styles that reuse the same CSS variable palette (`--cdrrmo-*`) and class naming convention (`privacy-*` prefix) as `Welcome.vue`. No new global CSS. The nav and footer blocks copy the same HTML structure from `Welcome.vue` — duplication is acceptable here since there is no shared layout component, and the policy page has significantly different content requirements than the main landing page.

---

## Out of Scope

- No admin editing UI for the policy content (static page only).
- No versioning or change-log tracking.
- No localization / Filipino translation.
- No register-page "agree to terms" checkbox changes (separate task if needed).
