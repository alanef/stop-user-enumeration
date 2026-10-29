# PLUGINS_SMALL-12: warn when a display name or author slug gives away a login name

Standalone issue, small. Free plugin, no sponsorship: keep it to one class, two hooks into
existing screens, tests and a changelog line.

## Premise check

- **True.** `wp_insert_user()` sets `display_name` to `user_login` when neither first nor
  last name is given (`wp-includes/user.php:2468-2483`; the nickname is not used), and
  `user_nicename` is `sanitize_title( mb_substr( user_login, 0, 50 ) )` (`:2349`, `:2361`).
- **Not already built.** Nothing in the plugin reads `display_name` or `user_nicename`; the
  only mention is the readme FAQ (readme.txt:61).
- `WP_User_Query` `capability` argument exists since WP 5.9; the plugin requires 6.3.

## Decisions (PLUGINS Manager, 2026-10-29)

1. Dismissal is per administrator, keyed by (user, display or slug). The notice lists, and comes
   back for, only the matches that administrator has not dismissed.
2. The notice shows on the Dashboard, Users, Plugins and the SUE settings page only. The
   settings page box always shows the full list, dismissed or not.

## Design

One new class, `Stop_User_Enumeration\Admin\Login_Exposure` in
`stop-user-enumeration/admin/class-login-exposure.php` (picked up by the existing composer
classmap; `composer dump-autoload` in the plugin directory).

**Audit** (`find()`): `get_users( capability => edit_posts, fields => ID, user_login,
display_name, user_nicename )`. A user is flagged with:

- `display`: `strtolower( display_name ) === strtolower( user_login )`
- `slug`: `strtolower( user_nicename ) === strtolower( sanitize_title( user_login ) )`

Returns `[ user_id => [ 'display' => bool, 'slug' => bool ] ]`, matches only.

**Cache**: option `stop-user-enumeration-login-exposure` (autoload off), computed on first
read when missing. Deleted (so recomputed on next admin read) on `profile_update`,
`user_register`, `deleted_user`, `set_user_role`, `add_user_role`, `remove_user_role`. The
role hooks matter because a subscriber promoted to author becomes exposed. The invalidation
hooks are registered on every request (they are cheap and profile updates can happen on the
front end); the notice only in admin.

**Admin notice**: users with `manage_options`, on the Dashboard, Users and Plugins screens
and the SUE settings page only (not every admin screen, so it does not nag). Lists each
not-yet-dismissed match: the affected user by display name, linked to their profile (`get_edit_user_link()`), with what
is exposed:

- display name: "change Display name publicly as on their profile";
- author URL: advice only. It is made from the login name, cannot be changed on the
  profile screen, and changing it changes the user's author archive URLs, so the plugin
  does not offer to.

**Dismiss**: a nonced link to `admin-post.php?action=sue_dismiss_login_exposure`
(no JavaScript), which stores the current set's keys (`"<id>:display"`, `"<id>:slug"`) in
the dismissing admin's user meta `sue_login_exposure_dismissed` and redirects back. The
notice shows again only when the current set holds a key that admin has not dismissed: a
new author, or a user whose fix was undone.

**Settings page**: a third meta box on the SUE settings page, "Login names on show", with
one line on why it matters and the same list, or "No user who can publish has a display
name or author URL that gives away their login name." Always shown, no dismiss.

**Uninstall**: `Uninstall::uninstall()` also deletes the option and the user meta
(`delete_metadata( 'user', 0, 'sue_login_exposure_dismissed', '', true )`).

**Wiring**: `Core` registers the class through the existing `Loader`, like `Admin_Settings`.

## Copy

New strings (notice, meta box, changelog) go to PLUGINS Manager for review before release.
readme FAQ "My username still seems to be leaked!" gets one sentence pointing at the new
warning.

## Tests (tests/test-login-exposure.php, wp-env PHPUnit, WP_UnitTestCase)

- Default admin (no names) is flagged for display and slug.
- Setting a first name and display name clears `display`; `slug` stays.
- Subscriber with matching names is not flagged; promoting them to author flags them
  (cache invalidated by `set_user_role`).
- Case-insensitive match (login `Admin`, display `admin`).
- Login with characters `sanitize_title` changes (`jo.smith@example.com`) still compares
  against the sanitised slug.
- Cache: computed once; `profile_update` deletes it.
- Notice: renders for an administrator on the dashboard, not for an editor; lists the
  user; after dismiss it does not render; a new author makes it render again.
- Dismiss handler refuses a bad nonce.

## Changelog (changelog.txt, new `= Unreleased =` above `= 1.7.10 =`)

`* Added: a warning, for administrators, when someone who can publish has a display name or
author URL that shows their login name to visitors. WordPress uses the login name as the
display name unless you give a first or last name, and builds the author URL from it, so a
fresh site shows the admin login name in feeds, oEmbed and bylines. The warning links to
each profile to change the display name; the author URL can only be changed outside the
profile screen.`

## Out of scope

Changing `user_nicename` automatically (changes URLs; issue says advice only). Multisite
network admin screens (per-site notice only).

## Done when

Issue criteria: fresh install shows the notice; display name change clears that part;
subscriber-only matches do not trigger; dismiss holds until a new match; changelog entry;
Plugin Check and PHP compatibility CI pass.
