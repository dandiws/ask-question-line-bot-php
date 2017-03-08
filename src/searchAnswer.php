<?php
/**
 *
 */

require_once('Google/CustomSearch.php');
class searchAnswer
{
  public $userQuestion;
  public $stackQuestionId=null;
  public $searchURL;

  function __construct($question)
  {
    $this->userQuestion=$question;
  }

  public function getQuestionId($link) {
      preg_match('/\d+/', $link, $qid);
  	return $qid[0];
  }
  public function getsearchURL(){
    return $this->searchURL;
  }

  public function getGoogleResult()
  {
    $search = new Google_CustomSearch($this->userQuestion);
    $search->setApiKey('AIzaSyBYGQaSGm17LUrcIqKZ4hSweECjD-j-G-8');
    $search->setCustomSearchEngineId('010128815605255667216:cdefrenijdi');
    $search->setNumberOfResults(1);
    $response = $search->getResponse();
    if ($response->hasResults()) {
    	$googleResult=$response->getResults();
    	$link=$googleResult[0]->getLink();
      $this->searchURL=$link;
    }
  }

  public function getAnswer()
  {
    if ($this->searchURL==null) {
      return "Sorry, I don't think I have answer for that question :(";
    }
    else {
      $s_url=$this->searchURL;
      if (preg_match('/https?:\/\/(www\.)?w3schools.com\//', $s_url)) {
        $answer=$this->getWschoolAnswer();
      }
      else {
        $this->stackQuestionId=$this->getQuestionId($this->searchURL);
        $answer=$this->getStackAnswer();
      }

      if ($answer) {
        return $answer;
      }

      return "Sorry, I don't think I have answer for that question :(";
    }
  }

  public function getStackAnswer()
  {
    if ($this->stackQuestionId!=null) {
      $qurl=$this->searchURL;
      switch ($qurl) {
        case (preg_match('/http:\/\/(\S+)\.stackexchange\.com\/questions\//', $qurl, $catch) ? true : false):
          $site=$catch[1];
          break;
        case (preg_match('/http:\/\/(\S+)\.com\/questions\//', $qurl, $catch) ? true : false):
          $site=$catch[1];
          break;
        default:
          $site="stackoverflow";
          break;
      }
      $url="https://api.stackexchange.com/2.2/questions/{$this->stackQuestionId}/answers?order=desc&sort=votes&site={$site}&filter=!t)HOO74TWaQb5BL(tj(DOAr*Qj9(E-L";
      $stackResult = file_get_contents($url);
      $stackResult=json_decode(gzdecode($stackResult));
      if (sizeof($stackResult->items)>0) {
        $mostResult=$stackResult->items[0];
        $question=$mostResult->title;
        $answer=$mostResult->body;
        $answer=strip_tags($answer);
        $more="More answer here : ".$this->searchURL;
        $message="$question\r\n\r\n$answer\r\n$more";
        $message=$this->messageValidate($message);
        return $message;
      }
      else {
        return false;
      }
    }
  }
public function getW3schoolsAnswer()
{
  return "In Progress. More : $this->searchURL";
}
public function getWschoolAnswer()
  {
    $url=$this->searchURL;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $html=curl_exec($curl);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $script = $dom->getElementsByTagName("script");
    while ($script->length > 0) {
      $sc = $script->item(0);
	    $sc->parentNode->removeChild($sc);
    }
    $xpath = new DomXPath($dom);
    $classname='w3-clear nextprev';
    $prevnext = $xpath->query("//*[contains(@class, '$classname')]");
    $classname='w3-btn';
    $w3Btn= $xpath->query("//*[contains(@class, '$classname')]");
    $classname='ezoic-ad';
    $ad=$xpath->query("//*[contains(@class, '$classname')]");
    for ($i=0; $i < $prevnext->length; $i++) {
      if($table = $prevnext->item($i)){
          $table ->parentNode->removeChild($table);
      }
    }
    for ($i=0; $i < $w3Btn->length; $i++) {
      if($table = $w3Btn->item($i)){
          $table->parentNode->removeChild($table);
      }
    }
    for ($i=0; $i < $ad->length; $i++) {
      if($table = $ad->item($i)){
          $table->parentNode->removeChild($table);
      }
    }
    $main=$dom->getElementById('main');
    $message=$dom->saveHTML($main);
    $message=str_replace("<hr>","\r\n\r\n",$message);
    $message=str_replace("<br>","\r\n\r\n",$message);
    $message=strip_tags($message);
    $message=$this->messageValidate($message);
    return $message;
  }

  public function messageValidate($message)
  {
    $message=htmlspecialchars_decode($message);
    $message=html_entity_decode($message);
    if (strlen($message)<=2000) {
      return $message;
    }
    $longString = $message;
    $arrayWords = explode(' ', $longString);
    $maxLineLength = 2000;
    $currentLength = 0;
    $index = 0;
    foreach ($arrayWords as $word) {
      $wordLength = strlen($word) + 1;
      if (($currentLength + $wordLength) <= $maxLineLength) {
        $arrayOutput[$index] .= $word . ' ';
        $currentLength += $wordLength;
      } else {
        $index += 1;
        $currentLength = $wordLength;
        $arrayOutput[$index] = $word;
      }
    }
    return $arrayOutput;
  }
}

 ?>
