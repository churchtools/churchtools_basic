
loadPersonJS=false;

function showFirstServiceRequests(max) {
  var counter=0;
  $("div.service-request").each(function() {
    counter=counter+1;
    if (counter<=max) {
      $(this).css("display", "");
    }
  });  
}

function renderServiceRequests() {
  var available=false;
  $("div.service-request").each(function() {
    available=true;
    if ($(this).attr("data-closed")==null) {
      var txt2="";
      txt2=txt2+'<a href="#" class="service-request request-confirm" id="zusagen">Zusagen</a> | ';              
      txt2=txt2+'<a href="#" class="service-request request-decline" id="absagen">Absagen</a>';            
      txt2=txt2+'&nbsp; &nbsp; <small>Anfrage von ';
      if ($(this).attr("data-modified-pid")!=null)
        txt2=txt2+'<a href="?q=churchdb#PersonView/searchEntry:#'+$(this).attr("data-modified-pid")+'">'+$(this).attr("data-modified-user")+'</a>';
      else
        txt2=txt2+$(this).attr("data-modified-user");
      txt2=txt2+"</small>";
      $(this).find("div.service-request-answer").html(txt2);
    }
  });
  if (!available) 
    $("li.service-request").remove();
  else {
    showFirstServiceRequests(3);
    addServiceRequestCallback();    
  }
}

function addServiceRequestCallback() {
  $("a.service-request").click(function() {
    
    var div_element=$(this).parents("div.service-request");
    var id=div_element.attr("data-id");
    div_element.attr("data-closed", "true");

    var txt='<div>';
    var obj=new Object();

    if ($(this).hasClass("request-confirm")) {
      txt=txt+"<p>Danke f&uuml;r die Zusage.&nbsp; ";
      txt=txt+"<a href=\"#\" class=\"service-request-undo\"><i>Zusage widerrufen</i></a>";
      obj.name=settings.user.vorname+" "+settings.user.name;
      obj.cdb_person_id=settings.user.id;
      obj.zugesagt_yn=1;
    }
    else if ($(this).hasClass("request-decline")) {
      txt=txt+"<p>Der Dienst wurde abgesagt.&nbsp; ";
      txt=txt+"<a href=\"#\" class=\"service-request-undo\"><i>Absage widerrufen</i></a>";
      obj.zugesagt_yn=0;
    }
    obj.func="updateEventService";
    obj.id=id;
    churchInterface.jsendWrite(obj, function(ok, data) {
      div_element.attr("data-new-id", data.eventservice.id);
    });
    if (obj.zugesagt_yn==0 || div_element.attr("data-comment-confirm")==1) {
      var form = new CC_Form();
      form.addInput({type:"medium", cssid:"reason", placeholder:"Bemerkung", controlgroup:false, htmlclass:"input-reason"});
      form.addHtml("&nbsp;");
      form.addButton({label:"Absenden", controlgroup:false, htmlclass:"submit"});
      txt=txt+form.render(false, "inline");
    }
    txt=txt+"</div>";
    var elem=$(this).parents("div.service-request-answer");    
    elem.animate({opacity: 0.0}, 200, function() {
      elem.html(txt);
      elem.find("input[id=reason]").focus();
      elem.find("div").hide();
      elem.find("div").fadeIn();
      elem.animate({opacity: 1}, 1);
      // Add comment
      elem.find("input.submit").click(function() {
        obj.func="addReasonToEventService"
        obj.reason=elem.find("input[id=reason]").val();
        obj.id=div_element.attr("data-new-id");
        churchInterface.jsendWrite(obj);
        div_element.remove();
        renderServiceRequests();
        return false;
      });
      // Undo last action
      elem.find("a.service-request-undo").click(function() {
        obj.func="undoLastUpdateEventService";
        obj.new_id=div_element.attr("data-new-id");
        obj.old_id=id;
        div_element.removeAttr("data-new-id");
        churchInterface.jsendWrite(obj);        
        $(this).parents("div.service-request").removeAttr("data-closed");
        renderServiceRequests();
        return false;
      });    
    });
    renderServiceRequests();
    return false;    
  });
}

function askMeYes(id) {
  var res=prompt("Wirklich verbindlich zusagen? Hier kannst Du noch eine Notiz angeben.","");
  if (res!=null) window.location.href="?q=home&zugesagt_yn=1&reason="+res+"&eventservice_id="+id;
} 


jQuery(document).ready(function() {
  //$(".span4").sortable();
  $(".span4").droppable({
      accept: ".ct_whitebox",
      activeClass: "ui-state-hover",
      hoverClass: "well",
      drop: function( event, ui ) {
      // this => da wurde es eingedrobj.
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
  churchInterface.setModulename("home");
  renderServiceRequests();
  renderForum();
});

function renderForum(selected, hint) {
  churchInterface.jsendRead({func:"getMasterData"}, function(ok, data) {
    if (ok) {
      if (data.mygroups==null || data.mygroups.length==0) {
        $("#cc_forum").parents("li").remove();
      } 
      else {
        var form = new CC_Form();
        form.addImage({src:"persons.png"});
        form.addHtml("&nbsp; ");
        form.addSelect({controlgroup:false, selected:selected, cssid: "groupid", htmlclass:"forum", freeoption:true,
            type:"medium", data:data.mygroups});
        if (hint!=null) form.addHtml("<br><br><i>"+hint+"</i>");
        if (selected!=null && selected!="") {
          form.addHtml('<div id="message" class="message well" contenteditable="true"></div>');
          form.addButton({label:"Absenden"});
        }
        $("#cc_forum").html(form.render(false, "vertical"));
        form_implantWysiwygEditor("message", false, true);

        $("#cc_forum div.message").focus();
        $("#cc_forum select.forum").change(function() {
          renderForum($(this).val());
        });
        $("#cc_forum input.btn").click(function() {
          var obj=form.getAllValsAsObject();
          obj.func="sendEmail";
          obj.message=CKEDITOR.instances.message.getData();
          CKEDITOR.instances.message.destroy();
          
          churchInterface.jsendWrite(obj, function(ok, data) {
            renderForum(null, "E-Mail wurde gesendet.");
          });
        });
      }
    }
  });
}

