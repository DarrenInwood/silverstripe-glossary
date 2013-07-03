<?php

class GlossaryPage extends Page {

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        
        $fields->removeFieldFromTab('Root.Content.Main', 'Content');
        $fields->addFieldToTab('Root.Content.Main', new TextareaField('Content', 'Intro (line breaks and HTML will be ignored)', 4));
    
        return $fields;    
    }

    /**
     * Strips HTML and line breaks from the Intro (Content) paragraph
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if ( isset($this->record['Content']) ) {
            $this->record['Content'] = strip_tags($this->record['Content']);
            $this->record['Content'] = str_replace("\n", ' ', $this->record['Content']);
        }
    }

}

class GlossaryPage_Controller extends Page_Controller {

    /* Stores the term object for this particular instance. */
    private $term;

    public function init() {
        $this->isAjax = false;
        if ( array_key_exists('ajaxget', $_REQUEST) ) {
            $this->isAjax = true;
        }
        parent::init();
    }

    public function index($r) {
        if ( $this->isAjax ) {
            return $this->renderWith('GlossaryAjax');
        }
        return $this->renderWith(array('GlossaryPage','Page'));
    }

    //////////     Glossary HTML functions

    public function GlossaryTerms() {
        return DataObject::get('GlossaryTerm', null, 'Term');
    }

    public function Term() {
        $term = $this->get_term();
        if ( ! is_object($term) ) return false;
        return $term->Term;
    }

    public function Definition() {
        $term = $this->get_term();
        if ( ! is_object($term) ) return false;
        return $term->Definition;
    }

    private function get_term() {
        if ( ! is_object($this->term) ) {
            $this->term = DataObject::get_one(
                'Glossary',
                "`Term` = '".mysql_real_escape_string($this->urlParams['ID'])."'"
            );
        }

        return $this->term;
    }

    //////////     AJAX functions

    /**
     * Overload $this->Children() to grab the bits we've specified on the URL.
     * URL is of format /glossary/ajax/?ajaxget=Title
     * @return  GlossaryTerm    The GlossaryTerm's Definition field.  If not found, returns blank.
     */
    public function AjaxOutput() {
        $item = $_REQUEST['ajaxget'];
        return DataObject::get_one(
            'GlossaryTerm',
            sprintf(
                "Term = '%s'",
                mysql_real_escape_string($item)
            )
        );
    }

}

