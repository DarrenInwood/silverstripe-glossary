<?php


Object::add_extension('DBField', 'GlossaryScanner');

// Scans Text type DBFields as root text nodes.
// This forces the xpath used to pick elements as body/text()
Object::set_static('Text', 'scanTags', array('*'));

// Set whether to only replace the first occurrence in a page for each term.
GlossaryScanner::$oncePerPage = true;


