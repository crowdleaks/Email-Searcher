<?php
function getpostvar($name)
{
	if(empty($_REQUEST[$name])) return false;
	return "'" .  mysql_real_escape_string($_REQUEST[$name]) . "'";
}
function AddLike($columnName, $var)
{
	if(!getpostvar($var)) return "";
	return " AND " . $columnName . " LIKE '%" . mysql_real_escape_string($_REQUEST[$var]) . "%'";
}
function AddQuery($start, $var)
{
	if(!getpostvar($var)) return "";
	return " AND " . $start . "'" . mysql_real_escape_string($_REQUEST[$var]) . "'";
}
function AddSearchPhrase($var)
{
	if(!getpostvar($var)) return "";
	return " AND MATCH(Subject, Body) AGAINST (" . getpostvar($var) . ")";
}
function GetDatabase()
{
	$db = mysql_connect('localhost', 'hbgary', 'password');
	mysql_select_db('hbgary',$db);
	return $db;
}

function GetSQL($count)
{
	if($count)
	{
		$sql = "SELECT count(1) as count  FROM emails WHERE 1 = 1";
	}
	else
	{
		$sql = "SELECT Id,Subject, SentFrom, SentTo, EmailDate, link  FROM emails WHERE 1 = 1";
	}
	$sql .= AddLike("SentFrom", "from");
	$sql .= AddLike("SentTo", "to");
	$sql .= AddQuery("EmailDate >", "endDate");
	$sql .= AddQuery("EmailDate <", "startDate");
	
	$sql .= AddSearchPhrase("searchPhrase");
	return $sql;
}

function GetSingle()
{
	$database = GetDatabase();
	$sql = "SELECT htmlemails.Body, emails.Subject, emails.SentFrom, emails.SentTo, emails.EmailDate, emails.Filename from htmlemails inner join emails on htmlemails.Id = emails.Id WHERE emails.Id = " . intval($_GET['id']) . " LIMIT 1" ;
	$result = mysql_query($sql, $database);
	return mysql_fetch_assoc($result);
}

$id = intval($_REQUEST['id']);
$db = GetDatabase();
if(isset($_REQUEST['id']) && $id > 0)
{
	$row = GetSingle();
	
	?>
		<div>
			Subject: <?php echo $row["Subject"];?>
		</div>
		<div>
		Sent From:<?php echo $row["SentFrom"];?>
		</div>
		<div>
		Sent To:<?php echo $row["SentTo"];?>
		</div>
		<div>
		Date:<?php echo $row["EmailDate"];?>
		</div>
		<div>
		Filename:<a href="<?php echo $row["Filename"];?>">Original File</a>
		</div>
		<hr/>
		<div>
			<?php echo $row["Body"];?>
		</div>
	<?php
	
}
else
{
	$page = $_REQUEST['page']; // get the requested page
	$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
	 // get index row - i.e. user click to sort
	$sord = $_REQUEST['sord'] == "asc"?"asc":"desc"; // get the direction
	
	$columns = array('Subject','SentFrom','SentTo','EmailDate');
	if (in_array($_GET['sort_column'], $columns)) {
		$sidx = $_REQUEST['sidx'];
	} else {
		$sidx = $columns[0];
	}

	$totalrows = isset($_REQUEST['totalrows']) ? $_REQUEST['totalrows']: false;
	if($totalrows) {
		$limit = $totalrows;
	}

	if(!$sidx) $sidx =1;
	// connect to the database
	$db = GetDatabase();

	$result = mysql_query(GetSQL(true),$db);
	$row = mysql_fetch_array($result,MYSQL_ASSOC);
	$count = $row['count'];

	if( $count >0 ) {
		$total_pages = ceil($count/$limit);
	} else {
		$total_pages = 0;
	}
	if ($page > $total_pages) $page=$total_pages;	
	$start = $limit*$page - $limit; // do not put $limit*($page - 1)
	if ($start<0) $start = 0;
	$SQL = GetSQL(false) . " ORDER BY ".$sidx." ".$sord. " LIMIT ".(int)$start." , ".(int)$limit;
	$result = mysql_query( $SQL, $db );
	$responce->page = $page;
	$responce->total = $total_pages;
	$responce->records = $count;
	$i=0;
	while($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
	$responce->rows[$i]['Id']=$row[Id];
		$responce->rows[$i]['cell']=array($row[Id],htmlspecialchars($row[Subject]),htmlspecialchars($row[SentFrom]),htmlspecialchars($row[SentTo]), $row[EmailDate], $row[link]);
		$i++;
	}
	echo json_encode($responce);
}
mysql_close($db);


?>
