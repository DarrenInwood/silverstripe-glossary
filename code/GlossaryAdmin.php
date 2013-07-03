<?php

/**
 * Extends ModelAdmin to provide administration for GlossaryTerm objects
 */

class GlossaryAdmin extends ModelAdmin {

    public static $managed_models = array(
        'GlossaryTerm'
    );

    static $url_segment = 'glossary';
    static $menu_title  = 'Glossary';

}
