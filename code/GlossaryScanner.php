<?php

/**
 * Adds a template escape type 'GlossaryScan'.  Allows us to add into templates
 * whereabouts to scan content for glossary terms.
 *
 * Eg. $Content.GlossaryScan OR <% control Content %>$GlossaryScan<% end_control %>
 * 
 * The replacement 
 */

class GlossaryScanner extends Extension {

    /** 
     * An array of tags to scan by default.  Defaults to p, li and td tags.
     * @static
     */
    public static $scanTags = array('p','li','td');

    /** 
     * Whether to only replace the first occurrence of each term. Defaults to true. 
     * @static
     */
    public static $oncePerPage = true;

    /**
     * The template used to decorate scanned terms.
     * @static
     */
    public static $template = null;
    
    /**
     * The URL to use for Glossary links.
     * @static
     */
    public static $glossaryPageUrl = null;
    
    /* A record of which tags have already been used */
    private static $foundTerms = array();

    /*
     * Scans an HTML string and inserts glossary markup where appropriate.
     * The default XPath to use to determine which nodes to scan is set on 
     * this class using GlossaryTerm:setDefaultTags().
     *
     * Defaults to a whitelist of <p> and <li> tags.
     * To scan text as plain text, pass in '*' for $tags.
     *
     * @param $string   The HTML string to scan.
     * @param $tags     A list of tags to whitelist-scan for.
     * @return          The scanned and replaced HTML string.
     */
    private static function filter($string, $tags=null) {

        // Blank dataobject to use as a template for replacement
        self::$template = new DataObject();

        // Use the first GlossaryPage as the URL to use, unless we've chosen
        // another one.
        $url = self::getGlossaryPageUrl();

        $xpath = null;
        $result = $string;

        if ( $tags === null ) {
            $tags = self::$scanTags;
        }
        if ( ! is_array($tags) ) $tags = explode(',',$tags);

        if ( $tags == array('*') ) {
            $xpath = 'body/text()';
        } else {
            // Determine XPath to scan
            $xpath = '';
            $tag = null;
            $k = null;
            foreach( $tags as $k => $tag ) {
                if ( $k > 0 ) {
                    $xpath .= ' or ';
                }
                $xpath .= 'name()='."'".strtolower($tag)."'";
            }
            $xpath = '//*['.$xpath.']/text()';
            unset($tag);
            unset($k);
        }

        $terms = DataObject::get('GlossaryTerm');

        $doc = new DOMDocument();

        if ( strpos($string, '<html>') === false ) { 
            // This is an HTML fragment; tell $doc->loadHTML what charset to use.
            $string = '<html><head>'    
                    . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'
                    . '</head><body>'.$string.'</body></html>'; 
        } else {
            // This is an entire document; add a content type meta tag if it's not there
            if ( ! preg_match('/\<meta[^\>]+http-equiv="Content-Type"/i', $string) ) {
                $string = str_ireplace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>', $string);
            }
        }

        // Avoids 'xmlParseEntityRef: no name' error when loading string with ampersand
        // with no following entity description
        $string = preg_replace('/&(?!#?\w+;)/', '&amp;', $string);
        
        @$doc->loadHTML($string);

        // Process template
        $xp = new DOMXPath($doc);
        $nodes = $xp->query($xpath);

        if($terms) {
            for( $i = 0; $i < $nodes->length; $i++ ) {
                // Skip any text nodes that are inside anchor tags
                if ( self::dom_has_ancestor($nodes->item($i), 'a') ) continue;
                $nodeValue = $nodes->item($i)->nodeValue;
                $nodeValue = preg_replace('/&(?!#?\w+;)/', '&amp;', $nodes->item($i)->nodeValue);
                if ( ! $nodeValue ) continue;
                $newNodeValue = $nodeValue;
                // Cycle thru terms, replace them all...
                foreach ( $terms as $term ) {
                    if ( ! $term->Term ) continue;
                    // Only replace the first occurrence of each term
                    if ( self::$oncePerPage && in_array($term->Term, self::$foundTerms) ) continue; 
                    // Check for the term surrounded by non-letter characters
                    if ( ! preg_match('/\\W'.preg_quote($term->Term).'\\W/i', ' '.$nodeValue.' ') ) {
                        continue;
                    }
                    self::$template->customise(array(
                        'Term' => $term->Term,
                        'Definition' => $term->Definition,
                        'Url' => $url
                    ));
                    $newNodeValue = preg_replace_callback(
                        '/(\\W+)('.preg_quote($term->Term).')(\\W+)/i',
                        create_function(
                            '$matches',
                           'return $matches[1]'
                           .'.GlossaryScanner::$template->renderWith(\'GlossaryTerm\', array(\'Text\' => $matches[2]))'
                           .'.$matches[3];'
                        ),
                        ' '.$newNodeValue.' ',  // Space accounts for match at very start/end of string
                        1                       // Only replace the first occurrence
                    );
                    self::$foundTerms[] = $term->Term;
                    // Remove leading and trailing spaces we added in
                    if ( substr($newNodeValue, 0, 1) == ' ' ) {
                        $newNodeValue = substr($newNodeValue, 1);
                    }
                    if ( substr($newNodeValue, -1) == ' ' ) {
                        $newNodeValue = substr($newNodeValue, 0, -1);
                    }
                }
                // Replace our old node with the new one, with the terms replaced
                $f = $doc->createDocumentFragment();
                $f->appendXML($newNodeValue);
                if(is_object($nodes->item($i)->parentNode)) {
                    $nodes->item($i)->parentNode->replaceChild($f, $nodes->item($i));
                }
            }
        }

        $result = $doc->saveXML();
        list($junk, $result) = split("<body>", $result);
        list($result, $junk) = split("</body>", $result);
        
        return $result;
    }

    /**
     * Returns the URL of the first GlossaryPage, unless we've overridden it
     * using GlossaryScanner::setGlossaryPageUrl().
     * 
     * If the URL has not been set, and there are no GlossaryPages, returns '/'.
     */
    private static function getGlossaryPageUrl($args) {
        if ( self::$glossaryPageUrl === null ) {
            self::$glossaryPageUrl = '/';
            $page = DataObject::get_one('GlossaryPage');
            if ( $page ) {
                self::$glossaryPageUrl = $page->Link();
            }
        }
        return self::$glossaryPageUrl;
    }

    /**
     * Sets the Url to use for the glossary page.
     * @param   String  $url    The Url to use when highlighting glossary terms.
     */
    public static function setGlossaryUrl($url) {
        self::$glossaryPageUrl = $url;
    }

    /**
     * Helper function for filter - returns whether a given node has an ancestor
     * with a specified tagname.
     * @param $node (DOMNode) The node to test.
     * @param $tag (String) The tagname to search ancestors for
     * @return (Boolean) Returns true if the node has an ancestor with the specified 
     *          tagName, otherwise false.
     */
    private static function dom_has_ancestor($node, $tag) {
        if ( $node->parentNode == null || $node->parentNode->nodeType == 13 ) return false;
        if ( strtolower($node->parentNode->tagName) == strtolower($tag) ) return true;
        return self::dom_has_ancestor($node->parentNode, $tag);
    }

    /**
     * Called by templates to scan content.
     */
    public function GlossaryScan() {
        $tags = self::$scanTags;
        if ( $this->owner->stat('scanTags') ) {
            $tags = $this->owner->stat('scanTags');
        }   
        return GlossaryScanner::filter($this->owner->value, $tags);
    }

}

