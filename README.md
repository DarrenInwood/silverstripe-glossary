silverstripe-glossary
=====================

Highlights keywords in text, perfect for targeting with JS and decorating with popups

## Overview

The Glossary plugin for the SilverStripe framework allows scanning of HTML or 
plain text content for keywords, and highlighting these keywords.

By default this will take a word included in the glossary and surround it with 
HTML like so:

    <!-- Assuming the Glossary terms scanned for are 'Lorem' and 'Magna' --> 
    
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris egestas 
    hendrerit arcu, non suscipit magna varius ut.</p> 
    <p>Aenean feugiat sollicitudin ipsum in dapibus.</p> 

    <!-- becomes... --> 
    
    <p><a href="/glossary/#Lorem" name="Lorem" class="glossaryterm">Lorem</a> ipsum 
    dolor sit amet, consectetur adipiscing elit. Mauris egestas hendrerit arcu, 
    non suscipit <a href="/glossary/#Magna" name="Magna" class="glossaryterm">magna
    </a> varius ut.</p>
    <p>Aenean feugiat sollicitudin ipsum in dapibus.</p>


## Requirements

SilverStripe 2.4, untested with 3.x


## Installation Instructions

Check out the archive into the root directory of your project. This should be
the same folder as the 'sapphire' directory.

Run /dev/build?flush=1 to tell your SilverStripe about your new module.


## Configuration

There are various configuration options available to change the behaviour of the 
Glossary Scanner.

To ensure a certain DBField subclass is always scanned as plain text, add the 
following line to your _config.php:

    // This tells the GlossaryScanner to scan Text DBFields (and descendants) as 
    // plain text.
    Object::set_static('Text', 'scanTags', array('*'));

By default, Text fields and fields descended from Text are scanned as plain 
text, and all other types are scanned as HTML.

### Scanned tags in HTML

HTML fields will only scan tags that are included in a whitelist.  This is to 
allow you to only highlight inside paragraphs if you don't want to scan 
headings.

By default, the scanner scans p, li and td tags and their contents.

To alter this, include the following in your _config.php:

> GlossaryScanner::$scanTags = array('p','li','td','h6','h5','h4','h3');

### First instance on page

The scanner defaults to only highlighting the first instance on each page.  To 
highlight every instance, add to your `_config.php`:

> GlossaryScanner::$oncePerPage = false;

### URL of GlossaryPage for highlighted links

By default the module will provide a link to the first GlossaryPage as $Url 
inside the GlossaryTerm.ss template.  If you wish to use another, you can 
override this in _config.php:

> GlossaryScanner::setGlossaryPageUrl('/glossary-page/');


## Usage

When you log into the SilverStripe CMS, you will have a new top-level tab 
called 'Glossary'.  This will give you a ModelAdmin allowing entry of Glossary 
Terms to scan for, and their Definitions.

There is a new page type, GlossaryPage, which provides the GlossaryTerms control 
for your templates.  A default template will simply echo out the entire Glossary 
as an HTML definition list.

To scan and highlight a piece of content, use the new GlossaryScan escape method 
in your template file:

    $Content.GlossaryScan
    <% control Content %>$GlossaryScan<% end_control %>


### Custom highlighting HTML

For content where the 'GlossaryScan' escape method has been used, found Glossary 
Terms are highlighted.  The stock highlighting includes a link to the first 
GlossaryPage.  If there are no GlossaryPages set up, the link will go to the 
site root.

The highlighting HTML resides in the module file `templates/GlossaryTerm.ss`.  
To override this, create a file called `GlossaryTerm.ss` in your theme's 
`templates` folder.  

Inside your highlighting template, you have several variables to work with:

 * $Text - the actual text scanned, including any capitalisation etc.
 * $Term - the Term that was matched (as a string, not a GlossaryTerm object)
 * $Definition - the Definition field of the matched GlossaryTerm
 * $Url - the URL of the first GlossaryPage, or the site root if there are none

This should allow you to construct almost any highlighting syntax you might need.

### Custom GlossaryPage template

Simple create a new file called GlossaryPage.ss in your theme's root or Layout 
folder to override the stock one.

You can use the GlossaryTerms control to output the terms you've entered via the 
Glossary Admin in the CMS:

    <% control GlossaryTerms %>
    <% if First %><dl><% end_if %>
        <dt id="{$Term.ATT}">$Term</dt>
        <dd>$Definition</dd>
    <% if Last %></dl><% end_if %>
    <% end_control %>

### Tooltips

You can use the Glossary scanned tags to display tooltips when the user hovers 
over a word in your Glossary.

The easiest way is to copy the supplied GlossaryTerm.ss file into your theme's 
root directory, and add a title attribute:

    <a class="glossaryterm" name="{$Term.ATT}" title="{$Term.ATT}" href="{$Url.ATT}#{$Term.ATT}">$Text</a>

You could also integrate this with a custom tooltip Javascript.  If you need to 
fetch data about a specific Glossary Term, you can use the AJAX interface on any 
GlossaryPage.  If your site doesn't need a GlossaryPage, but you do want to use 
the AJAX functionality, just add a page but remove it from the menus using the 
Behaviour tab.

You can fetch the Definition for a given Term like so: (remember to ensure the 
URL is that of an existing GlossaryPage)

> /gallery/?ajaxget=termname

You will get the Definition text sent back.  If the Glossary Term isn't found, 
you will get a blank response.

You can customise the AJAX response by creating a GlossaryAjax.ss template in 
your theme's templates directory. inside this template you have use of the 
AjaxOutput control.  For example, to output the GlossaryTerm as JSON instead, 
you could use:

    <% control AjaxOutput %>
    {
        "term" : "$Term.ATT",
        "definition" : "$Definition.ATT"
    }
    <% end_control %>

