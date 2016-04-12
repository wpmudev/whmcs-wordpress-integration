<?php

/**
 * Custom Streams HTTP transport implementation.
 *
 * Adds some new hooks to WP Core implementation.
 *
 *
 * @since 1.4.2
 */
class WP_Http_WHMCS_Streams extends WP_Http_Streams
{
	/**
	 * Send a HTTP request to a URI using PHP Streams.
	 *
	 * @see WP_Http::request For default options descriptions.
	 *
	 * @since 1.4.2
	 *
	 * @access public
	 * @param string $url The request URL.
	 * @param string|array $args Optional. Override the defaults.
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'. A WP_Error instance upon error
	 */
	public function request($url, $args = array()) {
		$defaults = array(
			'method' => 'GET', 'timeout' => 5,
			'redirection' => 5, 'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(), 'body' => null, 'cookies' => array()
		);

		$r = wp_parse_args( $args, $defaults );

		if ( isset( $r['headers']['User-Agent'] ) ) {
			$r['user-agent'] = $r['headers']['User-Agent'];
			unset( $r['headers']['User-Agent'] );
		} elseif ( isset( $r['headers']['user-agent'] ) ) {
			$r['user-agent'] = $r['headers']['user-agent'];
			unset( $r['headers']['user-agent'] );
		}

		// Construct Cookie: header if any cookies are set.
		WP_Http::buildCookieHeader( $r );

		$arrURL = parse_url($url);

		$connect_host = $arrURL['host'];

		$secure_transport = ( $arrURL['scheme'] == 'ssl' || $arrURL['scheme'] == 'https' );
		if ( ! isset( $arrURL['port'] ) ) {
			if ( $arrURL['scheme'] == 'ssl' || $arrURL['scheme'] == 'https' ) {
				$arrURL['port'] = 443;
				$secure_transport = true;
			} else {
				$arrURL['port'] = 80;
			}
		}

		// Always pass a Path, defaulting to the root in cases such as http://example.com
		if ( ! isset( $arrURL['path'] ) ) {
			$arrURL['path'] = '/';
		}

		if ( isset( $r['headers']['Host'] ) || isset( $r['headers']['host'] ) ) {
			if ( isset( $r['headers']['Host'] ) )
				$arrURL['host'] = $r['headers']['Host'];
			else
				$arrURL['host'] = $r['headers']['host'];
			unset( $r['headers']['Host'], $r['headers']['host'] );
		}

		/*
		 * Certain versions of PHP have issues with 'localhost' and IPv6, It attempts to connect
		 * to ::1, which fails when the server is not set up for it. For compatibility, always
		 * connect to the IPv4 address.
		 */
		if ( 'localhost' == strtolower( $connect_host ) )
			$connect_host = '127.0.0.1';

		$connect_host = $secure_transport ? 'ssl://' . $connect_host : 'tcp://' . $connect_host;

		$is_local = isset( $r['local'] ) && $r['local'];
		$ssl_verify = isset( $r['sslverify'] ) && $r['sslverify'];
		if ( $is_local ) {
			/**
			 * Filter whether SSL should be verified for local requests.
			 *
			 * @since 2.8.0
			 *
			 * @param bool $ssl_verify Whether to verify the SSL connection. Default true.
			 */
			$ssl_verify = apply_filters( 'https_local_ssl_verify', $ssl_verify );
		} elseif ( ! $is_local ) {
			/**
			 * Filter whether SSL should be verified for non-local requests.
			 *
			 * @since 2.8.0
			 *
			 * @param bool $ssl_verify Whether to verify the SSL connection. Default true.
			 */
			$ssl_verify = apply_filters( 'https_ssl_verify', $ssl_verify );
		}

		$proxy = new WP_HTTP_Proxy();

		$context = stream_context_create( array(
			'ssl' => array(
				'verify_peer' => $ssl_verify,
				//'CN_match' => $arrURL['host'], // This is handled by self::verify_ssl_certificate()
				'capture_peer_cert' => $ssl_verify,
				'SNI_enabled' => true,
				'cafile' => $r['sslcertificates'],
				'allow_self_signed' => ! $ssl_verify,
			)
		) );

		$timeout = (int) floor( $r['timeout'] );
		$utimeout = $timeout == $r['timeout'] ? 0 : 1000000 * $r['timeout'] % 1000000;
		$connect_timeout = max( $timeout, 1 );

		// Store error number.
		$connection_error = null;

		// Store error string.
		$connection_error_str = null;

		if ( !WP_DEBUG ) {
			// In the event that the SSL connection fails, silence the many PHP Warnings.
			if ( $secure_transport )
				$error_reporting = error_reporting(0);

			if ( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) )
				$handle = @stream_socket_client( 'tcp://' . $proxy->host() . ':' . $proxy->port(), $connection_error, $connection_error_str, $connect_timeout, STREAM_CLIENT_CONNECT, $context );
			else
				$handle = @stream_socket_client( $connect_host . ':' . $arrURL['port'], $connection_error, $connection_error_str, $connect_timeout, STREAM_CLIENT_CONNECT, $context );

			if ( $secure_transport )
				error_reporting( $error_reporting );

		} else {
			if ( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) )
				$handle = stream_socket_client( 'tcp://' . $proxy->host() . ':' . $proxy->port(), $connection_error, $connection_error_str, $connect_timeout, STREAM_CLIENT_CONNECT, $context );
			else
				$handle = stream_socket_client( $connect_host . ':' . $arrURL['port'], $connection_error, $connection_error_str, $connect_timeout, STREAM_CLIENT_CONNECT, $context );
		}

		if ( false === $handle ) {
			// SSL connection failed due to expired/invalid cert, or, OpenSSL configuration is broken.
			if ( $secure_transport && 0 === $connection_error && '' === $connection_error_str )
				return new WP_Error( 'http_request_failed', __( 'The SSL certificate for the host could not be verified.' ) );

			return new WP_Error('http_request_failed', $connection_error . ': ' . $connection_error_str );
		}

		// Verify that the SSL certificate is valid for this request.
		if ( $secure_transport && $ssl_verify && ! $proxy->is_enabled() ) {
			if ( ! self::verify_ssl_certificate( $handle, $arrURL['host'] ) )
				return new WP_Error( 'http_request_failed', __( 'The SSL certificate for the host could not be verified.' ) );
		}

		stream_set_timeout( $handle, $timeout, $utimeout );

		if ( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) ) //Some proxies require full URL in this field.
			$requestPath = $url;
		else
			$requestPath = $arrURL['path'] . ( isset($arrURL['query']) ? '?' . $arrURL['query'] : '' );

		$strHeaders = strtoupper($r['method']) . ' ' . $requestPath . ' HTTP/' . $r['httpversion'] . "\r\n";

		$include_port_in_host_header = (
			( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) ) ||
			( 'http'  == $arrURL['scheme'] && 80  != $arrURL['port'] ) ||
			( 'https' == $arrURL['scheme'] && 443 != $arrURL['port'] )
		);

		if ( $include_port_in_host_header ) {
			$strHeaders .= 'Host: ' . $arrURL['host'] . ':' . $arrURL['port'] . "\r\n";
		} else {
			$strHeaders .= 'Host: ' . $arrURL['host'] . "\r\n";
		}

		if ( isset($r['user-agent']) )
			$strHeaders .= 'User-agent: ' . $r['user-agent'] . "\r\n";

		if ( is_array($r['headers']) ) {
			foreach ( (array) $r['headers'] as $header => $headerValue )
				$strHeaders .= $header . ': ' . $headerValue . "\r\n";
		} else {
			$strHeaders .= $r['headers'];
		}

		if ( $proxy->use_authentication() )
			$strHeaders .= $proxy->authentication_header() . "\r\n";

		$strHeaders .= "\r\n";

		if ( ! is_null($r['body']) )
			$strHeaders .= $r['body'];

		fwrite($handle, $strHeaders);

		if ( ! $r['blocking'] ) {
			stream_set_blocking( $handle, 0 );
			fclose( $handle );
			return array( 'headers' => array(), 'body' => '', 'response' => array('code' => false, 'message' => false), 'cookies' => array() );
		}

		$strResponse = '';
		$bodyStarted = false;
		$keep_reading = true;
		$block_size = 4096;
		if ( isset( $r['limit_response_size'] ) )
			$block_size = min( $block_size, $r['limit_response_size'] );

		// If streaming to a file setup the file handle.
		if ( $r['stream'] ) {
			if ( ! WP_DEBUG )
				$stream_handle = @fopen( $r['filename'], 'w+' );
			else
				$stream_handle = fopen( $r['filename'], 'w+' );
			if ( ! $stream_handle )
				return new WP_Error( 'http_request_failed', sprintf( __( 'Could not open handle for fopen() to %s' ), $r['filename'] ) );

			$bytes_written = 0;
			while ( ! feof($handle) && $keep_reading ) {
				$block = fread( $handle, $block_size );
				if ( ! $bodyStarted ) {
					$strResponse .= $block;
					if ( strpos( $strResponse, "\r\n\r\n" ) ) {
						$process = WP_Http::processResponse( $strResponse );
						$bodyStarted = true;
						$block = $process['body'];
						unset( $strResponse );
						$process['body'] = '';
					}
				}

				$this_block_size = strlen( $block );

				if ( isset( $r['limit_response_size'] ) && ( $bytes_written + $this_block_size ) > $r['limit_response_size'] ) {
					$this_block_size = ( $r['limit_response_size'] - $bytes_written );
					$block = substr( $block, 0, $this_block_size );
				}

				$bytes_written_to_file = fwrite( $stream_handle, $block );

				if ( $bytes_written_to_file != $this_block_size ) {
					fclose( $handle );
					fclose( $stream_handle );
					return new WP_Error( 'http_request_failed', __( 'Failed to write request to temporary file.' ) );
				}

				$bytes_written += $bytes_written_to_file;

				$keep_reading = !isset( $r['limit_response_size'] ) || $bytes_written < $r['limit_response_size'];
			}

			fclose( $stream_handle );

		} else {
			$header_length = 0;
			while ( ! feof( $handle ) && $keep_reading ) {
				$block = fread( $handle, $block_size );
				$strResponse .= $block;
				if ( ! $bodyStarted && strpos( $strResponse, "\r\n\r\n" ) ) {
					$header_length = strpos( $strResponse, "\r\n\r\n" ) + 4;
					$bodyStarted = true;
				}
				$keep_reading = ( ! $bodyStarted || !isset( $r['limit_response_size'] ) || strlen( $strResponse ) < ( $header_length + $r['limit_response_size'] ) );
			}

			$process = WP_Http::processResponse( $strResponse );
			unset( $strResponse );

		}

		fclose( $handle );

		$arrHeaders = WP_Http::processHeaders( $process['headers'], $url );

		$response = array(
			'headers' => $arrHeaders['headers'],
			// Not yet processed.
			'body' => null,
			'response' => $arrHeaders['response'],
			'cookies' => $arrHeaders['cookies'],
			'filename' => $r['filename']
		);

		// Handle redirects.
		if( apply_filters('http_api_redirect', true)){ //Added by WHMCS Integration
			if ( false !== ( $redirect_response = WP_Http::handle_redirects( $url, $r, $response ) ) )
				return $redirect_response;
		}

		// If the body was chunk encoded, then decode it.
		if ( ! empty( $process['body'] ) && isset( $arrHeaders['headers']['transfer-encoding'] ) && 'chunked' == $arrHeaders['headers']['transfer-encoding'] )
			$process['body'] = WP_Http::chunkTransferDecode($process['body']);

		if ( true === $r['decompress'] && true === WP_Http_Encoding::should_decode($arrHeaders['headers']) )
			$process['body'] = WP_Http_Encoding::decompress( $process['body'] );

		if ( isset( $r['limit_response_size'] ) && strlen( $process['body'] ) > $r['limit_response_size'] )
			$process['body'] = substr( $process['body'], 0, $r['limit_response_size'] );

		$response['body'] = $process['body'];

		return $response;
	}
}