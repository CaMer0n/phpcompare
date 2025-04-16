# e107 PHP Compare Plugin

The `php comparison` plugin for e107 allows administrators to compare PHP configuration (`php.ini`) files across different PHP versions installed on a cPanel/WHM or Windows server. It helps ensure compatibility during PHP version migrations by identifying differences in settings and extensions, such as `memory_limit`, `curl`, `exif`, and more.

## Features

- **Dynamic PHP Version Detection**: Scans for `php.ini` files in cPanel/WHM EasyApache 4 paths (e.g., `/opt/cpanel/ea-php8*/root/etc/php.ini`) and Windows paths (e.g., `W:\bin\php\php-8.1.6-*\php.ini`).
- **User-Friendly Dropdown**: Lists available PHP versions (e.g., “PHP 8.1”, “PHP 8.4”) excluding the current PHP version, with clean labels for easy selection.
- **Comprehensive Comparison**:
  - Compares standard settings (e.g., `memory_limit`, `max_execution_time`, `open_basedir`).
  - Lists all PHP extensions (e.g., `curl`, `exif`, `zip`) and Zend extensions (e.g., `opcache`) as “Enabled” or “Disabled”.
  - Highlights additional setting differences.
- **Migration Support**: Focuses on settings critical for PHP upgrades, helping prevent issues like missing extensions or incompatible configurations.
- **Secure and Lightweight**: Validates file paths, requires admin permissions, and uses e107’s form handlers for a streamlined UI.

## Requirements

- e107 CMS v2.x
- PHP 7.4 or higher
- Read access to `php.ini` files and `php.d/*.ini` files (e.g., `/opt/cpanel/ea-php81/root/etc/php.ini`, `W:\bin\php\php-8.1.6-*\php.ini`)
- cPanel/WHM with EasyApache 4 (for Linux) or a Windows server with multiple PHP versions

## Installation

1. **Download the Plugin**:
   - Clone this repository or download the ZIP file.
   - Extract to your e107 plugins directory: `/path/to/e107/e107_plugins/phpcompare/`.

2. **Set Permissions** (Linux):
   ```bash
   chown -R www-data:www-data /path/to/e107/e107_plugins/phpcompare
   chmod -R 644 /path/to/e107/e107_plugins/phpcompare/*
   chmod 755 /path/to/e107/e107_plugins/phpcompare
   ```
   Replace `www-data` with your web server user (e.g., `apache`).

3. **Install via e107 Admin**:
   - Log in to your e107 admin panel.
   - Navigate to **System** > **Plugin Manager**.
   - Scan for new plugins; `phpcompare` should appear.
   - Click **Install**.

4. **Verify File Permissions**:
   - Ensure `php.ini` and `php.d/*.ini` files are readable:
     ```bash
     ls -l /opt/cpanel/ea-php81/root/etc/php.ini
     chmod 644 /opt/cpanel/ea-php81/root/etc/php.ini
     chmod 644 /opt/cpanel/ea-php81/root/etc/php.d/*.ini
     ```
     On Windows, ensure the web server user has read access to `W:\bin\php\*\php.ini`.

## Usage

1. **Access the Plugin**:
   - Go to **Admin** > **PHP INI Compare** (labeled “Compare” in the e107 admin menu).

2. **Select a PHP Version**:
   - The form displays the current PHP version (e.g., PHP 8.1).
   - Choose another PHP version from the dropdown (e.g., “PHP 8.2”, “PHP 8.4”).
   - Click **Compare Files**.

3. **View Results**:
   - **Standard Settings**: Table comparing settings like `memory_limit`, `open_basedir`, etc.
   - **Extensions**: Table listing extensions (e.g., `curl`, `exif`, `zip`) as “Enabled” or “Disabled”.
   - **Zend Extensions**: Table for extensions like `opcache` (if present).
   - **Other Settings**: Table for additional differences.
   - Matches are marked green (“Match”), mismatches yellow (“Mismatch”).

## Configuration

No additional configuration is required after installation. The plugin automatically detects `php.ini` files in:
- Linux: `/opt/cpanel/ea-php8*/root/etc/`, `/bin/php/*/`
- Windows: Paths like `W:\bin\php\php-X.Y.Z-*`

To add custom paths:
1. Edit `admin_config.php`.
2. Modify the `$search_dirs` array:
   ```php
   $search_dirs = array(
       '/opt/cpanel/ea-php8*/root/etc/',
       '/bin/php/*/',
       '/your/custom/path/*/'
   );
   ```

## Troubleshooting

- **Dropdown Shows “PHP Version (etc)” or “PHP Version (bin)”**:
  - Ensure `php.ini` files are in expected paths.
  - Check file readability:
    ```bash
    ls -l /opt/cpanel/ea-php81/root/etc/php.ini
    ```
  - Verify the regex in `admin_config.php` matches your folder names.

- **No Extensions Listed**:
  - Confirm `php.d/*.ini` files exist and are readable:
    ```bash
    ls -l /opt/cpanel/ea-php81/root/etc/php.d/*.ini
    ```

- **Error: Could Not Detect Current PHP INI**:
  - Ensure `php_ini_loaded_file()` returns a valid path:
    ```bash
    php -i | grep php.ini
    ```

## Contributing

Contributions are welcome! To contribute:
1. Fork the repository.
2. Create a feature branch (`git checkout -b feature-name`).
3. Commit changes (`git commit -m 'Add feature'`).
4. Push to the branch (`git push origin feature-name`).
5. Open a Pull Request.

Please include tests and update this README if needed.

## License

This plugin is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Credits

Developed for e107 CMS, with thanks to the e107 community for feedback and testing.

---

⭐ If you find this plugin useful, please star the repository on GitHub!