<?php

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$results1 = false;
$results2 = false;
$additionalParameters1 = array(
  'fl' => 'title,id,og_url,og_description'
  // notice I use an array for a multi-valued parameter 
);
$additionalParameters2 = array(
  'fl' => 'title,id,og_url,og_description',
  'sort' => 'pageRankFile desc'
  // notice I use an array for a multi-valued parameter 
);


if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
    $results1 = $solr->search($query, 0, $limit, $additionalParameters1);
    $results2 = $solr->search($query, 0, $limit, $additionalParameters2);
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
  <head>
    <title>Comparing Search Engine Ranking Algorithms</title>
  </head>
  <body>
    <form  accept-charset="utf-8" method="get" action="">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
      
      <label class="heading">Algorithm:</label>
      <input name="algorithm" type="radio" value="Solr Lucene">Solr Lucene
      <input name="algorithm" type="radio" value="PageRank">PageRank
      <?php
      $algorithm= $_GET ['algorithm'];
      if($algorithm == 'PageRank')
        $results = $results2;
      else{
        $algorithm = "Solr Lucene";
        $results = $results1;
      }

      ?>
      <input type="submit"/>
    </form>
<?php
// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?> using <?php echo $algorithm; ?>: </div>
    <ol>
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {
?>
      <li>
        <table style="text-align: left">
<?php
    // iterate document fields / values
    $url = '';
    $title = '';
    $id = '';
    $desc = '';
    foreach ($doc as $field => $value)
    {
      if($field == 'id')
        $id = $value;
      if($field == 'title')
        $title = $value;
      if($field == 'og_url')
        $url = $value;
      if($field == 'og_description')
        $desc = $value;
    }
    if($desc == '')
      $desc = 'NA';
    if($url == ''){
      if(($handle = fopen("URLtoHTML_nypost.csv", "r"))!== FALSE){
        while(($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
          $newID = "/Users/xuechengzhe/solr-7.5.0/../nypost/" . $data[0];
          if($newID==$id){
            $url = $data[1];
          }
        }
        fclose($handle);
      } 
    }
    ?>
          <tr>
            <td><?php 
              echo "<a href=$url>$title</a>";
            ?></td>
          </tr>
    <?php
    ?>
          <tr>
            <td><?php 
              echo "<a href=$url>$url</a>";
            ?></td>
          </tr>
    <?php
    ?>
          <tr>
            <td><?php 
              echo htmlspecialchars($id, ENT_NOQUOTES, 'utf-8');
            ?></td>
          </tr>
    <?php
    ?>
          <tr>
            <td><?php 
              echo htmlspecialchars($desc, ENT_NOQUOTES, 'utf-8');
            ?></td>
          </tr>
    <?php
?>
        </table>
      </li>
<?php
  }
?>
    </ol>
<?php
}
?>
  </body>
</html>