<?php

namespace CodeAuth;

use \Exception;

class CodeAuth {
    private static $Endpoint;
    private static $ProjectID;
    private static $UseCache;
    private static $CacheDuration;
    private static $CacheSession = [];
    private static $CacheTimestamp;
    private static $HasInitialized = false;

    /**
     * @summary Starts CodeAuth
     * @param string $project_endpoint
     * @param string $project_id
     * @param bool $use_cache
     * @param int $cache_duration (Seconds)
     * @throws Exception
     */
    public static function Initialize($project_endpoint, $project_id, $use_cache = true, $cache_duration = 5) 
    {
        if (self::$HasInitialized) {
            throw new Exception('CodeAuth has already been Initialized.');
        }
        self::$HasInitialized = true;

        self::$Endpoint = $project_endpoint;
        self::$ProjectID = $project_id;
        self::$UseCache = $use_cache;
        // Convert to milliseconds to match JS logic
        self::$CacheDuration = $cache_duration * 1000;
        self::$CacheSession = [];
        self::$CacheTimestamp = microtime(true) * 1000;
    }
    
    /**
     * @summary Begins the sign in or register flow by sending the user a one time code via email.
     * @param string $email
     * @return array
     */
    public static function SignInEmail($email) 
    {
        if (!self::$HasInitialized) throw new Exception('CodeAuth has not been initialized.');

        return self::request(
            "https://" . self::$Endpoint . "/signin/email", 
            [
                'project_id' => self::$ProjectID,
                'email' => $email,
            ]
        );
    }

    /**
     * @summary Checks if the one time code matches in order to create a session token.
     * @param string $email
     * @param string $code
     * @return array
     */
    public static function SignInEmailVerify($email, $code) 
    {
        if (!self::$HasInitialized) throw new Exception('CodeAuth has not been initialized.');

        return self::request(
            "https://" . self::$Endpoint . "/signin/emailverify", 
            [
                'project_id' => self::$ProjectID,
                'email' => $email,
                'code' => $code
            ]
        );
    }

    /**
     * @summary Begins the sign in or register flow by allowing users to sign in through a social OAuth2 link.
     * @param string $social_type
     * @return array
     */
    public static function SignInSocial($social_type) 
    {
        if (!self::$HasInitialized) throw new Exception('CodeAuth has not been initialized.');

        return self::request(
            "https://" . self::$Endpoint . "/signin/social", 
            [
                'project_id' => self::$ProjectID,
                'social_type' => $social_type
            ]
        );
    }

    /**
     * @summary Checks the authorization code given by the social media company.
     * @param string $social_type
     * @param string $authorization_code
     * @return array
     */
    public static function SignInSocialVerify($social_type, $authorization_code) 
    {
        if (!self::$HasInitialized) throw new Exception('CodeAuth has not been initialized.');

        return self::request(
            "https://" . self::$Endpoint . "/signin/socialverify", 
            [
                'project_id' => self::$ProjectID,
                'social_type' => $social_type,
                'authorization_code' => $authorization_code
            ]
        );
    }

    /**
     * @summary Gets the information associated with a session token.
     * @param string $session_token
     * @return array
     */
    public static function SessionInfo($session_token) 
    {
        if (!self::$HasInitialized) throw new Exception('CodeAuth has not been initialized.');

        // check if caching is enabled
        if (self::$UseCache)
        {
            $now = microtime(true) * 1000;
            
            // first check if cache expired
            if (self::$CacheTimestamp + self::$CacheDuration > $now) 
            {
                if (isset(self::$CacheSession[$session_token])) {
                    return self::$CacheSession[$session_token];
                }
            }
            else
            {
                self::$CacheTimestamp = $now;
                self::$CacheSession = [];
            }
        }

        $result = self::request(
            "https://" . self::$Endpoint . "/session/info", 
            [
                'project_id' => self::$ProjectID,
                'session_token' => $session_token
            ]
        );

        // save to cache if enabled
        if (self::$UseCache) {
            self::$CacheSession[$session_token] = $result;
        }

        return $result;
    }

    /**
     * @summary Create a new session token using existing session token.
     * @param string $session_token
     * @return array
     */
    public static function SessionRefresh($session_token) 
    {        
        if (!self::$HasInitialized) throw new Exception('CodeAuth has not been initialized.');

        $result = self::request(
            "https://" . self::$Endpoint . "/session/refresh", 
            [
                'project_id' => self::$ProjectID,
                'session_token' => $session_token
            ]
        );

        // check if caching is enabled
        // Note: Corrected logic from JS source (assumed == 'no_error' for success)
        if (self::$UseCache && isset($result['error']) && $result['error'] == 'no_error')
        {
            $now = microtime(true) * 1000;

            // if cache expired, delete everything
            if (self::$CacheTimestamp + self::$CacheDuration < $now) 
            {
                self::$CacheTimestamp = $now;
                self::$CacheSession = [];
            }
            // if not, delete the old cache and add the new one
            else
            {
                if (isset(self::$CacheSession[$session_token])) {
                    unset(self::$CacheSession[$session_token]);
                }
                
                self::$CacheSession[$result['session_token']] = [
                    'email' => $result['email'], 
                    'expiration' => $result['expiration'], 
                    'refresh_left' => $result['refresh_left']
                ];
            }
        }

        return $result;
    }

    /**
     * @summary Invalidate a session token.
     * @param string $session_token
     * @param string $invalidate_type
     * @return array
     */
    public static function SessionInvalidate($session_token, $invalidate_type) 
    {
        if (!self::$HasInitialized) throw new Exception('CodeAuth has not been initialized.');
        
        $result = self::request(
            "https://" . self::$Endpoint . "/session/invalidate", 
            [
                'project_id' => self::$ProjectID,
                'session_token' => $session_token,
                'invalidate_type' => $invalidate_type
            ]
        );

        // check if caching is enabled
        // Note: Corrected logic from JS source (assumed == 'no_error' for success)
        if (self::$UseCache && isset($result['error']) && $result['error'] == 'no_error')
        {
            $now = microtime(true) * 1000;

            // if cache expired, delete everything
            if (self::$CacheTimestamp + self::$CacheDuration < $now) 
            {
                self::$CacheTimestamp = $now;
                self::$CacheSession = [];
            }
            // if not, delete the cache
            else { 
                if (isset(self::$CacheSession[$session_token])) {
                    unset(self::$CacheSession[$session_token]); 
                }
            }
        }

        return $result;
    }

    private static function request($url, $body) {
        // Initialize cURL
        $ch = curl_init($url);
        $dataString = json_encode($body);

        // Set Options
        curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dataString)
        ]);
        
        // Execute
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for cURL connection errors
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
            curl_close($ch);

            return ['error' => '1connection_error'];
        }
        
        curl_close($ch);

        // Parse Response
        try {
            $json = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                 return ['error' => '2connection_error'];
            }

            if ($httpCode == 200) {
                $json['error'] = 'no_error';
            }
            
            return $json;
        } catch (Exception $e) {
            return ['error' => '3connection_error'];
        }
    }
}

?>