# Timberland :evergreen_tree:

Timberland is an opinionated WordPress theme using [Timber](https://www.upstatement.com/timber/), [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/), [Vite](https://vitejs.dev/), [Tailwind](https://tailwindcss.com/) and [Alpine.js](https://github.com/alpinejs/alpine).

This repository is Barry Tickle's maintained Timberland boilerplate for starting new WordPress themes. It is based on the original Timberland project by Chris Earls.

As of version 1.0, Timberland uses the WordPress block editor to visually edit the site. This is made possible by the [ACF Blocks feature](https://www.advancedcustomfields.com/resources/blocks/).

## Installation

1. Download the zip for this theme (or clone it) and move it to `wp-content/themes` in your WordPress installation.
2. Run `composer install` in the theme directory.
3. Run `npm install` in the theme directory.
4. Activate the theme in Appearance > Themes.
5. Make sure you have installed [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/).

## Development

Timberland builds your CSS and JS files using Vite. This allows you to use modern JavaScript and CSS features.

To get started:
1. Run `npm run build` to generate assets used by the frontend and admin block editor.
2. Run `npm run dev` to start the Vite dev server.

### Live Reload

Live reload is enabled by default.

### Versioning

To assist with long-term caching, file hashing (for example `main-e1457bfd.js`) is enabled by default.

### Tailwind safelist

Add any Tailwind classes that are created dynamically, or cannot be found by Tailwind's normal content scan, to `safelist.txt`. The Tailwind config reads this file automatically.

### WordPress frontend assets

The boilerplate keeps its existing lean frontend behaviour by removing several WordPress core styles and jQuery by default. If a project needs those assets, disable the optimisation from project code:

```php
add_filter( 'timberland_strip_wordpress_assets', '__return_false' );
```

### Automated checks

GitHub Actions validates Composer configuration, checks PHP syntax, installs frontend dependencies and runs the production Vite build on pushes to `main` and on pull requests.

## Production

When you're ready for production, run `npm run build` from the theme directory. You can test production assets in development by setting the `vite` → `environment` property to `production` in `config.json`.

If you're developing locally and moving files to your production environment, only the `theme` and `vendor` directories are needed inside the `timberland` theme directory. The theme directory structure should look like the following:

```
  timberland/
  ├── theme/
  ├── vendor/
```

## Blocks

A block is a self-contained page section and includes its own template, script, style, functions and `block.json` files.

```
  example/
  ├── block.json
  ├── functions.php
  ├── index.twig
  ├── script.js
  ├── style.css
```

To create a new block, create a directory in `theme/blocks`. Add your `index.twig` and `block.json` files and it is ready to be used with the WordPress block editor. You can optionally add `style.css`, `script.js` and `functions.php` files. An example block is provided for reference. Add editable fields by creating a new ACF field group and setting the location rule to your new block.

ACF blocks are configured globally to use Block API v3 with automatic inline editing enabled. Supported fields can therefore be edited directly in the block preview without repeating the setting in every `block.json` file. Complex field types such as repeaters may still use ACF's expanded editing interface.

### Accessing Fields

You access your block's fields in the `index.twig` file by using the `fields` variable. The example below shows how to display a block's field. We'll use `heading` as the example ACF field name, but it can be whatever name you give your field.

`{{ fields.heading }}`

Here's an example of how to loop through a repeater field where `features` is the ACF field name and the repeater has a heading field.

```
{% for feature in fields.features %}
{{ feature.heading }}
{% endfor %}
```

## Directory Structure

`theme/` contains all of the WordPress core template files.

`theme/acf-json/` contains your Advanced Custom Fields JSON files. The boilerplate starts with this directory empty apart from `.keep`; project-specific field groups are then created by ACF's Local JSON feature.

`theme/assets/` contains fonts, images, styles and scripts.

`theme/blocks/` contains all of your site's blocks. These blocks are available to use on any page via the block editor. Each block can have its own template, script and style files.

`theme/patterns/` contains block patterns. Block Patterns are collections of predefined blocks that you can insert into pages and posts and then customise with your own content.

`theme/views/` contains Twig templates. These broadly correspond to the PHP files in the WordPress template hierarchy. At the end of each PHP template, a `Timber::render()` call selects the Twig file where that data or `$context` is used.

## License

MIT © Chris Earls. Maintained boilerplate modifications by Barry Tickle.
