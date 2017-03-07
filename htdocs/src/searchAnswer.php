<?php
/**
 *
 */

require_once('Google/CustomSearch.php');
class searchAnswer
{
  public $userQuestion;
  public $stackQuestionId=null;
  public $questionURL;

  function __construct($question)
  {
    $this->userQuestion=$question;
  }

  public function getQuestionId($link) {
      preg_match('/\d+/', $link, $qid);
  	return $qid[0];
  }
  public function getQuestionURL(){
    return $this->questionURL;
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
      $this->questionURL=$link;
    	$questionId=$this->getQuestionId($link);
      $this->stackQuestionId=$questionId;
    }
  }

  public function getStackAnswer()
  {
    if ($this->stackQuestionId!=null) {
      $qurl=$this->questionURL;
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
        return json_encode(array('question' => $question,'answer'=>$answer ));
      }
      else {
        return false;
      }
    }
  }

}

 ?>
