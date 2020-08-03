<?php
  ini_set('memory_limit', '1024M');
  include 'SpellCorrector.php';
    // make sure browsers see this page as utf-8 encoded HTML
    header('Content-Type: text/html; charset=utf-8');
    $limit = 10;
    $div=false;
    $correct = "";
    $correct1="";
    $output = "";
    $choice="lucene";
    $query= isset($_REQUEST['q'])?$_REQUEST['q']:false;
    $results = false;
    $limit = 10;
    $query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
    $results = false;

    if ($query) {
        // The Apache Solr Client library should be on the include path
        // which is usually most easily accomplished by placing in the
        // same directory as this script ( . or current directory is a default
        // php include path entry in the php.ini)
        require_once('Apache/Solr/Service.php');

        // create a new solr service instance - host, port, and webapp
        // path (all defaults in this example)
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');

        // if magic quotes is enabled then stripslashes will be needed
        if (get_magic_quotes_gpc() == 1) {
            $query = stripslashes($query);
        }

        // in production code you'll always want to use a try /catch for any
        // possible exceptions emitted  by searching (i.e. connection
        // problems or a query parsing error)
        try {
          if (!isset($_GET['rank_option'])) {
            $_GET['algorithm']="lucene";
          }
          if ($_GET['rank_option'] == "lucene") {
            $param = array('fl'=>'id,description,title,og_url', 'wt'=>'json');   
          } else {
            $choice="pagerank";
            $param = array('fl'=>'id,description,title,og_url', 'wt'=>'json','sort'=>'pageRankFile desc');
          }


          $word = explode(" ",$query);
          $encode_query = str_replace(" ","+",$query);
          $spell = $word[sizeof($word)-1];
          for($i = 0; $i < sizeOf($word); $i++) {
            ini_set('memory_limit',-1);
            ini_set('max_execution_time', 300);
            $che = SpellCorrector::correct($word[$i]);
            if($correct!="")
              $correct = $correct."+".trim($che);
            else{
              $correct = trim($che);
            }
          }
          $correct1 = str_replace("+"," ",$correct);
          $div=false;
          if(strtolower($query)==strtolower($correct1)){
            $results = $solr->search($query, 0, $limit, $param);
          } else {
            if(isset($_REQUEST['custom'])) {
              $results = $solr->search($correct, 0, $limit, $param);
              $div=false;
            }else{
              $div=true;
              $results = $solr->search($origin, 0, $limit, $param);
            }
 
            $original_link= "http://localhost/solr-php-client/script.php?q=$encode_query&rank_option=$choice";
            $corrected_link = "http://localhost/solr-php-client/script.php?q=$correct&rank_option=$choice&custom=true";
            $output = "<div class='h3'>Showing results for: $query</div>"
                      ."<div class='h3'>Did you mean <a href='$corrected_link'>$correct</a></div>";

          }

          
        } catch (Exception $e) {
          // in production you'd probably log or email this error to an admin
          // and then show a special message to the user but for this example
          // we're going to show the full exception
          die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
        }
    }
?>

<html>
  <head>
    <title>PHP Solr Client Example</title>
    <script src="jquery-3.5.0.js"></script>
    <link rel="stylesheet" href="jquery-ui-1.12.1/jquery-ui.css">
    <script src="jquery-ui-1.12.1/jquery-ui.min.js"></script>

    <script>
    var stopWords = "a,able,about,above,across,after,all,almost,also,am,among,can,an,and,any,are,as,at,be,because,been,but,by,cannot,could,dear,did,do,does,either,else,ever,every,for,from,get,got,had,has,have,he,her,hers,him,his,how,however,i,if,in,into,is,it,its,just,least,let,like,likely,may,me,might,most,must,my,neither,no,nor,not,of,off,often,on,only,or,other,our,own,rather,said,say,says,she,should,since,so,some,than,that,the,their,them,then,there,these,they,this,tis,to,too,twas,us,wants,was,we,were,what,when,where,which,while,who,whom,why,will,with,would,yet,you,your,not";

      $(function() {
        var URL_Start = "http://localhost:8983/solr/myexample/suggest?q=";
        var URL_End = "&wt=json";
        console.log("working");
        var count=0;
        var tags = [];
        $("#q").autocomplete({
          source : function(request, response) {
            var query = $("#q").val().toLowerCase().split(" ").pop(-1);
            var URL = URL_Start + query + URL_End;
            $.ajax({
          url : URL,
          success : function(data) {
            var input=$("#q").val().toLowerCase().split(" ").pop(-1);
            var suggestions=data.suggest.suggest[input].suggestions;
            suggestions=$.map(suggestions,function(value,index){
              var prefix="";
              var query=$("#q").val();
              var queries=query.split(" ");
              if(queries.length>1) {
                var lastIndex=query.lastIndexOf(" ");
                prefix=query.substring(0,lastIndex+1).toLowerCase();
              }
              if (prefix == "" && is_stop_word(value.term)) {
                return null;
              }
               if(!/^[0-9a-zA-Z]+$/.test(value.term)) {
                return null;
              }
              return prefix+value.term;
            });
            response(suggestions.slice(0,5));
          },
          dataType: 'jsonp',
          jsonp: 'json.wrf'
        });  
      },
      minLength: 1 
    });
    });


      function is_stop_word(stopword) {
        var regex=new RegExp("\\b"+stopword+"\\b","i");
        return stopWords.search(regex) < 0 ? false : true;
      }
    </script>
  </head>
  <body>
    <form  accept-charset="utf-8" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
      <input type="submit"/><br/><br/>
      <?php if ($div){echo $output;}?><br/>
      <input type="radio" name="rank_option" value="lucene" checked <?php if(isset($_GET['rank_option']) && $_GET['rank_option'] =='lucene' ){echo "checked";}?> /> Lucene 
	    <input type="radio" name="rank_option" value="pagerank" <?php if(isset($_GET['rank_option']) && $_GET['rank_option'] =='pagerank' ){echo "checked";}?> /> PageRank <br/><br/> 
    </form>

<?php
    // display results
    if ($results) {
    $total = (int) $results->response->numFound;
    $start = min(1, $total);
    $end = min($limit, $total);
?>
    <div style="padding-left: 30px;padding-right: 30px;">Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {
?>
      <li>
        <table style="border: 1px solid black; text-align: left">

    <tr>
    <th style="padding-right:20px"><?php echo htmlspecialchars("id", ENT_NOQUOTES, 'utf-8'); ?></th>
    <td style="width:100%"><?php echo htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8'); ?></td>
    </tr>

    <tr>
    <th style="padding-right:20px"><?php echo htmlspecialchars("description", ENT_NOQUOTES, 'utf-8'); ?></th>
    <td style="width:100%"><?php if ($doc->description) echo htmlspecialchars($doc->description, ENT_NOQUOTES, 'utf-8'); else echo htmlspecialchars('NA', ENT_NOQUOTES, 'utf-8')?></td>
    </tr>
    <?php $str = htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8');
      if ($str == null) {
        $id_not_found = htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8');
        $arr = explode("/", $id_not_found);
        $id_not_found = $arr[count($arr) - 1];
        $lines = file("fileMap.txt");
        foreach ($lines as $line) {
          $pieces = explode(": ", $line);
          if (strcmp($id_not_found, $pieces[0]) === 0) {
            $str = $pieces[1];
            break;
          }
        }
      }
    ?>
    <tr>
    <th style="padding-right:20px"><?php echo htmlspecialchars("title", ENT_NOQUOTES, 'utf-8'); ?></th>
    <td style="width:100%"><?php echo "<a href=$str>"; echo htmlspecialchars($doc->title, ENT_NOQUOTES, 'utf-8'); echo "</a>";?></td>
    </tr>

    <tr>
    <th style="padding-right:20px"><?php echo htmlspecialchars("og_url", ENT_NOQUOTES, 'utf-8'); ?></th>
    <td style="width:100%">
    <?php 
      
      echo "<a href=$str>"; 
      echo $str; echo "</a>";
      
      ?></td>
    </tr>

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

<style>
  body {
    padding-top: 50px;
    padding-left: 30px;
    padding-right: 30px;
    margin: auto;
  }
  form { 
    margin: 0 auto; 
    width:250px;
  }

</style>
