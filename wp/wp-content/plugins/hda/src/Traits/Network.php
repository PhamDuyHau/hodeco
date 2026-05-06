<?php
/**
 * Network & IP utility trait.
 *
 * Provides: IP address detection, IP matching (exact, CIDR, dash range, IPv6).
 *
 * @author HD
 */

namespace HDAddons\Traits;

use HDAddons\Security\Firewall\CrawlerWhitelist;

\defined( 'ABSPATH' ) || exit;

trait Network {

	// ══════════════════════════════════════════════════
	// IP Address Detection
	// ══════════════════════════════════════════════════

	/**
	 * Get server IP address.
	 *
	 * @return string
	 */
	public static function serverIpAddress(): string {
		// Check for SERVER_ADDR first (sanitized)
		if ( ! empty( $_SERVER['SERVER_ADDR'] ) ) {
			$serverAddr = filter_var( $_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP );
			if ( $serverAddr !== false ) {
				return $serverAddr;
			}
		}

		$hostname = gethostname();
		if ( $hostname === false ) {
			return '127.0.0.1';
		}

		$ipv4 = gethostbyname( $hostname );

		// Validate and return the IPv4 address
		if ( filter_var( $ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return $ipv4;
		}

		// Get the IPv6 address using dns_get_record
		$dnsRecords = dns_get_record( $hostname, DNS_AAAA );
		if ( $dnsRecords ) {
			foreach ( $dnsRecords as $record ) {
				if ( isset( $record['ipv6'] ) && filter_var( $record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					return $record['ipv6'];
				}
			}
		}

		return '127.0.0.1';
	}

	// --------------------------------------------------

	/**
	 * Get client IP address with proper sanitization.
	 *
	 * When behind Cloudflare, only trusts CF-Connecting-IP if REMOTE_ADDR
	 * is a verified Cloudflare IP. This prevents IP spoofing.
	 *
	 * @return string
	 */
	public static function ipAddress(): string {
		// CloudFlare — only trust if REMOTE_ADDR is actually a Cloudflare IP.
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$cfIp = filter_var( $_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP );
			if ( $cfIp !== false ) {
				$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

				// Verify the direct connection is from Cloudflare.
				if ( class_exists( CrawlerWhitelist::class ) ) {
					$whitelist = new CrawlerWhitelist();
					if ( $whitelist->isCloudflareIp( $remoteAddr ) ) {
						return $cfIp;
					}
				} else {
					// Fallback: trust CF header without verification
					// (CrawlerWhitelist not yet loaded or Firewall module disabled).
					return $cfIp;
				}
			}
		}

		// Forwarded IP (proxy) - take only the first valid IP
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwardedIps = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			foreach ( $forwardedIps as $forwardedIp ) {
				$ip = filter_var( trim( $forwardedIp ), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
				if ( $ip !== false ) {
					return $ip;
				}
			}
		}

		// Client IP
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = filter_var( $_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP );
			if ( $ip !== false ) {
				return $ip;
			}
		}

		// Remote address
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
			if ( $ip !== false ) {
				return $ip;
			}
		}

		return '127.0.0.1';
	}

	// ══════════════════════════════════════════════════
	// IP Matching (exact, CIDR, dash range, IPv6)
	// ══════════════════════════════════════════════════

	/**
	 * Check if an IP matches against a single entry (IP, CIDR, or dash range).
	 *
	 * @param string $ip    The visitor IP to check.
	 * @param string $entry The entry to check against (IP, CIDR, or dash range).
	 *
	 * @return bool True if IP matches the entry.
	 */
	public static function ipMatchesEntry( string $ip, string $entry ): bool {
		$entry = trim( $entry );

		if ( empty( $ip ) || empty( $entry ) ) {
			return false;
		}

		// ── IPv6: exact match only ──────────────────────
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			if ( ! filter_var( $entry, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				return false;
			}

			return inet_ntop( inet_pton( $ip ) ) === inet_ntop( inet_pton( $entry ) );
		}

		// ── IPv4 validation ─────────────────────────────
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return false;
		}

		// Fast path: exact IP match (most common case).
		if ( filter_var( $entry, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return $ip === $entry;
		}

		// CIDR notation: 192.168.1.0/24
		if ( str_contains( $entry, '/' ) ) {
			return self::ipMatchesCidr( $ip, $entry );
		}

		// Dash range: 192.168.1.1-100
		if ( str_contains( $entry, '-' ) ) {
			return self::ipMatchesDashRange( $ip, $entry );
		}

		return false;
	}

	/**
	 * Check if an IP matches any entry in a list.
	 *
	 * @param string $ip      The visitor IP to check.
	 * @param array  $entries List of IPs, CIDRs, and/or dash ranges.
	 *
	 * @return bool True if IP matches any entry.
	 */
	public static function ipMatchesAny( string $ip, array $entries ): bool {
		foreach ( $entries as $entry ) {
			if ( self::ipMatchesEntry( $ip, (string) $entry ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an IP is within a CIDR range.
	 *
	 * @param string $ip   The IP to check (must be valid IPv4).
	 * @param string $cidr CIDR notation, e.g. "192.168.1.0/24".
	 *
	 * @return bool
	 */
	public static function ipMatchesCidr( string $ip, string $cidr ): bool {
		$parts = explode( '/', $cidr, 2 );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$subnet = $parts[0];
		$bits   = (int) $parts[1];

		if ( ! filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return false;
		}

		if ( $bits < 0 || $bits > 32 ) {
			return false;
		}

		$ipLong  = ip2long( $ip );
		$subLong = ip2long( $subnet );

		if ( false === $ipLong || false === $subLong ) {
			return false;
		}

		$mask = -1 << ( 32 - $bits );

		return ( $ipLong & $mask ) === ( $subLong & $mask );
	}

	/**
	 * Check if an IP is within a dash range.
	 * Supports formats: "192.168.1.1-100" (last octet range)
	 * and "192.168.1.1-192.168.1.100" (full IP range).
	 *
	 * @param string $ip    The IP to check (must be valid IPv4).
	 * @param string $range Dash range notation.
	 *
	 * @return bool
	 */
	public static function ipMatchesDashRange( string $ip, string $range ): bool {
		$ipLong = ip2long( $ip );

		if ( false === $ipLong ) {
			return false;
		}

		$parts = explode( '-', $range, 2 );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$start = trim( $parts[0] );
		$end   = trim( $parts[1] );

		// Full IP on both sides: 192.168.1.1-192.168.1.100
		if ( filter_var( $start, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && filter_var( $end, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$startLong = ip2long( $start );
			$endLong   = ip2long( $end );

			return $ipLong >= $startLong && $ipLong <= $endLong;
		}

		// Short notation: 192.168.1.1-100 (last octet range).
		if ( filter_var( $start, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && is_numeric( $end ) ) {
			$endOctet = (int) $end;

			if ( $endOctet < 0 || $endOctet > 255 ) {
				return false;
			}

			$startLong = ip2long( $start );
			$endLong   = ( $startLong & 0xFFFFFF00 ) | ( $endOctet & 0xFF );

			return $ipLong >= $startLong && $ipLong <= $endLong;
		}

		return false;
	}
}
