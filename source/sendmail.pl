#!/usr/local/bin/perl
use strict;
use warnings;
#use File::Copy;
#use Tie::File;
#use GD; 
#use GD::Graph::lines; 
#use GD::Simple;
use Net::SMTPS;
use DBI;

use constant MTIME_STAT_INDEX => 9;
use constant FILENAME_INDEX => 0;
use constant MTIME_INDEX => 1;

my $dbname = 'ebam';
# имя пользователя
#my $username = 'postgres';
my $username = 'ebam';
# пароль
#my $password = 'postgres';
my $password = 'ebam';
# имя или IP адрес сервера
my $dbhost = 'pg_bsd12';
# порт
my $dbport = '5432';
# опции
my $dboptions = '-e';

my $dirname = '/usr/home/ebam/ftp/bin';
my @time ;# =  (0) x 10;# = ('00:00', '03:00', '06:00', '09:00', '12:00', '15:00'); 
my @site1 ;#  = (    0.80 ,     0.930 ,     0.615 ,    0.40 ,   0.250 ,   0.340 );
my @site2 ;#  = (   0.20 ,     0.5 ,    0.21 ,     0.043 ,    0.060 ,    0.150 );
my @site3 ;#  = (   0.5,		0.5,	0.5,	0.5,	0.5,	0.5); 
my @date ;
my @at;
my @rh;
my @bp;
my @ws;
my @wsm;
my @wd;
my @datetime;
my @data = (\@time, \@site1, \@site2, \@site3);

my $logfile = 'Logfile.1';
my @line;
my $lines;
my $ret;

my $query;

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname;host=$dbhost;port=$dbport;options=$dboptions;","$username","$password",
		    {PrintError => 0});
nullarr();

my $sth = $dbh->prepare("select true" );

selectmail();

sendMail();

#is_sent($datetime[0],$datetime[$#datetime]);

$dbh->disconnect();

sub nullarr
{
#	@private = ();
	@time=();
	@site1=();
#	@site3=();
	@site2=();
	@at=();
	@date=();
	@datetime=();
	@wd=();
	@ws=();
	@wsm=();
}

sub selectmail
{
my $stmt = "SELECT 
ROUND(AVG(case when concrt<0 then concrt *-1 else concrt end)::numeric,3)   AS concrt, 
ROUND(AVG(wd)::numeric,0) AS wd, 
ROUND(AVG(ws)::numeric,1) AS ws, 
ROUND(MAX(ws)::numeric,1) AS wsm, 
ROUND(AVG(AT)::numeric,1) AS at, 
to_char((date_trunc('hour', datetime) +
    (((date_part('minute', datetime)::integer /10::integer) * 10::integer)
     || ' minutes')::interval)::time,\'HH24:mi\') AS time,
to_char((date_trunc('hour', datetime) +
    (((date_part('minute', datetime)::integer /10::integer) * 10::integer)
     || ' minutes')::interval)::date,\'DD.MM.YYYY\') AS date,
  date_trunc('hour', datetime) +
    (((date_part('minute', datetime)::integer /10::integer) * 10::integer)
     || ' minutes')::interval AS datetime1
FROM tbl_ebam
WHERE status =0
AND datetime > '2021-09-01'
AND is_sent=false
GROUP BY datetime1 order by datetime1 DESC LIMIT 72 ";
# 0 concrt 1 AT 2 time 3 date 4 datetime
#	$lines=$ret+$lines;
    $sth = $dbh->prepare( $stmt );
    my $rv = $sth->execute() or die $DBI::errstr;
    if($rv < 0) {
	print $DBI::errstr;
    }
    my $i=0;
    while(my @row = $sth->fetchrow_array()) {
#      print "CONCRT = ". $row[0] . "\n";
#      print "TIME = ". $row[1] ."\n";
	$time[$i]=$row[5];#time 5
	$site1[$i]=$row[0];#concrt 0
#	$site3[$i]=0.5;
	$at[$i]=$row[4];#AT temperatura 4
	$date[$i]=$row[6];# data 6
	$datetime[$i]=$row[7];#datetime 7
	$ws[$i]=$row[2];#wind speed 2
	$wsm[$i]=$row[3];#wind speed max 3
	$wd[$i]=$row[1];#wind direction 1
	$i++;
    }
7
}


sub sendMail
{
    my ( $Date,$Time,$ConcRT,$AT,$WS,$WSM,$WD);

    my $smtpserver='smtp.mail.ru';
    #my $smtpserver='smtp.yandex.ru';
    my $smtpport=587;
    my $smtptimout=20;
    my $smtpdebug=1;
    my $doSSL='starttls';
    my $smtpuser   = 'anymail@sssmail.ru';
    my $smtppassword ="anypassword";
    my $smtpfrom = 'anymail@sssmail.ru';
    my $smtpto = 'some@controlorgan.ru';
    my $subject = 'Test Message';
    my $resp ='';
    my $resp1 ='';
    my $mail_headers = "From: $smtpfrom\n".
    "To: $smtpto\n".
    "Subject: DMM EBAM $date[0] $time[0]\n".
    "MIME-Version: 1.0\n";
    my  $ConcRT1;#=  $ConcRT;
    my $smtp = Net::SMTPS->new(
	Host 	=> $smtpserver,
	Port    => $smtpport,
	Timeout => $smtptimout,
	Debug   => $smtpdebug,
	doSSL   => $doSSL,
	);
	
die "Initialization failed: $!" if !defined $smtp;
    $smtp->auth($smtpuser, $smtppassword ) or die("Could not authenticate .\n"); 
    $smtp->mail($smtpfrom);
    $smtp->to($smtpto);
    $smtp->data();
    $smtp->datasend($mail_headers);
					# Указываем информацию для поля "Кому"
    $smtp->datasend("\n");                        # Пустая строка
    foreach my $i (0 .. $#site1) 
    {
#   	 print "$i - $datetime[$i] \n";

	$Date=$date[$i];
	$Time=$time[$i];
	$AT=$at[$i];
	$AT=~ s/\W/,/g;
	$ConcRT1=$site1[$i];
#print "ConcRT1=$ConcRT1\n";
	$ConcRT1 =~ s/\W/,/g;
	$WD=$wd[$i];
	$WS=$ws[$i];
#print "$WS\n";
	$WS=~ s/\W/,/g;
	#print "$WS\n";
	$WSM=$wsm[$i];
	$WSM=~ s/\W/,/g;#change . to , -> 3.4 -> 3,4
    # #13;Data;Time;ConcRT;/;WindSpeed;WindSpeedMax;AT temperatura;/;/
	$smtp->datasend("#13;$Date;$Time;$ConcRT1;/;$WS;$WSM;$WD;$AT;/;/ \n"); 
    }
    $smtp->dataend();
    $resp= $smtp->message();
    if($resp=~/^(OK)/)
    {
#        print $resp."dddddddd \n";
        is_sent($datetime[0],$datetime[$#datetime]);
    }
    #rint $resp."\n";
#resp1=$resp=~/^(\w\w)/;
#   print $resp1."\n";

    $smtp->quit;


}

sub is_sent
{
    my $start = shift;
    my $stop = shift;
#print "$start $stop\n";
    my $stmt = "UPDATE tbl_ebam SET is_sent=true WHERE datetime BETWEEN '".$stop."' AND '".$start."' ";
#print $stmt; # update wrong? but work
    $sth = $dbh->prepare( $stmt );
    my $rv = $sth->execute() or die $DBI::errstr;

}

