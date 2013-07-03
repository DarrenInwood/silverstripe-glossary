<h1>Glossary page</h1>

<p>$Content</p>

<% control GlossaryTerms %>
<% if First %><dl><% end_if %>
    <dt id="{$Term.ATT}">$Term</dt>
    <dd>$Definition</dd>
<% if Last %></dl><% end_if %>
<% end_control %>
