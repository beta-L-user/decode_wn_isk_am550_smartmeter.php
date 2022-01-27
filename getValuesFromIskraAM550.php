<?php

include "php_serial.class.php";
// Let's start the class
$serial = new phpSerial();
// First we must specify the device. This works on both Linux and Windows (if
// your Linux serial device is /dev/ttyS0 for COM1, etc.)

$serial->deviceSet("/dev/ttyUSB0");

// Set for 9600-8-N-1 (no flow control)
$serial->confBaudRate(9600); //Baud rate: 9600
$serial->confParity("none");  //Parity (this is the "N" in "8-N-1")
$serial->confCharacterLength(8); //Character length     (this is the "8" in "8-N-1")
$serial->confStopBits(1);  //Stop bits (this is the "1" in "8-N-1")
$serial->confFlowControl("none");

//  open it
$serial->deviceOpen();
$serial->deviceClose();
// ---------------------------------------------------------------------------
// script vorgesehen fÃ¼r Iskraemeca-AM550-Lesen: 
// AM 550 sendet in ein Sekunden-AbstÃ¤nden!
//

// following code taken from pocki
# use this to decode WienerNetze Smartmeter ISKRAEMECO AM550 HDLC packets from your infrared interface
# example key and data is designed to decipher as real example

#paste your AES-key here, taken from WienerNetze Webportal https://www.wienernetze.at/wnapp/smapp/ -> Anlagedaten
$key='8F67AFBA0409A4497A364C9F1A6F0EC1';

#paste a full HDLC frame here: starting 7ea067 ending 7e, should be 210 hex digits = 105 bytes = 0x69 -> length byte 3 shows 0x67
//$data='7ea067cf022313fbf1e6e700db0849534b68745865df4f200040f69b4c6d16f58134475ab03072a8c00a97c479e83f26bafe2f7c4b5b815ab3708dd851690a21171a0e49e564494c8428f02847a555ac906c0ab3d445c559308fd3744177db1778f26852c2e1d5477e';


// -----------------------------------------------------------------
// Namen fÃ¼r Datei, Variablen initialisieren, Verzeichnis anlegen
// -----------------------------------------------------------------
$readChar = "";
$line = "";

$startpacket = chr(0x7e).chr(0xa0).chr(0x67); 	// begin of package
$endletter = chr(0x7e); 			// end of package

$terminal_device = '/dev/ttyUSB0';

$path = '/var/www/html/11_AM550/';

$dateiname = "energyV.csv"; // Name der Log-Datei

$data="";
$bn = "\r\n"; // NÃ¤chste Zeile und Zeilenbeginn - fÃ¼r Ausgabe
$packet = "";

while (true) { // ENDLOSSCHLEIFE !! muss mit kill beendet werden

	$loopflag = true;

// -----------------------------------------------------------------
// Hauptschleife
// -----------------------------------------------------------------
	$path1 = date('Y');
	if (!is_dir($path.$path1)) {
		mkdir($path.$path1);
	}
	$path2 = date('m');
	if (!is_dir($path.$path1."/".$path2)) {
		mkdir($path.$path1."/".$path2);
	}
	$my_save_dir=$path1."/".$path2."/";	
	$datename = date('Y-m-d_');

	$h = fopen($terminal_device, 'r');
	$packet="";

// following while-loop searches for the header
	while (strpos($packet,"7ea067",0) === false) {
		$readChar = fgetc($h);
		$myhex = dechex(ord($readChar));
		if (strlen($myhex) == 1) {
			$myhex = "0".$myhex ; 
		}	
		$packet .= $myhex;
		// echo "_".(substr($packet,0,2) == "7e")."_" ;
		if (substr($packet,0,2) != "7e") {
			$packet="";
		} else {
			//$packet .= $myhex;
		}
echo strlen($packet)." ";
	}
	$packet = substr($packet,strpos($packet,"7ea067",0),6);
// following while-loop searches for the rest of the package	
	while ($loopflag){ 
		$readChar = fgetc($h);
		$myhex = dechex(ord($readChar));
		if (strlen($myhex) == 1) {
			$myhex = "0".$myhex ; 
		}	
		if ((strlen($data) == 208) ) { 
			$data=$packet.$myhex;
			//$packet = "7e";
			$loopflag=false;
		} else {
			$packet .= $myhex;
			$data = $packet;			
		}
	}
	fclose($h);

	$loopflag=true;
	echo strlen($data).$bn;

#prepare /thx to pocki!!
	$st=substr($data, 28,16);
	$ic=substr($data, 48, 8);
	$iv=$st.$ic.'00000002';
	$enc=substr($data, 56, 148);

#decode /thx  to pocki!!
	$dec=bin2hex(openssl_decrypt(hex2bin($enc), 'aes-128-ctr', hex2bin($key), 1, hex2bin($iv)));

	$crc1 = substr($data, 14, 4); // crc16 within the data packet
	$crc2 = substr($data, 204, 4); // crc16 within the data packet
	echo "in data:".$crc1." ".$crc2."\r\n";
	$crc3 = dechex(calc_crc16(hex_to_str(substr($data,2,12)))); //calculated to $crc1
	$crc4 = dechex(calc_crc16(hex_to_str(substr($data,2,202)))); //calculated to $crc2

	if ($crc1 != $crc3) { // output only if crc2=crc4 ok ------------------------
		echo "------------------------------ header ------";
	}
	if ($crc2 == $crc4) { // output only if crc2=crc4 ok ------------------------
		echo "in calc:".$crc3." ".$crc4."\r\n";
#parse
		$a=substr($dec, 70, 8);
		$b=substr($dec, 80, 8);
		$c=substr($dec, 90, 8);
		$d=substr($dec,100, 8);
		$e=substr($dec,110, 8);
		$f=substr($dec,120, 8);
		$g=substr($dec,130, 8);
		$h=substr($dec,140, 8);

		$ap=hexdec($a)/1000;
		$am=hexdec($b)/1000; 
		$rp=hexdec($c)/1000 ;
		$rm=hexdec($d)/1000 ;
		$pp=hexdec($e) ;
		$pm=hexdec($f) ;
		$qp=hexdec($g) ;
		$qm=hexdec($h) ;

#output
/*
		print "+A: ".$ap . "kWh".$bn;
		print "-A: ".$am . "kWh".$bn;
		print "+R: ".$rp . "varh".$bn;
		print "-R: ".$rm . "varh".$bn;
		print "+P: ".$pp . "W".$bn;
		print "-P: ".$pm . "W".$bn;
		print "+Q: ".$qp . "var".$bn;
		print "-Q: ".$qm . "var".$bn;
*/

// ---------------------------------------------------------------------
//  Schreiben der Daten als Textdatei; leichter lesbar als json oder DB 
// ---------------------------------------------------------------------
//
		$myTi=date('Y-m-d H:i:s');
		$handler=fOpen($path.$my_save_dir.$datename.$dateiname , "a+");
		fputs($handler, "$myTi;$ap;$rp;$pp;$qp;$am;$rm;$pm;$qm \n");
		fClose($handler);
	}
}
function byte_mirror($c) {
    $a1=0xF0;
    $a2=0x0F;
    $a3=0xCC;
    $a4=0x33;
    $a5=0xAA;
    $a6=0x55;
    $c=((($c&$a1)>>4)|(($c&$a2)<<4));
    $c=((($c&$a3)>>2)|(($c&$a4)<<2));
    $c=((($c&$a5)>>1)|(($c&$a6)<<1));
    return $c;
}

// ------------------------------------------------------------------------
// the following code ist from pocki written in Python and transfered to php 
// ------------------------------------------------------------------------
function calc_crc16($data) {
	$crc_init=0xFFFF;
	$polynominal=0x1021;
	$crc=$crc_init;
	for ($i=0;$i<strlen($data);$i++) {
		$myByte=hexdec(str_to_hex(substr($data,$i,1)));
 		$c=byte_mirror($myByte)<<8;
		for ($j=0;$j<8;$j++) {
			$crc=((($crc^$c)&0x8000)?($crc<<1)^$polynominal:$crc<<1)%65536;
		    $c=($c<<1)%65536;	    
		}
	}
	$crc=$crc%65536;
	$crc=0xFFFF-$crc;
    return 256*byte_mirror(intdiv($crc,256))+  byte_mirror($crc%256);
}

// ------------------------------------------------------------------------
// following code transferred from pocki from Python to php, but not proofed
// ------------------------------------------------------------------------
function verify_crc16($input, $skip=0, $last=2, $cut=0) {
    $lenn=len($input);
    $data=substr(input,$skip,$lenn-$last-$cut);
    $goal=substr(input,$lenn-$last-$cut,$lenn-$cut);
echo $goal;
    if ($last == 0){
	return hex($calc_crc16($data));
    } elseif  ($last == 2) {
    	return calc_crc16($data)==goal[0]*256 + goal[1];
    } else {
	return false;
    }
}
function str_to_hex($string) {
	$hexstr = unpack('H*', $string);
	return array_shift($hexstr);
}
function hex_to_str($string) {
	return hex2bin("$string");
}

?>
