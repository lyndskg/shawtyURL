<?php

class Shortener {
    // Default characters to use for shortening.
    // Var: String.
    private $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    // Salt for ID encoding.
    // Var: String.
    private $salt = '';

    // Length of number padding.
    private $padding = 1;

    // Hostname
    private $hostname = '';

    // PDO database connection.
    // Var: Object.
    private $connection = null;

    // Whitelist of IPs allowed to save URLs.
    // If the list is empty, then any IP is allowed.
    // Var: Array.
    private $whitelist = array();

    // Constuctor
    // Param string hostname: Hostname
    // Param object connection: Database connection
    public function __construct($hostname, $connection) {
        $this->hostname = $hostname;
        $this->connection = $connection;
    }

    //Gets the character set for encoding.
    // Returns string (set of characters).
    public function get_chars() {
        return $this->chars;
    }

    // Sets the character set for encoding.
    // Param string chars: (set of characters).
    public function set_chars($chars) {
        if (!is_string($chars) || empty($chars)) {
            throw new Exception('Invalid input.');
        }
        $this->chars = $chars;
    }

    //Gets the salt string for encoding.
    // Returns string: Salt.
    public function get_salt() {
        return $this->salt;
    }

    //Sets the salt string for encoding.
    // Param string salt: Salt string
    public function set_salt($salt) {
        $this->salt = $salt;
    }

    // Gets the padding length.
    // Returns int: Padding length.
    public function get_padding() {
        return $this->padding;
    }

    // Sets the padding length.
    // Param int padding: Padding length
    public function set_padding($padding) {
        $this->padding = $padding;
    }

    // Converts an ID to an encoded string.
    // Param int n: Number to encode
    // Returns string: Encoded string.
    public function encode($n) {
        $k = 0;

        if ($this->padding > 0 && !empty($this->salt)) {
            $k = self::get_seed($n, $this->salt, $this->padding);
            $n = (int)($k.$n);
        }

        return self::num_to_alpha($n, $this->chars);
    }

    //Converts an encoded string into a number.
    // Param string s: String to decode
    // Returns int: Decoded number.
    public function decode($s) {
        $n = self::alpha_to_num($s, $this->chars);

        return (!empty($this->salt)) ? substr($n, $this->padding) : $n;
    }

    // Gets a number for padding based on a salt.
    // Param int n: Number to pad
    // Param string salt: Salt string
    // Param int padding: Padding length
    // 
    // Returns int Number for padding.
    public static function get_seed($n, $salt, $padding) {
        // Calculate the MD5 hash of the given number based on the salt value.
        $hash = md5($n.$salt);
        $dec = hexdec(substr($hash, 0, $padding));
        $num = $dec % pow(10, $padding);

        if ($num == 0) $num = 1;
        $num = str_pad($num, $padding, '0');

        return $num;
    }

    // Converts a number to an alpha-numeric string.
    // Param int num: Number to convert
    // Param string s: String of characters for conversion
    // Returns string: Alpha-numeric string.
    public static function num_to_alpha($n, $s) {
        $b = strlen($s);
        $m = $n % $b;

        if ($n - $m == 0) return substr($s, $n, 1);

        $a = '';

        while ($m > 0 || $n > 0) {
            $a = substr($s, $m, 1).$a;
            $n = ($n - $m) / $b;
            $m = $n % $b;
        }

        return $a;
    }

    // Converts an alpha numeric string to a number.
    // Param string a: Alpha-numeric string to convert
    // Param string s: String of characters for conversion
    // Returns int: Converted number.
    public static function alpha_to_num($a, $s) {
        $b = strlen($s);
        $l = strlen($a);

        for ($n = 0, $i = 0; $i < $l; $i++) {
            $n += strpos($s, substr($a, $i, 1)) * pow($b, $l - $i - 1);
        }

        return $n;
    }

    // Looks up a URL in the database by id.
    // Param string id: URL id
    // Returns array URL record.
    public function fetch($id) {
        $statement = $this->connection->prepare(
            'SELECT * FROM urls WHERE id = ?'
        );
        $statement->execute(array($id));

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    // Attempts to locate a URL in the database.
    // Param string url: URL
    // Returns array: URL record.
    public function find($url) {
        $statement = $this->connection->prepare(
            'SELECT * FROM urls WHERE url = ?'
        );
        $statement->execute(array($url));

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    // Stores a URL in the database.
    // Param string url: URL to store
    // Returns int: Insert id
    public function store($url) {
        $datetime = date('Y-m-d H:i:s');

        $statement = $this->connection->prepare(
            'INSERT INTO urls (url, created) VALUES (?,?)'
        );
        $statement->execute(array($url, $datetime));

        return $this->connection->lastInsertId();
    }

    // Updates statistics for a URL.
    // Param int id: URL id
    public function update($id) {
        $datetime = date('Y-m-d H:i:s');

        $statement = $this->connection->prepare(
            'UPDATE urls SET hits = hits + 1, accessed = ? WHERE id = ?'
        );
        $statement->execute(array($datetime, $id));
    }

    // Sends a redirect to a URL.
    // Param string url: URL
    public function redirect($url) {
        header("Location: $url", true, 301);
        exit();
    }

    // Sends a 404 response.
    public function not_found() {
        header('Status: 404 Not Found');
        exit(
            '<h1>404 Not Found</h1>'.
            str_repeat(' ', 512)
        );
    }

    // Sends an error message.
    // Param string message: Error message
    public function error($message) {
        exit("<h1>$message</h1>");
    }

    //Adds an IP to allow saving URLs.
    // Param string|array ip: IP address or array of IP addresses
    public function allow($ip) {
        if (is_array($ip)) {
            $this->whitelist = array_merge($this->whitelist, $ip);
        } else {
            array_push($this->whitelist, $ip);
        } // if-else
    } // allow()

    // Starts the program.
    public function run() {
        $q = str_replace('/', '', $_GET['q']);
        $url = '';

        if (isset($_GET['url'])) {
          $url = urldecode($_GET['url']);
        } // if

        $format = '';
        if (isset($_GET['format'])) {
          $format = strtolower($_GET['format']);
        } // if

        // If adding a new URL
        if (!empty($url)) {
            if (!empty($this->whitelist) && !in_array($_SERVER['REMOTE_ADDR'], $this->whitelist)) {
                $this->error('Not allowed.');
            } // if

            if (preg_match('/^http[s]?\:\/\/[\w]+/', $url)) {
                $result = $this->find($url);

                // Not found, so save it
                if (empty($result)) {

                    $id = $this->store($url);

                    $url = $this->hostname.'/'.$this->encode($id);
                } else {
                    $url = $this->hostname.'/'.$this->encode($result['id']);
                } // if-else

                // Display the shortened url
                switch ($format) {
                    // Text
                    case 'text':
                        exit($url);

                    // JSON
                    case 'json':
                        header('Content-Type: application/json');
                        exit(json_encode(array('url' => $url)));
                    
                    // XML 
                    case 'xml':
                        header('Content-Type: application/xml');
                        exit(implode("\n", array(
                            '<?xml version="1.0"?'.'>',
                            '<response>',
                            '  <url>'.htmlentities($url).'</url>',
                            '</response>'
                        )));
                    
                    // HTML
                    default:
                        exit('<a href="'.$url.'">'.$url.'</a>');
                } // switch
            } else {
                $this->error('Bad input.');
            } // if-else
        } // if (!empty($url))

        // Lookup by id
        else {
            if (empty($q)) {
              $this->not_found();
              return;
            }

            if (preg_match('/^([a-zA-Z0-9]+)$/', $q, $matches)) {
                $id = self::decode($matches[1]);

                $result = $this->fetch($id);

                if (!empty($result)) {
                    $this->update($id);

                    $this->redirect($result['url']);
                }
                else {
                    $this->not_found();
                } // if-else
            } // if
        } // if-else
    } // run()
} // class Shortener
