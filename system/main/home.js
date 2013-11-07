
loadPersonJS=false;

jQuery(document).ready(function() {
  $(".span4").sortable();
  $(".span4").droppable({
      accept: ".ct_whitebox",
      activeClass: "ui-state-hover",
      hoverClass: "well",
      drop: function( event, ui ) {
      // this => da wurde es eingedropt.
      //ui.draggable.html("genommenes");
      if (ui.draggable!=$(this)) {
        ui.draggable.removeAttr("style");
          $( this ).append("<li class=\"ct_whitebox\">"+ui.draggable.html()+"</li>");
          ui.draggable.remove();
/*                
                  $( this )
                      .addClass( "ui-state-highlight" )
                      .find( "label" )
                          .html( "Dropped!" );*/
        }
      }
  });  
  churchInterface.setAllDataLoaded(true);  
});
