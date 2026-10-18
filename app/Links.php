<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class Links
{
    public static function first(string $text): string
    {
        preg_match_all('~https?://[^\s<>"\']+~iu',$text,$matches);
        foreach($matches[0] as $candidate) {
            $candidate=rtrim($candidate,'.,!?;:');
            foreach([')'=>'(',']'=>'[','}'=>'{'] as $close=>$open) {
                while(str_ends_with($candidate,$close) && substr_count($candidate,$close)>substr_count($candidate,$open)) $candidate=substr($candidate,0,-1);
            }
            $candidate=preg_replace_callback('~^https?://~i',static fn($m)=>strtolower($m[0]),$candidate);
            try { return self::url($candidate); } catch(DomainException) {}
        }
        return '';
    }
    public static function fallback(string $url): array
    {
        $url=self::url($url);
        return ['url'=>$url,'title'=>(string)parse_url($url,PHP_URL_HOST),'description'=>''];
    }
    public static function url(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            || !$parts || !in_array($parts['scheme'] ?? '', ['http','https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || !filter_var($url, FILTER_VALIDATE_URL)) throw new DomainException('Invalid HTTP(S) URL.');
        return $url;
    }
    public static function publicIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
        // Fetch IPv4 only: reject mapped IPv6 and special-use ranges including CGNAT.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
        $n = (int) sprintf('%u', ip2long($ip));
        foreach (['0.0.0.0/8','100.64.0.0/10','192.0.0.0/24','192.0.2.0/24','198.18.0.0/15','198.51.100.0/24','203.0.113.0/24','224.0.0.0/4','240.0.0.0/4'] as $range) {
            [$base,$bits] = explode('/', $range);
            $mask = (0xffffffff << (32 - (int) $bits)) & 0xffffffff;
            if (($n & $mask) === ((int) sprintf('%u', ip2long($base)) & $mask)) return false;
        }
        return true;
    }
    public static function metadata(string $html, string $url): array
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $meta = [];
        foreach ($dom->getElementsByTagName('meta') as $node) {
            $name = strtolower($node->getAttribute('property') ?: $node->getAttribute('name'));
            if (in_array($name, ['og:title','og:description','description'], true)) $meta[$name] = $node->getAttribute('content');
        }
        $clean = static fn(string $text, int $length): string => trim(iconv_substr(preg_replace('/[\x00-\x1f]/u', ' ', strip_tags($text)) ?? '', 0, $length, 'UTF-8') ?: '');
        return ['url'=>$url, 'title'=>$clean($meta['og:title'] ?? ($dom->getElementsByTagName('title')->item(0)?->textContent ?? parse_url($url,PHP_URL_HOST)),300),
            'description'=>$clean($meta['og:description'] ?? $meta['description'] ?? '',600)];
    }
    public static function preview(string $url): array
    {
        $url = self::url($url);
        $fallback = self::fallback($url);
        if (!extension_loaded('curl')) return $fallback;
        // No redirects, cookies, proxy, remote images or browser-side requests. Pin a vetted IP
        // to prevent DNS rebinding between validation and connection.
        $parts = parse_url($url);
        $host = strtolower($parts['host']);
        if (($parts['port'] ?? ($parts['scheme']==='https'?443:80)) !== ($parts['scheme']==='https'?443:80)
            || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/D', $host)) return $fallback;
        $ips = gethostbynamel($host);
        if (!$ips) return $fallback;
        foreach ($ips as $ip) if (!self::publicIp($ip)) return $fallback;
        $body = '';
        $curl = curl_init($url);
        if (PHP_OS_FAMILY==='Windows' && defined('CURLSSLOPT_NATIVE_CA')) curl_setopt($curl,CURLOPT_SSL_OPTIONS,CURLSSLOPT_NATIVE_CA);
        curl_setopt_array($curl, [
            CURLOPT_PROXY=>'', CURLOPT_FOLLOWLOCATION=>false, CURLOPT_PROTOCOLS_STR=>'http,https',
            CURLOPT_CONNECTTIMEOUT=>2, CURLOPT_TIMEOUT=>4, CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_RESOLVE=>[$host . ':' . ($parts['scheme']==='https'?443:80) . ':' . $ips[0]],
            CURLOPT_USERAGENT=>'Friends-SNCMS/0.3 LinkPreview', CURLOPT_HTTPHEADER=>['Accept: text/html'],
            CURLOPT_WRITEFUNCTION=>static function ($handle, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > 262144) return 0;
                $body .= $chunk; return strlen($chunk);
            }
        ]);
        $ok = curl_exec($curl);
        $status = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        $mime = (string) curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
        curl_close($curl);
        return $ok && $status===200 && str_starts_with(strtolower($mime),'text/html') ? self::metadata($body,$url) : $fallback;
    }
    public static function render(string $text): string
    {
        $parts = preg_split('~(https?://[^\s<>]+)~u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach ($parts ?: [$text] as $part) {
            try { $url=self::url($part); $result.='<a href="'.e($url).'" target="_blank" rel="ugc noopener noreferrer">'.e($url).'</a>'; }
            catch (DomainException) { $result .= e($part); }
        }
        return $result;
    }
}
