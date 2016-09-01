<?php
/*
Shellbot 0.9a by Tom Ashley

The following variables need to be set before execution:
$host, $port, $nick, $nickpass, $chan, $shserver, $shuser, $shpass

This script requires net_smartirc (included) and the ssh2 pear module.

execute with: php shellbot.php

Once shellbot has joined the chat, any message prefixed with # will be
forwarded to the shell. Example: # ps x
send % to the room for % command help
*/

include_once('Net/SmartIRC.php');

class shellbot
{

function shelly(&$irc, &$data) {
  global $shell;
  if(substr($data->message, 0, 2) == "# ") {
    fwrite($shell, substr($data->message, 2) . "\n");
    $result = "";
    sleep(1);

    $linenum = 1;
    $bytes = 0;
    $ircdbuffer = 1400;

    while($line = fgets($shell)) {
      flush();
      if ($linenum <> 1) {
        //$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $line);
        $sleeptime = strlen($line);
        $irc->setSenddelay($sleeptime * 200 + ($linenum * 2));
        //$bytes = $bytes + strlen($line);
        //echo $bytes . "\n";
        //if ($bytes >= $ircdbuffer) {
        //  echo "Starting Pause ... " . date('l dS \of F Y h:i:s A');
        //  $irc->setSenddelay(250 * strlen($line));
        //  sleep(2);
        //  $bytes = 0;
        //  echo "Finished Pause ... " . date('l dS \of F Y h:i:s A');
        //}
        $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $line);
      }
      $linenum++;
    }
  
  }

  if(substr($data->message, 0, 1) == "%") {
    $key = substr($data->message, 1, 1);
    switch($key) {
    case "C":
      $folder = "ctrl/";
      $keycode = "CTRL";
      break;

    case "T":
      $folder = "";
      fwrite($shell, chr(3));
      $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "TEST was sent");
      break;

    case "E":
      $folder = "";
      fwrite($shell, "\n");
      $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "/me has sent ENTER");
      break;
    
    default:
      $folder = "";
      $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "Correct Usage of %:");
      $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "%C <key> for CTRL keys");
      $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "%F <num> for F keys");
      $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "%A <key> for ALT keys");
      $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "%E for ENTER");

    }

    if ($folder <> "") {
      $char = substr($data->message, 3);
      $handle = @fopen("keys/" . $folder . $char, "r");
      if ($handle) {
        while (!feof($handle)) {
          $buffer = fgets($handle, 4096);
          fwrite($shell, $buffer);
        }
        fclose($handle);
        $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "/me has sent " . $keycode . " + " . $char);
      } else {
        $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "/me did not send " . $keycode . " + " . $char);
      }

    while($line = fgets($shell)) {
      flush();
      $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $line);
    }

    }
  }
}

}

$host = "irc.ircserver.com";
$port = 6667;
$nick = "botnick";
$nickpass = "botpass";
$chan = "#chantojoin";
$shserver = "sshserver.com";
$shuser = "sshuser";
$shpass = "sshpass";
    
$bot = &new shellbot( );
$irc = &new Net_SmartIRC( );
$irc->setUseSockets( TRUE );
$irc->registerActionhandler( SMARTIRC_TYPE_CHANNEL,'', $bot, 'shelly' );
$irc->connect( $host, $port );
$irc->login( $nick, 'ShellBot', 0, $nick );
$irc->join( array( $chan ) );
$irc->message(SMARTIRC_TYPE_QUERY, 'nickserv', "identify" . $nickpass);

//connect to ssh
if (!($con=@ssh2_connect($shserver, 22))) {

$irc->message(SMARTIRC_TYPE_CHANNEL, '#linux', "Connection to " . $shserver . " failed ... Killing ShellBot");
 
die();
}
$irc->message(SMARTIRC_TYPE_CHANNEL, '#linux', "/me is now connected to " . $shserver . " as: " . $shuser);
ssh2_auth_password($con, $shuser, $shpass);
$shell = ssh2_shell($con, 'xterm');

// fwrite($shell, "unalias ls\n");
// fwrite($shell, "/bin/rbash --rcfile ~/.shellbotrc\n");

while($line = fgets($shell)) {
  flush();
  echo($line . "\n");
  //$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $line);
}


$irc->listen( );
$irc->disconnect( );

?>
