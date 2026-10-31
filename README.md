# Comment Likes

A dependency-free WordPress plugin that adds an AJAX "Like" button to approved comments — no page reload, no jQuery.

## Features

- Like button appended to each approved comment with a live count
- One like per visitor: guarded by a 1-year cookie **and** a salted hash of the visitor IP stored in commentmeta (raw IPs are never stored)
- Voter list capped at 5,000 entries per comment so commentmeta stays bounded
- Works for logged-in and logged-out visitors
- Accessible: `aria-pressed` state updates on like

## Files

- `wp-comment-likes.php` — button rendering, AJAX handler, meta storage
- `comment-likes.js` — vanilla JS fetch handler
- `uninstall.php` — removes `_wcl_likes` / `_wcl_voters` commentmeta on uninstall

## Installation

1. Copy this folder to `wp-content/plugins/wp-comment-likes/`.
2. Activate **Comment Likes** in **Plugins**. No settings needed.

## License

MIT
