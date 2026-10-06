=== AC Print + Email ===
Contributors: absolutewebdev
Tags: print, email, sharing, accessibility, privacy
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A clean, privacy-first Print and Email toolbar for WordPress. No tracking, no third-party services, no UI bloat.

== Description ==

AC Print + Email adds a simple Print link and a lightweight Email dropdown to posts, pages, and archive views.

It is designed for sites that value clarity, accessibility, and user trust over analytics-heavy sharing widgets and third-party services.

The plugin uses:
* The browser’s native print dialog
* The visitor’s own email provider or mail client
* A copy-link fallback for universal compatibility

No emails are sent from your server.  
No tracking pixels are injected.  
No external APIs are required.

== Philosophy: Human-first sharing ==

Most sharing plugins today are built around tracking, engagement metrics, and external networks.

AC Print + Email is built around people.

This plugin follows a few simple principles:

* **Use tools users already trust**  
  Printing uses the browser’s native print dialog. Email opens in the user’s own mail provider or mail app.

* **Avoid third-party services**  
  No URL shorteners, redirect chains, or external dashboards.

* **Respect privacy by default**  
  No analytics, no tracking pixels, no background requests.

* **Separate user intent**  
  Printing and emailing are distinct actions with different expectations and behaviors.

* **Stay out of the way**  
  The toolbar is minimal, theme-agnostic, and designed to blend into content.

This approach mirrors how the early web worked: direct, understandable, and user-controlled.

== Features ==

* Print using the browser’s native print dialog
* Clean print stylesheet (removes navigation, widgets, and UI chrome)
* Email options:
  - Gmail
  - Yahoo Mail
  - Outlook / Hotmail
  - AOL Mail
  - Default mail client (mailto)
  - Copy link fallback
* Fully accessible:
  - Keyboard navigation
  - ARIA roles and states
  - Screen-reader friendly feedback
* Display modes:
  - Icons + labels
  - Icons only
  - Labels only
* Per-content controls:
  - Enable on posts
  - Enable on pages
  - Enable on category archives
  - Enable on tag archives
* Placement control:
  - Automatic injection
  - Shortcode-only mode for manual placement [ac_print_email]
* Live admin preview panel
* No dependencies
* No tracking
* No advertisements

== Placement & Theme Compatibility ==

By default, the toolbar is automatically injected into supported content.

For older or heavily customized themes, you may prefer **Shortcode only** placement, which gives you full control over where the toolbar appears.

Use the shortcode: [ac_print_email]


This mode avoids layout conflicts and is recommended for legacy or custom templates.

== Why this plugin does NOT send email from your server ==

Sending email from a WordPress server introduces complexity, privacy concerns, and deliverability issues.

AC Print + Email intentionally avoids server-side email so that:
* Emails are sent by the user, not your site
* SPF and DKIM issues are avoided
* User expectations are respected
* Privacy is preserved

If you need server-side email campaigns, this plugin is not designed to replace that functionality.

== Accessibility ==

Accessibility is treated as a first-class feature.

The toolbar supports:
* Full keyboard navigation
* Proper focus management
* ARIA-expanded and ARIA-live announcements
* Screen-reader friendly labels and feedback

== Usage ==

The toolbar can be automatically injected into content, or placed manually using a shortcode: [ac_print_email]


== Screenshots ==

1. Toolbar displayed on a post
2. Email dropdown menu
3. Clean print output
4. Plugin settings page
5. Plugin settings page live preview and shortcode

== Changelog ==

= 1.2.4 =
* Compatibility: Updated class prefix to be plugin-specific (ACPE_) and added safe class guard to satisfy WordPress naming conventions.
* Maintenance: Minor internal housekeeping for standards compliance.

= 1.2.3 =
* Fixed: Auto-injected toolbar could fail to appear on single posts/pages due to global render lock.
* Improved: Removed overly aggressive duplicate-render prevention and replaced it with safer content-level checks.
* Stability: Ensured consistent toolbar output across posts, pages, archives, and shortcode placement.
* Improved: Admin preview now accurately reflects toolbar color settings

= 1.2.2 =
* Added Placement setting (Auto inject vs Shortcode only)
* Prevented duplicate toolbar output in custom and legacy themes
* Improved archive toolbar handling
* Hardened rendering logic for safer theme compatibility
* Minor internal refactoring for clarity and maintainability

= 1.2.1 =
* Improved output sanitization to meet WordPress Plugin Check requirements
* Hardened inline SVG handling and attribute allowlists
* Internal security and standards compliance improvements

= 1.2.0 =
* Added display mode setting (icons, labels, or both)
* Added per-taxonomy controls (categories and tags)
* Added admin preview panel
* Improved accessibility and keyboard navigation

= 1.1.0 =
* Initial public release


== License ==

This plugin is licensed under the GPLv2 or later.
