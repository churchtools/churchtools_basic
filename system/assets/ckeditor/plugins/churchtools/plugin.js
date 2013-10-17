( function() {
    CKEDITOR.plugins.add( 'churchtools',
    {
        init: function( editor )
        {
           var me = this;
 
            editor.addCommand( 'vorname', {
              exec: function( editor ) {
                div = editor.document.createElement('span');
                div.setHtml("[Vorname]");
                editor.insertElement(div);
              }
            } );
            editor.addCommand( 'nachname', {
              exec: function( editor ) {
                div = editor.document.createElement('span');
                div.setHtml("[Nachname]");
                editor.insertElement(div);
              }
            } );
            editor.addCommand( 'spitzname', {
              exec: function( editor ) {
                div = editor.document.createElement('span');
                div.setHtml("[Spitzname]");
                editor.insertElement(div);
              }
            } );
            
            editor.ui.addButton( 'vorname',
            {
                label: 'Serienfeld: Vorname',
                command: 'vorname',
                icon: this.path + 'images/vorname.png',
                toolbar: 'vorname'
            } );
            editor.ui.addButton( 'spitzname',
                {
                    label: 'Serienfeld: Spitzname (wenn nicht vorhanden, dann Vorname)',
                    command: 'spitzname',
                    icon: this.path + 'images/spitzname.png',
                    toolbar: 'spitzname'
                } );
            editor.ui.addButton( 'nachname',
                {
                    label: 'Serienfeld: Nachname',
                    command: 'nachname',
                    icon: this.path + 'images/nachname.png',
                    toolbar: 'nachname'
                } );
        }
    } );
} )();