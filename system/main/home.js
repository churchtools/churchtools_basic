
loadPersonJS=false;

jQuery(document).ready(function() {
  //jQuery(".ct_whitebox").draggable( {sn_ap: ".span4", revert: "invalid", stop: function(event, ui) {console.log(ui);} });
  $(".span4").sortable();
  //jQuery(".span4").disableSelection();
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
