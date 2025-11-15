<?php
/**
 * Encryption utility for API keys.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/claude
 */

/**
 * Handles encryption and decryption of sensitive data.
 */
class Claude_SEO_Encryption {

    /**
     * Encryption method.
     */
    const METHOD = 'AES-256-CBC';

    /**
     * Encrypt data.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data with IV.
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return '';
        }

        $key = self::get_encryption_key();
        $iv_length = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt($data, self::METHOD, $key, 0, $iv);

        if ($encrypted === false) {
            Claude_SEO_Logger::error('Encryption failed');
            return '';
        }

        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data.
     *
     * @param string $data Encrypted data with IV.
     * @return string|false Decrypted data or false on failure.
     */
    public static function decrypt($data) {
        if (empty($data)) {
            return false;
        }

        $key = self::get_encryption_key();
        $data = base64_decode($data, true);

        if ($data === false) {
            return false;
        }

        $iv_length = openssl_cipher_iv_length(self::METHOD);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        $decrypted = openssl_decrypt($encrypted, self::METHOD, $key, 0, $iv);

        if ($decrypted === false) {
            Claude_SEO_Logger::error('Decryption failed');
            return false;
        }

        return $decrypted;
    }

    /**
     * Get encryption key based on WordPress salts.
     *
     * @return string Encryption key.
     */
    private static function get_encryption_key() {
        if (!defined('LOGGED_IN_SALT')) {
            wp_die('LOGGED_IN_SALT is not defined in wp-config.php');
        }

        return hash('sha256', LOGGED_IN_SALT, true);
    }

    /**
     * Store encrypted API key.
     *
     * @param string $api_key Plain API key.
     * @return bool True on success.
     */
    public static function store_api_key($api_key) {
        $encrypted = self::encrypt($api_key);

        if (empty($encrypted)) {
            return false;
        }

        return update_option('claude_seo_api_key_encrypted', $encrypted, false);
    }

    /**
     * Retrieve and decrypt API key.
     *
     * @return string|false Decrypted API key or false on failure.
     */
    public static function get_api_key() {
        $encrypted = get_option('claude_seo_api_key_encrypted', '');

        if (empty($encrypted)) {
            return false;
        }

        return self::decrypt($encrypted);
    }

    /**
     * Validate API key format.
     *
     * @param string $api_key API key to validate.
     * @return bool True if valid format.
     */
    public static function validate_api_key_format($api_key) {
        // Claude API keys start with 'sk-ant-api03-'
        return (bool) preg_match('/^sk-ant-api03-[a-zA-Z0-9_-]+$/', $api_key);
    }

    /**
     * Delete stored API key.
     *
     * @return bool True on success.
     */
    public static function delete_api_key() {
        return delete_option('claude_seo_api_key_encrypted');
    }
}
