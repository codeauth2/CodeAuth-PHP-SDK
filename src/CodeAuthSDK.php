<?php

namespace CodeAuthSDK;

class CodeAuth {
    private static $Endpoint;
    private static $ProjectID;
    private static $UseCache;
    private static $CacheDuration;
    private static $CacheSession = [];
    private static $CacheExpiration;
    private static $HasInitialized = false;

    /**
     * @summary Initialize the CodeAuth SDK
     * @param string $project_endpoint - The endpoint of your project. This can be found inside your project settings.
     * @param string $project_id - Your project ID. This can be found inside your project settings.
     * @param bool $use_cache - Whether to use cache or not. Using cache can help speed up response time and mitigate some rate limits. This will automatically cache new session token (from '/signin/emailverify', 'signin/socialverify', 'session/info', 'session/refresh') and automatically delete cache when it is invalidated (from 'session/refresh', 'session/invalidate').
     * @param int $cache_duration (Seconds) - How long the cache should last. At least 15 seconds required to effectively mitigate most rate limits. Check docs for more info.
     * @throws Exception
     */
    public static function Initialize($project_endpoint, $project_id, $use_cache = true, $cache_duration = 30) 
    {
        if (self::$HasInitialized) throw new Exception('CodeAuth has already been Initialized.');
        self::$HasInitialized = true;

        self::$Endpoint = $project_endpoint;
        self::$ProjectID = $project_id;
        self::$UseCache = $use_cache;
        self::$CacheDuration = $cache_duration * 1000;
        self::$CacheSession = [];
        self::$CacheExpiration = microtime(true) * 1000 + self::$CacheDuration;
    }
    
    // -------
    // Makes sure that the CodeAuth SDK has been initialized
    // -------
    private static function EnsureInitialized()
    {
        if (!self::$HasInitialized) {
            throw new Exception("CodeAuth has not been initialized.");
        }
    }

    // -------
    // Makes sure cache hasn't expired, if it did, delete the whole map
    // -------
    private static function EnsureCache()
    {
        if (!self::$UseCache) return;

        $now = microtime(true) * 1000;

        if (self::$CacheExpiration < $now) {
            self::$CacheSession = [];
            self::$CacheExpiration = $now + self::$CacheDuration;
        }
    }

    // -------
    // Create api request and call server
    // -------
    private static function CallApiRequest($path, $body)
    {
        $url = "https://" . self::$Endpoint . $path;

        try {
            $payload = json_encode($body);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Content-Length: " . strlen($payload)
            ]);

            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                return ["error" => "connection_error"];
            }

            $json = json_decode($response, true);

            if (!is_array($json)) {
                return ["error" => "connection_error"];
            }

            if ($statusCode === 200) {
                $json["error"] = "no_error";
            }

            return $json;
        } catch (Exception $e) {
            return ["error" => "connection_error"];
        }
    }

    /**
     * @summary Begins the sign in or register flow by sending the user a one time code via email.
     * @param string $email The email of the user you are trying to sign in/up. Email must be between 1 and 64 characters long. The email must also only contain letter, number, dot (not first, last, or consecutive), underscore(not first or last) and/or hyphen(not first or last).
     * @return array A success response will return error = 'no_error'
     */
    public static function SignInEmail($email) 
    {
        // make sure CodeAuth SDK has been initialized
        self::EnsureInitialized();

        // make sure cache if valid
        self::EnsureCache();

        return self::CallApiRequest(
            "/signin/email", 
            [
                "project_id" => self::$ProjectID,
                "email" => $email
            ]
        );
    }

    /**
     * @summary Checks if the one time code matches in order to create a session token.
     * @param string $email The email of the user you are trying to sign in/up. Email must be between 1 and 64 characters long. The email must also only contain letter, number, dot (not first, last, or consecutive), underscore(not first or last) and/or hyphen(not first or last).
     * @param string $code The one time code that was sent to the email.
     * @return array [ session_token, email, expiration, refresh_left ]
     */
    public static function SignInEmailVerify($email, $code) 
    {
        // make sure CodeAuth SDK has been initialized
        self::EnsureInitialized();

        // make sure cache if valid
        self::EnsureCache();

        // call server and get response 
        $result = self::CallApiRequest(
            "/signin/emailverify", 
            [
            "project_id" => self::$ProjectID,
            "email" => $email,
            "code" => $code
            ]
        );

        // save to cache if enabled
        if (self::$UseCache && $result["error"] === "no_error") {
            self::$CacheSession[$result["session_token"]] = $result;
        }
        
        // return signin email verify
        return $result;
    }

    /**
     * @summary Begins the sign in or register flow by allowing users to sign in through a social OAuth2 link.
     * @param string $social_type The type of social OAuth2 url you are trying to create. Possible social types: "google", "microsoft", "apple"
     * @return array [signin_url]
     */
    public static function SignInSocial($social_type) 
    {
        // make sure CodeAuth SDK has been initialized
        self::EnsureInitialized();

        // make sure cache if valid
        self::EnsureCache();

        // return signin social 
        return self::request(
            "https://" . self::$Endpoint . "/signin/social", 
            [
                'project_id' => self::$ProjectID,
                'social_type' => $social_type
            ]
        );
    }

    /**
     * @summary This is the next step after the user signs in with their social account. This request checks the authorization code given by the social media company in order to create a session token.
     * @param string $social_type The type of social OAuth2 url you are trying to verify. Possible social types: "google", "microsoft", "apple"
     * @param string $authorization_code The authorization code given by the social. Check the docs for more info.
     * @return array [session_token, email, expiration, refresh_left]
     */
    public static function SignInSocialVerify($social_type, $authorization_code) 
    {
        // make sure CodeAuth SDK has been initialized
        self::EnsureInitialized();

        // make sure cache if valid
        self::EnsureCache();

        // call server and get response 
        $result = self::CallApiRequest("/signin/socialverify", [
            "project_id" => self::$ProjectID,
            "social_type" => $social_type,
            "authorization_code" => $authorization_code
        ]);

        // save to cache if enabled
        if (self::$UseCache && $result["error"] === "no_error") {
            self::$CacheSession[$result["session_token"]] = $result;
        }
        
        // return signin social verify
        return $result;
    }

    /**
     * @summary Gets the information associated with a session token.
     * @param string $session_token The session token you are trying to get information on.
     * @return array [email, expiration, refresh_left ]
     */
    public static function SessionInfo($session_token) 
    {
        // make sure CodeAuth SDK has been initialized
        self::EnsureInitialized();

        // make sure cache if valid
        self::EnsureCache();

        // return the cached info if it is enabled, not expired and exist
        $now = microtime(true) * 1000;
        if (self::$UseCache && self::$CacheExpiration > $now) {
            if (isset(self::$CacheSession[$session_token])) {
                return self::$CacheSession[$session_token];
            }
        }

        // call server and get response 
        $result = self::CallApiRequest(
            "/session/info", 
            [
                "project_id" => self::$ProjectID,
                "session_token" => $session_token
            ]
        );

        // save to cache if enabled
        if (self::$UseCache && $result["error"] === "no_error") {
            self::$CacheSession[$session_token] = $result;
        }

        return $result;
    }

    /**
     * @summary Create a new session token using existing session token.
     * @param string $session_token The session token you are trying to use to create a new token.
     * @return array [session_token:<string>, email:<string>, expiration:<int>, refresh_left:<int> ]
     */
    public static function SessionRefresh($session_token) 
    {        
        // make sure CodeAuth SDK has been initialized
        self::EnsureInitialized();

        // make sure cache if valid
        self::EnsureCache();

        // call server and get response 
        $result = self::CallApiRequest(
            "/session/refresh", 
            [
                "project_id" => self::$ProjectID,
                "session_token" => $session_token
            ]
        );

        // if cache is enabled, delete old session token cache and set the new one
        if (self::$UseCache && $result["error"] === "no_error") {
            unset(self::$CacheSession[$session_token]);
            self::$CacheSession[$result["session_token"]] = $result;
        }

        // return
        return $result;
    }

    /**
     * @summary Invalidate a session token. By doing so, the session token can no longer be used for any api call.
     * @param string $session_token The session token you are trying to use to invalidate.
     * @param string $invalidate_type How to use the session token to invalidate. Possible invalidate types: 'only_this', 'all', 'all_but_this'
     * @return array []
     */
    public static function SessionInvalidate($session_token, $invalidate_type) 
    {
        // make sure CodeAuth SDK has been initialized
        self::EnsureInitialized();
        
        // make sure cache if valid
        self::EnsureCache();

        // call server and get response 
        $result = self::CallApiRequest("/session/invalidate", [
            "project_id" => self::$ProjectID,
            "session_token" => $session_token,
            "invalidate_type" => $invalidate_type
        ]);


        // if cache is enabled, and there is no problem with the request, delete the session token cache
        if (self::$UseCache && $result["error"] === "no_error") {
            unset(self::$CacheSession[$session_token]);
        }

        // return
        return $result;
    }
}

?>
