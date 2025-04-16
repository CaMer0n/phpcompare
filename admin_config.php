<?php
require_once("../../class2.php");
if(!getperms("P"))
{
	e107::redirect("index.php");
	exit;
}


// Define classes
class inicompare_adminArea extends e_admin_dispatcher
{

	protected $modes = array(
		'main' => array(
			'controller' => 'inicompare_admin_ui',
			'path'       => null,
			'ui'         => 'inicompare_admin_form_ui',
			'uipath'     => null
		)
	);

	protected $adminMenu = array(
		'main/custom' => array('caption' => 'Compare', 'perm' => 'P'),
		// 'main/prefs' => array('caption' => 'LAN_PREFS', 'perm' => 'P')
	);

	protected $menuTitle = 'PHP INI Compare';
}


class inicompare_admin_ui extends e_admin_ui
{

	protected $pluginTitle = 'PHP INI Compare';
	protected $pluginName  = 'inicompare';

	public function customPage()
	{

		$frm = $this->getUI();

		$text = '';

		// Define php.ini files for PHP 8 versions (confirmed paths)
		$ini_files = array();
		$search_dirs = array(
			'/opt/cpanel/ea-php8*/root/etc/', // EasyApache 4 PHP 8 versions
			'/bin/php/*/'                     // Additional path with subdirectories
		);

		foreach($search_dirs as $dir)
		{
			$files = glob($dir . 'php.ini');
			foreach($files as $file)
			{
				if(is_readable($file) && pathinfo($file, PATHINFO_EXTENSION) === 'ini')
				{
					$ini_files[] = realpath($file);
				}
			}
		}

		// Get current PHP's php.ini
		$current_ini = php_ini_loaded_file();
		if($current_ini)
		{
			$current_ini = realpath($current_ini);
		}

		// Remove current PHP's php.ini from dropdown options
		$ini_files = array_filter($ini_files, function ($file) use ($current_ini)
		{

			return $file !== $current_ini;
		});


		// Prepare dropdown options
		$options = array('' => 'Select PHP Version');
		foreach($ini_files as $file)
		{
			// Determine directory level based on path pattern
			if(strpos($file, '/opt/cpanel/') === 0)
			{
				// Linux EasyApache 4 (e.g., /opt/cpanel/ea-php82/root/etc/php.ini → ea-php82)
				$version_dir = basename(dirname(dirname(dirname($file))));
			}
			else
			{
				// Windows or other (e.g., W:\bin\php\php-8.1.6-nts-Win32-vs16-x64\php.ini → php-8.1.6-nts-Win32-vs16-x64)
				$version_dir = basename(dirname($file));
			}
			// Extract PHP version
			if(preg_match('/^ea-php(\d)(\d+)$/', $version_dir, $matches))
			{
				// EasyApache 4 (e.g., ea-php81 → PHP 8.1)
				$major = $matches[1];
				$minor = $matches[2];
				$version = "PHP $major.$minor";
			}
			elseif(preg_match('/^php-(\d+\.\d+\.\d+)/', $version_dir, $matches))
			{
				// Windows (e.g., php-8.1.6-nts-Win32-vs16-x64 → PHP 8.1.6)
				$version = "PHP {$matches[1]}";
			}
			else
			{
				// Fallback: use version folder name
				$version = "PHP Version ($version_dir)";
			}
			$options[$file] = $version;
		}

		// Form
		$selected_ini = $_POST['ini_file'] ?? null;
		$text .= $frm->open('inicompare_form', 'post', 'admin_config.php');
		$text .= '<table class="table table-bordered">
            <tr>
                <td>Current PHP Version</td><td>' . PHP_VERSION . '</td>
             </tr>
            <tr>
                <td>Select other installed version</td>
                <td>
                ' . $frm->select('ini_file', $options, $selected_ini, ['required' => 1, 'size' => 'xlarge']) . '</td>
            </tr>
            <tr>
             </table>
            <div class="buttons-bar center">
            ' . $frm->submit('submit', 'Compare Files', 'submit', array('class' => 'btn btn-primary')) . '
            </div>
           
        ';
		$text .= $frm->close();

		// Process form submission
		if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ini_file']))
		{
			$selected_ini = trim($_POST['ini_file']);

			// Validate selected file
			$valid_file = false;
			$real_selected = realpath($selected_ini);
			if($real_selected && in_array($real_selected, array_map('realpath', $ini_files)) && is_readable($real_selected))
			{
				$valid_file = true;
			}

			if(!$current_ini)
			{
				$text .= '<div class="alert alert-danger">Error: Could not detect the current PHP INI file.</div>';
			}
			elseif(!$valid_file)
			{
				$text .= '<div class="alert alert-danger">Error: Invalid or unreadable selected INI file.</div>';
			}
			else
			{
				// Load and parse ini files
				$ini1_settings = $this->parseAllIniFiles($current_ini);
				$ini2_settings = $this->parseAllIniFiles($real_selected);

				if($ini1_settings === false || $ini2_settings === false)
				{
					$text .= '<div class="alert alert-danger">Error: Could not parse one or both INI configurations.</div>';
				}
				else
				{
					// Key settings for migration (excluding extensions)
					$key_settings = array(
						'allow_url_fopen',
						'allow_url_include',
						'cgi.fix_pathinfo',
						'date.timezone',
						'default_charset',
						'disable_classes',
						'disable_functions',
						'display_errors',
						'error_log',
						'error_reporting',
						'exec',
						'expose_php',
						'file_uploads',
						'log_errors',
						'max_execution_time',
						'max_input_time',
						'max_input_vars',
						'memory_limit',
						'mysqli.reconnect',
						'open_basedir',
						'passthru',
						'pdo_mysql.cache_size',
						'popen',
						'post_max_size',
						'proc_open',
						'register_globals',
						'session.cookie_httponly',
						'session.cookie_secure',
					//	'session.save_path',
						'shell_exec',
						'system',
						'upload_max_filesize'
					);
					// Comparison table for standard settings
					$text .= '
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Setting</th>
                                            <th style="width:25%">Current PHP (' . htmlspecialchars(basename($current_ini)) . ')</th>
                                            <th style="width:25%">Selected (' . htmlspecialchars(basename($real_selected)) . ')</th>
                                            <th style="width:25%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

					foreach($key_settings as $setting)
					{
						$value1 = isset($ini1_settings[$setting]) ? $ini1_settings[$setting] : '(not set)';
						$value2 = isset($ini2_settings[$setting]) ? $ini2_settings[$setting] : '(not set)';
						$status = ($value1 === $value2) ? '<span class="label label-success">Match</span>' : '<span class="label label-warning">Mismatch</span>';

						$text .= '<tr>
                                    <td>' . htmlspecialchars($setting) . '</td>
                                    <td>' . htmlspecialchars($value1) . '</td>
                                    <td>' . htmlspecialchars($value2) . '</td>
                                    <td>' . $status . '</td>
                                  </tr>';
					}

					$text .= '</tbody></table></div>';

					// Compare extensions
					$ext1 = isset($ini1_settings['extensions']) ? $ini1_settings['extensions'] : array();
					$ext2 = isset($ini2_settings['extensions']) ? $ini2_settings['extensions'] : array();
					$all_extensions = array_unique(array_merge($ext1, $ext2));
					sort($all_extensions);

					// Extensions table
					$text .= '
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Extension</th>
                                            <th style="width:25%" >Current PHP (' . htmlspecialchars(basename($current_ini)) . ')</th>
                                            <th style="width:25%">Selected (' . htmlspecialchars(basename($real_selected)) . ')</th>
                                            <th style="width:25%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

					foreach($all_extensions as $ext)
					{
						$in1 = in_array($ext, $ext1) ? 'Enabled' : 'Disabled';
						$in2 = in_array($ext, $ext2) ? 'Enabled' : 'Disabled';
						$status = ($in1 === $in2) ? '<span class="label label-success">Match</span>' : '<span class="label label-warning">Mismatch</span>';

						$text .= '<tr>
                                    <td>' . htmlspecialchars($ext) . '</td>
                                    <td>' . $in1 . '</td>
                                    <td>' . $in2 . '</td>
                                    <td>' . $status . '</td>
                                  </tr>';
					}

					$text .= '</tbody></table></div>';

					// Compare zend_extensions
					$zend1 = isset($ini1_settings['zend_extensions']) ? $ini1_settings['zend_extensions'] : array();
					$zend2 = isset($ini2_settings['zend_extensions']) ? $ini2_settings['zend_extensions'] : array();
					$all_zend_extensions = array_unique(array_merge($zend1, $zend2));
					sort($all_zend_extensions);

					if(!empty($all_zend_extensions))
					{
						$text .= '<div class="panel panel-default">
                                    <div class="panel-heading">Comparison Results - Zend Extensions</div>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Zend Extension</th>
                                                <th style="width:25%">Current PHP (' . htmlspecialchars(basename($current_ini)) . ')</th>
                                                <th style="width:25%">Selected (' . htmlspecialchars(basename($real_selected)) . ')</th>
                                                <th style="width:25%">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

						foreach($all_zend_extensions as $zend_ext)
						{
							$in1 = in_array($zend_ext, $zend1) ? 'Enabled' : 'Disabled';
							$in2 = in_array($zend_ext, $zend2) ? 'Enabled' : 'Disabled';
							$status = ($in1 === $in2) ? '<span class="label label-success">Match</span>' : '<span class="label label-warning">Mismatch</span>';

							$text .= '<tr>
                                        <td>' . htmlspecialchars($zend_ext) . '</td>
                                        <td>' . $in1 . '</td>
                                        <td>' . $in2 . '</td>
                                        <td>' . $status . '</td>
                                      </tr>';
						}

						$text .= '</tbody></table>';
					}

					// Additional differences (excluding extensions)
					$all_keys = array_unique(array_merge(array_keys($ini1_settings), array_keys($ini2_settings)));
					$text .= '
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Setting</th>
                                            <th style="width:25%">Current PHP (' . htmlspecialchars(basename($current_ini)) . ')</th>
                                            <th style="width:25%">Selected (' . htmlspecialchars(basename($real_selected)) . ')</th>
                                            <th style="width:25%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

					foreach($all_keys as $key)
					{
						if(!in_array($key, $key_settings) && $key !== 'extensions' && $key !== 'zend_extensions')
						{
							$value1 = isset($ini1_settings[$key]) ? $ini1_settings[$key] : '(not set)';
							$value2 = isset($ini2_settings[$key]) ? $ini2_settings[$key] : '(not set)';
							if($value1 !== $value2)
							{
								$text .= '<tr>
                                            <td>' . htmlspecialchars($key) . '</td>
                                            <td>' . htmlspecialchars($value1) . '</td>
                                            <td>' . htmlspecialchars($value2) . '</td>
                                            <td><span class="label label-warning">Mismatch</span></td>
                                          </tr>';
							}
						}
					}

					$text .= '</tbody></table>';
				}
			}
		}

		return $text;
	}

	private function parseAllIniFiles($main_ini)
	{

		if(!is_readable($main_ini))
		{
			return false;
		}

		// Parse main php.ini
		$settings = parse_ini_file($main_ini);
		if($settings === false)
		{
			return false;
		}

		// Initialize extensions arrays
		$settings['extensions'] = array();
		$settings['zend_extensions'] = array();

		// Get php.d directory
		$php_d_dir = dirname($main_ini) . '/php.d/';
		if(is_dir($php_d_dir))
		{
			$ini_files = glob($php_d_dir . '*.ini');
			sort($ini_files); // Ensure consistent order

			foreach($ini_files as $ini_file)
			{
				if(is_readable($ini_file))
				{
					$content = file_get_contents($ini_file);
					if($content !== false)
					{
						// Parse extension and zend_extension directives manually
						preg_match_all('/^\s*(extension|zend_extension)\s*=\s*["\']?([^"\']+)["\']?\s*$/m', $content, $matches, PREG_SET_ORDER);
						foreach($matches as $match)
						{
							$type = $match[1];
							$value = $match[2];
							// Remove .so suffix and path if present
							$value = preg_replace('/\.so$/', '', basename($value));
							if($type === 'extension')
							{
								$settings['extensions'][] = $value;
							}
							elseif($type === 'zend_extension')
							{
								$settings['zend_extensions'][] = $value;
							}
						}
					}
				}
			}
		}

		// Parse main php.ini for extensions
		$content = file_get_contents($main_ini);
		if($content !== false)
		{
			preg_match_all('/^\s*(extension|zend_extension)\s*=\s*["\']?([^"\']+)["\']?\s*$/m', $content, $matches, PREG_SET_ORDER);
			foreach($matches as $match)
			{
				$type = $match[1];
				$value = $match[2];
				$value = preg_replace('/\.so$/', '', basename($value));
				if($type === 'extension')
				{
					$settings['extensions'][] = $value;
				}
				elseif($type === 'zend_extension')
				{
					$settings['zend_extensions'][] = $value;
				}
			}
		}

		// Remove duplicates
		$settings['extensions'] = array_unique($settings['extensions']);
		$settings['zend_extensions'] = array_unique($settings['zend_extensions']);
		sort($settings['extensions']);
		sort($settings['zend_extensions']);

		return $settings;
	}
}


class inicompare_admin_form_ui extends e_admin_form_ui
{

}


// Instantiate admin area
new inicompare_adminArea;

// Load auth.php after classes and instantiation
require_once(e_ADMIN . "auth.php");

e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
?>