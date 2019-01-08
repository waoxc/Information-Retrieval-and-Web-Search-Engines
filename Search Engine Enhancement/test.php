<?php

include 'simple_html_dom.php';
include 'SpellCorrector.php';

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$query = trim($query);
$results = false;
$results1 = false;
$results2 = false;
$correction = false;
$correctPrompt = "";
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

    $words = explode(" ", $query);
    $new_query = "";
    foreach ($words as $word) {
      # code...
      ini_set('memory_limit',-1);
      $new_query=$new_query.SpellCorrector::correct($word)." ";
      
      
      }
      $new_query = trim(strtolower($new_query));
      if($new_query !== trim(strtolower($query))){
        $correction = true;
        $link = "http://localhost/test.php?q=$new_query&algorithm=Solr+Lucene";
        $correctPrompt = "Did you mean: <a href='$link'>$new_query</a> ?";
      }

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
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
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
    <script>
      $(function() {
        var URL_PREFIX = "http://localhost:8983/solr/myexample/suggest?q=";
        var URL_SUFFIX = "&wt=json";
        $("#q").autocomplete({
          source : function(request, response) {
            var URL = URL_PREFIX + $("#q").val().split(" ").pop().toLowerCase() + URL_SUFFIX;
            $.ajax({
              url : URL,
              success : function(data) {
                var query = $("#q").val().split(" ").pop().toLowerCase();
                var js =data.suggest.suggest;
                var docs = JSON.stringify(js);
                var jsonData = JSON.parse(docs);
                var result =jsonData[query].suggestions;
                result = $.map(result, function(value, key) {
                  var query = $("#q").val();
                  var former = "";
                  if (query.split(" ").length > 1) {
                  var iOfLast = query.lastIndexOf(" ") + 1;
                    former = query.substring(0, iOfLast).toLowerCase();
                  }
                  return former + value.term;
                });
                response(result);
              },
              dataType : 'jsonp',
              jsonp : 'json.wrf'
            });
          },
          minLength : 1
        })
      });
    </script>
    <?php
    if($correction){
      echo $correctPrompt;
    }
    ?>

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
    $snippets = "";
    $termwords = explode(" ", $query);
    $count = 0;
    $numOfWords = sizeof($termwords);
    $file_content = file_get_contents($id);
    $html = str_get_html($file_content);
    $content = $html->plaintext;
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line)
    {
      $line = trim($line);
      for($i = 0 ; $i < sizeof($termwords); $i++){
        if(strpos(strtolower($line), strtolower($termwords[$i])) !== false){
          $count = $count+1;
        }
      }
      if($numOfWords == $count){
        $snippets = $line;
        break;
      }
      else if($count > 0){
        $snippets = $line;
        break;
      }
      $count = 0;
    }

    if($snippets == "")
      $snippets = $desc;
    $pos_term = 0;
    $start_pos = 0;
    $end_pos = 0;
    for($i = 0 ; $i < sizeof($termwords); $i++){
      if (strpos(strtolower($snippets), strtolower($termwords[$i])) !== false) {
        $pos_term = strpos(strtolower($snippets), strtolower($termwords[$i]));
        break;
      }
    }
    if($pos_term > 80){
      $start_pos = $pos_term - 80; 
    }
    $end_pos = $start_pos + 160;
    if(strlen($snippets) < $end_pos){
      $end_pos = strlen($snippets) - 1;
      $trim_end = "";
    }
    else{
      $trim_end = "....";
    }
    if(strlen($snippets) > 160){
      if($pos_term > 0)
        $trim_beg = "....";
      else
        $trim_beg = "";
      $snippets = $trim_beg.substr($snippets , $start_pos , $end_pos - $start_pos + 1).$trim_end;
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
              echo "<b>ID: </b> $id<br/>";
            ?></td>
          </tr>
    <?php
    ?>
          <tr>
            <td><?php 
              echo "<b>Description:</b> $desc<br/>";
            ?></td>
          </tr>
    <?php
    ?>
          <tr>
            <td><?php 
              echo "<b>Snippetspet:</b> $snippets<br/>";
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