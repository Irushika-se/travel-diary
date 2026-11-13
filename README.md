# Travel Diary

A responsive Sri Lanka travel blog built with WordPress and the free Astra theme. It has a photo-led Home page, About and Contact pages, six destination guides in three categories (Beaches, Mountains, Culture) and a working contact form. No page builder and no third-party plugins: all the custom work is in one small plugin and a few CSS files.





## Features
- Responsive layout for desktop, tablet and phone
- Home page with hero, category tiles, latest stories (updates automatically) and call-to-action
- Six destination guides with featured images, reading time and related stories
- About page and Contact page with photo banners
- Contact form with nonce check, spam trap and sanitised input; messages are saved in the WordPress admin under "Messages"
- Comments turned off on posts, scroll-to-top button, clean footer

## Tech
WordPress, Astra (free), PHP (custom plugin, shortcodes, custom post type, activation hook), CSS, WAMP (local server).

## What is in this repo
Only my own work is stored here, not the full WordPress install.

| Path | Content |
| --- | --- |
| `plugin/travel-diary-setup/` | Custom plugin: creates pages, posts, categories, menu and styling, plus the contact form and a latest-posts shortcode |
| `plugin/travel-diary-setup/css/` | Custom styling (`travel-diary.css`, `-v2.css` to `-v8.css`) |
| `plugin/travel-diary-setup/images/`, `assets/` | Featured images |
| `docs/screenshots/` | Screenshots of the finished site |

## Run it locally
1. Install WordPress with WAMP (for example in `C:\wamp64\www\travel`, database `travel_db`).
2. Install and activate the Astra theme.
3. Zip the folder `plugin/travel-diary-setup`, then Plugins > Add New > Upload Plugin, and activate it.
4. Settings > Permalinks > Post name > Save.

## What I learned
Posts, categories and featured images in WordPress; writing a plugin with shortcodes, a custom post type and an activation hook; responsive CSS; and using Git and GitHub safely (secrets such as `wp-config.php` are never committed).


