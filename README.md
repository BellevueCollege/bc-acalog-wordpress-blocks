# Bellevue College Acalog WordPress Blocks

This plugin includes WordPress Block Editor Blocks that pull data from Modern Campus Acalog.
This plugin leverages the Acalog API. This plugin was not developed by Acalog or Modern Campus, and should be considered unofficial. 

## Available Blocks

Currently only one block is available, called Program. This block will display a link to the selected program, and the link will automatically update as new catalogs are published. 

## Configuration
1. Download the plugin
2. Compile the plugin by running `npm install` and `npm run build` from within the plugin
3. Define configuration constants in your `wp-config.php` file. The block will not become available until these are present. 

```php
define('ACALOG_BASE_URL', 'https://catalog.bellevuecollege.edu'); // <- Your Public URL Here
define('ACALOG_BASE_API_URL', 'https://bellevuecollege.apis.acalog.com/v1'); // <- Your API URL Here
define('ACALOG_API_KEY', 'YOUR KEY HERE!');
```


## Project Structure

All blocks are in folders under the `src` directory. Each block has its own folder, and each folder contains the block's source code.

NPM is used for package management. To install all dependencies, run the following command from the root directory:

```bash
npm install
```

This project uses [wordpress-scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) compile/transpile code and build blocks. To watch for changes and build blocks, run the following commands from the root directory:

```bash
# Watch for changes:
npm start

# Build all blocks:
npm run build
```


## Block Structure

### Registration
Each block is registered in the main `plugin.php` file. You can register a new block by adding the folder name to the array of registered blocks.

### Block Structure

Each block has a folder within `src/` that contains the block's source code, and a folder within `build/` that contains the block's build artifacts (not committed to the repository).

Within the block folder, there is a `block.json` file that contains the block's metadata, and defines its attributes. There is also an `index.js` file that contains the block's implementation. This draws in the other .js files in the `src/` folder.

There are also two Sass files: `style.scss` and `editor.scss`. These are the styles for the block in the front-end and the editor, respectively.

Any PHP files are included in the Source file.