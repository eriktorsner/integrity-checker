<?php
namespace WPChecksum;

class WPRepository
{
    const WPAPI_PLUGIN_URL = "https://api.wordpress.org/plugins/info/1.0/";
    
    private $result;
    
    public function __construct($slug, $type)
    {
		$apiArgs = (object)array(
		    'slug' => $slug,
			'banners' => false,
			'reviews' => false,
			'downloaded' => false,
			'active_installs' => true,
			'locale' => 'en_US',
			'per_page' => 24,
		);
    
        $args = array(
            'action' => $type == 'plugin'?'plugin_information':'theme_information',
            'request' => serialize($apiArgs),
        );
        $response = wp_remote_post(self::WPAPI_PLUGIN_URL, $args);
        $this->result = unserialize($response['body']);
    }
    
    public function __get($name) {
        if (isset($this->result->$name)) {
            return $this->result->$name;
        }

        return null;
    }
    
    public function found()
    {
        return $this->result != null;
    }
}
