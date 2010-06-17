<?
/*

3G Phone download database generator
###########################################################################
Copyright (C) 2003 by Harper Reed 
web: http://www.3gcoding.com
email: 3g@nata2.org
###########################################################################



This is my attempt at putting all of the knowledge i used in the creation of
the first to market website 3gScreensaver.com and A500hacking.com into a
project that everyone can enjoy and benefit from.

This project is basically a php script that you can drop into ANY directory 
and have any of the appropriate content served to your phone. Any php hosting site with access to the mail
function can do this. 

***This is a beta version***

there arn't many bugs as far as i can tell but that doesn't mean anything

send feedback to 3g@nata2.org

###########################################################################
LICENSE

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License (GPL)
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

To read the license please visit http://www.gnu.org/copyleft/gpl.html

###########################################################################
*/


//Variables for you to change

$sitename = "3GDemon"; // site name
$description = "3G Phone download database generator"; // description of site;
$contentdir = "./"; // where are the files located.  this should remain set to "." to
//insure that files somewhere else on your serve's HD are not inadvertently
//served.

$fileMissingError = "No Files Available"; // the error message to give when no files are available
$numrows = 2; //number of rows to display the files in. helpful if you have a bundle of files
$truncsize = 20;// the size the display of the file name is truncated.
$smsgatewayaddress="messaging.sprintpcs.com"; // the server that is appended to
//the cell number to create the SMS gateway. change this if you use bell canadian
//or whatever
$phonemsg="Extract This!!"; // the message your phone gets

//file types
//    extension     content type
$files['png'] = "image/png";
$files['jpg'] = "image/jpeg";
$files['cmx'] = "application/x-pmd";
$files['pmd'] = "application/x-pmd";
$files['qcp'] = "audio/vnd.qcelp";
$files['mid'] = "audio/midi";











###########################################################################

// This is the truncate function
// it truncates the filename display to insure a static look
function truncate ($string,$maxlength) { if (strlen($string)<=$maxlength) {
		return $string; } else { return substr($string,0,($maxlength-1))."..."; }
		}

//this is the get file extension function
//it gets the file extension ;)
function get_file_ext( $filename ) {
      ereg( ".*\.([a-zA-z0-9]{0,5})$", $filename, $regs);
      return( strtolower($regs[1]) );
}

// this sets up the mimtype regex
function getMediaRegex(){
		global $files;
$extensions = array_keys ($files);
$rgx = "/(";
for ($i=0;$i<count($extensions);$i++){
		$rgx .= $extensions[$i] ;
		if ($i != (count($extensions)-1)) $rgx .= "|";
}
$rgx .= ")/";
return $rgx;
}
//this gets the mime type
function getMIMEtype($url)
{
global $files;
$ext= get_file_ext($url);
$filetype = $files[$ext];
return $filetype;
}


//a really cheesy function that can tell wether the client is a phone or not. 
function is_phone(){
                $headers= @getallheaders();
                if ($headers["Accept"]!="") $accept =  $headers["Accept"];
                if ($headers["accept"]!="") $accept =  $headers["accept"];
                 if ($headers["ClientID"]!="") return 1;
                if ((preg_match("/gcd/",$accept))&&(preg_match("/wap/",$accept)))
                                return 1;
                                else
                                                return 0;
}


// if $d equals something then the script starts a download.. otherwise display
// the filelisting
if ($d==""){

//start the session
// the session is used to manage the phone number and email address that the
// OTA delivery uses. 
session_start();

// set the cookie for storing the phone and email for the OTA delivery 
if ($nameset == "1") {
		setcookie("DemonInfo[phone]", $phone);
		setcookie("DemonInfo[email]", $email);
		header("Location: $PHP_SELF");
}
//blows the  cookie away
if ($nameset == "0") {
		setcookie("DemonInfo[phone]", "");
		setcookie("DemonInfo[email]", "");
		header("Location: $PHP_SELF");
}

// begin of the HTML shite
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang='en' xml:lang='en' xmlns='http://www.w3.org/1999/xhtml'>
<head>
<? 
//if you define the cssfile var - you can theoretically change how the page
//looks. i have yet to really make this work. i will soon though.
if(!$cssfile) { ?>
<style type="text/css">
body{
		        font-family: Verdana, Helvetica, sans-serif;
				font-size:9pt;
}
a{color:#000000;font-color:#000000;	text-decoration: underline;}
a.preview{font-color:#000000; color: #000000;text-decoration: none;}
a.preview:hover{font-color:#000000; text-decoration: line-through;}
a.send{font-color:#000000; color: #192837;text-decoration: none;}
a.send:hover{font-color:#000000; text-decoration: line-through;}
a:hover{color:#555ccc; font-color:888888; text-decoration: none;}
input{background:#bbbbbb; border-color:#444444; border:1px; border-style: solid; font-size:8pt;}
.header{color:888888; font-color:888888;font-size: 24pt; font-weight:bold;}
.copy{color:888888; font-color:888888;font-size: 7pt; }
DIV.phoneinput {
			float:right;
			background: #888888;
			BORDER-RIGHT: #000 2px solid;
			BORDER-top: #000 2px solid;
			BORDER-bottom: #000 2px solid;
			BORDER-left: #000 2px solid;
}
DIV.title {
		float:left;
}
DIV.filelist {
		TOP:100px;
		left:10px;
		float:left;
		POSITION: left;
}
DIV.copyright {
		BOTTOM: 1px; POSITION: absolute; align:center;
}

</style>
<?

}
else
{
}


// the following is the guts of the html and such
?>
<title>.:: <?=$sitename?> ::.&nbsp;&nbsp;&nbsp;   <?=$description?> </title>
</head>
<body bgcolor="#505050">

<div class="title">
<font class="header"><a href="<?=$PHP_SELF?>" style="text-decoration:none; color:000000;"><?=$sitename?></a></font><br>
<?=$description?>
</div>


<?
//this is the section that sets the email and phone for the OTA delivery.

?>
<div class="phoneinput">
<table>
<form method="post" action="<?=$PHP_SELF?>" name="emailform">
<tr><td align="left">Phone:</td><td><input  type="text" name="phone" value="<?=$DemonInfo[phone];?>"></td>
<td><input  type="submit" value="Set!"></td></tr>
<tr><td align="left">Email:</td><td><input type="text" name="email" value="<?=$DemonInfo[email];?>"></td>
<td><input  type="reset" onClick="document.location='<?=$PHP_SELF?>?nameset=0'" value="clear">
</td></tr></table>
<input type="hidden" value="1" name="nameset">
</form>
</div>


<?
//This is the filelist section
?>
<div class="filelist">
<?
//this sets which directory to open. 
if ($handle = @opendir($contentdir)) {

//while their is content in the dir. 
while ($file = readdir($handle))
{
		//sets the file types that can be displayed
		if ($file != "." && $file != ".." && preg_match(getMediaRegex(),$file))
        $retVal[count($retVal)] = $file;
}

//Clean up and sort
closedir($handle);

//if no files are available tell the user
if (count ($retVal)==0){
echo $fileMissingError;
}else {
//sorts the files in soem order ;)
sort($retVal);
// set the number of files in dir
$numitems = count($retVal);
//sets number of items in each row
$numinrow = round($numitems/$numrows);
//set up content dir
$sPath = $contentdir;
//$DEBUG=1; //debug info....  
if ($DEBUG){
echo "<table border=\"1\" bgcolor=\"ffffff\"><tr><td>Num items:$numitems<br>Num in each row:$numinrow </td></tr></table>";
}
//return $retVal;
echo "<b>$message</b>";
?>
<table bgcolor="000000" border="0"><tr><td align="left" valign="top">
<?
//top of the file display. doesn;t really have to be echod.. heh. whatever
echo "<table border=\"0\" cellspacing=\"1\" cellpadding=\"0\" ><tr><td bgcolor=\"222222\"><font color=\"ffffff\">&nbsp;Title&nbsp;</font></td><td bgcolor=\"222222\"><font color=\"ffffff\">&nbsp;Size&nbsp;</font></td><td bgcolor=\"222222\"><font color=\"ffffff\">&nbsp;Type&nbsp;</font></td><td bgcolor=\"222222\"><font color=\"ffffff\">&nbsp;Action&nbsp;</font></td></tr>";


// this is getting ready for a paginated display
// not quite done yet. but soon.
if ($beg =="")$beg=0;
if ($end =="")$end=$numitems;
for ($y=$beg;$y<=$end;$y++){
$filelistdata[$y]=$retVal[$y];
}
//startsparsing through the file list
$x=0;
while (list($key, $val) = each($filelistdata))
{
		// checks if it is a kosher file types
if ($val != "." && $val != ".." && preg_match(getMediaRegex(),$val))
{
$x++;
//make the path 
$path = str_replace("//","/",$sPath.$val);
//set up the path the user sees
$disppath = preg_replace(getMediaRegex(),"",$val);
$disppath = str_replace(".","",$disppath);
//gets the file size
$size = filesize($sPath.$val);  
//make it readable
$size = round($size/1024);
//change _ int space
$disppath = str_replace("_","&nbsp;",$disppath);
//truncate the display path to a set size
$disppath2 =  truncate($disppath,$truncsize);
//set every other row a different color
if (($x%2)==0)$color="888888"; else $color="aaaaaa";
//more debug info
if ($DEBUG) $debugcount="#$x:&nbsp;";
//out put the table section that has all the file info. Include a preview part
//and a display part. also include the link that sends it to the
//phone
echo "<tr><td bgcolor=\"$color\">$debugcount&nbsp;<a class=\"preview\" href=\"$path\" title=\"$disppath\">$disppath2</a>&nbsp;</td><td bgcolor=\"$color\">&nbsp;".$size."k&nbsp;</td><td  bgcolor=\"$color\">&nbsp;".get_file_ext($path)."&nbsp;</td><td bgcolor=\"$color\"><b>[</b>&nbsp;<a href=\"?d=$val\" class=\"send\">send</a>&nbsp;<b>]</b></td></tr>";
//make columns
if ((($x%$numinrow)==0)&&($x <$numitems)) echo
		"</table></td><td valign=\"top\"><table border=\"0\" cellspacing=\"1\" cellpadding=\"0\"><tr><td bgcolor=\"222222\"><font color=\"ffffff\">&nbsp;Title&nbsp;</font></td><td  bgcolor=\"222222\"><font color=\"ffffff\">&nbsp;Size&nbsp;</font></td><td bgcolor=\"222222\"><font color=\"ffffff\">&nbsp;Type&nbsp;</font></td><td	bgcolor=\"222222\"><font color=\"ffffff\">&nbsp;Action&nbsp;</font></td></tr>";
}
}
}
echo "</table>";
?>
</td></tr></table>
</td></tr>
<tr><td align="right" >
<?
//soon this will help with the pagination 

//echo "next";
?>
</td></tr></table>
<?
}
?>
</div>

<div class="copyright">
<center>
<font class="copy">
<br><small><b>Powered by <a href="http://3gcoding.com">3GPddg</a>. <br>A <a href="http://www.nata2.com">NaTa2.COM</a> Project.<br><br>
</center></div>
<b1><!--x5_0YUwZeNody0EOgCAMBdEbUUgT9SBeAOELGygpNV5fw2beaqh1ozNqgflAzGHbF4dnmvbct6tRB1SB7EQLvbgoSTf8X5IM4pLRpNOqG3XMD39IHMA=--></b1></body>
</html>
<?
}
else {
		//set thedownload flag to be the filename
$name = $contentdir.$d;
//get the size of the file
$size = filesize($name);
//get the server name and all that jazz.. i just found a better way to do this
//and will implement it in the next release
ereg( "(.*\/)([a-zA-z0-9])(.*\.)([a-zA-z0-9]{0,5})$", $PHP_SELF, $regs);
//set up the url
$url = "http://".$SERVER_NAME . $regs[1] . $name;
$site_url = "http://".$SERVER_NAME . $regs[1];
//set upo the gcdurl
$gcdurl = "http://".$SERVER_NAME . $regs[0] ."?".$QUERY_STRING;
//set the phone number that this sms message is going to
$phone = $DemonInfo[phone];
//put the sms address together
$to="$phone@$smsgatewayaddress";
//set the email to throw the bounce message in case it all breaks
$email="$DemonInfo[email]";
//$to=$email;
//test if this is a phone 
if (is_phone()) {
//if it is a phone then start to build the GCD for the phone to use to download
$phone_sitename = truncate(ereg_replace(" ","_",$sitename),20);
if (preg_match("/image/",getMIMEtype($url))) $id = "$phone_sitename/image"; else $id = "$phone_sitename/audio";
//set header so phone can handle the GCD file
header("Content-Type: text/x-pcs-gcd");
//Set the content type so the phone knows what to do withthe file once it is
//downloaded
echo "Content-Type: ".getMIMEtype($url)."\n".
//set the file name on the phone
"Content-Name: ".ereg_replace(" ","_",$d)."\n".
//set the contentID
"Content-ID: $id\n".
//Set the GCD version
"Content-Version: 1.0\n".
//Set the site url to be the storfront for the GCD
"Content-Storefront-URL: $site_url\n".
//set the info URL
"Content-Info-URL: $phone_sitename\n".
//set the folder that the files all go into
"Content-Folder: $phone_sitename\n".
// set the vendor that the files are coming from
"Content-Vendor: $phone_sitename\n".
//set the URL that the file is located at
"Content-URL: $url\n".
//and set the size of the file
"Content-Size: $size\n";
}else {
		//start the session to pull the cookie info(phone and email)
		session_start();
		//check if the cookie has the correct info in it
		if (($DemonInfo[phone]=="")||($DemonInfo[email]=="")) header("Location: ".$PHP_SELF."?message=Please+enter+your+email+and+phone+number");else{
		//send the mail to the phone
		//set up the from
		$from = "From: $sitename <".ereg_replace(" ","_",$sitename)."@".$SERVER_NAME.">\r\n";
		//send the mail
		mail($to,$phonemsg, $gcdurl, $from."Return-Path:  <$email>\r\n");
		//go back to the home page
		header("Location: ". $PHP_SELF. "?message=$d+has+been+sent+to+$phone");
		}
}




}
?>