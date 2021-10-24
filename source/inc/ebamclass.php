<?php
#$configs = include('config.php');
/**
* PHP Ebam Met One class
*
* @author      denis Davydov <dendem1980@yandex.ru>
* @version     0.1.0
*/
ini_set('memory_limit', '-1');
class Ebam
{
    /**
     * Construct the class
     */
    public $table='tbl_ebam';
    public $mask='P*.CSV';
    public $workdir='/usr/home/ebam/ftp/dmm1/';
    public $dir='';
    public $files=array();
    private $headers=array();
    private $cols=array();
    public $all_rows=array();
    public $dbhost='pg_bsd12';
    public $dbport='5432';
    public $dbname='ebam';
    public $dbuser='ebam';
    public $dbpass='ebam';
    private $bol=false;
    
    function Ebam() 
    {
    }

public    function BulbOn()
    {
//	exec ('python3.7 P100_on.py &');
    }
public    function BulbOff()
    {
//	exec ('python3.7 P100_off.py');
    }

public   function filemov($file)
   {
	  $csvFile = pathinfo($file);

	preg_match('/(.+)\W(\d{4})(\d{2})\d{2}.+$/', $csvFile['filename'], $filedate);

	$dir= $filedate[1];
	$year = $filedate[2];
	$month = $filedate[3];
	$dir1= $filedate[1];
	unset($filedate);// =array(); 

//	echo "$year $month \n"; 
	$dir=$this->workdir.$dir1."/".$year."/".$month."/";
	echo $dir;
	if(!is_dir($dir)) 
	{
	    mkdir($dir, 0777, true);
	}
	if($this->bol)
	{
	    rename($file, $dir . pathinfo($file, PATHINFO_BASENAME));
	}
	else
	{
	    rename($file,$dir . pathinfo($file, PATHINFO_BASENAME));
	}
	unset($year);// =array(); 
	unset($month);// =array(); 

//	$filedate =array(); 
   }
    /**
     * List all the items
     */
public    function ereaddir()
    {
	$i=0;
	foreach (glob($this->workdir.$this->dir.$this->mask) as $csvfile) 
	{
	    $this->files[$i]=$csvfile;
	    $i++;
//	    echo "111\n";
	}
//	print_r($this->files);
    }

private    function parse_arr_upper($arr)
    {
	$num = count($arr);
	unset($arr[$num-1]);
	for ($c=0; $c < $num-1; $c++) 
	{
	    $pos=strrpos($arr[$c], " ");
	    $arr[$c] = strtoupper($arr[$c]);
	    if ($pos === false)
	    {
	    }
	    else
	    {
		$arr[$c] = strstr($arr[$c]," ",true);
	    }
	}
//	print_r( $arr);
	return $arr;
    }

private    function parse_arr_null($arr)
    {
	$num = count($arr);
	unset($arr[$num-1]);
	return $arr;
    }

private    function normaldata($arr)
    {
	if(strpos($arr[0],"."))
	$arr[0]=date_create_from_format("Y.m.d", $arr[0])->format("Y-m-d");

	if(strpos($arr[0],"/"))
	$arr[0]=date("Y-m-d",strtotime($arr[0]));

	if($arr[2]<0) $arr[2]=$arr[2]*-1;
	if($arr[2]>0.35) $this->BulbOn();

	return $arr;

    }


public    function getEbamData($file)
    {
	$row=1;
	if (($handle = fopen($file, "r")) !== FALSE) 
	{
	    $str = fgets($handle);
	    $pos=strrpos($str, ";");
//	    echo $str." e".$pos." e";
	    if($pos!=false)
	    {
//		echo $str;
		$str=str_replace(',' , '.', $str);
		$str=str_replace(';' , ',', $str);
		$str=str_replace(' ' , '', $str);
//		echo $str;
	    }
		$str=str_replace(' ' , '', $str);
	    $this->headers = explode(",",$str);
//	    print_r($this->headers);
	    $this->headers = $this->parse_arr_upper($this->headers);
//	    print_r($this->headers);
	    while(!feof($handle))
	    {
		$str = fgets($handle);
		$pos=strrpos($str, ";");
		if($pos!=false)
		{
		    $str=mb_substr($str, 0, -1);
		    $str=str_replace(',' , '.', $str);
		    $str=str_replace(';' , ',', $str);
		    $str=str_replace(' ' , '', $str);
//		    echo $str;
		}
		$str=str_replace(' ' , '', $str);
//		echo $str." ".$pos."eeee";
		$this->cols = explode(",",$str);
		if($this->cols[0] ==null )
		{
		}
		else
		{
		    $this->cols=$this->normaldata($this->cols);
		    $this->cols=$this->parse_arr_null($this->cols);
		    $this->all_rows[] = array_combine($this->headers, $this->cols);
		}
	    }
	}
	$this->addcol('DATETIME');
//	print_r($this->all_rows);
	fclose($handle);
	$this->bol=false;
	unset($str);
    }
    
private    function addcol($col)
    {
	$num=count($this->all_rows);
	$date='';
	$time='';
	for($i=0;$i<$num;$i++)
	{
//	    $this->all_rows[$col]='';
	    if(isset($this->all_rows[$i]['DATE']) and isset($this->all_rows[$i]['TIME']))
	    { 
		$this->all_rows[$i]['DATETIME']=$this->all_rows[$i]['DATE'].' '.$this->all_rows[$i]['TIME'];
	    } 
	    elseif (isset($this->all_rows[$i]['TIME']))
	    {
		list($date,$time)=explode(" ",$this->all_rows[$i]['TIME']);
		$this->all_rows[$i]['DATETIME']=$this->all_rows[$i]['TIME'];
		$this->all_rows[$i]['TIME']=$time;
		$this->all_rows[$i]['DATE']=$date;
	    }
	    elseif(isset($this->all_rows[$i]['DATE']))
	    {
		$this->all_rows[$i]['DATETIME']=$this->all_rows[$i]['DATE'];
	    }
	    else
	    {
		echo "DATETIME ".$this->all_rows[$i]['DATETIME']."22222  \n";
	    }
	}
//	print_r($this->all_rows);
	return true;
    }

public    function connectdb()
    {
	$host=$this->dbhost;
	$db_name=$this->dbname;
	$port=$this->dbport;
	$user=$this->dbuser;
	$pass=$this->dbpass;
	$conn_string = "host=".$host." port=".$port." dbname=".$db_name." user=".$user." password=".$pass;
	$dbconn = pg_connect($conn_string);
	
	return $dbconn;
    }

public    function closedb($dbconn)
    {
	pg_close($dbconn)or die('connection failed');
	return true;
    }

    private function reindex_arr($arr)
    {
    $newarr=array();
    $i=0;
	foreach($arr as $arr1)
	{
	$newarr[$i]=$arr1;
	$i++;
	}
    unset($arr1);
    return $newarr;
    }



public    function incertdb($dbconn,$arr)
    {
	reset($arr);
	$first = current($arr);
	if(is_array($first))
	{
	    $first=0;
	}
	$start=$arr[$first]['DATETIME'];
	$idx=count($arr)-1;
	$end=$arr[$idx]['DATETIME'];
	$sel=array ( 
	0 => 'date AS "DATE"',
	1 => 'time AS "TIME"',
	2 =>'concrt AS "CONCRT"',
	3 =>'conchr AS "CONCHR"',
	4 =>'flow AS "FLOW"',
	5 =>'at AS "AT"',
	6 =>'rh AS "RH"',
	7 =>'bp AS "BP"',
	8 =>'wd AS "WD"',
	9 =>'ws AS "WS"',
	10 =>'ft AS "FT"',
	11 =>'frh AS "FRH"',
	12 =>'bp AS "BP"',
	13 =>'pm AS "PM"',
	14 =>'status AS "STATUS"',
	15 =>'datetime AS "DATETIME"'	);
	$where=" WHERE DATETIME BETWEEN '".$start."' AND '".$end."'\n";

//        echo $where;
	$res1=$this->selectdb($dbconn,$sel,$where);
//	echo count($res1)." res \n ";
	if(!$res1)
	{
	$qw1=$this->prep_insert($arr);
//echo $qw1;
	$res = pg_query($dbconn, $qw1);
//print_r($res1);
	    if ($res) 
	    {
		    $this->bol=true;
		echo "1111 Данные из POST успешно внесены в журнал\n";
	    }
	    else
	    {
		echo "Пользователь прислал неверные данные\n";
	    }
	}
	else
	{	
    
	    $final = array();
	    $final=array_diff_key($arr,$res1);
	    $final = $this->reindex_arr($final);
	    if(count($final)>0)
	    {
		$qw1=$this->prep_insert($final);
		$res = pg_query($dbconn, $qw1);
		if ($res) 
		{
		    $this->bol=true;
		    echo "2122 Данные из POST успешно внесены в журнал\n";
		}
		else
		{
		    echo "Пользователь прислал неверные данные\n";
		}
	    }
	}
    }
    
    
private    function prep_insert($arr)
    {
	reset($arr);
	$first = current($arr);
	if (is_array($first))
	{
	$first=0;
	}
	$idx=count($arr)-1;
	$fields=implode (",",array_keys($arr[$first]));
	$query="INSERT INTO public.".$this->table."(".$fields.")  VALUES ";
	$query.="('".implode("','",$arr[$first])."')\n";
	for ($i=1;$i <= $idx; $i++)
	{
	    $query1=",('".implode("','",$arr[$i])."')\n";
	    $query.=$query1;
	}
	return $query;
    }

public function selectmail($dbconn)
{
	$table=$this->table;
	$query= "SELECT
ROUND(AVG(case when concrt<0 then concrt *-1 else concrt end)::numeric,3)   AS concrt,
ROUND(AVG(wd)::numeric,0) AS WD,
ROUND(AVG(ws)::numeric,1) AS WS,
ROUND(MAX(ws)::numeric,1) AS WSM,
ROUND(AVG(AT)::numeric,1) AS AT,
to_char((date_trunc('hour', datetime) +
    (((date_part('minute', datetime)::integer /10::integer) * 10::integer)
     || ' minutes')::interval)::time,'HH24:mi') AS TIME,
to_char((date_trunc('hour', datetime) +
    (((date_part('minute', datetime)::integer /10::integer) * 10::integer)
     || ' minutes')::interval)::date,'DD.MM.YYYY') AS date,
  date_trunc('hour', datetime) +
    (((date_part('minute', datetime)::integer /10::integer) * 10::integer)
     || ' minutes')::interval AS datetime1
FROM ".$table."
WHERE status =0
AND datetime > '2021-09-01'
AND is_sent=false
GROUP BY datetime1 order by datetime1 DESC LIMIT 72 ";
	$rec = pg_query($dbconn, $query);
	if (!$rec) 
	{
	    echo "Произошла ошибка.\n";
	    return false;
//		exit;
	}
	$arr1=array();
	$arr=array();
	while($arr1 = pg_fetch_array($rec, NULL, PGSQL_ASSOC))
	{
	    $arr[]=$arr1;
	}
	unset($arr1,$rec);
	return $arr;

}
public function is_sent($dbconn,$datetime)
{
	$table=$this->table;
	$query='UPDATE '.$table." SET is_sent = true WHERE ".$table.".datetime >= '"
	 .$datetime."' AND ".$table.".datetime < '".$datetime."'::timestamp+ interval '10 minutes'";
	$rec = pg_query($dbconn, $query);
	if (!$rec) 
	{
	    echo "Произошла ошибка.\n";
	    return false;
//		exit;
	}
return true;
}

private    function selectdb($dbconn,$column,$where)
    {
	$table=$this->table;
	$query='';
	$query.='SELECT '.implode(",",$column).' FROM '.$table.' '.$where.'ORDER BY datetime';
//	echo $query;
	$rec = pg_query($dbconn, $query);
	if (!$rec) 
	{
	    echo "Произошла ошибка.\n";
	    return false;
//		exit;
	}
	$arr1=array();
	$arr=array();
	while($arr1 = pg_fetch_array($rec, NULL, PGSQL_ASSOC))
	{
	    $arr[]=$arr1;
	}
	return $arr;
    }
    

}

?>
