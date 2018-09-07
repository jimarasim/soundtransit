<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext
{

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {

    }

    /**
     * @Given I go to :arg1 and visit each link to verify there are no 404s
     */
    public function iGoToAndVisitEachLinkToVerifyThereAreNoS($arg1)
    {
        //go to the first page
        $this->getSession()->visit($this->getMinkParameter('base_url') . $arg1);
        //make record that the first page was visited
        $visited = array($arg1 => $this->getSession()->getCurrentUrl());
        //get unique links to visit for first pass
        $toVisit = $this->getUniqueInternalHrefs($visited);
        //array to track 404s
        $pagesNotFound = array();

        //check each page for 404 message until there are no more unique urls to check
        do {
            //array to track links found while going through a pass
            $foundWhileVisiting = array();
            //for each unique url found
            foreach ($toVisit as $url => $referrer) {
                print("ANCHOR $url FROM $referrer\r\n");
                //visit the page
                $this->getSession()->visit($this->getMinkParameter('base_url') . $url);
                //check for 404
                if ($this->getSession()->getPage()->has('xpath', '//div[contains(text(),"The requested page could not be found.")]')) {
                    $pagesNotFound[$url] = $referrer;
                }
                //check for links that haven't been visited
                $foundWhileVisiting = array_merge($foundWhileVisiting, $this->getUniqueInternalHrefs(array_merge($visited, $toVisit, $foundWhileVisiting)));
            }
            //save off links visited in last pass
            $visited = array_merge($visited, $toVisit);
            //visit the set of links found while visiting the last pass
            $toVisit = $foundWhileVisiting;
        } while (count($toVisit));

        //CHECK FOR PAGES NOT FOUND
        if (count($pagesNotFound)) {
            foreach ($pagesNotFound as $url => $referrer) {
                print("PAGE NOT FOUND: $url FROM: $referrer\r\n");
            }

            throw new Exception("SOME PAGES WERE NOT FOUND. SEE ABOVE FOR LIST.");
        }
    }

    /**
     *
     * @param type $visited - array of urls that have already been accounted for, where the key is the url, and the value is the referral page.
     * @return type
     */
    private function getUniqueInternalHrefs($visited)
    {
        $currentUrl = $this->getSession()->getCurrentUrl();
        $foundUniqueInternalHrefs = array();
        $internalLinks = $this->getSession()->getPage()->findAll('css', 'a[href^="/"]');

        foreach ($internalLinks as $internalLink) {
            $href = $internalLink->getAttribute("href");
            if (!array_key_exists($href, $visited)) {
                $foundUniqueInternalHrefs[$href] = $currentUrl;
            }
        }

        return $foundUniqueInternalHrefs;
    }
}