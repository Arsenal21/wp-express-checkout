<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

class Utils_Downloads
{
    /**
     * Detects whether the file is a local file or not by checking file url and if the file exists.
     * Warning: Assumes $src_file_url is at, or below, the server's document root directory. If the $src_file_url is
     * outside the scope of the server's document root directory, a FALSE value will be returned.
     *
     * @param string $src_file_url
     * @return boolean
     */
    public static function is_local_file_url($src_file_url)
    {
        //Logger::log('Determining if the file is local or remote.');
        if (preg_match("/^http/i", $src_file_url) != 1) {
            return false;
        }

        // Not a qualified URL.
        $wpurl = get_bloginfo('wpurl'); // iGet WP root URL1 and directory.
        // Calculate position in $src_file_url just after the WP root URL and directory.
        $wp_root_pos = stripos($src_file_url, $wpurl);

        if ($wp_root_pos !== false && file_exists(self::absolute_path_from_url($src_file_url))) {
            //Logger::log('Target download file is a local file url.');
            return true;
        }

        Logger::log('Target download file is not a local file url.');
        return false;
    }

	public static function is_local_file_path($src_file_path){
		//Logger::log('Determining if this is a local file path or not.');
		if (preg_match("/^http/i", $src_file_path) == 1) {
            //This is a URL, not a local file path.
			return false;
		}

		if (file_exists($src_file_path)){
			Logger::log('Target download file is a local file path.');
			return true;
		}

		Logger::log('Target download file is not a local file path.');
		return false;
	}

    /**
     * Converts $src_file_url into an absolute file path, starting at the server's root directory.
     * Warning: Assumes $src_file_url is at, or below, the server's document root directory. If the $src_file_url is
     * outside the scope of the server's document root directory, a FALSE value will be returned.
     * FALSE is also returned if $src_file_url is not a qualified URL.
     * -- The Assurer, 2010-10-06.
     *
     * @param string $src_file_url File URL
     * @return string Absolute file path
     */
    public static function absolute_path_from_url($src_file_url)
    {
        //Logger::log('Converting URL to absolute file path.');
        if (preg_match("/^http/i", $src_file_url) != 1) {
            return false;
        }
        // Not a qualified URL.
        $domain_url = $_SERVER['SERVER_NAME']; // Get domain name.
        $domain_url_no_www = str_replace("www.", "", $domain_url);
        $absolute_path_root = $_SERVER['DOCUMENT_ROOT']; // Get absolute document root path.

        // Calculate position in $src_file_url just after the domain name.
        $domain_name_pos = stripos($src_file_url, $domain_url);
        if ($domain_name_pos === false) {
            Logger::log("No direct domain URL match found in the source file.", false);
            $file_on_this_domain = stripos($src_file_url, $domain_url_no_www);
            if ($file_on_this_domain === false) {
                Logger::log("The source file is hosted externally, not on this domain.", true);
                return false;
            }
            //Lets try another method of conversion
            Logger::log("Trying the secondary URL conversion method.");
            $path = parse_url($src_file_url, PHP_URL_PATH);
            $abs_path = $absolute_path_root . $path;
            //$abs_path = ABSPATH.$path;//another option
            $abs_path = str_replace('//', '/', $abs_path);
            return $abs_path;
        }
        $domain_name_length = strlen($domain_url);
        $total_length = $domain_name_pos + $domain_name_length;
        // Replace http*://SERVER_NAME in $src_file_url with the absolute document root path.
        return substr_replace($src_file_url, $absolute_path_root, 0, $total_length);
    }

    /**
     * Returns file_exists($file_path) and if necessary, writes appropriate ADVISORY and WARNING messages to the debugger log file.
     *
     * @param string $file_path Target file path
     * @return boolean Return failed file_exists() status
     */
    public static function dl_file_exists($file_path)
    {
        if (file_exists($file_path) === true) {
            return true;
        }

        Logger::log("Invalid URL conversion target: " . $file_path . ". Forcing 'Do Not Convert' option.", true);
        return false;
    }

    /**
     * Returns the size, in bytes, of a file whose path is specified by a URI.  If the URI is a qualified URL and cURL is not
     * installed on the server, a string of "unknown" is returned.  Note: We use "URI" instead of "URL" because this is not
     * necessarily an HTTP request.
     *
     * @param string $uri
     * @param string $user
     * @param string $pw
     * @return string File size. Return "unknown" if no information available.
     */
    public static function dl_filesize($uri, $user = '', $pw = '')
    {
        if (preg_match("/^http/i", $uri) != 1) {
            // Not a qualified URL...
            $retVal = @filesize($uri); // Get file size.
            if ($retVal === false) {
                $retVal = 'unknown';
            }
            // Whitewash any stat() errors.
            return $retVal; // Return local file size.
        }
        if (!function_exists('curl_init')) {
            return 'unknown';
        }
        // If cURL not installed, size is "unknown."
        $ch = curl_init($uri); // Initialize cURL for this URI.
        if ($ch === false) {
            return 'unknown';
        }
        // Return "unknown" if initialization fails.
        curl_setopt($ch, CURLOPT_HEADER, true); // Request header in output.
        curl_setopt($ch, CURLOPT_NOBODY, true); // Exclude body from output).
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return transfer as string on curl_exec().
        // if auth is needed, do it here
        if (!empty($user) && !empty($pw)) { // Set optional authentication headers...
            $headers = array('Authorization: Basic ' . base64_encode($user . ':' . $pw));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $header = curl_exec($ch); // Retrieve the remote file header.
        if ($header === false) {
            return 'unknown';
        }
        // Return "unknown" if header could not be retrieved.
        // Parse the remote file header for the content length...
        if (preg_match('/Content-Length:\s([0-9].+?)\s/', $header, $matches) == 1) {
            return $matches[1]; // Return remote file size.
        } else {
            return 'unknown'; // Return "unknown" if no information available.
        }
    }

    /**
     * Attempts to convert $src_file_url into either a relative path, or an absolute path, if possible.  If no
     * conversion is possible, returns $src_file_url.  Reasons for why no conversion takes place are that
     * $src_file_url is not a qualified URL, or it is outside the scope of either the WP root directory or the
     * SERVER_NAME document root directory.  Also, trying to pass the path of a file, instead of its URL will
     * also result in a non-conversion.
     *
     * @param string $src_file_url File URL
     * @param string $conversion_type Path type to convert to
     *
     * @return string File path.
     */
    public static function url_to_path_converter($src_file_url, $conversion_type)
    {
        Logger::log('Trying to convert url to specified type of path.', true);
        switch ($conversion_type) {
                // Return conversion, based on conversion type...
            case 'absolute': // Absolute path...
                $absolute = self::absolute_path_from_url($src_file_url);
                if (($absolute != false) && self::dl_file_exists($absolute)) {
                    return $absolute;
                }
                break;

                // case 'relative': // Relative path...
                //     $relative = self::relative_path_from_url($src_file_url);
                //     if (($relative != false) && self::dl_file_exists($relative)) {
                //         return $relative;
                //     }
                //     break;
        }
        //If the preferred URL conversions failed, or if "No Conversion" was choosen, then return the original URL.
        Logger::log('URL to path conversion failed, returning default value.', false);
        return $src_file_url;
    }

    public static function download_using_fopen($file_path, $chunk_blocks = 8, $session_close = false)
    {
        Logger::log('Trying to dispatch file using fopen.', true);
        $file_name = basename($file_path);
        // Download methods #1, #2, #4 and #5.
        // -- The Assurer, 2010-10-22.
        $chunk_size = 1024 * $chunk_blocks; // Number of bytes per chunk.
        $fp = @fopen($file_path, "rb"); // Open source file.
        if ($fp === false) {
            // File could not be opened...
            return "Error on fopen('$file_path')"; // Catch any fopen() problems.
        }
        if ($session_close) {
            @session_write_close();
        }
        // Close current session, if requested.
        // Write headers to browser...
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        $mimetype = self::dl_file_type($file_path);
        header("Content-Type: " . $mimetype);
        header("Content-Disposition: attachment; filename=\"$file_name\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . self::dl_filesize($file_path));

        //Trigger an action hook so additional headers can be added from a 3rd party plugin or custom code.
        do_action('wpec_fopen_after_download_headers', $file_path);

        $chunks_transferred = 0; // Reset chunks transferred counter.
        while (!feof($fp)) {
            // Process source file in $chunk_size byte chunks...
            $chunk = @fread($fp, $chunk_size); // Read one chunk from the source file.
            if ($chunk === false) {
                // A read error occurred...
                @fclose($fp);
                return 'Error on fread() after ' . number_format($chunks_transferred) . ' chunks transferred.';
            }
            // Chunk was successfully read...
            print($chunk); // Send the chunk on its way.
            flush(); // Flush the PHP output buffers.
            $chunks_transferred += 1; // Increment the transferred chunk counter.
            // Check connection status...
            // Note: it is a known problem that, more often than not, connection_status() will always return a 0...  8(
            $constat = connection_status();
            if ($constat != 0) {
                // Something happened to the browser connection...
                @fclose($fp);
                switch ($constat) {
                    case 1:
                        return 'Connection aborted by client.';
                    case 2:
                        return 'Connection timeout.';
                    default:
                        return "Unrecognized connection_status(). Value: " . $constat;
                }
            }
        }
        // Well, we finally made it without detecting any server-side errors!
        @fclose($fp); // Close the source file.
        return true; // Success!
    }

    public static function download_using_xsend_file($file_path)
    {
        Logger::log('Trying to dispatch file using xsend file.', true);
        $file_name = basename($file_path);
        // Write headers to browser and send file using X-sendfile
        header('X-Sendfile: ' . $file_path);
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$file_name\"");

        //Trigger an action hook so additional headers can be added from a 3rd party plugin or custom code.
        do_action('wpec_xsend_file_after_download_headers', $file_path);
    }

    public static function download_using_curl($file_url)
    {
        Logger::log('Trying to dispatch file using cURL. The cURL method uses the file URL (it cannot use a local file path)', true);

        //Before we proceed, lets check if the file URL is accessible. 
		//The verify function will be used only when the file URL is used for the download. (not the local file-path).        
        View_Downloads::verify_file_url_accessible( $file_url );

        if (!function_exists('curl_init')) {
            $error_msg = __('cURL is not installed on this server. Cannot dispatch the download using cURL method.', 'wp-express-checkout');
            Logger::log($error_msg, false);
            // wp_die($error_msg);
            return $error_msg;
        }

        $output_headers = array();
        $output_headers[] = 'Content-Type: application/octet-stream';
        $output_headers[] = 'Content-Disposition: attachment; filename="' . basename($file_url) . '"';
        $output_headers[] = 'Content-Encoding: none';

        foreach ($output_headers as $header) {
            header($header);
        }

        //Trigger an action hook so additional headers can be added from a 3rd party plugin or custom code.
        do_action('wpec_curl_after_download_headers', $file_url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt( $ch, CURLOPT_WRITEFUNCTION, array( $this, 'stream_handler' ) );

        curl_exec($ch);
        curl_close($ch);

        return true;
    }

    public static function dl_file_type($filename)
    {
        // get base name of the filename provided by user
        $filename = basename($filename);

        // break file into parts seperated by .
        $filename = explode('.', $filename);

        // take the last part of the file to get the file extension
        $ext = $filename[count($filename) - 1];

        // find mime type
        return self::get_mime_type_form_ext($ext);
    }

    public static function get_mime_type_form_ext($ext)
    {
        // Mime types array
        $mimetypes = array(
            "ez" => "application/andrew-inset",
            "hqx" => "application/mac-binhex40",
            "cpt" => "application/mac-compactpro",
            "doc" => "application/msword",
            "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "bin" => "application/octet-stream",
            "dms" => "application/octet-stream",
            "lha" => "application/octet-stream",
            "lzh" => "application/octet-stream",
            "exe" => "application/octet-stream",
            "class" => "application/octet-stream",
            "so" => "application/octet-stream",
            "dll" => "application/octet-stream",
            "oda" => "application/oda",
            "pdf" => "application/pdf",
            "ai" => "application/postscript",
            "eps" => "application/postscript",
            "ps" => "application/postscript",
            "smi" => "application/smil",
            "smil" => "application/smil",
            "wbxml" => "application/vnd.wap.wbxml",
            "wmlc" => "application/vnd.wap.wmlc",
            "wmlsc" => "application/vnd.wap.wmlscriptc",
            "bcpio" => "application/x-bcpio",
            "vcd" => "application/x-cdlink",
            "pgn" => "application/x-chess-pgn",
            "cpio" => "application/x-cpio",
            "csh" => "application/x-csh",
            "dcr" => "application/x-director",
            "dir" => "application/x-director",
            "dxr" => "application/x-director",
            "dvi" => "application/x-dvi",
            "spl" => "application/x-futuresplash",
            "gtar" => "application/x-gtar",
            "hdf" => "application/x-hdf",
            "js" => "application/x-javascript",
            "skp" => "application/x-koan",
            "skd" => "application/x-koan",
            "skt" => "application/x-koan",
            "skm" => "application/x-koan",
            "latex" => "application/x-latex",
            "nc" => "application/x-netcdf",
            "cdf" => "application/x-netcdf",
            "sh" => "application/x-sh",
            "shar" => "application/x-shar",
            "swf" => "application/x-shockwave-flash",
            "sit" => "application/x-stuffit",
            "sv4cpio" => "application/x-sv4cpio",
            "sv4crc" => "application/x-sv4crc",
            "tar" => "application/x-tar",
            "tcl" => "application/x-tcl",
            "tex" => "application/x-tex",
            "texinfo" => "application/x-texinfo",
            "texi" => "application/x-texinfo",
            "t" => "application/x-troff",
            "tr" => "application/x-troff",
            "roff" => "application/x-troff",
            "man" => "application/x-troff-man",
            "me" => "application/x-troff-me",
            "ms" => "application/x-troff-ms",
            "ustar" => "application/x-ustar",
            "src" => "application/x-wais-source",
            "xhtml" => "application/xhtml+xml",
            "xht" => "application/xhtml+xml",
            "zip" => "application/zip",
            "au" => "audio/basic",
            "snd" => "audio/basic",
            "mid" => "audio/midi",
            "midi" => "audio/midi",
            "kar" => "audio/midi",
            "mpga" => "audio/mpeg",
            "mp2" => "audio/mpeg",
            "mp3" => "audio/mpeg",
            "m4a" => "audio/mp4",
            "aif" => "audio/x-aiff",
            "aiff" => "audio/x-aiff",
            "aifc" => "audio/x-aiff",
            "m3u" => "audio/x-mpegurl",
            "ram" => "audio/x-pn-realaudio",
            "rm" => "audio/x-pn-realaudio",
            "rpm" => "audio/x-pn-realaudio-plugin",
            "ra" => "audio/x-realaudio",
            "wav" => "audio/x-wav",
            "pdb" => "chemical/x-pdb",
            "xyz" => "chemical/x-xyz",
            "bmp" => "image/bmp",
            "gif" => "image/gif",
            "ief" => "image/ief",
            "jpeg" => "image/jpeg",
            "jpg" => "image/jpeg",
            "jpg_backup" => "image/jpeg",
            "jpe" => "image/jpeg",
            "png" => "image/png",
            "tiff" => "image/tiff",
            "tif" => "image/tif",
            "djvu" => "image/vnd.djvu",
            "djv" => "image/vnd.djvu",
            "wbmp" => "image/vnd.wap.wbmp",
            "ras" => "image/x-cmu-raster",
            "pnm" => "image/x-portable-anymap",
            "pbm" => "image/x-portable-bitmap",
            "pgm" => "image/x-portable-graymap",
            "ppm" => "image/x-portable-pixmap",
            "rgb" => "image/x-rgb",
            "xbm" => "image/x-xbitmap",
            "xpm" => "image/x-xpixmap",
            "xwd" => "image/x-windowdump",
            "igs" => "model/iges",
            "iges" => "model/iges",
            "msh" => "model/mesh",
            "mesh" => "model/mesh",
            "silo" => "model/mesh",
            "wrl" => "model/vrml",
            "vrml" => "model/vrml",
            "css" => "text/css",
            "html" => "text/html",
            "htm" => "text/html",
            "asc" => "text/plain",
            "txt" => "text/plain",
            "rtx" => "text/richtext",
            "rtf" => "text/rtf",
            "sgml" => "text/sgml",
            "sgm" => "text/sgml",
            "tsv" => "text/tab-seperated-values",
            "wml" => "text/vnd.wap.wml",
            "wmls" => "text/vnd.wap.wmlscript",
            "etx" => "text/x-setext",
            "xml" => "text/xml",
            "xsl" => "text/xml",
            "mpeg" => "video/mpeg",
            "mpg" => "video/mpeg",
            "mpe" => "video/mpeg",
            "mp4" => "video/mp4",
            "qt" => "video/quicktime",
            "mov" => "video/quicktime",
            "mxu" => "video/vnd.mpegurl",
            "avi" => "video/x-msvideo",
            "movie" => "video/x-sgi-movie",
            "xls" => "application/vnd.ms-excel",
            "epub" => "application/epub+zip",
            "mobi" => "application/x-mobipocket-ebook",
            "ice" => "x-conference-xcooltalk",
        );

        if (isset($mimetypes[$ext])) {
            // return mime type for extension
            return $mimetypes[$ext];
        } else {
            // if the extension wasn't found return octet-stream
            return 'application/octet-stream';
        }
    }
}
