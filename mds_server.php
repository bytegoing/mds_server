#! /usr/bin/env php
<?php
/**
 * 用于黑莓断续膏进行断网修复
 * 
 *  !需要开启pcntl, libevent扩展
 * 
 * 使用方法:
 * 
 * #chmod a+x mds_server.php
 * #./mds_server.php 22.22.22.22 54321
 * 
 * @author bit.kevin@gmail.com
 * @since 2011-01
 * 
 */

/* Run as Deamon */
$pid = pcntl_fork();
if ($pid == -1) {
	die("could not fork");
} else if ($pid) {
	exit(); // we are the parent
} else {
	// we are the child
}

// detatch from the controlling terminal
if (posix_setsid() == -1) {
	die("could not detach from terminal");
}
/* /Run as Deamon */


//TODO check libevent extesion

/* config */
$_config = array();
$_config['host'] = '0.0.0.0';
$_config['port'] = 54321;




/* argv */
if( !empty($argv[1]) ) {
	$_config['host'] = trim($argv[1]);
}
if( !empty($argv[2]) ) {
	$_config['port'] = intval($argv[2]);
}


//create socket
$server_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);//udp
//bind
socket_bind($server_socket, $_config['host'], $_config['port']); 


$event_base = event_base_new();
$event_fd = event_new();
event_set($event_fd, $server_socket, EV_WRITE | EV_PERSIST, 'handle_request', array($event_fd, $event_base));
event_base_set($event_fd, $event_base);
event_add($event_fd);
event_base_loop($event_base);



/**
 * 处理链接，接受数据，写回数据
 * 
 * @param res $server_socket
 * @param res $events
 * @param mixed $arg
 */
function handle_request($server_socket, $events, $arg)
{
	//recive data from client
	socket_recvfrom($server_socket, $data, 18, 0, $c_host, $c_port);
	
	//unpack二进制
	$data_arr = unpack("C*", $data);
	//pack
	$binarydata = pack("C*", 16, 8, 0, 0, 0, 0, $data_arr[3], $data_arr[4], $data_arr[5], $data_arr[6], $data_arr[11]-128);
	
	//send to client
	socket_sendto($server_socket, $binarydata, 11, 0x100, $c_host, $c_port);
	
	$pin = get_pin($data_arr);
	//测试模拟程序发出的PIN为FFFFFFFF，若为此则不记录LOG
	if( $pin != "FFFFFFFF" ) {
		echo '['.date('Y-m-d H:i:s').']'."\tPIN:{$pin}\t$c_host:$c_port\n";
	}
}





/**
 * 获取PIN码
 * 
 * @param array $data_arr
 * @return string
 */
function get_pin($data_arr)
{
	return strtoupper(''.dechex($data_arr[3]).dechex($data_arr[4]).dechex($data_arr[5]).dechex($data_arr[6]));
}
