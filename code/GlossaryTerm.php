<?php

/**
 * Dataobject to store a glossary term.
 */

class GlossaryTerm extends DataObject {

    static $db = array(
        'Term' => 'Text',
        'Definition' => 'HTMLText'
    );

    static $searchable_fields = array(
        'Term' => 'PartialMatchFilter'
    );

    static $summary_fields = array(
        'Term'
    );

    /**
     * Set up the glossary entry fields
     */
    function getCMSFields() {
        $fields = new FieldSet();
        $fields->push( new TextField('Term', 'Term') );
        $fields->push( new HtmlEditorField('Definition', 'Definition', 10) );
        return $fields;
    }

}

