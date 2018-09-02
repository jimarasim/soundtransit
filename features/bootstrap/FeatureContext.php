<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
      
  }
  
      public function spin($lambda, $wait = 5) {
        for ($i = 0; $i < $wait; $i++) {
          try {
            if ($lambda($this)) {
              return TRUE;
            }
          } catch (Exception $e) {
            // do nothing
          }

          sleep(1);
        }

        $backtrace = debug_backtrace();

        throw new Exception(
          "Timeout thrown by " . $backtrace[0]['class'] . "::" . $backtrace[0]['function'] . "()\n" .
          $backtrace[0]['file'] . ", line " . $backtrace[0]['line']
        );
      }
    
     /**
     * @When I click the :arg1 element
     */
    public function iClickTheElement($arg1)
    {
        $element = $this->getSession()->getPage()->find('css',$arg1);
        if($element === NULL) {
            throw new Exception("ELEMENT WITH CSS PATH {$arg1} NOT FOUND");
        }
        
        $element->click();
    }
    
    /**
     * @When wait for the :elementId element to be visible
     */
    public function waitForTheElementToBeVisible($elementId)
    {
        $this->spin(function ($context) use ($elementId) {
            $element = $context->getSession()->getPage()->find('css', $elementId);
            if ($element === NULL) {
              return (FALSE);
            }
            return ($element->isVisible());
          });
    }
    
    /**
     * @Given I visit each link to verify no page not found errors
     */
    public function iVisitEachLinkToVerifyNoPageNotFoundErrors()
    {
        $anchorsToVisit = array();
        $anchorsFound = array();
        $pagesNotFound = array();
        $this->getUniqueInternalHrefs($anchorsToVisit);
        
        //FIRST PASS THROUGH ANCHORS ON CURRENT PAGE
        foreach($anchorsToVisit as $url => $referrer) {
            print("ANCHOR $url FROM $referrer TO VISIT\r\n");
            $this->getSession()->visit('https://beta.soundtransit.org'.$url);
            
            if($this->getSession()->getPage()->has('xpath', '//div[contains(text(),"The requested page could not be found.")]')) {
                $pagesNotFound[$url] = $referrer;
            }
            
            $this->getUniqueInternalHrefs($anchorsFound, $anchorsToVisit);
        }
        
        //SECOND PASS THROUGH ANCHORS FOUND IN FIRST PASS
        foreach($anchorsFound as $url => $referrer) {
            print("ANCHOR $url FROM $referrer FOUND\r\n");
            $this->getSession()->visit('https://beta.soundtransit.org'.$url);
            
            if($this->getSession()->getPage()->has('xpath', '//div[contains(text(),"The requested page could not be found.")]')) {
                $pagesNotFound[$url] = $referrer;
            }
        }
        
        //CHECK FOR PAGES NOT FOUND
        if(count($pagesNotFound)) {
            foreach($pagesNotFound as $url => $referrer) {
                print("PAGE NOT FOUND: $url FROM: $referrer\r\n");
            }
            
            throw new Exception("SOME PAGES WERE NOT FOUND. SEE ABOVE FOR LIST.");
        }
        
    }
    
    /**
     * Store links found on the page, beginning with "/", if they haven't been stored already.
     * The key of the array will be the link, and the value will be its referring page.
     * @param type $anchors
     * @param type $moreAnchors
     */
    private function getUniqueInternalHrefs(&$anchors,$moreAnchors=array()) {
        $links = $this->getSession()->getPage()->findAll('css','a[href^="/"]');
        $currentUrl = $this->getSession()->getCurrentUrl();
        foreach ($links as $link) {
            $href = $link->getAttribute("href");
            if(!array_key_exists($href,$anchors) && !array_key_exists($href,$moreAnchors)) {
                $anchors[$href] = $currentUrl;
            }
        }        
    }

}
