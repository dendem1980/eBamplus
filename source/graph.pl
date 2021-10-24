#!/usr/local/bin/perl
use strict;
use warnings;
use File::Copy;
use Tie::File;
use GD; 
use GD::Graph::lines; 
use GD::Simple;
use DBI;

use constant MTIME_STAT_INDEX => 9;
use constant FILENAME_INDEX => 0;
use constant MTIME_INDEX => 1;
# имя базы данных
my $dbname = 'ebam';
# имя пользователя
my $username = 'ebam';
# пароль
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
my @datetime;
my @data = (\@time, \@site1, \@site2, \@site3);


my $ret;

my $query;

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname;host=$dbhost;port=$dbport;options=$dboptions;","$username","$password",
		    {PrintError => 0});

nullarr();

copy("/home/ebam/ftp/bin/Penguins.jpg","/home/ebam/ftp/bin/img.jpg") || die "Copy failed: $!";

selectpic();

my $sth = $dbh->prepare("select true" );

Graphik("/usr/home/ebam/ftp/bin/23",300,300);
resize("/usr/home/ebam/ftp/bin/23.png",500,400,"/usr/home/ebam/ftp/bin/img.jpg",500,100);

nullarr();
copy("/usr/home/ebam/ftp/bin/img.jpg","/usr/home/ebam/ftp/image/img.jpg") || die "Copy failed: $!";
copy("/usr/home/ebam/ftp/bin/img.jpg","/usr/home/ebam/ftp/image/img1.jpg") || die "Copy failed: $!";

$dbh->disconnect();

sub nullarr
{
	@time=();
	@site1=();
	@site3=();
	@site2=();
	@datetime=();
}


sub selectpic
{
my $stmt = "WITH ebam1 (concrt1,time1,datetime1) AS (
SELECT
ROUND(AVG(CASE WHEN a1.concrt<0 THEN a1.concrt *-1 ELSE a1.concrt END)::NUMERIC,3)   AS concrt1 ,
to_char((date_trunc('hour', a1.datetime) +
    (((date_part('minute', a1.datetime)::INTEGER /10::INTEGER) * 10::INTEGER)
     || ' minutes')::INTERVAL)::TIME,'HH24:mi') AS time1,

  date_trunc('hour', a1.datetime) +
    (((date_part('minute', a1.datetime)::INTEGER /10::INTEGER) * 10::INTEGER)
     || ' minutes')::INTERVAL AS datetime1
FROM tbl_ebam1 AS a1
WHERE a1.status =0
AND a1.datetime > NOW() - INTERVAL '12 hours'
GROUP BY datetime1
),
ebam2 (concrt2,time2,datetime2) AS (
SELECT
ROUND(AVG(CASE WHEN a2.concrt<0 THEN a2.concrt *-1 ELSE a2.concrt END)::NUMERIC,3)   AS concrt2 ,
to_char((date_trunc('hour', a2.datetime) +
    (((date_part('minute', a2.datetime)::INTEGER /10::INTEGER) * 10::INTEGER)
     || ' minutes')::INTERVAL)::TIME,'HH24:mi') AS time2,
  date_trunc('hour', a2.datetime) +
    (((date_part('minute', a2.datetime)::INTEGER /10::INTEGER) * 10::INTEGER)
     || ' minutes')::INTERVAL AS datetime2
FROM tbl_ebam AS a2
WHERE a2.status =0
AND a2.datetime > NOW() - INTERVAL '12 hours'
GROUP BY datetime2--a2.datetime 
)
SELECT COALESCE(datetime1,datetime2) AS datetime,COALESCE(concrt2,0) AS concrt0,COALESCE(concrt1,0) AS concrt1,time2 AS time
FROM ebam1
RIGHT JOIN ebam2 ON ebam1.datetime1=ebam2.datetime2
ORDER BY datetime2 DESC";
# 0 concrt 1 AT 2 time 3 date 4 datetime
#	$lines=$ret+$lines;
my $sth = $dbh->prepare( $stmt );
my $rv = $sth->execute() or die $DBI::errstr;
if($rv < 0) {
   print $DBI::errstr;
}
my $i=0;
while(my @row = $sth->fetchrow_array()) {
	$datetime[$i]=$row[0];
	$site1[$i]=$row[1];
	$site2[$i]=$row[2];
	$site3[$i]=0.5;
	$time[$i]=$row[3];
	$i++;
#      print "ADDRESS = ". $row[2] ."\n";
#      print "SALARY =  ". $row[3] ."\n\n";
}

}
sub selectpic1
{
my $stmt = " ";
# 0 concrt 1 AT 2 time 3 date 4 datetime
my $sth = $dbh->prepare( $stmt );
my $rv = $sth->execute() or die $DBI::errstr;
if($rv < 0) {
   print $DBI::errstr;
}
my $i=0;
while(my @row = $sth->fetchrow_array()) {
#      print "TIME = ". $row[1] ."\n";
$i=-1;
#print "next\n";
    do
    {
	$i++;
	if($row[1] eq $datetime[$i])
	{
	    $site2[$i]=$row[0];
	    $time[$i]=$row[1]
	}
#	print $i."\n";
	
    }
    while(!($row[2] eq $datetime[$i]))

}

}


sub CreatePngFile {
    my $image = shift;
    my $fname = shift;
 
    open    (my $file, ">$fname.png") or die $!;
    binmode ($file);
    print    $file $image->png;
    close   ($file);
 
    return 1;
}
sub Graphik {
	my ($file,$sizex,$sizey
) = @_;
my @data1 = (\@time, \@site1, 
\@site2, 
\@site3);

my %config = (
    title           => 'Vozduh',
    x_label         => 'time',
    y_label         => 'ConcRT',
 
    dclrs           => [ ('green', 
'blue' ,
'red') ],
 
#    x_label_skip    =>  1,
    x_labels_vertical => 1,
    y_label_skip    =>  1,
    x_label_skip    =>  6,
    y_tick_number   =>  8,
      line_width  => 2,
       transparent => 0,
);
 
my $lineGraph = GD::Graph::lines->new(450, 300);
$lineGraph->set(%config) or warn $lineGraph->error;
 
$lineGraph->set_legend_font('GD::gdMediumNormalFont');
$lineGraph->set_legend('SZZ E-Bam', 'PRU E-Bam');
 
my $lineImage = $lineGraph->plot(\@data1) or die $lineGraph->error;
 
CreatePngFile($lineImage, $file);

}

sub resize { 
    my ($inputfile, $width, $height, $outputfile,$xx,$yy) = @_; 
    GD::Image->trueColor(1); 
    my $gdo = GD::Image->new($inputfile); 

    { 
     my $k_h = $height/$gdo->height; 
     my $k_w = $width/$gdo->width; 
     my $k = ($k_h < $k_w ? $k_h : $k_w); 
     $height = int($gdo->height * $k); 
     $width = int($gdo->width * $k); 
    } 
	my $xot=1024-400;	
	my $yot=$height-100;	
    my $image = GD::Image->new($outputfile); 
    $image->alphaBlending(0); 
    $image->saveAlpha(1); 
    $image->copyResampled($gdo, $xx, $yy, 0, 0, $width, $height, $gdo->width, $gdo->height); 

    open my $FH, '>', $outputfile; 
    binmode $FH; 
    print {$FH} $image->png; 
    close $FH; 
} 
