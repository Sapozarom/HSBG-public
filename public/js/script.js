$(document).ready(function(){

  var i=0;

  $('.board-event').each(function(){
      i++;
      var newID=i;
      $(this).attr('id',newID);
      $(this).val(i);
      
  });

  $('.event-board').each(function(){
    var boardId='b' + $(this).prev('.board-event').attr('id');
    $(this).attr('id',boardId);
    
  });

});

$(document).ready(function(){
    var boardId = '#b' + $(".board-event:first").attr('id');
    $(".board-event:first").toggleClass("active-event", true);
    $("td.board-panel").html($(boardId).html());
});

$(document).ready(function(){
    $(".board-event").click(function(){
      var boardId = '#b'+ $(this).attr('id');
      $("td.board-panel").html($(boardId).html());
      $(".active-event").toggleClass("active-event", false);
      $(this).toggleClass("active-event", true);
      $('[data-toggle="tooltip"]').tooltip();
      // scrollToElement($(this).attr('id'));
      
    });
  });    
  
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  });

  $(function () {
    $(".next-event").click(function(){
      
    var activeId = $(".active-event").attr('id');
    var nextId = parseInt(activeId, 10) + 1;
    var nextSelector = '#'+ nextId;
    
    if ($(nextSelector).length) {
      $('#'+ activeId).toggleClass("active-event", false);
      $('#'+ nextId).toggleClass("active-event", true);
      $("td.board-panel").html($('#b'+ nextId).html());
      $('[data-toggle="tooltip"]').tooltip();
      scrollToElement(nextId);

    }

    });
  });

  $(function () {
    $(".previous-event").click(function(){
      
    var activeId = $(".active-event").attr('id');
    var nextId = parseInt(activeId, 10) -1;
    var nextSelector = '#'+ nextId;
    
    if ($(nextSelector).length) {
      $('#'+ activeId).toggleClass("active-event", false);
      $('#'+ nextId).toggleClass("active-event", true);
      $("td.board-panel").html($('#b'+ nextId).html());
      
      scrollToElement(nextId);
      $('[data-toggle="tooltip"]').tooltip();
    }

    });
  }); 

  // $(function () {
  //   $("#reg_form_submit").click(function(){
  //     // $("#reg_form_submit").hide();
  //     // $("#registration_form_email").hide();
  //     var veryfied = $("#registration_form_email").val();
  //     var lenght = veryfied.lenght;
  //     console.log(veryfied);
  //     console.log(lenght);
  //     //veryfied.hide();
  //     // console.log('asd');
  //     if (veryfied.value < 6) {
  //       //console.log(veryfied.length);
  //       console.log('asd');
  //     }
  //   });
  // });


function scrollToElement(id) {
  var distance = $('#'+ id).offset().top - $('.event-text:first').offset().top  
  var listHeight = ($('.event-text').length -1 ) * $(".event-text").innerHeight() ;
  var scrollPosition =  (distance / listHeight) * ($('td.event-panel').prop('scrollHeight') - $('td.event-panel').innerHeight());

  $(".event-panel").scrollTop(scrollPosition);

}

// function verifyPassword() {  
//   var inputField = document.getElementById("registration_form_email");
//   var pw = inputField.value;  
   
//  //minimum password length validation  
//   if(pw.length < 6) {  
//     //  document.getElementById("message").innerHTML = "**Password length must be atleast 8 characters"; 
//     console.log(pw);
//     inputField.setCustomValidity("krótkie");
//     return false;  
//   }  
  
// //maximum length of password validation  
//   if(pw.length > 15) {  
//      document.getElementById("message").innerHTML = "**Password length must not exceed 15 characters";  
//      return false;  
//   } else {  
//      alert("Password is correct");  
//   }  
// }  

function validatePassword(textbox) {
  if (textbox.value === '') {
      textbox.setCustomValidity('Pasword required');
  } else if (textbox.value.length < 6){
      textbox.setCustomValidity('Your password must contain at least 6 characters');
  } else {
     textbox.setCustomValidity('');
  }

  return true;
}

  