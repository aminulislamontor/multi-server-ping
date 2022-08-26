<?php
/**
 * Hybula Looking Glass
 *
 * The LookingGlass class provides all functionality.
 *
 * @copyright 2022 Hybula B.V.
 * @license Mozilla Public License 2.0
 * @version 0.1
 * @since File available since release 0.1
 * @link https://github.com/hybula/lookingglass
 */

declare(strict_types=1);

namespace Hybula;

class LookingGlass
{
    public static $targetHost;
    public static $targetType;

    /**
     * Validates the config.php file for required constants.
     *
     * @return void
     */
    public static function validateConfig(): void
    {
        if (!defined('LG_TITLE')) die('LG_TITLE not found in config.php');
        if (!defined('LG_LOGO')) die('LG_LOGO not found in config.php');
        if (!defined('LG_LOGO_URL')) die('LG_LOGO_URL not found in config.php');
        if (!defined('LG_CSS_OVERRIDES')) die('LG_CSS_OVERRIDES not found in config.php');
        if (!defined('LG_BLOCK_NETWORK')) die('LG_BLOCK_NETWORK not found in config.php');
        if (!defined('LG_BLOCK_LOOKINGGLAS')) die('LG_BLOCK_LOOKINGGLAS not found in config.php');
        if (!defined('LG_BLOCK_SPEEDTEST')) die('LG_BLOCK_SPEEDTEST not found in config.php');
        if (!defined('LG_BLOCK_CUSTOM')) die('LG_BLOCK_CUSTOM not found in config.php');
        if (!defined('LG_CUSTOM_HTML')) die('LG_CUSTOM_HTML not found in config.php');
        if (!defined('LG_CUSTOM_PHP')) die('LG_CUSTOM_PHP not found in config.php');
        if (!defined('LG_LOCATION')) die('LG_LOCATION not found in config.php');
        if (!defined('LG_FACILITY')) die('LG_FACILITY not found in config.php');
        if (!defined('LG_FACILITY_URL')) die('LG_FACILITY_URL not found in config.php');
        if (!defined('LG_IPV4')) die('LG_IPV4 not found in config.php');
        if (!defined('LG_IPV6')) die('LG_IPV6 not found in config.php');
        if (!defined('LG_METHODS')) die('LG_METHODS not found in config.php');
        if (!defined('LG_LOCATIONS')) die('LG_LOCATIONSnot found in config.php');
        if (!defined('LG_SPEEDTEST_IPERF')) die('LG_SPEEDTEST_IPERF not found in config.php');
        if (!defined('LG_SPEEDTEST_LABEL_INCOMING')) die('LG_SPEEDTEST_LABEL_INCOMING not found in config.php');
        if (!defined('LG_SPEEDTEST_CMD_INCOMING')) die('LG_SPEEDTEST_CMD_INCOMING not found in config.php');
        if (!defined('LG_SPEEDTEST_LABEL_OUTGOING')) die('LG_SPEEDTEST_LABEL_OUTGOING not found in config.php');
        if (!defined('LG_SPEEDTEST_CMD_OUTGOING')) die('LG_SPEEDTEST_CMD_OUTGOING not found in config.php');
        if (!defined('LG_SPEEDTEST_FILES')) die('LG_SPEEDTEST_FILES not found in config.php');
        if (!defined('LG_TERMS')) die('LG_TERMS not found in config.php');
    }

    /**
     * Starts a PHP session and sets security tokens.
     *
     * @return void
     */
    public static function startSession(): void
    {
        session_name('HYLOOKINGLASS');
        @session_start() or die('Could not start session!');
    }

    /**
     * Validates and checks an IPv4 address.
     *
     * @param string $ip The IPv4 address to validate.
     * @return bool True or false depending on validation.
     */
    public static function isValidIpv4(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        return false;
    }

    /**
     * Validates and checks an IPv6 address.
     *
     * @param string $ip The IPv6 address to validate.
     * @return bool True or false depending on validation.
     */
    public static function isValidIpv6(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        return false;
    }

    /**
     * Validates and checks a host address.
     * Differs from isValidIpvX because it also extracts the host.
     *
     * @param string $host The host to validate.
     * @return string Actual hostname or empty if none found.
     */
    public static function isValidHost(string $host, string $type): string
    {
        $host = str_replace(['http://', 'https://'], '', $host);
        if (!substr_count($host, '.')) {
            return '';
        }
        if (filter_var('https://' . $host, FILTER_VALIDATE_URL)) {
            if ($host = parse_url('https://' . $host, PHP_URL_HOST)) {
                if ($type == 'ipv4' && isset(dns_get_record($host, DNS_A)[0]['ip'])) {
                    return $host;
                }
                if ($type == 'ipv6' && isset(dns_get_record($host, DNS_AAAA)[0]['ipv6'])) {
                    return $host;
                }
                return '';
            }
        }
        return '';
    }

    /**
     * Determine the IP address of the client.
     * Also supports clients behind a proxy, however we need to validate this as this header can be spoofed.
     * The REMOTE_ADDR header is secure because it's populated by the webserver (extracted from TCP packets).
     *
     * @return string The IP address of the client.
     */
    public static function detectIpAddress(): string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Executes a ping command.
     *
     * @param string $host The target host.
     * @param int $count Number of requests.
     * @return bool True on success.
     */
    public static function ping(string $host, int $count = 4): bool
    {
        return self::procExecute('ping -c' . $count . ' -w15', $host);
    }

    /**
     * Executes a ping6 command.
     *
     * @param string $host The target host.
     * @param int $count Number of requests.
     * @return bool True on success.
     */
    public static function ping6(string $host, int $count = 4): bool
    {
        return self::procExecute('ping -6 -c' . $count . ' -w15', $host);
    }

    /**
     * Executes a mtr command.
     *
     * @param string $host The target host.
     * @return bool True on success.
     */
    public static function mtr(string $host): bool
    {
        return self::procExecute('mtr -4 --report --report-wide', $host);
    }

    /**
     * Executes a mtr6 command.
     *
     * @param string $host The target host.
     * @return bool True on success.
     */
    public static function mtr6(string $host): bool
    {
        return self::procExecute('mtr -6 --report --report-wide', $host);
    }

    /**
     * Executes a traceroute command.
     *
     * @param string $host The target host.
     * @param int $failCount Number of failed hops.
     * @return bool True on success.
     */
    public static function traceroute(string $host, int $failCount = 4): bool
    {
        return self::procExecute('traceroute -4 -w2', $host, $failCount);
    }

    /**
     * Executes a traceroute6 command.
     *
     * @param string $host The target host.
     * @param int $failCount Number of failed hops.
     * @return bool True on success.
     */
    public static function traceroute6(string $host, int $failCount = 4): bool
    {
        return self::procExecute('traceroute -6 -w2', $host, $failCount);
    }

    /**
     * Executes a command and opens pipe for input/output.
     * Directly taken from telephone/LookingGlass (MIT License)
     *
     * @param  string  $cmd The command to execute.
     * @param  string  $host The host that is used as param.
     * @param  int $failCount Number of consecutive failed hops.
     * @return boolean True on success.
     * @link https://github.com/telephone/LookingGlass/blob/master/LookingGlass/LookingGlass.php#L172
     * @license https://github.com/telephone/LookingGlass/blob/master/LICENCE.txt
     */
    private static function procExecute(string $cmd, string $host, int $failCount = 2): bool
    {
        // define output pipes
        $spec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        // sanitize + remove single quotes
        $host = str_replace('\'', '', filter_var($host, FILTER_SANITIZE_URL));
        // execute command
        $process = proc_open("{$cmd} '{$host}'", $spec, $pipes, null);

        // check pipe exists
        if (!is_resource($process)) {
            return false;
        }

        // check for mtr/traceroute
        if (strpos($cmd, 'mtr') !== false) {
            $type = 'mtr';
        } elseif (strpos($cmd, 'traceroute') !== false) {
            $type = 'traceroute';
        } else {
            $type = '';
        }

        $fail = 0;
        $match = 0;
        $traceCount = 0;
        $lastFail = 'start';
        // iterate stdout
        while (($str = fgets($pipes[1], 4096)) != null) {
            // check for output buffer
            if (ob_get_level() == 0) {
                ob_start();
            }

            // fix RDNS XSS (outputs non-breakble space correctly)
            $str = htmlspecialchars(trim($str));

            // correct output for mtr
            if ($type === 'mtr') {
                if ($match < 10 && preg_match('/^[0-9]\. /', $str, $string)) {
                    $str = preg_replace('/^[0-9]\. /', '&nbsp;&nbsp;' . $string[0], $str);
                    $match++;
                } else {
                    $str = preg_replace('/^[0-9]{2}\. /', '&nbsp;' . substr($str, 0, 4), $str);
                }
            }
            // correct output for traceroute
            elseif ($type === 'traceroute') {
                if ($match < 10 && preg_match('/^[0-9] /', $str, $string)) {
                    $str = preg_replace('/^[0-9] /', '&nbsp;' . $string[0], $str);
                    $match++;
                }
                // check for consecutive failed hops
                if (strpos($str, '* * *') !== false) {
                    $fail++;
                    if ($lastFail !== 'start'
                        && ($traceCount - 1) === $lastFail
                        &&  $fail >= $failCount
                    ) {
                        echo str_pad($str . '<br />-- Traceroute timed out --<br />', 4096, ' ', STR_PAD_RIGHT);
                        break;
                    }
                    $lastFail = $traceCount;
                }
                $traceCount++;
            }

            // pad string for live output
            echo str_pad($str . '<br />', 4096, ' ', STR_PAD_RIGHT);

            // flush output buffering
            @ob_flush();
            flush();
        }

        // iterate stderr
        while (($err = fgets($pipes[2], 4096)) != null) {
            // check for IPv6 hostname passed to IPv4 command, and vice versa
            if (strpos($err, 'Name or service not known') !== false || strpos($err, 'unknown host') !== false) {
                echo 'Unauthorized request';
                break;
            }
        }

        $status = proc_get_status($process);
        if ($status['running'] == true) {
            // close pipes that are still open
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            if ($status['pid']) {
                // retrieve parent pid
                //$ppid = $status['pid'];
                // use ps to get all the children of this process
                $pids = preg_split('/\s+/', 'ps -o pid --no-heading --ppid '.$status['pid']);
                // kill remaining processes
                foreach ($pids as $pid) {
                    if (is_numeric($pid)) {
                        posix_kill($pid, 9);
                    }
                }
            }
            proc_close($process);
        }
        return true;
    }
}
