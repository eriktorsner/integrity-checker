<?php
namespace integrityChecker\Tests;

require_once __DIR__ . '/Checksum/FolderChecksum.php';

use integrityChecker\BackgroundProcess;
use \WPChecksum\FolderChecksum;

/**
 * Class Settings
 * @package integrityChecker\Tests
 */
class Settings extends BaseTest
{
	/**
	 * @var string
	 */
    public $name = 'settings';


	/**
	 * Start the settings and various things
     *
	 * @param \WP_REST_Request $request
	 */
    public function start($request)
    {
        $this->backgroundProcess->init();
        parent::start($request);
        $this->transientState = array();

	    $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'allowFileEdit'));
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'dbCredentials'));
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'sslLogins'));
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'checkUpdates'));
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'directoryIndex'));
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'checkSalts'));

        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'userEnumeration'));
	    //$bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'weakWPCredentials'));

        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'versionLeak'));
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'checkTablePrefix'));
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'adminUsername'));

        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'shapeResult'), 20);
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'finish'), 99);

        $this->backgroundProcess->process();
    }

	/**
	 * Prepare the output for storage
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
    public function shapeResult($job)
    {
	    $this->transientState = array(
	    	'result' => array(
	    		'checks' => $this->transientState,
		    ),
	    );
    }

	/**
	 * Check the database table name prefix
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function checkTablePrefix($job)
	{
		global $table_prefix;

		$this->transientState['checkTablePrefix'] = array(
			'name' => __('Table prefix', 'integrity-checker'),
			'slug' => 'checkTablePrefix',
			'type' => 'obscurity',
			'result' => __('Your table prefix is: ', 'integrity-checker') . $table_prefix ,
			'acceptable' => ($table_prefix !== 'wp_'),
			'description' => __(
				'Most SQL-injection attacks on WordPress assumes that the site is using the default wp_ prefix for '.
				'all tables. Changing to a non-standard table prefix might protect your site from some SQL-injection '.
				'attacks.',
				'integrity-checker'
			),
		);
	}

	/**
	 * Check the default table prefix
	 *
	 * @param object $job Job parameters from the calling BackgroundProcess object
	 */
    public function checkSalts($job)
    {
		$salts = array(
			'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
			'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT',
		);

	    $result = array();
	    foreach ($salts as $salt) {
		    $exists = defined($salt);
		    $entropy = $exists ? $this->entropy(constant($salt)) : 0;
		    if (!$exists) {
			    $result[] = $salt . __(" isn't defined", 'integrity-checker');
		    } elseif ($entropy < 4.2) {
			    $result[] = $salt . __(" exists but have too low entropy", 'integrity-checker');
		    }
	    }

	    if (count($result) == 0) {
		    $strResult = __('All expected WordPress salts exists and have high entropy', 'integrity-checker');
	    } else {
		    $strResult = join(',', $result);
	    }

	    $this->transientState['checkSalts'] = array(
	    	'name' => __('Unique keys and salts', 'integrity-checker'),
		    'slug' => 'checkSalts',
        	'type' => 'severe',
            'result' => $strResult,
            'acceptable' => count($result) == 0,
            'description' => __(
                'WordPress Security Keys and salts is a set of random values that improve encryption of information ' .
                'stored in the user’s cookies among other things. Without proper random keys in place the risk of '.
                'an attacker guessing session keys and other sensitive secrets increase',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Check the admin user name
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function adminUsername($job)
    {
        $exists = username_exists('admin');

	    $this->transientState['adminUsername'] = array(
		    'name' => __('Admin user', 'integrity-checker'),
		    'slug' => 'adminUsername',
	        'type' => 'obscurity',
            'acceptable' => (!$exists),
            'result' => __('Username admin ', 'integrity-checker') .
                        ($exists ? __('Exists.','integrity-checker') : __('Does not exist.', 'integrity-checker')),
            'description' => __(
                'Several brute force attacks on WordPress sites assumes that the default user has user name "admin". '.
                'An easy way to avoid getting hacked is to rename the administrative user account to another user ' .
                'name.',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Is file editing allowed from within the site
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function allowFileEdit($job)
    {
        $allowed = (!defined('DISALLOW_FILE_EDIT') || !DISALLOW_FILE_EDIT);

	    $this->transientState['allowFileEdit'] = array(
		    'name' => __('Allow file edit', 'integrity-checker'),
		    'slug' => 'allowFileEdit',
		    'type' => 'limitation',
            'acceptable' => !$allowed,
            'result' => ($allowed? __('File editing is allowed', 'integrity-checker'):
                                __('File editing is not allowed', 'integrity-checker')),
            'description' => __(
                'The WordPress Dashboard by default allows administrators to edit PHP files, such as plugin and theme '.
                'files. This is a convenient tool for an attacker that has managed to guess the credentials for an  ' .
                'admin account. Adding the line define(\'DISALLOW_FILE_EDIT\', true); to wp-config.php disables the '.
                'editor and reduces risk',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Check db credentals
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function dbCredentials($job)
    {
        $acceptable = true;
        $value = array();
        if (DB_PASSWORD == DB_USER || DB_PASSWORD == DB_NAME) {
            $acceptable = false;
            $value[] = __('DB password easy to guess', 'integrity-checker');
        }

        if (strlen(DB_PASSWORD) < 10) {
            $acceptable = false;
            $value[] = __('DB password is shorter than 10 characters', 'integrity-checker');
        }

        if (!preg_match("#[0-9]+#", DB_PASSWORD) ) {
            $acceptable = false;
            $value[] = __('DB password does not contain numbers', 'integrity-checker');
        }

        if (!preg_match("#[a-z]+#", DB_PASSWORD) ) {
            $acceptable = false;
            $value[] = __('DB password does not contain lowercase letters', 'integrity-checker');
        }

        if (!preg_match("#[A-Z]+#", DB_PASSWORD) ) {
            $acceptable = false;
            $value[] = __('DB password does not contain capitalized letters', 'integrity-checker');
        }

        if (!preg_match("#\W+#", DB_PASSWORD) ) {
            $acceptable = false;
            $value[] = __('DB password does not contain symbols', 'integrity-checker');
        }

	    if (in_array(DB_USER, array('admin', 'root', 'wordpress', 'wp', 'administrator'))) {
		    $acceptable = false;
		    $value[] = __('DB user is too easy to guess.', 'integrity-checker');
	    }

        if (DB_PASSWORD == DB_NAME) {
            $acceptable = false;
            $value[] = __('DB password is far too easy to guess.', 'integrity-checker');
        }

        $this->transientState['dbCredentials'] = array(
	        'name' => __('Database credentials', 'integrity-checker'),
	        'slug' => 'dbCredentials',
	        'type' => 'severe',
            'acceptable' => $acceptable,
            'result' => join(', ', $value),
            'description' => __(
                'The database password should be as strong as possible. '.
                'At least 10 characters, contain letters numbers and symbols. '.
                'Also, avoid accessing the database with user root',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Check if this installation requires ssl logins
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function sslLogins($job)
    {
        $ssl = (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN);

	    $this->transientState['sslLogins'] = array(
		    'name' => __('SSL Logins', 'integrity-checker'),
		    'slug' => 'sslLogins',
		    'type' => 'severe',
            'acceptable' => $ssl,
            'result' => $ssl?
                __('SSL Logins are enabled', 'integrity-checker'):
                __('SSL Logins are not enabled', 'integrity-checker'),
            'description' => __(
                'By accessing the WordPress admin area over a non-encrypted (http) connection, the risk of leaking '.
                'passwords and other sensitive information increases dramatically. You will increase security by '.
                'by enforcing ssl (https) logins or even better, enable ssl on the entire site.',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Does this installation reveal it's core version
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function versionLeak($job)
    {
        global $wp_version;
        $generator = strpos(
            apply_filters('the_generator', get_the_generator('html'), 'html'),
            $wp_version
        );

        $readMe = file_exists(ABSPATH . '/readme.html');

	    $this->transientState['versionLeak'] = array(
		    'name' => __('WordPress version leak', 'integrity-checker'),
		    'slug' => 'versionLeak',
		    'type' => 'obscurity',
            'acceptable' => ($generator === false && !$readMe),
            'result' => ($generator === false && !$readMe)?
                __('You are not showing your WordPress version', 'integrity-checker'):
                __('You are showing your WordPress version.', 'integrity-checker'),
            'description' => __(
                'By default, WordPress shows its version via a tag on each page and a publicly available readme file '.
                'that comes with each version of WordPress. By making it easy to determine what version of WordPress '.
                'You have, you make it easier for attackers to target their attacks',
                'integrity-checker'
            ),
        );

        if ($generator !== false) {
            $this->transientState['versionLeak']['result'] .=
                ' The generator tag contains the current version.';
        }

        if ($readMe) {
            $this->transientState['versionLeak']['result'] .=
                ' The readme.html file exists in the root folder.';
        }
    }

	/**
	 * Check if any plugins or themes needs updating
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function checkUpdates($job)
    {
	    require_once ABSPATH . 'wp-admin/includes/update.php';
	    $this->impersonateAdmin();

        $updates = wp_get_update_data();
        $count = $updates['counts']['total'];

	    $this->transientState['checkUpdates'] = array(
		    'name' => __('Version updates', 'integrity-checker'),
		    'slug' => 'checkUpdates',
		    'type' => 'severe',
            'acceptable' => ($count == 0),
            'result' => sprintf(esc_html__('There are %d items to update', 'integrity-checker'), $count),
            'description' => __(
                'Keeping WordPress and all plugins and themes up to date is fundamental for security',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Is it possible to browse folders via http/https
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function directoryIndex($job)
    {
        $url = plugins_url('src/', INTEGRITY_CHECKER_ROOT . '/integrity-checker.php');
        $response = @wp_remote_get($url);
        $acceptable = true;
        if (is_array($response) && isset($response['body'])) {
            if (substr_count($response['body'], 'integrity-checker.php') > 0) {
                $acceptable = false;
            }
        }

        $this->transientState['directoryIndex'] = array(
	        'name' => __('Directory index', 'integrity-checker'),
	        'slug' => 'directoryIndex',
	        'type'       => 'severe',
            'acceptable' => $acceptable,
            'result'     => $acceptable?
                __('Directory Index does not seem to be enabled', 'integrity-checker'):
                __('Directory Index is enabled', 'integrity-checker'),
            'description' => __(
                'Directory Index allows outsiders to list files in each folder that does not have an index.php file '.
                'This could make your site vulnerable to attacks simply by revealing too much information.',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Is it possible to enumerate users from the outside
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function userEnumeration($job)
    {
        $acceptable = false;
        $users = get_users('role=administrator');
        $user = $users[0];
        $url = get_home_url() . '/?author=' . $user->ID;
        $response = @wp_remote_get($url);
        if (!is_wp_error($response)) {
            $classAuthorSlug = substr_count($response['body'], 'author-' . $user->user_login);
            $classAuthorId = substr_count($response['body'], 'author-' . $user->ID);
            $acceptable = ($classAuthorSlug == 0) && ($classAuthorId == 0);
        }

        $this->transientState['userEnumeration'] = array(
	        'name' => __('User enumeration', 'integrity-checker'),
	        'slug' => 'userEnumeration',
	        'type'       => 'severe',
            'acceptable' => $acceptable,
            'result'     => $acceptable?
                __('Your site does not allow user enumeration', 'integrity-checker'):
                __('Your site allow user enumeration', 'integrity-checker'),
            'description' => __(
                'User enumeration is a technique that can allow attackers to figure out the user names in use on your '.
                'site. That makes brute force attacks easier since the attacker doesn\'t have to guess the names. '.
                'More users, regardless of level, increases the likelihood that at least one has an easy to guess ' .
                'password',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Look for backup files still on the server
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function backupFiles($job)
    {
        $value = array();
	    $fChecksums = new FolderChecksum(ABSPATH);
	    $fChecksums->calcHash = false;
	    $files = $fChecksums->scan();

	    foreach ($files->checksums as $file => $data) {
		    if (strpos($file, '~') !== false) {
			    $value[] = $file;
		    }
		    if (strpos($file, '.sql') !== false) {
			    $value[] = $file;
		    }
		    if (strpos($file, '.sql') !== false) {
			    $value[] = $file;
		    }

	    }

        $acceptable = count($value) == 0;

	    $this->transientState['backupFiles'] = array(
		    'name' => __('Backup files on server', 'integrity-checker'),
		    'slug' => 'backupFiles',
		    'type'       => 'severe',
            'acceptable' => $acceptable,
            'result'     => $acceptable?
                __('No suspicious files found', 'integrity-checker'):
                __('The following files look suspicious: ', 'integrity-checker') . ' ' . join(', ', $value),
            'description' => __(
                'Keeping backups of the entire site, database dumps and temporary files on your server may expose '.
                'sensible information like passwords, database content or code to attackers. This is especially '.
                'dangerous if Directory Indexing is allowed on the server',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Scan for dangerous code
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function dangerousCode($job)
    {
        $valuePhpInfo = array();
        $valueEval = array();
        foreach(glob(trailingslashit(ABSPATH) . '*.php') as $file) {
            $content = @file_get_contents($file);
            if ($content) {
                if(substr($file, 0, strlen(ABSPATH)) === ABSPATH) {
                    $fileName = substr($file, strlen(ABSPATH));
                }
                $phpInfo = preg_match('/\bphpinfo\s*\(/i', $content);
                if ($phpInfo) {
                    $valuePhpInfo[] = $fileName;
                }
                $eval = preg_match('/\beval\s*\(/i', $content);
                if ($eval) {
                    $valueEval[] = $fileName;
                }
            }
        }

        $acceptable = count($valueEval) == 0 && count($valuePhpInfo) == 0;

	    $this->transientState['dangerousCode'] = array(
		    'name' => __('Dangerous code', 'integrity-checker'),
		    'slug' => 'dangerousCode',
            'acceptable' => $acceptable,
            'result' => $acceptable?
                __('No suspicious files found', 'integrity-checker'):
                __('The following files look suspicious: ', 'integrity-checker') . '<br>' .
                count($valuePhpInfo)==0?'':
                    __('PHP files with phpinfo: ', 'integrity-checker') . ' ' . join(', ', $valuePhpInfo) . '<br>' .
                count($valueEval)==0?'':
                    __('PHP files with eval: ', 'integrity-checker') . ' ' . join(', ', $valueEval),
            'description' => __(
                'Keeping backups of the entire site, database dumps and temporary files on your server may expose '.
                'sensible information like passwords and database content to attackers. This is especially dangerous '.
                'if Directory Indexing is allowed on the server',
                'integrity-checker'
            ),
        );
    }

	/**
	 * Scan for weak user credentials
	 *
     * @param object $job Job parameters from the calling BackgroundProcess object
	 */
	public function weakWPCredentials($job)
    {
	    $weakPasswords = ['password', '123456', '12345678', '1234', 'qwerty', '12345', 'dragon', 'secret', 'dragon',
	        'pussy', '696969', 'mustang', 'abc123'];

	    $users = get_users();

	    foreach ($users as $user) {
		    $toCheck = $weakPasswords;
		    $toCheck[] = $user->data->user_login;
		    $toCheck[] = $user->data->user_login . '1';
		    $toCheck[] = $user->data->user_login . '123';
		    $toCheck[] = $user->data->user_login . '1234';
		    $toCheck[] = $user->data->user_login . '!';
		    $toCheck[] = $user->data->user_login . '1!';
		    $toCheck[] = $user->data->user_login . '123!';

		    foreach ($weakPasswords as $weakPassword) {
			    $check = wp_check_password($weakPassword, $user->data->user_pass);
			    if ($check) {
				    //$weak = true;
			    }
			}
	    }

	    $this->transientState['weakWPCredentials'] = array();
    }

	/**
	 * Measure the entropy of a string
	 *
	 * @param $string
	 *
	 * @return float|int
	 */
    private function entropy($string)
    {
        $h=0;
        $size = strlen($string);
        foreach (count_chars($string, 1) as $v) {
            $p = $v/$size;
            $h -= $p*log($p)/log(2);
        }
		return $h;
	}
}